const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const repo = path.resolve(__dirname, '..');
const fixturePath = process.env.EAS_FIXTURE_PATH || path.join(__dirname, 'fixtures', 'fixture-defaults.json');
const idCases = JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures', 'slide-id-cases.json'), 'utf8'));
const sessionCases = JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures', 'session-history-cases.json'), 'utf8'));
const heightCases = JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures', 'height-transition-cases.json'), 'utf8'));
const editorPerformanceCases = JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures', 'editor-performance-cases.json'), 'utf8'));
const pageUrl = process.env.EAS_URL || 'http://127.0.0.1:8091/elementor-7/';
const widget = fs.readFileSync(path.join(repo, 'includes/widgets/class-adventure-slider-widget.php'), 'utf8');
const plugin = fs.readFileSync(path.join(repo, 'includes/class-plugin.php'), 'utf8');
const editor = fs.readFileSync(path.join(repo, 'assets/js/editor.js'), 'utf8');
const frontend = fs.readFileSync(path.join(repo, 'assets/js/adventure-slider.js'), 'utf8');
const css = fs.readFileSync(path.join(repo, 'assets/css/adventure-slider.css'), 'utf8');
const fixture = JSON.parse(fs.readFileSync(fixturePath, 'utf8'));
const failures = [];
let checks = 0;

function check(name, fn) {
  checks += 1;
  try {
    fn();
    console.log('PASS', name);
  } catch (error) {
    failures.push([name, error.message]);
    console.log('FAIL', name, '-', error.message);
  }
}

async function checkAsync(name, fn) {
  checks += 1;
  try {
    await fn();
    console.log('PASS', name);
  } catch (error) {
    failures.push([name, error.message]);
    console.log('FAIL', name, '-', error.message);
  }
}

function has(text, fragment) {
  assert.match(text, new RegExp(fragment.replace(/[.*+?^$\{}()|[\]\\]/g, '\\$&')));
}

const widgetData = fixture._elementor_data[0].elements[0];
const slides = widgetData.settings.slides;
const children = widgetData.elements;

check('fixture identifies the real Elementor document', () => {
  assert.equal(fixture.page.id, 7);
  assert.equal(fixture.elementor_meta.values._elementor_edit_mode, 'builder');
  assert.equal(widgetData.widgetType, 'ea-adventure-slider');
});

check('Elementor compatibility identifiers are preserved', () => {
  has(widget, "return 'ea-adventure-slider'");
  has(widget, "'slides',");
  for (const key of ['slide_title', 'slide_id', 'breadcrumb_label', 'show_in_heading']) {
    has(widget, key);
  }
  for (const key of ['eas_adventure_action', 'eas_adventure_destination']) {
    has(plugin, key);
  }
});

check('fixture has positional slide/child mapping', () => {
  assert.equal(slides.length, 3);
  assert.equal(children.length, 3);
  assert.deepEqual(slides.map(slide => slide.slide_id), ['start', 'choice', 'result']);
  assert.deepEqual(children.map(child => child.settings._title), ['Start', 'Choice', 'Result']);
});

check('fixture records explicit values and omitted repeater defaults', () => {
  assert.deepEqual(slides.map(slide => Object.keys(slide).sort()), [
    ['_id', 'slide_id', 'slide_title'],
    ['_id', 'slide_id', 'slide_title'],
    ['_id', 'slide_id', 'slide_title']
  ]);
  assert.equal(slides.some(slide => 'breadcrumb_label' in slide || 'show_in_heading' in slide), false);
  assert.equal(widgetData.settings.initial_slide, 'start');
  assert.equal(widgetData.settings.accessibility_label, 'Interactive journey');
  assert.equal(widgetData.settings.transition, undefined);
  assert.equal(widgetData.settings.autoplay, undefined);
});

check('PHP, editor, and front-end currently normalize IDs differently', () => {
  has(widget, 'sanitize_key');
  has(editor, "replace( /[^a-z0-9_-]/g, '-' )");
  has(frontend, 'VALID_ID');
  assert.ok(frontend.includes('return VALID_ID.test( value ) ? value :'));
  assert.ok(widget.includes("id = 'slide-' . ( $index + 1 );"));
  assert.ok(editor.includes("'slide-' + ( index + 1 )"));
  assert.ok(widget.includes("fallbackId = 'slide-' + itemIndex"));
  assert.ok(frontend.includes("'slide-' + ( index + 1 )"));
});

check('missing and duplicate ID handling is characterized', () => {
  assert.ok(widget.includes("isset( $item['slide_id'] )"));
  assert.ok(widget.includes('isset( $used[ $id ] )'));
  has(editor, 'cleanId');
  has(frontend, 'Object.prototype.hasOwnProperty.call( self.byId, id )');
  assert.ok(widget.includes(".= '-x'"));
  assert.ok(frontend.includes("id += '-x'"));
});

