
import { registerStepType } from 'data/step-type-registry'

const STEP_TYPE = 'error'

const stepAtts = {

  type: STEP_TYPE,

  group: 'error',

  name: 'Error',

  view: ({data, meta, stats}) => {
    return <></>
  },

  edit: ({data, meta, stats}) => {
    return <></>
  },
}

registerStepType( STEP_TYPE, stepAtts );