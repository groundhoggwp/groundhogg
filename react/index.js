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

// const {
//   current_app,
// } = groundhogg

// const current_app = 'contacts';

function renderApp (app) {

  console.debug(app)

  if (apps[app]) {

    console.debug({ rendering: app })

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

renderApp('contacts')

console.debug('hello? Is anybody out there!')
