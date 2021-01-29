import React from 'react'
import { Fragment } from '@wordpress/element'
import {
  Link,
  useParams
} from 'react-router-dom'
import { makeStyles } from '@material-ui/core/styles'
import Paper from '@material-ui/core/Paper'
import Grid from '@material-ui/core/Grid'
import {ContactTimeline} from './contact-timeline'
import TabPanel from 'components/core-ui/tab-panel'
import { applyFilters } from '@wordpress/hooks'

import { useSelect } from '@wordpress/data'
import { CONTACTS_STORE_NAME } from 'data/contacts'

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
import Box from '@material-ui/core/Box'

const useStyles = makeStyles((theme) => ({
   imgRaised: {
    borderRadius: '50% !important',
    boxShadow:
      '0 5px 15px -8px rgba(0, 0, 0, 0.24), 0 8px 10px -5px rgba(0, 0, 0, 0.2)'
  },
  profile: {
    textAlign: 'center',
    '& img': {
      maxWidth: '150px',
      width: '100%',
      margin: '0 auto',
      transform: 'translate3d(0, -50%, 0)'
    }
  },

  actions: {
    marginTop: '-80px'
  },
  mainRaised: {
    background: '#FFFFFF',
    position: 'relative',
    zIndex: '3',
    borderRadius: '10px',

  },
  title: {
    // display: 'inline-block',
    position: 'relative',
    fontSize: 20,
    fontWeight: 'bold',
    textDecoration: 'none',
    marginTop:10


  },
  emailStyle :{
    position: 'relative',
    fontSize: 18,
    // fontWeight: 'bold',
    textDecoration: 'none',
    marginTop:8

  },

  navWrapper: {
    margin: '20px auto 50px auto',
    textAlign: 'center'
  }

}))


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

  ]

  detailsTab = applyFilters('groundhogg.contacts.details', detailsTab)

  var additionalInfo = [
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

  additionalInfo = applyFilters('groundhogg.contacts.additionalInfo', additionalInfo)

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
          <TabPanel tabs={additionalInfo} enableRouting={false} history={history} match={match}/>
        )
      }
    },
  ]

  contactSections = applyFilters('groundhogg.contacts.sections', contactSections)

  return (
    <Fragment>
      {/*<p>Single view: Contact ID: {id}</p>*/}
      <Grid container spacing={1}>
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

export const SideBar = ({ contact }) => {

  const classes = useStyles()
  const { meta, data } = contact

  return (
    <Fragment>
      <Grid container spacing={2}>
        <Grid item style={{ width: '100%', marginTop: 75 }}>
          <Box className={classes.mainRaised} boxShadow={2}>
            <div className={classes.profile}>
              <Grid container justify="center" spacing={2}>
                <Grid Item xs={12} sm={12} md={12}>
                  <div className={classes.profile}>
                    <img src={meta.profile_picture} alt="..." className={classes.imgRaised}/>
                    <div className={classes.actions}>
                      <Actions style={{ width: 100 }}/>
                    </div>
                  </div>
                </Grid>
                <Grid item>
                  {/*<Typography variant="h3" component="h3" style={{ marginTop: 10 }}*/}
                  {/*>{data.first_name + ' ' + data.last_name} </Typography>*/}
                  {/**/}
                  {/*<Typography variant="h6" component="h6" style={{ marginTop: 10 }}*/}
                  {/*>{data.email}</Typography>*/}
                  <div className={classes.title}> {data.first_name + ' ' + data.last_name} </div>
                  <div className={classes.emailStyle}>{data.email}</div>
                </Grid>
              </Grid>
            </div>
          </Box>
        </Grid>

        {/*<Grid item style={{ width: '100%' }}>*/}
        {/*  <Box boxShadow={2} className={classes.mainRaised}>*/}
        {/*    <Actions/>*/}
        {/*  </Box>*/}

        {/*</Grid>*/}

        <Grid item style={{ width: '100%' }}>
          <Box className={classes.mainRaised} style={{ padding: 5 }} boxShadow={2}>
            <div>
              {/*<div className={classes.profile}>*/}
              <Grid container justify="center">
                <Grid Item xs={12} sm={12} md={12}>
                  <div className={classes.profile}>
                    <Segmentation contact={contact}/>
                  </div>
                </Grid>
              </Grid>
              {/*</div>*/}
            </div>
          </Box>
        </Grid>
      </Grid>
    </Fragment>
  )
}
