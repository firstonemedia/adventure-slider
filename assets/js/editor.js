( function () {
	'use strict';

	var registered = false;
	var panelHooksRegistered = false;

	function itemToObject( item ) {
		if ( ! item ) { return {}; }
		if ( item.attributes ) { return item.attributes; }
		if ( 'function' === typeof item.toJSON ) { return item.toJSON(); }
		return item;
	}

	function repeaterItems( value ) {
		if ( Array.isArray( value ) ) {
			return value.map( itemToObject );
		}
		if ( value && Array.isArray( value.models ) ) {
			return value.models.map( itemToObject );
		}
		if ( value && 'function' === typeof value.toJSON ) {
			var json = value.toJSON();
			return Array.isArray( json ) ? json.map( itemToObject ) : [];
		}
		return [];
	}

	function cleanId( value, fallback ) {
		value = String( value || fallback || '' ).toLowerCase().replace( /[^a-z0-9_-]/g, '-' );
		return value || fallback || '';
	}

	function legacyAliases( value, canonical, index ) {
		var raw = String( value || '' ).toLowerCase();
		var aliases = [];
		[ raw, raw.replace( /[^a-z0-9_-]/g, '' ) ].forEach( function ( alias ) {
			if ( alias && alias !== canonical && aliases.indexOf( alias ) === -1 ) {
				aliases.push( alias );
			}
		} );
		if ( ! raw ) {
			var oldFallback = 'slide-' + index;
			if ( oldFallback !== canonical ) { aliases.push( oldFallback ); }
		}
		return aliases;
	}

	function updateEditorStatus( root, slideElement ) {
		var status = root.querySelector( '[data-adventure-editor-status]' );
		if ( ! status || ! slideElement ) { return; }
		var title = slideElement.dataset.slideTitle || slideElement.dataset.adventureSlide || 'Slide';
		var id = slideElement.dataset.adventureSlide || '';
		status.textContent = 'Editing slide: ' + title + ( id ? ' (ID: ' + id + ')' : '' );
	}

	function revealSlideElement( slideElement, resetHistory ) {
		if ( ! slideElement ) { return; }
		var root = slideElement.closest( '.ea-adventure-slider' );
		if ( ! root ) { return; }
		var slides = root.querySelectorAll( '.ea-adventure-slider__slides > .ea-adventure-slide' );
		var activeId = slideElement.dataset.adventureSlide || '';
		var instance = root.adventureSlider;
		activeId = instance && instance.resolveId ? instance.resolveId( activeId ) || activeId : activeId;

		if ( instance && activeId && instance.byId && instance.byId[ activeId ] ) {
			if ( resetHistory ) {
				instance.history = [ activeId ];
				instance.setImmediate( activeId );
			} else {
				instance.navigate( activeId, { focus: false } );
			}
		}

		Array.prototype.forEach.call( slides, function ( slide ) {
			var active = slide === slideElement;
			slide.hidden = ! active;
			slide.classList.toggle( 'is-active', active );
			slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
		} );
		root.querySelectorAll( '[data-adventure-target]' ).forEach( function ( button ) {
			button.setAttribute( 'aria-current', button.dataset.adventureTarget === activeId ? 'true' : 'false' );
		} );
		updateEditorStatus( root, slideElement );
	}

	function registerAdventureSliderType() {
		if ( registered || ! window.elementor || ! window.$e ) { return; }
		var nestedComponent = $e.components.get( 'nested-elements' );
		if ( ! nestedComponent || ! nestedComponent.exports || ! nestedComponent.exports.NestedView ) { return; }

		class AdventureSliderView extends nestedComponent.exports.NestedView {
			events() {
				var inherited = 'function' === typeof super.events ? super.events() : {};
				return Object.assign( {}, inherited, {
					'click [data-adventure-editor-slide]': 'onHeadingButtonClick'
				} );
			}

			onRender() {
				super.onRender();
				var editSettings = this.model.get( 'editSettings' );
				if ( editSettings && 'function' === typeof this.listenTo ) {
					this.stopListening( editSettings, 'change:activeItemIndex', this.onActiveItemIndexChange );
					this.listenTo( editSettings, 'change:activeItemIndex', this.onActiveItemIndexChange );
				}
				window.setTimeout( function () {
					var requested = Number( editSettings && editSettings.get( 'activeItemIndex' ) ) - 1;
					this.activateSlideByIndex( Number.isInteger( requested ) && requested >= 0 ? requested : 0, true );
				}.bind( this ), 0 );
			}

			getChildIndex( childView ) {
				var elements = this.model.get( 'elements' );
				if ( elements && 'function' === typeof elements.indexOf ) {
					var found = elements.indexOf( childView.model );
					if ( found >= 0 ) { return found; }
				}
				var fallback = Number( childView.model && childView.model.attributes && childView.model.attributes.dataIndex );
				return Number.isInteger( fallback ) && fallback >= 0 ? fallback : 0;
			}

			getSlideItem( index ) {
				return repeaterItems( this.model.getSetting( 'slides' ) )[ index ] || {};
			}

			onAddChild( childView ) {
				var index = this.getChildIndex( childView );
				var items = repeaterItems( this.model.getSetting( 'slides' ) );
				var item = items[ index ] || {};
				var fallback = 'slide-' + ( index + 1 );
				var id = cleanId( item.slide_id, fallback );
				var aliases = legacyAliases( item.slide_id, id, index );
				var title = String( item.slide_title || fallback );
				var breadcrumb = String( item.breadcrumb_label || title );
				var initialId = cleanId( this.model.getSetting( 'initial_slide' ), items[ 0 ] && items[ 0 ].slide_id ? items[ 0 ].slide_id : 'slide-1' );
				var isActive = id === initialId || ( ! initialId && index === 0 );
				childView.$el.addClass( 'ea-adventure-slide' );
				childView.$el.toggleClass( 'is-active', isActive );
				childView.$el.attr( {
					'data-adventure-slide': id,
					'data-slide-index': index,
					'data-slide-title': title,
					'data-breadcrumb-title': breadcrumb,
					'role': 'group',
					'aria-roledescription': 'slide',
					'aria-label': ( index + 1 ) + ' of ' + items.length + ': ' + title,
					'aria-hidden': isActive ? 'false' : 'true',
					'data-adventure-aliases': aliases.length ? JSON.stringify( aliases ) : null
				} );
				if ( ! isActive ) {
					childView.$el.attr( 'hidden', 'hidden' );
				} else {
					childView.$el.removeAttr( 'hidden' );
				}
			}

			activateSlideByIndex( index, resetHistory ) {
				var slide = this.$el.find( '.ea-adventure-slider__slides > .ea-adventure-slide' ).get( index );
				revealSlideElement( slide, resetHistory );
				return slide;
			}

			onActiveItemIndexChange( settings, value ) {
				var index = Number( value ) - 1;
				if ( Number.isInteger( index ) && index >= 0 ) {
					this.activateSlideByIndex( index, true );
				}
			}

			selectRepeaterItem( index ) {
				var editSettings = this.model.get( 'editSettings' );
				if ( editSettings && editSettings.get( 'activeItemIndex' ) !== index + 1 ) {
					editSettings.set( 'activeItemIndex', index + 1 );
				}
			}

			onHeadingButtonClick( event ) {
				event.preventDefault();
				event.stopPropagation();
				var index = Number( event.currentTarget.dataset.slideIndex );
				if ( ! Number.isInteger( index ) || index < 0 ) {
					index = this.$el.find( '[data-adventure-editor-slide]' ).index( event.currentTarget );
				}
				this.selectRepeaterItem( index );
				var slide = this.activateSlideByIndex( index, false );
				var elements = this.model.get( 'elements' );
				var childModel = elements && 'function' === typeof elements.at ? elements.at( index ) : null;
				var childView = childModel && this.children && 'function' === typeof this.children.findByModel ? this.children.findByModel( childModel ) : null;
				if ( childModel && childView ) {
					$e.run( 'panel/editor/open', { model: childModel, view: childView } );
				}
			}
		}

		class AdventureSliderType extends elementor.modules.elements.types.NestedElementBase {
			getType() { return 'ea-adventure-slider'; }
			getView() { return AdventureSliderView; }
		}
		elementor.elementsManager.registerElementType( new AdventureSliderType() );
		registered = true;
	}

	function addDestinationChoices( panel, model, view ) {
		window.setTimeout( function () {
			var panelRoot = panel && panel.$el ? panel.$el : ( elementor.$panel || null );
			var sliderRoot = view && view.$el ? view.$el.closest( '.elementor-widget-ea-adventure-slider' ) : null;
			if ( ! panelRoot || ! sliderRoot || ! sliderRoot.length ) { return; }
			var control = panelRoot.find( '.elementor-control-eas_adventure_destination' );
			var input = control.find( 'input[data-setting="eas_adventure_destination"]' );
			if ( ! control.length || ! input.length ) { return; }
			control.find( '.eas-destination-options' ).remove();
			var choices = document.createElement( 'div' );
			choices.className = 'eas-destination-options';
			var title = document.createElement( 'div' );
			title.className = 'eas-destination-options__title';
			title.textContent = 'Choose a slide';
			choices.appendChild( title );
			sliderRoot.find( '.ea-adventure-slider__slides > .ea-adventure-slide' ).each( function () {
				var id = this.dataset.adventureSlide || '';
				if ( ! id ) { return; }
				var button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'eas-destination-options__choice';
				var selected = input.val() === id;
				button.classList.toggle( 'is-selected', selected );
				button.setAttribute( 'aria-pressed', selected ? 'true' : 'false' );
				button.textContent = ( this.dataset.slideTitle || id ) + ' → ' + id + ( selected ? ' (selected)' : '' );
				button.addEventListener( 'click', function () {
					input.val( id ).trigger( 'input' ).trigger( 'change' );
					addDestinationChoices( panel, model, view );
				} );
				choices.appendChild( button );
			} );
			control.get( 0 ).appendChild( choices );
		}, 0 );

		var settings = model && model.get ? model.get( 'settings' ) : null;
		if ( view && settings && ! view.easDestinationListenerAdded && 'function' === typeof view.listenTo ) {
			view.easDestinationListenerAdded = true;
			view.listenTo( settings, 'change:eas_adventure_action', function () {
				addDestinationChoices( panel, model, view );
			} );
		}
	}

	function revealEditedElement( panel, model, view ) {
		if ( ! view || ! view.$el ) { return; }
		var slide = view.$el.closest( '.ea-adventure-slide' ).get( 0 );
		if ( ! slide ) { return; }
		var root = slide.closest( '.ea-adventure-slider' );
		var slides = root ? root.querySelectorAll( '.ea-adventure-slider__slides > .ea-adventure-slide' ) : [];
		var index = Array.prototype.indexOf.call( slides, slide );
		var widget = root && root.closest( '.elementor-widget-ea-adventure-slider' );
		var widgetId = widget && widget.dataset.id;
		var widgetContainer = widgetId && window.elementor && elementor.getContainer ? elementor.getContainer( widgetId ) : null;
		var editSettings = widgetContainer && widgetContainer.model ? widgetContainer.model.get( 'editSettings' ) : null;
		if ( editSettings && index >= 0 ) {
			editSettings.set( 'activeItemIndex', index + 1 );
		}
		revealSlideElement( slide, true );
	}

	function registerPanelHooks() {
		if ( panelHooksRegistered || ! window.elementor || ! elementor.hooks ) { return; }
		elementor.hooks.addAction( 'panel/open_editor/widget/button', addDestinationChoices );
		elementor.hooks.addAction( 'panel/open_editor/container', revealEditedElement );
		elementor.hooks.addAction( 'panel/open_editor/widget', revealEditedElement );
		panelHooksRegistered = true;
	}

	function boot() {
		registerAdventureSliderType();
		registerPanelHooks();
	}

	if ( window.elementorCommon && elementorCommon.elements && elementorCommon.elements.$window ) {
		elementorCommon.elements.$window.on( 'elementor/nested-element-type-loaded elementor/init-components', boot );
	}
	if ( window.elementor && elementor.modules && elementor.modules.nestedElements && 'function' === typeof elementor.modules.nestedElements.then ) {
		elementor.modules.nestedElements.then( boot );
	}
	boot();
}() );
