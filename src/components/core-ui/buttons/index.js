import React from 'react'
import Grid from '@material-ui/core/Grid'
import Button from '@material-ui/core/Button'
import ButtonGroup from '@material-ui/core/ButtonGroup'
import ArrowDropDownIcon from '@material-ui/icons/ArrowDropDown'
import MenuItem from '@material-ui/core/MenuItem'
import { useRef, useState } from '@wordpress/element'
import Menu from '@material-ui/core/Menu'

/**
 * Render a button with a dropdown component
 *
 * @param props
 * @returns {*}
 * @constructor
 */
export function ButtonWithDropDown (props) {

  const { onClick, onMenuSelect, button, menuOptions } = props

  const [anchorEl, setAnchorEl] = React.useState(null)

  const handleClick = (event) => {
    setAnchorEl(event.currentTarget)
  }

  const handleMenuItemClick = (event, index) => {
    onMenuSelect(index)
  }

  const handleClose = (event) => {
    setAnchorEl(null)
  }

  return (
    <>
      <ButtonGroup size="small" aria-label="split button">
        <Button onClick={ onClick }>{ button }</Button>
        <Button
          aria-controls={ open ? 'split-button-menu' : undefined }
          aria-expanded={ open ? 'true' : undefined }
          aria-label={ 'select action' }
          aria-haspopup="menu"
          onClick={ handleClick }
        >
          <ArrowDropDownIcon/>
        </Button>
      </ButtonGroup>
      <Menu
        anchorEl={ anchorEl }
        keepMounted
        open={ Boolean(anchorEl) }
        onClose={ handleClose }
      >
        { menuOptions.map(option => {
          return <MenuItem onClick={ (e) => handleMenuItemClick(e,
            option.key) }>{ option.render }</MenuItem>
        }) }
      </Menu>
    </>
  )
}