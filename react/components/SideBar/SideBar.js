import React, { useState } from 'react'
import { Link, NavLink } from 'react-router-dom'
import { FaIcon } from '../basic-components'

import './style.scss'

export const SideBar = ({ routes, expanded }) => {

  return (
    <div className={ 'groundhogg-sidebar ' + ( expanded ? 'expanded' : '' ) }>
      <Nav routes={ routes } />
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

