import { useDispatch } from '@wordpress/data'
import { useState } from '@wordpress/element'
import { useKeyPress } from 'utils/index'
import { objEquals } from 'utils/core'

/**
 * Handle the table quick edit
 *
 * @param id
 * @param data
 * @param exitQuickEdit
 * @returns {*}
 * @constructor
 */
export default ({ id, data, meta, store, children }) => {

  // const classes = useStyles()
  const { updateItem } = useDispatch(store)
  const [editing, setEditing] = useState(false)
  const [tempState, setTempState] = useState({
    data: { ...data },
    meta: { ...meta }
  })

  // Exit quick edit
  useKeyPress(27, null, () => {
    setEditing(false)
  })

  /**
   * Handle pressing enter in the tag name
   *
   * @param keyCode
   */
  const handleOnKeydown = ({ keyCode }) => {
    switch (keyCode) {
      case 13:
        commitChanges()
    }
  }

  // Set the temp data of the state
  const setTempData = (data) => {
    setTempState({
      ...tempState,
      data: { ...tempState.data, ...data }
    })
  }

  // Set the temp data of the state
  const setTempMeta = (data) => {
    setTempState({
      ...tempState,
      meta: { ...tempState.meta, ...data }
    })
  }

  /**
   * Commit the changes
   */
  const commitChanges = () => {

    const origState = {
      data: { ...data },
      meta: { ...meta }
    }

    if (!objEquals(origState, tempState)) {
      updateItem(id, tempState)
    }

    setEditing(false)
  }

  return (
    <>
      {children}
    </>
  )
}