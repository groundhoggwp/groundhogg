/**
 * The CSS of a text block has the rules for its tags
 *
 * It only wrote the rule for a tag that was in the content that the editor saved, which left what a replacement code adds
 * when the email is built, like the links of {files}, with no rule for its color and font. A block that has a
 * replacement code gets the rules for all of its tags.
 *
 * Run with: npm run test:js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const source = fs.readFileSync(path.join(__dirname, '../../assets/js/admin/emails/email-block-editor.js'), 'utf8').replace(/\r\n/g, '\n')

// the css property of the text block, as it is written in the source
const start = source.indexOf('    css      : ({\n      selector = \'\',\n      content,', source.indexOf('registerBlock(\'text\', \'Text\''))
const end = source.indexOf('    plainText: ({ content }) => extractPlainText(content),', start)

assert.ok(start > 0 && end > start, 'found the css of the text block')

const block = vm.runInNewContext(`({ ${ source.slice(start, end) } })`, {
  // the font styles are written as `color:…` so the rules can be read in the test
  fontStyle    : style => Object.entries(style).map(([k, v]) => `${ k }:${ v };`).join(''),
  objectToStyle: style => Object.entries(style).map(([k, v]) => `${ k }:${ v };`).join(''),
})

const props = {
  p : { color: 'p' },
  h1: { color: 'h1' },
  h2: { color: 'h2' },
  h3: { color: 'h3' },
  a : { color: 'link' },
}

const css = content => block.css({ selector: '#b-1', content, ...props })

test('a tag that is in the content gets its rule, and the others do not', () => {
  const rules = css('<p style="color:red">Hello <a href="#" style="color:red">there</a></p>')

  assert.match(rules, /#b-1 p, #b-1 li\{color:p;\}/)
  assert.match(rules, /#b-1 a\{\s*color:link;\s*\}/)
  assert.doesNotMatch(rules, /h1|h2|h3/)
})

test('a replacement code that could add tags gets the rules for all of them', () => {

  for (const content of ['<p style="color:red">{files}</p>', '<p>{contact.first_name|there}</p>', '{full_name}', '<p style="x">Files:</p>{files}']) {
    const rules = css(content)

    for (const tag of ['#b-1 h1', '#b-1 h2', '#b-1 h3', '#b-1 p, #b-1 li', '#b-1 a']) {
      assert.ok(rules.includes(tag), `${ tag } for ${ content }`)
    }

    assert.match(rules, /#b-1 a\{\s*color:link;\s*\}/, content)
  }
})

test('braces that are not a replacement code do not add the rules', () => {

  for (const content of ['<p style="color:red">a { b }</p>', '<p style="color:red">{ }</p>', '<p style="color:red">{1}</p>']) {
    assert.doesNotMatch(css(content), /#b-1 a|h1/, content)
  }
})

test('a block with no content has no rules', () => {
  assert.equal(css(''), '')
})

test('the minified editor has the same condition', () => {
  const min = fs.readFileSync(path.join(__dirname, '../../assets/js/admin/emails/email-block-editor.min.js'), 'utf8')

  assert.ok(min.includes('[a-z_][^{}]*'), 'the minified file tests for replacement codes')
})
