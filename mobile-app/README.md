# Groundhogg mobile app (source)

The source for the mobile web app served at `{managed page}/app/` (default `/gh/app/`). The design, the feature
registry contract and the decisions behind it are in [`../docs/mobile-app-registry.md`](../docs/mobile-app-registry.md).

This folder has its own `package.json` on purpose: it is kept apart from core's legacy admin JS tooling and from the
Gutenberg webpack build.

```bash
cd mobile-app
npm install
npm run build   # writes ../assets/js/mobile/mobile.js, mobile.min.js, sw.js and ../assets/css/mobile/mobile.css
npm run watch   # rebuilds mobile.js and the stylesheet on change
npm test        # unit tests for the registry, router, API client, i18n and href templates
```

The build output is committed, like the rest of core's assets, so a release doesn't need a build step. This folder is
excluded from the distributed zip (`.distignore`).

## Layout

```
src/index.jsx        boot: reads the manifest, builds the runtime, mounts the app
src/runtime.jsx      wires manifest + API + registry + loader, builds the ctx every component receives
src/app.jsx          the shell: header, routed content, tab bar, create menu, sheet and toast hosts
src/contribution.jsx renders one contribution (loads its bundle on demand, contains its errors)
src/core/            pure modules with unit tests: api, router, registry, loader, template, i18n
src/ui/              the shared UI kit handed to features as ctx.ui
src/views/           core's screens
src/features/core.jsx  core registering its own feature through the public API
src/styles/app.css   --ghm-* design tokens, light and dark
src/sw.js            the service worker (caches the shell's static files only)
test/                node --test
```

## Writing an add-on feature

1. Declare it in PHP on `groundhogg/mobile/register_features` (see the doc, section 4).
2. Build a classic-script (IIFE) bundle that calls `GroundhoggMobile.register( id, { ... } )`, and **does not bundle
   Preact**: use `window.GroundhoggMobile.preact` (`h`, `Fragment`, and the hooks) so there is only one copy.
3. Style with the `--ghm-*` tokens so the feature follows the user's theme.
