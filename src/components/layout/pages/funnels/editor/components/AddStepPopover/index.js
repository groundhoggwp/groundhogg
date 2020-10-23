import Popover from '@material-ui/core/Popover'
import CombinedStepPicker from '../Pickers/CombinedStepPicker'

export default (props) => {

  const {target, onClose} = props;

  const open = Boolean(target);
  const id = open ? 'step-picker-popover' : undefined;

  return (
    <Popover
      id={id}
      open={open}
      anchorEl={target}
      onClose={onClose}
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
  )

}
