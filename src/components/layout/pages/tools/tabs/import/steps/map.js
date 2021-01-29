import { Fragment, useEffect, useState } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import Button from '@material-ui/core/Button'
import { FieldMap } from 'components/core-ui/field-map'
import { readRemoteFile } from 'react-papaparse'
import { TagPicker } from 'components/index'
import CardContent from '@material-ui/core/CardContent'
import React from 'react'
import FormControlLabel from '@material-ui/core/FormControlLabel'
import Checkbox from '@material-ui/core/Checkbox'

export const Map = (props) => {

  const { handleBack, handleNext, data, setData } = props
  const { file, map } = data

  const [fields, setFields] = useState([])
  const [mapping, setMapping] = useState(map)
  const [tags, setTags] = useState([])
  const [confirm, setConfirm] = useState(false)

  //sets the mapping for the import
  const handleImport = () => {

    //get tagIds
    let tags_apply = []

    if (tags !== null) {
      tags_apply = tags.map((tag) => tag.value)
    }

    setData({
      ...data,
      ...{
        map: mapping,
        tags: tags_apply,
        confirm: confirm
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
            <td>{__('Field Mapping', 'groundhogg')}</td>
            <td><FieldMap fields={fields} setMap={setMap} map={mapping}/></td>
          </tr>
          <tr>
            <td>{__('Add additional tags to this import', 'groundhogg')}</td>
            <td><TagPicker onChange={setTags} value={tags}/></td>
          </tr>
          <tr>
            <td>{__('I have previously confirmed these email addresses.', 'groundhogg')}</td>
            <td>
              <FormControlLabel
                control={<Checkbox
                  color="primary"
                  checked={confirm}
                  onChange={(event) => {
                    if (confirm) {
                      setConfirm(false)
                    } else {
                      setConfirm(true)
                    }
                  }}/>}
                label={'Yes'}
                labelPlacement="end"
              /></td>
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