import React from 'react'
import axios from 'axios'
import { HashRouter, Route, Switch } from 'react-router-dom'
import SideBar from './components/SideBar/SideBar'
import { Provider } from 'react-redux'
import store from './store'

import './app.scss'
import { TopBar } from './components/TopBar/TopBar'
import PageContent from './components/PageContent/PageContent'
import routes from './routes/routes'
import VideoModal from './components/VideoModal/VideoModal'
import * as Qs from 'qs'
import BulkJob from './components/BulkJob/BulkJob'

axios.defaults.headers.common['X-WP-Nonce'] = groundhogg.nonces._wprest

axios.interceptors.request.use(config => {

  config.paramsSerializer = params => {
    // Qs is already included in the Axios package
    return Qs.stringify(params, {
      arrayFormat: "brackets",
      encode: false
    });
  };

  return config;
});

export function App () {
  return (
    <Provider store={store}>
      <div className="groundhogg groundhogg-app">
        <HashRouter>
          <TopBar/>
          <SideBar routes={ routes }/>
          <PageContent>
            <Switch>
              {
                routes.map((route, i) => (
                  <Route
                    key={i}
                    path={route.path}
                    exact={route.exact}
                    children={<route.render/>}
                  />
                ))
              }
            </Switch>
          </PageContent>
        </HashRouter>
      </div>
      <VideoModal/>
      <BulkJob/>
    </Provider>
  )
}