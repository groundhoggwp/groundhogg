/**
 * The JS titles are the same as generate_step_title()
 *
 * The fixtures are written by Step_Titles_Tests in the PHPUnit suite, run it with GH_UPDATE_JS_FIXTURES=1 to update them
 *
 * Run with: npm run test:js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')

const StepTitles = require('../../assets/js/admin/funnels/step-titles')()
const fixture = require('./fixtures/titles.json')

const lookup = map => id => map[id]

const names = {
  tag   : lookup(fixture.names.tag),
  email : lookup(fixture.names.email),
  funnel: lookup(fixture.names.funnel),
}

fixture.cases.forEach(({
  type,
  meta,
  title,
}, i) => {
  test(`${ type } #${ i } is titled like the server`, () => {
    assert.equal(StepTitles[type](meta, names), title)
  })
})

test('titles that need names that aren\'t loaded are left to the server', () => {
  const tagged = fixture.cases.find(({ type, meta }) => type === 'apply_tag' && meta.tags.length === 2)
  assert.equal(StepTitles.apply_tag(tagged.meta, { tag: () => undefined }), undefined)
  assert.equal(StepTitles.send_email({ email_id: 5 }, {}), undefined)
})
