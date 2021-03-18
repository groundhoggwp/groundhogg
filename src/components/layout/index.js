/**
 * External dependencies
 */
import clsx from 'clsx'
import { makeStyles } from '@material-ui/core/styles'
import { compose } from '@wordpress/compose'
import { Component } from '@wordpress/element'
import { withFilters } from '@wordpress/components'
import { BrowserRouter, Route, Switch, Link, useRouteMatch, useParams } from 'react-router-dom'
import { identity } from 'lodash'
import { parse } from 'qs'
import PropTypes from 'prop-types'
import Container from '@material-ui/core/Container'
import Paper from '@material-ui/core/Paper'
import Grid from '@material-ui/core/Grid'
import { ThemeProvider } from '@material-ui/styles';
import { createMuiTheme } from '@material-ui/core/styles';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './index.scss'
import { Controller } from './controller'
import navSections from './nav-sections'
import NavBar from './nav-bar'
import TopBar from './top-bar'
import BreadCrumb from '../core-ui/bread-crumb'
import { SnackbarArea } from './snackbar'
import { withSettingsHydration } from '../../data';
import Page from "../core-ui/page";

import { createTheme } from '../../theme';


const theme = createTheme({
  // direction: settings.direction,
  // responsiveFontSizes: settings.responsiveFontSizes,
  // theme: settings.theme
});



const useStyles = makeStyles((theme) => ({
  root: {
    backgroundColor: theme.palette.background.dark,
    minHeight: '100%',
    paddingTop: theme.spacing(3),
    paddingBottom: theme.spacing(3)
  },
  toolbar: {
    paddingRight: 24, // keep right padding when drawer closed
  },
  appBar: {
    zIndex: theme.zIndex.drawer + 1,
    transition: theme.transitions.create(['width', 'margin'], {
      easing: theme.transitions.easing.sharp,
      duration: theme.transitions.duration.leavingScreen,
    }),
  },
  title: {
    flexGrow: 1,
  },
  appBarSpacer: theme.mixins.toolbar,
  content: {
    flexGrow: 1,
    height: '100vh',
    overflow: 'auto',
    paddingLeft: '265px'
  },
  container: {
    paddingTop: theme.spacing(4),
    paddingBottom: theme.spacing(4),
  },
} ))

export default function PrimaryLayout (props) {
  const classes = useStyles()
  const { children } = props

  return (
    <Page className={ `groundhogg-layout__primary ${ classes.root }` } title="Dashboard">
      <div className={ classes.appBarSpacer }/>
      <Container className={ classes.content } maxWidth="xl">
        { children }
      </Container>
    </Page>
  )
}

const Layout = (props) => {
  const { ...restProps } = props;

    return (
      <div className="groundhogg-layout" style={ { display: 'flex' } }>
        <NavBar { ...restProps } />
        <TopBar { ...restProps } />
        <SnackbarArea />
        <PrimaryLayout>
          {/*<BreadCrumb { ...restProps }/>*/}
          <Controller { ...restProps } />
        </PrimaryLayout>
      </div>
    )
}

Layout.propTypes = {
  isEmbedded: PropTypes.bool,
  page: PropTypes.shape({
    container: PropTypes.oneOfType([
      PropTypes.func,
      PropTypes.object, // Support React.lazy
    ]),
    path: PropTypes.string,
  }).isRequired,
}


export const PAGES_FILTER = 'groundhogg.navigation';
	const pages = applyFilters(
		PAGES_FILTER,
		navSections[0].items
	);

const _PageLayout = ( props ) => {
    return (
      <ThemeProvider theme={theme}>
        <BrowserRouter basename={ window.Groundhogg.preloadSettings.basename }>
          <Switch>
            {
             pages.map((page, index) => {
              return (
                  <Route
                    path={ page.href }
                    key={ index }
                    exact={ '/' === page.href }
                    render={ (props) => (
                      <Layout page={ page } selectedIndex={index} { ...props } />
                    ) }
                  />
              )
            }) }
          </Switch>
        </BrowserRouter>
      </ThemeProvider>
    )
}

export const PageLayout = compose(
  // Use the withFilters HoC so PageLayout is re-rendered when filters are used
  // to add new pages
  withFilters(PAGES_FILTER),
  window.Groundhogg.preloadSettings
    ? withSettingsHydration({
      ...window.Groundhogg.preloadSettings.settings,
    })
    : identity,
)(_PageLayout)
