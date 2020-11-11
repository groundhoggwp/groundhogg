import { Fragment, useEffect, useState } from '@wordpress/element'
import { addNotification } from 'utils/index'
import { __ } from '@wordpress/i18n'
import Button from '@material-ui/core/Button'
import { FieldMap } from 'components/core-ui/field-map'
import { readRemoteFile } from 'react-papaparse'

export const Map = (props) => {

  const { handleBack, handleNext, data, setData } = props
  const { file, map } = data

  const [fields, setFields] = useState([])
  const [mapping, setMapping] = useState(map)

  //sets the mapping for the import
  const handleImport = () => {
    setData({
      ...data,
      ...{
        map: mapping,
      }
    })
    handleNext()
  }

  //set mapping field based on selction
  const setMap = (key, value) => {
    setMapping({
      ...mapping,
      ...{
        [key]: value
      }
    })
  }

  // check for file
  if (!file) {
    handleBack()
  }

  // Fetch Mapping Fields and set delimiter
  useEffect(() => {
    readRemoteFile(file.file_url, {
      preview: 1,
      step: (row) => {

        // set list of fields to map from CSV file
        setFields(row.data)

        //set delimiter for import
        setData({ ...data, ...{ delimiter: row.meta.delimiter } })
      },
      complete: () => {
        console.log('All done!')
      }
    })

  }, [file.file_url])

  // render mapping fields and other controls
  return (
    <Fragment>
      <div style={{
        padding: 24,
        background: '#fff',
      }}>

        <table>
          <tr>
            <td> Field Mapping</td>
            <td><FieldMap fields={fields} setMap={setMap} map={mapping}/></td>
          </tr>
        </table>
        <br/>
      </div>
      <div style={{
        padding: 24,
        background: '#fff',
        marginTop: 10
      }}>
        {/*<Button variant="contained" color="secondary" onClick={() => {*/}
        {/*  // clear a file*/}
        {/*  handleBack()*/}
        {/*}}>*/}
        {/*  {__('Back', 'groundhogg')}*/}
        {/*</Button>*/}
        <Button variant="contained" color="primary" onClick={handleImport}>
          {__('Import Contacts (' + file.rows + ')', 'groundhogg')}
        </Button>
      </div>
    </Fragment>
  )
}