import Fab from '@material-ui/core/Fab'
import AddIcon from '@material-ui/icons/Add'
import { useState } from '@wordpress/element'
import Popover from '@material-ui/core/Popover'
import CombinedStepPicker from '../Pickers/CombinedStepPicker'
import Tooltip from '@material-ui/core/Tooltip'
import makeStyles from '@material-ui/core/styles/makeStyles'

export default ({id, groups, parents, children, position}) => {

  const [anchorEl, setAnchorEl] = useState(null)

  const openPicker = (e) => {
    setAnchorEl(e.currentTarget)
  }

  const closePicker = () => {
    setAnchorEl(null);
  }

  return (
    <>
      <Tooltip title={'Add'}>
        <Fab id={id} style={{
          position: 'absolute',
          top: position.y,
          left: position.x,
        }}
             size={'small'} aria-label="add"
             onClick={openPicker}>
          <AddIcon/>
        </Fab>
      </Tooltip>
      <Popover
        id={id + '-popover'}
        open={Boolean(anchorEl)}
        anchorEl={anchorEl}
        onClose={closePicker}
        anchorOrigin={{
          vertical: 'center',
          horizontal: 'right'
        }}
        transformOrigin={{
          vertical: 'center',
          horizontal: 'left'
        }}
      >
        <CombinedStepPicker
          showGroups={groups}
          parentSteps={parents}
          childSteps={children}
          closePicker={closePicker}
        />
      </Popover>
    </>
  )

}
