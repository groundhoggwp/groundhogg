import Editor from './editor'
import { canUser } from 'utils'

export default () => {

  const { ID } = window.Groundhogg.funnel

  return (
    <>
      <div id={'funnel-editor'}>
        <Editor id={ID}/>
      </div>
    </>
  )
}

