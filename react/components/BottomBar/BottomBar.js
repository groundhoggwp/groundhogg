import React from 'react'
import { connect } from 'react-redux'
import { CSSTransitionGroup } from 'react-transition-group'
import './style.scss'

const BottomBar = ({ show, expandedStatus, children, className }) => {

  const classes = [
    'groundhogg-bottom-bar',
    'sidebar-' + expandedStatus,
    className,
  ].join(' ')

  return (
    <CSSTransitionGroup
      transitionName={'bottomBar'}
      transitionEnterTimeout={5000}
      transitionLeaveTimeout={200}
    >
      { show && <div key={'groundhogg-bottom-bar'} className={ classes }>
        { children }
      </div> }
    </CSSTransitionGroup>
  )
}

export default connect(state => ( {
  expandedStatus: state.sideBar.status,
} ), null)(BottomBar)