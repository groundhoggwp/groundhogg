import { addFilter, applyFilters } from '@wordpress/hooks'
import { __ } from '@wordpress/i18n'
import Grid from '@material-ui/core/Grid'
import Card from '@material-ui/core/Card'
import CardContent from '@material-ui/core/CardContent'
import Typography from '@material-ui/core/Typography'
import CardActions from '@material-ui/core/CardActions'
import Button from '@material-ui/core/Button'
import Box from '@material-ui/core/Box'
import { Route, Switch, useRouteMatch } from 'react-router-dom'
import React from 'react'
import { CreateUserBulkJob } from 'components/layout/pages/tools/tabs/sync/create-user-bulk-job'
import { SyncUsersBulkJob } from 'components/layout/pages/tools/tabs/sync/sync-users-bulk-job'

export const SyncPage = (props) => {

  const handleSyncUsers = () => {
    //code to display sync user bulk job

  }

  const handleCreateUsers = () => {

  }

  return (
    <Box style={{ marginTop: 20 }}>
      <Grid container spacing={2}>
        <Grid item xs={12} sm={6} md={6} lg={6}>
          <Card>
            <CardContent>
              <Typography gutterBottom variant="h5" component="h2">
                {__('Sync Users & Contacts', 'groundhogg')}
              </Typography>
              <Typography variant="body2" color="textSecondary" component="p">
                {__('The sync process will create new contact records for all users in the database. If a contact records already exists then the association will be updated.', 'groundhogg')}
              </Typography>
            </CardContent>
            <CardActions>
              <Button variant="contained" size="large" color="primary" onClick={handleSyncUsers}>
                {__('Start Sync process')}
              </Button>
            </CardActions>
          </Card>
        </Grid>
        <Grid item xs={12} sm={6} md={6} lg={6}>
          <Card>
            <CardContent>
              <Typography gutterBottom variant="h5" component="h2">
                {__('Create Users', 'groundhogg')}
              </Typography>
              <Typography variant="body2" color="textSecondary" component="p">
                {__('', 'groundhogg')}
              </Typography>
            </CardContent>
            <CardActions>
              <Button variant="contained" size="large" color="primary" onClick={handleSyncUsers}>
                {__('Create Users')}
              </Button>
            </CardActions>
          </Card>
        </Grid>
      </Grid>
    </Box>
  )

}

export const Sync = (props) => {

  let { path } = useRouteMatch()
  return (
    <Switch>
      <Route exact path={path}>
        <SyncPage />
      </Route>
      <Route path={`${path}/sync-users`}>
        <SyncUsersBulkJob />
      </Route>
      <Route path={`${path}/create-users`}>
        <CreateUserBulkJob />
      </Route>
    </Switch>
  )

}

//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
  tabs.push({
    title: __('Sync', 'groundhogg'),
    path: '/sync',
    description: __('First Description', 'groundhogg'),
    component: (classes) => {
      return (
        <Sync/>
      )
    }
  })
  return tabs

}, 10)