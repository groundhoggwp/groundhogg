import { registerChartType } from 'data/reports-registry'
import Card from '@material-ui/core/Card'
import { makeStyles } from '@material-ui/core/styles'
import ArrowDropUpIcon from '@material-ui/icons/ArrowDropUp'
import ArrowDropDownIcon from '@material-ui/icons/ArrowDropDown'
import { isObject } from 'utils/core'

const useStyles = makeStyles((theme) => ({
  root: {
    // display: 'inline-block',
    position: 'relative'
  },
  title: {
    display: 'block',
    fontSize: '18px',
    textTransform: 'capitalize',
    padding: '10px 5px 5px 10px',
    // color: '#ffffff',
    // background: theme.palette.primary.main,
    marginBottom: '10px'
  },
  current: {
    fontSize: '50px',
    fontWeight: 900
  },
  compareArrow: {
    position: 'absolute',
    left: '-7px',
    bottom: '-15px',
    fontSize: '50px'
  },
  compare: {
    position: 'absolute',
    left: '85px',
    bottom: '0',
    fontSize: '12px'
    // fontWeight: 900
  },
  percent: {
    position: 'absolute',
    // left: '-45px',
    bottom: '-2px',
    fontSize: '18px',
    fontWeight: 700
  }
}))

const dummyData = {
  chart: {
    data: {
      current: 0,
      compare: 0
    },
    number: 0,
    compare: {
      text: '',
      percent: true,
      arrow: {
        direction: 'up',
        color: 'green',
      }
    }
  }
}

export const QuickStat = ({ id, title, data, loading }) => {

  const classes = useStyles()

  const chartData = loading ? dummyData : data

  // console.debug( chartData )

  const { current, compare } = chartData.chart.data
  const { number } = chartData.chart.number

  const { text, percent } = chartData.chart.compare
  const { direction, color } = chartData.chart.compare.arrow

  const percentPosition = (-15 * (percent.length - 1) - 5) + 'px'
  const arrow = direction === 'up' ? <ArrowDropUpIcon style={{ color }} className={classes.compareArrow}/> :
    <ArrowDropDownIcon style={{ color }} className={classes.compareArrow}/>

  return (
    <Card className={classes.root}>
      <div className={classes.title}>
        {title}
      </div>
      <div className={classes.current}>
        {current}
      </div>
      <div className={classes.currentMetric}>
        {number}
      </div>
      {arrow}
      <div className={classes.compare}>
        <div style={{ left: percentPosition }} className={classes.percent}>{percent}</div>
        {text}
      </div>
    </Card>
  )
}

registerChartType('quick_stat', QuickStat)