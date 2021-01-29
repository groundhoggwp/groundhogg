import LocalOfferIcon from '@material-ui/icons/LocalOffer'
import { BENCHMARK, BENCHMARK_TYPE_DEFAULTS } from '../../constants'
import { registerStepType } from 'data/step-type-registry'

const STEP_TYPE = 'tag_removed'

const stepAtts = {

  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: 'Tag Removed',

  icon: <LocalOfferIcon/>,

  view: ({ data, meta, stats }) => {
    return <></>
  },
  edit: ({ data, meta, stats }) => {
    return <></>
  },
}

registerStepType(STEP_TYPE, stepAtts)