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

export const Actions = (props) => {

  let actions = [

    {
      label: 'General Info ',
      route: 'general',
      component: () => {
        return <SendEmails/>
      }
    },

  ]

  let contactActions = applyFilters('groundhogg.contacts.actions', actions)
  // let contactActions = actions

  return (
    <Fragment>

      {contactActions.map((action) => {
        return <action.component/>
      })}

    </Fragment>

  )

}

export const SendEmails = (props) => {
  const [open, setOpen] = useState(false)
  const [email, setEmail] = useState({})
  let { id } = useParams()
  const { sendEmailById } = useDispatch(EMAILS_STORE_NAME)

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
      addNotification({ message: __('Email Scheduled.' ,'groundhogg' ), type: 'success' })
    }

    setOpen(false)

  }

  return (
    <div>
      <Tooltip title={__('Send Email', 'groundhogg')}>
        <IconButton aria-label={__('Send Email', 'groundhogg')} onClick={handleClickOpen} style={{
          bgcolor: 'background.paper',
          borderColor: 'text.primary',
          m: 1,
          border: 1,
          style: { width: '5rem', height: '5rem' },
        }}>


          <EmailIcon color={'primary'}/>
        </IconButton>
      </Tooltip>


      {/*<Button*/}
      {/*  variant="outlined"*/}
      {/*  color="primary"*/}
      {/*  size="large"*/}
      {/*  onClick={handleClickOpen}*/}
      {/*  // className={classes.button}*/}
      {/*  startIcon={<EmailIcon />}*/}
      {/*>*/}
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