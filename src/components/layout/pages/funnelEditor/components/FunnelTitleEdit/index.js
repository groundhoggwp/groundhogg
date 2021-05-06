import { useDispatch } from '@wordpress/data'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import { useState } from '@wordpress/element'
import { useKeyPress, useTempState } from 'utils/index'
import Box from '@material-ui/core/Box'
import TextField from '@material-ui/core/TextField'
import Button from '@material-ui/core/Button'
import Typography from '@material-ui/core/Typography'

/**
 * Handle the table quick edit
 *
 * @param id
 * @param data
 * @param exitQuickEdit
 * @returns {*}
 * @constructor
 */
export default ({ id, data }) => {

  // const classes = useStyles()
  const { updateItem } = useDispatch(FUNNELS_STORE_NAME)
  const [editing, setEditing] = useState(false)
  const { tempState, setTempState, resetTempState } = useTempState( data );

  // Exit quick edit
  useKeyPress(27, null, () => {
    resetTempState()
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

  /**
   * Store the changes in a temp state
   *
   * @param atts
   */
  const handleOnChange = (atts) => {
    setTempState({
      ...tempState,
      ...atts
    })
  }

  /**
   * Commit the changes
   */
  const commitChanges = () => {

    if ( data.title !== tempState.title ){
      updateItem(id, {
        data: tempState
      })
    }
    setEditing(false)
  }

  if (!editing) {
    return (
      <Typography variant="h6" onClick={() => setEditing(true)}>
        <span>{'Now Editing'}</span> <b>{tempState && tempState.title}</b>
      </Typography>
    )
  }

  return (
    <>
      <TextField
        autoFocus
        label={'Funnel Title'}
        id={'funnel-title'}
        fullWidth
        value={tempState && tempState.title}
        onChange={(e) => handleOnChange({ title: e.target.value })}
        onKeyDown={handleOnKeydown}
        onBlur={() => commitChanges()}
        variant="outlined"
        size={'small'}
      />
    </>
  )
}