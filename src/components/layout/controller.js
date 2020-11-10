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
import {
	Dashboard,
	Reports,
	Emails,
	Tags,
	Contacts,
	Funnels,
	Settings
} from './pages'

import { Spinner } from '../../components';
import DashboardIcon from '@material-ui/icons/Dashboard';
import PeopleIcon from '@material-ui/icons/People';
import BarChartIcon from '@material-ui/icons/BarChart';
import SettingsIcon from '@material-ui/icons/Settings';
import EmailIcon from '@material-ui/icons/Email';
import LinearScaleIcon from '@material-ui/icons/LinearScale';
import LocalOfferIcon from '@material-ui/icons/LocalOffer';

export const PAGES_FILTER = 'groundhogg.navigation';

export const getPages = () => {
	let pages = [];

	/** @TODO: parse/hydrate PHP-registered nav items for app navigation */

	pages.push( {
		component: Dashboard,
		icon : DashboardIcon,
		label: __( 'Dashboard' ),
		name: 'dashboard',
		path: '/',
		priority: 1
	} );

	pages.push( {
		component: Reports,
		icon : BarChartIcon,
		label: __( 'Reports' ),
		name: 'reports',
		path: '/reports/:routeId',
		link: '/reports/overview',
		priority: 10
	} );

	pages.push( {
		component: Contacts,
		icon : PeopleIcon,
		label: __( 'Contacts' ),
		path: '/contacts',
		link: '/contacts',
		name: 'contacts',
		priority: 20
	} );

	pages.push( {
		component: Tags,
		icon : LocalOfferIcon,
		label: __( 'Tags' ),
		name: 'tags',
		path: '/tags',
		link: '/tags',
		priority: 30
	} );

	pages.push( {
		component: Emails,
		icon : EmailIcon,
		label: __( 'Emails' ),
		name: 'emails',
		path: '/emails',
		link: '/emails',
		priority: 40
	} );

	pages.push( {
		component: Funnels,
		icon : LinearScaleIcon,
		label: __( 'Funnels' ),
		name: 'funnels',
		path: '/funnels',
		link: '/funnels',
		priority: 50
	} );

	pages.push( {
		component: Settings,
		icon : SettingsIcon,
		label: __( 'Settings' ),
		name: 'settings',
		path: '/settings/:routeId',
		link: '/settings/general',
		priority: 60
	} );


	pages = applyFilters(
		PAGES_FILTER,
		pages
	);

	pages.sort((a, b) => (a.priority > b.priority) ? 1 : -1)

	return pages;
};

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
