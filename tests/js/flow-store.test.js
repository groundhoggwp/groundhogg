/**
 * The flow store derives the same order and levels as Funnel::set_step_levels()
 *
 * The fixtures are written by Flow_Canvas_Tests in the PHPUnit suite, where set_step_levels() ran on each flow
 *
 * Run with: node --test tests/js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')

const FlowStore = require('../../assets/js/admin/funnels/flow-store')

const FIXTURES = path.join(__dirname, 'fixtures', 'canvas')

const load = name => JSON.parse(fs.readFileSync(path.join(FIXTURES, `${ name }.json`), 'utf8'))

const storeOf = fixture => FlowStore.createStore({
  steps : fixture.steps,
  canvas: fixture.canvas,
})

for (const file of fs.readdirSync(FIXTURES).filter(file => file.endsWith('.json'))) {

  const name = path.basename(file, '.json')

  test(`derives the ${ name } flow's levels like the server`, () => {
    assert.deepEqual(storeOf(load(name)).levelDifferences(), [])
  })
}

test('the owner of a branch is before the first dash', () => {
  assert.equal(FlowStore.branchOwnerId('main'), 0)
  assert.equal(FlowStore.branchOwnerId('12'), 12)
  assert.equal(FlowStore.branchOwnerId('12-yes'), 12)
  assert.equal(FlowStore.branchOwnerId('12-a-b'), 12)
})

test('moving a step into a branch changes the levels after it', () => {

  const fixture = load('branches')
  const store = storeOf(fixture)

  const ifElse = fixture.steps.find(step => step.data.step_type === 'if_else')
  const last = fixture.steps.find(step => step.data.step_type === 'send_email')

  const before = store.stepLevels()

  // move the last main step to the end of the if/else's yes branch
  last.data.branch = `${ ifElse.ID }-yes`
  last.data.step_order = 999

  const after = store.stepLevels()

  assert.equal(after[last.ID].branch_path, `${ ifElse.ID }-yes|main`)
  assert.equal(after[last.ID].ancestors, `${ ifElse.ID }`)
  assert.ok(after[last.ID].step_level > before[ifElse.ID].step_level)

  // it now comes before the reroute step that followed the if/else
  const reroute = fixture.steps.find(step => step.data.step_type === 'logic_jump')
  assert.ok(after[last.ID].step_order < after[reroute.ID].step_order)
})

test('branch steps are ordered by step_order', () => {

  const steps = [
    { ID: 3, data: { branch: 'main', step_order: 2, step_group: 'action' } },
    { ID: 1, data: { branch: 'main', step_order: 3, step_group: 'action' } },
    { ID: 2, data: { branch: 'main', step_order: 1, step_group: 'action' } },
    { ID: 4, data: { branch: '2-yes', step_order: 1, step_group: 'action' } },
  ]

  assert.deepEqual(FlowStore.branchSteps(steps, 'main').map(step => step.ID), [2, 3, 1])
})
