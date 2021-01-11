import { Fragment } from '@wordpress/element'
import { ContactPanel } from 'components/layout/pages/contacts/contact-panel'
import React from 'react'

import { __ } from '@wordpress/i18n'

export const GeneralInfo = (props) => {

  const info = [
    {
      id: 'contact_info',
      tab: 'general_info',
      title: __('Contact Information', 'groundhogg'),
      fields: [
        {
          defaultValue: '',
          desc: '',
          id: 'first_name',
          label: __('First Name', 'groundhogg'),
          section: 'contact_info',
          dataType: 'data',
          type: 'input',
          sm: '12',
          md: '6',
          lg: '6'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'last_name',
          label: __('Last Name', 'groundhogg'),
          dataType: 'data',
          section: 'contact_info',
          type: 'input',
          sm: '12',
          md: '6',
          lg: '6'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'email',
          label: __('Email', 'groundhogg'),
          dataType: 'data',
          section: 'contact_info',
          type: 'input',
          sm: '12',
          md: '12',
          lg: '12'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'primary_phone',
          label: __('Primary Phone', 'groundhogg'),
          dataType: 'meta',
          section: 'contact_info',
          type: 'input',
          sm: '12',
          md: '6',
          lg: '6'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'primary_phone_extension',
          label: __('Primary Phone Extension', 'groundhogg'),
          dataType: 'data',
          section: 'contact_info',
          type: 'input',
          sm: '12',
          md: '6',
          lg: '6'
        }
      ],

    }, {
      id: 'user_account',
      tab: 'general_info',
      title: __('User Account', 'groundhogg'),
      fields: [
        {
          defaultValue: '',
          desc: 'The primary user to reference for contact information.',
          id: 'gh_primary_user',
          label: 'Primary User',
          section: 'general_other',
          type: 'input',
        }
      ],

    }, {
      id: 'personal_info',
      tab: 'general_info',
      title: __('Personal Info', 'groundhogg'),
      fields: [
        {
          defaultValue: '',
          desc: '',
          id: 'birthday',
          label: __('Birthday', 'groundhogg'),
          section: 'personal_info',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
      ],

    }, {
      id: 'company_info',
      tab: 'general_info',
      title: __('Company Info', 'groundhogg'),
      fields: [
        {
          defaultValue: '',
          desc: '',
          id: 'company_name',
          label: __('Company Name', 'groundhogg'),
          section: 'company_info',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'job_title',
          label: __('Job Title', 'groundhogg'),
          section: 'company_info',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'company_address',
          label: __('Company Address', 'groundhogg'),
          section: 'personal_info',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
      ],

    },
    {
      id: 'location',
      tab: 'general_info',
      title: __('Location', 'groundhogg'),

      fields: [
        {
          defaultValue: '',
          desc: '',
          id: 'street_address_1',
          label: __('Street Address 1', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '6',
          lg: '6'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'street_address_2',
          label: __('Street Address 2', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '6',
          lg: '6'
        },

        {
          defaultValue: '',
          desc: '',
          id: 'postal_zip',
          label: __('Postal/Zip Code', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '6',
          lg: '6'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'city',
          label: __('city', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '6',
          lg: '6'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'region',
          label: __('State/Province', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '6',
          lg: '6'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'region',
          label: __('country', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '6',
          lg: '6'

        },
        {
          defaultValue: '',
          desc: '',
          id: 'ip_address',
          label: __('IP Address', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
        {
          defaultValue: '',
          desc: '',
          id: 'time_zone',
          label: __('Time Zone', 'groundhogg'),
          section: 'location',
          type: 'input',
          dataType: 'meta',
          sm: '12',
          md: '12',
          lg: '12'
        },
      ],
    },
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
      ],
    }
  ]

  return (
    <Fragment>
      <ContactPanel section={info} contact={props.contact}/>
    </Fragment>
  )

}