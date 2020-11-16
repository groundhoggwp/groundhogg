import { useState } from '@wordpress/element'
import { addNotification } from 'utils/index'
import { __ } from '@wordpress/i18n'
import BulkJob from 'components/core-ui/bulk-job'
import { Redirect } from 'react-router'
import { DATETIME_FULL } from 'luxon/src/impl/formats'

export const Exporting = (props) => {


  // get Broadcast ID
  const { data, setData, handleNext } = props
  const { fields } = data

  //build context for bulk-job operation
  let context = {
    headers: fields,
    file_name: Math.random() + '.csv'
  }

  const onFinish = ({ finished, data }) => {
    // handle the response and do any tasks which are required.
    addNotification({ message: __('Contacts exported successfully'), type: 'success' })
    setData({ ...data, ...{ file: data.file } })
    handleNext()
  }


  return (
    <div style={{
      padding: 24,
      background: '#fff',
    }}>
      <BulkJob
        jobId={Math.random()}
        perRequest={100}
        title={__('Exporting contacts')}
        context={context}
        onFinish={onFinish}
        action={'gh_export_contacts_rest'}
      />
    </div>
  )
}