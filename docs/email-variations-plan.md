# Email variations: multilingual sends and A/B tests

Status: built (2026-11-26). Phases 1 (locale foundation), 2 (variation core), 3 (translation workflow) and 4 (A/B tests) are done; see each phase's notes below.

## The problem

A site running WPML, Polylang, TranslatePress or Weglot has contacts who speak
different languages. Groundhogg can only send each email in one language. Today
users work around that in one of three ways, and all of them are bad:

1. **One email per language plus a branch.** An if/else on locale in front of
   N "Send email" steps in every flow, and N broadcasts with N segments. Flows
   balloon, stats are split across N emails, and every change has to be made N
   times.
2. **Conditional blocks.** Every block exists once per language, each with an
   `include_filters` locale condition. The subject line and preview text can't
   vary, the editor shows every language at once, and the body roughly doubles
   per language.
3. **They don't.** Everyone gets the site's default language.

A/B testing has the same shape: one "slot" (a flow step, a broadcast) that
should send different content to different contacts and report on each. Today
that needs the premium Split Test logic step plus two Send Email steps, and
there's no A/B for broadcasts at all.

## What already exists

Worth knowing before designing, because some of it is half of the answer and
some of it is broken.

| Piece | Where | State |
|---|---|---|
| Contact locale | `Contact::get_locale()` / `set_locale()`, meta `locale`, `includes/classes/contact.php:479` | Set once on `Contact::create()` from `get_locale()`, and from `$user->locale` on user sync (`functions.php:1859`). Never updated afterwards. Falls back to the site locale. |
| Locale switch while sending | `Event_Queue::process()`, `includes/queue/event-queue.php:249` | `switch_to_locale( $contact->get_locale() )` around every event, so Groundhogg's own strings (footer, unsubscribe text) come out in the contact's language **if that language pack is installed**. Immediate sends (`send-email-template` ability, test sends) don't switch. |
| Locale segment filter | `Contact_Query::filter_locale()`, `includes/contact-query.php:675` | **Broken in two ways.** (1) It intersects the requested locales with `wp_get_available_translations()`, which never contains `en_US`, so "locale is en_US" matches nobody. (2) `wp_get_available_translations()` lives in `wp-admin/includes/translation-install.php`, which Groundhogg never requires, so evaluating this filter outside wp-admin (cron, REST, front end, i.e. a conditional block at send time) should fatal; and where it does load, it can make a remote call to api.wordpress.org. (From reading the code; not reproduced yet.) |
| Conditional blocks | `Email::maybe_hide_blocks()` / `Block_Registry::parse_blocks()` | Per-block `include_filters`/`exclude_filters`, one `Contact_Query` count per conditional block per send. Works, but see workaround 2 above. |
| Polylang compat | `includes/plugin-compatibility.php:41` | Only rewrite rules for managed pages. Nothing for WPML. |
| Split Test step | `includes/steps/premium/logic/split-test.php` | Records the branch in event `args` and counts with `->contains( 'args', $branch )`, which the code itself calls "hella slow". A lesson for this design: per-variant tracking needs a real column. |
| Agents | abilities | No ability can read or set a contact's locale, and `Segment_Schema` has no locale audience key. An agent can't even find out who speaks French. |

Also important for the design: **events and activity both carry `email_id`**
(`db/events.php`, `db/activity.php`). `Step::enqueue()` stamps the step's
email onto the event (`includes/classes/step.php:1484`), broadcasts stamp theirs
(`broadcast.php:595`), and open/click tracking resolves the email through the
event (`Tracking::get_current_email_id()`). So whatever `email_id` is on the
event when the email goes out is what every open, click and unsubscribe is
attributed to.

## Proposal: variations are child emails

A **variation** is an ordinary row in the `emails` table with a new
`parent_id` column pointing at the email it varies. The parent is what flows,
broadcasts and notifications reference, exactly as today. At send time the
parent picks which member of its family this contact gets, and that member is
sent.

```
Email #12 "Welcome"  (parent, en_US, the fallback)
├── #31 "Welcome — FR"   audience: locale in [fr_FR, fr_CA]
├── #32 "Welcome — DE"   audience: locale in [de_DE]
└── #33 "Welcome — B"    test: weight 50   (A/B against #12)
```

### Why child rows rather than a JSON blob on one email

