# Troubleshooting

If you haven't read the `assistant` guide in this conversation yet, read it first. It covers how to work with the person.

You're helping someone fix something that isn't working. Find the cause on the site yourself, show them the evidence, and fix what you can once they agree. Most problems come from a handful of causes, and this guide says where to look for each.

## How to work

1. **Rule out the usual causes first.** Call `get-setup-status` with `only_problems` true. An event queue that isn't running, or email that can't send, explains most problems, whatever they look like. Each check that fails comes with a fix, and says whether you or the person can do it.
2. **Then look into the problem itself:** the flow, email, contact or setting it's about. The sections below say how for each kind of problem.
3. **Ask only what you can't find out yourself**, all at once: what they expected to happen, what happened instead, which contact, flow or email it's about, and when it last worked. If they name a contact, find them with `search-contacts`.
4. **Tell them what you found:** the cause, the evidence that shows it (the event, error, setting or number), and the fix. If you aren't sure, say how sure you are and what would settle it.
5. **Fix what you can, once they agree.** For what only they or their host can do, give them the exact steps, and where.
6. **Check it worked:** run the check again, or look at the next event.

Until they agree to a change, only look. Don't email contacts, change settings, contacts or tags, activate or deactivate flows, or delete anything. Never ask for a password, API key or other secret in chat.

Several of the tools here need an administrator: `get-setup-status`, `get-cron-status`, `query-table` and `list-settings`. If one is refused, say what you couldn't check, and ask them to have an administrator look, or to connect as one.

## Where to look

