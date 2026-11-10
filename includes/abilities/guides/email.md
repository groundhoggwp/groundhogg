# Building an email that stays editable

Emails on this site are edited in a drag-and-drop block editor. To make an email the site owner can open and keep editing there, save it with `create-email-template` (or `update-email-template`) using `editor: "blocks"`, and write `content` in the block format below. Plain HTML (`editor: "html"`) also sends fine, but it opens in a code editor instead, so use blocks unless you're asked otherwise.

## Before you write

1. Call `list-email-templates` with `expand: ["content"]` and look at one or two existing block emails. Reuse their structure, fonts and colors, so the new email looks like it belongs to this site. Swap in new copy; don't invent new markup when a working example is right there.
2. Call `list-replacement-codes` for the merge tags this site supports. Use them for greetings, sign-offs and compliance links instead of static text.
3. Call `list-sender-profiles` if the email should come from someone other than the default sender.

## The format

The saved content is ordinary HTML in which each block is wrapped in a pair of HTML comments that carry its type and settings. The editor parses those comments back into blocks when the email is opened.

```html
<table cellpadding="0" cellspacing="0" width="100%" role="presentation">
<tr><!-- {type}:{id} {json-props} --><td id="b-{id}">...the block's HTML...</td><!-- /{type}:{id} --></tr>
<tr><!-- {type}:{id} {json-props} --><td id="b-{id}">...</td><!-- /{type}:{id} --></tr>
</table>
```

Rules the parser enforces:

- `{type}` is lowercase letters only (`a-z`). No digits, underscores or camelCase.
- `{id}` is letters, digits and hyphens, unique within the email. `intro-1` is fine, it doesn't need to be a UUID.
- Exactly one space between `{id}` and `{json-props}`, and `{json-props}` must be valid JSON. Invalid JSON is not an error: the block is left in the email as literal text. Check it before saving.
- The closing comment repeats the exact same `{type}:{id}`.
- Each block is its own `<tr>`, all inside one outer `<table>`.

**The HTML between the comments is what gets sent.** Only `global` blocks are rebuilt when the email is sent. For every other type the JSON props only matter for editing the block later, so the HTML has to be correct on its own: inline styles, table-based layout, absolute URLs.

## Block types

### text

The HTML sits directly in the `<td>`, no wrapper. Allowed tags: `p`, `ul`, `ol`, `li`, `h1`-`h3`, `a`, `strong`, `b`, `em`. The editor inlines the font styles from the props onto each tag; do the same so the email looks right before it's reopened.

```html
<!-- text:intro {"p":{"lineHeight":"1.4","fontFamily":"system-ui, sans-serif","fontWeight":"normal","fontSize":"16","fontStyle":"normal","textTransform":"none"},"a":{"color":"#488aff"}} --><td id="b-intro" style="padding:20px">
<p style="line-height: 1.4;font-family: system-ui, sans-serif;font-weight: normal;font-size: 16px;margin: 0 0 1em">Hi {first::there},</p>
<p style="line-height: 1.4;font-family: system-ui, sans-serif;font-weight: normal;font-size: 16px;margin-bottom: 0">Last paragraph, no bottom margin.</p>
</td><!-- /text:intro -->
```

Props `p`, `a`, `h1`, `h2`, `h3` are font style objects: `lineHeight`, `fontFamily`, `fontWeight`, `fontSize`, `fontStyle`, `textTransform`, and optionally `color`.

### heading

```html
<!-- heading:title {"tag":"h2","content":"Section title","fontStyle":{"lineHeight":"1.4","fontFamily":"system-ui, sans-serif","fontWeight":"500","fontSize":24,"fontStyle":"normal","textTransform":"none"},"alignment":"left"} --><td id="b-title">
<h2 style="line-height: 1.4;font-family: system-ui, sans-serif;font-weight: 500;font-size: 24px;margin: 0;text-align: left">Section title</h2>
</td><!-- /heading:title -->
```

`tag` is one of `h1`, `h2`, `h3`, `h4`, `p`.

### image

```html
<!-- image:hero {"src":"https://example.com/image.png","alt":"Description","width":600,"align":"center","link":"https://example.com"} --><td id="b-hero" style="padding: 20px">
<div class="img-container" style="text-align: center">
<a href="https://example.com"><img src="https://example.com/image.png" alt="Description" width="600" height="auto" style="vertical-align: bottom;height: auto;border-width: 0px;max-width: 100%"></a>
</div>
</td><!-- /image:hero -->
```

Leave out the `<a>` when there's no link.

Where images come from, in this order:

