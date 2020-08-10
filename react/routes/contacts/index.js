import React, { useEffect } from 'react'
import { IconButton } from '../../components/Buttons/Buttons'
import { connect } from 'react-redux'
import { clearItems, fetchContacts, fetchMoreContacts, resetQuery, updateQuery } from '../../actions/contactListActions'
import { ListTable } from '../../components/ListTable/ListTable'
import { FaIcon } from '../../components/basic-components'
import { Badge } from 'react-bootstrap'
import moment from 'moment'

import './style.scss'

const optinStatusMap = {
  1: <Badge variant={'secondary'}>{'Unconfirmed'}</Badge>,
  2: <Badge variant={'success'}>{'Confirmed'}</Badge>,
  3: <Badge variant={'dark'}>{'Unsubscribed'}</Badge>,
  4: <Badge variant={'success'}>{'Weekly'}</Badge>,
  5: <Badge variant={'success'}>{'Monthly'}</Badge>,
  6: <Badge variant={'danger'}>{'Bounced'}</Badge>,
  7: <Badge variant={'danger'}>{'Spam'}</Badge>,
  8: <Badge variant={'danger'}>{'Complained'}</Badge>,
}

const columns = [
  {
    id: 'id',
    name: <input
      type={'checkbox'}
      className={'big-checkbox'}
      name={'ID[]'}
      readOnly={true}
    />,
    render: ({ item }) => {
      return <input
        type={'checkbox'}
        className={'big-checkbox'}
        name={'ID[' + item.ID + ']'}
        readOnly={true}
      />
    }
  },
  {
    id: 'picture',
    name: '',
    render: ({ item }) => {
      return <img width={50} style={{ borderRadius: 50 }} className={'gravatar'} src={item.data.gravatar}
                  alt={'gravatar'}/>
    }
  },
  {
    id: 'name',
    name: 'Name',
    render: ({ item }) => {

      const { data, user } = item

      return ( <div className={'name-details'}>
        {data.first_name + ' ' + data.last_name }
        { user && <div className={'user-details'}>
          <FaIcon classes={ ['user'] }/> {user.data.user_login}
        </div> }
      </div>)
    }
  },
  {
    id: 'contact-info',
    name: 'Contact Info',
    render: ({ item }) => {

      return (
        <div className={'contact-info'}>
          <div className={'email'}>
            {item.data.email && <span className={'email'}>
                    <a href={'mailto:' + item.data.email}><FaIcon classes={['envelope-square']}/> {item.data.email}</a>
                  </span>}
          </div>
          {item.meta.primary_phone &&
          <div className={'phone'}>
            <a href={'tel:' + item.meta.primary_phone }>
              <span className={'phone'}><FaIcon classes={['phone-square']}/> {item.meta.primary_phone}</span>
              {item.meta.primary_phone_extension &&
              <span className={'ext'}> x{item.meta.primary_phone_extension}</span>}
            </a>
          </div>
          }
        </div>
      )
    }
  },
  {
    id: 'status',
    name: 'Status',
    render: ({ item }) => {
      return optinStatusMap[item.data.optin_status]
    }
  },
  {
    id: 'date_created',
    name: 'Date Added',
    render: ({ item }) => {
      return moment( item.data.date_created ).format('LLL')
    }
  }
]

const ContactsList = ({ fetching, query, contacts, error, fetchContacts, resetQuery, fetchMoreContacts, updateQuery, clearItems }) => {

  const loadMoreContacts = () => {
    updateQuery( {
      offset: query.offset + query.number
    } )

    fetchMoreContacts()
  }

  useEffect(() => {
    clearItems()
    resetQuery()
    fetchContacts()
  }, [])

  return (
    <div>
      <ListTable
        isLoading={fetching}
        items={contacts}
        columns={columns}
        fetchData={loadMoreContacts}
      />
    </div>
  )
}


const mapStateToProps = state => ({
  query: state.contactList.query,
  contacts: state.contactList.data,
  fetching: state.contactList.fetching,
  error: state.contactList.error
})

const ConnectedContactsList = connect(mapStateToProps, { fetchContacts, fetchMoreContacts, updateQuery, resetQuery, clearItems })(ContactsList)

export default {
  path: '/contacts',
  icon: 'user',
  title: 'Contacts',
  capabilities: [],
  exact: true,
  render: () => <div className={'contacts'}>
    <header className={'with-padding'}>
      <h2>{'Contacts'}</h2>
      <div className={'page-actions'}>
        <IconButton
          icon={'plus-circle'}
          variant={'icon-only'}
        />
      </div>
    </header>
    <ConnectedContactsList/>
  </div>
}