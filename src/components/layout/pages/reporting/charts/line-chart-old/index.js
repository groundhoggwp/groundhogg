import { registerChartType } from 'data/reports-registry'
// import lineChartConfig from 'components/core-ui/chart/chart-config/line-chart-config'
import Chartjs from 'chart.js'
import Card from '@material-ui/core/Card'
import { useRef, useState, useEffect } from '@wordpress/element'
import { makeStyles } from '@material-ui/core/styles'
import { isObject } from 'utils/core'
import CardContent from '@material-ui/core/CardContent'
import Typography from '@material-ui/core/Typography'

const useStyles = makeStyles((theme) => ({
  root: {
    // padding: theme.spacing(2)
  },
  loading: {
    filter: 'blur(5px)'
  },
  chartContainer: {
    position: 'relative',
    height: 300,
    width: '100%'
  }
}))

export const LineChart = ({ id, title, report, loading }) => {
  const [chartInstance, setChartInstance] = useState(null);
  const classes = useStyles()

  const chartContainer = useRef(null)
  const { chart } = report

  const chartConfig = {
    ...lineChartConfig,
    data: isObject(chart) ? chart.data : {}
  }

  useEffect(() => {
    if (chartContainer && chartContainer.current) {
      const newChartInstance = new Chartjs(chartContainer.current, chartConfig)
    }
  }, [report, chartContainer])

  console.log('asdfasdfasdfasdf')

  return (
    <Card className={classes.root}>
      <CardContent>
        <Typography variant="h5" component="h2">
          {title}
        </Typography>
        <div className={classes.chartContainer}>
          <canvas width={'100%'} height={300} className={`Chart__canvas ${id}`} ref={chartContainer}/>
        </div>
      </CardContent>
    </Card>
  )
}

registerChartType('line_chart', LineChart)
