# Building an email that stays editable

Emails on this site are edited in a drag-and-drop block editor. To make an email the site owner can open and keep editing there, give `create-email-template` (or `update-email-template`) the body as `blocks`: a JSON array of block specs. The markup, block CSS and plain text are written for you, the same way the editor writes them. Plain HTML (`content` with `editor: "html"`) also sends fine, but it opens in a code editor instead, so use blocks unless you're asked otherwise.

## Before you write

1. Look at one or two existing block emails so the new one looks like it belongs to this site. Find them with `list-email-templates` (without `expand`) and read one with `get-email-template` and `view: "specs"`. Reuse their structure, fonts and colors and swap in new copy.
2. Call `list-replacement-codes` for the merge tags this site supports. Use them for greetings, sign-offs and compliance links instead of static text.
3. Call `list-sender-profiles` if the email should come from someone other than the default sender.

{{email_design}}

## Block specs

A spec is a block's `type` and only the settings that differ from the defaults. Settings you leave out get this site's block defaults. Give an `id` (letters, digits and hyphens, like `intro` or `cta`) to blocks you may want to change later; otherwise one is made for you.

```json
[
  { "type": "image", "src": "https://example.com/logo.png", "alt": "Acme", "width": 140 },
  { "type": "heading", "id": "title", "tag": "h1", "content": "The October roundup", "fontStyle": { "fontSize": 36 } },
  { "type": "text", "id": "intro", "content": "<p>Hi {first::there},</p><p>Here's what's new this month.</p>" },
  { "type": "button", "id": "cta", "text": "Read the blog", "link": "https://example.com/blog" },
  { "type": "columns", "columns": [
    [ { "type": "image", "src": "https://example.com/a.png", "alt": "Feature A", "width": 280 },
      { "type": "text", "content": "<p><strong>Feature A</strong> does this.</p>" } ],
    [ { "type": "image", "src": "https://example.com/b.png", "alt": "Feature B", "width": 280 },
      { "type": "text", "content": "<p><strong>Feature B</strong> does that.</p>" } ]
  ] },
  { "type": "social" },
  { "type": "footer", "alignment": "center" }
]
```

Colors are hex (`#1e73be`), or `palette:N` for this site's palette colors. Specs with a mistake (an unknown type, a taken id, a bad layout) are refused with what's wrong, and nothing is saved.

### Every block

- `advancedStyle`: the block's box. `padding` (a number for every side, `"10px 20px"`, or `{top, right, bottom, left}`; 5 by default), `backgroundColor`, `borderStyle` (`solid`, `dashed`...), `borderColor`, `borderWidth`, `borderRadius`.
- `hide_on_mobile`, `hide_on_desktop`, `hide_in_plain`, `hide_in_browser`: true to hide it there.
- `css`: custom CSS for the block, where `selector` stands for the block, like `selector p { letter-spacing: 1px; }`.

Font settings (`p`, `fontStyle`, `style`...) are objects of `fontSize` (pixels), `fontFamily`, `fontWeight`, `lineHeight`, `fontStyle` (`normal`, `italic`), `textTransform` and `color`. `fontFamily` is one of the editor's fonts (Arial, Arial Black, Arial Narrow, Book Antiqua, Calibri, Century Gothic, Comic Sans MS, Copperplate, Courier New, Futura, Garamond, Georgia, Helvetica, Impact, Lucida Grande, Lucida Sans, Palatino, system-ui, Tahoma, Times New Roman, Trebuchet MS, Verdana); others become Arial. `use` takes the name of one of the site's global fonts instead.

### Types

