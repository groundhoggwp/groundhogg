import React from 'react'
import ReactDOM from 'react-dom'

const apps = {
  contacts: {
    elId: 'groundhogg-app-contacts',
    render: () => import( './contacts' ),
  },
  funnels: {
    elId: 'groundhogg-app-funnels',
    render: () => import( './funnels' ),
  },
  funnelEditor: {
    elId: 'groundhogg-app-funnel-editor',
    render: () => import( './funnelEditor' ),
  },
}

const {
  current_app,
} = groundhogg

function renderApp (app) {

  if (apps[app]) {
    apps[app].render().then(App => {
      ReactDOM.render(
        <React.StrictMode>
          <App/>
        </React.StrictMode>,
        document.getElementById(apps[app].elId),
      )
    })
  }
}

renderApp( current_app )