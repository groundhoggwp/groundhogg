import { useHistory, useLocation } from 'react-router-dom'

export const SyncUsers = (props) => {


  // check if the bulk job flag is set
  let history = useHistory()
  const location = useLocation()
  console.log(location.sync_meta)


  if (!location.bulk_job) {
    history.goBack( '/tools/sync' )
  }


  return <h1> Sync users </h1>;


}
