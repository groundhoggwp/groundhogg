
import { registerStepType } from 'data/step-type-registry'
import { STEP_DEFAULTS } from 'components/layout/pages/funnelEditor/steps-types/constants'

const STEP_TYPE = 'error'

const stepAtts = {

  ...STEP_DEFAULTS,

  type: STEP_TYPE,

  group: 'error',

  name: 'Error',
}

registerStepType( STEP_TYPE, stepAtts );
