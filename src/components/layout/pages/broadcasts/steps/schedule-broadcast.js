import BulkJob from 'components/core-ui/bulk-job'
import { __ } from '@wordpress/i18n'
import { BROADCASTS_STORE_NAME } from 'data/broadcasts'
import { useSelect, useDispatch } from '@wordpress/data'
import Button from '@material-ui/core/Button'
import { Link } from 'react-router-dom'
import { Fragment, useState } from '@wordpress/element'
import { mergeDeep } from 'utils/core'
import TagPicker from 'components/core-ui/tag-picker'
import TextField from '@material-ui/core/TextField'
import EmailPicker from 'components/core-ui/email-picker'
import FormControlLabel from '@material-ui/core/FormControlLabel'
import Checkbox from '@material-ui/core/Checkbox'
import Typography from '@material-ui/core/Typography'
import React from 'react'

// sets data into the broadcast data option
export const ScheduleBroadcast = (props) => {

  // createItem({
  //     data: {
  //         email_or_sms_id: 474,
  //         tags: [156],
  //         exclude_tags: [],
  //         // date: date,
  //         // time: time,
  //         send_now: true,
  //         // send_in_timezone: sendInTimezone,
  //         type: 'email',
  //     }
  // });

  const { handleNext, setData, data } = props

  const [tagsApplied, setTagsApplied] = useState([])
  const [tagsExclude, setTagsExclude] = useState([])
  const [object, setObject] = useState({})
  const [type, setType] = useState('email')  // do not support SMS yet
  const [date, setDate] = useState('')
  const [time, setTime] = useState('')
  const [sendNow, setSendNow] = useState('')

  const handleConfirm = () => {
    // merge the response.....
    let tags_applied = []

    if (tagsApplied !== null) {
      tags_applied = tagsApplied.map((tag) => tag.value)
    }

    let tags_exclude = []

    if (tagsExclude !== null) {
      tags_exclude = tagsExclude.map((tag) => tag.value)
    }

    setData({
      email_or_sms_id: object.value,
      tags: tags_applied,
      exclude_tags: tags_exclude,
      send_now: sendNow,
      type: type,
      date: date,
      time: time
    })

    // setData({
    //     ...data
    //     , ...{
    //         //Success Test
    //         email_or_sms_id: 474,
    //         tags: [156],
    //         exclude_tags: [],
    //         send_now: true,
    //         type: 'email',
    //
    //     // Error test
    //     // email_or_sms_id: 479,
    //     //     tags: [156],
    //     // exclude_tags: [],
    //     // send_now: true,
    //     // type: 'email',
    //
    //     }
    // });
    handleNext();
  }

  const handleDateChange = (event) => {
    setDate(event.target.value)
  }

  const handleTimeChange = (event) => {
    setTime(event.target.value)
  }

  return (
    <Fragment>
      <div style={{
        padding: 24,
        background: '#fff',

      }}>
        <h2>Schedule Broadcast</h2>

        <h3>Email ID</h3>
        <EmailPicker onChange={setObject}/>

        <h3> Apply Tags </h3>
        <TagPicker onChange={setTagsApplied} value={tagsApplied}/>

        <h3> Exclude Tags </h3>
        <TagPicker onChange={setTagsExclude} value={tagsExclude}/>

        <h3> Send on </h3>

        <FormControlLabel
          control={<Checkbox
            color="primary"
            checked={sendNow}
            onChange={(event) => {
              if (sendNow) {
                setSendNow(false)
              } else {
                setSendNow(true)
              }
            }}/>}
          label={'Send Now'}
          labelPlacement="end"
        />

        <TextField
          id="date"
          label={__('Date')}
          type="date"
          value={date}
          onChange={handleDateChange}
          InputLabelProps={{
            shrink: true,
          }}
          InputProps={{ inputProps: { min: '2020-10-29' } }}
          disabled={sendNow}
        />

        <TextField
          id="time"
          label={__('Time', 'groundhogg')}
          type="time"
          defaultValue="09:00"
          value={time}
          onChange={handleTimeChange}
          InputLabelProps={{
            shrink: true,
          }}
          inputProps={{
            step: 300, // 5 min
          }}
          disabled={sendNow}
        />

      </div>

      <div style={{
        padding: 24,
        background: '#fff',
        marginTop: 20

      }}>
        <Button variant="contained" color="primary"
                onClick={handleConfirm}>{__('Confirm', 'groundhogg')}</Button>
      </div>
    </Fragment>

  )
}