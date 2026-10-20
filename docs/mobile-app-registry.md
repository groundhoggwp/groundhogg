# Mobile app: feature registry (draft spec)

**Status:** first slice implemented on the `mobile-app` branch: the route, shell, manifest builder, registry (PHP and
JS), the service worker, and core's own screens (Home, Contacts, contact record, Tasks, notes, quick create). No add-on
has registered a feature yet. See "Implementation notes" at the end for where it differs from this draft.

A mobile-first web app (PWA) for Groundhogg, where core ships the shell and the common screens, and add-ons
(appointments, pipeline, companies, ...) register their own mobile features into it. This document defines
**the registry**: how a feature is declared in PHP, how its UI is supplied in JS, what it can contribute, and the
rules that keep core and independently-released add-ons compatible.

---

## 1. Decisions this spec builds on

| Decision | Choice |
|---|---|
| Users | Admins, sales managers, sales reps. Reps mostly work "their" contacts; managers see their team. |
| Platform | PWA only. No native wrapper for now. |
| Entry point | A branded route under the managed page root: `{managed page}/app/` (default `/gh/app/`). |
| Scope | **Triage and act**: look things up, call/text/email, notes, tasks, appointments, deals, glance reports. Configuration (flow builder, email editor, settings, imports) stays on desktop and is reached by "open on desktop" links. |
| Offline | Not in scope. The service worker caches the app shell only; all data is live from REST. |
| Calling / texting | Plain `tel:` and `sms:` links. No call logging for now. |
| Permissions | Enforced by the endpoints. The registry only decides what to **show** (see section 6). |

Non-goals for v1: push notifications (the contract reserves a slot, section 5.8), offline writes, a native app,
contact/client-facing screens, per-site admin toggles for individual features.

---

## 2. Architecture

```
GET {managed page}/app/            -> PHP: auth check, nocache headers, shell HTML + inline manifest (JSON)
GET {managed page}/app/manifest.webmanifest
GET {managed page}/app/sw.js       -> service worker (scope /app/, caches shell assets only)

Browser:
  core.js      shell, hash router, API client, UI kit, Preact, registry
  feature.js   one lazy bundle per add-on, loaded on demand, calls GroundhoggMobile.register()
```

- **One server route.** Everything after `#` is client-side routing (`#/contacts/42`, `#/deals/7`), so adding a
  screen never needs a rewrite rule or a flush. Deep links, back button and install all work from one URL.
- **Outside wp-admin.** No admin chrome, no admin CSS, no admin-ajax. Same origin as the site, so the REST cookie +
  `X-WP-Nonce` session just works.
- **The service worker** is served from under `/app/`, so its default scope is the app. It does not need root scope.
- **Unauthenticated requests** redirect to `wp_login_url( app_url )`. A logged-in user who can see no feature at all
  gets a plain "no access" screen.
- **The route must never be page-cached.** Send `nocache_headers()` and define `DONOTCACHEPAGE`.
- Mount it with `add_managed_rewrite_rule( 'app/?$', 'subpage=app' )` alongside the existing archive, preferences
  and campaigns rules (`includes/rewrites.php`). It reuses the managed page name, which is configurable.

---

## 3. The split: PHP declares, JS renders

A feature is two halves joined by contribution ids.

| | PHP (declaration) | JS (implementation) |
|---|---|---|
| Owns | id, label, icon, capability, order, placement, bundle URL | the components that render each contribution |
| Why there | The server can filter by the current user's capabilities **before** the page is built, so a rep never receives a tab, card or bundle they can't use | Components need the live shell, router and API client |
| Costs the add-on | A small registration array | A bundle that calls `register()` once |

Because the shell learns about contributions from PHP, it can draw navigation, order the home screen and decide
which bundles to load **without running any add-on code**. A bundle loads only when something it contributes is about
to render.

Declarative-only contributions need no JS at all. A contact action that is just a link is fully described in PHP
(section 5.4), which is how core ships call and text.

---

## 4. Registering a feature (PHP)

Register on one action, and never at file load or on a guessed `init` priority. This mirrors
[`abilities-registration.md`](abilities-registration.md).

