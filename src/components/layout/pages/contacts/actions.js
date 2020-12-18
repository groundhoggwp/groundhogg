import React from 'react'
import Button from '@material-ui/core/Button'
import TextField from '@material-ui/core/TextField'
import Dialog from '@material-ui/core/Dialog'
import DialogActions from '@material-ui/core/DialogActions'
import DialogContent from '@material-ui/core/DialogContent'
import DialogContentText from '@material-ui/core/DialogContentText'
import DialogTitle from '@material-ui/core/DialogTitle'
import { applyFilters } from '@wordpress/hooks'
import { Fragment, useState } from '@wordpress/element'
import EmailPicker from 'components/core-ui/email-picker'
import { __ } from '@wordpress/i18n'
import IconButton from '@material-ui/core/IconButton'
import EmailIcon from '@material-ui/icons/Email'
import Tooltip from '@material-ui/core/Tooltip/Tooltip'
import { useParams } from 'react-router-dom'
import { useDispatch } from '@wordpress/data'
import { EMAILS_STORE_NAME } from '../../../../data'
import { addNotification } from '../../../../utils'
import { Grid } from '@material-ui/core'
import { makeStyles } from '@material-ui/core/styles'
import Box from '@material-ui/core/Box'

const useStyles = makeStyles((theme) => ({
  action: {
    // backgroundColor: 'primary',

  },
  button: {
    height: 95, // setting height/width is optional
    width: 100,

    // width: '100%'
  },
  label: {
    // Aligns the content of the button vertically.
    flexDirection: 'column'
  },
  icon: {
    display: 'block',
    width: 50,
    height: 50,
    border: '2px solid #909090',
    borderColor :theme.palette.primary,
    borderRadius: 50,
    textAlign: 'center',

  }

}))

export const Actions = (props) => {

  let classes = useStyles()

  let actions = [
    {
      label: 'SendEmail',
      component: () => {
        return <SendEmails/>
      }
    },
  ]

  let contactActions = applyFilters('groundhogg.contacts.actions', actions, classes)
  // let contactActions = actions

  return (
    <Fragment>

      <Grid container spacing={2} justify="center">
        {contactActions.map((action) => {
          return (
            // <Grid item lg={3} md={3} sm={3} xs={3} className={classes.action}>
            <Grid item className={classes.action}>
              <action.component/>
            </Grid>

          )
        })}
      </Grid>
    </Fragment>
  )

}

export const SendEmails = (props) => {
  const [open, setOpen] = useState(false)
  const [email, setEmail] = useState({})
  let { id } = useParams()
  const { sendEmailById } = useDispatch(EMAILS_STORE_NAME)

  let classes = useStyles()

  const handleClickOpen = () => {
    setOpen(true)
  }

  const handleClose = () => {
    setOpen(false)
  }

  const handleSendEmail = () => {
    if (Object.keys(email).length !== 0) {
      // schedules the event
      sendEmailById(email.value, {
        id_or_email: id
      })
      addNotification({ message: __('Email Scheduled.', 'groundhogg'), type: 'success' })
    }

    setOpen(false)

  }

  return (

    <div>
      <Tooltip title={__('Send Email', 'groundhogg')}>
        <IconButton aria-label={__('Send Email', 'groundhogg')} onClick={handleClickOpen} className={classes.icon} >
          <EmailIcon color={'primary'}/>
        </IconButton>
      </Tooltip>


      {/*<Button*/}
      {/*  // classes={{ root: classes.button, label: classes.label }}*/}
      {/*  variant="raised"*/}
      {/*  color="primary"*/}
      {/*  disableRipple={true}*/}
      {/*  onClick={handleClickOpen}*/}

      {/*  className={classes.icon}*/}
      {/*>*/}
      {/*  <EmailIcon />*/}
      {/*  {__('Send Email', 'groundhogg')}*/}
      {/*</Button>*/}


      <Dialog open={open} onClose={handleClose} aria-labelledby="form-dialog-title" fullWidth={true} maxWidth={'md'}>
        <DialogTitle id="form-dialog-title">{__('Send Email', 'groundhogg')}</DialogTitle>
        <DialogContent>
          <DialogContentText>
            {__('Select Email', 'groundhogg')}
          </DialogContentText>
          <EmailPicker onChange={setEmail}/>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleClose} color="primary">
            {__('Cancel', 'groundhogg')}
          </Button>
          <Button onClick={handleSendEmail} color="primary">
            {__('Send Email', 'groundhogg')}
          </Button>
        </DialogActions>
      </Dialog>
    </div>
  )
}