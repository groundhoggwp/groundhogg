import React from 'react'

const {
  bigG,
  logoBlack
} = groundhogg.assets;

import './style.scss'

export const TopBar = () => {

  return (
    <div className={'groundhogg-top-bar'}>
      <div className={'logo'}>
        <img src={logoBlack} className={'logo-black'}/>
      </div>
      <div className={'search-and-quick-add'}>
        {'search and quick add'}
      </div>
      <div className={'topbar-actions'}>
        { 'top bar actions' }
      </div>
    </div>
  )

}