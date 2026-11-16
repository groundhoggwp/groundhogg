# Telemetry

How Groundhogg sends usage data home: what runs when, which options hold its state, the hooks an add-on uses to add its
own data, and how to test it and turn it on. For every field in the payload, where it comes from and what the
backend does with it, see [telemetry-payload.md](telemetry-payload.md).

**Rule of thumb:** counts and dates, never content. Nothing is sent unless the site opted in, and nothing about the
site's address, the license key, contacts or what's in emails is ever sent.

| File | What's in it |
|---|---|
| `includes/telemetry.php` | `Telemetry`: the flag, opting in, the site ID, the envelope, sending, lifecycle events, milestones, the ability counter, the notification. One instance, `Plugin::instance()->stats_collection`. |
| `includes/telemetry-snapshot.php` | `Telemetry_Snapshot`: builds the weekly snapshot, one method per section. |
| `tests/phpunit/unit-tests/includes/class-telemetry-tests.php` | The tests, with the flag on and off. |

---

## Contents

1. [The flag](#1-the-flag)
2. [Opting in](#2-opting-in)
3. [Messages](#3-messages)
4. [The weekly snapshot](#4-the-weekly-snapshot)
5. [Lifecycle events](#5-lifecycle-events)
6. [Milestones](#6-milestones)
7. [The notification for sites that opted in before](#7-the-notification-for-sites-that-opted-in-before)
8. [Options and cron hooks](#8-options-and-cron-hooks)
9. [Hooks for add-ons and sites](#9-hooks-for-add-ons-and-sites)
10. [Testing](#10-testing)
11. [Shipping schema 2](#11-shipping-schema-2)

---

## 1. The flag

Everything below sends to one endpoint, `Telemetry::ENDPOINT` (`https://telemetry.groundhogg.io/v2/collect`). That's
"schema 2". It's behind a flag until the endpoint is live:

```php
Telemetry::is_v2(); // GROUNDHOGG_TELEMETRY_V2 if defined, else Telemetry::V2_DEFAULT (false), then the groundhogg/telemetry/v2 filter
```

| | Flag off (the default for now) | Flag on |
|---|---|---|
| Weekly | The old ping to the CRM, `send_legacy_snapshot()` → `LEGACY_WEEKLY_WEBHOOK` | The snapshot → `ENDPOINT` |
| Opting in from the guided setup | The old opt-in webhook, `LEGACY_OPTIN_WEBHOOK` | The old opt-in webhook, plus `opted_in` → `ENDPOINT` |
| Lifecycle events, milestones | Not sent | Sent |
| Install date, milestones | Recorded | Recorded and sent |
| Ability calls | Not counted | Counted, reset by each snapshot |
| Site ID | Not made | Made with the first message |
| The notification | Not in the feed | In the feed for sites that opted in without a site ID |
| Wording in settings and guided setup | The old wording | The new wording |

Anything new that sends goes through `can_send()`, which checks the flag, the opt-in and the notice. Don't call
`send()` without it.

## 2. Opting in

The opt-in is the `gh_opted_in_stats_collection` option, read through `Telemetry::is_enabled()` (filter:
`groundhogg/stats_collection/is_enabled`). There are three ways to turn it on:

- **The guided setup** calls `Telemetry::optin( $subscribed )`. That saves the current user's email to
  `gh_telemetry_email`, turns the option on, and posts to the old opt-in webhook (the CRM's newsletter and discount
  flow).
- **The settings page** checkbox (Misc › Danger Zone › Share usage data) saves the option.
- **The update-settings ability** sets `opted_in_stats_collection`.

All three end in `add_option_`/`update_option_gh_opted_in_stats_collection`. `optin_option_updated()` watches for it
going from off to on, wherever that happened. With the flag on, it then:
- sends `opted_in`, the only message that carries an email;
- schedules the first snapshot a minute later;
- schedules any milestones already reached.

Opting out sends nothing. The site just stops.

## 3. Messages

### Identity

| Option | What |
|---|---|
| `gh_telemetry_site_id` | A UUID, made by `get_site_id()` the first time a message is built |
| `gh_telemetry_url_hash` | `Telemetry::url_hash()`: sha256 of `home_url()`, lowercased, without the scheme, `www.` or a trailing slash |
| `gh_telemetry_cloned_from` | Set when the stored hash no longer matches. The site was cloned or moved, so it gets a new ID and reports the old one until a snapshot gets through. |

The raw URL is never sent. Telemetry posts with its own `post()`, a `wp_remote_post()` whose User-Agent is just
`Groundhogg/<version>`, because `remote_post_json()` puts `home_url()` in the User-Agent.

### The envelope

Every message starts with `envelope( $type, $key )`. `send()` adds the message's own fields after it, and they can't
overwrite it:

```json
{ "schema": 2, "type": "snapshot", "site_id": "…", "message_id": "<site_id>:<type>:<key>", "sent_at": "2026-11-16T20:31:04Z",
  "url_hash": "…", "cloned_from": null, "environment": "production", "groundhogg": "4.11" }
```

- `message_id` lets the backend ignore repeats. `<key>` is the ISO week for snapshots, the milestone's name for
  milestones, and the time for everything else.
- `environment`:
  - `demo` for a site `is_demo_environment()` recognises: our `*.groundhogg-demo.com` sites, InstaWP, TasteWP and
    WordPress Playground. Override it with `GROUNDHOGG_IS_DEMO`, and add hosts with the `groundhogg/demo_host_suffixes`
    filter. InstaWP on any domain is also found by its must-use plugins (`is_instawp_site()`: `0-iwp-*.php`, or a
    must-use plugin authored by InstaWP).
  - Otherwise `wp_get_environment_type()`, except that a production site `is_staging_environment()` spots counts as
    `staging`. That check looks at the request's host, which a real cron job doesn't have. Demo detection uses
    `home_url()` instead.

### Sending

```php
$telemetry->send( string $type, array $data = [], bool $blocking = false, string $key = '' ); // true|WP_Error
```

- **Lifecycle events don't wait** (`blocking` false, 1s timeout), because they happen during someone's request.
- **Snapshots and milestones wait** (15s timeout) because they run from cron and need to know whether they got
  through. A non-2xx answer is a `WP_Error`.
- `groundhogg/telemetry/message` can change any message or drop it (return false). `groundhogg/telemetry/endpoint`, or
  the `GROUNDHOGG_TELEMETRY_URL` constant, changes where they go.

## 4. The weekly snapshot

The `groundhogg/telemetry` cron event runs weekly (scheduled on `admin_init`). With the flag on,
`maybe_send_telemetry()` calls `send_snapshot()`, which:

1. sends `upgraded` if the version changed (see below),
2. builds `( new Telemetry_Snapshot() )->build()` and sends it,
3. on success, records `gh_telemetry_last_sent`, clears `gh_telemetry_last_error`, resets the ability counts,
   clears `cloned_from`, and sends any milestones that haven't been sent;
4. on failure, records the error in `gh_telemetry_last_error` and keeps everything for next week.

`build()` runs each section (`system`, `license`, `addons`, `plugins`, `size`, `activity`, `features`, `health`,
`onboarding`) on its own. A section that throws is sent as `{ "error": "<exception class>" }` and doesn't stop the
others. Then `groundhogg/telemetry/snapshot` lets add-ons add theirs under `extensions`.

### Adding a field

- **Counts, dates, versions, slugs, booleans only.** No names, titles, emails, URLs, IPs, custom field keys or values,
  or message text, including Site Health messages (send the status).
- **Cheap on a site with a million contacts.** Use an indexed column (`contacts.date_created`, `events.time`,
  `activity.timestamp`), a small table (flows, steps, emails, tags), or an option. Activity counts cover
  `Telemetry_Snapshot::PERIOD` (7 days) so they ride the time indexes. Anything that loads objects needs a cap, like
  `email_editors` (500 emails, 90 days). Check `KEY` lines in `db/*.php` before counting by a column: `submissions`
  has no date index, which is why form submissions aren't counted.
- **The same shape every week.** Wrap a map that can be empty in `self::map()` so it's `{}` and not `[]`.
- **Use `Table_Query`.** It doesn't take `MIN()`/`MAX()`. Order by the column and `setLimit( 1 )`, like
  `Telemetry_Snapshot::earliest()`.
- **Update the docs.** Add the field to [telemetry-payload.md](telemetry-payload.md), and to the README's
  "Telemetry and usage" section once schema 2 is on (until then, to the schema 2 README text in the payload spec).

## 5. Lifecycle events

| Type | When | Where |
|---|---|---|
| `opted_in` | The opt-in turns on | `optin_option_updated()` |
| `activated` | Plugin activation; `first_activation` says whether it's the first on this site | `activating()` on `groundhogg/main/activating` records it, `activated()` on `groundhogg/main/activated` sends it |
| `deactivated` | Plugin deactivation | `groundhogg/main/deactivated` |
| `upgraded` | `GROUNDHOGG_VERSION` differs from `gh_telemetry_last_version` | `maybe_send_upgraded()` on `admin_init` and before each snapshot (there's no general "plugin updated" hook) |

`gh_installed_at` is set on `groundhogg/main/activating`, before the installer creates anything. Sites installed
before it existed get an estimate on the first `admin_init`, from the earliest of the first contact (the installer
creates one), the first flow and the update routine dates, marked with `gh_installed_at_estimated`.

## 6. Milestones

The first time each step of getting started happens on a site, it's sent once as a `milestone` message. These are
for the onboarding funnel: the weekly snapshot alone can't show what a site did in the days before it gave up.

| Milestone | Reached on |
|---|---|
| `setup_finished` | `add_option_`/`update_option_gh_guided_setup_finished` turning on |
| `contact_added` | `groundhogg/contact/created`, except during activation (the installer's own contact) |
| `contacts_imported` | `groundhogg/contact/imported` |
| `form_submitted` | `groundhogg/submission/post_create` with type `form` |
| `flow_activated` | `groundhogg/funnel/post_update` to `active`, or `post_create` as `active` |
| `flow_email_sent` | `groundhogg/event/complete`, a flow event with an email |
| `broadcast_sent` | `groundhogg/event/complete`, a broadcast event |
| `agent_connected` | `added_user_meta` for `Mcp\Connection::SEEN_META` |
| `mobile_app_used` | `added_user_meta` for `Mobile\Promo::USED_META` |

`reach_milestone( $name )`:
- **Records the time** in `gh_telemetry_milestones`, autoloaded, so the check on busy paths like completing events
  costs nothing once a milestone has been reached.
- **Records whether or not the site opted in**, so the dates are right if it opts in later.
- **Never sends during the request.** It schedules `groundhogg/telemetry/milestones` a minute later.
  `send_milestones()` sends each one not yet in `gh_telemetry_milestones_sent`, and stops at the first failure.
  Failures are retried after the next successful snapshot, and the snapshot's `onboarding.milestones` carries every
  date as a backstop.
- **Skips old sites.** Sites with an estimated install date don't record milestones, or they'd report a first flow
  from years ago as new.

To add a milestone: add its name to `Telemetry::MILESTONES` (names outside it are ignored), hook it in the constructor
with a public method that calls `reach_milestone()`, add a case to `test_each_milestone_is_reached_from_its_hook`, and
add it to the table in [telemetry-payload.md](telemetry-payload.md). Keep the hook cheap: it runs on every occurrence,
not just the first.

### The ability counter

`count_ability()` on `wp_after_execute_ability` (the Abilities API, WordPress 6.9+) counts each ability whose name
starts with `groundhogg`. It only counts while opted in with the flag on. The counts are kept in
`gh_telemetry_ability_counts`, sent in `activity.abilities`, and reset by each successful snapshot.

## 7. The notification for sites that opted in before

A site that's opted in but has no `gh_telemetry_site_id` agreed to the old, smaller ping. That covers sites that opted
in before schema 2, or while the flag was off, because new opt-ins only make a site ID with the flag on.
`needs_notice()` holds back every new message from such a site until an admin has seen what's shared now.

- **Where it shows.** `maybe_add_notification()` (on `admin_init`) adds it to the notifications feed, the bell, with
  `notices()->add_notification( 'telemetry-schema-2', … )`. It's for `manage_options` users only, and links to the
  setting to stop sharing.
- **What counts as seen.** An admin opening the feed marks it read, which fires `groundhogg/notices/read`; dismissing it
  fires `groundhogg/notices/dismissed`. `notification_seen()` listens to both and records `gh_telemetry_notice_shown`,
  which lets sending start. Someone without `manage_options` reading it doesn't count.
- **White-labeled sites** have no bell, so the feed shows as a banner on the plugin's pages, and dismissing it counts.
- **How long.** It stays in the feed for anyone who hasn't dismissed it, and it's removed if the site opts out or the
  flag is off.

## 8. Options and cron hooks

All are `gh_` options, so uninstalling removes them. `functions.php` clears the cron hooks on uninstall.

| Option | What |
|---|---|
| `gh_opted_in_stats_collection` | The opt-in |
| `gh_telemetry_email` | The email sent with `opted_in` (and with the old weekly ping) |
| `gh_telemetry_site_id`, `gh_telemetry_url_hash`, `gh_telemetry_cloned_from` | Identity, see above |
| `gh_telemetry_last_version` | The version `upgraded` compares against |
| `gh_telemetry_last_sent`, `gh_telemetry_last_error` | How the last snapshot went. Check here first when a site isn't reporting. |
| `gh_telemetry_ability_counts` | Ability calls since the last snapshot |
| `gh_telemetry_milestones`, `gh_telemetry_milestones_sent` | When each milestone was reached, and which have been sent |
| `gh_telemetry_notice_shown` | When an admin first read or dismissed the notification |
| `gh_installed_at`, `gh_installed_at_estimated` | The install date, and whether it's an estimate |

| Cron hook | When |
|---|---|
| `groundhogg/telemetry` | Weekly: the snapshot, or the old ping while the flag is off |
| `groundhogg/telemetry/snapshot_soon` | Once, a minute after opting in |
| `groundhogg/telemetry/milestones` | Once, a minute after a milestone is reached |

## 9. Hooks for add-ons and sites

| Hook | Type | Use |
|---|---|---|
| `groundhogg/telemetry/snapshot` | filter | Add an add-on's own counts to the snapshot |
| `groundhogg/telemetry/message` | filter | Change or drop (return false) any message before it's sent |
| `groundhogg/telemetry/endpoint` | filter | Where messages go (or the `GROUNDHOGG_TELEMETRY_URL` constant) |
| `groundhogg/telemetry/v2` | filter | Turn schema 2 on or off (or the `GROUNDHOGG_TELEMETRY_V2` constant) |
| `groundhogg/stats_collection/is_enabled` | filter | Override the opt-in |

An add-on adds its counts under its own download ID:

```php
add_filter( 'groundhogg/telemetry/snapshot', function ( $snapshot ) {

	$snapshot['extensions'][ 12345 ] = [
		'orders_tracked_7d' => my_addon_count_orders( WEEK_IN_SECONDS ),
	];

	return $snapshot;
} );
```

The rules from "Adding a field" apply: counts only, and cheap. The filter runs from cron once a week.

## 10. Testing

- **PHPUnit:** `--filter Telemetry_Tests` (see the PHPUnit setup notes for this machine). The tests turn the flag on in
  `setUp()`, catch every request with `pre_http_request` and answer with `$this->status`. `flag_off()` turns the flag
  off for the tests about that. `new_site()` gives a real, non-estimated install date for milestone tests.
- **On a dev site, end to end:**
  - Add `define( 'GROUNDHOGG_TELEMETRY_V2', true );` and `define( 'GROUNDHOGG_TELEMETRY_URL', '<a request bin or a dev backend>' );` to `wp-config.php`.
  - Opt in from the settings, then run the `groundhogg/telemetry/snapshot_soon` and `groundhogg/telemetry/milestones`
    cron events.
  - `gh_telemetry_last_error` says why a snapshot didn't get through.
- **Preview a snapshot without sending it:**
  - Run `wp eval-file` on a script that prints `( new \Groundhogg\Telemetry_Snapshot() )->build()` as JSON.
  - Don't call `envelope()` in it: it makes a site ID, which skips the notice on a site that opted in before.
  - On groundhogg.dev, WP-CLI needs `-d memory_limit=1024M`.

## 11. Shipping schema 2

When the endpoint is live:

1. Set `Telemetry::V2_DEFAULT` to `true`.
2. Replace the README's "Telemetry and usage" section with the schema 2 text in
   [telemetry-payload.md](telemetry-payload.md#feature-flag).
3. Regenerate translations. The schema 2 wording is already in the source, but the `.po` files predate it.

Sites that already opted in get the notification in their feed, and start sending once an admin has read it. Later, once traffic to the old webhooks has died
down, remove `send_legacy_snapshot()`, `LEGACY_WEEKLY_WEBHOOK`, the flag and the old wording. Keep the old opt-in
webhook until the endpoint forwards `opted_in` to the CRM.
