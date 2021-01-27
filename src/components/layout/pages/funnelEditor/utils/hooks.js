import { useDispatch, useSelect } from '@wordpress/data'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import { getStepType } from 'data/step-type-registry'
import { useParams } from 'react-router-dom'

export const useCurrentStep = () => {

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

  return {
    step,
    funnelId: funnel_id,
    stepId,
    isUpdating,
    StepType,
    updateStep,
    deleteStep
  }
}

export const useCurrentFunnel = () => {

  const { funnel } = useSelect( (select) => {
    return {
      funnel: select(FUNNELS_STORE_NAME).getItem()
    }
  } )

  const { updateItem, createStep, deleteItem } = useDispatch( FUNNELS_STORE_NAME )

  return {
    funnel,
    funnelId: funnel.ID,
    updateFunnel: updateItem,
    createStep,
    deleteFunnel: deleteItem
  }
}