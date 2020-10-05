Settings Panels
=======

The settings used to modify the way data is retreived or displayed in Groundhogg.

## Extending Settings

Internally, we're using wp.hooks to register settings, panels, menus and more. Using a lower-level API, settings panels can be added, removed, or modified by hooking into `groundhogg_settings_panels`.  For example:

```js
addFilter( 'groundhogg_settings_panels', 'gh-example/custom-settings-panel', panels => {
      return [
            ...panels,
            {
            panel: 'groundhogg_custom_panel',
            parent: 'groundhogg_settings',
            priority: 100,
            label: __( 'Custom Panel', 'groundhogg' ),
            description: __( 'This panel, it is custom, it is a custom panel.', 'groundhogg' ),
            },
      ];
} );
```

The benefit to this approach is how closely it mirrors PHP patterns. This lower-level API makes it extremely accessible to PHP developers who are used to WordPress hook patterns. At a higher-level API, we'll have a local registry method available for setting registration:

```js
if (window.Groundhogg) {
      Groundhogg.registerSettingsPanel( {
            panel: 'groundhogg_custom_panel',
            parent: 'groundhogg_settings',
            priority: 100,
            label: __( 'Custom Panel', 'groundhogg' ),
            description: __( 'This panel, it is custom, it is a custom panel.', 'groundhogg' ),
      } );
}
```

Each panel has the following properties:

- `panel` (string): The panel in which the setting should be rendered.
- `parent` (string) (optional): The parent panel under which this panel should be rendered.
- `priority` (integer): The priority in which the setting is rendered. Rendered in ascending order.
- `label` (string): The label used to describe and displayed next to the setting.
- `description` (string): Text displayed beneath the setting.

If third-party developers decide to use PHP hooks instead, the registration pattern is nearly identical.

```php

/* Note: worth consideration to either standardize on namespaced filters, e.g. groundhogg/admin/settings/sections, or global filters. Global for in JS - happy to namespace, but will need further back-compat consideration. */
add_filter( 'groundhogg_settings_panels', function( $panels ) {
	return array_merge( $panels, [
            'panel'       => 'groundhogg_custom_panel', // A PHP callback for rendering their settings,
            'parent'      => 'groundhogg_settings',
            'priority'    => 100,
            'label'       => __( 'Custom Panel', 'groundhogg' ),
            'description' => __( 'This panel, it is custom, it is a custom panel.', 'groundhogg' ),
	];
} );
```