/**
 * Edits in the flow store, the save queue, and undo and redo
 *
 * Run with: npm run test:js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')

const FlowStore = require('../../assets/js/admin/funnels/flow-store')

const load = name => JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures', 'canvas', `${ name }.json`), 'utf8'))

const storeOf = fixture => FlowStore.createStore({
  steps    : fixture.steps,
  canvas   : fixture.canvas,
  stepTypes: fixture.step_types,
})

// each step's branch and place in it, which is what edits change
const layout = store => Object.fromEntries(store.steps.map(step => [
  step.ID, `${ step.data.branch }#${ store.branchSteps(step.data.branch).indexOf(step) }`,
]))

const byType = (store, type) => store.steps.find(step => step.data.step_type === type)

/**
 * Apply operations and then their inverses, which should put everything back
 */
const roundTrip = (store, operations) => {

  const before = layout(store)
  const undo = []

  operations.forEach(operation => undo.unshift(...store.apply(operation)))

  const changed = layout(store)

  undo.forEach(operation => store.apply(operation))

  assert.deepEqual(layout(store), before)

  return changed
}

test('adding steps puts them where the position says, with a temporary ID', () => {

  const store = storeOf(load('branches'))
  const ifElse = byType(store, 'if_else')
  const id = FlowStore.newTempId()

  assert.ok(FlowStore.isTempId(id))

  store.apply({
    op   : 'add',
    at   : { branch_of: ifElse.ID, branch: 'no', position: 'start' },
    steps: [{ type: 'delay_timer', id }],
  })

  const added = store.getStep(id)

  assert.equal(added.data.branch, `${ ifElse.ID }-no`)
  assert.equal(store.branchSteps(`${ ifElse.ID }-no`)[0], added)
  assert.equal(added.data.step_group, 'action')
  assert.deepEqual(store.levelDifferences(), [])
})

test('every operation has an inverse that puts things back', () => {

  const fixture = load('branches')
  const store = storeOf(fixture)

  const ifElse = byType(store, 'if_else')
  const trigger = store.steps.find(step => step.data.step_type === 'tag_applied' && step.data.branch === 'main')
  const email = byType(store, 'send_email')

  // add
  roundTrip(store, [{ op: 'add', at: { after: trigger.ID }, steps: [{ type: 'apply_tag', id: FlowStore.newTempId() }] }])

  // move into a branch
  const moved = roundTrip(store, [{ op: 'move', step: email.ID, at: { branch_of: ifElse.ID, branch: 'yes', position: 'start' } }])
  assert.equal(moved[email.ID], `${ ifElse.ID }-yes#0`)

  // delete a logic step, which takes its branches with it
  const count = store.steps.length
  const inverse = store.apply({ op: 'delete', step: ifElse.ID })
  assert.ok(store.steps.length < count - 3)
  assert.equal(inverse[0].op, 'restore')
  inverse.forEach(operation => store.apply(operation))
  assert.equal(store.steps.length, count)
  assert.deepEqual(store.levelDifferences(), [])

  // update settings and the title
  const emailId = email.meta.email_id ?? null
  const [undo] = store.apply({ op: 'update', step: email.ID, meta: { email_id: 12 }, title: 'New title' })
  assert.equal(store.getStep(email.ID).meta.email_id, 12)
  assert.equal(store.getStep(email.ID).data.step_title, 'New title')
  assert.deepEqual(undo, { op: 'update', step: email.ID, meta: { email_id: emailId }, title: 'send_email' })
})

test('a step can\'t be moved into its own branches', () => {

  const store = storeOf(load('branches'))
  const [outer, inner] = store.steps.filter(step => step.data.step_type === 'if_else')

  assert.throws(() => store.apply({ op: 'move', step: outer.ID, at: { branch_of: inner.ID, branch: 'yes', position: 'end' } }), /own branches/)
})

test('temporary IDs are replaced everywhere once the server says what they are', () => {

  const ids = { tmp_abc: 99 }

  assert.deepEqual(FlowStore.rewriteIds([
    { op: 'move', step: 'tmp_abc', at: { after: 'tmp_abc' } },
    { op: 'add', at: { branch_of: 'tmp_abc', branch: 'yes' }, steps: [{ type: 'apply_tag', id: 'tmp_def' }] },
    { branch: 'tmp_abc-yes', other: 'tmp_abcd' },
  ], ids), [
    { op: 'move', step: 99, at: { after: 99 } },
    { op: 'add', at: { branch_of: 99, branch: 'yes' }, steps: [{ type: 'apply_tag', id: 'tmp_def' }] },
    { branch: '99-yes', other: 'tmp_abcd' },
  ])

  assert.equal(FlowStore.branchOwnerId('tmp_abc-yes'), 'tmp_abc')
  assert.deepEqual(FlowStore.branchPosition('tmp_abc-yes', 'start'), { branch_of: 'tmp_abc', branch: 'yes', position: 'start' })
  assert.deepEqual(FlowStore.branchPosition('12', 'end'), { branch_of: 12, branch: 'then', position: 'end' })
  assert.equal(FlowStore.positionBranch({ branch_of: 12, branch: 'then' }), '12')
})

