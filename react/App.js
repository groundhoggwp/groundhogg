import React from 'react'
import axios from 'axios'
import { HashRouter } from 'react-router-dom'
import SideBar from './components/SideBar/SideBar'
import { Provider } from 'react-redux'
import store from './store'

import './app.scss'

axios.defaults.headers.common['X-WP-Nonce'] = groundhogg.nonces.rest

const routes = [
  {
    path: '/',
    icon: 'lightbulb-o',
    title: 'Welcome',
    capabilities: [],
    exact: true,
    render: () => <div>home!</div>,
  },
  {
    path: '/contacts',
    icon: 'user',
    title: 'Contacts',
    capabilities: [],
    exact: true,
    render: () => <div>home!</div>,
  },
  {
    path: '/emails',
    icon: 'envelope-o',
    title: 'Emails',
    capabilities: [],
    exact: true,
    render: () => <div>home!</div>,
  },
]

export function App () {
  return (
    <Provider store={store}>
      <div className="groundhogg groundhogg-app">
        <HashRouter>
          <SideBar routes={ routes }/>
        </HashRouter>
      </div>
    </Provider>
  )
}