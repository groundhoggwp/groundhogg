Navigation Registration
=======

Register Navigation Items and their corresponding views as components.

## Extending TopBar Panels

TopBar navigation panels can be added, removed, or modified by hooking into `groundhogg_navigation`.  For example:

```js
/**
 * External dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { Component, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

class Panel extends Component {

	onSave() {
	}

	renderPanel() {
		return ...;
	}

	render() {
		const { label } = this.props;

		return (
			<Fragment>
				<SectionHeader title={title} />
				<div className="groundhogg-navigation">
					{this.renderPanel()}
				</div>
			</Fragment>
		);
	}
}

addFilter( 'groundhogg_navigation', 'gh-example/custom-navigation', navigation => {
	return [
		...navigation,
		{
			name: 'groundhogg_custom_nav_item',
			priority: 10,
			label: __( 'Advanced Integrations', 'groundhogg' ),
			component: Panel,
		},
	];
} );

/* Alternatively, a higher-level API may be used. */
if (window.groundhogg) {
        groundhogg.registerNavItem( {
            name: 'groundhogg_custom_nav_item',
			priority: 10,
			label: __( 'Advanced Integrations', 'groundhogg' ),
			component: Panel,
        } );
}
```

Each settings has the following properties:

- `name` (string): The slug of the setting to be updated.
- `priority` (integer): The priority in which the setting is rendered. Rendered in ascending order.
- `label` (string): The label used to describe and displayed next to the setting.
- `component` (React component): The component that should be rendered for the panel..