1. Images you were given, or that the site's existing emails already use (`list-email-templates` shows them).
2. A relevant image from the site's media library. Search it with your connection's own media library tools if it has them, otherwise with `search-media`.
3. An image that's online and that you're allowed to use: add it to the media library with `upload-media`, giving its `url` so the site downloads it, and use the URL it returns. Never write base64 yourself, it won't be a valid image.
4. Otherwise a placeholder from placehold.co, the size of the image it stands in for, labelled with what belongs there: `https://placehold.co/600x300/png?text=Product+photo`. Keep `/png` in the URL, the default is SVG and many email clients don't show SVG images. Write spaces in the label as `+`. Full width is 600 wide, half of a two column row about 290, a third about 190.

Never make up an image URL or link to an image on someone else's site. When you use placeholders, say which images need replacing.

### button

```html
<!-- button:cta {"link":"https://example.com","align":"center","text":"Read more","size":"md","backgroundColor":"#dd3333","style":{"color":"#ffffff","fontSize":18,"fontWeight":"600","fontFamily":"Arial, sans-serif"}} --><td id="b-cta">
<table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation"><tr><td align="center">
<table border="0" cellspacing="0" cellpadding="0" role="presentation" width="auto"><tr>
<td class="email-button" bgcolor="#dd3333" align="center" style="padding: 12px 18px;border-radius: 3px;background-color: #dd3333">
<a href="https://example.com" style="color: #ffffff;font-weight: 600;font-family: Arial, sans-serif;font-size: 18px;text-decoration: none;display: inline-block;background-color: #dd3333">Read more</a>
</td>
</tr></table>
</td></tr></table>
</td><!-- /button:cta -->
```

`size`: `sm` (padding `8px 12px`), `md` (`12px 18px`), `lg` (`16px 24px`). `align`: `left`, `center`, `right`, or `justify` (makes the inner table `width="100%"`).

### spacer

```html
<!-- spacer:gap-1 {"height":20} --><td id="b-gap-1" style="padding: 5px"><table cellspacing="0" cellpadding="0" role="presentation"><tr><td height="20" style="height: 20px"></td></tr></table></td><!-- /spacer:gap-1 -->
```

### divider

```html
<!-- divider:line-1 {"height":2,"color":"#cccccc","width":100,"lineStyle":"solid"} --><td id="b-line-1"><hr class="divider" style="border-width: 2px 0px 0px;width: 100%;border-color: #cccccc;border-style: solid"></td><!-- /divider:line-1 -->
```

`lineStyle`: `solid`, `dashed`, `dotted`, `double`. `width` is a percentage.

### columns

Each column holds its own blocks, as `<tr>` rows inside a `<table class="column">`:

```html
<!-- columns:row-1 {"layout":"two_columns","gap":10} --><td id="b-row-1">
<table class="email-columns two_columns responsive" cellspacing="0" cellpadding="0" width="100%" role="presentation" style="table-layout: fixed;border-spacing: 0px;width: 100%"><tr class="email-columns-row">
<td class="email-columns-cell one-half" width="50%" style="width: 50%;vertical-align: top;padding: 0px">
<table class="column" cellpadding="0" cellspacing="0" role="presentation" width="100%">
  <!-- one <tr> per block in the first column -->
</table>
</td>
<td class="email-columns-cell gap" width="10" style="width: 10px">&nbsp;</td>
<td class="email-columns-cell one-half" width="50%" style="width: 50%;vertical-align: top;padding: 0px">
<table class="column" cellpadding="0" cellspacing="0" role="presentation" width="100%">
  <!-- the second column's blocks -->
</table>
</td>
</tr></table>
</td><!-- /columns:row-1 -->
```

`layout`: `two_columns` (50/50), `three_columns` (33/33/33), `four_columns`, `two_columns_left` (67/33), `two_columns_right` (33/67), `three_columns_center` (25/50/25). Put the gap cell between columns, not after the last one.

### menu

A row of text links:

```html
<!-- menu:nav {"links":[["Blog","https://example.com/blog"],["Shop","https://example.com/shop"]],"align":"center","gap":20,"separator":"&bull;"} --><td id="b-nav">
<table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation"><tr><td align="center">
<table border="0" cellspacing="0" cellpadding="0" role="presentation" width="auto"><tr>
<td><a href="https://example.com/blog" style="color: #000000;font-size: 14px;text-decoration: none">Blog</a></td>
<td class="gap" width="20" align="center" style="width: 20px;text-align: center">&bull;</td>
<td><a href="https://example.com/shop" style="color: #000000;font-size: 14px;text-decoration: none">Shop</a></td>
</tr></table>
</td></tr></table>
</td><!-- /menu:nav -->
```

### social

Always use `"use":"custom"` with the links listed in `socials`. `"use":"global"` depends on settings that only exist inside the editor, and renders empty otherwise.

