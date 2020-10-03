/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { Component } from '@wordpress/element';
import { withFilters } from '@wordpress/components';
import { BrowserRouter, Route, Switch, Link } from 'react-router-dom';
import { identity } from 'lodash';
import { parse } from 'qs';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { Controller, getPages, PAGES_FILTER } from './controller';
import TopBar from "./top-bar";
import BottomBar from './bottom-bar';
import Notices from './notices';
import { withSettingsHydration } from '../../data';

export class PrimaryLayout extends Component {
	render() {
		const { children } = this.props;
		return (
			<div
				className="groundhogg-layout__primary"
				id="groundhogg-layout__primary"
			>
				<Notices />
				{ children }
			</div>
		);
	}
}

class Layout extends Component {

	getQuery( searchString ) {
		if ( ! searchString ) {
			return {};
		}

		const search = searchString.substring( 1 );
		return parse( search );
	}

	render() {
		const { ...restProps } = this.props;
		const { location, page } = this.props;
		const query = this.getQuery( location && location.search );

		return (
			<div className="groundhogg-layout">
				<TopBar />

				<PrimaryLayout>
					<div className="groundhogg-layout__main">
						<Controller { ...restProps } query={ query } />
					</div>
				</PrimaryLayout>

				<BottomBar />
			</div>
		);
	}
}

Layout.propTypes = {
	isEmbedded: PropTypes.bool,
	page: PropTypes.shape( {
		container: PropTypes.oneOfType( [
			PropTypes.func,
			PropTypes.object, // Support React.lazy
		] ),
		path: PropTypes.string,
		breadcrumbs: PropTypes.oneOfType( [
			PropTypes.func,
			PropTypes.arrayOf(
				PropTypes.oneOfType( [
					PropTypes.arrayOf( PropTypes.string ),
					PropTypes.string,
				] )
			),
		] ).isRequired,
		wpOpenMenu: PropTypes.string,
	} ).isRequired,
};

class _PageLayout extends Component {
	render() {
		return (
			<BrowserRouter basename="/wp-admin/groundhogg">
				<Link to="/">Dashboard</Link> | |
				<Link to="/reports">Reports</Link>
				<Switch>
					{ getPages().map( ( page ) => {
						return (
							<Route
								key={ page.path }
								path={ page.path }
								exact
								render={ ( props ) => (
									<Layout page={ page } { ...props } />
								) }
							/>
						);
					} ) }
				</Switch>
			</BrowserRouter>
		);
	}
}

export const PageLayout = compose(
	// Use the withFilters HoC so PageLayout is re-rendered when filters are used to add new pages
	withFilters( PAGES_FILTER ),
	window.Groundhogg.preloadSettings
		? withSettingsHydration( {
				...window.Groundhogg.preloadSettings,
		  } )
		: identity
)( _PageLayout );