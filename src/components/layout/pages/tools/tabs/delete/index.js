import { addFilter, applyFilters } from '@wordpress/hooks'
import { __ } from '@wordpress/i18n'
import { Route, Switch, useHistory, useLocation, useRouteMatch } from 'react-router-dom'
import React from 'react'
import Box from '@material-ui/core/Box'
import Grid from '@material-ui/core/Grid'
import Card from '@material-ui/core/Card'
import CardContent from '@material-ui/core/CardContent'
import Typography from '@material-ui/core/Typography'
import CardActions from '@material-ui/core/CardActions'
import Button from '@material-ui/core/Button'
import { useState } from '@wordpress/element'
import { addNotification } from 'utils/index'
import BulkJob from 'components/core-ui/bulk-job'
import { TagPicker } from 'components/index'

export const DeleteContacts = (props) => {

  //get location details
  let history = useHistory()
  const location = useLocation()

  //build context for bulk-job operation
  let context = {
    tags_include: location.tags_include,
  }

  const onFinish = ({ finished, data }) => {
    // handle the response and do any tasks which are required.
    addNotification({ message: __('Contacts deleted successfully'), type: 'success' })
    history.goBack('/tools/sync')
  }

  if (!location.bulk_job) {
    history.goBack('/tools/delete')
  } else {
    return (
      <div style={{
        padding: 24,
        background: '#fff',
      }}>
        <BulkJob
          jobId={Math.random()}
          perRequest={10}
          title={__('Deleting contacts', 'groundhogg')}
          context={context}
          onFinish={onFinish}
          action={'gh_delete_contacts'}
        />
      </div>
    )
  }

  //default case
  return (<p>loading...</p>)

}

export const DeletePage = (props) => {

  //state to get the data
  const [tags, setTags] = useState([])

  //get location details
  let history = useHistory()
  let { path } = useRouteMatch()

  const handleDeleteContacts = () => {

    let tags_include = []

    if (tags !== null) {
      tags_include = tags.map((tag) => tag.value)
    }

    history.push({
      pathname: path + '/delete-contacts',
      bulk_job: true,
      tags_include: tags_include
    })
  }

  return (
    <Box style={{ marginTop: 20 }}>
      <Grid container spacing={2}>
        <Grid item xs={12} sm={6} md={6} lg={6}>
          <Card>
            <CardContent>
              <Typography gutterBottom variant="h5" component="h2">
                {__('Delete Users', 'groundhogg')}
              </Typography>
              {/*<Typography variant="body2" color="textSecondary" component="p">*/}
              <TagPicker onChange={setTags} value={tags}/>
              {/*</Typography>*/}
              <Typography variant="body2" color="textSecondary" component="b">
                { __('⚠ Once you click the delete button there is no going back! ' ,'groundhogg') }
              </Typography>
            </CardContent>
            <CardActions>
              <Button variant="contained" size="large" color="primary" onClick={handleDeleteContacts}>
                {__('Delete Contacts')}
              </Button>
            </CardActions>
          </Card>
        </Grid>
      </Grid>
    </Box>
  )
}

export const Delete = (props) => {

  let { path } = useRouteMatch()
  return (
    <Switch>
      <Route exact path={path}>
        <DeletePage/>
      </Route>
      <Route exact path={`${path}/delete-contacts`}>
        <DeleteContacts/>
      </Route>

    </Switch>
  )
}

//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
  tabs.push({
    title: __('Bulk Delete', 'groundhogg'),
    path: '/delete',
    description: __('First Description', 'groundhogg'), component: (classes) => {
      return (
        <Delete/>
      )
    }
  })
  return tabs

}, 10)