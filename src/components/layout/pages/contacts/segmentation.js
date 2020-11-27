import React from 'react'
import { Fragment } from '@wordpress/element'
import { ContactPanel } from 'components/layout/pages/contacts/contact-panel'
import { __ } from '@wordpress/i18n'

export const Segmentation = (props) => {

  let segments = [
    {
      id: 'segmentation',
      tab: 'segmentation',
      title: __('Segmentation', 'groundhogg'),
      fields: [
        {
          defaultValue: '',
          desc: '',
          id: 'owner_id',
          label: __('Owner', 'groundhogg'),
          section: 'segmentation',
          type: 'dropdown_owners',
          dataType: 'data',
          sm: '12',
          md: '12',
          lg: '12'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'source_page',
          label: __('Source Page', 'groundhogg'),
          section: 'segmentation',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'lead_source',
          label: __('Lead Source', 'groundhogg'),
          section: 'segmentation',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
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