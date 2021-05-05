import { makeStyles } from '@material-ui/core/styles'
import Box from '@material-ui/core/Box'

const useStyles = makeStyles((theme) => ({
  root: {
    marginTop: theme.spacing(2),
    marginBottom: theme.spacing(1)
  }
}))

export default ({ children }) => {

  const classes = useStyles()

  return (<>
    <Box className={classes.root}>
      {children}
    </Box>
  </>)
}