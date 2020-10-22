import Fab from '@material-ui/core/Fab'
import AddStepPopover from '../AddStepPopover'
import AddIcon from '@material-ui/icons/Add';
import { useState } from '@wordpress/element'

export default (props) => {

  const { className, openStepBlock, closeStepBlock, anchorEl, setAnchorEl } = props;

  return (
    <>
      <Fab className={ className } size={'small'} aria-label="add" onClick={openStepBlock}>
        <AddIcon />
      </Fab>
      <AddStepPopover
        target={anchorEl}
        onClose={closeStepBlock}
        onOpen={openStepBlock}
        {...props}
      />
    </>
  )

}
