import React, { useState } from 'react'
import PropTypes from 'prop-types';
import { Link, NavLink } from 'react-router-dom'
import { FaIcon } from '../basic-components'
import { connect } from 'react-redux'
import { expandSidebar, collapseSidebar } from '../../actions/sidebarActions'

import './style.scss'

const {
  bigG,
  logoBlack,
} = groundhogg.assets

const SideBar = ({ routes, status, expandSidebar, collapseSidebar }) => {

  const toggleStatus = () => {
    status === 'expanded' ? collapseSidebar() : expandSidebar()
  }

  return (
    <div className={ 'groundhogg-sidebar ' + status }>
      <div className={'logo'}>
        <img src={bigG} alt={'big-g'} />
      </div>
      <div className={'sidebar-inner'}>
        <ExpandControl
          onClick={toggleStatus}
          status={status}
        />
        <Nav routes={ routes }/>
      </div>
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

  const icon = status === 'collapsed' ? 'bars' : 'times'

  return (
    <div className={ 'expand-control' } onClick={onClick}>
      <span className={ 'expand-icon' }>
        <FaIcon
          classes={ [
            icon
          ] }
        />
      </span>
    </div>
  )
}

const Nav = ({ routes }) => {

  return (
    <nav className={ 'groundhogg-siderbar-nav' }>
      { routes && routes.map( (route, index ) => <NavItem key={index} route={ route }/>) }
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