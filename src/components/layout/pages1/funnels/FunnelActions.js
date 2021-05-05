import React from 'react'
import Button from '@material-ui/core/Button'
import Menu from '@material-ui/core/Menu'
import MenuItem from '@material-ui/core/MenuItem'
import { useState } from '@wordpress/element'

export default ({ id }) => {
  const [anchorEl, setAnchorEl] = useState(null)

  const handleClick = (event) => {
    setAnchorEl(event.currentTarget)
  }

  const handleClose = () => {
    setAnchorEl(null)
  }

  return (
    <div>
      <Button aria-controls="simple-menu" aria-haspopup="true" onClick={handleClick}>
        {'Actions'}
      </Button>
      <Menu
        id={'funnel-actions'}
        anchorEl={anchorEl}
        keepMounted
        open={Boolean(anchorEl)}
        onClose={handleClose}
      >
        <MenuItem onClick={handleClose}>{'Reporting'}</MenuItem>
        <MenuItem onClick={handleClose}>{'Share'}</MenuItem>
        <MenuItem onClick={handleClose}>{'Export'}</MenuItem>
      </Menu>
    </div>
  )
}