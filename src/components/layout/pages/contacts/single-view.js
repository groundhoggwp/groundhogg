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
import { GeneralInfo } from './general-info'
import { CustomInfo } from './custom-info'
import { Segmentation } from './segmentation'
import { ContactNotes } from 'components/layout/pages/contacts/contact-notes'
import { Files } from 'components/layout/pages/contacts/files'

import Accordion from '@material-ui/core/Accordion'
import AccordionSummary from '@material-ui/core/AccordionSummary'
import AccordionDetails from '@material-ui/core/AccordionDetails'
import Typography from '@material-ui/core/Typography'
import ExpandMoreIcon from '@material-ui/icons/ExpandMore'
import { Actions } from 'components/layout/pages/contacts/actions'

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

  },
  heading: {
    fontSize: theme.typography.pxToRem(15),
    fontWeight: theme.typography.fontWeightRegular,
  },

}))

//
// const useStyles = makeStyles((theme) => ({
//   root: {
//     width: '100%',
//     maxWidth: 360,
//     backgroundColor: theme.palette.background.paper,
//   },
// }));

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

  var detailsTab = [
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
    },

  ]

  var AdditionalInfo = [
    {
      label: 'Notes',
      route: 'notes',
      component: () => {
        return (
          <ContactNotes contact={contact}/>
        )
      }
    },
    {
      label: 'Files',
      route: 'files',
      component: () => {
        return (
          <Files contact={contact}/>
        )
      }
    },

  ]

  var contactSections = [
    {
      label: 'Details ',
      component: () => {
        return (
          <TabPanel tabs={detailsTab} enableRouting={false} history={history} match={match}/>
        )
      }
    }, {
      label: 'Additional Info ',
      component: () => {
        return (
          <TabPanel tabs={AdditionalInfo} enableRouting={false} history={history} match={match}/>
        )
      }
    },
  ]

  contactSections = applyFilters('groundhogg.contacts.sections', contactSections)

  return (
    <Fragment>
      {/*<p>Single view: Contact ID: {id}</p>*/}
      <Grid container spacing={0}>
        <Grid item xs={12} md={3} lg={3}>
          <SideBar contact={contact}/>
        </Grid>
        <Grid item xs={12} md={7} lg={7}>
          <Fragment>
            {contactSections.map((section) => {

              return (
                <Accordion defaultExpanded>
                  <AccordionSummary
                    expandIcon={<ExpandMoreIcon/>}
                    aria-controls="panel1a-content"
                  >
                    <Typography variant="h5" component="h5" style={{ marginTop: 10 }}
                    >{section.label}</Typography>
                    {/*<h2> {section.label} </h2>*/}
                    {/*<Typography className={classes.heading}>{section.label}</Typography>*/}
                  </AccordionSummary>
                  <AccordionDetails style={{ padding: 0 }}>
                    <section.component/>
                  </AccordionDetails>
                </Accordion>
              )
            })}
          </Fragment>
        </Grid>

        <Grid item xs={12} md={2} lg={2}>
          <ContactTimeline/>
        </Grid>
      </Grid>
    </Fragment>
  )
}

export const SideBar = (props) => {

  const classes = useStyles()
  const { meta, data } = props.contact

  return (

    <Fragment>
      <img className={classes.contactImage}
           src={meta.profile_picture}/>
      <h3> {data.first_name + ' ' + data.last_name} </h3>
      <p> {data.email}</p>
      <Actions/>
    </Fragment>
  )
}
