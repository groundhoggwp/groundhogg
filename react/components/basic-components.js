import React from 'react'

export const FaIcon = ({ classes }) => {
  return <i className={ 'fa ' + classes.map(c => 'fa-' + c).join(' ') }></i>
}