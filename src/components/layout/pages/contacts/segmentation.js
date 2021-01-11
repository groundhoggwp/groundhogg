import React from 'react'
import { Fragment } from '@wordpress/element'
import { ContactPanel } from 'components/layout/pages/contacts/contact-panel'
import { __ } from '@wordpress/i18n'

export const Segmentation = (props) => {

  let segments = [
    {
      id: 'tags',
      tab: 'tags',
      title: __('Tags', 'groundhogg'),
      fields: [
        {
          defaultValue: '',
          desc: '',
          id: 'lead_source',
          label: __('Lead Source', 'groundhogg'),
          section: 'segmentation',
          type: 'tag_picker',
          dataType: 'tags',
          sm: '12',
          md: '12',
          lg: '12'
        },
      ],
    }
  ]

  return (
    <Fragment>
      <ContactPanel section={segments} contact={props.contact}/>
    </Fragment>
  )
}