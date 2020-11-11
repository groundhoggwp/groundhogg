import Fab from '@material-ui/core/Fab'
import AddIcon from '@material-ui/icons/Add';
import { useState } from '@wordpress/element';
import Popover from '@material-ui/core/Popover';
import CombinedStepPicker from '../Pickers/CombinedStepPicker';
import Tooltip from '@material-ui/core/Tooltip'
import makeStyles from '@material-ui/core/styles/makeStyles'


const useStyles = makeStyles((theme) => ( {
  topMiddle: {
    position: 'absolute',
    margin: 'auto',
    top: -theme.spacing(8),
    left: 0,
    bottom: 'auto',
    right: 0,
  },
  rightMiddle: {
    position: 'absolute',
    margin: 'auto',
    right: -theme.spacing(8),
    top: 0,
    left: 'auto',
    bottom: 0,
  },
  bottomMiddle: {
    position: 'absolute',
    margin: 'auto',
    bottom: -theme.spacing(8),
    top: 'auto',
    left: 0,
    right: 0,
  },
  bottomRight: {
    position: 'absolute',
    margin: 'auto',
    bottom: -theme.spacing(16),
    top: 'auto',
    right: 0,
  },
  bottomLeft: {
    position: 'absolute',
    margin: 'auto',
    bottom: -theme.spacing(16),
    top: 'auto',
    left: 0,
  },
} ))

export default (props) => {

  const classes = useStyles();

  const { position, openStepBlock, closeStepBlock, anchorEl, setAnchorEl, open, id, toolTipTitle } = props;

  return (
    <>
      <Tooltip title={toolTipTitle}>
        <Fab id={id} className={ classes[ position ] } size={'small'} aria-label="add" onClick={openStepBlock}>
          <AddIcon />
        </Fab>
      </Tooltip>
      <Popover
        id={id + '-popover' }
        open={open}
        anchorEl={anchorEl}
        onClose={closeStepBlock}
        anchorOrigin={{
          vertical: 'center',
          horizontal: 'right',
        }}
        transformOrigin={{
          vertical: 'center',
          horizontal: 'left',
        }}
      >
        <CombinedStepPicker {...props}/>
      </Popover>
    </>
  )

}
