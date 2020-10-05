/**
 * External dependencies
 */
import '@wordpress/notices';
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PageLayout } from './components/layout';

import {
	withCurrentUserHydration,
	withSettingsHydration,
	SETTINGS_STORE_NAME
} from './data';

const appRoot = document.getElementById( 'root' );
const settingsGroup = SETTINGS_STORE_NAME;
const hydrateUser = window.Groundhogg.user;

let HydratedPageLayout = withSettingsHydration(
	settingsGroup,
	window.Groundhogg
)( PageLayout );

if ( hydrateUser ) {
	HydratedPageLayout = withCurrentUserHydration( hydrateUser )(
		HydratedPageLayout
	);
}

render( <HydratedPageLayout />, appRoot );