| type | settings |
|---|---|
| `text` | `content`: HTML with `p`, `h1`-`h3`, `ul`/`ol`/`li`, `a`, `strong`/`b`, `em`, `br`. Font settings for its tags: `p` (also lists), `a` (links, usually just `color`), `h1`, `h2`, `h3`. The fonts are added to each tag as inline styles, so don't write those yourself; a tag's own `style`, like `text-align`, is kept. |
| `heading` | `content` (inline HTML), `tag` (`h1`-`h6`, default `h2`), `alignment` (`left`, `center`, `right`), `fontStyle`. The size doesn't follow the tag: set `fontStyle.fontSize` for a big `h1`. |
| `button` | `text`, `link`, `align` (`left`, `center`, `right`, or `justify` for full width), `size` (`sm`, `md`, `lg`), `backgroundColor`, `style` (font settings, `color` is the text color), `borderStyle` (`{borderStyle, borderColor, borderWidth, borderRadius}`). |
| `image` | `src`, `alt`, `width` (pixels), `link`, `align`, `borderStyle` (as for a button; `{"borderRadius": 8}` for rounded corners). |
| `spacer` | `height` (pixels). |
| `divider` | `height` (line thickness), `width` (percent), `color`, `lineStyle` (`solid`, `dashed`, `dotted`, `double`, `ridge`, `groove`). |
| `columns` | `columns`: an array of columns, each an array of specs. `layout`: `one_column`, `two_columns` (50/50), `three_columns`, `four_columns`, `two_columns_left` (66/33), `two_columns_right` (33/66), `three_columns_center` (25/50/25), `three_columns_left` (50/25/25), `three_columns_right` (25/25/50), or `custom_columns` with `customColumns` widths in percent, like `[30, 70]`. `gap` (pixels between columns), `verticalAlign` (`top`, `middle`, `bottom`), `responsive` (stack on phones, default true), `columnStyle` (`{backgroundColor, padding, borderStyle...}` for every column that has blocks). |
| `menu` | `links`: `[["Blog", "https://..."], ["Shop", "https://..."]]`, `align`, `gap`, `separator` (text between links, like `"|"` or `"&bull;"`), `fontStyle`. |
| `social` | `use`: `"global"` (default, the site's own social accounts) or `"custom"` with `socials`: `[["facebook", "https://..."], ...]`. `theme`, `size` (pixels), `gap`, `align`. Icons: facebook, instagram, linkedin, pinterest, reddit, threads, tiktok, tumblr, twitch, twitter, vimeo, whatsapp, wordpress, youtube, github, truthsocial, odysee, discord, rumble, bluesky. Themes: `brand-circle`, `brand-boxed`, `brand-icons`, and `black-`, `dark-grey-`, `grey-`, `white-` with `circle`, `boxed` or `icons`. |
| `html` | `content`: raw HTML for anything the other blocks can't do. Scripts, forms, iframes and video are taken out. |
| `footer` | The business name, address, phone, privacy policy and terms links and the unsubscribe link, from the site's settings. `alignment`, `style` (font settings), `linkStyle` (`{color}`), and `tel`, `privacyPolicy`, `terms` (false to leave that link out). There's no unsubscribe line in a transactional email. |
| `global` | `templateId`: the ID of a global block (a saved reusable block) that exists on the site. It's put in when the email is sent. |

### Images

Where images come from, in this order:

1. Images you were given, or that the site's existing emails already use.
2. A relevant image from the site's media library. Search it with your connection's own media library tools if it has them, otherwise with `search-media`.
3. An image that's online and that you're allowed to use: add it to the media library with `upload-media`, giving its `url` so the site downloads it, and use the URL it returns. Never write base64 yourself, it won't be a valid image.
4. Otherwise a placeholder from placehold.co, the size of the image it stands in for, labelled with what belongs there: `https://placehold.co/600x300/png?text=Product+photo`. Keep `/png` in the URL, the default is SVG and many email clients don't show SVG images. Write spaces in the label as `+`. Full width is 600 wide, half of a two column row about 280, a third about 180.

Never make up an image URL or link to an image on someone else's site. When you use placeholders, say which images need replacing.

## Changing an email

Don't read or rewrite the whole email to change part of it. Use `edit-email`, which changes blocks by id:

1. `get-email-template` returns an outline: each block's `id`, `type`, the `parent` and `column` of blocks in columns, and a `summary` of its text, image or link. It also returns the email's `version`. With `view: "specs"` it returns the blocks as specs instead, and with `blocks` (ids) only those. `find` returns where some text or HTML is and which block it's in.
2. Call `edit-email` with `expected_version` and a list of `operations`, applied in order, all or nothing:

```json
{
  "email_id": 12,
  "expected_version": "…",
  "operations": [
    { "op": "replace_text", "find": "Sale ends Friday", "replace": "Sale ends Sunday" },
    { "op": "replace_text", "block": "cta", "find": "https://example.com/old", "replace": "https://example.com/new" },
    { "op": "replace", "block": "cta", "blocks": [ { "type": "button", "id": "cta", "text": "Shop now", "link": "https://example.com/shop", "size": "lg" } ] },
    { "op": "add", "at": { "after": "intro" }, "blocks": [ { "type": "spacer", "height": 20 } ] },
    { "op": "move", "block": "footer", "at": { "position": "end" } },
    { "op": "delete", "block": "old-banner" }
  ]
}
```

- `replace_text` is the cheapest change. It must match exactly once (give `block` to narrow it down, or `all: true`), and it matches the HTML as written, so text split by tags like `<strong>` won't match.
- To change a block's settings, get its spec (`view: "specs"`, `blocks: [id]`), change it, and `replace` the block with it, keeping its id.
- `add` and `replace` take new blocks as `blocks` (specs). `add` and `move` take `at`: `{"after": id}`, `{"before": id}`, `{"in": id, "column": 0}` for a column of a columns block, or `{"position": "start"}` / `{"position": "end"}`.
- `set_content` replaces the HTML inside a block's `<td>` as it is, without styling it. `set_props` changes settings in a block's comment that are read when the email is sent, like a global block's `templateId`.
- Use `dry_run: true` to check a change without saving it.

The result lists the blocks that changed and the new `version` for the next call. Use `update-email-template` for the subject, preview text, sender, status and other settings.

### Emails that aren't built with blocks

An email from the HTML editor has no blocks. `get-email-template` returns its visible text as `text`, a line for each paragraph, heading or cell, with links as `[text](href)` and images as `![alt](file)`, instead of the HTML. Change it with `edit-email` too:

```json
{
  "email_id": 37,
  "expected_version": "…",
  "operations": [
    { "op": "replace_text", "find": "ends Sunday", "replace": "ends Monday" },
    { "op": "replace_html", "find": "href=\"https://example.com/fall\"", "replace": "href=\"https://example.com/fall-sale\"" }
  ]
}
```

- `replace_text` only changes visible text, never tags, attributes or the CSS, so it's the safe way to change wording.
- `replace_html` replaces an exact piece of the HTML: a link, an image, a style, or a whole `<tr>…</tr>`. Copy it from `get-email-template` with `find`, which returns each place some text or HTML is with the HTML around it, exactly as saved. A piece that opens a tag has to close it.
- Both must match exactly once, unless `all: true`.

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

- Every marketing email needs an unsubscribe link and the business address. End it with a footer block.
- One clear call to action, usually a button. Keep the copy short.
- Write a subject line under about 50 characters and a `preview_text` that adds to it rather than repeating it.
- Don't make up facts, prices, dates, testimonials or links. Where something is missing, leave a clear placeholder like `[LINK TO OFFER]`, or a placehold.co image, and say where it is.
- Save as `status: "draft"` unless asked otherwise, then send a test with `send-test-email` and share the email's edit link. Both kinds of test work on a draft: a design test (the default) shows how it looks; `type: "simulated"` sends it the way a broadcast or flow would, so links, tracking, personalization and conditional blocks can be checked too. Send tests only to the person you're working for.
- Never send it to contacts (`send-email-template`, `send-email-broadcast`) unless the person you're working for asks you to, with that audience.

## The markup, for reference

You don't need to write it, but this is what the content looks like: a `<table>` with a `<tr>` per block, each block between comments that carry its type, id and the settings that aren't in its HTML, around a `<td id="b-{id}">` with the block's HTML. `get-email-template`'s `blocks` input returns a block's markup, and `edit-email` also takes markup as `html`, in this format:

```html
<table cellpadding="0" cellspacing="0" width="100%" role="presentation">
<tr><!--button:cta {"size":"md"}--><td id="b-cta" class="b-button" ...>...</td><!--/button:cta--></tr>
</table>
```
