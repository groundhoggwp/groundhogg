import { useDispatch, useSelect } from '@wordpress/data'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import ButtonGroup from '@material-ui/core/ButtonGroup'
import Button from '@material-ui/core/Button'
import { useTempState } from 'utils/index'

export default ({ id, data }) => {

  const { updateItem } = useDispatch(FUNNELS_STORE_NAME)
  const { isUpdating } = useSelect( (select) => {
    return {
      isUpdating : select(FUNNELS_STORE_NAME).isItemsUpdating()
    }
  }, [] )

  const { tempState, setTempState } = useTempState( data );

  const handleClick = (status) => {
    setTempState( { ...tempState, status : status } );
    updateItem(id, {
      data: {
        status: status,
      },
    })
  }

  return <>
    <ButtonGroup color="primary"
                 aria-label={ 'funnel status' }>
      <Button
        disabled={isUpdating}
        onClick={()=>handleClick('active')}
        variant={ tempState.status === 'active'
          ? 'contained'
          : 'outlined' }>{ 'Active' }</Button>
      <Button
        disabled={isUpdating}
        onClick={()=>handleClick('inactive')}
        variant={ tempState.status !== 'active'
          ? 'contained'
          : 'outlined' }>{ 'Inactive' }</Button>
    </ButtonGroup>
  </>
}