```php
add_action( 'groundhogg/mobile/register_features', function ( $features ) {

	$features->register( 'pipeline', [
		'label'           => __( 'Pipeline', 'groundhogg-pipeline' ),
		'min_api_version' => 1,                       // lowest mobile API the bundle was written against
		'cap'             => 'view_deals',            // see section 6
		'script'          => [                        // the lazy bundle
			'src'     => GH_PIPELINE_ASSETS_URL . 'js/mobile/pipeline.min.js',
			'version' => GH_PIPELINE_VERSION,
			'domain'  => 'groundhogg-pipeline',       // text domain, for translations
		],
		'style'           => [ 'src' => GH_PIPELINE_ASSETS_URL . 'css/mobile/pipeline.min.css', 'version' => GH_PIPELINE_VERSION ],

		'nav' => [
			[ 'id' => 'deals', 'label' => __( 'Deals', 'groundhogg-pipeline' ), 'icon' => 'deals', 'route' => '/deals', 'placement' => 'more', 'priority' => 30 ],
		],
		'home_cards' => [
			[ 'id' => 'stale-deals', 'priority' => 40 ],
		],
		'contact_panels' => [
			[ 'id' => 'contact-deals', 'label' => __( 'Deals', 'groundhogg-pipeline' ), 'priority' => 30 ],
		],
		'quick_create' => [
			[ 'id' => 'new-deal', 'label' => __( 'New deal', 'groundhogg-pipeline' ), 'icon' => 'deals', 'cap' => 'add_deals' ],
		],
		'search_providers' => [
			[ 'id' => 'deals', 'label' => __( 'Deals', 'groundhogg-pipeline' ), 'route' => '/deals/:id' ],
		],
	] );
} );
```

Rules:

- **`id`** is a slug, unique across add-ons, and immutable once shipped (like a step type). Contribution ids are
  unique only within their feature.
- **`cap`** is a capability string, an array (all required), or a callable. Each contribution may add its own `cap`
  on top of the feature's. See section 6 for what it is for and what it is not.
- **`script` is registered, not enqueued.** The shell injects it on demand. Translations travel with the manifest
  (section 7), so `wp_set_script_translations()` does not apply to these bundles.
- **Order.** `priority` sorts ascending within each extension point; ties break by feature id.
- The registry evaluates `cap` and `min_api_version` once per page load. Failing features are dropped from the manifest
  entirely, and a feature dropped for `min_api_version` is logged (only when `WP_DEBUG` is on) so the add-on author can see why.

---

## 5. Extension points

### 5.1 Navigation and routes (`nav`)

The shell has a bottom tab bar (core: **Home, Contacts, Tasks**, plus the create button) and a **More** screen.

- `placement`: `'tab'` or `'more'` is a *request*. The tab bar holds five items at most, so core grants `tab` by
  `priority` and sends the rest to More. Appointments is the one add-on likely to deserve a tab.
- `route` is the hash path. A feature owns everything under its own prefix.
- `badge` (optional): a REST route returning `{ "count": n }`. The shell polls it when the app is foregrounded.

JS side: `routes: { '/deals': DealsList, '/deals/:id': DealView }`.

### 5.2 Home cards (`home_cards`)

Cards on **Home**, which is "my day" for a rep and a team glance for a manager. Examples: today's appointments,
deals needing attention. JS side: `homeCards: { 'stale-deals': StaleDealsCard }`.

A card renders its own loading and empty states, and **must render nothing** (not an empty frame) when it has no data.
Keep cards to one request each. If a card needs several, that is a case for an aggregate endpoint, not for the card.

### 5.3 Contact panels (`contact_panels`)

Sections on the contact record, below the action bar and timeline. Examples: the contact's deals, appointments,
company. JS side: `contactPanels: { 'contact-deals': ContactDeals }`, receiving the contact as a prop.

### 5.4 Contact actions (`contact_actions`)

Buttons on the contact's action bar. Two kinds:

```php
// Declarative: no JS. {tokens} come from the contact; the action hides itself if a token is empty.
[ 'id' => 'call', 'label' => __( 'Call', 'groundhogg' ), 'icon' => 'phone', 'href' => 'tel:{mobile_phone|primary_phone}', 'priority' => 10 ]
[ 'id' => 'text', 'label' => __( 'Text', 'groundhogg' ), 'icon' => 'sms',   'href' => 'sms:{mobile_phone}',            'priority' => 20 ]

// Handler: opens or does something in the app. JS side: contactActions: { 'book': ( { contact, ctx } ) => ctx.navigate( ... ) }
[ 'id' => 'book', 'label' => __( 'Book', 'groundhogg-appointments' ), 'icon' => 'calendar', 'priority' => 50 ]
```

