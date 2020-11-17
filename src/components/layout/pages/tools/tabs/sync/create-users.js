import {useHistory, useLocation} from "react-router-dom";
import {addNotification} from "utils/index";
import {__} from "@wordpress/i18n";
import BulkJob from "components/core-ui/bulk-job";

export const CreateUsers = (props) => {

  // check if the bulk job flag is set
  let history = useHistory()
  const location = useLocation()


  if (!location.bulk_job) {
    history.goBack( '/tools/sync' )
  }


  //build context for bulk-job operation
  let context = {
      tags_include :[] ,
      tags_exclude :[]
  }

  const onFinish = ({ finished, data }) => {
    // handle the response and do any tasks which are required.
    addNotification({ message: __('Contacts Synced successfully'), type: 'success' })
    history.goBack( '/tools/sync' )
  }


  return (
      <div style={{
        padding: 24,
        background: '#fff',
      }}>
        <BulkJob
            jobId={Math.random()}
            perRequest={100}
            title={__('Syncing contacts')}
            context={context}
            onFinish={onFinish}
            action={'gh_sync_users_rest'}
        />
      </div>
  )

}
