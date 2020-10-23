import Fab from '@material-ui/core/Fab'
import AddIcon from '@material-ui/icons/Add';
import { useState } from '@wordpress/element';
import Popover from '@material-ui/core/Popover';
import CombinedStepPicker from '../Pickers/CombinedStepPicker';

export default (props) => {

  const { className, openStepBlock, closeStepBlock, anchorEl, setAnchorEl } = props;
  const open = Boolean(anchorEl);
  const id = open ? 'step-picker-popover' : undefined;

  console.log(props)
  return (
    <>
      <Fab className={ className } size={'small'} aria-label="add" onClick={openStepBlock}>
        <AddIcon />
      </Fab>
      <Popover
        id={id}
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
