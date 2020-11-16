import { useState } from '@wordpress/element'
import { addNotification } from 'utils/index'
import { __ } from '@wordpress/i18n'
import BulkJob from 'components/core-ui/bulk-job'
import { Redirect } from 'react-router'

export const Import = (props) => {

  // track bulk-job is finished
  const [finish, setFinish] = useState(false)

  // get Broadcast ID
  const { data } = props
  const { file, map ,tags ,confirm } = data

  //build context for bulk-job operation
  let context = {
    import: file.file_name,
    map: map,
    tags :tags ,
    confirm : confirm
  }

  const onFinish = (newValue) => {
    // handle the response and do any tasks which are required.
    addNotification({ message: __('Contacts imported successfully'), type: 'success' })
    setFinish(true)
  }

  // Redirect to main page once broadcast is scheduled sucessfully
  if (finish === true) {
    return <Redirect to={'/tools/import'}/>
  }

  return (
    <div style={{
      padding: 24,
      background: '#fff',
    }}>
      <BulkJob
        jobId={Math.random()}
        perRequest={100}
        title={__('Importing contacts')}
        context={context}
        onFinish={onFinish}
        action={'gh_import_contacts_rest'}
      />
    </div>
  )

}