# Bringing add-ons up to date with Groundhogg 4.9

Groundhogg 4.9 rebuilt the flow editor. The canvas, the settings panels and the add steps panel are drawn in
JavaScript, and edits are saved as operations instead of posting the whole form. Most add-on step types keep
working unchanged, but some patterns don't. This guide is for a session working in one add-on repo: what to
check, how to fix it, and how to verify it.

Besides fixing what's broken, **every step type is ported to the current APIs**: its settings to
`get_settings_schema()` (checklist 1) and its editor JS to `Funnel.registerStepType()` (checklist 4), unless
there's a reason not to, which the plan should say.

**Read first:** core's `docs/flow-architecture.md`, sections 4-5 (step types and settings), 7 (editing model),
9 (the editor frontend) and 10 (the add-on checklist). This guide only covers what's different in 4.9.

**Ground rules** (from the Groundhogg memory notes):

- PHP must still parse on **PHP 7.4**: no `match`, `?->`, named arguments, union types, constructor promotion,
  and so on. WordPress polyfills the `str_*` functions, so those are fine.
- Don't build `.min.js` or `.css` by hand; the PhpStorm watchers regenerate them when the source is saved.
- Use the `wordpress-groundhogg-dev` abilities MCP for test data on the dev site, not WP-CLI. The dev site loads
  unminified scripts with a `?ver` that doesn't change, so reload a script with `fetch(src, { cache: 'reload' })`
  before testing a change.
- Don't commit or merge without asking Adrian.

---

## What changed in 4.9 that add-ons can feel

1. **Settings save through an `update` operation, not the form.** When a field in a step's panel changes, the
   editor waits 400ms, then sends the panel's `steps[ID][…]` fields to the server, which calls
   `Step::save( $form )`. The element's `save( $step )` still runs, and `get_posted_data()`,
   `setting_name_prefix()` and `setting_id_prefix()` work as before. **`$_POST['steps']` is no longer
   populated**, and only the fields inside that step's panel (`#settings-<ID>`) are sent.
2. **Panels are drawn from HTML "islands".** `settings()`, `before_settings()`, `after_settings()` and
   `before_step_notes()` output is captured on the server and inserted by JS with `innerHTML`. **Inline
   `<script>` tags in that output never run.**
3. **`Funnel.save()`** with no arguments (or `true`) saves the open step's panel through an `update` operation
   and resolves once it's saved. Only `Funnel.save( { moreData } )` still posts the old form (to the legacy
   `process_edit()`).
4. **Only one person can edit a flow at a time.** There's no take-over, and saves by anyone else are refused.
5. **Deleted steps are soft until publishing, and then archived** if contacts went through them. Step queries
   must exclude `step_status = archived` (and usually `deleted`).
6. **Steps other steps point at can't be deleted**, if their type declares which settings hold step IDs
   (`get_step_reference_settings()`).
7. **New JS API, `Funnel.registerStepType( type, { settings, title, validate, branches, defaults, onDuplicate } )`.**
   It's optional; `registerStepCallbacks()` with `onActive` keeps working.
8. **Runtime fixes that can change behaviour:**
   - trigger frequency limits ("At most once per contact", …) are now enforced;
   - a benchmark's data and args are reset before each time its hook fires, so a trigger firing for several
     contacts in one request no longer carries the first contact's state.

---

## Checklist for each add-on

Work through these for every step type the add-on registers (`register_funnel_steps()` /
`groundhogg/steps/init`).

### 1. Settings reach the server, and are described by a schema

- **Check:** every setting is an input **inside the step's panel**, named with `$this->setting_name_prefix( 'x' )`
  (`steps[ID][x]`), or written from JS with `Funnel.updateStepMeta( { x }, ID )` / `update()`.
- **Check:** `save( $step )` and any `sanitize` callbacks read settings with `$this->get_posted_data( 'x' )`, never
  from `$_POST`, `$_REQUEST`, `get_post_var()` or `get_request_var()`, and never read other steps' fields or
  `funnel_title`.
- **Port:** replace a custom `save( $step )` with `get_settings_schema()`:
  - every setting gets a `sanitize` callback and a `default`, plus `initial` where a new step needs a value, and
    `if_undefined` for checkboxes that aren't posted when off (architecture doc, section 5);
  - **keep the stored meta keys and the sanitized values exactly as `save()` stored them**, so existing flows,
    import/export, and the runtime code that reads them keep working. Don't rename keys or change their types;
  - anything in `save()` that isn't sanitizing (creating related objects, side effects) moves to
    `after_save( $step )`, which must call `parent::after_save( $step )` (it fires
    `groundhogg/steps/save/after` and saves the generated title);
  - if a step type can't be expressed as a schema, keep `save()`, use `get_posted_data()`, and say why in the plan.

