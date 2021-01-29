import React from 'react'
import IconButton from '@material-ui/core/IconButton'
import EditIcon from '@material-ui/icons/Edit'
import DeleteIcon from '@material-ui/icons/Delete'
import DeleteForeverIcon from '@material-ui/icons/DeleteForever'
import FileCopyIcon from '@material-ui/icons/FileCopy'
import Tooltip from '@material-ui/core/Tooltip/Tooltip'

/**
 *
 *
 * @param actions
 * @returns {*}
 */
export default ({
  onEdit,
  onTrash,
  onDelete,
  onDuplicate,
}) => {

  const actions = []

  if (typeof onEdit === 'function') {
    actions.push(
      <Tooltip title={ 'Edit' }>
        <IconButton aria-label={ 'Edit item' } onClick={onEdit}>
          <EditIcon/>
        </IconButton>
      </Tooltip>,
    )
  }

  if (typeof onDuplicate === 'function') {
    actions.push(
      <Tooltip title={ 'Delete' }>
        <IconButton aria-label={ 'Delete item' } onClick={onDuplicate}>
          <FileCopyIcon/>
        </IconButton>
      </Tooltip>,
    )
  }

  if (typeof onTrash === 'function') {
    actions.push(
      <Tooltip title={ 'Trash' }>
        <IconButton color={'secondary'} aria-label={ 'Trash item' } onClick={onTrash}>
          <DeleteIcon/>
        </IconButton>
      </Tooltip>,
    )
  }

  if (typeof onDelete === 'function') {
    actions.push(
      <Tooltip title={ 'Delete' }>
        <IconButton color={'secondary'} aria-label={ 'Delete item' } onClick={onDelete}>
          <DeleteForeverIcon/>
        </IconButton>
      </Tooltip>,
    )
  }

  return actions
}