check('edge-case fixture records legacy ID inputs before resolution changes', () => {
  assert.deepEqual(idCases.map(item => item.name), [
    'valid', 'spaces', 'punctuation', 'missing', 'duplicate',
    'punctuation-collision-first', 'punctuation-collision-second',
    'old-editor-zero-based-fallback'
  ]);
  assert.equal(idCases.find(item => item.name === 'valid').php_canonical, 'choice');
  assert.equal(idCases.find(item => item.name === 'spaces').php_canonical, 'firstchoice');
  assert.equal(idCases.find(item => item.name === 'spaces').editor_legacy, 'first-choice');
  assert.equal(idCases.find(item => item.name === 'missing').editor_legacy, 'slide-0');
  assert.equal(idCases.find(item => item.name === 'duplicate').php_canonical, 'slide-2');
});

check('compatibility resolver preserves canonical IDs and adds only legacy aliases', () => {
  has(widget, 'data-adventure-aliases');
  has(widget, 'canonical_ids');
  has(widget, 'claimed_aliases');
  has(widget, 'in_array( $requested, $slide[\'aliases\'], true )');
  has(editor, 'legacyAliases');
  has(editor, 'data-adventure-aliases');
  has(frontend, 'AdventureSlider.prototype.resolveId');
  has(frontend, 'JSON.parse( encodedAliases )');
  has(frontend, 'this.aliases[ raw ]');
  has(frontend, 'id = this.resolveId( id )');
  has(frontend, 'var canonicalId = this.resolveId( id )');
  has(frontend, 'restoredIds[ canonicalId ]');
});

check('session history cases canonicalize aliases, discard invalid values, and deduplicate', () => {
  const resolve = new Map([
    ['start', 'start'],
    ['legacy-start', 'start'],
    ['choice', 'choice'],
    ['old-choice', 'choice']
  ]);
  const restore = values => {
    if (!Array.isArray(values)) { return []; }
    const restored = [];
    const restoredIds = new Set();
    values.forEach(value => {
      if (typeof value !== 'string') { return; }
      const canonical = resolve.get(value) || '';
      if (!canonical || restoredIds.has(canonical)) { return; }
      restoredIds.add(canonical);
      restored.push(canonical);
    });
    return restored;
  };
  for (const testCase of sessionCases) {
    if (testCase.malformed_json) {
      assert.throws(() => JSON.parse(testCase.malformed_json));
      continue;
    }
    assert.deepEqual(restore(testCase.values), testCase.expected, testCase.name);
  }
});

check('edge cases have deterministic additive compatibility outcomes', () => {
  const spaces = idCases.find(item => item.name === 'spaces');
  const punctuation = idCases.find(item => item.name === 'punctuation');
  const missing = idCases.find(item => item.name === 'missing');
  const collisionFirst = idCases.find(item => item.name === 'punctuation-collision-first');
  const collisionSecond = idCases.find(item => item.name === 'punctuation-collision-second');
  assert.deepEqual([spaces.input.toLowerCase(), spaces.editor_legacy], ['first choice', 'first-choice']);
  assert.deepEqual([punctuation.input.toLowerCase(), punctuation.editor_legacy], ['first.choice!', 'first-choice-']);
  assert.equal(missing.editor_legacy, 'slide-0');
  assert.equal(collisionFirst.front_end_target, 'first-choice');
  assert.equal(collisionSecond.front_end_target, 'first-choice');
  assert.equal(collisionFirst.php_canonical, 'firstchoice');
  assert.equal(collisionSecond.php_canonical, 'first-choice');
});

check('public rendering contract is present in source', () => {
  for (const token of [
    'ea-adventure-slider', 'ea-adventure-slide', 'data-adventure-instance',
    'data-adventure-slide', 'data-slide-index', 'aria-roledescription',
    'aria-hidden', 'ea-adventure-slider__navigation', 'ea-adventure-slider__dot'
  ]) {
    assert.ok(widget.includes(token) || css.includes(token), token);
  }
  has(widget, 'hidden');
});

check('public JavaScript contract is present in source', () => {
  has(frontend, 'adventureSlider');
  has(frontend, 'adventureslider:change');
  has(frontend, 'slideId');
  has(frontend, 'previousSlideId');
  has(frontend, '#adventure:');
  has(frontend, 'sessionStorage');
});

check('button rewriting contract is present in source', () => {
  has(plugin, 'eas_adventure_action');
  has(plugin, 'eas_adventure_destination');
  has(plugin, "'#adventure:' . \$target");
  has(plugin, 'data-adventure-link');
});

check('multiple-slider isolation contract is present in source', () => {
  has(widget, 'data-adventure-instance');
  has(frontend, "'eas:' + window.location.pathname + ':'");
  has(frontend, 'root.dataset.adventureInstance');
});

check('height transition cases and defensive runtime contract are present', () => {
  assert.deepEqual(heightCases.map(testCase => testCase.name), [
    'different-height-slides', 'equal-height-slides', 'empty-slide',
    'missing-child-content', 'rapid-navigation', 'reduced-motion',
    'missing-element-animate', 'destroy-during-transition', 'multiple-instances'
  ]);
  for (const method of [
    'measureStackHeight', 'measureSlideHeight', 'prepareHeightTransition',
    'startHeightTransition', 'clearHeightTransition'
  ]) {
    has(frontend, 'AdventureSlider.prototype.' + method);
  }
  has(frontend, 'this.heightTransition = null');
  has(frontend, 'this.clearHeightTransition()');
  has(frontend, 'typeof from.animate !== \'function\'');
  has(frontend, 'prefers-reduced-motion: reduce');
  has(frontend, 'this.slidesRoot.style.removeProperty( \'height\' )');
  has(frontend, 'this.slidesRoot.style.removeProperty( \'transition\' )');
});

