import StepsPicker from '../StepPicker'
import { useSelect } from '@wordpress/data'
import { STEP_TYPES_STORE_NAME } from 'data/step-type-registry'
import { CONDITION } from '../../../steps-types/constants'

export default (props) => {

  const { steps } = useSelect( (select) => {
    return {
      steps: select( STEP_TYPES_STORE_NAME ).getGroup( CONDITION )
    }
  }, [] )

  return <StepsPicker
    steps={steps}
    stepGroup={CONDITION}
    {...props}
  />
}