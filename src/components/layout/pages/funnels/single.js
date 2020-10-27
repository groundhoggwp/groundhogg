import Editor from './editor';
import {
  useParams
} from "react-router-dom";

export default () => {

  const { id } = useParams();

  return (
    <>
      <Editor id={id}/>
    </>
  )
}
