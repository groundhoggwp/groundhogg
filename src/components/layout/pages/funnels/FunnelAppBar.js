import AppBar from '@material-ui/core/AppBar'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import FunnelStatus from 'components/layout/pages/funnels/FunnelStatus'
import { useSelect } from '@wordpress/data'
import Toolbar from '@material-ui/core/Toolbar'
import FunnelTitleEdit from 'components/layout/pages/funnels/FunnelTitleEdit'
import makeStyles from '@material-ui/core/styles/makeStyles'
import Box from '@material-ui/core/Box'
import Button from '@material-ui/core/Button'
import FunnelActions from 'components/layout/pages/funnels/FunnelActions'

const useStyles = makeStyles((theme) => ({
  appBar: {
    '& .MuiAppBar-colorPrimary': {
      color: '#000',
      backgroundColor: '#ffffff'
    }
  }
}))

export default ({ id, width }) => {

  const classes = useStyles()

  const { funnel } = useSelect((select) => {
    return {
      funnel: select(FUNNELS_STORE_NAME).getItem(id)
    }
  }, [])

  return (
    <div className={classes.appBar}>
      <AppBar style={{ top: 32, width: width }}>
        <Toolbar>
          <Box display={'flex'} justifyContent={ 'space-between' } flexGrow={1}>
            <Box flexGrow={2}>
              <FunnelTitleEdit id={id} data={funnel.data}/>
            </Box>
            <Box display={ 'flex' }  flexGrow={1} justifyContent={ 'flex-end' }>
              <FunnelStatus id={id} data={funnel.data || {}}/>
              <FunnelActions id={id} />
            </Box>
          </Box>
        </Toolbar>
      </AppBar>
    </div>
  )

}