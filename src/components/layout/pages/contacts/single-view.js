import React from 'react'
import { Fragment } from '@wordpress/element'
import {
  Link,
  useParams
} from 'react-router-dom'
import { makeStyles } from '@material-ui/core/styles'
import Paper from '@material-ui/core/Paper'
import Grid from '@material-ui/core/Grid'
import ContactTimeline from './contact-timeline'
import TabPanel from 'components/core-ui/tab-panel'
import { filter, forEach } from 'lodash'
import { applyFilters } from '@wordpress/hooks'
import { SettingsPanel } from 'components/layout/pages/settings/settings-panel'
import { Divider } from '@material-ui/core'
import { useSelect } from '@wordpress/data'
import { CONTACTS_STORE_NAME } from 'data/contacts'


import MailOutlineIcon from '@material-ui/icons/MailOutline'
import { ContactPanel } from 'components/layout/pages/contacts/contact-panel'
import TextField from '@material-ui/core/TextField'
import {GeneralInfo}  from './general-info'

const useStyles = makeStyles((theme) => ({
  root: {
    flexGrow: 1,
  },
  paper: {
    padding: theme.spacing(2),
    textAlign: 'center',
    color: theme.palette.text.secondary,
  },
  contactImage: {
    maxWidth: '150px',
    borderRadius: '50%',
    // boxShadow: '1px 1px 4px #ccc',

  }
}))

const ContactMeta = (props) => {

}

const CustomInfo = (props) => {

  return (
    <h1> custom </h1>
  )

}

//
// const useStyles = makeStyles((theme) => ({
//   root: {
//     width: '100%',
//     maxWidth: 360,
//     backgroundColor: theme.palette.background.paper,
//   },
// }));


const Segmentation = (props) => {
  return (
    <h1> Segementation </h1>
  )

}

export const ContactSection = ({ section }) => {
  return (
    <div>
      <h3>{section.title}</h3>
      <Divider/>
      <section.component/>
    </div>
  )

}

export const SingleView = (props) => {
  const classes = useStyles()
  let { id } = useParams()

  const { match, history } = props

  const { contact } = useSelect((select) => {
    const store = select(CONTACTS_STORE_NAME)
    return {
      contact: store.getItem(id)
    }
  }, [])

  if (Object.keys(contact).length === 0 && contact.constructor === Object) {
    return (<h1> Loading... </h1>)
  }

  const { data, meta } = contact

  const tabs = [
    {
      label: 'General Info ',
      route: 'general',
      component: () => {
        return (
          <GeneralInfo contact={contact}/>
        )
      }
    },
    {
      label: 'Custom Info',
      route: 'custom',
      component: () => {
        return (
          <CustomInfo contact={contact}/>
        )
      }
    },
    {
      label: 'Segmentation',
      route: 'segmentation',
      component: () => {
        return (
          <Segmentation contact={contact}/>
        )
      }
    }
  ]


  return (
    <Fragment>
      {/*<p>Single view: Contact ID: {id}</p>*/}
      <Grid container spacing={3}>
        <Grid item xs={12} md={3} lg={3}>
          <Grid container spacing={2}>
            <Grid item xs={12} md={12} lg={12}>
              <img className={classes.contactImage}
                   src={meta.profile_picture}/>
              <h3> {data.first_name + ' ' + data.last_name} </h3>
              <p> {data.email}</p>
            </Grid>
          </Grid>
        </Grid>
        <Grid item xs={12} md={7} lg={7}>

            <Fragment>
              <TabPanel tabs={tabs} enableRouting={false} history={history} match={match}/>
            </Fragment>
          
        </Grid>
        <Grid item xs={12} md={2} lg={2}>
          <Paper>
            <ContactTimeline/>
          </Paper>
        </Grid>
      </Grid>
    </Fragment>
  )
}
