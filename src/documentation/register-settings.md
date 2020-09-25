Settings
=======

The settings used to modify the way data is retreived or displayed in Groundhogg.

## Extending Settings

Settings can be added, removed, or modified by hooking into `groundhogg_settings`.  For example:

```js
addFilter( 'groundhogg_settings', 'gh-example/custom-setting', settings => {
	return [
		...settings,
		{
                  name: 'groundhogg_custom_setting',
                  panel: 'custom_panel',
                  priority: 10,
                  label: __( 'Custom setting:', 'groundhogg' ),
                  inputType: 'text',
                  helpText: __( 'Help text describing what the setting does.' ),
                  defaultValue: 'Default value',
		},
	];
} );

/* Alternatively, a higher-level API may be used. */
if (window.groundhogg) {
        groundhogg.registerSetting( {
                name: 'groundhogg_custom_setting',
                panel: 'custom_panel',
                priority: 10,
                label: __( 'Custom setting:', 'groundhogg' ),
                inputType: 'text',
                helpText: __( 'Help text describing what the setting does.' ),
                defaultValue: 'Default value'
        } );
}
```

Each settings has the following properties:

- `name` (string): The slug of the setting to be updated.
- `panel` (string): The panel in which the setting should be rendered.
- `priority` (integer): The priority in which the setting is rendered. Lower is closer to the top of the screen.
- `label` (string): The label used to describe and displayed next to the setting.
- `inputType` (enum: text|checkbox|checkboxGroup|dropdown): The type of input to use.
- `helpText` (string): Text displayed beneath the setting.
- `options` (array): Array of options used for inputs with selectable options.
- `defaultValue` (string|array): Value used when resetting to default settings.

If third-party developers decide to use PHP hooks instead, the registration pattern is nearly identical.

```php
/* Note: worth consideration to either standardize on namespaced filters, e.g. groundhogg/admin/settings/settings, or global filters. Global for in JS - happy to namespace, but will need further back-compat consideration. */
add_filter( 'groundhogg_settings', function( $settings ) {
	return array_merge( $settings , [
        'name'          => 'groundhogg_custom_setting',
        'panel'         => 'custom_panel',
        'priority'      => 10,
        'label'         =>  __( 'Custom setting:', 'groundhogg' ),
        'input_type'    => 'text',
        'help_text'     =>  __( 'Help text describing what the setting does.' ),
        'default_value' => 'Default value',
	];
} );
```