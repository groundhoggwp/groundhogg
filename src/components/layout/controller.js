/**
 * External dependencies
 */
import { Component, Suspense } from '@wordpress/element';
import { parse } from 'qs';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';


/**
 * Internal dependencies
 */

import navSections from './nav-sections';
import { Spinner } from '../../components';
import DashboardIcon from '@material-ui/icons/Dashboard';
import PeopleIcon from '@material-ui/icons/People';
import BarChartIcon from '@material-ui/icons/BarChart';
import SettingsIcon from '@material-ui/icons/Settings';
import EmailIcon from '@material-ui/icons/Email';
import LinearScaleIcon from '@material-ui/icons/LinearScale';
import LocalOfferIcon from '@material-ui/icons/LocalOffer';
import SettingsInputAntennaSharpIcon from '@material-ui/icons/SettingsInputAntennaSharp';
import BuildIcon from '@material-ui/icons/Build';

export class Controller extends Component {

	getQuery( searchString ) {
		if ( ! searchString ) {
			return {};
		}

		const search = searchString.substring( 1 );
		return parse( search );
	}

	render() {
		const { page, match, location, history } = this.props;
		const { url, params } = match;
		const query = this.getQuery( location.search );

		return (
			<Suspense fallback={ <Spinner /> }>
				<page.component
					params={ params }
					path={ url }
					pathMatch={ page.path }
					query={ query }
					match={match}
					location={location}
					history={history}
				/>
			</Suspense>
		);
	}
}
