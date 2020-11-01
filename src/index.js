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
	withSettingsHydration
} from './data';

const appRoot = document.getElementById( 'root' );
const settingsGroup = 'gh_admin';
const hydrateUser = window.Groundhogg.user;

let HydratedPageLayout = withSettingsHydration(
	settingsGroup,
	window.Groundhogg.preloadSettings
)( PageLayout );

if ( hydrateUser ) {
	HydratedPageLayout = withCurrentUserHydration( hydrateUser )(
		HydratedPageLayout
	);
}

render( <HydratedPageLayout />, appRoot );