| | Child email rows | Variations in emailmeta |
|---|---|---|
| Per-variation opens/clicks | Free: set `event.email_id` to the variation, and tracking, activity and the email log attribute to it with no schema change on the big tables | Needs a new column on events, event_queue and activity, or the slow `args` LIKE the Split Test uses |
| Agent tooling | Every existing email ability (`get-email-template` outline, `edit-email`, `update-email-template`, `send-test-email`, the block renderer) works on a variation id unchanged | Every ability needs a `variation` parameter threaded through it |
| Editor | Open the variation in the existing editor | Editor must hold N documents |
| Cost | Reports that filter on one `email_id` must learn about families; email pickers must hide children | Less churn in reports |

The tooling argument wins, because agents are the primary user. An agent that
already knows how to edit an email knows how to edit a variation.

### Data model

- `emails.parent_id bigint unsigned NOT NULL DEFAULT 0`, indexed. Cheap: the
  emails table is small. (Meta would work but every "hide the children" list
  query would need a join.)
- Variation settings in emailmeta on the child:
  - `variation_kind`: `audience` or `test`
  - `variation_audience`: Filters DSL (`include_filters` / `exclude_filters`,
    same shape conditional blocks and if/else use), plus a fast path
    `locales: []` that's compared in PHP against `$contact->get_locale()` with
    no SQL at all. Language is by far the common case, so it shouldn't cost a
    query per send.
  - `variation_weight`: 0–100, for `test`
  - `variation_priority`: order of evaluation for `audience`
  - `variation_locale`: the language the variation is written in (drives
    `switch_to_locale()` while building it; see below)
  - `variation_source`: `{ email_id, version, strings_hash }` of the parent when
    the variation was last translated from it (staleness, see below)
