import StepsPicker from '../StepPicker'

export default (props) => {

  let actions = [
    'send_email',
    'apply_tag',
    'remove_tag',
  ]

  return <StepsPicker
    steps={actions}
    stepGroup={'action'}
    {...props}
  />
}