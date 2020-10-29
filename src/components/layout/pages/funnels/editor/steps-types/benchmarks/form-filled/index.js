import LocalOfferIcon from '@material-ui/icons/LocalOffer';
import { BENCHMARK } from '../../constants'
import { registerStepType } from 'data/step-type-registry'

const STEP_TYPE = 'form_filled'

const stepAtts = {

  type: STEP_TYPE,

  group: BENCHMARK,

  name: 'Form Filled',

  icon: <LocalOfferIcon/>,

  view: ({data, meta, stats}) => {
    return <></>
  },
  edit: ({data, meta, stats}) => {
    return <></>
  },
}

registerStepType( STEP_TYPE, stepAtts );