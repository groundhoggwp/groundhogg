# Registering abilities, schema extensions and step types

How an add-on (or an agent working on one) adds to Groundhogg's abilities: its own abilities and categories,
additions to the shared schemas, and step types agents can build with `create-flow`.

**Rule of thumb:** register on the four actions core fires, don't pick an `init` priority. Core fires them once
each, in a fixed order, at the moment it builds the abilities registries.

| # | Action | Register here |
|---|---|---|
| 1 | `groundhogg/abilities/register_schema_extensions` | `Segment_Schema::extend()`, `Contact_Schema::extend()`, other additions to shared schemas, settings groups |
| 2 | `groundhogg/abilities/register_step_types` | `Step_Type_Schema::extend()` |
| 3 | `groundhogg/abilities/register_categories` | `Abilities::add_category()` |
| 4 | `groundhogg/abilities/register_abilities` | `Abilities::add_ability()` |

Everything is in `includes/abilities/`. The registry and the actions: `abilities.php`. The shared schemas:
`schemas/`. Examples to copy: groundhogg-pro `includes/abilities/step-types.php`, groundhogg-edd
`includes/segment-schema.php` and `step-type-schema.php`, groundhogg-companies `includes/abilities/abilities.php`.

---

## A complete example

```php
use Groundhogg\Abilities\Abilities;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Abilities\Schemas\Step_Type_Schema;

// In the add-on's own bootstrap (plugins_loaded or later), just add the listeners:

add_action( 'groundhogg/abilities/register_schema_extensions', function () {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	Segment_Schema::extend( 'woocommerce', $json_schema, $to_filters, true );
} );

add_action( 'groundhogg/abilities/register_step_types', function () {
	Step_Type_Schema::extend( 'my_addon_step', $settings_schema, [], $resolver, $exporter );
} );

add_action( 'groundhogg/abilities/register_categories', function () {
	Abilities::add_category( 'my-addon', [
		'label'       => __( 'My Add-on', 'my-addon' ),
		'description' => __( 'Abilities provided by My Add-on.', 'my-addon' ),
	] );
} );

add_action( 'groundhogg/abilities/register_abilities', function () {
	Abilities::add_ability( My_Addon\Abilities\Do_The_Thing::class );
} );
```

Put only the registration inside each callback. Nothing needs to be loaded or built earlier.

---

## When things happen, and why the order matters

WordPress builds its abilities registries **lazily**, the first time anything asks for one (`wp_get_ability()`,
the REST abilities endpoint, an MCP request, ...). That is usually after `init`, but any plugin can trigger it
earlier, so no `init` priority is reliably "before it".

An ability **bakes its input and output schema** from the schema classes at the moment it registers. So
whatever an add-on contributes to `Segment_Schema`, `Contact_Schema` or `Step_Type_Schema` has to be in place
first, and a step type's schema that embeds `Segment_Schema::properties()` has to be built after every
add-on's segment extension is in (otherwise it misses `edd`, `woocommerce`, ... depending on load order).

Core makes that order explicit. The sequence, from `Abilities::register_schemas()` and the registry hooks:

```
(plugins_loaded 0)   core boots; step types register on groundhogg/steps/init
(plugins_loaded 10+) add-ons add their listeners to the four actions
        ...          something asks WordPress for the abilities (or categories) registry
wp_abilities_api_categories_init (priority 0)  -> Abilities::register_schemas():
   groundhogg/abilities/register_schema_extensions     1. shared schema extensions
   groundhogg/abilities/register_step_types            2. step types (sees every extension)
wp_abilities_api_categories_init               -> core categories, then
   groundhogg/abilities/register_categories            3. add-on categories
wp_abilities_api_init                          -> core abilities, then
   groundhogg/abilities/register_abilities             4. add-on abilities
```

WordPress builds the categories registry before the abilities one whichever is asked for, so
`wp_abilities_api_categories_init` is where `register_schemas()` normally runs. It is hooked on
`wp_abilities_api_init` at priority 0 as well, as a safeguard. It only ever runs once, and always before
anything registers. `Abilities::schemas_registered()` tells you whether it has.

Guarantees you can rely on inside the callbacks:

- Every plugin has loaded, including the one the add-on depends on (WooCommerce, EDD, ...). That is why EDD no
  longer waits for a late `init` priority. Still guard with `class_exists()` / `function_exists()` if the add-on
  works without that dependency.
- Every step type is registered with the step manager (needed by `Step_Type_Schema::extend()`).
- In 2, every schema extension from 1, from every add-on, is in.
- In 3 and 4, WordPress is accepting registrations, so `add_category()` and `add_ability()` register
  immediately. In 4, core's own abilities and categories exist already.

Don't:

- Call `Segment_Schema::extend()`, `Step_Type_Schema::extend()` etc. at file load or on `plugins_loaded`. It
  works only if it happens to beat the registry. Use the actions.
- Capture `Segment_Schema::properties()` at file load or in a constructor and reuse it. Call it inside the
  callback, so it includes everything.
- Hook `groundhogg/steps/init` (any priority) to describe step types. That hook is for registering the step
  type itself. Describing it for abilities belongs on `register_step_types`.
