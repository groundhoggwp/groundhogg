import React, { useState } from 'react'
import PropTypes from 'prop-types';
import { Link, NavLink } from 'react-router-dom'
import { FaIcon } from '../basic-components'
import { connect } from 'react-redux'
import { expandSidebar, collapseSidebar } from '../../actions/sidebarActions'

import './style.scss'

const SideBar = ({ routes, status, expandSidebar, collapseSidebar }) => {

  const toggleStatus = () => {
    status === 'expanded' ? collapseSidebar() : expandSidebar()
  }

  return (
    <div className={ 'groundhogg-sidebar ' + status }>
      <Nav routes={ routes }/>
      <ExpandControl
        onClick={toggleStatus}
        status={status}
      />
    </div>
  )

}

SideBar.propTypes = {
  routes: PropTypes.arrayOf(PropTypes.object).isRequired,
  status: PropTypes.string,
  expandSidebar: PropTypes.func.isRequired,
  collapseSidebar: PropTypes.func.isRequired
}

const mapStateToProps = state => ({
  status: state.sideBar.status
})

const ExpandControl = ({onClick, status}) => {
  return (
    <div className={ 'expand-control' } onClick={onClick}>
      { status === 'collapsed' ? <span className={ 'nav-icon' }>
          <FaIcon
            classes={ [
              'arrow-circle-right'
            ] }
          />
        </span> :
        <><span className={ 'nav-icon' }>
          <FaIcon
            classes={ [
              'arrow-circle-left'
            ] }
          />
        </span>
        <span className={ 'nav-text' }>
          { 'Collapse' }
        </span></> }
    </div>
  )
}

const Nav = ({ routes }) => {

  return (
    <nav className={ 'groundhogg-siderbar-nav' }>
      { routes && routes.map(route => <NavItem route={ route }/>) }
    </nav>
  )
}

const NavItem = ({ route }) => {

  const {
    path,
    icon,
    title,
    exact,
  } = route

  return (
    <NavLink
      className={ 'nav-item' }
      activeClassName={ 'active' }
      exact={ exact }
      to={ path }
    >
        <span className={ 'nav-icon' }>
          <FaIcon
            classes={ [
              icon,
            ] }
          />
        </span>
      <span className={ 'nav-text' }>
          { title }
        </span>
    </NavLink>
  )

}

export default connect(mapStateToProps, { expandSidebar, collapseSidebar })(SideBar)