`a|b` in a token means "first non-empty". The first four actions show in the bar and the rest go to an overflow
menu. Core's call and text are plain declarative actions, so the simplest-possible calling and texting is the same
mechanism add-ons use.

Email is a handler action, because it is a choice and not a link. Tapping it opens a sheet offering three things:
the phone's own email app (a `mailto:` link), writing an email in the app, or sending one of the site's saved
Groundhogg emails (with a confirmation step before it goes). The last two use the same endpoints as the desktop
app (`emails/send`, `emails/{id}/send`), so sender, logging and recipient permissions are the site's own. They need
`send_emails` (and `view_emails` for saved emails) and a contact who can receive email, and the sheet hides or
disables them otherwise. Writing in the app records an outbound message on the contact.

### 5.5 Search providers (`search_providers`)

Contribute results to the global search. PHP declares the provider and the route a result opens. JS side:
`searchProviders: { deals: { search: ( q, ctx ) => Promise<Result[]>, render: ResultRow } }`. A result is
`{ id, title, subtitle, route }`. Core searches contacts itself.

### 5.6 Quick create (`quick_create`)

Items under the create button, e.g. "New deal", "New appointment". JS side: `quickCreate: { 'new-deal': NewDealSheet }`,
presented as a bottom sheet.

### 5.7 Reports (`report_cards`, reserved)

Glance metrics for managers and admins. Held back until Home and the report endpoints have been designed together.

### 5.8 Notifications (reserved)

The manifest reserves a `notifications` key so features can later declare event types ("deal assigned to you")
without a contract change. Nothing consumes it in v1.

---

## 6. Permissions

Three layers, with different jobs. Keeping them straight is the whole design.

| Layer | Job | Who maintains it |
|---|---|---|
| **Endpoint** (`permission_callback`, per-object caps like `view_contact`) | **Security.** The only layer that protects data. | Core / the add-on's REST classes (already in place) |
| **Registry `cap`** | **Visibility.** Don't show a tab, card or bundle the user can't use. | The add-on author, mirroring the endpoint's cap |
| **Query scoping** (owner and team restrictions) | Which records the user sees inside an endpoint they may call. | Already in core |

- If a registry `cap` drifts from the endpoint's, the worst outcome is a visible control that returns a 403. It can
  never expose data, because the registry does not grant anything.
- **"My contacts" needs no client logic.** What `Contact_Query` does today (`maybe_restrict_results_by_owner()` and `restrict_results_by_owner()`
  in `db/query/query.php`):
  - a sales rep (not a manager) sees only their own contacts,
  - a manager with a sales team (`gh_sales_team_ids` user meta) sees their team's contacts,
  - a manager with no team, or an administrator, sees everyone.

  Single objects are scoped by `map_meta_cap` (`view_contact`, `edit_contact`), including the team check through `owner_in_team()`.
- **Manager "team" toggle:** a UI filter gated on `view_others_contacts` / `view_others_tasks`, sending an owner
  filter. The server still enforces the real scope, so the toggle cannot widen access.
- **Known gap to verify before building Tasks and Home:** how the tasks and notes list endpoints scope for reps
  (notes scope by author, and `map_meta_cap` has a cascade for the object they belong to). Confirm against the real
  list routes, not just the single-object checks.

---

## 7. The manifest

PHP builds one JSON object per request, already filtered for the current user, and inlines it in the shell.

```jsonc
{
  "apiVersion": 1,
  "user": { "id": 3, "name": "...", "roles": ["sales_rep"], "teamIds": [3] },
  "caps": { "view_deals": true, "add_deals": false, "view_others_contacts": false },   // only caps the registrations mention, plus a core set
  "rest": { "root": "https://example.com/wp-json/gh/v4/", "nonce": "..." },
  "features": {
    "pipeline": {
      "script": { "src": "...", "version": "3.7.0", "translations": { /* locale data */ } },
      "style": "...",
      "nav": [ ... ], "home_cards": [ ... ], "contact_panels": [ ... ]
    }
  }
}
```

- `caps` is **a list of booleans for the caps the registrations name**, not the user's full capability set, so the
  page never reveals more than it needs. JS asks `ctx.can( 'add_deals' )`.
- The nonce lasts 12 to 24 hours, so the API client must refresh it silently on a 403 `rest_cookie_invalid_nonce` and
  retry once. This matters on a phone that is kept installed for days.
