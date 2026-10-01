# Flow architecture (Groundhogg core + Pro)

A map of how flows work end to end: the data model, the step tree, step types and their settings, the
runtime, editing and publishing, the flow editor's frontend, add-on extension points, and the flow
abilities (MCP/Abilities API). Written for developers and AI sessions picking this code up cold.

- Written against core `master` at `e2cc98a8a` and groundhogg-pro `master` at `7fff269` (2026-09-26).
  Line numbers drift; this doc names classes and methods instead, grep for them.
- "Flow" is the product name, the code says **funnel** everywhere (`Funnel`, `gh_funnels`, `funnel_id`).
  "Trigger" is a **benchmark**. A "sequence" is steps in a **branch**.
- Section 17 lists known bugs and gotchas. Read it before trusting any behaviour described here.

## Contents

1. [Where things live](#1-where-things-live)
2. [Data model](#2-data-model)
3. [The step tree: branches, order and levels](#3-the-step-tree-branches-order-and-levels)
4. [Step types](#4-step-types)
5. [Step settings and sanitization](#5-step-settings-and-sanitization)
6. [Runtime: how contacts move through a flow](#6-runtime-how-contacts-move-through-a-flow)
7. [Editing model: drafts, publishing, deletes, undo](#7-editing-model-drafts-publishing-deletes-undo)
8. [The editor save protocol (server)](#8-the-editor-save-protocol-server)
9. [The flow editor frontend](#9-the-flow-editor-frontend)
10. [Adding a step type (add-on checklist)](#10-adding-a-step-type-add-on-checklist)
11. [Flow abilities (Abilities API / MCP)](#11-flow-abilities-abilities-api--mcp)
12. [Import, export, templates, duplicating](#12-import-export-templates-duplicating)
13. [REST API and the admin flows page](#13-rest-api-and-the-admin-flows-page)
14. [Reporting, contact filters, replacements](#14-reporting-contact-filters-replacements)
15. [Simulator](#15-simulator)
16. [Hooks index](#16-hooks-index)
17. [Known bugs and gotchas](#17-known-bugs-and-gotchas)
18. [Testing](#18-testing)

---

## 1. Where things live

| Area | Files |
|---|---|
| Flow model | `includes/classes/funnel.php` (`Funnel`) |
| Step model | `includes/classes/step.php` (`Step`), `includes/classes/temp-step.php` |
| Step type base classes | `includes/steps/funnel-step.php` (`Funnel_Step`), `actions/action.php`, `benchmarks/benchmark.php`, `logic/logic.php`, `logic/branch-logic.php` |
| Step type registry | `includes/steps/manager.php` (`Groundhogg\Steps\Manager`, reached via `Plugin::instance()->step_manager`) |
| Core step types | `includes/steps/actions/*`, `benchmarks/*`, `logic/if-else.php` |
| Premium stubs (Pro not active) | `includes/steps/premium/{actions,benchmarks,logic}/*`, `trait-premium-step.php` |
| Placeholders for unregistered types | `includes/steps/*/polyfill-*.php`, `trait-polyfill.php`, `error.php` |
| Events and queue | `includes/classes/event.php`, `event-queue-item.php`, `includes/queue/event-queue.php`, `event-store-v2.php`, `process-contact-events.php`, `db/event-queue.php`, `db/events.php` |
| Editor page (PHP) | `admin/funnels/funnels-page.php` (`Funnels_Page`), `funnel-editor.php` (template), `add-funnel.php`, `funnels-table.php`, `simulator.php` |
| Editor (JS) | `assets/js/admin/funnels/funnel-editor.js`, `funnel-steps.js`, `simulator.js`, `logic-lines.js`; CSS `assets/css/admin/funnel-editor.css` |
| Script handles | `includes/scripts.php` (`register_admin_scripts()`) |
| REST | `api/v4/funnels-api.php`, `api/v4/steps-api.php`, `api/v4/base-object-api.php` |
| Flow abilities | `includes/abilities/funnels/*` (create/get/edit/publish/discard/activate/deactivate/list/add-to/simulate), `includes/abilities/schemas/step-type-schema.php`, `segment-schema.php` |
| Pro step types | groundhogg-pro `includes/steps/{actions,benchmarks,logic}/*`, registered in `includes/plugin.php` `register_funnel_steps()` |
| Pro editor JS | groundhogg-pro `assets/js/admin/funnel-steps.js` |
| Pro abilities opt-in | groundhogg-pro `includes/abilities/step-types.php` (`GroundhoggPro\Abilities\Step_Types`) |
| Add-on scaffolding | `includes/extension.php` (`Groundhogg\Extension`), repo `groundhogg-extension-template` |
| Templates library | `includes/library.php` (remote, library.groundhogg.io) |
| Tests | `tests/phpunit/unit-tests/includes/classes/class-step-*`, `class-funnel-editing-tests.php`, `class-step-references-tests.php`, `includes/abilities/*`; Pro `tests/phpunit/unit-tests/test-flow-logic-abilities.php` |

---

## 2. Data model

### Tables

- **`gh_funnels`**: `ID`, `title`, `status` (`active` | `inactive` | `archived`), `author`, `date_created`,
  `last_updated`. Meta in **`gh_funnelmeta`**: `description`, `replacements` (the `{this_flow.key}`
  merge tag values), and others.
- **`gh_steps`**, one row per step. The columns and their sanitizers (`Step::sanitize_columns()`) are:

  | column | meaning |
  |---|---|
  | `funnel_id` | owner flow |
  | `step_title` | admin title, kses allowing `b, strong, u, i, em, code` (generated titles contain `<b>`) |
  | `step_type` | registered type key, e.g. `send_email` |
  | `step_group` | `action` \| `benchmark` \| `logic` |
  | `step_status` | `active` \| `inactive` \| `deleted` \| `archived` (see 7) |
  | `step_order` | global depth-first order in the flow (see 3) |
  | `step_level` | depth, strictly increasing along any path (see 3) |
  | `branch` | which branch the step is in: `main`, `"<benchmarkId>"`, or `"<logicId>-<key>"` |
  | `branch_path` | pipe-joined chain of branches from this one up to `main` |
  | `ancestors` | pipe-joined parent step IDs, nearest first |
  | `is_entry`, `is_conversion`, `can_passthru` | benchmark flags |
  | `is_locked` | locked in the editor (written directly, bypasses staging) |
  | `step_slug` | `"<ID>-<sanitized title>"`, used by link/form tracking |
  | `changes` | serialized staged edits for active steps (see 7) |
  | `date_committed`, `date_activated` | bookkeeping |

  Meta in **`gh_stepmeta`** holds the step's **settings** (one meta key per setting), plus `step_notes`,
  `_trigger_frequency*`, and import bookkeeping (`imported_step_id`).
- **`gh_event_queue`**: pending work (`WAITING`, `PAUSED`, claimed or in progress).
  **`gh_events`**: history (`COMPLETE`, `SKIPPED`, `FAILED`, `CANCELLED`). Rows have `time`,
  `time_scheduled`, `micro_time`, `funnel_id`, `step_id`, `contact_id`, `event_type`, `status`, `priority`,
  `claim`, `args` (serialized), `error_code`, `error_message`, `email_id`. For flows,
  `event_type = Event::FUNNEL (1)`.
- **Campaigns** attach to flows through `gh_object_relationships` (the flow is the primary side).

### Objects

- **`Funnel`** (`Base_Object_With_Meta`).
  - `get_as_array()` = the row plus `steps` (`get_steps()`), `campaigns`, and `links{export, report}`.
  - `export()` = the same, with `steps = get_real_steps()`.
- **`Step`** (`Base_Object_With_Meta`, implements `Event_Process`).
  - The constructor accepts an ID, an encrypted ID, or a `"<ID>-slug"` string.
  - `get_as_array()` = `{ID, data, meta, export, is_starting, is_entry}`, with staged changes merged when
    the flow is being edited.
  - `get_step_element()` returns the singleton `Funnel_Step` for its type. For unregistered types it
    returns a per-step `Polyfill_{Action|Benchmark|Logic}`.
- **Step elements are singletons.** One `Funnel_Step` instance exists per type. Any `Step` using it rebinds
  it with `set_current_step()`, and benchmarks reset per-fire state with `reset()`. Never keep per-step state
  on an element between calls.

---

## 3. The step tree: branches, order and levels

A flow is a tree stored flat. Each step's `branch` column says which list it belongs to:

- **`main`**: the top-level sequence.
- **`"<benchmarkId>"`**: steps that run only after that particular trigger. Adjacent benchmarks in a branch
  form an **OR group** (any of them starts or continues the flow), and each one can have its own inner
  branch. The editor shows this as the "add step" button inside a trigger. `get-flow` calls it the `then`
  branch.
- **`"<logicId>-<key>"`**: the branches of a branching logic step. Examples:
  - if/else: `123-yes`, `123-no`;
  - Multi Branch (`split_path`): `123-<key>` for each defined branch, plus `123-else`;
  - A/B test (`split_test`): `123-a`, `123-b`;
  - Random Distribution (`weighted_distribution`): `123-<key>`;
  - Evergreen / Date Sync (`evergreen_sequence`): `123-eg`.

  Branch keys go through `sanitize_key`, so they can contain `-`. Parse the owner with
  `strtok($branch, '-')` or `explode('-')[0]`, never by splitting on the last dash.

### `Funnel::set_step_levels( $branch = 'main', $level = 1 )`

This is the single source of truth for `step_order`, `step_level`, `branch_path` and `ancestors`. Run it
after any structural change (the editor save, `create-flow`, `edit-flow`, and some updaters do).

- The top-level call resets the static counter `Step::increment_step_order(0)`.
- It walks the steps of `$branch` in their current `step_order`, calling `update_branch_path_in_db()` on
  each.
- **Benchmark:** it gets `step_level = $level` and the next order. The walk then recurses into branch
  `"$ID"` at `$level + 1` and tracks `maxDepth`. Consecutive benchmarks share a level.
- **Non-benchmark:**
  - If the previous step was a benchmark, `$level = maxDepth`, so it sits below every trigger's inner
    branch.
  - It gets `step_level = $level` and the next order, then `$level++`.
  - **Branch logic:** it recurses into every distinct `branch` value that its existing sub-steps use, at
    `$level`, then sets `$level = maxDepth`. Empty branches aren't visited.

The result:

- `step_order` is a depth-first pre-order numbering.
- `step_level` increases strictly along any path.
- The step after a branch group has a level above everything inside that group. This is what lets a branch
  "fall through" to its parent's next step.

**Relative order in a branch is whatever `step_order` says before the call.** To position steps, write
`branch` plus a `step_order` that sorts correctly within that branch (it only has to be relative), then call
`set_step_levels()`. `edit-flow` writes 1..n per branch; the editor increments a global counter in posted
order.

### Navigation helpers (`Step`)

- **Order comparisons:**
  - `is_before($other)` / `is_after($other)`: same flow, `step_order` **and** `step_level` both
    smaller/greater, and not parallel.
  - `is_parallel()`: lowest-common-ancestor logic. Steps in sibling branches, and adjacent benchmarks at the
    same level, are parallel. Check this instead of comparing orders.
- **Tree walking:**
  - `get_parent_step()`: parsed from `branch`.
  - `get_sub_steps()`: benchmarks match `branch === "ID"`, logic matches `branch` starting with `"ID-"`.
  - `get_descendant_ids()`: recursive.
  - `get_branch_path_ids()`, `get_ancestor_ids()`.
  - `get_siblings()`, `get_siblings_of_same_level()`, `get_next_sibling()`.
- **Step lists:**
  - `get_preceding_steps()` / `_siblings()` / `_actions()` (`is_before && is_action`) /
    `get_preceding_actions_of_type()`.
  - `get_proceeding_actions()` / `_benchmarks()`: these are by order only.
  - Older helpers are **order-only and not branch-aware**: `get_next_of_type()`, `get_prev_of_type()`,
    `get_prev_action()`, `get_prev_step()`.
- **Flow-level (`Funnel`):**
  - `get_first_action_id()`: first action by order; not branch-aware.
  - `get_first_step_id()`: first benchmark.
  - `get_entry_steps()`: `is_starting() || is_entry()`.
  - `get_conversion_steps()`.
- **`Step` predicates:**
  - `is_starting()`: a benchmark in `main` at level 1 or order 1. `is_inner()` is its negation.
  - `is_timer()`: filterable; defaults to `delay_timer`, `date_timer`, `advanced_timer`, `field_timer`.
  - `is_branch_logic()`: a logic step whose element extends `Branch_Logic`.
  - `has_branches()`: `is_benchmark() || is_branch_logic()`.

---

## 4. Step types

### Registration: `Groundhogg\Steps\Manager`

`Manager::init_steps()` runs on `init` priority 1. In order it:

1. registers **sub-groups**: delay, comms, notifications, forms, activity, crm, wordpress, sms, user, lms,
   other, developer, branching, logic ("Routing"), special. Add more with
   `register_sub_group($group, $name, $priority)`.
2. registers the **core types**:

   | group | types |
   |---|---|
   | actions | `send_email`, `admin_notification`, `apply_tag`, `remove_tag` (extends `Apply_Tag`), `apply_note`, `create_task`, `delay_timer`, `add_to_flow` |
   | benchmarks | `web_form`, `account_created`, `link_click`, `tag_applied`, `tag_removed`, `email_confirmed`, `optin_status_changed`, `form_fill` (legacy), `task_completed`, `email_opened` (legacy) |
   | logic | `if_else` |
   | other | `error` |

   `sleep` exists but isn't registered.
3. registers the **premium stubs**, but only when `! is_pro_features_active() && ! is_white_labeled()`:

   | group | types |
   |---|---|
   | actions | `apply_owner`, `create_user`, `edit_meta`, `date_timer`, `field_timer`, `advanced_timer`, `http_post`, `loop`, `new_custom_activity`, `plugin_action`, `skip` |
   | benchmarks | `custom_activity`, `field_changed`, `login_status`, `page_visited`, `plugin_api`, `post_published`, `role_changed`, `webhook_listener` |
   | logic | `split_path`, `split_test`, `weighted_distribution`, `evergreen_sequence`, `logic_loop`, `logic_skip`, `logic_stop`, `logic_jump` |

   `timer_skip` is commented out.
4. fires **`do_action('groundhogg/steps/init', $manager)`**. Add-ons call `$manager->add_step(new X())`
   here. `add_step()` keys by `get_type()`, so registering an existing type again **replaces** it; this is
   how Pro swaps its real classes over the stubs. Pro registers at the default priority 10.

Other `Manager` API:

- `get_types()`, `type_is_registered($t)`
- `get_element($t)`: falls back to the `error` element
- `get_elements()`: actions + benchmarks + logic
- `get_actions()`, `get_benchmarks()`, `get_logic()`, `get_*_types()`
- `filter_by_group()`, `filter_by_sub_group()`
- magic `__set` alias for `add_step`

`Groundhogg.rawStepTypes` in JS is `get_elements()`, JSON-serialized through
`Funnel_Step::jsonSerialize()` as `{icon, svg, name, type, group, context}`.

### Special elements

- **Premium stubs** (`Trait_Premium_Step`):
  - `run()` returns `WP_Error('premium')`, so the event **fails** and the contact stops.
  - Benchmark hooks are neutralised.
  - `settings()` shows an upgrade notice; `generate_step_title()` is the name; `is_premium()` is true.
- **Polyfills** (`Trait_Polyfill`), used for steps whose type isn't registered, e.g. a deactivated add-on:
  - the element shows a "not registered" notice;
  - `validate_settings()` adds `invalid-type`;
  - `run()` returns **false**, so the event is **skipped** and the flow continues past it.
- **`error`** element: `run()` returns `WP_Error('invalid_step_type')`. Only direct `Manager::get_element()`
  calls reach it.
- **Legacy** (`Trait_Legacy`, or `is_legacy()` returning true): hidden from the step picker unless the
  `gh_show_legacy_steps` option is on. Existing legacy steps still render and run. Examples: `form_fill`,
  `email_opened`, premium `loop`, `skip`, `logic_loop`, `logic_skip`. "Legacy" is about the picker; it
  doesn't mean the type uses `get_posted_data` (`link_click` does, but isn't legacy).

### `Funnel_Step`: the step type contract

It's abstract and extends `Supports_Errors`. The group constants are `ACTION`, `BENCHMARK` and `LOGIC`.

**Must implement:**

- `get_name()`, `get_type()`, `get_description()`, `settings( $step )`
- `get_group()`: the base classes (`Action`, `Benchmark`, `Logic`) supply it as `final`.

**Commonly overridden:**

| method | purpose |
|---|---|
| `get_sub_group()` | picker grouping (default `other`) |
| `get_icon()` / `get_icon_svg()` | default `assets/images/funnel-icons/<type-with-dashes>.svg` |
| `get_settings_schema()` | sanitizer rules for settings (5) |
| `save( $step )` | anything the schema can't express (legacy `get_posted_data()` style, side effects) |
| `after_save( $step )` | call parent; it saves the generated title and slug |
| `validate_settings( $step )` | add warnings with `$step->add_error( $code, $msg )`; shown in the editor (9) |
| `generate_step_title( $step )` | return a title derived from settings, or false to use the stored title |
| `run( $contact, $event )` | do the work; return `true` (complete), `false` (skip, continue), or `WP_Error` (fail, stop) |
| `calc_run_time( int $base, Step $step ): int` | when the step should run (timers); default "now" |
| `get_step_reference_settings(): string[]` | setting names that hold other step IDs (7) |
| `import( $args, $step )`, `export( $args, $step )`, `post_import( $step )`, `duplicate( $new, $original )`, `delete( $step )` | lifecycle hooks (12) |
| `settings_should_ignore_morph()` | default true; see 9 |
| `add_additional_actions()` | constructor hook point |
| `admin_scripts()`, `frontend_scripts()` | enqueued automatically on the editor or frontend |
| `labels()` | extra labels on the canvas card |

**Helpers:**

- **Settings:**
  - `get_setting( $key, $default = null )`: reads meta for the *current step*. If `$default` is null it uses
    the schema default. It returns `$val ?: $default`, so falsy values fall back to the default.
  - `save_setting( $key, $val )` → `Step::update_meta()`.
  - `get_posted_settings()`, `get_posted_data( $key, $default )`: the settings passed to `pre_save()`.
- **Legacy form helpers:**
  - `setting_id_prefix( $s )` → `step_<ID>_<s>`, `setting_name_prefix( $s )` → `steps[<ID>][<s>]`.
  - `start_controls_section()`, `add_control()`, `end_controls_section()`: the legacy form-table builder.
- **Context:** `get_current_step()`, `get_current_contact()`, `get_current_event()`.
- **Other:** `get_like_steps( $query )` (benchmarks use it to find all their steps across flows),
  `get_help_article()`.

**Deprecated timing:** `enqueue( $step )` and `get_delay_time( $step )`. Use `calc_run_time()`.

### Base classes

- **`Action`**: sets the group, nothing else.
- **`Benchmark`**:
  - Implement:
    - `get_complete_hooks()`: `[ 'wp_hook' => numArgs ]`, or entries of `[hook, numArgs]`;
    - `setup( ...args )`: store data with `add_data()` / `set_args()`;
    - `get_the_contact()`: a `Contact`, an array of them, or false;
    - `can_complete_step()`: bool, using `get_setting()` and the stored data.
  - The constructor hooks each WP hook three times: `reset` at priority 97, `setup` at 98, `complete` at 99.
  - `complete()` walks `get_like_steps( [ 'step_status' => 'active' ] )` across all flows. For each step it
    binds the step and contact, checks `can_complete_step()`, then calls
    `$step->benchmark_enqueue( $contact, data_as_args() + args )`.
  - `data_as_args()` turns objects into IDs so the event args stay scalar.
  - `sortable_item()` renders the OR group and the inner branch; `delete()` and `duplicate()` cascade to
    sub-steps (`duplicate` skips them when the `__ignore_inner` post var is set).
- **`Logic`**: implement `get_logic_action( Contact ): Step|false`. False means "fall back to the next
  step".
- **`Branch_Logic`** (extends `Logic`):
  - Implement `get_branches()` (prefixed keys, e.g. `"<ID>-yes"`), `get_branch_name( $branch )` and
    `matches_branch_conditions( string $branch, Contact )`.
  - `get_logic_action()` = first matching branch → `get_first_of_branch()`, which is false when the branch
    is empty.
  - Helpers: `maybe_prefix_branch()`, `get_sub_steps( $branch = false )`.
  - `sortable_item()` renders the side-by-side branches; `delete()` and `duplicate()` recurse (duplicate
    rewrites branch prefixes).
  - **`If_Else`**: settings `include_filters` / `exclude_filters`, run through a `Contact_Query` once.
    Empty filters match "yes".
- **Pro logic:**
  - `Split_Path` (Multi Branch): `meta.branches = { key: { name, include_filters, exclude_filters } }` plus
    an implicit `else`.
  - `Split_Test` extends `Split_Path`: fixed `a` / `b`, `weight`, a winner declared by branch.
  - `Weighted_Distribution` extends `Split_Path`: `branches = { key: { weight } }`, no `else`.
  - `Evergreen_Sequence`: branch `eg`, optional `timers` (IDs of timer steps inside `eg`).
  - Non-branching Pro logic types keep their target step ID in the `next` setting:
    - `logic_jump`: go to a step;
    - `logic_loop`: back to a preceding step;
    - `logic_skip`: ahead to a later step;
    - `logic_stop`: end.
  - Pro's `loop` / `skip` actions work the same way.

---

## 5. Step settings and sanitization

### The schema (`Funnel_Step::get_settings_schema()`)

It's a map of `setting => rules`. This is **not** JSON Schema; the JSON Schema for abilities lives in
`Step_Type_Schema` (11).

| rule | used by | meaning |
|---|---|---|
| `sanitize` | `sanitize_setting()` | callable run on the value; default `\Groundhogg\sanitize_payload` |
| `default` | `sanitize_setting()`, `get_setting()` | on save, an `empty()` value becomes the default **before** sanitizing (so `0`, `''`, `[]` and `false` all become the default); on read, the fallback |
| `initial` | `Funnels_Page::process_edit()`, `Step_Tree_Builder::get_initial_settings()` | written once when the editor or the abilities' builder creates a new step (given settings win). Not applied by import, duplicate or `Funnel::add_step()` |
| `if_undefined` | `pre_save()` | saved when the key is **missing** from the posted settings. Used for checkboxes, which aren't posted when unchecked |
| `import` | `sanitize_setting( ..., 'import' )` | looser sanitizer used while importing, e.g. Pro `next` uses `absint` instead of an existence check, so IDs can be remapped in `post_import` |

`in_settings_schema( $key )` uses `isset_not_empty`, so an entry with an empty rules array counts as absent.

Examples:

- **`delay_timer`**:
  - `delay_amount` → `absint`;
  - `delay_type` → one_of minutes … none;
  - `run_when` → now | later | between;
  - `run_time` → normalized to `H:i:s`;
  - `run_on_dow` / `run_on_months` → intersected against the allowed values;
  - `run_on_dom` → 1–31 or `last`;
  - `delay_preview` → `wp_kses( 'data' )`.
- **`send_email`**:
  - `email_id` → `absint`;
  - `reply_in_thread` → `absint`;
  - `skip_if_confirmed` → `boolval` with `if_undefined` false (it's a PHP checkbox).
- **`apply_tag`**: `tags` → `parse_tag_list`.
- **`if_else`**:
  - `include_filters` / `exclude_filters` → `Filters::sanitize`, with default and initial `[]`;
  - `*_display` → kses.
- **`web_form`**: the main user of `initial` (form name, ajax on, success message).

### How settings reach the database

There are three paths, all ending in `Step::update_meta()`:

1. **Schema + posted form fields** (`pre_save`). PHP renders inputs named `steps[<ID>][<setting>]`. The
   editor posts the whole form; `Funnel_Step::pre_save( $step, ?array $settings )` then:
   - reads `$_POST['steps'][ID]` (the editor), or uses the `$settings` array passed in (the abilities, via
     `Step::save( $settings )` / `update_settings()`);
   - for each schema key: if posted, calls `save_setting()`; if missing, applies `if_undefined` when there
     is one and otherwise leaves the stored value alone;
   - updates the columns:
     - `branch`: defaults to `main` when missing, so a `save()` with a full settings array must include
       `branch`;
     - `step_order`: editor path only, using the global counter;
     - `step_title`: only if posted;
     - `is_entry` / `is_conversion` / `can_passthru` for benchmarks.
2. **JS meta** (`metaUpdates`). Step JS calls `Funnel.updateStepMeta( { key: value } )`. The editor posts
   JSON `metaUpdates = { stepId: { key: value } }`, and `process_edit()` applies it with
   `update_meta( array )` **before** `$step->save()`.
3. **Legacy `save()`**. `save( $step )` reads `get_posted_data()` and calls `save_setting()` with inline
   sanitizing. Still used by `form_fill`, `link_click` and `email_opened`, which have **no schema**: their
   meta isn't sanitized by `sanitize_meta`, only by their own `save()`.

### `Step::sanitize_meta( $key, $value )`

- Keys in the element's schema → `sanitize_setting()`, in import mode while `Step::is_importing()`
  (only while `Funnel::import()` creates and post-imports the steps, between `Step::start_importing()` and
  `stop_importing()`).
- Otherwise a switch:
  - `_trigger_frequency` → one_of unlimited | once | x;
  - `_trigger_frequency_range` → all | x_days;
  - `_trigger_frequency_x_times` / `_x_days` → `absint`;
  - `step_notes` → `kses( simple )`.
- **Everything else is stored unsanitized.**
- It runs from `Base_Object_With_Meta::update_meta()` / `add_meta()` for inactive (or committing) steps,
  and from `Step::update_meta()` / `add_meta()` before staging for active steps.

### The save pipeline and titles

- `Step::save( ?array $settings )`: `merge_changes()` → element `pre_save()` → `save()` → `after_save()`.
- `after_save()`:
  - fires `groundhogg/steps/save/after`;
  - writes `generate_step_title()` into `step_title` unless `force_custom_step_names()`;
  - updates the slug and clears errors.

  So a title the user or an agent set is overwritten on the next save for types that generate titles.
  `edit-flow` works around this by re-applying the title (11).
- `Step::get_settings()`: the current settings in `save()`'s shape (all meta + `step_title`, `branch` and
  the benchmark flags, with staged changes merged). `Step::update_settings( $patch )` merges a patch into
  that and saves: a partial update that behaves like the editor.
- **Delay timer titles:** the editor JS writes `delay_preview` (`delayTimerName()` in `funnel-steps.js`).
  `Delay_Timer::generate_step_title()` uses it, falling back to the server-side `Delay_Timer::delay_preview()`,
  which mirrors the JS (so steps created by abilities get correct titles). If you change the JS wording,
  change the PHP too.

---

## 6. Runtime: how contacts move through a flow

### Getting in

- **Benchmarks.** A WP hook fires; the benchmark's `complete()` finds every active step of that type and
  calls `Step::benchmark_enqueue( $contact, $args )`:
  1. `can_complete( $contact )`:
     - the step must be an active benchmark;
     - a starting or entry benchmark goes straight to `check_trigger_frequency()`: meta
       `_trigger_frequency` = `unlimited` (default), `once`, or `x` (`_trigger_frequency_x_times`,
       optionally within `_trigger_frequency_x_days` when `_trigger_frequency_range` is `x_days`), counted
       from the contact's events for this step in history;
     - an inner benchmark needs the contact to be in the flow: `get_current_funnel_step()` takes the
       contact's waiting event in this flow, else their last completed one. Then `can_travel( $from )`
       walks the parents: a logic parent must match `matches_branch_conditions()`, and a benchmark parent
       must be passthru. Then the trigger-frequency check.
     - Otherwise it asks the filter `groundhogg/step/can_complete` (default false).
  2. For inner steps the previous event's args are merged in, with the new args winning.
  3. `enqueue()`.
- **Directly**, with `Step::enqueue( $contact, $skip_enqueued = true, $args = [] )`. This is used by
  `add-to-flow`, REST `/funnels/{id}/start`, background tasks and the `add_to_flow` action.
  **There is no `Funnel::add_contact()`.**

### `Step::_enqueue()`

1. A logic step is replaced by `get_next_action( $contact )`, because logic steps are never queued
   themselves.
2. `groundhogg/step/enqueue` (a filter by reference) runs until it returns the same step. A non-`Step`
   result aborts. Timers and skip logic hook in here.
3. If `$skip_enqueued`, the contact's other WAITING events in this flow become SKIPPED
   (`skipped_by_step`). **A contact is in one place per flow.**
4. It inserts the queue row `{ time: get_run_time(), funnel_id, step_id, contact_id,
   event_type: FUNNEL, priority: 10, args }`, filtered by `groundhogg/step/enqueue/event`. `email_id` is
   added for `send_email`.

`get_run_time()` → `element->calc_run_time( base ?: time(), $step )`.

### Processing

- `Event_Queue` runs from WP cron (`groundhogg_process_queue`, every minute), from `gh-cron.php` (when
  installed and pinging, WP cron is skipped), or from the heartbeat.
- `run_queue()` → `process()` loop:
  - `Event_Store_V2::claim_events()` claims WAITING rows with `time <= now`, ordered by priority then ID;
  - each event runs;
  - claims are released;
  - finished rows are moved to history.
- **PAUSED events are never claimed.** Limits: 50 events per batch (filterable) and a time limit.
- `process_events( $contacts )` runs the queue for just those contacts (5s), e.g. right after a form
  submission.
- **Local dev site:** `DISABLE_WP_CRON` is true, so nothing advances until cron runs deliberately.

### `Event::run()` → `Step::run()`

- **`Step::run( $contact, $event )`:**
  - sets `the_funnel()`;
  - an inactive step returns `WP_Error('funnel_inactive')`;
  - filter `groundhogg/steps/run/do_step`;
  - `groundhogg/steps/{type}/run/before`, element `pre_run()` + `run()`, then `…/run/after`;
  - filter `groundhogg/steps/run/result`;
  - conversion benchmarks record a `funnel_conversion` activity.
- **`Event::run()` interprets the result:**

  | result | event | then |
  |---|---|---|
  | `true` / anything else | `complete()` | `Step::run_after()` enqueues the next action, with args carried forward |
  | `false` | `skip()` (`soft_fail`) | `Step::run_after()` — the flow **continues** |
  | `WP_Error` | `fail()` | the contact **stops** here |

  Exceptions are caught.
- Status actions fire on each transition: `groundhogg/event/{queued|cancelled|failed|skipped|pause|complete}`.

### Choosing the next step

- **`Step::get_next_action( Contact )`** → `_get_next_action()`:
  - benchmark: the first step after it, which goes into its own inner branch first;
  - logic: `element->get_logic_action( $contact )`, falling back to `get_next_step()` on false (e.g. an
    empty branch);
  - action: `get_next_step()`.
- **The result is checked:**
  - an action, or none → returned;
  - a **non-passthru benchmark** → false, so the contact waits there (the flow ends for them until that
    trigger fires);
  - logic or a passthru benchmark → recurse.
- **`get_next_step()`:** the first step in flow order whose `branch` is in this step's `branch_path`
  (its own branch or any ancestor branch) and which `is_after()` this one. That's how the end of a branch
  continues in the parent. If the next step is a non-passthru benchmark and this isn't a benchmark, a
  passthru benchmark sibling at the same level is used instead when there is one. Filters:
  `groundhogg/step/next_step`, `groundhogg/step/next_action`.
- **Passthru** (`can_passthru`, benchmarks only): a contact arriving at the trigger from above continues
  through it instead of waiting.
- **Entry** (`is_entry`): an inner trigger can also start the flow for contacts not in it.
- **Conversion** (`is_conversion`): completing the trigger counts as a conversion in reports.
- **Event args** travel with the contact (`run_after()` passes `$event->args`; benchmarks merge the
  previous args). Use `add_event_args()` / `get_event_arg()`, which also work under the Simulator.

### Flow status and events

- `Funnel::update()` on a status change → `update_step_status()` (activating sets inactive steps active,
  with `date_activated`) + `update_events_from_status()`:
  - active → `unpause_events()`;
  - inactive → `pause_events()` (WAITING → PAUSED);
  - archived → `cancel_events()` (cancelled, moved to history).
- `Funnel::delete()` archives, cancels, deletes the steps, then deletes the row.
- Contacts **in** an inactive flow are paused, not removed.

---

## 7. Editing model: drafts, publishing, deletes, undo

### Editing mode

- `Funnel::get_steps()` **depends on context**:
  - not editing + active flow → only `active` steps, as stored (the live flow);
  - editing → every non-archived step, with staged `changes` merged, `deleted` ones dropped, and re-sorted
    (the draft).
- `Funnel::is_editing()` is true when `Funnel::start_editing()` / `while_editing( fn )` is in effect, or
  when the request or referer is the editor page (`page=gh_funnels&action=edit&funnel=<id>`).
- Editing mode is stored **per funnel ID in a static**, because every `Step` builds its own `Funnel`
  instance via `get_funnel()`. Calls nest (it's a counter).
- **Anything that edits a flow outside the editor (abilities, REST, CLI) must wrap its work in
  `while_editing()`**, or it will ignore new steps and staged changes.
- `Funnel::get_real_steps()` = the stored rows, no merging, no deleted filtering (still excludes archived).

### Active flows stage every edit

- **Routing.** `Step::update()` / `update_meta()` / `add_meta()` on an **active** step (and not committing)
  go to `add_changes()`: sanitize, merge into `changes`, keep only differences from the stored values,
  write just the `changes` column.
- **Viewing.** `merge_changes()` overlays changes in memory: column keys to data, the rest to meta.
- **New steps** are created `inactive` (editor, builder, `edit-flow`).
- **Deletes are soft:**
  - `Step::delete()` sets `step_status = deleted`; on an active step that is staged in `changes`, on an
    inactive step it's written directly;
  - the element's `delete()` cascades to sub-steps;
  - soft deletes exist **so undo/redo can restore the row with the same ID, meta, references and paused
    events**.
- **Publishing (`Funnel::commit( $deleted_step_choices )`, active flows only):**
  1. `resolve_deleted_step_events( $choices )`: contacts waiting or paused at deleted steps are **moved**
     to another action (`get_move_targets()`) or **cancelled**. Cancelled ones get `error_code
     step_deleted` and are moved to history.
  2. `Step::commit()` on every real step: apply changes to meta and data, clear `changes`, stamp
     `date_committed`. A `deleted` step is **archived** if `has_history()` (events or activity for it),
     otherwise hard-deleted.
  3. `update_step_status()`: inactive becomes active.
- **Discarding (`Funnel::uncommit()`):** inactive steps are deleted for real; active ones get their
  `changes` cleared.
- **Inactive flows:**
  - edits are written directly and deletes set `deleted` on the row;
  - activating runs `resolve_deleted_step_events()` + `remove_deleted_steps()` before the status change
    (the editor's Activate, and `activate-flow`);
  - deactivating through the editor uncommits unless Update was also pressed; `deactivate-flow` requires
    `pending_changes: publish|discard` when there are unpublished changes.
- **Archived steps are kept only for their history.** Every step query must exclude `archived`
  (`get_steps()` / `get_real_steps()` do).
- `Funnel::has_changes()` = any step with changes or status `inactive`, or any soft-deleted step (use it
  in editing mode). The editor's save response sends it as `has_changes` for the Publish button; the
  abilities use `Flow_Changes::has_unpublished_changes()` (active flows only).
- `Funnel::get_deleted_steps()`, `count_pending_events( $step )` (waiting + paused), `get_move_targets()`
  (non-deleted actions).

### Undo/redo vs rollback

- **The editor's undo** (since 4.9) is operations, see 9: each edit records the operations that reverse it, and
  undoing sends those like any other edit. Undoing a delete is Flow_Operations' `restore`, which is why deletes
  stay soft until publishing.
- **`snapshot()` / `restore( $snapshot )`**: the editor's old undo format, `[ { ID, data, meta } ]` as the
  editor sees the steps. `snapshot()` is still what `Get_Flow::revision()` hashes. `restore()`:
  - soft-deletes steps not in the snapshot;
  - `update()`s the rest back to their data (staged on active flows);
  - `update_meta()`s their meta.

  It **doesn't remove meta keys added after the snapshot**. That's harmless for editor defaults, and why
  it isn't used for transactional rollback.
- **`backup()` / `rollback( $backup )`**: exact raw restore of step rows (including `changes`) and meta,
  deleting rows added since. It writes the tables directly, so nothing is staged, cascaded or hooked.
  `edit-flow` uses it for all-or-nothing edits and dry runs.

### Step references (delete guard)

- Steps point at other steps in their settings. Each type declares which settings through
  `Funnel_Step::get_step_reference_settings()`:

  | type | setting |
  |---|---|
  | `send_email` | `reply_in_thread` |
  | `task_completed` | `tasks` |
  | `email_opened` | `email_steps` |
  | `add_to_flow` | `step_id` (may be in **another** flow) |
  | premium `loop`, `skip`, `logic_jump`, `logic_loop`, `logic_skip` | `next` |
  | `evergreen_sequence` | `timers` |

  The Pro types are declared on core's premium base classes, which Pro's classes extend, so this works with
  any Pro version.
- `Funnel_Step::get_step_references( $step )` → the IDs.
- `Funnel::get_step_references_map()` → `{ stepId: [ referencingIds ] }`. It covers the draft, plus
  `add_to_flow` steps in other flows that point into this one.
- `Funnel::can_delete_steps( $ids )` → true, or `WP_Error('step_referenced')` naming the users (and their
  flow if it's a different one). Pass the step **plus its descendants** (`Step::get_descendant_ids()`).
  Referencing steps that are being deleted in the same operation don't block.
- **Enforced** in the editor's `_delete_step` (server, plus a JS pre-check with a "This step is being used"
  dialog) and in `edit-flow`'s delete. **Not enforced** by `Step::delete()` itself, on purpose: deleting a
  whole flow, undo, and discard must still work.
- Moves are not checked yet; moving a step can still break e.g. `is_before()`-dependent references.

### Locking

- **Edit lock (the whole flow):** `includes/edit-lock.php`, the same system WP uses for posts. Opening the
  editor calls `use_edit_lock( $funnel, false )`: a `_edit_lock` funnel meta of `"<time>:<user>"`, refreshed by
  the heartbeat every 30s, valid for 150s (`wp_check_post_lock_window`). There's no take-over: anyone else gets
  a modal that only lets them exit. The editor releases it on `pagehide` (`sendBeacon` to
  `wp_ajax_groundhogg_remove_lock`, only the holder can). While another user holds it, the editor's save and
  the `edit-flow`, `publish-flow-changes`, `discard-flow-changes`, `activate-flow` and `deactivate-flow`
  abilities are refused (`flow_locked` / `groundhogg_flow_locked`, via `Flow_Changes::check_lock()`). The
  same user in another tab, or an agent acting as them, isn't locked out.
- **Step locks:** `is_locked` is written directly, bypassing staging, by the `lock` / `unlock` operations (the
  editor and `edit-flow`) and the old form save's `_lock_step` / `_unlock_step`.
- Locked steps can't be dragged (`.locked` cancels sortable), and `edit-flow` refuses to update, move or
  delete them.
- `Step::is_locked()` is filterable (`groundhogg/step/is_locked`).

---

## 8. The editor save protocol (server)

The editor saves two ways (the REST `/funnels/{id}/commit` route is **not** used by it):

- **Operations** (since 4.9): adding, moving, deleting, and undo/redo post `admin-ajax.php?action=gh_flow_operations`
  (`Funnels_Page::ajax_flow_operations()`) with `funnel`, `revision`, and `operations`, a JSON list in
  `groundhogg/edit-flow`'s format. See below.
- **Flow actions** (`gh_flow_action`, `Funnels_Page::ajax_flow_action()`): `flow_action` publish, activate,
  deactivate, or revert, with `deleted_steps` choices in the abilities' format (`Flow_Changes::get_choices()`).
  Publish and activate use `Flow_Changes::publish()` / `remove_deleted_steps()`, like the abilities; deactivate and
  revert `uncommit()`.
- **The form** (`gh_save_funnel_via_ajax`, `?auto-save=1` or `?explicit-save=1`): the editor no longer uses it.
  `Funnel.save()` only posts it when a caller adds its own fields (`moreData`), for third-party JS.
  `Funnels_Page::ajax_save_funnel()` calls `verify_action()`, then `process_edit()`.

Both respond with `Funnels_Page::get_editor_state()`, encoded while editing: `{ canvas, settings, funnel,
pending_deletes, step_references, has_changes, revision }`, plus `err?` from the form, and `ids` from operations.

**Operations (`ajax_flow_operations()`):**

- checks the admin ajax nonce and `edit_funnel`;
- refuses when another user holds the edit lock (`flow_locked`), or when `revision` isn't the flow's current
  `Get_Flow::revision()` (`flow_changed`, it was changed in another tab or by an agent);
- applies the operations with `new Flow_Operations( $funnel, true )` → `apply_all_or_nothing()`: in editing mode,
  inside `backup()` / `rollback()`, so a refused batch changes nothing;
- responds `{ ids: { localId: realId }, ...state }`, or when refused `success: false` with `{ code, message, state }`.

**`Flow_Operations`** (`includes/abilities/funnels/flow-operations.php`) is the engine `edit-flow` used to have
inline; the ability now calls it without editor mode. Editor mode adds:

- `add` of **any registered type** (`Step_Tree_Builder::allow_any_type()`), not only the ones described for the
  abilities; those get their initial settings and can't be given settings or branches. Premium placeholders are
  refused.
- `update` with `meta` (setting → value, `null` deletes) and/or a raw `title`: set as stored, no save handlers,
  and allowed on locked steps. For undo/redo.
- `restore` `{ step, at, steps: [ids deleted with it] }`: un-deletes soft-deleted steps (the row's `deleted` →
  `inactive`, or the staged delete is dropped on active steps) and places them.
- `write_layout()` numbers steps with one count across branches, so sibling branches don't tie and
  `set_step_levels()` walks them in a stable order.

**`process_edit()`, in order:**

1. **`_restore`** (the old undo/redo, no longer used by the editor): `$funnel->restore( json )` and return.
2. **`_delete_step`**:
   - `can_delete_steps( [ step, ...descendants ] )` inside `while_editing()`;
   - if blocked, return the `WP_Error` and save nothing else (the response re-renders the step);
   - otherwise soft `$step->delete()`.
3. **`_lock_step` / `_unlock_step`**: written directly.
4. **`max_input_vars` guard**: returns an error when the POST is too big.
5. **Walk `step_ids[]` in order.** `Step::increment_step_order(0)` resets the counter first; each save
   takes the next number, so the posted order becomes the new order. Each entry is one of:
   - **JSON `{ duplicate: id }`** → `Step::duplicate()` in place (inactive).
   - **JSON `{ copy: id, branch }`** → duplicate into this flow and branch (copy/paste across flows).
   - **JSON `{ step_type, step_group, branch }`** → a new inactive step, titled with the element name,
     with schema `initial` values written. An unregistered type is skipped.
   - **A numeric ID** → apply `metaUpdates[ID]` with `update_meta()`, then `$step->save()` (reads
     `$_POST['steps'][ID]`).
6. **`$funnel->set_step_levels()`.**
7. **`_activate`**: `resolve_deleted_step_events( choices )` + `remove_deleted_steps()`, status `active`.
8. **`_deactivate`**: `uncommit()` unless `_commit`; status `inactive`.
9. **`_uncommit`**: `uncommit()`.
10. **`_commit`** (active): `commit( choices )`. `choices` comes from `_deleted_steps` JSON
    `{ stepId: { action: cancel|move, to } }`.
11. Title → `funnel_title`; `do_action( 'groundhogg/admin/funnel/updated', $funnel )`.

**Response helpers:**

- `get_canvas_data()`: `{ stepId: Funnel_Step::get_canvas_data() }`, running `validate_settings()` first. The
  editor draws the canvas from it (see 9). `step_flow()` still renders the old server markup, which the canvas
  fixtures compare against, but nothing in the editor uses it.
- **Edit lock:** the request is refused with `wp_send_json_error( WP_Error( 'flow_locked' ) )` when another user
  holds the flow's edit lock (`check_lock()`, see 7).
- `step_settings()`: settings panels, **sorted by ID** so morphdom positions stay stable.
- `get_pending_deletes()`: `{ steps: [ { ID, title, contacts, next } ], targets: [ { ID, title } ] }`.
- `get_step_references()`: `{ stepId: [ { ID, title } ] }`, with titles suffixed "(in <flow>)" for other
  flows.

Page load (`Funnels_Page::scripts()`) prints the same data as the inline global
`var Funnel = { ...get_as_array(), id, save_text, export_url, is_active, funnelTourDismissed,
scratchFunnelURL, is_editor: true, pending_deletes, step_references, canvas, debug, revision }`, then fires
`groundhogg/admin/funnels/editor_scripts` with the `Funnel`.

---

## 9. The flow editor frontend

### Scripts

- Handles are registered in `includes/scripts.php` `register_admin_scripts()`:
  - `groundhogg-admin-flow-store` (`flow-store.js`) and `groundhogg-admin-flow-canvas` (`flow-canvas.js`),
    which draw the canvas;
  - `groundhogg-admin-funnel-editor` (`funnel-editor.js`), which depends on jquery, the flow canvas, groundhogg-admin,
    -element, -functions, -form-builder-v2, email block editor, `groundhogg-admin-flow-logic-lines`,
    `groundhogg-admin-flow-simulator` and `groundhogg-admin-funnel-scheduler`;
  - `groundhogg-admin-funnel-steps` (`funnel-steps.js`), which depends on the editor.
- Add-ons register their handles on `groundhogg/scripts/after_register_admin_scripts` and enqueue them on
  `groundhogg/admin/funnels/editor_scripts`. The `Extension` base class wires both
  (`register_admin_scripts()`, `funnel_editor_scripts( $funnel )`). **Declare a dependency on
  `groundhogg-admin-funnel-editor`** (SMS and Pro do).
- `.min.js` files are generated by PhpStorm file watchers from the `.js`. **Don't build them by hand**;
  re-save the `.js` in the IDE. The dev site loads unminified files with `?ver=<plugin version>`, which
  doesn't change on edits, so Chrome caches old scripts. Fetch the script with `cache: 'reload'` before
  testing.

### The `Funnel` global

`funnel-editor.js` is a jQuery IIFE. Inside `if ( Funnel.is_editor )` it does
`$.extend( Funnel, { ...editor } )`.

**State:**

- `store` (`FlowStore.createStore()`), the steps and canvas data everything draws from. `steps` is a getter for
  `store.steps`: `[ { ID, data, meta, … } ]`, replaced from every save response;
- `queue` (`FlowStore.createQueue()`), `history` (`FlowStore.createHistory()`), `revision`, `realIds`
  (temporary ID → real ID), `metaBefore` (settings `updateStepMeta()` changed, before they changed, for undo);
- `pending_deletes`, `step_references`;
- `stepCallbacks`, `metaUpdates`;
- `editing` (the step ID whose panel is open);
- `saving`, `dragging`, `moving`;
- `addEl`, `targetAdd`, `targetStep`.

**Routing** is on the URL hash (`views`):

| hash | view |
|---|---|
| `#<stepId>` | edit a step |
| `#add/<group>` | step picker |
| `#simulator` | simulator |
| `#settings` | flow settings |
| empty | nothing selected |

`#step-settings-inner[data-view]` switches the panels in CSS.

**Editing through the store (since 4.9):** `perform( operations )` applies `groundhogg/edit-flow` operations to
the store (`store.apply()`, which returns their inverses), records `{ undo, redo }` in the history, queues them,
and redraws right away (`redraw()`: `drawCanvas()`, `makeSortable()`, `drawLogicLines()`).

- **Store operations** (`flow-store.js`, DOM-free): `add` (steps get a temporary `tmp_…` ID, no dashes, so
  branch strings like `tmp_x-yes` still parse), `move` (refused into its own branches), `delete` (the steps go to
  `store.trash`), `restore`, and `update` (`meta`, `title`). `relayout()` re-derives order, levels, and
  `is_starting` with the JS `set_step_levels()`.
- **The queue** sends one `gh_flow_operations` request at a time; operations made meanwhile go in the next. On
  success the temporary IDs are replaced in the history, the trash, and waiting operations, and `applyState()`
  loads the server's state and re-applies the operations still waiting. On `flow_changed` the batch is re-applied
  on the server's state and sent again (up to twice). Other refusals load the returned state, clear the history,
  and show the message. Requests that don't get through are retried with backoff ("Changes not saved yet").
- **Undo/redo** replay the recorded operations the same way. Once an added step is saved, redoing its add becomes
  a `restore` of the same step.
- `applyState( data, { quiet, shouldMorphSettings, loaded } )` is shared by both saves.

**`save( args )`**: sends pending settings changes, then

- with `moreData` (or `before`), posts the form like the old editor, for third-party JS that adds its own fields;
- otherwise saves the open step's panel through an `update` operation, for step type JS that changes its panel's
  fields and calls `Funnel.save()` (Pro's A/B test and branch editors, the webhook listener, EDD's and Woo's
  legacy triggers), and resolves once it's saved.

`flowAction( action, choices )` does publish, activate, deactivate, and revert, after what's queued
(`whenSaved()`, so `pending_deletes` is current for the choices), and clears the history.

**The form's save** (`postForm()`):

- Args: `{ quiet = true, moreData( formData ), before(), shouldMorphSettings = true }`; `save( true )`
  means quiet.
- It runs through `queue.exclusive()`, after anything queued before it, and `before()` runs right before posting
  (duplicate and paste insert their placeholders there, so a redraw can't wipe them).
- Changes the server makes from the posted settings are diffed (`store.changesSince()`) into a history entry, so
  settings edits can be undone too; `metaBefore` supplies what `updateStepMeta()` values were.
- It calls `updateBranches()` (copies each step's containing `.step-branch[data-branch]` into its hidden
  `steps[ID][branch]` input), then builds `FormData( #funnel-form )`:
  - every `step_ids[]` (existing IDs, or JSON placeholders for new, copied or duplicated steps);
  - every `steps[ID][…]` input;
  - `funnel_title`, `funnel`, the nonce.
- It drops `step_notes` / `note_text`, adds `metaUpdates` JSON, runs `moreData`.
  - `metaUpdates` is taken for this request and reset, so edits made while it's in flight go in the next
    save. If the request fails or the response has `err`, the sent updates are put back (newer edits win)
    and go out with the next save.
- **Response handling** (`applyState()`):
  - updates `pending_deletes`, `step_references`, `revision`;
  - loads `funnel.steps` and `canvas` into `Funnel.store` and redraws the canvas (unless dragging; keeps
    `.editing` and the highlighted add button), then `makeSortable()`;
  - a refused request (`success: false`, e.g. `flow_locked`) shows its message and keeps the `metaUpdates`;
  - morphdoms `.step-settings` with `settings`. **On quiet saves it skips any element matching
    `.editing .ignore-morph`**, so the panel being edited isn't wiped.
  - `drawLogicLines()`;
  - "Publish Changes" (`#funnel-update`) is enabled when `response.data.has_changes`;
  - **explicit saves:** show "Flow saved!" or `err`, re-run `stepSettingsCallbacks()`;
  - **quiet saves:** trigger `auto-save` and `gh-init-pickers`, and don't show `err`. Callers that need
    errors must check `response.data.err` themselves, as delete does.
  - a failed request resets `saving` (otherwise quiet saves would wait for it forever) and shows an error.
- **Settings save through operations (since 4.9):** a `change` in a step's panel → `saveSettings( id )`, and
  `updateStepMeta( meta, id )` → `saveSettings( id, { meta } )`. After 400ms without more changes,
  `flushSettings()` sends `{ op: 'update', step, form, meta, flags }`: `form` is every `steps[ID][…]` field in the
  panel (`FlowStore.formToSettings( $(panel).find(':input').serializeArray(), id )`), which the server saves with
  `Step::save( $form )` after writing `meta`, keeping the step's branch (see 8). `meta` and `flags` apply to the
  store right away. `step_notes` is `meta`. The flow title saves through REST (`FunnelsStore.patch()`).
  - The step before its first unsent change is kept in `settingsBefore`, and once saved
    (`recordSettingsChanges()`) the difference, including the regenerated title, becomes a history entry of
    stored-value updates (`FlowStore.stepChanges()`).
  - Inputs with `.no-morph` don't redraw the step type's settings after (`skipIslandMorph`).
  - `save()` (the form) sends pending settings first (`flushAllSettings()`).

**Undo/redo:**

- `UndoRedoManager` is the buttons over `Funnel.history` (50 entries), see above.
- An explicit save (Publish/Activate) and Revert clear the history. That's when soft deletes become final.

**Canvas interactions:**

- **Adding.** Clicking a `button.add-step` sets `addEl` (`.here`) and opens `#add/<group>`. Picking a tile
  performs an `add` at `addButtonPosition( addEl )`: `before-<id>` / `before-group-<id>` → `{ before }`,
  `add-to-group-after-<id>` → `{ after }`, `in-branch-<branch>` / `end-inside-<id>` → the branch's end,
  `end-funnel` → the end of main.
- **Drag and drop:**
  - jQuery UI `.step-branch` sortables (`connectWith: '.step-branch'`, `cancel: '.locked'`,
    `distance: 100`);
  - picker tiles are draggables connected to them; `receive` performs an `add` where the tile was dropped;
  - `stop` performs a `move` to `domPosition( item )` (after the previous `.sortable-item`, else before the
    next, else the branch's start); dragging an OR group moves each of its triggers. The redraw undoes whatever
    the drag did to the DOM.
- **Keyboard:**
  - Ctrl/Cmd+C copies `{ copy: editingId }`;
  - Ctrl/Cmd+V pastes the copied step at `targetAdd` with a `duplicate` operation, it can be from another flow;
  - Ctrl/Cmd+M toggles move mode (the next `add-step` click performs a `move` there).
- **Delete** (`deleteStep( id )`):
  - `deleting` = the step plus every `.step[data-id]` inside its `.sortable-item`;
  - **blocked** if `step_references` shows a user not in `deleting` → the `cantDeleteUsedSteps()` modal;
  - branch logic or benchmarks with inner steps get a danger confirm;
  - then fade out and perform a `delete`; if the server refuses, the step comes back with its state.
- **Duplicate** (`duplicateStep( id )`):
  - asks whether to include sub-steps (`include_branches`);
  - runs the type's `onDuplicate()` for extra choices (e.g. `__duplicate_email`), which become `options`;
  - performs a `duplicate` operation; the store puts a copy of the step right after it, the server's copies of
    the steps in its branches arrive with the reply. Undo deletes them all, redo restores them with their IDs.
- **Lock / unlock** → `lock` / `unlock` operations; the card's lock shows right away.
- **Undo/redo** record what the other takes as the inverse of the operations as they were applied.
- **Publish / Activate / Deactivate:**
  - if `#step-flow .has-errors` exists, a confirm ("Some of your steps have issues") comes first;
  - then `confirmDeletedSteps( pending_deletes, … )`: one row per deleted step with waiting contacts,
    cancel or move (with a target select);
  - then `save( { quiet: false, moreData: _commit | _activate, _deleted_steps } )`.
- **Flow settings panel** (description, campaigns, `{this_flow.*}` replacements) saves through **REST**
  `FunnelsStore.patch()`, not the ajax save.
- **Other:** the "More" menu (export, share, reports, add contacts through `FunnelScheduler`, screenshot
  and full-screen modes, shortcuts, "Revert changes" = `_uncommit`), and the tour.

### Step type JS: `Funnel.registerStepType( type, handler )` (since 4.9)

Everything is optional, and what a type leaves out comes from the server:

- **`settings( step, update )`** returns the type's settings UI (MakeEl), drawn in the panel's
  `.step-type-settings` (where PHP `settings()` prints, the rest of the island stays: the header, the custom name
  field, and the `settings/before|after` hooks). `update( { setting: value } )` = `updateStepMeta()`, so it's saved
  with the panel's fields. Only the step being edited is mounted (`mountSettings()`, from `stepSettingsCallbacks()`
  and `drawPanels()`, into elements without `data-mounted`), like `onActive`; it's drawn again when a new island
  replaces it, or after undo. Leaving the step (`unmountSettings()`) puts the server's island back, so the other
  panels keep the PHP fields the form's saves post. A type with `settings()` doesn't
  get `onActive`. Re-render with MakeEl's `morph` (return one element or a `Fragment` from a function child).
- **`title( step )`**, **`validate( step )`** (`[ { code, message } ]`) and **`branches( step )`**
  (`[ { key, name, classes } ]`) are shown on the canvas only while the step is new or has unsent settings changes
  (`previewOf()`); once saved the server's win. An empty or undefined title keeps the last one.
- **`defaults`**: settings steps added in the editor start with, until the server's arrive.
- **`onDuplicate( step )`**: extra fields to post when duplicating, or a promise of them.

Ported so far (`funnel-steps.js`): `delay_timer` (settings; its title is `delay_preview`, which its JS writes, so
the JS is the source), `apply_tag`, `remove_tag`, `tag_applied`, `tag_removed` (settings, title), `if_else`
(settings, title, branches), `add_to_flow` (settings, title), and titles only for `send_email` (its panel is still
drawn by `onActive`), `create_task`, `admin_notification`, `web_form` (TinyMCE and the FormBuilder need the
`step-active` event), `email_confirmed` and `task_completed` (from the task steps in the store). For types with a
JS title, `saveSettings()` copies the panel's pending fields into the step's settings so the title can use them.
Left to the server: `account_created` (role names), `optin_status_changed` (preference names), and the types
without a generated title.

The premium branching types (`split_path`, `weighted_distribution`, `split_test`) register `branches()` in core,
because their branches are defined by the premium base classes Pro's classes extend, so the canvas shows branches
added, renamed, or removed in Pro's branch editors right away. `StepTitles.branches` is checked against their
`get_canvas_data()` with the `branches` cases in `titles.json`.

**Add-on compatibility (checked for 4.9):** Pro, SMS, EDD, WooCommerce, Pipeline, and Logic only use
`registerStepCallbacks()`, `updateStepMeta()`, `getActiveStep()`, `Funnel.steps`, the `step-active` event,
`Funnel.save()` (Pro's A/B test and branch deletes, EDD's and Woo's legacy triggers, which now wait their turn in
the queue), and the `sortable/labels|classes` hooks (Logic). None change the canvas markup, so none need changes.
Types whose picker is printed by `after_settings()` (`send_email`, SMS's `send_sms`) keep their `onActive` panels. Their titles are in `step-titles.js` (`Groundhogg.StepTitles`, DOM-free), which match
`generate_step_title()`: `tests/js/step-titles.test.js` checks them against cases PHPUnit's `Step_Titles_Tests`
writes to `tests/js/fixtures/titles.json`. Titles that need a name that isn't loaded (a tag, an email, a flow) are
undefined.

### Step type JS: `Funnel.registerStepCallbacks( type, callbacks )`

The older API, still supported for types that don't have `registerStepType()` settings; it **replaces** any
earlier registration for the type. Only two callbacks are ever called:

- **`onActive( step )`** runs when the step's panel opens (hash `#<id>`), after every explicit save, and
  after an undo/redo restore. `step` is `{ ID, data, meta, export, is_starting, is_entry,
  updateStep( meta ) }`. Typical work:
  - find the placeholder PHP rendered in `#settings-<ID>` (IDs are `step_<ID>_<setting>`);
  - replace or morph it with a MakeEl component;
  - mark the container `.ignore-morph`;
  - write values with `updateStep( { key: value } )` (= `Funnel.updateStepMeta( meta, ID )`, merged into
    `step.meta` and `metaUpdates`, then autosaved).
- **`onDuplicate( step, resolve, reject )`**: resolve with extra form fields to post.

To reuse another type's callbacks, spread them: `{ ...Funnel.stepCallbacks.if_else }`. Pro does this for
`logic_loop`, `logic_skip`, `logic_stop` and `logic_jump`.

**Core registrations (`funnel-steps.js`):**

| type | UI |
|---|---|
| `delay_timer` | morphs `#step_<ID>_delay_timer_settings`; every change also writes `delay_preview: delayTimerName( settings )` |
| `apply_tag`, `remove_tag`, `tag_applied`, `tag_removed` | tag picker in `#step_<ID>_tags` → `{ tags }` |
| `add_to_flow` | flow and step pickers → `{ funnel_id, step_id }` |
| `if_else` | `ContactFilters` into `#step_<ID>_include_filters` / `_exclude_filters` → `{ include_filters, include_display }` and `{ exclude_filters, exclude_display }` |
| `send_email` | email picker, block editor in a modal frame, preview → `{ email_id }`; `onDuplicate` asks to copy the email → `{ __duplicate_email: true }` |

- **Older pattern**, still in use: `FunnelSteps.init()` listens to the `step-active` document event and
  switches on `step_type` (`apply_note`, `admin_notification`, `create_task` use TinyMCE; `web_form` uses
  `WebForm.edit()` / `onMount()` with FormBuilder; `form_fill` has an upgrade button). Pro's
  `renderComponent( id, step, { edit, onMount } )` is the same idea.
- **Document events available to step JS:** `step-active` (after `onActive`), `gh-init-pickers`,
  `auto-save`, `saved`, `draw-logic-lines`. Useful editor methods: `Funnel.getActiveStep()`, `getStep( id )`,
  `save()`, `saveQuietly()`, `stepSettingsCallbacks()`.
- **The three ways a step writes settings** (5):
  1. `updateStepMeta()` → `metaUpdates`;
  2. PHP inputs named `steps[ID][setting]` (autosaved on change);
  3. hidden inputs injected by JS, then `Funnel.save()`. Pro's `split_test` injects `start_new_test` /
     `winner` this way.
- **Trigger frequency UI**: `TriggerFrequencySettings()` renders into `#trigger-frequency-settings-<ID>` on
  `step-active` for benchmarks, writing the `_trigger_frequency*` meta (see 17).

**Pro registrations** (groundhogg-pro `funnel-steps.js`):

- `apply_owner`, `post_published`;
- `split_path` and `weighted_distribution`: branch editors with 8-character random branch keys and A/B/C
  labels; deleting a branch updates meta and then saves, so the server re-renders the columns;
- `logic_*` (spread from `if_else`);
- `split_test` (start test / declare winner);
- legacy `step-active` components: `http_post`, webhook listener (polls with `Funnel.save( true )` every
  10s), `edit_meta`, `field_timer`, custom activity;
- **`evergreen_sequence` has no JS**; it uses PHP inputs and `settings_should_ignore_morph()` returns
  false.

### DOM contract (server-rendered, relied on by the JS)

**The canvas is drawn in JS** (since 4.9): `flow-store.js` (`Groundhogg.FlowStore`) holds the steps and the
server's canvas data, and `flow-canvas.js` (`Groundhogg.FlowCanvas`) draws them with MakeEl. Both are DOM-free
UMD modules so Node can load them for tests. The markup is the same the server rendered with `sortable_item()`
(below), so drag and drop, the old save path, the logic lines and step type JS keep working.

- **Canvas data** (`Funnel_Step::get_canvas_data( $step )`, per step): `layout` (`default`, `benchmark`,
  `branches`, `stop`, `html`), `branch_logic`, `title` (`get_title()`), `classes` (the filtered card classes,
  `get_sortable_classes()`), `notes` (HTML), `entry`, `conversion`, `locked`, `errors [{code, message}]`,
  `labels` (`labels()` output), `extra_labels` (`groundhogg/steps/sortable/labels`), `inside` (the
  `…/sortable/inside` actions), plus `name`/`icon`/`svg` for unregistered types. `Branch_Logic` adds
  `branches [{id, name, classes}]`.
- **Step types that override `sortable_item()`** (`uses_custom_sortable_item()`, by reflection) get `layout:
  html` and their server markup in `html`, inserted as-is. Pro's `logic_jump`/`logic_loop` overrides only call
  the parent, and Pro's `logic_stop` is drawn by the `stop` layout; the premium stubs say so.
- Steps in a branch a logic step no longer has are drawn in an extra `.split-branch.unused-branch` column
  instead of being hidden.
- `Groundhogg.drawFlow( { steps, canvas }, { reporting } )` draws a flow that isn't being edited; the reporting
  page (`funnel-flow-preview.php`) uses it with the `.step-reporting` stat slots.
- In debug mode the editor compares `store.levelDifferences()` (a JS port of `set_step_levels()`) with the
  server's values and warns in the console.

**Canvas markup** (what `Step::sortable_item()` → element `sortable_item()` rendered, and the JS reproduces):

- **`div.sortable-item.<group>`**, containing:
  - `button.add-step.add-action` (before),
  - `.flow-line`,
  - the **`.step`** card,
  - `.flow-line`.
- **`.step`** card:
  - Classes: `#step-<ID>.step.<group>.<type>.<status>`, plus `has-errors` and each error code as a class,
    `has-changes`, `locked`, `invalid`, `passthru`, `entry`. Filter: `groundhogg/steps/sortable/classes`.
  - Attributes: `data-id`, `data-type`, `data-group`, `data-level`.
  - Contents:
    - hidden `step_ids[]` and `steps[ID][branch]`;
    - `.step-labels` (notes, entry, conversion, lock icons, plus the `groundhogg/steps/sortable/labels` and
      `…/sortable/inside` actions);
    - `.actions` with `button.lock-step`, `.unlock-step`, `.duplicate-step`, `.delete-step`;
    - `.hndle` with `.hndle-icon`, `.step-title`, `.step-name`;
    - `.step-reporting` (only on the reporting page).
- **Benchmarks** render `.sortable-item.benchmarks[.starting]` → `.step-branch.benchmarks[data-branch]`,
  with `span.benchmark-or` between siblings. Each one is `.sortable-item.benchmark` containing its inner
  `.step-branch[data-branch=<ID>]`.
- **Branch logic** renders `.sortable-item.logic.branch-logic` → `.step` → `.step-branches` →
  `.split-branch.<classes>` for each branch. Each branch has `.logic-line.line-above`
  (`#branch-name-indicator-<key>`), `#branch-<key>.step-branch[data-branch=<key>]` with an
  `add-step` inside, and `.logic-line.line-below`.
- `#step-sortable.step-branch[data-branch=main]` is the root.

**Settings panels are drawn in JS** (since 4.9), `drawPanels()` → `SettingsPanel( step )`, with the same markup
`html_v2()` renders, which stays for the server. The step type's part, its **island**, is HTML from
`Funnel_Step::get_settings_island( $step )`: `{ html, before_notes, ignore_morph }`, everything inside
`.step-edit.panels` plus what `before_step_notes()` prints. The page gets every island (`Funnel.islands`), a form
save sends them all again, and an operations reply only the ones for steps it added or changed
(`Flow_Operations::get_touched()`). An island is only redrawn when a new one arrives (`drawnIslands`), and not while
its settings have unsent changes, and focused fields aren't touched. The trigger flags are rendered as the same
named checkboxes, so every save posts them. After undo/redo (`forcePanels`) everything is redrawn and
`stepSettingsCallbacks()` runs again.

**Settings markup** (`html_v2()`, and `SettingsPanel()`):

- `#settings-<ID>.step.<group>.<type>.settings` contains `.step-warnings` (from `validate_settings()`
  errors) and `.step-flex`.
- `.step-flex` holds:
  - **`.step-edit.panels`**, which gets `ignore-morph` when `settings_should_ignore_morph()` is true, the
    default. Inside it: `.main-step-settings-panel .custom-settings` (the `step_title` input when custom
    names are forced or no title is generated, then `settings( $step )`), and the
    `groundhogg/steps/{type}/settings/before|after` and `groundhogg/steps/settings/before|after` actions.
  - **`.step-notes`**: for benchmarks, a panel with `is_entry` / `can_passthru` (not on starting ones),
    `is_conversion`, and `#trigger-frequency-settings-<ID>`; then the `step_notes` textarea.
- **`settings_should_ignore_morph()`**:
  - **true** (default): the panel of the step being edited isn't re-rendered by quiet saves. Use this when
    JS owns the UI.
  - **false**: the PHP output must refresh after each autosave (e.g. `admin_notification`,
    `form-integration`, `evergreen_sequence`, several Pro types).
- `.editing` is on both `#step-<ID>` and `#settings-<ID>` for the open step.

**Logic lines:**

- `drawLogicLines()` (CSS-border lines) draws the benchmark pills ("Start the flow when…" / "Until…"),
  the end-of-flow button, OR lines, branch lines, and the jump, loop and skip arrows for `.step.loop`,
  `.logic_loop`, `.skip`, `.logic_skip`, `.logic_jump`, `.logic_stop` and `.timer_skip`. Targets come from
  `Funnel.steps[…].meta.next` / `.timers`.
- Exported as `Groundhogg.drawLogicLines`, which the reporting page reuses.
- `logic-lines.js` (SVG) is loaded but unused.

**Validation display** is server-side only: `validate_settings()` → `$step->add_error()` → the card gets
`.has-errors` (a ⚠️ via CSS) and the panel shows `.step-warnings`. JS only checks `.has-errors` before
Publish/Activate.

---

## 10. Adding a step type (add-on checklist)

1. **PHP class** extending `Groundhogg\Steps\Actions\Action`, `Benchmarks\Benchmark`, `Logic\Logic` or
   `Logic\Branch_Logic`:
   - `get_name()`, `get_type()` (unique, snake_case), `get_description()`, `get_sub_group()`,
     `get_icon()`;
   - `settings( $step )`: render placeholders with `id = $this->setting_id_prefix( 'x' )`, or plain inputs
     named `$this->setting_name_prefix( 'x' )`;
   - `get_settings_schema()`: `sanitize` + `default` for every setting; `initial` for sensible new-step
     values; `if_undefined` for PHP checkboxes; `import` if a strict sanitizer would reject IDs during
     import;
   - `validate_settings()`, `generate_step_title()` (keep it in sync with any JS title logic);
   - `run()` (actions) and `calc_run_time()` (timers);
   - benchmarks: `get_complete_hooks()`, `setup()`, `get_the_contact()`, `can_complete_step()`;
   - logic: `get_logic_action()`; branch logic also `get_branches()`, `get_branch_name()`,
     `matches_branch_conditions()`;
   - `get_step_reference_settings()` if any setting holds step IDs;
   - `import()` / `export()` / `post_import()` if settings reference things that change IDs across sites
     (tags, emails, steps).
2. **Register** in `register_funnel_steps( $manager )` (the `Extension` base hooks `groundhogg/steps/init`)
   with `$manager->add_step( new My_Step() )`.
3. **JS** (only if the settings need more than plain inputs):
   - register a script depending on `groundhogg-admin-funnel-editor` in `register_admin_scripts()`;
   - enqueue it in `funnel_editor_scripts()`;
   - in it, `Funnel.registerStepCallbacks( 'my_type', { onActive( { ID, meta, updateStep } ) { … } } )`.
4. **Icon**: an SVG; `get_icon()` can point anywhere.
5. **Abilities** (optional, to make it buildable and editable by agents), on
   `groundhogg/abilities/register_step_types` (after registration, and after every add-on's
   `register_schema_extensions`, see section 16):

   ```php
   add_action( 'groundhogg/abilities/register_step_types', function () {
   	Step_Type_Schema::extend( 'my_type', $json_schema_for_settings, $branch_keys, $resolver, $exporter );
   } );
   ```

   See 11 for each parameter.
6. **Tests**: see 18. The `groundhogg-extension-template` repo still shows the older `save()` +
   `get_posted_data()` pattern with no schema and no JS. Prefer the schema approach.

---

## 11. Flow abilities (Abilities API / MCP)

The abilities are in `includes/abilities/funnels/`, registered in `includes/abilities/abilities.php`. Each
is an `Ability` subclass with `NAME`, `CAPABILITY` and annotations. `WP_Ability::execute()` validates input
**and output** against the schemas with WordPress's validator.

| ability | what it does |
|---|---|
| `list-flows` | list/search; `expand: [ 'steps' ]` gives a flat step list |
| `list-step-types` | step types, `buildable` flag, per-type `settings_schema`, `branch_keys` |
| `create-flow` | builds a new **inactive** flow from a step tree, all or nothing (deletes the flow on failure) |
| `get-flow` | one flow as a tree in create-flow's shape (below) |
| `edit-flow` | add/update/move/delete operations, all or nothing (below) |
| `publish-flow-changes` | the editor's Update: `commit( choices )`, `last_updated`, fires `groundhogg/api/funnel/updated` |
| `discard-flow-changes` | `uncommit()` |
| `activate-flow` | handles steps deleted while inactive (`deleted_steps` choices), then activates; refuses a flow with no non-deleted steps |
| `deactivate-flow` | needs `pending_changes: publish \| discard` when there are unpublished changes (the editor asks the same) |
| `add-to-flow` | enqueue a contact or a segment (background) at a step |
| `simulate-flow` / `live-simulate-flow` | Simulator, dry or real (15) |

### `Step_Type_Schema` (`includes/abilities/schemas/step-type-schema.php`)

- `BUILTIN_TYPES`: the core types the abilities can build (the legacy `form_fill` and `email_opened` are
  excluded). `supported_types()` = built-ins + extensions.
- `settings_schema( $type )`: JSON Schema for a type's `settings` input.
- `branch_keys( $type, $settings )`: `if_else` → `yes` / `no`; extensions → their array or callable.
- **`resolve_settings( $type, $settings, $declared )`** turns ability-shaped input into stored meta:
  - tags → IDs (`parse_tag_list`, never creating tags);
  - `send_email.email_id` must exist;
  - `reply_in_thread` / `tasks` → real step IDs through `resolve_step_reference( $local_id, $declared,
    $expected_type )`;
  - `add_to_flow.flow_id` → `funnel_id`;
  - `web_form` fields → nested `form`;
  - `if_else.include_condition` → `include_filters` via `Segment_Schema::to_filters()`, **or** stored
    `include_filters` accepted as they are (both at once is an error).

  It returns `[ 'settings' => …, 'deferred_settings' => … ]`. Deferred settings (e.g. `task_completed.tasks`,
  whose sanitizer needs the final `is_before()` order) are written after `set_step_levels()`.
- **`export_settings( Step $step )`** is the reverse, for `get-flow`:
  - it narrows meta to the schema's keys;
  - undoes the shape changes (`form` → flat fields, `funnel_id` → `flow_id`);
  - returns `if_else` filters **raw**, because editor-built conditions often can't be expressed as a
    segment;
  - returns references as real step IDs.

  It returns null for types that aren't buildable.
- **`extend( $type, $settings_schema, $branch_keys = [], ?callable $resolver = null, ?callable $exporter = null )`**:
  - `$type` must already be registered with the step manager and not already supported;
  - `$branch_keys`: `string[]`, or `callable( array $settings ): string[]` for per-instance branches;
    called with `[]` for type-level discovery;
  - `$resolver`: `callable( array $settings, array $declared ): array|WP_Error`, returning `[ 'settings' ]`
    and optionally `'deferred_settings'`;
  - `$exporter`: `callable( array $settings, Step $step ): array`. It gets the meta narrowed to the schema's
    keys. Omit it if the stored meta is already in the settings shape.
- **Pro's opt-ins** (groundhogg-pro `Abilities\Step_Types`, registered on `groundhogg/steps/init` priority
  20, to be moved to `groundhogg/abilities/register_step_types`): its actions and benchmarks, plus `split_path`, `split_test`, `weighted_distribution` and
  `evergreen_sequence`.
  - `split_path`'s resolver accepts a branch's `include_condition` / `exclude_condition` **or** its stored
    `include_filters` / `exclude_filters`.
  - Deliberately **not** opted in: `loop`, `skip`, `logic_jump`, `logic_loop`, `logic_skip` (they hold
    step references that would need forward references), and `timer_skip`. Also not exposed:
    `split_test.winner` and `evergreen_sequence.timers`.

### Schema rules for abilities

These are enforced by `class-abilities-schema-tests.php`:

- **Every sub-schema needs a `type`.** WordPress's validator requires it and doesn't resolve `$ref`.
- **Recursive nodes:** inline the top level in full, and put `type: object` next to each recursive `$ref`.
- **Use `definitions`, not `$defs`.** `wp_prepare_json_schema_for_client()` (the REST abilities endpoint
  and the WP AI client) keeps only draft-04 keywords. It keeps `definitions` and `$ref` but drops `$defs`,
  which would leave dangling `$ref`s. The MCP adapter passes schemas through as they are.
- `Step_Tree_Builder::node_schema()` / `node_out_schema()` and `Get_Flow::node_schema()` /
  `flow_schema()` are the shared node schemas.

### `Step_Tree_Builder`

- `new Step_Tree_Builder( $funnel, $declared = [] )`.
- `build( $nodes, $branch = 'main' )`:
  - creates `inactive` steps with `Funnel::add_step()`, with meta from `resolve_settings()`;
  - recurses into `branches` (`"<ID>-<key>"`);
  - validates types and branch keys, and refuses duplicate local IDs.
- `declare( $key, $id, $type )` makes a step referable by a key.
- Then call `set_step_levels()` and `apply_deferred_settings()`.
- New steps start from the type's schema `initial` values (`get_initial_settings()`), with the given
  settings on top, like steps added in the editor.

### `get-flow`

- Input: `flow_id`, `view: draft | live`. Draft is the default, read with `while_editing()`.
- Each node:
  - `id`, `type`, `type_name`, `group`, `title`;
  - `buildable`, `settings` (via `export_settings()`);
  - `is_locked`, `waiting_contacts` (waiting + paused);
  - **`referenced_by`**;
  - benchmark flags;
  - `has_unpublished_changes` (draft view of an active flow);
  - `branches` (if/else always lists `yes` and `no`; a benchmark's inner branch is `then`).
- Flow level:
  - `has_unpublished_changes`;
  - **`revision`** = `md5( json( snapshot() ) )`: the draft's fingerprint;
  - `unplaced_steps` (steps whose branch owner is missing);
  - `admin_link`.
- `Get_Flow::describe( $funnel, $view )` and `Get_Flow::revision()` are static so other abilities reuse
  them.

### `edit-flow`

- **Input:** `flow_id`, `operations[]`, `expected_revision?`, `dry_run?`.
- **Operations:**
  - `add { at, steps }`: nodes like `create-flow`'s, including branches and local `id`s.
  - `update { step, title?, settings?, is_entry?, is_conversion?, can_passthru? }`:
    - settings are a **partial** update merged into `export_settings()`;
    - a `branches` map is merged **branch by branch**, and `null` removes a branch (refused if the branch
      has steps);
    - a `*_condition` replaces only that side's stored `*_filters`, top level or per branch;
    - the whole merged settings are then resolved and saved with `update_settings()`;
    - the title only changes when `title` is given (it's re-applied after `after_save()`'s generated title).
  - `move { step, at }`: carries the step's branches; can't move into its own branches.
  - `delete { step }`: soft; cascades; checks `can_delete_steps()`.
  - `duplicate { step, at?, id?, include_branches?, options? }`: the copy goes right after the step unless `at`
    says where; the step can be in another flow the user can edit (pasting), which needs `at`; `Step::duplicate()` does the copying, with the step types' own `duplicate()` handlers, which read
    choices from the request: `include_branches: false` sets `__ignore_inner`, and `options` (like
    `__duplicate_email`) are set in `$_POST` while it runs. The copies in its branches are placed in the model
    after it. `id` is a local id for later operations.
  - `lock { step }` / `unlock { step }`: written directly to `is_locked`, not staged.
- **Positions:** `{ after }`, `{ before }`, `{ branch_of, branch, position: start | end }` (branch keys
  are the type's, `then` for benchmarks), or `{ branch: 'main', position }`.
- **Step references** in ops and settings are a real ID, or the local id of a step added earlier in the
  same call. Every existing or added step is declared by its real ID.
- **Mechanics:**
  - it runs in `while_editing()`;
  - `backup()` first;
  - it keeps a model of each branch's ordered IDs;
  - it writes `branch` + per-branch order (`write_layout()`), including before deletes, because the
    elements' cascades read the stored branches;
  - then `set_step_levels()` and the deferred settings;
  - on any error (or exception), `rollback()` and return `operations[i] (op): message` with
    `data.operation`;
  - `dry_run` applies, describes, then rolls back.
- **Output:** `{ dry_run, flow (get-flow shape), added[], deleted_steps[ { id, title, type, waiting_contacts } ] }`.
- **On active flows** every change is staged and needs `publish-flow-changes`. Waiting contacts at deleted
  steps are decided at publish/activate time, not here.

### `Flow_Changes` helpers (`includes/abilities/funnels/flow-changes.php`)

- `deleted_steps_schema()` / `outcomes_schema()`;
- `get_choices()`: validates that each step is really deleted and that a move target is a non-deleted
  action;
- `outcomes()`: computed before resolving;
- `has_unpublished_changes()`, `publish()`, `remove_deleted_steps()`.

---

## 12. Import, export, templates, duplicating

- **Export:**
  - `Funnel::export()` = `get_as_array()` with the real steps. Each step includes `export` =
    `element->export( [], $step )`.
  - Types serialize portable data there: `apply_tag` / `tag_applied` export tag **names**; `send_email`
    embeds the whole email.
  - Download link: `Funnel::export_url()` → `managed_page_url( 'funnels/export/<encrypted id>/' )`, served
    as `funnel-<slug>.funnel` by `includes/rewrites.php`.
  - `legacy_export()` is the old `{ title, steps: [ { title, group, order, type, meta, args } ] }` format.
- **`Funnel::import( $data )`:**
  1. data with a top-level `title` → `legacy_import()`;
  2. otherwise `do_action_ref_array( 'groundhogg/funnel/import/before', [ &$data ] )`. This also turns on
     `Step::is_importing()`, so schema `import` sanitizers apply;
  3. create an inactive flow;
  4. for each step: create from `data` (inactive, old `branch` values kept), `update_meta( meta )`,
     `element->import( export, $step )`, and store `imported_step_id` = the old ID;
  5. a second pass calls `post_import()` on every step:
     - element `post_import()` (e.g. `send_email` remaps `reply_in_thread`; link and form steps fix links;
       Pro jump, loop and skip remap `next`);
     - then `Step::post_import()` rewrites child `branch` values from old to new IDs (benchmark branches,
       and each `get_branches()` key);
  6. delete the `imported_step_id` meta;
  7. `groundhogg/funnel/import/after`.

  `set_step_levels()` is **not** called; the imported order and levels are kept.
- **Templates** come from a remote library (`Library::get_funnel_templates()`, `library.groundhogg.io`,
  CDN first), filtered to registered step types. They're imported through `Funnels_Page::import_funnel()`.
  The `groundhogg/templates/funnels` filter that `Extension::register_funnel_templates()` hooks is never
  applied (dead code).
- **Duplicate a flow** (`process_duplicate`): export → import → "Copy of …". `send_email` import creates
  **new copies of the emails**.
- **Duplicate a step:** `Step::duplicate( $overrides )` → element `duplicate( $new, $original )`
  (benchmark and branch logic copy sub-steps; `send_email` optionally copies the email when
  `__duplicate_email` is set).

---

## 13. REST API and the admin flows page

- **`gh/v4/funnels`**: the base CRUD, meta, relationships, duplicate and merge routes, plus:
  - `POST /funnels/import`: `Funnel::import( json )`.
  - `POST /funnels/{id}/commit`: `update_meta` + `commit()` (which returns false if the flow isn't
    active). Not used by the editor.
  - `POST /funnels/{id}/start` (`start_flows`): add one contact (`edit_contact`) or a query (sync with a
    `limit`, else a background task, optionally scheduled); `step_id` defaults to the first action.
  - `GET /funnels/form-integration?type=`.
  - `update_single` also syncs `campaigns`.
- **`gh/v4/steps`**: CRUD, plus `POST /steps/html`, which renders `html_v2()` for a `Temp_Step`.
- **Permissions**: `view_funnels` / `export_funnels` / template site for reading, `edit_funnels`,
  `add_funnels`, `delete_funnels`, `import_funnels`, `schedule_flows`, `start_flows`.
- **Admin page actions** (`Funnels_Page`):
  - add: templates, import (a `.funnel` / `.json` file or pasted JSON, possibly an array of flows),
    start from scratch;
  - duplicate, export (bulk JSON download), activate / deactivate / archive / restore through
    `update_funnels_status()` (skipping locked flows), delete (archived only);
  - campaigns add / remove.
- **Table views:** active / inactive / archived. The `active_contacts` column counts WAITING events. Row
  actions: Edit, Report (active only), Duplicate, Export, …

---

## 14. Reporting, contact filters, replacements

- **Reports** key off `events.step_id` with `event_type = FUNNEL`: `table_funnel_stats` (complete from
  history, waiting from the queue, per step), `chart_funnel_breakdown` (benchmarks), conversion stats
  (steps marked `is_conversion`), email stats by step.
- The flow **reporting page** reuses the editor's canvas markup: `.step-reporting` stats come from
  `reporting.js` `renderFunnelFlowReport()`, then `Groundhogg.drawLogicLines()`.
- **`Step::has_history()`** (events or activity rows) decides archive vs hard delete at commit.
- **Contact filter `funnel_history`**: `funnel_id`, `step_id`, `status`, date range. Used for report
  drill-downs, table links, and segments. `funnel_pending` covers queued events.
- **Replacements:** `{this_flow.<key>}` reads flow meta `replacements[key]` from `the_funnel()`, which is
  set in `Step::run()`, in form rendering, and from the editor referer. The editor syncs a `this_flow`
  group into `Groundhogg.replacements` client-side.

---

## 15. Simulator

`admin/funnels/simulator.php` (`Simulator::simulate( Step $from, Contact, bool $dry )`), reached through
ajax `gh_flow_simulate` (the editor's ▶ button, `simulator.js`), WP-CLI, and the `simulate-flow` /
`live-simulate-flow` abilities.

It walks the flow **in memory** from a step:

- **Benchmarks:** the next step after it.
- **Logic:** `get_logic_action()`, logging the branch; loops are capped at 2 visits per step.
- **Timers:** the time is advanced with `get_run_time()` and the step isn't run.
- **Other actions:** on a dry run nothing executes (elements log through `Simulator::log()`). On a live run
  a real event row is created and the element's `run()` is called.
- It stops at a non-passthru benchmark and returns the trigger options to pick from.

**Live vs draft.** It walks whatever `Funnel::get_steps()` returns (see 7), so the version depends on editing mode:
the editor's ▶ button (editor referer) traces the draft, and the abilities take `view: "live"` (default, the
published flow) or `"draft"` (staged changes merged, like `get-flow`'s `view`), which wraps the run in
`while_editing()`. `live-simulate-flow` with `view: "draft"` really executes unpublished steps and records events
against them. A start step that isn't in the chosen version is refused (`groundhogg_step_not_in_view`) rather than
giving an empty trace. Static state (`$flow`, `$options`, `$event_args`) is reset at the start of each run.

It does **not** use the queue, `can_complete()`, or trigger frequency. `Simulator::is_simulating()` and
`get_event_arg()` let elements behave accordingly (`Step::is_simulating()` is a stub that always returns
false).

---

## 16. Hooks index

**Registration and scripts:**

- `groundhogg/steps/init` (action, `$manager`): register step types.
- Abilities, each fired once and in this order from inside WordPress's own registry actions (see the
  `Abilities` class docblock): `groundhogg/abilities/register_schema_extensions` (`Segment_Schema::extend()`,
  `Contact_Schema::extend()`), `groundhogg/abilities/register_step_types` (`Step_Type_Schema::extend()`, after every
  schema extension), `groundhogg/abilities/register_categories` and `groundhogg/abilities/register_abilities`
  (`Abilities::add_category()` / `add_ability()`, after core's own). Guide: `abilities-registration.md`.
- `groundhogg/scripts/after_register_admin_scripts`, `groundhogg/admin/funnels/editor_scripts`,
  `groundhogg_enqueue_step_type_assets`.

**Rendering:**

- `groundhogg/steps/sortable/classes` (filter), `…/sortable/labels`, `…/sortable/inside`,
  `groundhogg/steps/{type}/sortable/inside`.
- `groundhogg/steps/{type}/settings/before|after`, `groundhogg/steps/settings/before|after`.

**Saving:**

- `groundhogg/steps/save/after` (`$element, $step`).
- `groundhogg/admin/funnel/updated` (editor save), `groundhogg/api/funnel/updated` (abilities / REST),
  `groundhogg/api/funnel/created`.

**Runtime:**

- `groundhogg/step/enqueue` (filter by ref, may swap the step), `groundhogg/steps/enqueue` (deprecated),
  `groundhogg/step/enqueue/event`.
- `groundhogg/steps/run/do_step`, `groundhogg/steps/{type}/run/before|after`, `groundhogg/steps/run/result`.
- `groundhogg/step/next_step`, `groundhogg/step/next_action`, `groundhogg/step/next_sibling`,
  `groundhogg/step/can_complete`, `groundhogg/step/is_locked`, `groundhogg/step/is_timer`.
- `groundhogg/event/run/before|after`, `groundhogg/event/{queued|cancelled|failed|skipped|pause|complete}`,
  `groundhogg/event/maybe_register_step_callbacks`, `groundhogg/event/post_setup/step_class`.
- `groundhogg/event_queue/before_process|after_process`, `groundhogg/queue/event_store/claim_events` (by
  ref), `groundhogg/event_queue/max_events`, `groundhogg/event_queue/max_time_limit`,
  `groundhogg/event_queue/is_enabled`.

**Import/export and data:**

- `groundhogg/funnel/import/before` (by ref), `groundhogg/funnel/import/after`,
  `groundhogg/steps/post_import`, `groundhogg/funnel/export`.
- `groundhogg/step/get_as_array`, `groundhogg/step/post_setup`.

---

## 17. Known bugs and gotchas

**Fixed on 2026-09-26** (tests in `class-step-trigger-frequency-tests.php` and
`class-flow-fixes-tests.php`), recorded here in case older code or add-ons still assume the old behaviour:

- **Trigger frequency was never enforced.** `check_trigger_frequency()` read `_frequency_rule` instead of
  `_trigger_frequency` (since `e722b0943`), and its "within x days" filter used a `timestamp` column
  instead of `time`.
- **REST `POST /funnels/{id}/commit` always answered 400**, because `Funnel::commit()` returned nothing.
  It now returns true, or false for an inactive flow.
- **`Funnel::import()`** passed the last step's data to `groundhogg/funnel/import/after` instead of the
  import payload, and deleted `imported_step_id` meta site-wide.
- **Import mode leaked:** `Step::is_importing()` was `did_action()`, so it stayed on for the rest of the
  request. It's now scoped with `start_importing()` / `stop_importing()`.
- **`Funnel::has_changes()` ignored soft-deleted steps** while editing.
- **Editor save:**
  - it lost `metaUpdates` when a save failed or was refused;
  - a failed request left `saving` stuck on, which stopped autosave;
  - every quiet save re-enabled "Publish Changes" even with nothing to publish.
- **A contact enqueued at a logic step with nothing after it** hit a `TypeError`; `_enqueue()` now returns
  false.
- **Abilities-built steps didn't get schema `initial` values.**
- **Step warning notices rendered an empty `id`** (missing `echo`). They now carry `data-error-code`.
- **Jump arrows:** `drawLogicLines` checked `.logic_jump:not(.broken)`, but Pro's jump error class is
  `loop_broken`.
- **`groundhogg/admin/funnels/editor_scripts`** now passes the `Funnel`, which
  `Extension::funnel_editor_scripts( $funnel )` expected.
- **Pro's `groundhogg-pro-funnel-steps`** now declares a dependency on `groundhogg-admin-funnel-editor`.

**By design, or still open:**

- **Generated titles replace custom titles on every save** unless `force_custom_step_names()`. The editor
  saves every step, so a custom title on a delay or tag step (say, from an agent) is replaced the next time
  anyone saves in the editor.
- **Legacy types without a schema** (`form_fill`, `link_click`, `email_opened`) store meta without
  `sanitize_meta()` sanitization; only their own `save()` sanitizes.
- **Schema `initial` is still not applied** by import, duplicate or `Funnel::add_step()` (import and
  duplicate carry the source's values).
- **`Benchmark::complete()`'s `process_events_after_complete` path is dead**: `$contacts_to_process` is
  never filled.
- **The trigger-frequency `step-active` listener** is registered outside the `is_editor` guard. That's
  harmless, because `step-active` only fires in the editor.

**Gotchas:**

- Always `while_editing()` when reading or writing a draft outside the editor. Without it, `get_steps()`
  on an active flow silently ignores new steps and staged changes.
- `get_setting()` returns the default for **any** falsy stored value (0, '', false). Store explicit
  non-falsy values when that matters.
- The schema `default` replaces **empty** values when saving; a legitimately empty value can't be stored
  when a non-empty default exists.
- `pre_save()` with an explicit `$settings` array resets `branch` to `main` if it's missing. Use
  `Step::update_settings()` for partial updates.
- Staged changes: `update()` on an active step doesn't touch the row; read with `merge_changes()` or in
  editing mode. Also, `add_step()` on an active flow stages the new step's meta.
- `restore()` doesn't remove added meta keys; use `backup()` / `rollback()` for exact rollback.
- Branch keys may contain `-`.
- A contact is in one place per flow: enqueueing skips their other waiting events in that flow.
- `get_first_action_id()` and the order-based helpers aren't branch-aware.
- **Dev site:** `DISABLE_WP_CRON` is true; Chrome caches edited JS (`?ver` doesn't change); use the
  `wordpress-groundhogg-dev` abilities MCP for test data, not WP-CLI.

---

## 18. Testing

- **PHPUnit, core:** `tests/phpunit/unit-tests/`. Flow-related suites:
  - `includes/classes/class-step-tests.php` (deletes, archive, waiting contacts);
  - `class-funnel-editing-tests.php` (editing mode, snapshot / restore, backup / rollback);
  - `class-step-save-tests.php`;
  - `class-step-references-tests.php`;
  - `class-step-trigger-frequency-tests.php`, `class-flow-fixes-tests.php` (commit, import, logic enqueue,
    initial settings, warnings);
  - `includes/abilities/class-{step-tree-builder,get-flow,edit-flow,flow-changes,flow-operations}-tests.php`;
  - `includes/class-abilities-schema-tests.php` (every ability schema is typed and its `$ref`s resolve,
    also after `wp_prepare_json_schema_for_client()`).
- **PHPUnit, Pro:** `tests/phpunit/unit-tests/test-flow-logic-abilities.php` (Multi Branch round trip,
  branch merge, references). Pro's bootstrap finds core through `GROUNDHOGG_CORE_DIR`; point it at a core
  worktree to test both together.
- **JS:** `npm run test:js` (Node's built-in runner, nothing to install) runs `tests/js/*.test.js`:
  - `flow-canvas.test.js` draws each fixture flow with the JS canvas and compares it, token by token, with the
    server's `step_flow()` markup;
  - `flow-store.test.js` checks the JS levels match `set_step_levels()`.
  - `flow-operations.test.js` covers the store's operations and their inverses, temporary IDs, the save queue,
    and the history.
  - The fixtures in `tests/js/fixtures/canvas/` are written by the PHPUnit `Flow_Canvas_Tests` with
    `GH_UPDATE_JS_FIXTURES=1`; without it that test fails when the server's markup no longer matches them.
- **Local test environment** (WP test lib, polyfills, php-test.ini, the `WP_PHP_BINARY` quirk, fresh-DB
  checks): see the Claude memory note `phpunit-test-workflow`. Always check a fresh database as well as a
  reused one; installer and role caching bugs only show on the first run.
- **End to end:**
  - build flows with `create-flow` over the dev MCP;
  - edit with `edit-flow`;
  - check in the editor at `https://groundhogg.dev/wp-admin/admin.php?page=gh_funnels&action=edit&funnel=<id>`;
    reading the DOM (`#step-sortable .step[data-id]`, `Funnel.steps`, `Funnel.step_references`) is more
    reliable than screenshots;
  - publish through the editor or the ability;
  - verify with `get-flow` (`revision` is a quick "did anything change" check) and `query-table`
    (`steps`, `event_queue`, `events`).
