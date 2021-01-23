import { useParams } from 'react-router-dom'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import { useDispatch, useSelect } from '@wordpress/data'
import { getStepType } from 'data/step-type-registry'
import { useTempState } from 'utils/index'
import Typography from '@material-ui/core/Typography'
import { Button } from '@material-ui/core'
import { useEffect } from '@wordpress/element'
import { objEquals } from 'utils/core'

export default () => {

  const { stepId } = useParams()

  const { step, isUpdating } = useSelect((select) => {
    const store = select(FUNNELS_STORE_NAME)
    return {
      step: store.getStep(parseInt(stepId)),
      isUpdating: store.isUpdatingStep
    }
  }, [stepId])

  const { updateStep, deleteStep } = useDispatch(FUNNELS_STORE_NAME)
  const { step_type, funnel_id } = step.data
  const StepType = getStepType(step_type)
  const { tempState, setTempState, resetTempState } = useTempState(step.meta)

  const commitChanges = () => {
    updateStep(funnel_id, stepId, {
      meta: {
        ...tempState
      }
    })
  }

  useEffect(() => {
    setTempState(step.meta)
  }, [isUpdating])

  return (
    <>
      <Typography variant={'h1'}>{StepType.name}</Typography>
      <div className={'edit-panel'}>
        <StepType.edit data={step.data} settings={tempState} updateSettings={setTempState}/>
        {!objEquals(tempState, step.meta) &&
        <>
          <Button onClick={commitChanges} variant={'contained'} color={'primary'}>{'Save Changes'}</Button>
          <Button onClick={resetTempState} variant={'contained'}>{'Cancel'}</Button>
        </>
        }
      </div>
    </>
  )

}