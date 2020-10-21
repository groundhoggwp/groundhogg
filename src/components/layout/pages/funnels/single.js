import { useSelect } from '@wordpress/data';
import { FUNNELS_STORE_NAME } from 'data';
import {
  useParams
} from "react-router-dom";
import Editor from './editor';

export default () => {

  let { id } = useParams();

  const { item, isCreating, isDeleting, isUpdating, isRequesting } = useSelect( (select) => {
    const store = select( FUNNELS_STORE_NAME )

    return {
      item: store.getItem( id ),
      getItem: store.getItem,
      isCreating: store.isCreatingStep(),
      isDeleting: store.isDeletingStep(),
      isUpdating: store.isUpdatingStep(),
      isRequesting: store.isItemsRequesting()
    }
  }, [] )

  console.log(isCreating, isDeleting, isUpdating, isRequesting, item)

  if ( ! item ){
    return <>Loading...</>
  }

  return (
    <>
      <Editor
        funnel={item}
      />
    </>
  )
}
