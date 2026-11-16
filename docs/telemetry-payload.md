# Telemetry payload, schema 2

What an opted-in site sends home, where each field comes from, and what that means for the backend that receives it.
Built in `includes/telemetry.php` and `includes/telemetry-snapshot.php`, behind a flag that is off until the endpoint is
live (see "Feature flag" below). Schema 1 is the old weekly ping to the CRM, which is still what sites send while the
flag is off. For how the code works, its hooks, and testing and shipping it, see [telemetry.md](telemetry.md).

## Feature flag

`Telemetry::is_v2()` turns schema 2 on. It's `Telemetry::V2_DEFAULT` (false) unless the `GROUNDHOGG_TELEMETRY_V2`
constant or the `groundhogg/telemetry/v2` filter says otherwise. Point a test site at a dev backend with
`GROUNDHOGG_TELEMETRY_URL`.

While it's off:

- **What's sent:** opted-in sites send the old weekly ping to the `1727` CRM webhook (`send_legacy_snapshot()`), plus
  the old opt-in webhook from the guided setup, as before. Nothing goes to the new endpoint: no snapshot, no events,
  no milestones.
- **What's recorded:** install dates, the last version seen and milestones are still recorded, so the dates are right
  when the flag is turned on. Ability calls aren't counted, because only schema 2 snapshots reset the counts.
- **Opting in** makes no site ID. A site that opts in while the flag is off agreed to the old wording, so it sees the
  notice when the flag is turned on, like sites that opted in before.
- **The wording** in the settings, the setting's description for agents and the guided setup follows the flag. The
  README describes schema 1.

To ship schema 2:

1. Set `V2_DEFAULT` to true.
2. Replace the README's "Telemetry and usage" section with the text below.
3. Once traffic to the old webhooks has died down, remove `send_legacy_snapshot()`, `LEGACY_WEEKLY_WEBHOOK` and the
   schema 1 wording.

README text for schema 2:

> Sharing usage data is opt-in. You can turn it on or off from Groundhogg » Settings » Misc » Danger Zone » Share usage data, or in the guided setup. When it is on, the following is sent to `https://telemetry.groundhogg.io`:
>
> * Once, when you opt in: your email address, whether you also asked for the newsletter, and your time zone.
> * Once a week: a random ID for the site and a one-way hash of its address (never the address itself); whether it is a production, staging, development or demo site; the versions of Groundhogg, WordPress, PHP and the database, the site language and time zone; your plan, its expiry date and a one-way hash of your license key (never the key itself); which Groundhogg add-ons are installed and licensed; the folder names of your active plugins; totals of contacts (by opt-in status), tags, flows, emails, campaigns, custom fields, saved searches and team members; for the last 7 days, the number of new contacts, emails sent, automation events, opens, clicks, broadcasts, abilities run by AI agents, and people who used the mobile app; which flow step types are in your active flows and which editors your recent emails were made with; a few settings (GDPR, confirmation, email logging, bounce checking); whether email sending, cron and the event queue are working; and when you installed Groundhogg, finished the setup, and added your first contact, flow and email.
> * When it happens: the plugin being activated, deactivated or updated (with the versions).
> * Once each, the first time it happens on a new site: finishing the setup, adding a contact, importing contacts, a form being submitted, activating a flow, a flow sending an email, sending a broadcast, connecting an AI agent, and using the mobile app (just the name of the milestone and when, nothing about what it was).
>
> Your contacts, what's in your emails, and the names or titles of anything you create are never shared. Nothing is sent when sharing is off, and turning it off sends nothing either.

## Principles

- **Opt-in only**, as now. Nothing in this document is sent without `gh_opted_in_stats_collection`.
- **Identity and usage are separate.** Only the `opted_in` event carries the email. The raw site URL is never sent, only
  a hash of it. Snapshots carry a random site ID and nothing that names the person. The backend joins the two only
  where it needs to (the CRM).
- **Counts, never content.** No contact data, no email content, no subjects, no titles, no custom field names or values.
  Active plugin slugs are the one list of names that gets sent.
- **Only data the site already has.** Every field comes from an existing table, option or Site Health check, except two
  small counter added for this (ability calls) and the install date.
- **Every query is cheap on a big site.** Each one either uses an index or hits a small table (flows, steps, emails).
  Activity counts cover the last 7 days so they ride the `time` indexes.
