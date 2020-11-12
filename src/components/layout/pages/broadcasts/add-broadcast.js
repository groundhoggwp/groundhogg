import { __ } from '@wordpress/i18n'
import { Fragment, useState } from '@wordpress/element'
import { ScheduleBroadcast } from './steps/schedule-broadcast'
import { ConfirmBroadcast } from './steps/confirm-broadcast'
import { ScheduleEvents } from './steps/schedule-events'
import { Steps } from 'components/core-ui/steps'

export const AddBroadcast = (props) => {

  const [data, setData] = useState({
    email_or_sms_id: 0,
    tags: [],
    exclude_tags: [],
    date: '',
    time: '',
    send_now: false,
    send_in_timezone: false,
    type: '',
  })

  const [broadcast, setBroadcast] = useState(0)

  const steps = [
    __('Schedule Broadcast', 'goundhogg'),
    __('Confirm Broadcast', 'goundhogg'),
    __('Send Broadcast', 'goundhogg'),
  ]

  const getStepContent = (stepIndex, handleNext, handleBack) => {
    switch (stepIndex) {
      case 0:
        return <ScheduleBroadcast
          handleNext={handleNext}
          handleBack={handleBack}
          data={data}
          setData={setData}
        />
      case 1:
        return <ConfirmBroadcast
          handleNext={handleNext}
          handleBack={handleBack}
          setData={setData}
          data={data}
          broadcast={broadcast}
          setBroadcast={setBroadcast}
        />
      case 2:
        return <ScheduleEvents
          handleNext={handleNext}
          handleBack={handleBack}
          setData={setData}
          data={data}
          broadcast={broadcast}
          setBroadcast={setBroadcast}
        />
      default:
        return 'Unknown stepIndex'
    }
  }

  return (
    <Fragment>
      <Steps steps={steps} getStepContent={getStepContent}/>
    </Fragment>
  )

}