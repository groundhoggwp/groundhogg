import { getStepType } from 'data/step-type-registry'

export default (props) => {

  const { step_type } = props.data;

  const StepType = getStepType( step_type );

  return <StepType.Edges {...props}/>
}