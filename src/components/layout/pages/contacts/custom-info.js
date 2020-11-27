import { Fragment } from '@wordpress/element'
import { ContactPanel } from 'components/layout/pages/contacts/contact-panel'
import React from 'react'

import { __ } from '@wordpress/i18n'







export const CustomInfo = (props) => {

  let meta = props.contact.meta

  console.log(meta)

  // get list of pre renderd meta

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


  let fields  = []
  for (const item  in meta) {
    if (! excludeMeta.includes( item )) {

      fields.push({
        defaultValue: '',
        desc: '',
        id: item,
        label: __( item , 'groundhogg'),
        section: 'meta',
        dataType: 'meta',
        type: 'input',
        sm: '12',
        md: '12',
        lg: '12'
      } )
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

  return (
    <Fragment>
      <ContactPanel section={info} contact={props.contact}/>
    </Fragment>
  )

}