test('changes the server made from posted settings can be undone', () => {

  const store = storeOf(load('simple'))
  const before = JSON.parse(JSON.stringify(store.steps))

  const [, email] = store.steps

  // what a save of the settings could send back
  store.load({
    steps : [
      ...store.steps.map(step => step.ID === email.ID ? {
        ...step,
        data: { ...step.data, step_title: 'Send "Welcome"' },
        meta: { ...step.meta, email_id: 5 },
      } : step),
      // a duplicate of the email step
      { ...email, ID: 500, meta: { email_id: 5 } },
    ],
    canvas: store.canvas,
  })

  const { undo, redo } = store.changesSince(before)

  assert.deepEqual(undo.find(operation => operation.op === 'update'), { op: 'update', step: email.ID, meta: { email_id: before[1].meta.email_id ?? null }, title: before[1].data.step_title })
  assert.deepEqual(redo.find(operation => operation.op === 'update'), { op: 'update', step: email.ID, meta: { email_id: 5 }, title: 'Send "Welcome"' })
  assert.deepEqual(undo[0], { op: 'delete', step: 500 })
  assert.equal(redo.find(operation => operation.op === 'restore').step, 500)
})

test('the history undoes and redoes in order, and a new change drops what was undone', () => {

  const history = FlowStore.createHistory({ size: 3 })

  history.record({ undo: [{ n: 1 }], redo: [{ n: -1 }] })
  history.record({ undo: [{ n: 2 }], redo: [{ n: -2 }] })

  assert.deepEqual(history.undo(), [{ n: 2 }])
  assert.deepEqual(history.redo(), [{ n: -2 }])
  assert.equal(history.canRedo(), false)

  history.undo()
  history.record({ undo: [{ n: 3 }], redo: [{ n: -3 }] })

  assert.equal(history.canRedo(), false)
  assert.deepEqual(history.undo(), [{ n: 3 }])
  assert.deepEqual(history.undo(), [{ n: 1 }])
  assert.equal(history.canUndo(), false)
})

test('once an added step is saved, redoing the add restores it instead of adding another', () => {

  const history = FlowStore.createHistory()

  history.record({
    undo: [{ op: 'delete', step: 'tmp_a' }],
    redo: [{ op: 'add', at: { after: 5 }, steps: [{ type: 'apply_tag', id: 'tmp_a' }] }],
  })

  history.rewriteIds({ tmp_a: 42 })

  history.undo()

  assert.deepEqual(history.redo(), [{ op: 'restore', step: 42, at: { after: 5 }, steps: [] }])
})

test('the queue sends one request at a time, with what was made while one was out', async () => {

  const sent = []
  let release

  const queue = FlowStore.createQueue({
    send: operations => {
      sent.push(operations.map(operation => operation.n))
      return new Promise(resolve => release = () => resolve({ success: true, data: {} }))
    },
  })

  const first = queue.push([{ n: 1 }])
  await Promise.resolve()

  queue.push([{ n: 2 }])
  queue.push([{ n: 3 }])

  // the second request waits for the first
  await new Promise(resolve => setTimeout(resolve))
  assert.deepEqual(sent, [[1]])
  assert.ok(queue.isBusy())

  release()
  await first
  await new Promise(resolve => setTimeout(resolve))

  assert.deepEqual(sent, [[1], [2, 3]])

  release()
  await queue.flush()

  assert.equal(queue.isBusy(), false)
})

test('the queue gives operations waiting to be sent the real IDs of steps it added', async () => {

  const sent = []
  let release

  const queue = FlowStore.createQueue({
    send: operations => {
      sent.push(operations)
      return new Promise(resolve => release = data => resolve({ success: true, data }))
    },
  })

  queue.push([{ op: 'add', at: { branch: 'main' }, steps: [{ type: 'apply_tag', id: 'tmp_a' }] }])
  await new Promise(resolve => setTimeout(resolve))

  queue.push([{ op: 'move', step: 'tmp_a', at: { branch: 'main', position: 'start' } }])

  release({ ids: { tmp_a: 7 } })
  await new Promise(resolve => setTimeout(resolve))

  assert.deepEqual(sent[1], [{ op: 'move', step: 7, at: { branch: 'main', position: 'start' } }])

  release({})
  await queue.flush()
})

test('the queue retries requests that don\'t get through, and reports refusals', async () => {

  let attempts = 0
  const refused = []
  const retries = []

  const queue = FlowStore.createQueue({
    send      : async () => {
      if (++attempts < 3) {
        throw new Error('offline')
      }
      return { success: false, data: { code: 'nope' } }
    },
    onRefused : (data, operations) => refused.push([data.code, operations.length]),
    onRetry   : (error, attempt) => retries.push(attempt),
    retryDelay: () => 0,
  })

  await queue.push([{ n: 1 }, { n: 2 }])

  assert.equal(attempts, 3)
  assert.deepEqual(retries, [0, 1])
  assert.deepEqual(refused, [['nope', 2]])
  assert.equal(queue.isBusy(), false)
})

test('other saves wait for what\'s queued before them', async () => {

  const order = []

  const queue = FlowStore.createQueue({
    send: async operations => {
      order.push('operations')
      return { success: true, data: {} }
    },
  })

  queue.push([{ n: 1 }])

  await queue.exclusive(async () => order.push('form'))

  assert.deepEqual(order, ['operations', 'form'])
})
