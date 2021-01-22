
/**
 * External dependencies
 */
import '@wordpress/notices';
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PageLayout } from './components/layout';
import FunnelEditor from 'components/layout/pages/funnels/FunnelEditor'

import {
	withCurrentUserHydration,
	withSettingsHydration
} from './data';
// import { Reports } from 'components/layout/pages'
import { EmailEditor } from 'components/layout/pages/emails/email-editor'
import { ReportsPage } from 'components/layout/pages/reporting'

const componentMap = {
	full : PageLayout,
	gh_funnels : FunnelEditor,
	gh_reporting: ReportsPage,
	gh_emails: EmailEditor,
}

const appRoot = document.getElementById( 'gh-react-app-root' );

const settingsGroup = 'gh_admin';
const hydrateUser = window.Groundhogg.user;

let HydratedPageLayout = withSettingsHydration(
	settingsGroup,
	window.Groundhogg.preloadSettings
)( componentMap[ window.Groundhogg.params.page ] );

if ( hydrateUser ) {
	HydratedPageLayout = withCurrentUserHydration( hydrateUser )(
		HydratedPageLayout
	);
}

render( <HydratedPageLayout />, appRoot );