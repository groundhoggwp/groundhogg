Registering Custom Columns
=======

You can register a custom column to any table within Groundhogg with the following filters. Note: Because these columns interface with Groundhogg's REST API, you may need to register the custom data with our schema as well in order for it to be available on the front-end. used to modify the way data is retreived or displayed in Groundhogg.

Internal @TODO: Confirm that we're actually implementing JSON schema via custom REST API endpoints. Cursory review in discovery leads me to assume we are not.

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

addFilter(
	'groundhogg_custom_columns',
	'gh-example/custom-column',
	( contactTableData ) => {
		if ( contactTableData.endpoint !== 'contact' ) {
			return contactTableData;
		}

		contactTableData.headers = [
			...contactTableData.headers,
			{
				label: 'NPS',
				key: 'nps',
			},
		];

		if (
			! contactTableData.items ||
			! contactTableData.items.data ||
			! contactTableData.items.data.length
		) {
			return contactTableData;
		}

		const newRows = contactTableData.rows.map( ( row, index ) => {
			const contact = contactTableData.items.data[ index ];
			const newRow = [
				...row,
				// We are assuming that contact metadata exists with the nps key.
				// An overly-simplified example, as starts are 0-5, and NPS is -100 to 100.
				{
					display: (
						<Rating name="read-only" value={ Number(
								contact.meta.nps
							) } readOnly />
					),
					value: contact.meta.nps,
				},
			];
			return newRow;
		} );

		contactTableData.rows = newRows;

		return contactTableData;
	}
);

/**	Alternative approach */
Groundhogg.addTableColumn( 'contact', {
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