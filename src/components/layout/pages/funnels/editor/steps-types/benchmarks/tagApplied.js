import LocalOfferIcon from '@material-ui/icons/LocalOffer';
import { BENCHMARK } from '../constants'
import { registerStepType } from '../../../../../../../data/step-type-registry'

const STEP_TYPE = 'tag_applied'

const stepAtts = {

  type: STEP_TYPE,

  group: BENCHMARK,

  name: 'Tag Applied',

  icon: <LocalOfferIcon/>,

  view: ({data, meta, stats}) => {},
  edit: ({data, meta, stats}) => {},
}

registerStepType( STEP_TYPE, stepAtts );