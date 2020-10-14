import StepsPicker from '../StepPicker'

export default (props) => {

  let benchmarks = [
    'form_filled',
    'email_confirmed',
    'tag_applied',
    'tag_removed',
    'logged_in',
    'link_clicked'
  ]

  return <StepsPicker
    steps={benchmarks}
    stepGroup={'benchmark'}
    {...props}
  />
}