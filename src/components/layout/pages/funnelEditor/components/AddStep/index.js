import { useState } from '@wordpress/element'
import Typography from '@material-ui/core/Typography'
import { useCurrentFunnel } from 'components/layout/pages/funnelEditor/utils/hooks'
import { ACTION, ACTIONS } from 'components/layout/pages/funnelEditor/steps-types/constants'
import { getStepGroup } from 'data/step-type-registry'

export default () => {

  const { funnelId, createStep } = useCurrentFunnel()
  const [stepGroup, setStepGroup] = useState(ACTION)

  const steps = getStepGroup(stepGroup);

  const choseStepType = (type, group) => {

    createStep(funnelId, {
      data: {
        funnel_id: funnelId,
        step_type: type,
        step_group: group
      }
    })
  }

  return (
    <>
      <Typography variant={'h1'}>{'Add new step'}</Typography>
      <ul>
      {
        steps.map( StepType => {
          return <>
            <li onClick={() => choseStepType( StepType.type, StepType.group)}>{StepType.name}</li>
          </>
        })
      }
      </ul>
    </>
  )
}