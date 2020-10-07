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
	Contacts
} from './pages';
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
		container: Dashboard,
		name: 'Dashboard',
		path: '/',
		icon : DashboardIcon
	} );

	pages.push( {
		container: Reports,
		name: 'Reports',
		path: '/reports',
		icon : BarChartIcon
	} );

	pages.push( {
		container: Emails,
		name: 'Emails',
		path: '/emails',
		icon : EmailIcon
	} );

	pages.push( {
		container: Tags,
		name: 'Tags',
		path: '/tags',
		icon : LayersIcon
	} );

	pages.push( {
		container: Contacts,
		name: 'Contacts',
		path: '/contacts',
		icon : PeopleIcon
	} );

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
				<page.container
					params={ params }
					path={ url }
					pathMatch={ page.path }
					query={ query }
				/>
			</Suspense>
		);
	}
}
