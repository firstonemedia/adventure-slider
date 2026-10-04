( function () {
	'use strict';

	var instances = new WeakMap();
	var ACTION_PREFIX = '#adventure:';
	var VALID_ID = /^[a-z0-9_-]+$/;
	var INTERACTIVE_SELECTOR = 'a, button, input, select, textarea, [contenteditable="true"], [role="button"]';

	function boolData( value ) {
		return value === 'true';
	}

	function clampNumber( value, minimum, maximum, fallback ) {
		var number = Number( value );
		return Number.isFinite( number ) ? Math.min( maximum, Math.max( minimum, number ) ) : fallback;
	}

	function AdventureSlider( root ) {
		this.root = root;
		this.slidesRoot = root.querySelector( '.ea-adventure-slider__slides' );
		this.slides = this.slidesRoot ? Array.prototype.slice.call( this.slidesRoot.children ).filter( function ( child ) {
			return child.classList.contains( 'ea-adventure-slide' );
		} ) : [];
		this.byId = Object.create( null );
		this.aliases = Object.create( null );
		this.breadcrumbs = root.querySelector( '[data-adventure-breadcrumbs]' );
		this.breadcrumbList = this.breadcrumbs ? this.breadcrumbs.querySelector( '.ea-adventure-slider__breadcrumb-list' ) : null;
		this.history = [];
		this.currentId = '';
		this.animations = [];
		this.heightTransition = null;
		this.autoplayTimer = 0;
		this.pointerStart = null;
		this.destroyed = false;
		this.editorMode = !! ( window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode() );
		this.options = {
			initial: root.dataset.initialSlide || '',
			transition: [ 'slide', 'fade', 'none' ].indexOf( root.dataset.transition ) !== -1 ? root.dataset.transition : 'slide',
			speed: clampNumber( root.dataset.transitionSpeed, 0, 3000, 350 ),
			swipe: boolData( root.dataset.allowSwipe ),
			keyboard: boolData( root.dataset.keyboardNavigation ),
			loop: boolData( root.dataset.loop ),
			persist: boolData( root.dataset.persistPath ),
			autoplay: boolData( root.dataset.autoplay ),
			autoplayDelay: clampNumber( root.dataset.autoplayDelay, 1000, 60000, 5000 ),
			pauseOnHover: boolData( root.dataset.pauseOnHover )
		};
		this.storageKey = 'eas:' + window.location.pathname + ':' + ( root.dataset.adventureInstance || 'slider' );
		this.boundClick = this.onClick.bind( this );
		this.boundKeydown = this.onKeydown.bind( this );
		this.boundPointerDown = this.onPointerDown.bind( this );
		this.boundPointerUp = this.onPointerUp.bind( this );
		this.boundPause = this.pauseAutoplay.bind( this );
		this.boundResume = this.startAutoplay.bind( this );
		this.init();
	}

	AdventureSlider.prototype.cleanId = function ( value ) {
		value = String( value || '' ).toLowerCase();
		return VALID_ID.test( value ) ? value : '';
	};

	AdventureSlider.prototype.resolveId = function ( value ) {
		var raw = String( value || '' ).toLowerCase();
		var cleaned = this.cleanId( raw );
		if ( cleaned && this.byId[ cleaned ] ) {
			return cleaned;
		}
		return this.aliases[ raw ] || this.aliases[ cleaned ] || '';
	};

	AdventureSlider.prototype.init = function () {
		var self = this;
		if ( ! this.slides.length ) {
			return;
		}

		this.slides.forEach( function ( slide, index ) {
			var id = self.cleanId( slide.dataset.adventureSlide ) || 'slide-' + ( index + 1 );
			if ( Object.prototype.hasOwnProperty.call( self.byId, id ) ) {
				id = 'slide-' + ( index + 1 );
				while ( Object.prototype.hasOwnProperty.call( self.byId, id ) ) {
					id += '-x';
				}
			}
			slide.dataset.adventureSlide = id;
			self.byId[ id ] = slide;
		} );
		this.slides.forEach( function ( slide ) {
			var id = slide.dataset.adventureSlide;
			var encodedAliases = slide.getAttribute( 'data-adventure-aliases' );
			if ( ! encodedAliases ) { return; }
			try {
				var aliases = JSON.parse( encodedAliases );
				if ( ! Array.isArray( aliases ) ) { return; }
				aliases.forEach( function ( alias ) {
					alias = String( alias || '' ).toLowerCase();
					if ( alias && ! self.byId[ alias ] && ! self.aliases[ alias ] ) {
						self.aliases[ alias ] = id;
					}
				} );
			} catch ( error ) {
				// Malformed compatibility metadata must not stop normal navigation.
			}
		} );

		this.options.initial = this.resolveId( this.options.initial );
		if ( ! this.options.initial || ! this.byId[ this.options.initial ] ) {
			this.options.initial = this.slides[ 0 ].dataset.adventureSlide;
		}

		var restored = this.editorMode ? [] : this.restoreHistory();
		this.history = restored.length ? restored : [ this.options.initial ];
		this.currentId = this.history[ this.history.length - 1 ];
		this.setImmediate( this.currentId );

		this.root.addEventListener( 'click', this.boundClick );
		if ( this.options.keyboard ) {
			this.root.addEventListener( 'keydown', this.boundKeydown );
		}
		if ( this.options.swipe && ! this.editorMode ) {
			this.root.classList.add( 'is-swipe-enabled' );
			this.slidesRoot.addEventListener( 'pointerdown', this.boundPointerDown, { passive: true } );
			this.slidesRoot.addEventListener( 'pointerup', this.boundPointerUp, { passive: true } );
		}
		if ( this.options.pauseOnHover ) {
			this.root.addEventListener( 'mouseenter', this.boundPause );
			this.root.addEventListener( 'mouseleave', this.boundResume );
			this.root.addEventListener( 'focusin', this.boundPause );
			this.root.addEventListener( 'focusout', this.boundResume );
		}
		this.startAutoplay();
	};

	AdventureSlider.prototype.restoreHistory = function () {
		if ( ! this.options.persist ) {
			return [];
		}
		try {
			var value = JSON.parse( window.sessionStorage.getItem( this.storageKey ) || '[]' );
			if ( ! Array.isArray( value ) || value.length > 100 ) {
				return [];
			}
			var restored = [];
			var restoredIds = Object.create( null );
			value.forEach( function ( id ) {
				if ( typeof id !== 'string' ) { return; }
				var canonicalId = this.resolveId( id );
				if ( ! canonicalId || restoredIds[ canonicalId ] ) { return; }
				restoredIds[ canonicalId ] = true;
				restored.push( canonicalId );
			}, this );
			return restored;
		} catch ( error ) {
			return [];
		}
	};

	AdventureSlider.prototype.saveHistory = function () {
		if ( ! this.options.persist || this.editorMode ) {
			return;
		}
		try {
			window.sessionStorage.setItem( this.storageKey, JSON.stringify( this.history.slice( -100 ) ) );
		} catch ( error ) {
			// Storage can be unavailable in private modes. The slider still works in memory.
		}
	};

	AdventureSlider.prototype.setImmediate = function ( id ) {
		var target = this.byId[ id ];
		if ( ! target ) {
			return;
		}
		this.slides.forEach( function ( slide ) {
			var active = slide === target;
			slide.hidden = ! active;
			slide.classList.toggle( 'is-active', active );
			slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
		} );
		this.currentId = id;
		this.updateUi();
	};

	AdventureSlider.prototype.navigate = function ( id, options ) {
		options = options || {};
		id = this.resolveId( id );
		if ( ! id || ! this.byId[ id ] || id === this.currentId || this.destroyed ) {
			return false;
		}

		var from = this.byId[ this.currentId ];
		var to = this.byId[ id ];
		var direction = options.direction || 'forward';
		if ( options.push !== false ) {
			this.history.push( id );
		}
		this.currentId = id;
		this.saveHistory();
		this.cancelAnimations();

		var reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( ! from || this.options.transition === 'none' || this.options.speed === 0 || reducedMotion || this.editorMode ) {
			this.finishTransition( from, to, options );
			return true;
		}
		this.prepareHeightTransition( from, to, reducedMotion );

		to.hidden = false;
		to.setAttribute( 'aria-hidden', 'false' );
		this.startHeightTransition();
		from.setAttribute( 'aria-hidden', 'true' );
		this.slidesRoot.classList.add( 'is-transitioning' );
		var distance = getComputedStyle( this.root ).getPropertyValue( '--ea-transition-distance' ).trim() || '12%';
		var sign = direction === 'back' ? -1 : 1;
		var fromFrames;
		var toFrames;

		if ( this.options.transition === 'fade' ) {
			fromFrames = [ { opacity: 1 }, { opacity: 0 } ];
			toFrames = [ { opacity: 0 }, { opacity: 1 } ];
		} else {
			fromFrames = [
				{ opacity: 1, transform: 'translateX(0)' },
				{ opacity: 0, transform: 'translateX(calc(' + distance + ' * ' + ( -sign ) + '))' }
			];
			toFrames = [
				{ opacity: 0, transform: 'translateX(calc(' + distance + ' * ' + sign + '))' },
				{ opacity: 1, transform: 'translateX(0)' }
			];
		}

		var timing = { duration: this.options.speed, easing: 'cubic-bezier(.22,.61,.36,1)', fill: 'both' };
		if ( typeof from.animate !== 'function' || typeof to.animate !== 'function' ) {
			this.finishTransition( from, to, options );
			return true;
		}

		var fromAnimation = from.animate( fromFrames, timing );
		var toAnimation = to.animate( toFrames, timing );
		this.animations = [ fromAnimation, toAnimation ];
		var self = this;
		Promise.allSettled( [ fromAnimation.finished, toAnimation.finished ] ).then( function () {
			if ( self.currentId === id && ! self.destroyed ) {
				self.finishTransition( from, to, options );
			}
		} );
		return true;
	};

	AdventureSlider.prototype.finishTransition = function ( from, to, options ) {
		this.cancelAnimations();
		this.slides.forEach( function ( slide ) {
			var active = slide === to;
			slide.hidden = ! active;
			slide.classList.toggle( 'is-active', active );
			slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			slide.style.removeProperty( 'opacity' );
			slide.style.removeProperty( 'transform' );
		} );
		this.slidesRoot.classList.remove( 'is-transitioning' );
		this.clearHeightTransition();
		this.updateUi();
		if ( options.focus !== false && ! this.editorMode ) {
			this.focusSlide( to );
		}
		this.root.dispatchEvent( new CustomEvent( 'adventureslider:change', {
			bubbles: true,
			detail: { slideId: this.currentId, previousSlideId: from ? from.dataset.adventureSlide : '' }
		} ) );
		this.startAutoplay();
	};

	AdventureSlider.prototype.measureStackHeight = function () {
		if ( ! this.slidesRoot ) {
			return 0;
		}
		var rect = typeof this.slidesRoot.getBoundingClientRect === 'function' ? this.slidesRoot.getBoundingClientRect() : null;
		var height = rect && Number.isFinite( rect.height ) ? rect.height : this.slidesRoot.offsetHeight;
		return Number.isFinite( height ) ? Math.max( 0, height ) : 0;
	};

	AdventureSlider.prototype.measureSlideHeight = function ( slide ) {
		if ( ! this.slidesRoot || ! slide ) {
			return 0;
		}
		var hiddenStates = this.slides.map( function ( item ) {
			return { slide: item, hidden: item.hidden };
		} );
		this.slides.forEach( function ( item ) { item.hidden = true; } );
		slide.hidden = false;
		var height = this.measureStackHeight();
		hiddenStates.forEach( function ( state ) { state.slide.hidden = state.hidden; } );
		return height;
	};

	AdventureSlider.prototype.prepareHeightTransition = function ( from, to, reducedMotion ) {
		this.clearHeightTransition();
		if ( reducedMotion || ! this.slidesRoot || ! from || ! to || from === to ) {
			return;
		}
		var fromHeight = this.measureSlideHeight( from );
		var toHeight = this.measureSlideHeight( to );
		if ( fromHeight === toHeight ) {
			return;
		}
		this.heightTransition = {
			fromHeight: fromHeight,
			toHeight: toHeight,
			previousHeight: this.slidesRoot.style.height,
			previousTransition: this.slidesRoot.style.transition
		};
		this.slidesRoot.style.height = fromHeight + 'px';
	};

	AdventureSlider.prototype.startHeightTransition = function () {
		var transition = this.heightTransition;
		if ( ! transition || ! this.slidesRoot ) {
			return;
		}
		this.slidesRoot.style.transition = 'height ' + this.options.speed + 'ms cubic-bezier(.22,.61,.36,1)';
		void this.slidesRoot.offsetHeight;
		if ( this.heightTransition === transition ) {
			this.slidesRoot.style.height = transition.toHeight + 'px';
		}
	};

	AdventureSlider.prototype.clearHeightTransition = function () {
		var transition = this.heightTransition;
		if ( ! transition || ! this.slidesRoot ) {
			return;
		}
		if ( transition.previousHeight ) {
			this.slidesRoot.style.height = transition.previousHeight;
		} else {
			this.slidesRoot.style.removeProperty( 'height' );
		}
		if ( transition.previousTransition ) {
			this.slidesRoot.style.transition = transition.previousTransition;
		} else {
			this.slidesRoot.style.removeProperty( 'transition' );
		}
		this.heightTransition = null;
	};

	AdventureSlider.prototype.cancelAnimations = function () {
		this.animations.forEach( function ( animation ) {
			try { animation.cancel(); } catch ( error ) { /* No-op. */ }
		} );
		this.animations = [];
	};

	AdventureSlider.prototype.focusSlide = function ( slide ) {
		if ( ! slide ) {
			return;
		}
		var focusTarget = slide.querySelector( '[data-adventure-autofocus], h1, h2, h3, h4, h5, h6, button, a[href], input, select, textarea' ) || slide;
		var addedTabindex = ! focusTarget.hasAttribute( 'tabindex' ) && ! focusTarget.matches( 'button, a[href], input, select, textarea' );
		if ( addedTabindex ) {
			focusTarget.setAttribute( 'tabindex', '-1' );
			focusTarget.addEventListener( 'blur', function cleanup() {
				focusTarget.removeAttribute( 'tabindex' );
				focusTarget.removeEventListener( 'blur', cleanup );
			}, { once: true } );
		}
		try { focusTarget.focus( { preventScroll: true } ); } catch ( error ) { focusTarget.focus(); }
	};

	AdventureSlider.prototype.updateUi = function () {
		var index = this.slides.indexOf( this.byId[ this.currentId ] );
		var total = this.slides.length;
		this.root.querySelectorAll( '[data-adventure-target]' ).forEach( function ( button ) {
			button.setAttribute( 'aria-current', button.dataset.adventureTarget === this.currentId ? 'true' : 'false' );
		}, this );

		var back = this.root.querySelector( '[data-adventure-action="back"]' );
		if ( back ) {
			back.disabled = this.history.length < 2;
		}
		var restart = this.root.querySelector( '[data-adventure-action="restart"]' );
		if ( restart ) {
			restart.disabled = this.currentId === this.options.initial && this.history.length === 1;
		}
		if ( ! this.options.loop ) {
			var previous = this.root.querySelector( '[data-adventure-action="previous"]' );
			var next = this.root.querySelector( '[data-adventure-action="next"]' );
			if ( previous ) { previous.disabled = index <= 0; }
			if ( next ) { next.disabled = index >= total - 1; }
		}

		var track = this.root.querySelector( '.ea-adventure-slider__progress-track' );
		var bar = this.root.querySelector( '.ea-adventure-slider__progress-bar' );
		var text = this.root.querySelector( '.ea-adventure-slider__progress-text' );
		if ( track ) {
			track.setAttribute( 'aria-valuemin', '1' );
			track.setAttribute( 'aria-valuemax', String( total ) );
			track.setAttribute( 'aria-valuenow', String( index + 1 ) );
		}
		if ( bar ) { bar.style.width = ( ( index + 1 ) / total * 100 ) + '%'; }
		if ( text ) { text.textContent = ( index + 1 ) + ' / ' + total; }
		var live = this.root.querySelector( '.ea-adventure-slider__live' );
		var current = this.byId[ this.currentId ];
		if ( live && current ) { live.textContent = current.dataset.slideTitle || 'Slide ' + ( index + 1 ); }
		this.renderBreadcrumbs();
	};

	AdventureSlider.prototype.renderBreadcrumbs = function () {
		if ( ! this.breadcrumbList ) {
			return;
		}
		while ( this.breadcrumbList.firstChild ) {
			this.breadcrumbList.removeChild( this.breadcrumbList.firstChild );
		}
		var clickable = this.breadcrumbs.dataset.clickable === 'true';
		var separatorText = this.breadcrumbs.dataset.separator || '›';
		var lastIndex = this.history.length - 1;
		this.history.forEach( function ( id, historyIndex ) {
			var slide = this.byId[ id ];
			if ( ! slide ) {
				return;
			}
			var item = document.createElement( 'li' );
			item.className = 'ea-adventure-slider__breadcrumb-item';
			var label;
			if ( clickable && historyIndex < lastIndex ) {
				label = document.createElement( 'button' );
				label.type = 'button';
				label.dataset.adventureBreadcrumbIndex = String( historyIndex );
			} else {
				label = document.createElement( 'span' );
			}
			label.className = 'ea-adventure-slider__breadcrumb-label';
			label.textContent = slide.dataset.breadcrumbTitle || slide.dataset.slideTitle || id;
			if ( historyIndex === lastIndex ) {
				label.setAttribute( 'aria-current', 'step' );
			}
			item.appendChild( label );
			if ( historyIndex < lastIndex ) {
				var separator = document.createElement( 'span' );
				separator.className = 'ea-adventure-slider__breadcrumb-separator';
				separator.setAttribute( 'aria-hidden', 'true' );
				separator.textContent = separatorText;
				item.appendChild( separator );
			}
			this.breadcrumbList.appendChild( item );
		}, this );
	};

	AdventureSlider.prototype.onClick = function ( event ) {
		var editorButton = event.target.closest( '[data-adventure-editor-slide]' );
		if ( editorButton && this.editorMode && this.root.contains( editorButton ) ) {
			event.preventDefault();
			this.navigate( editorButton.dataset.adventureEditorSlide, { focus: false } );
			return;
		}

		var breadcrumb = event.target.closest( '[data-adventure-breadcrumb-index]' );
		if ( breadcrumb && this.root.contains( breadcrumb ) ) {
			event.preventDefault();
			var historyIndex = Number( breadcrumb.dataset.adventureBreadcrumbIndex );
			if ( Number.isInteger( historyIndex ) && historyIndex >= 0 && historyIndex < this.history.length - 1 ) {
				var breadcrumbTarget = this.history[ historyIndex ];
				this.pauseAutoplay();
				this.history = this.history.slice( 0, historyIndex + 1 );
				this.saveHistory();
				this.navigate( breadcrumbTarget, { push: false, direction: 'back' } );
			}
			return;
		}

		var directTarget = event.target.closest( '[data-adventure-target]' );
		if ( directTarget && this.root.contains( directTarget ) ) {
			event.preventDefault();
			this.manualNavigate( directTarget.dataset.adventureTarget );
			return;
		}

		var actionButton = event.target.closest( '[data-adventure-action]' );
		if ( actionButton && this.root.contains( actionButton ) ) {
			event.preventDefault();
			this.runAction( actionButton.dataset.adventureAction );
			return;
		}

		var link = event.target.closest( 'a[href]' );
		if ( ! link || ! this.root.contains( link ) ) {
			return;
		}
		var href = link.getAttribute( 'href' ) || '';
		if ( href.indexOf( ACTION_PREFIX ) !== 0 ) {
			return;
		}
		event.preventDefault();
		if ( this.editorMode ) {
			return;
		}
		this.runAction( href.slice( ACTION_PREFIX.length ) );
	};

	AdventureSlider.prototype.runAction = function ( action ) {
		action = String( action || '' ).toLowerCase();
		if ( ! action ) { return; }
		if ( action === 'back' ) { this.back(); return; }
		if ( action === 'restart' ) { this.restart(); return; }
		if ( action === 'next' ) { this.relative( 1 ); return; }
		if ( action === 'previous' ) { this.relative( -1 ); return; }
		this.manualNavigate( action );
	};

	AdventureSlider.prototype.manualNavigate = function ( id ) {
		this.pauseAutoplay();
		this.navigate( id, { direction: 'forward' } );
	};

	AdventureSlider.prototype.back = function () {
		if ( this.history.length < 2 ) { return; }
		this.pauseAutoplay();
		this.history.pop();
		var target = this.history[ this.history.length - 1 ];
		this.saveHistory();
		this.navigate( target, { push: false, direction: 'back' } );
	};

	AdventureSlider.prototype.restart = function () {
		this.pauseAutoplay();
		this.history = [ this.options.initial ];
		this.saveHistory();
		if ( this.currentId === this.options.initial ) {
			this.setImmediate( this.options.initial );
			return;
		}
		this.navigate( this.options.initial, { push: false, direction: 'back' } );
	};

	AdventureSlider.prototype.relative = function ( delta ) {
		var index = this.slides.indexOf( this.byId[ this.currentId ] );
		var nextIndex = index + delta;
		if ( this.options.loop ) {
			nextIndex = ( nextIndex + this.slides.length ) % this.slides.length;
		}
		if ( nextIndex < 0 || nextIndex >= this.slides.length ) { return; }
		this.pauseAutoplay();
		this.navigate( this.slides[ nextIndex ].dataset.adventureSlide, { direction: delta < 0 ? 'back' : 'forward' } );
	};

	AdventureSlider.prototype.onKeydown = function ( event ) {
		if ( event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.target.matches( 'input, select, textarea, [contenteditable="true"]' ) ) {
			return;
		}
		if ( event.key === 'ArrowRight' ) { event.preventDefault(); this.relative( 1 ); }
		if ( event.key === 'ArrowLeft' ) { event.preventDefault(); this.relative( -1 ); }
	};

	AdventureSlider.prototype.onPointerDown = function ( event ) {
		if ( event.pointerType === 'mouse' || event.target.closest( INTERACTIVE_SELECTOR ) ) {
			this.pointerStart = null;
			return;
		}
		this.pointerStart = { x: event.clientX, y: event.clientY, time: Date.now() };
	};

	AdventureSlider.prototype.onPointerUp = function ( event ) {
		if ( ! this.pointerStart ) { return; }
		var dx = event.clientX - this.pointerStart.x;
		var dy = event.clientY - this.pointerStart.y;
		var elapsed = Date.now() - this.pointerStart.time;
		this.pointerStart = null;
		if ( elapsed <= 700 && Math.abs( dx ) >= 50 && Math.abs( dx ) > Math.abs( dy ) * 1.25 ) {
			this.relative( dx < 0 ? 1 : -1 );
		}
	};

	AdventureSlider.prototype.startAutoplay = function () {
		this.pauseAutoplay();
		if ( ! this.options.autoplay || this.editorMode || this.destroyed ) { return; }
		var self = this;
		this.autoplayTimer = window.setTimeout( function () {
			var before = self.currentId;
			self.relative( 1 );
			if ( before === self.currentId && ! self.options.loop ) {
				self.pauseAutoplay();
			}
		}, this.options.autoplayDelay );
	};

	AdventureSlider.prototype.pauseAutoplay = function () {
		if ( this.autoplayTimer ) {
			window.clearTimeout( this.autoplayTimer );
			this.autoplayTimer = 0;
		}
	};

	AdventureSlider.prototype.destroy = function () {
		this.destroyed = true;
		this.pauseAutoplay();
		this.cancelAnimations();
		this.clearHeightTransition();
		this.root.removeEventListener( 'click', this.boundClick );
		this.root.removeEventListener( 'keydown', this.boundKeydown );
		if ( this.slidesRoot ) {
			this.slidesRoot.removeEventListener( 'pointerdown', this.boundPointerDown );
			this.slidesRoot.removeEventListener( 'pointerup', this.boundPointerUp );
		}
		this.root.removeEventListener( 'mouseenter', this.boundPause );
		this.root.removeEventListener( 'mouseleave', this.boundResume );
		this.root.removeEventListener( 'focusin', this.boundPause );
		this.root.removeEventListener( 'focusout', this.boundResume );
	};

	function mount( scope ) {
		var root = scope.querySelector ? scope.querySelector( '.ea-adventure-slider' ) : null;
		if ( ! root ) { return; }
		var existing = instances.get( root );
		if ( existing ) { existing.destroy(); }
		var instance = new AdventureSlider( root );
		instances.set( root, instance );
		root.adventureSlider = instance;
	}

	var hookRegistered = false;
	function registerElementorHook() {
		if ( hookRegistered || ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/ea-adventure-slider.default', function ( $scope ) {
			mount( $scope[ 0 ] );
		} );
		hookRegistered = true;
	}

	window.addEventListener( 'elementor/frontend/init', registerElementorHook );
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', registerElementorHook );
	}
	registerElementorHook();
}() );
