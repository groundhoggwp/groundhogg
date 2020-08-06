import React from 'react'
import axios from 'axios'
import { HashRouter } from 'react-router-dom'
import { SideBar } from './components/SideBar/SideBar'

axios.defaults.headers.common['X-WP-Nonce'] = groundhogg.nonces.rest

import './app.scss'

const routes = [
  {
    path: "/",
    icon: 'lightbulb-o',
    title: 'Welcome',
    capabilities: [],
    exact: true,
    render: () => <div>home!</div>,
  },
  {
    path: "/contacts",
    icon: 'user',
    title: 'Contacts',
    capabilities: [],
    exact: true,
    render: () => <div>home!</div>,
  },
  {
    path: "/emails",
    icon: 'envelope-o',
    title: 'Emails',
    capabilities: [],
    exact: true,
    render: () => <div>home!</div>,
  }
];

export function App () {
  return (
    <div className="groundhogg groundhogg-app">
      <HashRouter>
        <SideBar routes={routes}/>
      </HashRouter>
    </div>
  )
}