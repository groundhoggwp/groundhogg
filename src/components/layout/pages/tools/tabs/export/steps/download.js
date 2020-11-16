import Button from '@material-ui/core/Button'
import { __ } from '@wordpress/i18n'
import { useHistory, useRouteMatch } from 'react-router-dom'

export const Download = (props) => {

  const { data, setData } = props
  const { file } = data

  let history = useHistory()

  return (
    <div style={{
      padding: 24,
      background: '#fff',
      marginTop: 10
    }}>
      <Button variant="contained" color="primary" onClick={() => {
        window.open(file.file_url, '_blank')
        //redirect to the main page of export
        history.goBack( '/tools/export' )
      }}>
        {__('Download', 'groundhogg')}
      </Button>
    </div>
  )

}