import Fab from '@material-ui/core/Fab'
import AddIcon from '@material-ui/icons/Add';
import { useState } from '@wordpress/element';
import Popover from '@material-ui/core/Popover';
import CombinedStepPicker from '../Pickers/CombinedStepPicker';
import Tooltip from '@material-ui/core/Tooltip'

export default (props) => {

  const { className, openStepBlock, closeStepBlock, anchorEl, setAnchorEl, open, id, toolTipTitle } = props;

  return (
    <>
      <Tooltip title={toolTipTitle}>
        <Fab id={id} className={ className } size={'small'} aria-label="add" onClick={openStepBlock}>
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
