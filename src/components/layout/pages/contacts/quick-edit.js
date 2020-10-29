import { useDispatch } from '@wordpress/data'
import { TAGS_STORE_NAME } from 'data/tags'
import { useState } from '@wordpress/element'
import { useKeyPress } from 'utils/index'
import Box from '@material-ui/core/Box'
import TextField from '@material-ui/core/TextField/TextField'
import Button from '@material-ui/core/Button'
import { CONTACTS_STORE_NAME } from 'data/contacts'
import { makeStyles } from '@material-ui/core/styles'


// Styling for inputs
const useStyles = makeStyles((theme) => ( {
  firstName: {
    marginRight: theme.spacing(2)
  },
  email: {
    marginTop: theme.spacing(2)
  }
} ))

export default ({ ID, data, exitQuickEdit }) => {

  const classes = useStyles()

  const { updateItem } = useDispatch(CONTACTS_STORE_NAME)
  const [tempState, setTempState] = useState({
    ...data,
  })

  // Exit quick edit
  useKeyPress(27, null, () => {
    exitQuickEdit()
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
      ...atts,
    })
  }

  /**
   * Commit the changes
   */
  const commitChanges = () => {
    updateItem(ID, {
      data: tempState,
    })
    exitQuickEdit()
  }

  return (
    <>
      <Box display={ 'flex' } justifyContent={ 'space-between' }>
        <Box>
          <Box display={ 'flex' } justifyContent={ 'space-between' }>
            <Box className={classes.firstName}>
              <TextField
                autoFocus
                label={ 'First Name' }
                id="first-name"
                fullWidth
                value={ tempState && tempState.first_name }
                onChange={ (e) => handleOnChange(
                  { first_name: e.target.value }) }
                onKeyDown={ handleOnKeydown }
                variant="outlined"
                size="small"
              />
            </Box>
            <Box>
              <TextField
                autoFocus
                label={ 'Last Name' }
                id="last-name"
                fullWidth
                value={ tempState && tempState.last_name }
                onChange={ (e) => handleOnChange(
                  { last_name: e.target.value }) }
                onKeyDown={ handleOnKeydown }
                variant="outlined"
                size="small"
              />
            </Box>
          </Box>
          <Box className={classes.email}>
            <TextField
              autoFocus
              label={ 'Email' }
              id="email"
              fullWidth
              value={ tempState && tempState.email }
              onChange={ (e) => handleOnChange({ email: e.target.value }) }
              onKeyDown={ handleOnKeydown }
              variant="outlined"
              size="small"
            />
          </Box>
        </Box>
        <Box>

        </Box>
      </Box>
      <Box display={ 'flex' } justifyContent={ 'flex-end' }>
        <Button variant="contained" color="primary" onClick={ commitChanges }>
          { 'Save Changes' }
        </Button>
        <Button variant="contained" onClick={ exitQuickEdit }>
          { 'Cancel' }
        </Button>
      </Box>
    </>
  )
}