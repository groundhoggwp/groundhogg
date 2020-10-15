import { useState } from '@wordpress/element'
import { useDispatch, useSelect } from '@wordpress/data'
import { STEP_TYPES_STORE_NAME } from '../../../../../../../data/step-type-registry'
import { STEPS_STORE_NAME } from '../../../../../../../data/steps'

export default ({ID, data, meta, onCancel}) => {

  const [ tempData, setTempData ] = useState(data);
  const [ tempMeta, setTempMeta ] = useState(meta);
  const { step_title, step_type, step_group, parent_steps, child_steps } = data;

  const { updateItem } = useDispatch( STEPS_STORE_NAME );

  const { stepType } = useSelect( (select) => {
    return {
      stepType: select( STEP_TYPES_STORE_NAME ).getType( step_type )
    }
  }, [] )

  const handleSave = () => {
    updateItem( ID, {
      data: tempData,
      meta: tempMeta,
    } )
  }

  return (
    <stepType.edit
      data={tempData}
      meta={tempMeta}
      onSave={handleSave}
      onCanel={onCancel}
    />
  )

 }