import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

// Someone with the app for several sites has to be able to tell which one they are in. The server says what the site's
// domain and color are (App::domain() and App::color()), and the app shows them: the domain under the title of every
// screen, the color as the header, and both on the More screen with who is signed in.

const src = path.join( path.dirname( fileURLToPath( import.meta.url ) ), '../src' )
const app = readFileSync( path.join( src, 'app.jsx' ), 'utf8' )
const css = readFileSync( path.join( src, 'styles/app.css' ), 'utf8' )

test( 'every screen shows the domain of the site under its title', () => {
  assert.match( app, /<h1>{ title }<\/h1>\s*<small class="ghm-head-domain">{ runtime\.manifest\.app\.domain }<\/small>/ )
} )

test( 'the app takes its color from the site', () => {
  assert.match( app, /'--ghm-site': runtime\.manifest\.app\.color/ )
  assert.match( css, /\.ghm-head \{[^}]*background: var\(--ghm-site, var\(--ghm-surface\)\);[^}]*color: #fff/ )
} )

test( 'More says which site it is, and who is signed in', () => {
  assert.match( app, /title={ manifest\.app\.domain }/ )
  assert.match( app, /sprintf\( __\( 'Signed in as %s' \), manifest\.user\.name \)/ )
} )

test( 'the colors are a header that white text is on, so a button in it is not left dark', () => {
  assert.match( css, /\.ghm-head \.ghm-btn\.icon \{ color: inherit; \}/ )
} )