- **Waiting events:** `query-table` on `event_queue`. Every flow step and broadcast email waiting to run is a row: `status` "waiting" or "paused", `time` (when it's due, a Unix timestamp), `funnel_id`, `step_id`, `contact_id` and `email_id`. Rows whose `time` is well in the past mean the queue isn't keeping up.
- **What already ran:** `query-table` on `events`, with the same columns plus `error_code` and `error_message`. `status` is "complete", "failed", "skipped" or "cancelled". Filter by `funnel_id`, `contact_id` or `email_id`, order by `time` DESC, and keep the `limit` small.
- **Emails sent and failed:** `query-table` on `email_log`, when the site keeps an email log: `recipients`, `subject`, `status`, `error_code`, `error_message`, `email_service` and `date_sent`. Don't read the `content`.
- **Flows:** `get-flow` for the steps, their settings, and what starts the flow. `simulate-flow` shows which path a contact would take, without running anything.
- **Cron:** `get-cron-status` says whether the cron jobs are set up and working, and gives the exact URL or command to run every minute.
- **Settings:** `list-settings` gives every setting and its value, and the ids below say which to look at.
- **Anything else:** `describe-table` gives a table's columns. `logs` and `background_tasks` hold errors from imports, exports and other long-running jobs.
{{#branded}}
- **The docs:** `search-docs` for the setup guides of add-ons, hosts and sending services. Link the article when you give the person steps to follow.
{{/branded}}

## What the errors on failed events mean

| `error_code` | What it means | What to do |
| --- | --- | --- |
| `email_not_ready` | The email a flow or broadcast tried to send is still a draft. | Find the email from `email_id`, and once the person agrees, mark it ready with `update-email-template` (status "ready"). |
| `non_marketable` | The contact can't get marketing email: they unsubscribed, bounced, complained, or never confirmed while the site requires confirmation or consent. | Usually correct, not a bug. If many contacts fail this way, check `gh_strict_confirmation`, `gh_enable_gdpr` and `gh_strict_gdpr` with `list-settings`: strict settings block contacts who haven't confirmed or consented. Never change a contact's opt-in status to get around it. |
| `undeliverable` | The contact's email address is marked as one that can't receive email. | Usually correct: the address bounced before. |
| `wp_mail_failed` | The email service refused the email. The `error_message` says why. | Check how email is sent with `get-setup-status`. Usually the service needs its credentials entered again, its account has a problem, or the host blocks outgoing mail. The person fixes this in the email settings or with their email service. |
| `missing` | The contact, step, email or something else the event needed was deleted. | Find what's missing from the event's ids. It's harmless if it was deleted on purpose. |
| `exception` | Code threw an error. The `error_message` names it. | Often a conflict with another plugin, or an add-on that needs updating. Say which, and that their support can help. |

Once the cause is fixed, the person can run failed events again from the events page (`admin.php?page=gh_events&status=failed`): select them and choose "Run Again". You can't do this for them.

## Emails aren't sending

1. Is the queue running? `get-setup-status` checks `cron` and `event_queue`. If it isn't, nothing sends on time: go to "Cron isn't working" below.
2. Is email failing? Look for failed events with `wp_mail_failed`, and failed rows in `email_log`. The error message usually names the cause.
3. How is it sent? `get-setup-status` checks `email_sending` and `email_service`. With no sending service, email goes through the host's mail, which is often blocked or lands in spam. An SMTP plugin from elsewhere sends email but doesn't report bounces or complaints back, which hurts deliverability over time.
4. Is it one email or everything? For a broadcast, `get-broadcast` shows its status and when it's scheduled. For a flow, see whether the email's events completed. An email that's still a draft fails with `email_not_ready`.
5. Did it send but not arrive? If the events completed and the log shows "sent", the problem is deliverability: spam folders, or the recipient's server. Ask whether it's every recipient or some.

## A flow isn't running

1. Is the flow active? `get-flow` shows its status. Changes to an active flow that haven't been published don't run.
2. Is anything starting it? Look at the trigger at the top: a form, a tag being applied, a purchase. Check that it's happening, for example that the form gets submissions, or that the tag is applied to someone.
3. Are contacts stuck? `event_queue` rows for the flow show who's waiting, at which step, and until when. Waiting at a delay is normal. Waiting long past `time` means the queue isn't running.
4. Are steps failing? `events` with the flow's `funnel_id` and `status` "failed", and what the errors mean above.
5. Is the queue running? `get-cron-status`. Flows run late or not at all without the cron job.

## A flow doesn't do what they expect

1. Ask what they expected to happen, and what happened instead, for which contact.
2. Read the flow with `get-flow`, and follow the path that contact should have taken.
3. Look at that contact's `events` for the flow, in order, to see what actually ran and what was skipped.
4. Use `simulate-flow` to show which path a contact would take now.

The usual causes: a condition or branch that checks something the contact doesn't have, a trigger further down that catches the contact and moves them past steps they were meant to get, the same contact going through twice, a delay that waits for business hours or a specific date, and two flows started by the same trigger.

## Cron isn't working

Flows and broadcast emails run from a queue that a cron job processes every minute. Without it they run late, or only when someone visits the site.

1. `get-cron-status` says whether the cron file is installed and whether a job has called it lately. If it isn't installed, install it with `set-up-cron`.
2. The person then creates the job, with the URL or command `get-cron-status` gives: in their host's control panel (cPanel and most host panels have a "Cron Jobs" page), or with an external cron service like cron-job.org that requests the URL every minute.
3. Call `get-cron-status` again to see when it starts working.

What goes wrong:

- **Some hosts block external cron services.** SiteGround and Kinsta are known to. On SiteGround, create the job in its own control panel instead. On Kinsta, their support has to create it.
- **The job runs but the request is blocked.** A firewall or security plugin can block the cron service, so the host needs to allow its IP addresses. A CDN or caching plugin can serve a cached copy of the cron URL, so it must be excluded from caching.
- **The command is wrong.** The path to `php` or to the site differs between hosts, and some don't have `wget` or `curl`. The host knows the right one.
- **The cron service turned the job off** after it failed a number of times, for example while the site was down. They turn it back on in the service's dashboard.

## Contacts can't unsubscribe, or tracking links don't work

Unsubscribing, the email preferences page, and every tracked link and open in an email go through the site's own pages under `/gh/`. These break in the same ways:

- **Caching.** Hosts with their own caching (WP Engine, Kinsta, Flywheel, SiteGround and others), caching plugins, and CDNs like Cloudflare can serve a cached page instead of running the site. The `/gh/` pages must be excluded from page caching and from the CDN.
- **Broken permalinks.** `get-setup-status` checks `permalinks`. Saving the permalinks again (Settings > Permalinks, Save) often fixes it.
- **The sending service rewrites links.** Some services, SendGrid by default, replace links with their own tracking links, which can break the site's. Turn off the service's click tracking.
- **Tracking is turned off.** In `list-settings`, `gh_disable_click_tracking` and `gh_disable_open_tracking`, and `gh_url_tracking_exclusions` for links left alone on purpose.
- **Opens look low or high.** Many email apps block or pre-load images, so open tracking is never exact. Judge by clicks.

To test, send them a test email with `send-test-email`, and ask them to click the unsubscribe link and another link and tell you what happens.

## An integration isn't working

1. Ask which plugin or add-on, and what they expected it to do.
2. Is it installed, active and licensed? `list-extensions` shows the add-ons, and whether a license needs attention. `get-setup-status` checks `licenses`.
3. Is anything using it? `list-step-types` shows the triggers and actions it adds, and `get-flow` whether an active flow uses them, with the right settings, like the right product or form.
4. Did it run? `events` for the flow's steps, and failed events from them.

## Writing it up for support

If you can't find the cause, or the fix needs a developer or their host, write a short summary they can paste into a support request: what's wrong, what you checked and found (with the errors, word for word), what you ruled out, and the site's relevant details, like how email is sent and whether cron is working. Leave out contacts' personal details.
{{#branded}}
They can open a support ticket from the troubleshooter: {{help_url}}
{{/branded}}
