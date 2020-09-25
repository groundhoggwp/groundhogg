import React from 'react'
import { CSSTransitionGroup } from 'react-transition-group'
import './style.scss'

export const BottomBar = ({ show, expandedStatus, children, className }) => {

  const classes = [
    'groundhogg-bottom-bar',
    'sidebar-' + expandedStatus,
    className,
  ].join(' ')

  return (
    <CSSTransitionGroup
      transitionName={ 'bottomBar' }
      transitionEnterTimeout={ 750 }
      transitionLeaveTimeout={ 500 }
      className={ show && 'bottom-bar-wrap' }
      component={ 'div' }
    >
      { show && <div key={ 'groundhogg-bottom-bar' } className={ classes }>
        { children }
      </div> }
    </CSSTransitionGroup>
  )
}
