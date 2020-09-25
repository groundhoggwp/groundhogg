import React from 'react'
import { FaIcon } from '../basic-components'

import './style.scss'

export const IconButton = ({icon, onClick, size, variant}) => {

  return <button className={'icon-button ' + variant + ' ' + size } onClick={onClick}>
    <FaIcon
      classes={[
        icon
      ]}
    />
  </button>

}