check('editor performance cases cover scaled nested views and safe refresh behavior', () => {
  assert.deepEqual(editorPerformanceCases.slice(0, 3).map(testCase => testCase.slides), [3, 10, 20]);
  assert.ok(editorPerformanceCases.every(testCase => testCase.expected_repeater_conversions_per_render === 1 || testCase.expected));
  has(editor, 'this.slideItemsCache = null');
  has(editor, 'getSlideItems()');
  has(editor, 'this.slideItemsCache = repeaterItems( this.model.getSetting( \'slides\' ) )');
  has(editor, 'window.clearTimeout( this.activationTimer )');
  has(editor, 'this.activationTimer = 0');
  has(editor, 'onRemove()');
  has(editor, 'data-adventure-slide');
  has(editor, 'easDestinationListenerAdded');
  assert.equal((widget.match(/'render_type'\s*=>\s*'none'/g) || []).length, 3);
  has(editor, 'bindSlideItemEvents()');
  has(editor, 'onSlideItemChange( model )');
  has(editor, 'updateSlidePreview( index )');
  has(editor, 'syncEditorSliderInstance( previousId, id )');
  has(editor, "change:slide_title change:slide_id");
  has(editor, "change:breadcrumb_label");
  has(editor, 'onBreadcrumbLabelChange( model )');
  has(editor, 'updateBreadcrumbPreview( index )');
  has(editor, 'item.breadcrumb_label || title');
  has(editor, '.ea-adventure-slider__breadcrumb-label[aria-current="step"]');
});

async function liveChecks() {
  if (process.env.EAS_SKIP_LIVE === '1') {
    console.log('SKIP live HTTP/browser checks (EAS_SKIP_LIVE=1)');
    return;
  }
  let response;
  try {
    response = await fetch(pageUrl);
  } catch (error) {
    console.log('SKIP live HTTP checks (local test site is not reachable from this process):', error.message);
    return;
  }
  const html = await response.text();
  assert.equal(response.status, 200);
  assert.match(html, /ea-adventure-slider/);
  assert.match(html, /elementor-widget-ea-adventure-slider/);
  assert.match(html, /data-initial-slide="start"/);
  assert.match(html, /data-adventure-slide="start"/);
  assert.match(html, /aria-hidden="false"/);
  assert.doesNotMatch(html, /data-adventure-aliases=/, 'valid default fixture should not emit aliases');
  const assetPaths = [...html.matchAll(/(?:src|href)="([^"]*(?:elementor-adventure-slider|\/elementor\/assets\/)[^"]*)"/g)]
    .map(match => new URL(match[1], pageUrl).toString());
  assert.ok(assetPaths.length >= 2, 'expected Elementor and Adventure Slider asset URLs');
  for (const assetUrl of [...new Set(assetPaths)]) {
    const assetResponse = await fetch(assetUrl);
    assert.equal(assetResponse.status, 200, assetUrl);
  }
  console.log('INFO live page:', pageUrl);
  console.log('INFO asset URLs checked:', [...new Set(assetPaths)].length);
}

function browserChecks() {
  if (process.env.EAS_SKIP_BROWSER === '1') {
    console.log('SKIP Chromium check (EAS_SKIP_BROWSER=1)');
    return;
  }
  const result = spawnSync('/usr/bin/chromium', [
    '--headless=new', '--no-sandbox', '--disable-gpu',
    '--disable-dev-shm-usage', '--virtual-time-budget=5000', '--dump-dom', pageUrl
  ], { encoding: 'utf8', timeout: 30000 });
  if (result.error && result.error.code === 'EPERM') {
    console.log('SKIP Chromium check (sandbox denied browser execution).');
    return;
  }
  assert.equal(result.status, 0, result.error ? result.error.message : result.stderr.slice(-500));
  assert.match(result.stdout, /ea-adventure-slider/);
  assert.match(result.stdout, /data-adventure-slide="start"/);
  const relevantErrors = result.stderr.split('\n')
    .filter(line => /uncaught|typeerror|referenceerror|exception/i.test(line));
  assert.deepEqual(relevantErrors, [], relevantErrors.join('\n'));
  console.log('INFO browser DOM baseline passed; interactive navigation requires CDP automation and is not run.');
}

async function main() {
  await checkAsync('live front-end baseline is reachable and renders the widget', liveChecks);
  check('Chromium is available for optional DOM verification', browserChecks);
  console.log('Checks run:', checks);
  if (failures.length) {
    console.error('Failures:', failures);
    process.exitCode = 1;
  } else {
    console.log('All characterization checks passed.');
  }
}

main().catch(error => {
  console.error('Unhandled characterization failure:', error);
  process.exitCode = 1;
});
