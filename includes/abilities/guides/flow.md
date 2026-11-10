# Designing a flow

A flow is an automation: triggers that pull contacts in, and actions, delays and logic that run for them. It is not a script that runs top to bottom. A contact sits at one position in the flow and can be pulled forward by any trigger below them. Most broken flows aren't badly written, they have steps nobody can reach. Design for that first.

## Find out what this site can build

Step types differ from site to site: add-ons and other plugins register their own. Never work from a remembered list, including the examples here.

1. `list-step-types` with `buildable_only: true` is the set you can build on this site.
2. `list-step-types` with `types: [...]` and `expand: ["settings_schema"]` gives the exact settings for the types you plan to use. Never guess a settings shape.
3. For logic types, read `branch_keys` and use exactly those as the keys of the step's `branches`. `["yes", "no"]` belongs to `if_else`; other logic types have their own.

Then resolve everything the flow refers to. `create-flow` creates none of it, and unknown names are errors:

- Emails: `list-email-templates` to reuse one, or `create-email-template` to write one (call `get-authoring-guide` with topic `email` first). A send-email step needs an email with `status: "ready"`. That's safe: the flow is created inactive, so nothing sends.
- Tags: `list-tags`. Create missing ones with `create-tag` when the flow needs them.
- Custom fields: `list-custom-fields`, when a condition or action uses one.

## Rules that hold for every flow you build

1. **Forward only.** A contact at a step can only move to steps after it.
2. **Triggers are magnets, not filters.** A trigger anywhere in the flow is both an entry point and a jump point: when a contact matches it, they jump straight to it and skip everything in between. Triggers aren't retroactive, so a contact who already has a tag won't enter a "tag applied" trigger added later.
3. **Branches are committal.** Steps in different branches of the same logic step can't reach each other.
4. **Logic is checked once,** when the contact arrives at it. Later changes don't move them to the other branch.
5. **Branches merge back automatically** at the step after the logic step. Don't copy a shared step into every branch.

## How the create-flow input maps to that

- `steps` is the main trunk, reachable from anywhere above.
- `branches: { "<branch_key>": [...] }` on a logic step is a side track that only contacts who took that branch can reach.
- An empty branch array means "skip". Don't invent a filler step.
- Triggers next to each other in the same list mean OR: any of them.
- Give every step a `title` that says what it does. Without one, the flow shows a column of identical step type names.
- Give a step a local `id` only when another step refers to it, and define it before the step that refers to it.

## Structure

- **Keep the trunk thick and branches thin.** Only steps that really differ go in a branch. Shared follow-ups, goal triggers and closing tags belong on the trunk.
- **Never put a trigger inside a branch** unless only that branch's contacts should reach it. A link-click goal in one branch is invisible to the other, and nothing warns you.
- **Branch as late as possible.** Every step before the logic step is a chance for data to change.
- **Wide and shallow beats deep.** For independent conditions, use logic steps one after another on the trunk rather than nesting them.
- **OR is adjacent triggers, AND is a trigger plus a logic step.** Stacked triggers never mean "both".
- **Retries are re-entry, not going back.** "Try again in 30 days" is a trigger at the top, a second flow, or `add-to-flow`. Flows don't loop back.
- **Place delays deliberately.** A delay parks the contact there. Triggers below it can still pull them forward during the wait; anything above it can't. A goal trigger above a long delay does nothing. Use the delay's settings to send at a sensible hour in the contact's time zone.

## When a step you need can't be built

Webhooks, editing custom fields, page-visit triggers, SMS, date-based timers and A/B splits are often registered but not buildable. That's normal.

- **Never quietly substitute.** Applying a tag instead of setting a field, or a fixed delay instead of a date timer, gives a flow that looks right and does something else.
- Check `list-step-types` without `buildable_only` to see whether the type exists on the site. If it does, say it can be added in the flow editor but not built from here.
- Offer a choice: build the rest and say exactly where the missing step goes, or stop so the person can build that part first.

## Example

Using types confirmed buildable on the site, with an email ID from `list-email-templates`:

```json
{
  "title": "Guide download follow-up",
  "steps": [
    { "type": "tag_applied", "title": "Requested the guide",
      "settings": { "tags": ["guide-requested"] } },
    { "id": "deliver", "type": "send_email", "title": "Send the guide",
      "settings": { "email_id": 412 } },
    { "type": "delay_timer", "title": "Wait 3 days",
      "settings": { "delay_amount": 3, "delay_type": "days", "run_when": "later", "run_time": "09:00:00", "send_in_timezone": true } },
    { "type": "link_click", "title": "Goal: booked a call",
      "settings": { "redirect_to": "https://example.com/book" } },
    { "type": "if_else", "title": "Already a customer?",
      "settings": { "include_condition": { "tags_include": ["customer"] } },
      "branches": {
        "yes": [ { "type": "send_email", "title": "Upsell", "settings": { "email_id": 419 } } ],
        "no":  [ { "type": "send_email", "title": "Nurture", "settings": { "email_id": 421, "reply_in_thread": "deliver" } } ]
      } },
    { "type": "apply_tag", "title": "Finished the follow-up",
      "settings": { "tags": ["guide-follow-up-done"] } }
  ]
}
```

- The goal trigger is **below the delay**, so contacts waiting there can still jump to it.
- The goal trigger is **on the trunk**, so it applies to everyone, not one branch.
- The closing tag comes **after the logic step**, written once and reached from both branches.

## Before you call create-flow

- Buildable step types confirmed on this site, and `settings_schema` read for each one used.
- Branch keys taken from `branch_keys`.
- Nothing substituted for a step that can't be built.
- Goal and entry triggers on the trunk, and below any delay their contacts wait at.
- No step copied into several branches that could come after the logic step instead.
- No condition that depends on something set below it.
- Every email exists and is ready, every tag exists.
- Every step has a title.

Afterwards, read back the `steps` in the response and check the stored structure is the one you meant. If you can, run `simulate-flow` with a test contact from the first step to see the path a contact takes.

## Never switch it on yourself

`create-flow` always makes an **inactive** flow, on purpose. Activating a flow, or adding contacts with `add-to-flow`, sends real messages to real people and can't be undone.

- Build it, share the flow's edit link, and explain what it will do.
- Only call `activate-flow` when the person you're working for explicitly asks.
- Never add a segment or a broad tag to a flow unless that person named that audience themselves.
