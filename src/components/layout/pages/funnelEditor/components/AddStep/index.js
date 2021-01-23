import { useDispatch, useSelect } from '@wordpress/data'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import { Button } from '@material-ui/core'

export default () => {

  const { funnel } = useSelect( (select) => {
    return {
      funnel: select(FUNNELS_STORE_NAME).getItem()
    }
  } )

  const { createStep } = useDispatch( FUNNELS_STORE_NAME );

  return (
    <>

    </>
  )
}