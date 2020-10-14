import { useSelect } from '@wordpress/data';
import { FUNNELS_STORE_NAME } from '../../../../data/funnels';
import {
  useParams
} from "react-router-dom";
import Editor from './editor';

export default () => {

  let { id } = useParams();
  const { item } = useSelect( (select) => {

    const store = select( FUNNELS_STORE_NAME )

    return {
      item: store.getItem( id ),
      getItem: store.getItem,
    }
  }, [] )

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
