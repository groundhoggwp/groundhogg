# Setting up the site

You're setting up this CRM for someone: their business details, privacy rules, how email is sent, the cron jobs that run automation, and then whatever they want built. Most of it you can do. Some of it only a person can do, and your job there is to tell them exactly what, where and why.

## Start here

1. Call `get-setup-status`. It lists what's left to set up and what's stopping automation or email from working, and for each one whether you can fix it (and with which abilities) or what a person has to do.
2. Gather what you need from the person in one go, rather than question by question as you hit each step. Use what's already set (`list-settings`) and only ask about what's missing:
   - Business name, postal address and phone number (they show in every email footer).
   - Links to their privacy policy and terms.
   - Who they email: people in Canada? In the EU, UK or California? Is the business itself in the EU or UK?
   - The name and address email should come from, and whether team members send as themselves.
   - Which email service they use or want (see "Sending email").
   - Who should own new contacts by default.
   - What they want built: emails, flows, forms, and the contacts to bring in.
3. Work through the sections below, then call `get-setup-status` again and keep going until everything you can fix is good.

## Business details and policies

Save them with `update-settings`: group `business_info` (`business_name`, `street_address_1`, `street_address_2`, `city`, `region`, `zip_or_postal`, `country`, `phone`) and `policies` (`privacy_policy`, `terms`, as full URLs). Anti-spam laws require a postal address in marketing email, so don't skip the address.

If they have no policy pages, say so; don't invent links. WordPress may already have a privacy policy page, `get-setup-status` mentions it.

## Privacy and consent

Turn their answers into settings the same way the guided setup does, with `update-settings`:

| They... | Set |
|---|---|
| email people in Canada (CASL) | `strict_confirmation: true`: new contacts must confirm their email address to keep getting marketing email after `confirmation_grace_period` days (default 14) |
| email people in the EU, UK or California (GDPR, CCPA) | `enable_gdpr: true`: a consent checkbox on forms, and contacts can download or delete their data |
| are based in the EU or UK | `enable_gdpr: true`, `strict_gdpr: true` (only email contacts who gave consent) and `disable_unnecessary_cookies: true` |

Only change the ones an answer covers, so you don't undo something set on purpose. If they use a cookie consent plugin, set `consent_cookie_name` and `consent_cookie_value` to the cookie it sets when a visitor accepts; ask them, or check the plugin's documentation.

With double opt-in on, contacts need a way to confirm: a flow that sends an email with `{confirmation_link}` after they sign up. See the guides for `email` and `flow`.

## Sending email

**The sender.** Set the default from name and address with `save-sender-profile` (`id: "default"`), or `update-settings` (`override_from_name`, `override_from_email`). Use an address on the business's own domain: sending as a gmail.com or other free address from a website fails the checks inboxes run, and the email goes to spam. For team members who send as themselves, use `save-sender-profile` with `"user-<user id>"`; add other addresses, like support@, as custom profiles.

**The email service.** Email sent straight from the web server mostly lands in spam. A person has to connect a real sending service, because it needs an account and credentials:

- A sending service offered in the email settings: they connect it there, signing in to its account.
- An SMTP or email API add-on (SMTP, SendGrid, AWS SES and others): you can install a licensed one with `install-extension`; they enter its credentials on its settings tab.
- An SMTP plugin from WordPress.org: they install and set it up; `wp_mail` then sends through it.

**Never ask for a password, API key or license key in chat.** Tell the person where to enter it themselves. The one exception is a license key they choose to give you for `activate-license`.

Once a service is connected, choose it for each kind of email with `update-settings`: `wordpress_email_service`, `transactional_email_service` and `marketing_email_service` (see `list-settings` for the services available). `"wp_mail"` sends with WordPress's default, or through an SMTP plugin if one is set up. `"log_only"` doesn't send at all.

**Authenticating the domain.** The service will ask the person to add DNS records (SPF, DKIM and a DMARC policy) for the sending domain. Only they, or whoever manages their DNS, can do that. Point it out; it's the biggest difference to whether email arrives.

**Check it works.** Turn on `log_emails` while setting up, then send a test with `send-test-email` to the person and ask them whether it arrived in the inbox, not spam. `query-table` on `email_log` shows what was sent and any errors.

## Cron

Flow steps and broadcast emails run from an event queue that should run every minute. Until a real cron job runs it, it only runs when someone visits the site, so on a quiet site automation runs late.

1. `set-up-cron` installs the cron file.
2. `get-cron-status` gives the exact URL or command for the cron job. A person with access to their host or server creates it, to run every minute. Many hosts have a cron tool in their control panel, and free cron services that request a URL work too.
3. Call `get-cron-status` again: the queue cron is working once it has run in the last 90 seconds.
4. Optional: once they also have a cron job calling `wp-cron.php` every minute, `set-up-cron` with `wordpress_cron: "cron_job"` stops WordPress cron from running on page loads, which speeds the site up. Don't do this without that second job, or imports and broadcast scheduling stop.

## Owners and team

`list-owners` shows who can own contacts. Set the default owner with `update-settings` (`primary_user`). Adding team members means creating WordPress users with the right role (Sales Representative, Sales Manager or Marketer), which a person does in Users › Add New.

## Contacts

New WordPress users become contacts automatically (`disable_user_sync` turns that off). For the users the site already had, run `sync-users` once.

To bring in a list, use `import-contacts` with the CSV as text or a link to the file, mapping its columns to contact fields. Before you do, ask the person three things and pass their answers: did these people confirm their email addresses, did they agree to their data being processed, and did they agree to marketing email? Never decide consent yourself. For a handful of people, `create-contact` works too.

## Tracking

Open and click tracking and page visit tracking are on by default. Turn them off with `disable_open_tracking`, `disable_click_tracking` or `disable_page_tracking` only if the person asks. `url_tracking_exclusions` leaves specific links untracked.

## Building

Emails, flows and forms have their own guides: `get-authoring-guide` with topic `email` or `flow`. Check `list-flow-templates` first: if a template is close to what's wanted, `import-flow-template` and then change it with `edit-flow`. Templates come with placeholder content from the library, like consent text naming another business and success pages on other sites, so go through every form and email and make it the site's own.

Forms are built as a web form step in a flow. `create-flow` and `get-flow` return each form's `embed`: a shortcode for a page on the site, a hosted page to link to, and an iframe for other websites. Tell the person where to put it, or put it on a page if your connection can edit pages.

Build everything as drafts and inactive flows, and let the person review before anything is sent or switched on.

## Finishing

1. Call `get-setup-status` and fix what you can.
2. Set `guided_setup_finished: true` with `update-settings`, so the setup reminder goes away.
3. Give the person a short list of what's left for them: connecting the email service, DNS records, the cron job, putting forms on pages, reviewing and switching on flows. Include where to do each one.