- Inherited from the parent and not editable on a child: `message_type`
  (a transactional email can't have a marketing variation), confirmation-email
  behaviour, and "is template". Sender, subject, preview text, content,
  template settings, UTM, custom headers all vary freely.
- A variation in `draft` is never chosen. This is a nice property for agents:
  they can draft five translations, and nothing changes for recipients until
  each one is marked `ready`.

### Choosing a variation at send time

One choke point: `Email::send()`. Every path (flow step, broadcast, email
notification, `send-email-template`, the admin "send email" modal) ends there.

```
Email::send( $contact, $event )
  if this email has ready children and is not itself a variation:
      $chosen = $this->resolve_variation( $contact )
      if $chosen is not $this:
          $event?->update( [ 'email_id' => $chosen->get_id() ] )   // attribution
          return $chosen->send( $contact, $event )
  ... existing send_to()
```

`resolve_variation()`:

1. **Audience variations**, in priority order, first match wins. `locales`
   checked in PHP; anything else is one `Contact_Query` count, the same cost as
   a conditional block today.
2. If none matched, the parent is the candidate. **Test variations** of that
   candidate split the traffic by weight (the remainder goes to the candidate
   itself).
3. Test assignment is **sticky and stateless**: `crc32( contact_id . ':' .
   parent_id ) % 100` against the cumulative weights. The same contact always
   lands in the same arm, retries and resends are consistent, and nothing has
   to be stored. Once a winner is declared, step 2 just returns the winner.

Notes:

- Rewriting `email_id` on the queued event before it runs means
  `Event::get_email()` loads the variation on a retry, and a variation sends
  itself without resolving again. Idempotent.
- Callers that read the email back after sending (the Send Email step logs the
  title and stores the subject for threading) need `$email->get_sent_variation()`
  so they report what actually went out.
- When the chosen variation has a `variation_locale`, wrap the build in
  `switch_to_locale()` so Groundhogg's own strings and date merge tags match the
  content, even if the contact's stored locale disagrees. Do the same for
  immediate sends, which skip the queue's switch today.
- Nesting (an A/B test inside the French variation) falls out of running the
  same resolution on the chosen audience variation. Cap the depth at 2, and
  consider leaving it for later.

### Reporting

- Email reports that filter `email_id = X` switch to
  `email_id IN ( family of X )`, so the parent's numbers are the total, as users
  expect. `Base_Report::get_email_id()` becomes `get_email_ids()`; the reports
  touched are listed under `includes/reporting/new-reports/` (`email-open-rate`,
  `email-click-rate`, `total-emails-sent`, `table-email-stats`,
  `table-email-links-clicked`, the funnel email performance tables).
- A new "Variations" table: sent, opened, clicked, unsubscribed per variation,
  and for test variations a confidence figure (two-proportion z-test on the
  chosen metric) and a suggested winner.
- Flow reports are keyed by `step_id` and need nothing.

### Things that must hide children

The emails list table, the email pickers (Send Email step, broadcast modal,
`api/v3/email-api.php` search), `list-email-templates`, the emails count in
telemetry, and the library/template browser. Deleting a parent deletes its
children; duplicating a parent duplicates the family.

## Agent surface (abilities)

Agents are the primary user, so the shape of the abilities matters more than
the admin UI. Principles: reuse the email abilities for content, keep the new
ones about structure, and make every decision explainable.

| Ability | What it does |
|---|---|
| `groundhogg/create-email-variation` | `{ email_id, label, kind, audience? (Segment_Schema subset or { locales }), weight?, locale?, copy_from: "parent" \| <id> \| "blank" }`. Copies the parent by default, **keeping block ids**, so the agent can then translate block by block. Returns the new id and its outline. |
| `groundhogg/update-email-template` (extended) | Accepts a `variation` object on a child id: audience, weight, priority, locale. No separate update ability. |
| `groundhogg/get-email-template` (extended) | On a parent: `variations: [{ id, label, kind, audience, weight, locale, status, stale }]`. On a child: `parent_id`, its variation settings, and `stale`. |
| `groundhogg/get-email-strings` / `set-email-strings` | The translation format. The subject, preview text, and each text-bearing block's HTML as a flat `{ key: html }` map keyed by block id (and button/image-alt props). An agent translates the map and writes it back to the variation in one call. Far cheaper and safer than replaying `edit-email` operations, and it can't break layout because only text moves. |
| `groundhogg/resolve-email-variation` | `{ email_id, contact_id }` → which variation the contact would get and why (matched audience, test arm, fell back to parent). Read-only. This is how an agent (or support) answers "why did Marie get English?". |
| `groundhogg/get-email-variation-report` | Per-variation stats, confidence and a suggested winner. |
| `groundhogg/declare-email-winner` | Ends a test: the winner takes 100% of the test traffic. Optional `promote: true` copies the winner's content into the parent and archives the losing arms. |
| `groundhogg/send-email-broadcast` (extended) | `test: { sample_percent, wait_hours, metric: "opens" \| "clicks", auto_send_winner }`: send the arms to a sample, wait, then send the winner to everyone else. |

And the locale groundwork agents need before any of that is useful:

- `Contact_Schema` exposes `locale`; `update-contact` / `bulk-update-contacts`
  can set it.
- `Segment_Schema` gains a `locales` audience key (so "send this to French
  speakers" and "how many contacts speak German" are one call).
- `get-setup-status` (or a small `get-site-languages`) reports the site's
  languages: the multilingual plugin's active languages mapped to WP locales,
  the installed WP language packs, and the contact count per locale. An agent
  can then say "you have 340 Spanish-speaking contacts and no Spanish version
  of your welcome email".

### Keeping translations in sync

The real long-term cost of translations is drift: someone edits the English
email and the French one silently goes out of date. Store
`variation_source.strings_hash` (a hash per string key) when a variation is
translated. `get-email-template` on the parent then reports each variation as
`stale` with the list of keys whose source text changed, so an agent re-translates
only those strings. This is the piece that makes "AI agents maintain the
translations" actually work rather than a one-off.

With the AI connectors work (site's own provider key, `includes/ai/jobs/`), the
same string map powers a server-side "Translate" job and an admin
"Translate with AI" button, with no agent involved.

## Multilingual plugin integration

Keep it narrow: the plugins tell Groundhogg **which language a contact speaks**,
and Groundhogg's variations do the rest. No WPML String Translation packages, no
Polylang-specific storage, so it works the same with TranslatePress, Weglot or
no plugin at all.

- **Capture.** On form submission (and optionally on login and on preferences
  page saves), set the contact's locale from the language the visitor is
  browsing in: `determine_locale()` already reflects WPML and Polylang on the
  front end. Small adapters for WPML (`wpml_current_language` →
  `wpml_active_languages[...]['default_locale']`) and Polylang
  (`pll_current_language( 'locale' )`) behind a
  `groundhogg/contact/detected_locale` filter. Policy question below on whether
  an explicit signal like a form submission overwrites an existing value.
- **Preferences page.** A "Language" choice for the contact, limited to the
  site's languages, so recipients can fix it themselves.
- **Links.** Managed pages (preferences, unsubscribe, browser view) should open
  in the contact's language: add the language to the URL the way each plugin
  expects. Polylang rewrite rules already exist; WPML needs the equivalent.
- **Fix `filter_locale()`**: drop the `wp_get_available_translations()`
  intersection (validate against the site's languages, and always allow
  `en_US`), so it's safe at send time and makes no remote calls.

## Alternatives considered

- **Conditional blocks only, made nicer.** Still can't vary subject, preview
  text or sender, and the editor gets worse with every language. Keep
  conditional blocks for small inline differences, not whole translations.
- **Per-language emails plus automatic routing** (a "language group" linking
  separate top-level emails). Essentially this proposal without the parent/child
  link; the family would have to be discovered on every report and every send.
- **Native WPML/Polylang translation of emails.** Emails aren't posts, each
  plugin models translation differently, WPML's editor is paid, and it would
  leave TranslatePress and Weglot users out.
- **Variations as JSON in emailmeta.** See the table above: per-variant
  tracking needs a new column on the three biggest tables or a slow `args`
  search, and every email ability would need a `variation` parameter.

## Suggested phases

1. **Locale foundation** (small, valuable on its own, ships first): fix
   `filter_locale()`; locale capture with WPML/Polylang adapters; locale on the
   preferences page; `locale` in `Contact_Schema`, a `locales` key in
   `Segment_Schema`; site languages in `get-setup-status`; switch locale for
   immediate sends.

   **Built (2026-11-26).** `Groundhogg\Multilingual` (`includes/multilingual.php`)
   reads the site's languages, default and current language from WPML, Polylang
   or TranslatePress (filters `groundhogg/multilingual/plugin`, `site_languages`,
   `default_locale`, `current_locale` for anything else). Forms v2 render a hidden
   `gh_locale` input with the page's language, because submissions arrive over
   REST where the plugin can't tell; on submit it overwrites the contact's locale
   when the site is multilingual and the locale is one of its languages
   (`groundhogg/multilingual/update_locale_from_form` to turn that off). That is
   the default chosen for open question 2. Contacts without a locale now fall back
   to the multilingual plugin's default language rather than the current request's
   `get_locale()`. Tests: `tests/phpunit/unit-tests/includes/class-multilingual-tests.php`.
   Weglot is not read (it uses language codes, not locales).
2. **Variation core:** `parent_id` column and updater; `resolve_variation()` in
   `Email::send()` with event `email_id` rewrite; hide children everywhere;
   family-aware email reports; `create-email-variation`, the
   `get-`/`update-email-template` extensions, `resolve-email-variation`; a
   section in `includes/abilities/guides/email.md`.

   **Built (2026-11-26).** `Groundhogg\Email_Variations` (`includes/email-variations.php`)
   holds the model, resolution, family IDs, create/update/copy/export/import and
   the delete cascade. Audience variations only (`locales` plus Filters DSL);
   test variations wait for phase 4. Details and where it differs from the plan above:
   - `emails.parent_id` (table version 2.2) is added by the pending 4.13 update.
     Until it runs, `Emails::supports_variations()` is false: `get_columns()`
     leaves the column out and nothing queries it, so a site on the old schema
     keeps working with no variations.
   - `Emails::query()` adds `parent_id = 0` unless the caller passes `parent_id`
     or `include`, which hides variations from the admin table, REST and v3
     search, pickers and `list-email-templates` in one place.
   - Only a ready parent resolves, and test sends never do. A variation's
     `message_type` is always its parent's.
   - Family-aware: the contact segment filters that take an `email_id` (email
     received/opened/clicked, unsubscribed, custom activity), every email report,
     and the funnel/broadcast performance tables (rows merged into the parent with
     `merge_into_parents()`). Add-ons that match one `email_id` won't see a
     variation's sends until they use `Email_Variations::get_family_ids()`.
   - Abilities: `create-email-variation`, `update-email-variation`,
     `delete-email-variation`, `resolve-email-variation`. Update and delete are
     their own abilities rather than a `variation` field on
     `update-email-template`, so the audience (a `Segment_Schema` object, with
     `locales` kept apart for the no-query check) never mixes with email fields.
     `Email_Schema` shows `variation_of`, `variation` and `variations`.
   - Duplicating an email, a flow template export and the Send Email step's
     import carry the variations. The emails table lists an email's variations
     under its title; there's no editor UI for them yet (phase 3).
   - Tests: `tests/phpunit/unit-tests/includes/class-email-variations-tests.php`.
3. **Translation workflow:** `get-`/`set-email-strings`; staleness tracking;
   AI translate job; a language switcher (tabs) in the email editor for humans.

   **Built (2026-11-26).** `Groundhogg\Email_Strings` (`includes/email-strings.php`):
   - A string is the text between block-level tags; inline tags become numbered
     placeholders (`<1>bold</1>`, `<2/>`) that are put back as the original tags,
     styles and URLs included, so translating can't break formatting. Keys are
     `subject`, `preview`, the block id (`{id}`, `{id}.2`…, `{id}.alt`) for
     text, heading, button and menu blocks and image alt text, and `text.N` in HTML
     emails. Footer, HTML, global and dynamic blocks aren't read.
   - Staleness is per string, not the whole-email `strings_hash` planned above:
     meta `variation_sources` holds a short hash of the email's text each string
     was translated from. Written on `set()` (agents, the AI job, and strings
     confirmed unchanged like names) and, for strings translated by hand, when the
     editor saves the variation. Statuses: `untranslated`, `changed`, `translated`,
     `not_in_email`; `missing` lists text in blocks the variation doesn't have.
   - Abilities `get-email-strings` (`needs_translation` filter) and
     `set-email-strings` (all or nothing, `expected_version`, edit lock).
   - AI job `translate-email` on the site's key: sends only strings that need it
     (up to 12,000 characters a request, the rest on the next click), saves what
     comes back, and leaves any string that lost a placeholder for the next try.
   - Editor: instead of language tabs, a "Variations" panel in Email Settings
     (`GET/POST gh/v4/emails/{id}/variations`, `Email_Variations::editor_data()`):
     the variations with status, languages and strings left to translate, a
     Translate button (AI), and "Add a language" for the site's other languages,
     which makes a draft copy. On a variation it links back to the email. Tabs
     that switch the canvas between versions in place would mean the editor
     holding several emails at once; the panel opens each in its own editor.
   - Tests: `tests/phpunit/unit-tests/includes/class-email-strings-tests.php`.
     Checked on groundhogg.dev: email #35 got a French variation (#44) that the AI
     translated in one click, with every placeholder back on the right words.
4. **A/B tests:** test variations with sticky weights; variation report with
   confidence; `declare-email-winner`; broadcast test-then-send-winner.

   **Built (2026-11-26).** Decisions taken as defaults for the open questions:
   in core; no tests nested inside an audience version (audience versions are
   checked first, everyone else is in the test); winners are declared by a person
   or an agent, never automatically for flows.
   - A test version is a variation with meta `variation_kind = test` and
     `variation_weight` (percent). New ones split evenly with the email and each
     other unless given a weight, and adding one restarts the test
     (`variation_test_started`, winner cleared). `bucket()` is
     `crc32(contact:email) % 100`, so a contact always lands in the same version.
     Draft test versions get nothing until ready; the email keeps their share.
   - `report()`: sent / opened / clicked / unsubscribed per version since the
     test started (optionally one broadcast), rates, the leader on clicks or opens,
     two-proportion z-test confidence against the runner-up, and a suggested winner
     at 95% (filter `groundhogg/email/variation/confidence_threshold`).
   - `declare_winner()` stores `variation_winner` on the email; everyone in the
     test gets it. Winner 0 restarts.
   - Broadcast: `start_broadcast_test()` sends a shuffled sample (5–50% of the
     marketable audience) and schedules `finish_broadcast_test()` with WP cron
     after `wait_hours`, which declares the leader on the sample (sure or not) and
     broadcasts the email to the audience minus the sample's IDs. Stored as
     `variation_broadcast_test` on the email; one at a time.
   - Abilities: `create-email-variation` `kind: test` + `weight`,
     `update-email-variation` `weight`, `get-email-variation-report`,
     `declare-email-winner`, `send-email-broadcast` `ab_test`,
     `resolve-email-variation` reasons `other_share` / `lost`. Guide section
     "A/B tests".
   - Editor: an "A/B test" section in the Variations panel: each version's share
     and results, who's ahead and how sure, "Pick as winner", "Start over", and
     "Add a test version". Route `POST gh/v4/emails/{id}/variations/winner`.
   - Broadcast scheduler: "A/B test on a sample first?" under the batching
     settings, shown when the email has ready test versions (otherwise a link to
     add one): sample %, wait hours, and clicks or opens. Turning it on hides
     recurring and extra dates. `POST gh/v4/broadcasts` takes `ab_test`
     `{sample_percent, wait_hours, metric}` and returns the sample's broadcast.
   - Gotcha found: `Base_Object_With_Meta::update_meta()` passes the object's
     cached value as `prev_value`, so writing through a stale object silently does
     nothing. The variation code writes through a fresh `Email` (`fresh()`).
   - Tests: `tests/phpunit/unit-tests/includes/class-email-ab-tests.php`.

## Open questions

1. **Pricing.** Language variations look like core (they fix a long-standing
   gap). Should A/B variations be core too, or sit with the premium Split Test
   step?
2. **Locale overwrite policy.** Should a form submitted on the French site
   overwrite a contact already marked English, or only fill an empty locale?
3. **Nesting.** A/B inside a language in the first version, or later?
4. **Flow tests and winners.** For flows (no natural end), auto-declare a
   winner after N sends at a confidence threshold, or always leave it to the
   user or agent?
