import { Fragment, useState } from '@wordpress/element'
import { addNotification } from 'utils/index'
import { __ } from '@wordpress/i18n'
import BulkJob from 'components/core-ui/bulk-job'
import {} from '@material-ui/core'
import { Redirect } from 'react-router'
import Button from '@material-ui/core/Button'
import Select from '@material-ui/core/Select'
import MenuItem from '@material-ui/core/MenuItem'
import { FormFileUpload } from '@wordpress/components'
import { useDispatch, useSelect } from '@wordpress/data'
import { IMPORT_STORE_NAME } from 'data/import'

export const Upload = (props) => {

  const { handleNext, data, setData } = props

  const [upload, setUpload] = useState(null)
  const { file } = data

  const { createItems } = useDispatch(IMPORT_STORE_NAME)

  const { getCreatedItems, isItemsCreating } = useSelect((select) => {
    const store = select(IMPORT_STORE_NAME)
    return {
      getCreatedItems: store.getCreatedItems(),
      isItemsCreating: store.isItemsCreating()
    }
  }, [])

  // redirect to the next page if file is set and already uploaded
  if (file) {
    handleNext()
  }

  if (!isItemsCreating && getCreatedItems[0]) {

    setData({
      ...data,
      ...{
        file: getCreatedItems[0]
      }
    })
  }

  const handleUpload = () => {
    if (upload) {

      // make a rest api call here to upload a file
      const formData = new FormData()
      formData.append('upload', upload)
      formData.append('key', 'value')
      createItems(formData, true)

    } else if (file) {
      handleNext()
    } else {
      addNotification({
        message: __('Please upload or select a file from list.'),
        type: 'error'
      })
    }
  }

  return (
    <Fragment>
      <div style={{
        padding: 24,
        background: '#fff',
      }}>

        <FormFileUpload
          accept="CSV/*"
          onChange={(event) => {
            setUpload(event.target.files[0])
          }}
        >
          Upload
        </FormFileUpload>

      </div>

      <div style={{
        padding: 24,
        background: '#fff',
        marginTop: 10

      }}>
        <Button
          variant="contained"
          color="primary"
          onClick={handleUpload}
          disabled={isItemsCreating}
        >
          {isItemsCreating ? 'Loading..' : __('Upload & Next', 'groundhogg')}
        </Button>
      </div>
    </Fragment>

  )

}