### 2. No inline scripts in settings output

- **Check:** grep the step classes for `<script`, `wp_add_inline_script` or `?><script` in `settings()`,
  `before_settings()`, `after_settings()` and `before_step_notes()`.
- **Why:** island HTML is inserted with `innerHTML`, so the script never runs. Buttons it wires up do nothing.
- **Fix:** move the behaviour into the add-on's editor script (registered on
  `groundhogg/scripts/after_register_admin_scripts`, enqueued on `groundhogg/admin/funnels/editor_scripts`,
  depending on `groundhogg-admin-funnel-editor`). Use `Funnel.registerStepCallbacks( type, { onActive( step ) { … } } )`,
  or a delegated listener such as `$(document).on( 'click', '#step_<ID>_upgrade', … )`. The element IDs from
  `setting_id_prefix()` are unchanged.

### 3. `Funnel.save()` callers

- **Check:** JS that sets a hidden input and calls `Funnel.save()` (upgrade buttons, "declare winner", branch
  deletes, pollers).
- **It works if** the input is inside `#settings-<ID>` and named `steps[ID][…]`: it's sent with the panel.
- **It doesn't work if** the input is outside the panel, or the code depends on the whole form being posted.
  Prefer `Funnel.updateStepMeta( { key: value }, ID )` and let it save itself; use `Funnel.save( { moreData } )`
  only as a last resort.

### 4. Editor JS, ported to `Funnel.registerStepType()`

- **Port:** settings drawn by `registerStepCallbacks( type, { onActive } )`, by `step-active` listeners, or by
  pickers the JS mounts over PHP placeholders move to:

  ```js
  Funnel.registerStepType( 'my_type', {
    settings: ( step, update ) => /* MakeEl UI, update( { key: value } ) saves it */,
    title   : step => /* the same text generate_step_title() makes, or undefined */,
    defaults: { /* what a new step starts with, like the schema's initial values */ },
    onDuplicate: step => /* extra choices when duplicating, optional */,
  } )
  ```

  - `settings()` is drawn into the panel's `.step-type-settings`, where PHP `settings()` prints, and only for the
    step being edited. Leaving the step puts the server's HTML back, so `settings()` in PHP should still output
    the inputs (or placeholders) it does now.
  - `title()` shows on the canvas while a change saves; once the server replies, its title wins. **It must match
    `generate_step_title()`**. Return undefined when it needs data that isn't loaded, like a name for an ID.
  - Types that branch (`Branch_Logic`) can add `branches( step )`, and `validate( step )` shows warnings early.
  - See core's `assets/js/admin/funnels/funnel-steps.js` (`delay_timer`, `apply_tag`, `if_else`, `add_to_flow`)
    for examples, and `step-titles.js` for titles.
- **Can stay on `onActive`:** types whose UI needs the old panel lifecycle, like a picker printed by
  `after_settings()` (core's `send_email`, SMS's `send_sms`), TinyMCE editors, or the form builder. Say which
  and why in the plan.
- Don't reach into the canvas DOM (`#step-sortable`, `.sortable-item`) or build canvas markup; the canvas is
  redrawn from data on every change. Use the PHP hooks `groundhogg/steps/sortable/classes|labels|inside`.
- Anything that referenced `Groundhogg.funnelEditor` (the old v2 editor API) is dead; core no longer defines it.
- Anything the JS puts into the page as HTML from settings or user input must be escaped (`escHTML`) or passed
  through `Groundhogg.element.sanitizeHTML()`. MakeEl parses string children as HTML.

### 5. Canvas

- A step type that overrides `sortable_item()` gets its server markup inserted as-is (`layout: html`), with no
  add/move/duplicate buttons drawn by JS. If the override only adds labels or classes, delete it and use the
  hooks instead.

### 6. Step references

- If a setting stores step IDs (a jump target, a step in another flow, a list of steps), declare it:

  ```php
  protected function get_step_reference_settings(): array {
      return [ 'next' ];
  }
  ```

  The editor and `edit-flow` then refuse to delete the referenced steps.

### 7. Queries on the steps table

- **Check:** `get_db( 'steps' )->query()` / `count()` / raw SQL on the steps table.
- **Fix:** add `'step_status' => 'active'` for runtime lookups, or at least exclude `archived` and `deleted`.
  Alternatively, check `$step->is_active()` on each result.
- Outside the editor (REST, cron, CLI, abilities), read or write a flow's **draft** inside
  `$funnel->while_editing( fn )`, or `get_steps()` on an active flow will ignore new steps and staged changes.
- `update_meta()` on a step of an **active** flow is staged until published; use `$step->update_settings( [ … ] )`
  when changing settings from code.

### 8. Benchmarks

