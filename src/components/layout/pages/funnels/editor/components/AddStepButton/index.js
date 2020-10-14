import Fab from '@material-ui/core/Fab'
import AddStepPopover from '../AddStepPopover'
import AddIcon from '@material-ui/icons/Add';
import { useState } from '@wordpress/element'

export default (props) => {

  const [anchorEl, setAnchorEl] = useState(null);

  const handleClick = (event) => {
    setAnchorEl(event.currentTarget);
  };

  const handleClose = () => {
    setAnchorEl(null);
  };

  return (
    <>
      <Fab color="primary" aria-label="add" onClick={handleClick}>
        <AddIcon />
      </Fab>
      <AddStepPopover
        target={anchorEl}
        onClose={handleClose}
        {...props}
      />
    </>
  )

}
