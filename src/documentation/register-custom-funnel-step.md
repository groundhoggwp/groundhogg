Registering Custom Funnel Steps
=======

You can register a custom column to any table within Groundhogg with the following filters. Note: Because these columns interface with Groundhogg's REST API, you may need to register the custom data with our schema as well in order for it to be available on the front-end. used to modify the way data is retreived or displayed in Groundhogg.

## Extending Tables

```php
function table_column_register_script() {

	wp_register_script(
		'table_column',
		plugins_url( '/dist/index.js', __FILE__ ),
		[
			'wp-hooks',
			'wp-element',
			'wp-i18n',
		],
		filemtime( dirname( __FILE__ ) . '/dist/index.js' ),
		true
	);

	wp_enqueue_script( 'table_column' );
}

add_action( 'groundhogg_head', 'table_column_register_script' );
```

Settings can be added, removed, or modified by hooking into `groundhogg_custom_columns`.  For example:

```js
/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import Rating from '@material-ui/lab/Rating';

Groundhogg.registerStepType( 'contact', {
	header : { label: 'NPS', key: 'nps' },
	column :  {
		display: (
			<Rating name="read-only" value={ Number(
					contact.meta.nps
				) } readOnly />
		),
		value: contact.meta.nps,
	}
} );
```