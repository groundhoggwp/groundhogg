/**
 * The dashboard reports close their loading modal, which the person can't close, when the request for them fails
 *
 * $.ajax has no `failure` callback, a request that fails calls `error`, so nothing closed the modal or said that it failed.
 *
 * Run with: npm run test:js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const source = fs.readFileSync(path.join(__dirname, '../../assets/js/admin/reports/reporting.js'), 'utf8')

// The page that refresh() runs on, with only what it touches
const load = (file = source) => {

  const log = { closed: 0, unblurred: 0, dialogs: [], ajax: null, rendered: 0 }

  const $ = Object.assign(selector => ({
    removeClass: () => {
      log.unblurred++
    },
  }), {
    extend: Object.assign,
    ajax  : options => {
      log.ajax = options
    },
  })

  const GroundhoggReporting = { reports: ['one'], other: {} }

  const context = {
    $,
    jQuery: $,
    GroundhoggReporting,
    groundhogg_nonces: {},
    ajaxurl: '/admin-ajax.php',
    window: { dispatchEvent: () => {}, addEventListener: () => {} },
    Event: function () {},
    document: { getElementById: () => null, addEventListener: () => {} },
    wp: { i18n: { sprintf: (s) => s, __: s => s, _x: s => s, _n: s => s } },
    MakeEl: {},
    Groundhogg: {
      stores : { emails: {}, campaigns: {} },
      element: {
        loadingModal: () => ({ close: () => log.closed++ }),
        adminPageURL: () => '',
        dialog      : options => log.dialogs.push(options),
      },
    },
  }

  vm.runInNewContext(`(function(jQuery, groundhogg_nonces){ const $ = jQuery;\n${ file.replace(/^\( function \(\$, nonces\) \{/, '').replace(/\} \)\(jQuery, groundhogg_nonces\)\s*$/, '') }\n})(jQuery, groundhogg_nonces)`, context)

  const calendar = {
    start_date: { format: () => '2026-10-01' },
    end_date  : { format: () => '2026-10-31' },
  }

  // the page draws the reports with what it has, the test only counts that it did
  GroundhoggReporting.renderReports = () => log.rendered++

  GroundhoggReporting.refresh(calendar)

  return { log, GroundhoggReporting }
}

test('the request has an error callback, and not a failure one that jQuery ignores', () => {
  const { log } = load()

  assert.equal(typeof log.ajax.error, 'function')
  assert.equal(log.ajax.failure, undefined)
  assert.equal(log.closed, 0, 'it stays open while the request is out')
})

test('a request that fails closes the loading modal, unblurs the page and says so', () => {
  const { log } = load()

  log.ajax.error({}, 'error', 'Forbidden')

  assert.equal(log.closed, 1)
  assert.equal(log.unblurred, 1)
  assert.equal(log.dialogs.length, 1)
  assert.equal(log.dialogs[0].type, 'error')
  assert.match(log.dialogs[0].message, /Unable to retrieve report data/)
})

test('an answer that says it failed is a failure too', () => {

  for (const answer of [{ success: false, data: 'Invalid permissions.' }, null, { success: true, data: {} }]) {
    const { log } = load()

    log.ajax.success(answer)

    assert.equal(log.closed, 1, JSON.stringify(answer))
    assert.equal(log.dialogs.length, 1, JSON.stringify(answer))
    assert.equal(log.rendered, 0, JSON.stringify(answer))
  }
})

test('an answer with the reports renders them and closes the modal without an error', () => {
  const { log, GroundhoggReporting } = load()

  log.ajax.success({ success: true, data: { reports: { one: { type: 'chart' } } } })

  assert.equal(log.closed, 1)
  assert.equal(log.unblurred, 1)
  assert.equal(log.rendered, 1)
  assert.deepEqual(log.dialogs, [])
  assert.deepEqual(JSON.parse(JSON.stringify(GroundhoggReporting.data)), { one: { type: 'chart' } })
})

test('the minified file has the same error callback', () => {
  const min = fs.readFileSync(path.join(__dirname, '../../assets/js/admin/reports/reporting.min.js'), 'utf8')

  assert.match(min, /error:\w+/)
  assert.doesNotMatch(min, /failure:/)
})
