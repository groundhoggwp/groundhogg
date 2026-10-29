/**
 * TinyMCE takes the colors of the interface theme
 *
 * Its text is in an iframe that the stylesheet of the theme can't reach, so it stayed white in the dark themes. The
 * colors are given to it as its content style, unless it has a content style of its own, like the text block of an
 * email, which shows the email as it will look.
 *
 * Run with: npm run test:js
 */
const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const source = fs.readFileSync(path.join(__dirname, '../../assets/js/admin/element.js'), 'utf8').replace(/\r\n/g, '\n')

const start = source.indexOf('  const getThemeContentStyle = () => {')
const end = source.indexOf('  $doc.on(\'tinymce-editor-setup\', addThemeContentStyleToTinyMCE)')

assert.ok(start > 0 && end > start, 'found the content style of the theme')

const load = variables => vm.runInNewContext(`(() => { ${ source.slice(start, end) }; return { getThemeContentStyle, addThemeContentStyleToTinyMCE } })()`, {
  document      : { body: {} },
  getComputedStyle: () => ( { getPropertyValue: name => variables[name] ?? '' } ),
})

const dark = {
  '--ght-surface'      : ' #0b0b0d',
  '--ght-text'         : '#f2f3f5 ',
  '--ght-accent'       : '#6ea8ff',
  '--ght-border-strong': '#33353b',
}

test('the content style has the colors of the theme', () => {
  const style = load(dark).getThemeContentStyle()

  assert.match(style, /background-color: #0b0b0d;/)
  assert.match(style, /color: #f2f3f5;/)
  assert.match(style, /a \{ color: #6ea8ff; \}/)
  assert.match(style, /blockquote \{ border-color: #33353b; \}/)
})

test('there is no content style without a theme', () => {
  assert.equal(load({}).getThemeContentStyle(), '')
})

test('an editor takes the content style of the theme', () => {
  const editor = { settings: {} }

  load(dark).addThemeContentStyleToTinyMCE({}, editor)

  assert.match(editor.settings.content_style, /#0b0b0d/)
})

test('an editor that has its own content style keeps it', () => {
  const editor = { settings: { content_style: 'body { background-color: #f0f0f0; }' } }

  load(dark).addThemeContentStyleToTinyMCE({}, editor)

  assert.equal(editor.settings.content_style, 'body { background-color: #f0f0f0; }')
})

test('an editor has no content style added without a theme', () => {
  const editor = { settings: {} }

  load({}).addThemeContentStyleToTinyMCE({}, editor)

  assert.equal(editor.settings.content_style, undefined)
})
