# UTM tags on links to groundhogg.io

Every link from the plugin to groundhogg.io (or any `*.groundhogg.io` host) carries UTM
parameters, so groundhogg.io analytics can tell which screen and which placement sent the visit.
The tags hold no personal data: no site URL, no user, no contact, no IDs.

## The scheme

| Parameter      | Value                                                                                 |
|----------------|---------------------------------------------------------------------------------------|
| `utm_source`   | Always `groundhogg-plugin`.                                                           |
| `utm_medium`   | Where the link is shown: `wp-admin`, `email` or `ai` (see below).                     |
| `utm_campaign` | The screen or feature the link sits on, e.g. `funnels-edit`, `help-troubleshooter`.   |
| `utm_content`  | Optional. The placement, when a screen has several links to the same page, and on every upsell (e.g. `premium-step`, `missing-license`). |
| `utm_term`     | Not used.                                                                             |

Values are lower case with hyphens.

### `utm_medium`

- `wp-admin`: any link shown in the WordPress dashboard (admin pages, notices, the plugins list).
- `email`: notification emails the plugin sends to site admins (branded header and footer, the
  funnels upgrade notice).
- `ai`: URLs the plugin hands to an AI assistant through the abilities API (`search-docs`). A
  person clicks them in their AI client, not in wp-admin.

### `utm_campaign`

In wp-admin the campaign is the screen, worked out from the admin URL:

1. Take the `page` query var and drop a leading `gh_` (`gh_funnels` → `funnels`). The main
   `groundhogg` page is `dashboard`.
2. Append the `tab` var, then the `action` var, when they are present
   (`?page=gh_funnels&action=edit` → `funnels-edit`, `?page=gh_help&tab=troubleshooter` →
   `help-troubleshooter`).
3. Turn underscores into hyphens (`gh_guided_setup` → `guided-setup`).

Only Groundhogg pages are named this way. On other admin screens the campaign is the WordPress
screen for a short list of core screens (`plugins`, `plugin-install`, `update-core`,
`wp-dashboard`, `profile`, `users`) and `other-screen` otherwise, so the slugs of other plugins' pages never reach
groundhogg.io.

Outside wp-admin the campaign is set by the caller: `notification` for notification emails,
`search-docs` for the docs search ability, `plugins` for the plugin header links.

## How links get tagged

There are two paths, and both use the same rules.

**In the browser (wp-admin).** `assets/js/admin/utm-links.js` loads on every admin page. It tags
the groundhogg.io links already on the page and, through a `MutationObserver`, every one added or
changed later, so the tags show on hover and when a link is copied. Links the observer can't see,
such as inside a shadow root, are tagged on `mousedown`, `click`, `auxclick` and `contextmenu`
(capture phase), before the browser follows them or opens the context menu. This covers links in
translated strings, links built by JS components, and content loaded from groundhogg.io (the News
widget's posts, docs search results) without changing any markup or translation strings.

Links that are being written rather than followed are never touched: anything inside a
`contenteditable` element, the email editor's canvas (`#builder-content`), or an element with
`data-utm-skip`. Otherwise an editor could save the tags into an email or post.

A link (or any ancestor) can set its own values:

```html
<a href="https://groundhogg.io/pricing/" data-utm-content="upgrade-button">…</a>
<div data-utm-campaign="email-editor"> … links … </div>
```

JS can also build a tagged URL directly with
`GroundhoggUTM.url( 'https://groundhogg.io/pricing/', { content: 'upgrade-button' } )`.

**On the server.** `Groundhogg\utm_url( $url, $content = '', $campaign = '', $medium = 'wp-admin' )`
tags a URL in PHP, and `Groundhogg\utm_links_in_html()` tags the `href`s in a piece of HTML
(images keep their `src`). Use them where no browser script runs: emails, server-side redirects,
and URLs handed to AI assistants. The remote notifications from groundhogg.io are tagged this way
with `utm_campaign=notifications&utm_content=notice-<ID>`, so each notice's clicks can be told
apart. It is also fine for admin markup, where it adds a `utm_content` the script
would not know about.

Both paths only touch groundhogg.io hosts, keep any `utm_` parameter already in the URL and only
fill in the missing ones, and leave a URL alone when its `utm_source` is not `groundhogg-plugin`.

The exception is our own older schemes, `utm_source` `wp-plugins`, `plugin` or `admin-menu`
(`UTM_LEGACY_SOURCES`). Every add-on's plugin header still has
`Plugin URI: …?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash`. Those links get
their `utm_` parameters replaced, and the old `utm_campaign` (`plugin-uri`, `author-uri`) becomes
the `utm_content`. So an add-on's "Visit plugin site" link on the plugins screen arrives as
`utm_campaign=plugins&utm_content=plugin-uri`, with the add-on's download page as the landing page,
without a new release of the add-on. Fix the headers whenever an add-on is next released, but
nothing depends on it.
Links that send traffic from somewhere else keep their own scheme, such as the "Powered by"
affiliate links in contact-facing emails and pages (`utm_source=email`,
`utm_campaign=email-affiliate`).

## Changing or turning it off

The `groundhogg/utm_params` filter receives the parameters and the URL. Return an empty array to
leave links untagged. In wp-admin it runs once per page load with the URL
`https://groundhogg.io/`, and an empty result stops the script from loading.

```php
add_filter( 'groundhogg/utm_params', function ( $params, $url ) {
	return []; // no UTM tags
}, 10, 2 );
```

## Reading the data

In GA4, filter **Session source** to `groundhogg-plugin`, then break down by **Session medium**,
**Session campaign** and **Session manual ad content**. The landing page tells you which link on a
screen was clicked when no `utm_content` is set. GA4 puts an unknown medium in the "Unassigned"
channel, so add a custom channel group (medium matches `wp-admin|email|ai` and source is
`groundhogg-plugin` → "Plugin") to see it as one channel.
