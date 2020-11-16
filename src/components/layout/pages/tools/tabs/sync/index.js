import { addFilter, applyFilters } from '@wordpress/hooks'
import { __ } from '@wordpress/i18n'
import Grid from '@material-ui/core/Grid'
import Card from '@material-ui/core/Card'
import CardContent from '@material-ui/core/CardContent'
import Typography from '@material-ui/core/Typography'
import CardActions from '@material-ui/core/CardActions'
import Button from '@material-ui/core/Button'
import Box from '@material-ui/core/Box'
import { Route, Switch, useHistory, useRouteMatch } from 'react-router-dom'
import React from 'react'
import { CreateUser } from './create-users'
import { SyncUsers } from './sync-users'
import Checkbox from '@material-ui/core/Checkbox'
import FormControlLabel from '@material-ui/core/FormControlLabel'
import { useState } from '@wordpress/element'

export const SyncPage = (props) => {

  const [syncMeta, setSyncMeta] = useState(false)

  let history = useHistory()
  let { path } = useRouteMatch()

  const handleSyncUsers = () => {
    //code to display sync user bulk job and sends the data about sync meta
    history.push({
      pathname: path + '/sync-users',
      bulk_job: true,
      sync_meta: syncMeta
    })

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
                <br/>
                <FormControlLabel
                  control={<Checkbox
                    color="primary"
                    checked={syncMeta}
                    onChange={(event) => {
                      if (syncMeta) {
                        setSyncMeta(false)
                      } else {
                        setSyncMeta(true)
                      }
                    }}/>}
                  label={'Sync all user meta.'}
                  labelPlacement="end"
                />
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
        <SyncPage/>
      </Route>
      <Route path={`${path}/sync-users`}>
        <SyncUsers/>
      </Route>
      <Route path={`${path}/create-users`}>
        <CreateUser/>
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