- Don't rely on state kept on the element instance between hook fires; `setup()` starts fresh each time.
- Test the trigger with "Can be triggered: At most once per contact". It's enforced now.

### 9. Abilities (optional, recommended)

- To let agents build and edit the add-on's steps with `create-flow` / `edit-flow`, describe them on
  `groundhogg/steps/init` **priority 20**:

  ```php
  Step_Type_Schema::extend( 'my_type', $settings_json_schema, $branch_keys, $resolver, $exporter );
  ```

  Every sub-schema needs a `type`, and use `definitions`, not `$defs` (architecture doc, section 11). Types
  that aren't described can still be added in the editor.

### 10. Tests

- Use the `groundhogg-addon-phpunit` skill to set up or run the add-on's PHPUnit suite against core.
- Worth adding for each step type:
  - it can be added through `Flow_Operations` in editor mode;
  - **before porting**, a test that saves representative settings through `Step::save( [ … ] )` and checks the
    stored meta; after porting to the schema, the same test must still pass unchanged;
  - `generate_step_title()` for a few settings, so the JS `title()` can be checked against it;
  - a contact runs through it (`process_events( [ $contact ] )`), like core's
    `tests/phpunit/unit-tests/includes/classes/class-flow-execution-tests.php`.

### 11. Verify in the editor

On the dev site, for each step type:

1. Add it to a test flow, open its panel, change each setting, wait a second, reload, and check that the
   settings stuck.
2. Check the canvas title updates after the save.
3. Try duplicate, copy and paste into another flow, and undo/redo.
4. Activate the flow and run a test contact through it.
5. Check the browser console for errors.

---

## Scan results (2026-09-30)

An automated scan of the repos in `PhpstormProjects` that register step types. Treat it as a starting point;
the scan matches patterns and doesn't prove anything works. Every add-on also gets the ports in checklist 1 and
4; the Action column is only what's specific to it.

| Add-on | Found | Action |
|---|---|---|
| **groundhogg-edd** | `download-purchased.php` and `payment-refunded.php` echo an inline `<script>` in `before_step_notes()` for the legacy "Upgrade" button, which calls `Funnel.save()` | **Broken:** the script never runs. Move the click handler to the editor script (checklist 2). The hidden `should_upgrade` input is inside the panel, so `Funnel.save()` will send it. |
| **groundhogg-wooc** | Same inline script in `legacy-payment-gateway.php` and `legacy-product-purchased.php`; `assets/js/admin/register-steps.js` uses `Groundhogg.funnelEditor.functions` | **Broken:** fix like EDD. `register-steps.js` isn't enqueued and its API is gone, so delete it. |
| **groundhogg-pro** | `onActive` / `updateStepMeta` everywhere; `Funnel.save()` for split path and weighted distribution branch deletes, A/B test start/winner, and the webhook listener's 10s poll | Expected to work (hidden inputs are inside the panel), but test each one in the browser. Known issues: the delete-branch prompt says the branch's steps will be deleted, but they move to the "unused branch" column; there are no JS titles for `split_path`, `weighted_distribution`, `split_test`. Core's `branches()` previews already cover them. |
| **groundhogg-sms** | `registerStepCallbacks( 'send_sms' )`; `sms-notification.php` has a legacy `save()` | Expected to work; verify both steps save. |
| **groundhogg-pipeline** | `onMount` / `updateStepMeta` components and a `step-active` listener; schemas on its steps | Expected to work; verify. |
| **groundhogg-twilio** | `registerStepCallbacks` for its two WhatsApp steps; `send-whatsapp-template.php` has a legacy `save()`; the opt-in lookup queries steps but checks `is_active()` | Expected to work; verify. |
| **groundhogg-birthday** | Cron queries steps with `step_status => active`; legacy `save()` | Fine; verify the step saves. |
| **groundhogg-zapier** | Legacy `save()` with `get_posted_data()`; its `get_request_var()` is in a separate test ajax action | Expected to work; verify. |
| **affwp, contracts, facebook-conversions-api, formidable, givewp, gravity, helpscout, rsp, thrivecart, wpforms** | Legacy `save( $step )` using `get_posted_data()`; no `$_POST` reads found | Expected to work as is; port the `save()` methods to `get_settings_schema()`. |
| **cf7, fluent-forms, forminator, ninja, weforms, wp-simple-pay, appointments** | No risky patterns found | Verify each step type saves. |
| **bookings, learndash, lifterlms, memberpress, presto-player, sheets, traffic-filter** | They register steps, but the scan didn't match their step classes | Audit by hand with the checklist. |

---

## Reporting back

When an add-on is done, tell Adrian:

- what was changed and why (by checklist item);
- what was verified in the editor and with PHPUnit;
- anything that looks like it needs a change in **core** instead (for example, if many add-ons need island
  scripts to run, core could run them).
