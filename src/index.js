
/**
 * External dependencies
 */
import '@wordpress/notices';
import { render } from '@wordpress/element';
import { ThemeProvider } from '@material-ui/styles';

/**
 * Internal dependencies
 */
import { PageLayout } from './components/layout';
import { FunnelEditorNew } from 'components/layout/pages/funnelEditor'

import {
	withCurrentUserHydration,
	withSettingsHydration
} from './data';
// import { Reports } from 'components/layout/pages'
import { EmailEditor } from 'components/layout/pages/emails/email-editor'
import { ReportsPage } from 'components/layout/pages/reporting'

import { createTheme } from './theme';


const theme = createTheme({
  // direction: settings.direction,
  // responsiveFontSizes: settings.responsiveFontSizes,
  // theme: settings.theme
});


const componentMap = {
	// full : PageLayout,
	gh_funnels : FunnelEditorNew,
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

render( <ThemeProvider theme={theme}><HydratedPageLayout /></ThemeProvider>, appRoot );