- A feature's translations come from `load_script_textdomain()` and are applied with `wp.i18n.setLocaleData()` before
  the bundle runs.

---

## 8. The JS API

```js
GroundhoggMobile.register( 'pipeline', {
	apiVersion: 1,
	routes:          { '/deals': DealsList, '/deals/:id': DealView },
	homeCards:       { 'stale-deals': StaleDealsCard },
	contactPanels:   { 'contact-deals': ContactDeals },
	contactActions:  { /* handler actions only */ },
	quickCreate:     { 'new-deal': NewDealSheet },
	searchProviders: { deals: { search, render } },
} );
```

Every component receives a `ctx` prop:

| `ctx.` | What |
|---|---|
| `api` | A thin `gh/v4` client (get/post/patch/delete) that adds the nonce, retries once on nonce expiry, and normalises errors |
| `user`, `can( cap )` | From the manifest |
| `navigate( path )`, `route` | Hash router |
| `toast( msg, type )`, `sheet( Component )`, `confirm( msg )` | Shell feedback and bottom-sheet presentation |
| `ui` | The shared kit: `List`, `ListItem`, `Card`, `Button`, `Field`, `Select`, `DatePicker`, `Avatar`, `Empty`, `Loading`, `Sheet` |
| `i18n` | `__`, `_x`, `_n`, `sprintf` with the feature's own text domain |
| `h`, `Fragment`, hooks | Preact, exported by the shell |

Rules for bundles:

- **Do not bundle Preact.** Treat it as an external provided by `GroundhoggMobile`. Two copies break hooks, and the
  shell would otherwise download it twice.
- **Registering twice, or for an id the manifest did not list, is a no-op** (logged in debug). The manifest is
  the source of truth for what exists.
- **A throwing component is contained.** The shell wraps each contribution in an error boundary, so one add-on's bug
  shows a small "couldn't load" card instead of taking the app down.
- Use `ctx.api`, never `fetch()` directly, so nonce handling and error shapes stay uniform.

---

## 9. Versioning and compatibility

- `GroundhoggMobile.apiVersion` is a single integer, starting at **1**.
- **Additive changes** (new extension point, new `ctx` member, new optional key) do **not** bump it. Add-ons must
  ignore keys they do not know.
- **Breaking changes** bump it, and core keeps the previous version working for **one minor release cycle** (the
  same deprecation policy as the rest of core), with a shim and a `_deprecated_*` notice in debug.
- An add-on built for a newer API than core provides is dropped from the manifest (section 4). An add-on built for an
  older supported API still loads.
- Gate the registration on core being new enough that the action exists, the same as for abilities:

  ```php
  add_action( 'groundhogg/loaded', function () {
  	if ( ! defined( 'GROUNDHOGG_VERSION' ) || version_compare( GROUNDHOGG_VERSION, '<version-that-ships-this>', '<' ) ) {
  		return;
  	}
  	// add the groundhogg/mobile/register_features listener
  } );
  ```

---

## 10. Build and tooling

- A **separate** small build for the mobile app (esbuild or Vite). It must not touch the legacy admin JS or the
  Gutenberg webpack config. Output is classic scripts (IIFE), not ES modules, so add-on bundles can rely on globals.
- Core exports `GroundhoggMobile` with Preact as globals, and add-ons build with those marked external.
- Provide a documented add-on build template (a minimal config plus an example feature) so each add-on does not
  invent its own. `groundhogg-extension-template` is the natural home.
- Tests: the registry, manifest filtering and the declarative-action token logic are pure functions, so they fit
  `tests/js` (`node --test`) and PHPUnit. A PHPUnit case should build the manifest as admin, manager and rep and assert
  what each sees.
- Theming: build on the same CSS custom properties as the admin interface themes (Light, Paper, Slate, Midnight) so
  the app follows the user's choice, and respect `prefers-color-scheme` for the default.

---

## 11. Phasing

1. **Foundation:** route, shell, auth, install flow, manifest builder, registry (PHP + JS) at `apiVersion` 1, API client, UI kit.
2. **The rep loop:** Home ("my day"), contacts (search, record, actions), tasks, notes. This proves the registry
   using only core features, because core registers through the same API.
3. **Add-ons:** appointments, then pipeline, then companies. They are the test of the contract's ergonomics.
4. **Manager and admin views:** team toggle, report cards, broadcast and queue monitoring.
5. **Notifications.**

