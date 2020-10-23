import Editor from './editor';
import {
  useParams
} from "react-router-dom";

export default () => {

  const { id } = useParams();

  // console.debug( id );

  return (
    <>
      <Editor id={id}/>
    </>
  )
}
