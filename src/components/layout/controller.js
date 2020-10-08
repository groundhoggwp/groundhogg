/**
 * External dependencies
 */
import { Component, Suspense } from '@wordpress/element';
import { parse } from 'qs';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

import Dashboard from './pages/dashboard';
import Reports from './pages/reports';
import Emails from './pages/emails';
import Tags from './pages/tags';
import Contacts from './pages/contacts';

import { Spinner } from '../../components';

import DashboardIcon from '@material-ui/icons/Dashboard';
import PeopleIcon from '@material-ui/icons/People';
import BarChartIcon from '@material-ui/icons/BarChart';
import LayersIcon from '@material-ui/icons/Layers';
import EmailIcon from '@material-ui/icons/Email';

export const PAGES_FILTER = 'groundhogg_navigation';

export const getPages = () => {
	const pages = [];

	/** @TODO: parse/hydrate PHP-registered nav items for app navigation */

	pages.push( {
		component: Dashboard,
		icon : DashboardIcon,
		label: 'Dashboard',
		name: 'dashboard',
		path: '/',
		priority: 1
	} );

	pages.push( {
		component: Reports,
		icon : BarChartIcon,
		label: 'Reports',
		name: 'reports',
		path: '/reports',
		priority: 10
	} );

	pages.push( {
		component: Emails,
		icon : EmailIcon,
		label: 'Emails',
		name: 'reports',
		path: '/emails',
		priority: 20
	} );

	pages.push( {
		component: Tags,
		icon : LayersIcon,
		label: 'Tags',
		name: 'tags',
		path: '/tags',
		priority: 30
	} );

	pages.push( {
		component: Contacts,
		icon : PeopleIcon,
		label: 'Contacts',
		path: '/contacts',
		name: 'contacts',
		priority: 40
	} );

	pages.sort((a, b) => (a.priority > b.priority) ? 1 : -1)

	return applyFilters( PAGES_FILTER, pages );
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
		const { page, match, location } = this.props;
		const { url, params } = match;
		const query = this.getQuery( location.search );

		return (
			<Suspense fallback={ <Spinner /> }>
				<page.component
					params={ params }
					path={ url }
					pathMatch={ page.path }
					query={ query }
				/>
			</Suspense>
		);
	}
}