Core must register its own features through the public registry. If core needs a private shortcut, the contract is
missing something.

---

## 12. Open questions

- **Tasks and notes list scoping for reps** (section 6): checked, see the findings under Implementation notes.
- **Entry from the login flow:** just the branded route for now. Should a user whose role is only a sales role be
  sent to it on mobile after login, or only by link/install? (Defer.)
- **Aggregate endpoints** (`/mobile/feed`): add only when Home is measurably slow, not up front.
- **Where the registry lives:** `includes/mobile/` in core, with the shell JS under `assets/js/mobile/`.
- **Core minimum version for add-ons:** fill in the release that ships the action.
- **Disabling the app:** a setting to turn the route off site-wide, and whether it ships in v1.

---

## Implementation notes (first slice)

Where the code is:

| | |
|---|---|
| `includes/mobile/features.php` | The registry, `groundhogg/mobile/register_features`, per-user filtering |
| `includes/mobile/core-features.php` | Core's own feature, registered through the public API |
| `includes/mobile/manifest.php` | Builds the per-user manifest |
| `includes/mobile/app.php` | The route, web app manifest, service worker, shell |
| `templates/mobile/shell.php` | The shell document |
| `mobile-app/` | Source and build for the JS (its own `package.json`, kept apart from the legacy admin tooling) |
| `assets/js/mobile/`, `assets/css/mobile/` | Build output, committed like the rest of core's assets |
| `tests/phpunit/.../class-mobile-features-tests.php`, `mobile-app/test/` | PHP and JS tests |

Where it differs from the draft above, or decides something the draft left open:

- **The core feature is gated on `view_contacts`**, which every Groundhogg role has. A user without it gets the
  "no access" page, not an empty app.
- **Asset version** is the plugin version plus the bundle's modified time, so a rebuilt bundle is never held back by a
  cache or by the service worker, without waiting for a release.
- **Browser target** is Safari 15 / ES2020. esbuild refuses to lower destructuring for older Safari.
- **`ctx.sheet( Component, props )`** shows a bottom sheet. The component receives `{ ctx, close, ...props }`, and a
  `title` prop becomes the sheet's heading. `ctx.sheet( null )` is not special, use the `close` prop.
- **`ListItem` takes a `control`** prop for an interactive element (a checkbox) that must sit beside the tappable part,
  because a button can't be nested in a link. A first version nested them and tapping the checkbox navigated away.
- **Plain permalinks work.** The REST root can already hold a query string (`index.php?rest_route=...`), and the API
  client joins with `&` when it does.
- **Translations** are wired through `ctx.i18n` (same surface as `wp.i18n`) but the manifest sends no locale data yet.
  Strings fall back to English until that is connected to the languages JSON generated for core.
- **Rewrite rules** flush once per `App::REWRITES_VERSION`, so a site needn't wait for an update routine.
- **No app icon art yet.** The web app manifest uses the site icon when one is set, and falls back to the small
  Groundhogg mark, which isn't square. Installability checks on some browsers want 192 and 512 square PNGs.

Findings from reading the API while building the screens (worth knowing for add-on authors):

- `Base_Object_Api::read()` filters items with `current_user_can_read()` **after** the query. For a user who can't see
  every row (a rep), `total_items` can overcount and a page can come back short. Fine for the short lists on Home;
  revisit before building long, paged lists.
- The contact REST item includes a `user` object (the linked WordPress user, with `allcaps`). The app doesn't read it,
  but it is more than a mobile client needs.
- Task and note list scoping for reps is by the `view_others_*` capabilities and `map_meta_cap`, with the list
  filtered per item as above. "My tasks" asks for `user_id = me` explicitly rather than relying on that.

---

## Checklist for an add-on adding a mobile feature

- [ ] Registered on `groundhogg/mobile/register_features`, gated on the core version that ships it.
- [ ] `cap` mirrors the capability the feature's REST endpoints actually check.
- [ ] Every contribution has an id and a `priority`, and the matching component is registered in JS under the same id.
- [ ] Preact is an external in the bundle, not bundled.
- [ ] Cards render nothing when empty, and use one request each.
- [ ] All requests go through `ctx.api`.
- [ ] Strings use the feature's own text domain and `ctx.i18n`.
- [ ] Verified as admin, manager and rep.