- **The schema is versioned and can be extended.** Add-ons add their own section through a filter. The backend stores
  the whole JSON, so a new field needs no backend change.

## Identity

| Option | What | Notes |
|---|---|---|
| `gh_telemetry_site_id` | `wp_generate_uuid4()`, made on first send | The key for everything. Never shown in the UI. |
| `gh_telemetry_url_hash` | `sha256( normalized home_url )` at the time the ID was made | When the current hash differs, the site was cloned or moved: make a new ID and send `cloned_from` once. |
| `gh_installed_at` | Timestamp, set on activation from now on | Older sites backfill it from the earliest contact or flow, flagged `estimated`. |

Staging, development and demo sites send like any other, marked by `environment` in the envelope. A staging clone gets
its own ID through the URL hash check, and points back to the live site with `cloned_from`. The backend leaves
non-production sites out of the default dashboards, and can still report on them (how many customers stage, which hosts
clone, how many people try it in a sandbox).

## Envelope

Every request has the same envelope. `type` says what kind of message it is.

```json
{
  "schema": 2,
  "type": "snapshot",
  "site_id": "0c1b6f7e-2f4a-4a51-9a39-6b8f3f1d2e77",
  "message_id": "0c1b6f7e-...:snapshot:2026-W47",
  "sent_at": "2026-11-16T20:31:04Z",
  "url_hash": "9f2c...e1",
  "cloned_from": null,
  "environment": "production",
  "groundhogg": "4.3.2"
}
```

`environment` is one of `production`, `staging`, `development`, `local` or `demo`:

- **`demo`:** whatever WordPress says, for a site `is_demo_environment()` recognises, since these are usually set to
  production. That's our own demo sites (`*.groundhogg-demo.com`), sandbox services (InstaWP's `*.instawp.xyz`,
  `.link` and `.co`; TasteWP's `*.s2-tastewp.com`), and WordPress Playground (`playground.wordpress.net`, or PHP running
  as `PHP.wasm`). It goes by `home_url()`, so it works in cron too. `GROUNDHOGG_IS_DEMO` overrides it, and
  `groundhogg/demo_host_suffixes` adds hosts. An InstaWP site on its own domain is found by the must-use plugins
  InstaWP installs (`is_instawp_site()`): files named `0-iwp-*.php`, or any must-use plugin with InstaWP as its
  author. InstaWP also hosts live sites, and those count as demo too.
- **Otherwise** `wp_get_environment_type()`, except that a site `is_staging_environment()` detects (host signals,
  staging subdomains, `GROUNDHOGG_IS_STAGING`) reports `staging` even when WordPress says `production`.

Demo sites matter on their own: hundreds of demo sites were handed out over the years, and sandbox sites are mostly
people trying a plugin. Counted as production, both would inflate active installs and drag down retention.

`message_id` makes retries idempotent. A snapshot is identified by its ISO week, and an event by its type plus a
timestamp.

## Lifecycle events

