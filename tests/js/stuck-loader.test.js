/**
 * The loading modal can't be closed by the person, so a screen that opens it has to close it when what it loads fails
 *
 * Custom reports and bulk edit contacts closed it in a then(), so a request that was blocked, or answered with a page
 * that isn't JSON, left the spinner for good and an unhandled rejection in the console.
 *
 * Run with: npm run test:js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const read = file => fs.readFileSync(path.join(__dirname, '../../assets/js/admin', file), 'utf8')

const tick = () => new Promise(resolve => setTimeout(resolve, 0))

// jQuery that runs what's ready right away, and any method of what it finds does nothing, but is written down
const makeJQuery = log => {

  const found = selector => new Proxy({}, {
    get: (_, method) => (...args) => {
      log.calls.push([selector, method, ...args])

      return found(selector)
    },
  })

  return Object.assign(selector => {

    if (typeof selector === 'function') {
      selector()
      return
    }

    return found(selector)
  }, { extend: Object.assign })
}

// Whatever the screen uses of the page that it isn't testing is there, does nothing, and gives back something that does
// nothing, so only the parts that the test cares about are written out
const lenient = (target = function () {}) => new Proxy(target, {
  get  : (t, key) => key in t ? t[key] : (typeof key === 'symbol' || key === 'then') ? undefined : lenient(),
  apply: () => lenient(),
})

const run = async (file, globals) => {

  const log = { closed: 0, dialogs: [], calls: [], unhandled: [] }
  const $ = makeJQuery(log)

  const onUnhandled = reason => log.unhandled.push(reason)
  process.on('unhandledRejection', onUnhandled)

  const context = {
    $,
    jQuery: $,
    console,
    setTimeout,
    wp: { i18n: { sprintf: s => s, __: s => s, _x: s => s, _n: s => s } },
    MakeEl: lenient({}),
    document: lenient({}),
    BulkEdit: { countries: {}, time_zones: {}, language_dropdown: '', gh_contact_custom_properties: false, query: {}, selected: [] },
    window: lenient({}),
    ...globals(log),
  }

  try {
    vm.runInNewContext(read(file), context)
    await tick()
    await tick()
  }
  finally {
    process.off('unhandledRejection', onUnhandled)
  }

  return { log, context }
}

const element = log => lenient({
  loadingModal: () => ({ close: () => log.closed++ }),
  dialog      : options => log.dialogs.push(options),
})

const customReports = (get, files = {}) => run('reports/custom-reports.js', log => ({
  Groundhogg: lenient({
    element: element(log),
    api    : { routes: { v4: { root: '/gh/v4' } }, get },
    stores : lenient({ options: {}, tags: {} }),
  }),
}))

const bulkEdit = count => run('contacts/bulk-edit-contacts.js', log => ({
  Groundhogg: lenient({
    element   : element(log),
    filters   : lenient({ owners: [], functions: lenient({}) }),
    stores    : lenient({ contacts: lenient({ count }) }),
    formatting: lenient({ formatNumber: n => String(n) }),
  }),
}))

test('custom reports: a request that fails closes the loader, unblurs the page and says why', async () => {
  const { log } = await customReports(() => Promise.reject(new Error('Forbidden')))

  assert.equal(log.closed, 1)
  assert.ok(log.calls.some(([selector, method]) => selector === '.wrap' && method === 'removeClass'))
  assert.equal(log.dialogs.length, 1)
  assert.equal(log.dialogs[0].type, 'error')
  assert.equal(log.dialogs[0].message, 'Forbidden')
  assert.deepEqual(log.unhandled, [])
})

test('custom reports: an error without a message gets one', async () => {
  const { log } = await customReports(() => Promise.reject({}))

  assert.equal(log.closed, 1)
  assert.match(log.dialogs[0].message, /Unable to load reports/)
})

test('custom reports: reports that load close the loader without an error', async () => {
  const { log } = await customReports(() => Promise.resolve({ items: [] }))

  assert.equal(log.closed, 1)
  assert.deepEqual(log.dialogs, [])
  assert.deepEqual(log.unhandled, [])
})

test('bulk edit: a count that fails closes the loader, says why, and does not mount the form', async () => {
  const { log } = await bulkEdit(() => Promise.reject(new Error('Unexpected token <')))

  assert.equal(log.closed, 1)
  assert.equal(log.dialogs.length, 1)
  assert.equal(log.dialogs[0].type, 'error')
  assert.equal(log.dialogs[0].message, 'Unexpected token <')

  // the form is what has the commit button, which edits the contacts that were counted
  assert.equal(log.calls.some(([selector]) => selector === '#bulk-edit'), false)
  assert.deepEqual(log.unhandled, [])
})

test('bulk edit: a count that works mounts the form and closes the loader', async () => {
  const { log } = await bulkEdit(() => Promise.resolve(12))

  assert.equal(log.closed, 1)
  assert.deepEqual(log.dialogs, [])
  assert.ok(log.calls.some(([selector, method]) => selector === '#bulk-edit' && method === 'html'))
})

test('the minified files have the same catch and finally', () => {

  for (const file of ['reports/custom-reports.min.js', 'contacts/bulk-edit-contacts.min.js']) {
    const min = read(file)

    assert.match(min, /\.catch\(/, file)
    assert.match(min, /\.finally\(/, file)
  }
})