```html
<!-- social:follow {"align":"center","theme":"brand-circle","use":"custom","socials":[["facebook","https://facebook.com/example"],["instagram","https://instagram.com/example"]],"gap":10,"size":24} --><td id="b-follow">
<table class="socials" cellpadding="0" cellspacing="0" role="presentation" width="100%"><tr><td align="center">
<table class="socials" cellpadding="0" cellspacing="0" role="presentation"><tr>
<td><a href="https://facebook.com/example"><img src="{{assets_url}}images/social-icons/brand-circle/facebook.png" alt="Facebook" height="24" width="24" style="vertical-align: bottom"></a></td>
<td class="gap" width="10" style="width: 10px"></td>
<td><a href="https://instagram.com/example"><img src="{{assets_url}}images/social-icons/brand-circle/instagram.png" alt="Instagram" height="24" width="24" style="vertical-align: bottom"></a></td>
</tr></table>
</td></tr></table>
</td><!-- /social:follow -->
```

Icons: `facebook`, `instagram`, `linkedin`, `pinterest`, `reddit`, `threads`, `tiktok`, `tumblr`, `twitch`, `twitter`, `vimeo`, `whatsapp`, `wordpress`, `youtube`, `github`, `discord`, `bluesky`. Themes: `brand-circle`, `brand-boxed`, `brand-icons`, and `black-`, `dark-grey-`, `grey-`, `white-` with `circle`, `boxed` or `icons`. The icon images are at `{{assets_url}}images/social-icons/{theme}/{icon}.png`.

### html

Raw HTML, for anything the other blocks can't do. No scripts, forms, iframes or video.

```html
<!-- html:custom {"content":"<p>Raw HTML</p>"} --><td id="b-custom"><p>Raw HTML</p></td><!-- /html:custom -->
```

### footer

Nothing fills this in for you. Write the business details and links yourself, with merge tags:

```html
<!-- footer:foot {"style":{"lineHeight":1.4,"fontFamily":"system-ui, sans-serif","fontSize":13,"color":"#999999"},"linkStyle":{"color":"#488aff"},"alignment":"center"} --><td id="b-foot">
<div id="footer" class="footer">
<p style="line-height: 1.4;font-family: system-ui, sans-serif;font-size: 13px;color: #999999;text-align: center;margin: 0.5em 0">&copy; {business_name}</p>
<p style="line-height: 1.4;font-family: system-ui, sans-serif;font-size: 13px;color: #999999;text-align: center;margin: 0.5em 0">{business_address}</p>
<p style="line-height: 1.4;font-family: system-ui, sans-serif;font-size: 13px;color: #999999;text-align: center;margin: 0.5em 0">Don't want these emails? <a href="{unsubscribe_url}" style="color: #488aff">Unsubscribe</a>.</p>
</div>
</td><!-- /footer:foot -->
```

Leave out the unsubscribe line in a transactional email.

### global

Inserts a saved reusable block (an email whose message type is "global block"). Its content is loaded when the email is sent, so the `<td>` stays empty:

```html
<!-- global:header {"templateId":"123"} --><td id="b-header"></td><!-- /global:header -->
```

Only use it with the ID of a global block that exists on the site.

## Merge tags

Merge tags are resolved for each recipient when the email is sent, in the subject, preview text and content.

- `{first}`, `{last}`, `{full_name}`, `{email}`: the contact's details.
- `{code::fallback}` gives a fallback for an empty value: `Hi {first::there},`.
- `{code.argument}` passes an argument: `{meta.company_name}` for a custom field, `{date.F j, Y|+2 days}` for a date.
- `{business_name}`, `{business_address}`, `{business_phone}`, `{site_url}`: from the site's settings.
- `{owner_first_name}`, `{owner_email}`, `{owner_signature}`: the contact's owner, useful for personal sign-offs.
- `{unsubscribe_url}` and `{view_in_browser_url}` are bare URLs, so wrap them in a link: `<a href="{unsubscribe_url}">Unsubscribe</a>`.
- `{confirmation_link}` is a complete link already; `{confirmation_url}` is the bare URL for a button.

`list-replacement-codes` has every code on this site, including ones add-ons provide.

## Good practice

- Every marketing email needs an unsubscribe link and the business address. Use a footer block.
- One clear call to action, usually a button. Keep the email under about 600px wide and the copy short.
- Write a subject line under about 50 characters and a `preview_text` that adds to it rather than repeating it.
- Don't make up facts, prices, dates, testimonials or links. Where something is missing, leave a clear placeholder like `[LINK TO OFFER]`, or a placehold.co image (see the image block), and say where it is.
- Save as `status: "draft"` unless asked otherwise, then send a test with `send-test-email` and share the email's edit link.
- Never send it to contacts (`send-email-template`, `send-email-broadcast`) unless the person you're working for asks you to, with that audience.
