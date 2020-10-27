Settings Panels
=======

The settings used to modify the way data is retreived or displayed in Groundhogg.

## Extending Settings

Internally, we're using wp.hooks to register settings, panels, menus and more. Using a lower-level API, settings panels can be added, removed, or modified by hooking into `groundhogg_settings_panels`.  For example:

```js
addFilter( 'groundhogg.settings.tabs', 'gh-example/custom-settings-panel', panels => {
      return [
            ...panels,
            {
                  id: 'groundhogg_custom_panel',
                  title: __( 'Custom Panel', 'groundhogg' ),
                  priority: 100,
            },
      ];
} );
```

The benefit to this approach is how closely it mirrors PHP patterns. This lower-level API makes it extremely accessible to PHP developers who are used to WordPress hook patterns. At a higher-level API, we'll have a local registry method available for setting registration:

```js
if (window.Groundhogg) {
      Groundhogg.registerSettingsPanel( {
            id: 'groundhogg_custom_panel',
            title: __( 'Custom Panel', 'groundhogg' ),
            priority: 100,
      } );
}
```

Each panel has the following properties:

- `panel` (string): The panel in which the setting should be rendered.
- `priority` (integer): The priority in which the setting is rendered. Rendered in ascending order.
- `title` (string): The label used to describe and displayed next to the setting.

If third-party developers decide to use PHP hooks instead, the registration pattern is nearly identical.

```php

/* Note: worth consideration to either standardize on namespaced filters, e.g. groundhogg/admin/settings/sections, or global filters. Global for in JS - happy to namespace, but will need further back-compat consideration. */
add_filter( 'groundhogg/admin/settings/sections', function( $panels ) {
	return array_merge( $panels, [
            'panel'       => 'groundhogg_custom_panel', // A PHP callback for rendering their settings,
            'title'       => __( 'Custom Panel', 'groundhogg' ),
            'priority'    => 100,

	];
} );
```