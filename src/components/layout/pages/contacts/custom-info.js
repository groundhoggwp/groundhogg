import { Fragment, useState } from '@wordpress/element'
import { ContactPanel } from 'components/layout/pages/contacts/contact-panel'
import React from 'react'

import { __ } from '@wordpress/i18n'
import { useDispatch, useSelect } from '@wordpress/data'
import { Button, List } from '@material-ui/core'
import TextField from '@material-ui/core/TextField'
import Tooltip from '@material-ui/core/Tooltip/Tooltip'
import IconButton from '@material-ui/core/IconButton'
import DeleteIcon from '@material-ui/icons/Delete'
import { CONTACTS_STORE_NAME } from 'data/contacts'
import { useParams } from 'react-router-dom'

export const CustomInfo = (props) => {

  let meta = props.contact.meta

  let { id } = useParams()

  const [customMeta, setCustomMeta] = useState([])

  const { updateItem } = useDispatch(CONTACTS_STORE_NAME)

  // get list of pre rendered meta
  let excludeMeta = [
    'birthday',
    'birthday_month',
    'birthday_day',
    'birthday_year',
    'lead_source',
    'source_page',
    'page_source',
    'terms_agreement',
    'terms_agreement_date',
    'gdpr_consent',
    'gdpr_consent_date',
    'primary_phone',
    'primary_phone_extension',
    'street_address_1',
    'street_address_2',
    'time_zone',
    'city',
    'postal_zip',
    'region',
    'country',
    'notes',
    'files',
    'company_name',
    'company_address',
    'job_title',
    'ip_address',
    'last_optin',
    'last_sent',
    'country_name',
    'region_code',
  ]

  let fields = []
  for (const item in meta) {
    if (!excludeMeta.includes(item)) {

      fields.push({
        defaultValue: '',
        desc: '',
        id: item,
        label: __(item, 'groundhogg'),
        section: 'meta',
        dataType: 'meta',
        type: 'input',
        sm: '12',
        md: '12',
        lg: '12'
      })
    }
  }

  const info = [
    {
      id: 'meta',
      tab: 'custom_info',
      title: __('Custom Meta', 'groundhogg'),
      fields: fields

    }
  ]

  let metaObject = {}
  for (let i = 0; i < customMeta.length; i++) {
    metaObject[customMeta[i].metaName] = customMeta[i].metaValue
  }

  return (
    <Fragment>
      <AddMeta inputList={customMeta} setInputList={setCustomMeta}/>
      <br/>
      <br/>
      <ContactPanel section={info} contact={props.contact} customObject={{ meta: metaObject }}/>
    </Fragment>
  )

}

export const AddMeta = (props) => {

  const { inputList, setInputList } = props

  // handle input change
  const handleInputChange = (e, index) => {

    const { name, value } = e.target

    const list = [...inputList]
    list[index][name] = value
    setInputList(list)
  }

// handle click event of the Remove button
  const handleRemoveClick = index => {
    const list = [...inputList]
    list.splice(index, 1)
    setInputList(list)
  }

// handle click event of the Add button
  const handleAddClick = () => {
    setInputList([...inputList, { metaName: '', metaValue: '' }])
  }

  return (
    <div className="App">
      <Button variant={'contained'} color={'primary'} onClick={handleAddClick}>Add meta </Button>
      <br/>
      <br/>

      <table>
        {inputList.map((x, i) => {
          return (
            <tr>
              <td>
                <TextField label={__('Meta Name', 'groundhogg')} name={'metaName'} value={x.metaName}
                           onChange={e => handleInputChange(e, i)}/>
              </td>
              <td>
                <TextField label={__('Meta Value', 'groundhogg')} name={'metaValue'} value={x.metaValue}
                           onChange={e => handleInputChange(e, i)}/></td>
              <td>

                <Tooltip title={'Delete'}>
                  <IconButton aria-label={'Delete'} color={'secondary'} onClick={() => handleRemoveClick(i)}>
                    <DeleteIcon/>
                  </IconButton>
                </Tooltip>
              </td>
            </tr>
          )
        })}
      </table>
    </div>
  )

}
