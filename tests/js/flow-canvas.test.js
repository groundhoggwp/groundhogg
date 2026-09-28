/**
 * The JS canvas draws the same markup the server's sortable_item() does
 *
 * The fixtures are written by Flow_Canvas_Tests in the PHPUnit suite, run it with GH_UPDATE_JS_FIXTURES=1 to update them
 *
 * Run with: node --test tests/js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')

const FlowStore = require('../../assets/js/admin/funnels/flow-store')
const FlowCanvas = require('../../assets/js/admin/funnels/flow-canvas')
const {
  h,
  tokens,
} = require('./html')

const FIXTURES = path.join(__dirname, 'fixtures', 'canvas')

const draw = (fixture, options = {}) => {

  const canvas = FlowCanvas.createCanvas({
    h,
    stepTypes  : fixture.step_types,
    defaultIcon: fixture.default_icon,
  })

  return canvas.render({
    store: FlowStore.createStore({
      steps : fixture.steps,
      canvas: fixture.canvas,
    }),
    editing: true,
    debug  : fixture.debug,
    ...options,
  }).join('')
}

/**
 * Compare token by token, so a failure points at the first difference instead of dumping both documents
 */
const assertSameMarkup = (actual, expected, name) => {

  const a = tokens(actual)
  const e = tokens(expected)

  for (let i = 0; i < Math.max(a.length, e.length); i++) {
    if (a[i] !== e[i]) {
      assert.fail(`${ name }: token ${ i } differs\n  js:     ${ a[i] }\n  server: ${ e[i] }\n  after:  ${ e.slice(Math.max(0, i - 3), i).join(' ') }`)
    }
  }
}

for (const file of fs.readdirSync(FIXTURES).filter(file => file.endsWith('.json'))) {

  const name = path.basename(file, '.json')
  const fixture = JSON.parse(fs.readFileSync(path.join(FIXTURES, file), 'utf8'))

  test(`draws the ${ name } flow like the server`, () => {
    assertSameMarkup(draw(fixture), fixture.html, name)
  })
}

test('draws steps in branches a logic step no longer has', () => {

  const fixture = JSON.parse(fs.readFileSync(path.join(FIXTURES, 'branches.json'), 'utf8'))

  const ifElse = fixture.steps.find(step => step.data.step_type === 'if_else')
  const inYes = fixture.steps.find(step => step.data.branch === `${ ifElse.ID }-yes`)

  // the step's branch key isn't one of the if/else's anymore
  inYes.data.branch = `${ ifElse.ID }-maybe`

  const html = draw(fixture)

  assert.match(html, new RegExp(`<div class="split-branch unused-branch">`))
  assert.match(html, new RegExp(`data-branch="${ ifElse.ID }-maybe"`))
  assert.match(html, new RegExp(`id="step-${ inYes.ID }"`))
})

test('draws the stop layout', () => {

  const fixture = JSON.parse(fs.readFileSync(path.join(FIXTURES, 'branches.json'), 'utf8'))
  const stop = fixture.steps.find(step => step.data.step_type === 'logic_stop')

  fixture.canvas[stop.ID] = {
    ...fixture.canvas[stop.ID],
    layout     : 'stop',
    has_filters: false,
  }

  const html = draw(fixture)

  assert.match(html, /<div class="logic-line line-end"><span class="path-indicator">Stop<\/span><\/div>/)
  assert.match(html, /<div style="height: 40px"><\/div>/)
})

test('draws server HTML for step types that draw themselves', () => {

  const fixture = JSON.parse(fs.readFileSync(path.join(FIXTURES, 'simple.json'), 'utf8'))
  const step = fixture.steps[1]

  fixture.canvas[step.ID] = {
    ...fixture.canvas[step.ID],
    layout: 'html',
    html  : '<div class="sortable-item custom">custom</div>',
  }

  const html = draw(fixture)

  assert.match(html, /<div class="sortable-item custom">custom<\/div>/)
  assert.doesNotMatch(html, new RegExp(`id="step-${ step.ID }"`))
})

test('leaves out the editing buttons when not editing, and adds the report stats', () => {

  const fixture = JSON.parse(fs.readFileSync(path.join(FIXTURES, 'benchmarks.json'), 'utf8'))

  const html = draw(fixture, {
    editing  : false,
    reporting: true,
  })

  assert.doesNotMatch(html, /add-step/)
  assert.doesNotMatch(html, /delete-step/)
  assert.equal(html.match(/class="step-reporting"/g).length, fixture.steps.filter(step => step.data.step_group !== 'logic').length)
})
