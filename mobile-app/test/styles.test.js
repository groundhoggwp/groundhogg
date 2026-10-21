import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const css = readFileSync( path.join( path.dirname( fileURLToPath( import.meta.url ) ), '../src/styles/app.css' ), 'utf8' )

// `.ghm button { font: inherit }` is more specific than `.ghm-btn` or `.ghm-action`, so it silently overrode their font
// size and weight on every button in the app. The element resets have to be zero specificity (:where) so that a
// component's own class always wins.
test( 'the base element resets cannot out-rank a component class', () => {
  assert.doesNotMatch( css, /^\.ghm (a|button)\b[^{]*\{/m, 'a reset like ".ghm button" beats ".ghm-btn"; use :where(.ghm) button' )
  assert.match( css, /:where\(\.ghm\) button\s*\{[^}]*font: inherit/ )
})
