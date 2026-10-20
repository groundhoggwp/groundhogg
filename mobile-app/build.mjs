// Builds the mobile app into the plugin's assets folders.
//
//   npm run build   one-off, writes mobile.js and mobile.min.js
//   npm run watch   rebuild mobile.js on change
//
// Output is a classic script (an IIFE) rather than an ES module, so add-on bundles can rely on the
// `GroundhoggMobile` global without an import map.

import * as esbuild from 'esbuild'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const here = path.dirname( fileURLToPath( import.meta.url ) )
const js = path.join( here, '../assets/js/mobile' )
const css = path.join( here, '../assets/css/mobile' )
const watch = process.argv.includes( '--watch' )

const common = {
  bundle: true,
  format: 'iife',
  target: [ 'es2020', 'safari15' ],
  jsx: 'automatic',
  jsxImportSource: 'preact',
  logLevel: 'info',
}

const builds = [
  // the app
  { ...common, entryPoints: [ path.join( here, 'src/index.jsx' ) ], outfile: path.join( js, 'mobile.js' ) },
  { ...common, entryPoints: [ path.join( here, 'src/index.jsx' ) ], outfile: path.join( js, 'mobile.min.js' ), minify: true },
  // the service worker, which is served by PHP with a config prefix
  { ...common, entryPoints: [ path.join( here, 'src/sw.js' ) ], outfile: path.join( js, 'sw.js' ), minify: true },
  // styles
  { entryPoints: [ path.join( here, 'src/styles/app.css' ) ], outfile: path.join( css, 'mobile.css' ), bundle: true, minify: true, logLevel: 'info' },
]

if ( watch ) {
  for ( const options of builds.filter( b => ! b.minify || b.outfile.endsWith( '.css' ) ) ) {
    const ctx = await esbuild.context( options )
    await ctx.watch()
  }
  console.log( 'watching…' )
} else {
  await Promise.all( builds.map( options => esbuild.build( options ) ) )
}
