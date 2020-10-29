import EmailIcon from '@material-ui/icons/Email';

import { ACTION } from '../../constants'
import { registerStepType } from 'data/step-type-registry'

const STEP_TYPE = 'admin_notification'

const stepAtts = {

  type: STEP_TYPE,

  group: ACTION,

  name: 'Admin Notification',

  icon: <EmailIcon/>,

  view: ({data, meta, stats}) => {
    return <></>
  },
  edit: ({data, meta, stats}) => {
    return <></>
  },
}

registerStepType( STEP_TYPE, stepAtts );