- Defer again inside a callback (`add_action( 'init', ..., 1 )`). By then that priority has passed and it
  silently never runs. (This broke groundhogg-wooc's `Segment_Schema` once.)
- Register after the registry has finished. `add_category()` / `add_ability()` report it with
  `_doing_it_wrong()`, but the `extend()` methods can't tell, and the abilities that already registered keep
  the schema they built without your addition.
- Translate strings before `init`. The actions fire once WordPress builds the registry, which is normally `init`
  or later, so `__()` in the callbacks is fine. Don't call the callbacks' code earlier yourself.

---

## 1. Schema extensions (`register_schema_extensions`)

### Segment_Schema: add an audience filter to every ability that takes a segment

`groundhogg/search-contacts`, `send-email-broadcast`, `add-to-flow`, `if_else` and logic step conditions all build
on `Segment_Schema::properties()`. One `extend()` adds a property to all of them.

```php
Segment_Schema::extend(
	'woocommerce',                 // the input property name; must not collide with a built-in or another add-on's
	$json_schema,                  // the full schema fragment for this property, used as-is
	function ( array $input ): array {   // returns Filters-DSL conditions, [] if this property isn't present
		if ( empty( $input['woocommerce']['min_orders'] ) ) {
			return [];
		}
		return [ [ 'type' => 'woocommerce_orders', 'compare' => 'greater_than_or_equal_to', 'value' => absint( $input['woocommerce']['min_orders'] ) ] ];
	},
	true                           // whether this property alone names a deliberate audience (see has_audience())
);
```

- The callback is called on every conversion whether or not the property was passed, so check `$input[ $key ]`.
  Return a `WP_Error` to reject the input.
- The condition `type` must be a filter registered with `Contact_Query` via the
  `groundhogg/contact_query/filters/register` action. `extend()` carries conditions through, it doesn't register
  filter types.
- One `extend()` covers all three conversions (`to_query()`, `to_contact_query()`, `to_filters()`).
- The audience flag matters for abilities that must not act on "everyone" by accident.
- Other hooks, for what `extend()` can't express: `groundhogg/segment_schema/properties`, `.../query`,
  `.../filters`, `.../audience_keys`, and the `.../contact_query` action.

### Contact_Schema: add an `expand` section to the contact abilities

```php
Contact_Schema::extend(
	'ltv',                                       // appears in `expand`; must not collide
	__( 'Lifetime value.', 'my-plugin' ),        // description
	function ( Contact $contact ) { return (float) my_ltv( $contact ); },   // computed only when requested
	'number'                                     // string|integer|number|boolean|object|array
);
```

The callback is only called when the key is in the caller's `expand`. For an `object`, also describe its
properties with the `groundhogg/contact_schema/properties` filter (see groundhogg-companies' `work_details`).
Other hooks: `.../expand_options`, `.../transform`. Any schema using the `Extensible_Schema` trait works the
same way.

### Settings groups

`Settings::add_setting()` registrations are read when an ability runs, so they can be registered any time before
use. `add_group()` is different: `list-settings` bakes the group ids into its schema at registration, so
register groups here.

---

## 2. Step types (`register_step_types`)

A step type is only buildable by `create-flow` / `edit-flow` / `get-flow` once it is opted in. The step type
itself must already be registered with the step manager (`groundhogg/steps/init`). `extend()` refuses, with
`_doing_it_wrong()`, a type that isn't, or one already supported.

```php
Step_Type_Schema::extend(
	'my_type',
	$settings_schema,    // JSON Schema for the step's `settings` input
	$branch_keys,        // [] unless it's a branching logic step; array, or callable( array $settings ): string[]
	$resolver,           // optional: callable( array $settings, array $declared ): array|WP_Error
	$exporter            // optional: callable( array $settings, Step $step ): array
);
```

### The settings schema

- It describes the **ability's** input shape. The step's stored meta may differ.
- Every sub-schema needs a `type`, and use `definitions`, not `$defs`. WordPress's client schema preparation drops
  `$defs` (architecture doc, section 11).
- A condition-style setting should take `Segment_Schema::properties()` (built inside the callback, so it has every
  add-on's extension) **and** the raw stored filters, so a flow built in the editor round-trips:

  ```php
  'include_condition' => array_merge( $condition_schema, [ 'description' => '...' ] ),
  'include_filters'   => array_merge( $filters_schema,   [ 'description' => 'Instead of include_condition: the stored filters, exactly as get-flow returns them.' ] ),
  ```

### Resolver: ability input to stored meta

Turns what the agent passed into what is written with `Step::update_meta()`. Return
`[ 'settings' => [...] ]`, or add `'deferred_settings' => [...]` for a meta key/value map that must be written
only after `Funnel::set_step_levels()` has finalized step order. Return a `WP_Error` to reject. `$declared` is the
local id to `[ 'id', 'type' ]` map of steps created so far; use
`Step_Type_Schema::resolve_step_reference()` for a setting that points at another step in the same flow.
Reject passing both a condition and its raw-filters form.

### Exporter: stored meta back to ability shape, for `get-flow`

`get-flow` keeps only the stored meta keys **named in the settings schema's `properties`**, then calls your
exporter. So:

- If the stored keys differ from the schema's (a schema with `include_condition`, stored as `include_filters`),
  the settings come back **empty** unless you provide an exporter that returns the stored form. Core does this for
  `if_else`. Without one the step looks unconfigured in `get-flow`, even though the meta is stored correctly.
- Everything returned must be accepted again by the resolver (round trip).

### Branching steps

Pass `$branch_keys` as a fixed array, or a callable when the branches come from the step's own settings
(called with `[]` for type-level discovery, so return whatever is always true, e.g. the implicit branch).

### Check it

- `groundhogg/list-step-types` with `types: [ 'my_type' ]` and `expand: [ 'settings_schema' ]` shows what
  agents see (it is large for condition-style types; query the JSON rather than reading it all).
- Build the step with `create-flow`, read it back with `get-flow`, then feed the result to `edit-flow`. All three
  must agree.

---

## 3. Categories and abilities (`register_categories`, `register_abilities`)

```php
add_action( 'groundhogg/abilities/register_categories', function () {
	Abilities::add_category( 'my-addon', [ 'label' => __( 'My Add-on', 'my-addon' ), 'description' => '...' ] );
} );

add_action( 'groundhogg/abilities/register_abilities', function () {
	foreach ( [ List_Things::class, Create_Thing::class ] as $ability ) {
		Abilities::add_ability( $ability );
	}
} );
```

An ability extends `Groundhogg\Abilities\Ability`; constructing it is what calls `wp_register_ability()`.

```php
class Create_Thing extends Ability {

	// Untyped constants: typed class constants are PHP 8.3+, and add-ons target PHP 7.4+
	protected const NAME       = 'groundhogg/create-thing';   // groundhogg/<verb>-<noun>
	protected const CATEGORY   = 'my-addon';
	protected const CAPABILITY = 'edit_things';               // checked by can_execute(); '' means anyone

	protected const READONLY    = false;   // annotations an agent reads: readonly, destructive, idempotent
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {
		return [
			'label'         => __( 'Create Thing', 'my-addon' ),
			'description'   => __( '...', 'my-addon' ),      // written for an agent deciding whether to call it
			'input_schema'  => [ 'type' => 'object', 'additionalProperties' => false, 'properties' => [ ... ] ],
			'output_schema' => [ 'type' => 'object', 'properties' => [ ... ] ],
		];
	}

	public function __invoke( $input ) {
		// return the output, or a WP_Error
	}
}
```

- `WP_Ability::execute()` validates input **and output** against the schemas, so keep them accurate.
- Use `Segment_Schema::properties()` for an audience, and the `Schema` classes (`Contact_Schema::transform()`...)
  for output shapes, so add-on abilities look like core's.
- Choose `CAPABILITY` to match what the same action needs in wp-admin. Don't be more permissive.
- Say in the description what an agent should call first (`see groundhogg/list-things`).

---

## Compatibility

The four actions exist from the core release that added them. On an older core they never fire, and an add-on
that listens only to them registers nothing. The old `Abilities::add_ability()` / `add_category()` calls still
work wherever they were called, but the schema `extend()` methods have no ordering guarantee outside the actions.

---

## Testing

- **Run an ability:** `wp_get_ability( 'groundhogg/create-thing' )->execute( $input )` returns the output or a
  `WP_Error`. Cover the error paths and permission as well as the happy path.
- **In an add-on's PHPUnit suite**, nothing builds the abilities registry, so the actions never fire and the
  schemas you read directly (`Segment_Schema::properties()`) miss your extension. Add this to the add-on's
  `tests/phpunit/bootstrap.php`, after the installers:

  ```php
  tests_add_filter( 'init', [ \Groundhogg\Abilities\Abilities::class, 'register_schemas' ], 99 );
  ```

  A test that calls `wp_get_ability()` builds the registry itself and doesn't need it.
- **Against a real site**, use the abilities MCP: `list-step-types` for step schemas, `get-flow`, and running the
  ability itself. A schema that looks right in a unit test can still be missing an extension in a real request
  if it was built too early.
- **Confirm a test can fail:** move the registration off its action (or break the resolver) and check the test
  goes red.

---

## Checklist

- [ ] Each registration is inside a callback on the matching action, not at file load or on a guessed `init` priority.
- [ ] Callbacks guard on the plugin dependency, not on whether core "has" the API.
- [ ] No registration is deferred again from inside a callback.
- [ ] `Segment_Schema::properties()` is called inside `register_step_types` (or later), not cached earlier.
- [ ] Step types: every sub-schema has a `type`, `definitions` not `$defs`, an exporter if stored keys differ from
      schema keys, and `create-flow` / `get-flow` / `edit-flow` round-trip.
- [ ] Abilities: untyped constants, `groundhogg/<verb>-<noun>` name, capability matches wp-admin, accurate schemas.
- [ ] The add-on's test bootstrap calls `Abilities::register_schemas()` if tests read the schemas directly.
- [ ] Verified once on a real site through the abilities MCP.
