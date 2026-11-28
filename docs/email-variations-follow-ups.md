# Email variations: follow-ups after 4.13's first cut

A proposal for what is left on email variations (multilingual versions and A/B
tests), written 2026-11-27 from a review of every open session. It follows
`docs/email-variations-plan.md`, which has the design and what each phase built.

Nothing here is started. Items marked **Decision** need Adrian's answer first.

## Where things stand

- Phases 1 to 4 and the review fixes are on `master` and pushed. `origin/master` is at
  `4bbe866c2`, which includes the review fixes (`cb08dcf78`).
- Core is version 4.13 (`5a64f0cc2`, not pushed, not tagged). The 4.13 update adds
  `emails.parent_id`, and now also the AI usage table and `use_ai`.
- Settled since the plan's "Not changed" list was written:
  - **Link-clicked rewrite:** fixed in `5a64f0cc2`. `Link_Clicked::replace_links_in_email_content()`
    loops over `Email_Variations::get_family_ids()`.
  - **Empty trash:** not a bug. Deleting an email deletes its variations through
    `groundhogg/email/pre_delete`, and no screen can trash a variation on its own.
  - **`_db_version` written when `dbDelta` fails:** accepted as is.
  - **Stale objects:** the convention is `$object->pull()` (or a fresh object) at the
    call site. `Base_Object_With_Meta::update_meta()` stays as it is.

