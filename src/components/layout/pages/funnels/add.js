import Button from '@material-ui/core/Button'
import { useDispatch, useSelect } from '@wordpress/data'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import { __ } from '@wordpress/i18n'

export default () => {

  const { createItem } = useDispatch( FUNNELS_STORE_NAME );
  const { isCreating } = useSelect( (select) => {
    return {
      isCreating: select( FUNNELS_STORE_NAME ).isItemsCreating()
    }
  })

  const createFunnel = () => {
    createItem({
      data: {
        title: "New Funnel"
      }
    })
  }

  if ( isCreating ){
    return (
      <p>Creating...</p>
    )
  }

  return (
    <>
      <p>{ __( "Looks like you don't have any funnels at the moment.", "groundhogg" ) }</p>
      <Button onClick={createFunnel}>Create New</Button>
    </>
  )
}