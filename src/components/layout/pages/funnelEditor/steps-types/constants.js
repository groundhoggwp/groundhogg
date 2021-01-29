import { makeStyles } from '@material-ui/core/styles'
import Typography from '@material-ui/core/Typography'

export const ACTION = 'action'
export const ACTIONS = 'actions'
export const BENCHMARK = 'benchmark'
export const BENCHMARKS = 'benchmarks'
export const CONDITION = 'condition'
export const CONDITIONS = 'conditions'

const useStyles = makeStyles((theme) => ({
  root : {
    marginBottom: 35,
  },
  icon: {
    height: 35,
    width: 35,
    float: 'left',
  },
  read: {
    marginLeft: 70,
  },
  actionIcon : {
    backgroundColor: 'green',
    borderRadius: 50,
  },
  benchmarkIcon : {
    backgroundColor: 'orange',
    borderRadius: 5,
  },

  conditionIcon : {
    backgroundColor: 'purple',
    borderRadius: 5,
    transform: 'rotate(45deg)'
  }
}))

export const STEP_DEFAULTS = {
  icon: <></>,
  edit: ({}) => {
    return <></>
  },
  read: ({ ID, data }) => {
    return <>{ID}: {data.step_type}</>
  },
  flow: ({ icon, read }) => {

    const classes = useStyles()

    return <>
      <div className={classes.root}>
        <div className={classes.icon + ' ' + classes.actionIcon}>
          {icon}
        </div>
        <div className={classes.read}>
          <Typography variant={'p'} component={'div'}>
            {read}
          </Typography>
        </div>
      </div>
    </>
  }
}

export const BENCHMARK_TYPE_DEFAULTS = {
  ...STEP_DEFAULTS,

  flow: ({ icon, read }) => {

    const classes = useStyles()

    return <>
      <div className={classes.root}>
        <div className={classes.icon + ' ' + classes.benchmarkIcon}>
          {icon}
        </div>
        <div className={classes.read}>
          <Typography variant={'p'} component={'div'}>
            {read}
          </Typography>
        </div>
      </div>
    </>
  }
}

export const ACTION_TYPE_DEFAULTS = {
  ...STEP_DEFAULTS,
}