- **Closest-language fallback** (built 2026-11-27, uncommitted, at Adrian's request):
  - When no variation matches the contact's language exactly,
    `Email_Variations::resolve()` tries again by language alone, so a fr_CA contact
    gets the fr_FR version.
  - It doesn't apply when the email's own language is the closer match (en_GB on an
    en_US site gets the original), or to zh, whose varieties use different scripts.
  - Filters in the audience still have to match.
  - Sites can change it with the filter `groundhogg/email/variation/closest_language`.

## Proposed order

| # | Item | Size | Before 4.13 ships? |
|---|---|---|---|
| 1 | Verify what was never run | half a day | Yes |
| 2 | Tell someone when a broadcast test's second send fails | small | Yes |
| 3 | A variation's status can only be draft or ready | tiny | Yes |
| 4 | Housekeeping: `.pot`, plan doc, dev test data | tiny | Yes |
| 5 | Managed pages in the contact's language | small | Built, uncommitted; checked under item 1 |
| 6 | Editor UI for filter audiences, order and test weights | medium | No |
| 7 | Weglot | small | No, **Decision** |
| 8 | Variations for global blocks | n/a | Not doing, **Decision** |

## 1. Verify what was never run

These are the riskiest gaps, because two of them send email to real audiences.

- **Broadcast A/B end to end with real cron** on groundhogg.dev: a sample, the
  wait, the winner, the rest. Once with batching and once in the contact's local
  time. Check the rest excludes the sample and that cancelling the sample cancels
  the rest.
- **WPML and Polylang installed**, one at a time: site languages, the form's
  `gh_locale`, the preferences page language choice, a send resolving the right
  variation. So far only the adapters' unit tests have run.
- **The 4.13 update on a site that hasn't had `parent_id` added by hand.**
  groundhogg.dev got the column manually, so the upgrade path is unexercised.

Done when: each has been run and what was seen is written into the plan doc, with
any bug found fixed or listed.

## 2. Tell someone when a broadcast test's second send fails

Today `Email_Variations::finish_broadcast_test()` catches the error, stores
`status: failed` with the message on the email's `variation_broadcast_test` meta,
and stops. Only the email's report shows it. Most of the audience gets nothing and
nobody is told.

The second send is a normal `Broadcast::schedule()`, so it fails for the reasons
any broadcast is refused. Most are not transient, so a blind retry would fail the
same way:

| Cause | Transient? |
|---|---|
| The email was set back to draft during the wait (`DraftException`) | No |
| The email was trashed during the wait | Not refused today, see below |
| A filter in the audience can't be applied any more | No |
| Nobody is left outside the sample who can receive it | No, and not really a failure |
| "Unable to schedule events" (queue insert failed) | Yes |
| The cron request died between `finishing` and the send | Yes, and it stays `finishing` |

`Broadcast::schedule()` refuses only a draft (`$email->is_draft()`), not a trashed
email. So if the email is trashed during the wait, the rest is scheduled and
recorded as `done`, and each event then fails because `Email::send()` sends only a
ready email. `finish_broadcast_test()` should check `is_ready()` itself before
scheduling and record `failed` with the reason.

Proposal:

1. **Notify.** On `failed`, add a notification for users who can schedule
   broadcasts, naming the email and the reason, and linking to its Variations panel.
   - Use `notices()->add_notification( $id, [ 'title', 'message', 'type' => 'error',
     'cap' => 'schedule_broadcasts', 'location' => 'both' ] )`. It is stored, and
     gated by the capability.
   - Not `notices()->add()`. That is a one-minute transient for the current
     request's user, so from a cron run nobody sees it.
   - Remove the notification on retry, or when the test is restarted.
   - Also email the user who scheduled the test. The record needs no new field:
     the sample's `Broadcast::get_scheduled_by_id()` already has that user.
2. **"Send the winner now."** A button in the Variations panel's A/B section,
   shown when the record is `failed` or stale `finishing`. It calls a new
   `Email_Variations::retry_broadcast_test( Email $email )` that re-runs the send
   half of `finish_broadcast_test()` with the stored winner, query and schedule.
   Route: `POST gh/v4/emails/{id}/variations/broadcast-test/retry`, `schedule_broadcasts`.
   Same thing for agents: an `action: retry` on `send-email-broadcast`'s `ab_test`
   is awkward, so prefer a line in `get-email-variation-report`'s output saying it
   failed and why, and let `send-email-broadcast` with the same audience be the retry.
3. **Recover a stuck `finishing`.** Store `finishing_at`. A `finishing` record
   older than 15 minutes is treated as `failed` with the reason "interrupted".
   Check it where the record is read (`editor_data()` and the report), not with
   another cron event.
4. **"No contacts left" is `done`,** not `failed`: catch `NoContactsException`
   separately and record `rest_broadcast_id: 0`.

Refactor first: split `finish_broadcast_test()` into "decide" (waiting, winner)
and "send the rest", so the retry reuses the second half.

Tests (`class-email-ab-tests.php`):

- A failed send leaves a notification and a record that can be retried.
- A retry sends to the audience minus the sample, exactly once.
- A stale `finishing` reads as failed.
- No contacts left reads as done.
- An email trashed during the wait reads as failed, with no broadcast scheduled.

## 3. A variation's status can only be draft or ready

`Email::sanitize_columns()` accepts `ready`, `draft`, `trash` and `global` for any
email (`includes/classes/email.php`, the `status` case). A REST update could put a
variation in the trash, where no screen shows it. Harmless today (it stops being
sent and is deleted with its parent), but it is a state the UI can't get out of.

Proposal: when `$this->is_variation()`, allow only `draft` and `ready`. One test.

## 4. Housekeeping

- Regenerate `languages/` (`.pot` and friends) once the strings from items 2 and 3
  are in. The workflow is in the project memory "i18n regen workflow".
- Update `docs/email-variations-plan.md`: the "Not changed" paragraph, per
  "Where things stand" above.
- groundhogg.dev still has test emails #44 to #47. **Decision:** delete them, or
  keep them for the verification in item 1 and delete after.

## 5. Managed pages in the contact's language

**Built (2026-11-27, uncommitted):**

- `Multilingual::switch_for_managed_page()` runs on `template_redirect` at
  priority 8.
- `get_managed_page_locale()` picks the language: the variation's
  `variation_locale` for `browser_view` and `emails`, otherwise the contact's.
  Sites can change it with the filter `groundhogg/multilingual/managed_page_locale`.
- `switch_page_to()` does the switch. It is safe to call twice, and does nothing
  when the site isn't in that language or WordPress can't switch to it.
- `filter_page_locale()` hooks `locale` at `PHP_INT_MAX` for catch 1. It asks the
  locale switcher for the switched locale rather than calling `determine_locale()`,
  which would recurse through `get_locale()`.
- `templates/preferences.php` calls `switch_page_to()` after a contact saves a new
  language (catch 2).
- 5 new tests are in `class-multilingual-tests.php`.
- **Not yet checked** with Polylang or WPML active, or in a browser. That stays
  under item 1.

The rest of this section is the proposal it was built from.

The plan's "Links" bullet. The preferences, unsubscribe, browser-view and archive
pages show in the site's default language, whatever the contact's language or the
version they got.

Earlier this proposed localizing the URLs in emails, with WPML, Polylang and
TranslatePress code and a send-language context. That is replaced by the idea
below (Adrian's): switch the locale when the page is opened. It needs no URL
changes, no per-plugin code and no send-time state. It avoids WPML's
different-domain cookie risk, and it works with any multilingual plugin or none.

### Why it works

`templates/managed-page.php` is Groundhogg's own standalone template. It prints
its own `<html>` and never calls `get_header()`, so every string on these pages is
the plugin's own gettext.

### What I checked (2026-11-27)

- **The contact is known by `template_redirect` on every sub-page that needs one.**
  - `get_contactdata()` falls back to `tracking()->get_current_contact()`, which is
    the tracking cookie (read at `after_setup_theme`, priority 1) or the logged-in
    user.
  - Click and open URLs are parsed in the `request` filter, before
    `template_redirect`.
  - Browser view and archive already refuse to render without a contact
    (`Rewrites::template_include()`).
  - Where no contact is known, the page doesn't show one, so there is nothing to
    switch.
- **Browser view renders the version that was sent.** `templates/archive/event.php`
  builds `new Email( $event->email_id )`, and `Email_Variations::attribute_event()`
  rewrites that to the variation. So the locale should come from that email's
  `variation_locale` first, then the contact's.
  - The same goes for the `emails` sub-page (one email from the archive), if the
    email is a variation.
  - `build()` doesn't switch locale itself (only `send()` does), so today the
    email's own strings, like the footer, show in the default language there.
- **Translations and `<html lang>` follow the switch, even under Polylang.**
  - Just-in-time textdomain loading (`_load_textdomain_just_in_time()`) and
    `get_bloginfo( 'language' )` both use `determine_locale()`.
  - `WP_Locale_Switcher` filters `determine_locale`. Polylang 3.9.2 doesn't.
  - So `language_attributes()` in `managed_page_head()` follows the switched
    locale with nothing extra.
- **Catch 1: Polylang overrides `get_locale()`.**
  - `PLL_Frontend_Filters` adds a `locale` filter at priority 10 in
    `pll_language_defined()`. That is after `WP_Locale_Switcher::init()` added its
    own, so Polylang's runs last and wins.
  - So after `switch_to_locale()`, `get_locale()` still returns Polylang's language
    for the URL, which is the default language on an unprefixed managed page URL.
  - Anything Groundhogg does on the page that reads `get_locale()` sees the wrong
    language. That includes `Multilingual::get_site_locale()`, whose comment
    assumes `get_locale()` is the switched one.
  - Fix: while switched on a managed page, add a `locale` filter at
    `PHP_INT_MAX` that returns the switched locale. Compare against
    `determine_locale()`, not `get_locale()`, when deciding to switch.
  - WPML isn't installed locally, so its behaviour is unverified.
- **Catch 2: changing language on the preferences page.**
  `templates/preferences.php` saves a new locale while rendering, after the switch.
  So the page that confirms the change would still show in the old language.
  Switch again right after that save, or the confirmation is in the language the
  contact just left.
- **Polylang also does a canonical redirect at `template_redirect` priority 4.**
  It runs before ours at 9, and it happens today already, so this changes nothing
  there.
- **A language with no Groundhogg translation files** falls back to English
  strings. That is no worse than today.

### Proposal

1. A `Multilingual::switch_for_managed_page()`, hooked to `template_redirect` at
   priority 8, so it runs before `Rewrites::template_redirect()` (9) and
   `Tracking`'s (10).
   - Only when `is_managed_page()` and a contact is known.
   - Locale: for `browser_view`, the event's email's `variation_locale`. For
     `emails`, the email's own. Otherwise the contact's locale.
   - Only if `Multilingual::is_site_locale()` and it differs from
     `determine_locale()`.
   - `switch_to_locale()`, plus the late `locale` filter from catch 1.
   - No restore needed: the request ends with the page.
2. In the preferences save (`templates/preferences.php`, around line 516), switch
   to the new locale after it is saved.
3. A filter `groundhogg/multilingual/managed_page_locale`, so a site can choose
   another locale or opt out by returning ''.
4. Leave tracked clicks alone. `Tracking::redirect_to_target()` could localize
   links to the site's own content, but that is a separate feature.

Size: small. It could ship in 4.13 once it has been checked with Polylang active
(part of item 1).

Tests (`class-multilingual-tests.php`, with a site language stubbed through the
`groundhogg/multilingual/site_languages` filter):

- With a contact in a site locale, a managed page switches and `determine_locale()`
  returns it.
- A browser view of a variation uses the variation's locale over the contact's.
- A locale the site doesn't have, or no contact, doesn't switch.
- A non-managed page doesn't switch.

## 6. Editor UI for filter audiences, order and test weights

Agents can already do all of this through `create-` and `update-email-variation`.
The editor's Variations panel can only add a language or an evenly split test
version; a filter audience shows as a label and can't be changed there.

Proposal, all inside the existing panel (`VariationsControls` and friends in
`assets/js/admin/emails/email-block-editor.js`):

- **Audience.** An Edit button on a non-test variation opens a modal: languages
  (multi-select of the site's languages) and filters (the same filter picker
  broadcasts and segments use). "Add a variation" gets the same modal, so a
  variation can be for a segment and not a language.
- **Order.** Audience variations resolve first match wins, by `variation_priority`.
  Show them in that order with drag handles; dropping saves priorities.
- **Test weights.** A number input per test version, the email's own share shown
  as the remainder, refused over 100, with a note that changing shares moves
  contacts between versions only for those not yet sent to.
- **REST.** `PATCH gh/v4/emails/{id}/variations/{variation_id}` to
  `Email_Variations::update()` (audience, priority, weight, locale), and
  `POST …/variations` accepts an audience. `edit_emails`, the parent's edit lock,
  errors from `update()` passed through as 400s.

`.min.js` is rebuilt by the PhpStorm watchers. One session reported the watcher
not firing for this file, so check the minified file's timestamp after saving.

Tests: REST tests for the new route (permission, validation, weights over 100,
a test version refusing an audience). The modal is checked by hand in Chrome on
groundhogg.dev.

## 7. Weglot (**Decision**)

Weglot isn't read today because it uses two-letter codes, not WordPress locales,
and it translates the page's output rather than loading translations, so
`get_locale()` never changes.

What variations need from it is small: the site's languages and the language a
form was shown in.

Proposal: an adapter in `Multilingual`, using Weglot's helpers (probably
`weglot_get_languages_available()` and `weglot_get_current_language()`) and a
code-to-locale map with a filter. Nothing more.

The helper names are **unverified**: Weglot isn't installed on any local site, and
plugins.trac.wordpress.org refuses automated reads. Item 5 needs nothing from
Weglot, because it switches from the contact's locale.

Recommendation: do it only if a customer asks. The filters
(`groundhogg/multilingual/plugin`, `site_languages`, `current_locale`) already
let a site wire it up in a snippet, which could be a docs article instead.

## 8. Variations for global blocks (**Decision**, recommend no)

Adrian was leaning no, and the code agrees: `Email_Variations::create()` refuses
templates and global blocks. A variation is a full copy of the email, so a global
block inside a French variation is already the place to put French content, by
making a second global block. Per-language global blocks would need resolution
inside block rendering, a second place where "which version" is decided.

Recommendation: don't build it. Add one sentence to the email authoring guide
(`includes/abilities/guides/email.md`) saying to use a separate global block per
language.

## Open questions for Adrian

1. Item 2: is an admin notice enough, or should the scheduling user also get an email?
2. Item 4: delete test emails #44 to #47 now or after verification?
3. Item 5 is built. Ship it in 4.13 once item 1 has checked it with Polylang active,
   or hold it for a 4.13.x?
4. Item 7: Weglot adapter, docs article, or nothing?
5. Item 8: confirm no.
6. Still open from the plan: A/B tests in core or premium, and whether flows ever
   declare a winner automatically.

## Conventions for whoever builds this

- PHP 7.4 syntax. Stale object: `pull()` first.
- Commit to `master`, only when Adrian asks. Don't push unprompted.
- No "Groundhogg" in UI copy (white label), and AI wording stays vendor neutral.
- Add-ons that count one `email_id` use `Email_Variations::get_family_ids()`.
