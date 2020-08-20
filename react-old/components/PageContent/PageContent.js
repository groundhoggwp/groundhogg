import React from 'react'
import { connect } from 'react-redux'

import './style.scss'

const PageContent = ({children, sidebarStatus}) => {
 return (
   <div className={'page-content ' + sidebarStatus }>
     {children}
   </div>
   )
}

const mapStateToProps = state => ({
  sidebarStatus: state.sideBar.status
})

export default connect(mapStateToProps, null)(PageContent)