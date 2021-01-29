import { useState } from '@wordpress/element'
import { useDispatch, useSelect } from '@wordpress/data'
import { STEP_TYPES_STORE_NAME } from 'data/step-type-registry'
import { FUNNELS_STORE_NAME } from 'data'
import { useTempState } from 'utils/index'

export default ({step, onClose}) => {

  const { tempState, setTempState, resetTempState } = useTempState( step )
  const { ID, data, meta } = step;
  const { funnel_id } = data;

  const { updateStep } = useDispatch( FUNNELS_STORE_NAME );

  const commitChanges = async () => {
    await updateStep( funnel_id, ID, tempState );
  }

  return (
    <>

    </>
  )

 }