| `type` | When | Extra fields |
|---|---|---|
| `opted_in` | Opt-in through guided setup or settings | `email`, `newsletter` (bool), `time_zone`. The only message with identity. No raw URL, the envelope's `url_hash` is enough to recognise the site. A `snapshot` follows right away. |
| `activated` | Plugin activation, while opted in | `first_activation` (bool) |
| `deactivated` | Plugin deactivation, while opted in | none |
| `upgraded` | `GROUNDHOGG_VERSION` differs from `gh_telemetry_last_version` (checked on `admin_init` and before each snapshot, since there's no general "plugin upgraded" hook) | `from`, `to` |
| `milestone` | The first time something that matters in getting started happens, once per milestone per site | `milestone`, `reached_at` (UTC, to the second). `message_id` is `<site_id>:milestone:<name>`. |

### Milestones

The weekly snapshot misses the first week, which is when an install is won or lost. A site that gives up on day 2
would send `opted_in`, one empty snapshot and `deactivated`, and nothing about how far it got. Milestones fill that in
without sending an event for every action. There are nine, and each is sent once:

| Milestone | Reached when | Hook |
|---|---|---|
| `setup_finished` | The guided setup is marked as done | `add_option_`/`update_option_gh_guided_setup_finished` |
| `contact_added` | A contact is created, other than the one the installer makes for whoever activated the plugin | `groundhogg/contact/created` |
| `contacts_imported` | A contact is imported | `groundhogg/contact/imported` |
| `form_submitted` | A form submission is saved | `groundhogg/submission/post_create` (type `form`) |
| `flow_activated` | A flow becomes active, or is created active | `groundhogg/funnel/post_update`, `groundhogg/funnel/post_create` |
| `flow_email_sent` | A flow event with an email completes | `groundhogg/event/complete` |
| `broadcast_sent` | A broadcast event completes | `groundhogg/event/complete` |
| `agent_connected` | An AI agent uses the site over MCP for the first time | `added_user_meta` for `gh_mcp_last_seen` |
| `mobile_app_used` | Someone opens the mobile app for the first time | `added_user_meta` for `gh_mobile_app_used` |

- **Recording.** A milestone's time is stored in `gh_telemetry_milestones`, an autoloaded option, so the check on busy
  paths like completing events costs nothing. It's recorded whether or not the site opted in, so the dates are right
  if it opts in later.
- **Sending.** Never during the request that reached it. A single cron event a minute later sends whatever hasn't
  been sent (tracked in `gh_telemetry_milestones_sent`), and so does opting in. One that fails is tried again after
  the next successful snapshot. The snapshot's `onboarding.milestones` also carries every date, as a backstop.
- **Only new sites.** Sites with an estimated install date (installed before this was recorded) don't record
  milestones. They'd report a first flow from years ago as new. The install date is now set on
  `groundhogg/main/activating`, before the installer creates its contact, so a fresh install's date isn't mistaken
  for an estimate.

## Snapshot (weekly)

Sections, each with where it comes from and what it costs. Counts are exact.

### `system`

| Field | Source |
|---|---|
| `php` | `PHP_VERSION` |
| `wp` | `get_bloginfo( 'version' )` |
| `db` | `$wpdb->db_server_info()`, reduced to `mysql 8.0.36` / `mariadb 10.11.6` |
| `locale` | `get_locale()` |
| `time_zone` | `wp_timezone_string()` (a rough stand-in for region) |
| `multisite` | `is_multisite()` |
| `object_cache` | `wp_using_ext_object_cache()` |
| `white_labeled` | `is_white_labeled()` |
| `safe_mode` | `gh_safe_mode_enabled` |

### `license`

Replaces `master_license`, so the key itself is never sent.

| Field | Source |
|---|---|
| `premium` | `has_premium_features()` |
| `plan` | Master license `item_name` (e.g. "Pro Plan"), or null |
| `price_id` | Master license `price_id` |
| `expires` | Master license `expires`, date only |
| `key_hash` | `sha256( master key )`. The backend hashes EDD's keys the same way to join. |
| `licenses` / `expired` | `License_Manager::get_num_licenses()`, `get_expired_licenses()` |

### `addons`

`[{ "id": 123, "version": "3.1.0", "licensed": true }]` from `Extension::get_extensions()`, the same list and license
check Site Health uses. This replaces the comma-separated `extensions` string.

### `plugins`

Every active plugin, as its folder slug (`woocommerce`, not `woocommerce/woocommerce.php`), with no versions. It's
`active_plugins`, plus `active_sitewide_plugins` on multisite. Groundhogg's own add-ons are already in `addons`, so
they're left out here.

This shows integrations we haven't thought of, but it's also the one field that can identify someone: a custom
plugin's slug can name the client or agency. The backend treats `plugins` as sensitive, and dashboards only show slugs
active on at least 10 sites.

### `size` (totals)

| Field | Source | Cost |
|---|---|---|
| `contacts` | `contacts` count | Uses the primary key |
| `contacts_by_status` | Grouped by `optin_status` | Indexed |
| `tags` | `tags` count | Small table |
| `flows` | `{ active, inactive }` grouped by `funnels.status` | Small table |
| `emails` | Non-template count | Small table |
| `campaigns`, `custom_fields`, `saved_searches`, `custom_objects` | Counts | Small tables / options |
| `team` | Users with `view_contacts` | One `get_users()` count query |

### `activity` (last 7 days)

| Field | Source | Cost |
|---|---|---|
| `contacts_created` | `contacts.date_created` | Indexed |
| `emails_sent` | `{ flow, broadcast, notification }`: `events` with `status = complete`, `email_id > 0`, grouped by `event_type` | `time` index range |
| `events_completed` / `events_failed` | `events` by `status` | `time` index range |
| `opens` / `clicks` | `activity` of type `email_opened` / `email_link_click` | `timestamp` index range |
| `broadcasts` | `broadcasts.send_time` in range | Small table |
| `abilities` | `{ "groundhogg/create-email": 4, ... }` from the new counter | One option |
| `agents_seen` | Users whose `gh_mcp_last_seen` is in the last 7 days | User meta |
| `mobile_users` | Users whose `gh_mobile_app_used` (written at most daily when the app opens) is in the last 7 days | User meta |

Form submissions are left out for now. `submissions.date_created` has no index, so counting a week of them scans the
whole table. Add the index first if we want this.

### `features`

| Field | Source | Cost |
|---|---|---|
| `step_types` | `{ "send_email": 41, "web_form": 6, ... }`: steps in active flows (`step_status = active`), grouped by `step_type`, including add-on step types | `steps` joined to `funnels`, small |
| `email_editors` | `{ blocks, html, legacy_blocks, legacy_plain }` from `Email::get_editor_type()` for emails updated in the last 90 days, at most 500 | Loads email objects, so it's capped |
| `mcp` | `{ adapter_active, connector_active, agents_ever }`: the plugins from `Groundhogg\Mcp\Connection`, and users an agent has ever used the site as (`gh_mcp_last_seen`) | Options, user meta |
| `settings` | `gdpr`, `gdpr_strict`, `confirmation_strict`, `email_logging`, `bounce_inbox`, `imap` | Already computed for Site Health › Info |

### `health`

| Field | Source |
|---|---|
| `checks` | `{ test_id: "good" / "recommended" / "critical" }` from `Site_Health::run()`, status only, no messages |
| `email_services` | `{ wordpress, transactional, marketing }` from `Groundhogg_Email_Services` |
| `wp_mail_plugin` | `extrapolate_wp_mail_plugin()` slug, or null |
| `gh_cron` | `{ installed, working }` from `gh_cron_installed()`, `is_event_queue_processing()` |
| `wp_cron` | `page_loads` / `cron_job` / `off_and_late` (same logic as `get-cron-status`) |
| `queue_seconds` | `gh_average_execution_time` |
| `overdue_events` / `background_tasks` | Same queries as Site Health › Info |

### `onboarding`

Dates only, so the backend can build install-week cohorts and activation funnels without the plugin storing anything new.

| Field | Source |
|---|---|
| `installed_at` | `gh_installed_at` (+ `installed_at_estimated`) |
| `guided_setup_finished` | `guided_setup_finished()` |
| `first_contact_at` | `MIN( contacts.date_created )`, indexed |
| `first_flow_at` | Earliest `funnels.date_created` among active flows |
| `first_email_at` | `MIN( events.time )` for complete events with an email. The `time` index lets it stop at the first match. |
| `milestones` | `{ name: "2026-11-16T20:31:04Z" }` from `gh_telemetry_milestones`, the same dates as the milestone messages |

### `extensions` (add-on data)

`apply_filters( 'groundhogg/telemetry/snapshot', $snapshot )` lets each add-on add `extensions[<download_id>] = [...]`
with its own counts, e.g. orders tracked or courses linked. Each add-on owns its own keys.

## In the plugin

Built in `includes/telemetry.php` (`Telemetry`: identity, sending, lifecycle, the notice) and
`includes/telemetry-snapshot.php` (`Telemetry_Snapshot`: the sections).

- **Sending.** Everything goes to `Telemetry::ENDPOINT`. Override it with the `GROUNDHOGG_TELEMETRY_URL` constant or
  the `groundhogg/telemetry/endpoint` filter. It's sent with `wp_remote_post()`, not `remote_post_json()`, because
  that puts `home_url()` in the User-Agent. Snapshots wait for the answer (15s timeout). Lifecycle events don't wait,
  so they never slow down someone's request. `groundhogg/telemetry/message` can change or drop any message.
- **Bookkeeping.** `gh_telemetry_last_sent` and `gh_telemetry_last_error` record how the last snapshot went. A
  successful snapshot resets the ability counts and clears `cloned_from`.
- **Ability calls** are counted on `wp_after_execute_ability` (the Abilities API, WordPress 6.9+), for names starting
  with `groundhogg`, only while opted in. The counts are kept in one option and reset by each successful snapshot.
- **Mobile users** come from `gh_mobile_app_used`, the user meta `Mobile\Promo::record_use()` already writes (at most
  daily). No new meta was needed.
- **`gh_installed_at`** is set on activation. Sites installed before it existed get an estimate on the first
  `admin_init`, from the earliest of the first contact (the installer creates one), the first flow, and the update
  routine dates. That's flagged with `gh_installed_at_estimated`.
- **Lifecycle events:**
  - `opted_in`: on `add_option_`/`update_option_gh_opted_in_stats_collection` going from off to on, wherever it was
    turned on (guided setup, settings, the update-settings ability). It schedules the first snapshot a minute later.
  - `activated` / `deactivated`: on `groundhogg/main/activated` and `groundhogg/main/deactivated`.
  - `upgraded`: on `admin_init`, and before each snapshot, when `GROUNDHOGG_VERSION` differs from
    `gh_telemetry_last_version`.
- **The old opt-in webhook** (`1724-telemetry-optin`) is still told about opt-ins from the guided setup, for the
  newsletter and the opt-in discount, until the endpoint forwards `opted_in` to the CRM. It goes out without the site
  address in the User-Agent too. The old weekly webhook (`1727`) is no longer used.
- **The notification for sites already opted in.** A site that is opted in but has no `gh_telemetry_site_id` (new
  opt-ins get one straight away) opted in to schema 1, so `needs_notice()` holds back everything until an admin has
  seen what's shared now.
  - It goes in the notifications feed (the bell) for `manage_options` users, with a link to the setting to stop
    sharing.
  - An admin reading it (opening the feed) or dismissing it records `gh_telemetry_notice_shown`, through the new
    `groundhogg/notices/read` and `groundhogg/notices/dismissed` actions. That lets sending start.
  - It stays in the feed until each person dismisses it, and goes if the site opts out.

## Example

```json
{
  "schema": 2,
  "type": "snapshot",
  "site_id": "0c1b6f7e-2f4a-4a51-9a39-6b8f3f1d2e77",
  "message_id": "0c1b6f7e-2f4a-4a51-9a39-6b8f3f1d2e77:snapshot:2026-W47",
  "sent_at": "2026-11-16T20:31:04Z",
  "url_hash": "9f2c41d0e1",
  "cloned_from": null,
  "environment": "production",
  "groundhogg": "4.3.2",
  "system": { "php": "8.2.18", "wp": "7.1.4", "db": "mariadb 10.11.6", "locale": "en_US", "time_zone": "America/Toronto", "multisite": false, "object_cache": true, "white_labeled": false, "safe_mode": false },
  "license": { "premium": true, "plan": "Pro Plan", "price_id": "2", "expires": "2027-03-01", "key_hash": "a1b2...", "licenses": 1, "expired": 0 },
  "addons": [ { "id": 22397, "version": "3.4.1", "licensed": true } ],
  "plugins": [ "woocommerce", "elementor", "elementor-pro", "wp-mail-smtp", "wordfence", "acme-client-tweaks" ],
  "size": { "contacts": 18342, "contacts_by_status": { "1": 2210, "2": 14980, "3": 812, "4": 120, "5": 140, "6": 30, "7": 50 }, "tags": 214, "flows": { "active": 12, "inactive": 31 }, "emails": 96, "campaigns": 5, "custom_fields": 18, "saved_searches": 7, "custom_objects": 0, "team": 3 },
  "activity": { "contacts_created": 143, "emails_sent": { "flow": 2210, "broadcast": 14500, "notification": 31 }, "events_completed": 9120, "events_failed": 4, "opens": 5230, "clicks": 610, "broadcasts": 1, "abilities": { "groundhogg/search-contacts": 12, "groundhogg/create-email": 2 }, "agents_seen": 1, "mobile_users": 2 },
  "features": { "step_types": { "web_form": 4, "send_email": 38, "delay_timer": 30, "apply_tag": 22, "if_else": 6, "wc_new_order": 2 }, "email_editors": { "blocks": 21, "html": 3, "legacy_blocks": 0, "legacy_plain": 1 }, "mcp": { "adapter_active": true, "connector_active": false, "agents_ever": 1 }, "settings": { "gdpr": true, "gdpr_strict": false, "confirmation_strict": false, "email_logging": true, "bounce_inbox": false, "imap": true } },
  "health": { "checks": { "event_queue": "good", "cron": "recommended", "email_service": "recommended", "failed_events": "good" }, "email_services": { "wordpress": "wp_mail", "transactional": "wp_mail", "marketing": "wp_mail" }, "wp_mail_plugin": "wp-mail-smtp", "gh_cron": { "installed": false, "working": false }, "wp_cron": "page_loads", "queue_seconds": 4.2, "overdue_events": 0, "background_tasks": 0 },
  "onboarding": { "installed_at": "2025-06-02", "installed_at_estimated": true, "guided_setup_finished": true, "first_contact_at": "2025-06-02", "first_flow_at": "2025-06-09", "first_email_at": "2025-06-10" },
  "extensions": { "22397": { "orders_tracked_7d": 88 } }
}
```

That's about 3 to 5 KB a week per site.

## What this means for the backend

1. **One endpoint, many message types.** `POST /v2/collect` takes the envelope, checks it against the schema for its
   version, rate-limits by `site_id`, and ignores a `message_id` it has already seen. The site gets a 2xx, and
   nothing in the response is needed yet (room for remote config later).
2. **Three stores, separated by sensitivity:**
   - `snapshots`: append-only, `site_id`, `week`, `schema`, `groundhogg`, `received_at`, plus the whole payload as
     JSON. This is the history; nothing in it identifies anyone.
   - `sites`: one row per `site_id` with `first_seen`, `last_seen`, `environment`, `cloned_from`, `deactivated_at`, the
     latest snapshot's main columns, and a derived status (active / gone quiet / deactivated / moved).
   - `identities`: `site_id` → email, written only from `opted_in`. Few people can read it, and it's the only thing
     synced to the CRM.
   - `events`: lifecycle and milestone messages (`site_id`, `type`, `milestone`, `occurred_at`), ordered to the minute
     so the onboarding funnel can be read as install → setup → first contact → first flow → first email, grouped by
     install week.
3. **JSON first, columns later.** Because the payload is stored whole, dashboards read through SQL views (e.g.
   `step_type_usage`, `version_mix`, `activation_funnel`) that flatten it. A new field is a new view, not a migration.
4. **Derived states, computed by the backend:**
   - active: a snapshot in the last 14 days
   - deactivated: by the event
   - moved: a newer production site claims it in `cloned_from`
   - gone quiet: no snapshot for 21+ days and none of the above. Without an opt-out message, this lumps together
     opting out, uninstalling without deactivating, a broken cron and a dead site. Read it as an upper bound on churn.
     Comparing it against EDD license renewals for licensed sites gives a check.

   Non-production sites are tracked like any other but left out of the default views. A staging clone that claims a
   production site in `cloned_from` doesn't mark it as moved.
5. **CRM sync, not CRM ingest.** A daily job pushes a small summary per identified site to its contact on groundhogg.io:
   plan, contacts, email service, key plugins, last seen, health problems. The CRM keeps the coupon/newsletter flow
   (forward `opted_in` to the existing 1724 webhook server-side) and segments for upsell and support, without storing
   history.
6. **Old plugin versions keep posting schema 1** to the 1724/1727 webhooks for months. Either leave those flows running
   until the traffic dies down, or point them at the new endpoint and translate them into `schema: 1` rows.
7. **Volume is small.** 20,000 sites × 52 weeks × 5 KB is about 5 GB a year of raw JSON. Keep raw snapshots for 13
   months, and keep weekly rollups indefinitely.
8. **Don't keep request metadata.** `remote_post_json()` puts `home_url()` in the User-Agent, so the endpoint drops the
   UA and IP after rate-limiting. Otherwise the snapshots stop being anonymous.

## Decisions (2026-11-16)

1. No raw site URL anywhere, only `url_hash`.
2. Send every active plugin slug, not a fixed list. The backend treats it as sensitive.
3. No `opted_out` message. Opting out just stops the sending.
4. Sites already opted in get a one-time notice, not a fresh opt-in.
5. Staging and development sites send too, identified by `environment`. So do demo and sandbox sites (our
   `*.groundhogg-demo.com` sites, InstaWP, TasteWP, WordPress Playground), as `demo`.
