/**
 * External dependencies
 */
import { Component, Suspense, lazy } from '@wordpress/element';
import { parse } from 'qs';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Spinner } from '../../components';
import { Dashboard } from './pages/dashboard';
import { default as Reports } from './pages/reports';

export const PAGES_FILTER = 'groundhogg_navigation';

export const getPages = () => {
	const pages = [];

	/** @TODO: parse/hydrate PHP-registered nav items for app navigation */

	pages.push( {
		container: Reports,
		path: 'reports',
	} );

	pages.push( {
		container: Dashboard,
		path: '/',
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