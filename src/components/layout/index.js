/**
 * External dependencies
 */
import clsx from 'clsx'
import { makeStyles } from '@material-ui/core/styles'
import { compose } from '@wordpress/compose'
import { Component } from '@wordpress/element'
import { withFilters } from '@wordpress/components'
import { BrowserRouter, Route, Switch, Link } from 'react-router-dom'
import { identity } from 'lodash'
import { parse } from 'qs'
import PropTypes from 'prop-types'
import Container from '@material-ui/core/Container'
import Paper from '@material-ui/core/Paper'
import Grid from '@material-ui/core/Grid'

/**
 * Internal dependencies
 */
import './style.scss'
import { Controller, getPages, PAGES_FILTER } from './controller'
import TopBar from './top-bar'
import Notices from './notices'
import { withSettingsHydration } from '../../data'

const useStyles = makeStyles((theme) => ( {
  // root: {
  //   display: 'flex',
  // },
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
    <main className={ `groundhogg-layout__primary ${ classes.content }` }>

      <div className={ classes.appBarSpacer }/>
      <Container maxWidth="xlg" className={ classes.container }>
        { children }
      </Container>
    </main>
  )
}

class Layout extends Component {

  getQuery (searchString) {
    if (!searchString) {
      return {}
    }

    const search = searchString.substring(1)
    return parse(search)
  }

  render () {
    const { ...restProps } = this.props
    const { location, page } = this.props
    const query = this.getQuery(location && location.search)

    return (
      <div className="groundhogg-layout" style={ { display: 'flex' } }>
        <TopBar { ...restProps } pages={ getPages() } query={ query } />
        <PrimaryLayout>
          <Controller { ...restProps } query={ query }/>
        </PrimaryLayout>
      </div>
    )
  }
}

Layout.propTypes = {
  isEmbedded: PropTypes.bool,
  page: PropTypes.shape({
    container: PropTypes.oneOfType([
      PropTypes.func,
      PropTypes.object, // Support React.lazy
    ]),
    path: PropTypes.string,
    breadcrumbs: PropTypes.oneOfType([
      PropTypes.func,
      PropTypes.arrayOf(
        PropTypes.oneOfType([
          PropTypes.arrayOf(PropTypes.string),
          PropTypes.string,
        ]),
      ),
    ]).isRequired,
    wpOpenMenu: PropTypes.string,
  }).isRequired,
}

class _PageLayout extends Component {
  render () {
    return (
      <BrowserRouter basename={ window.Groundhogg.preloadSettings.basename }>
        <Switch>
          { getPages().map((page, index) => {
            return (
              <Route
                key={ page.path }
                path={ page.path }
                exact
                render={ (props) => (
                  <Layout page={ page } selectedIndex={index} { ...props } />
                ) }
              />
            )
          }) }
        </Switch>
      </BrowserRouter>
    )
  }
}

export const PageLayout = compose(
  // Use the withFilters HoC so PageLayout is re-rendered when filters are used
  // to add new pages
  withFilters(PAGES_FILTER),
  window.Groundhogg.preloadSettings
    ? withSettingsHydration({
      ...window.Groundhogg.preloadSettings,
    })
    : identity,
)(_PageLayout)
