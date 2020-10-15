import { Card } from '@material-ui/core'
import Box from '@material-ui/core/Box'
import LineTo from 'react-lineto'
import { useDispatch, useSelect } from '@wordpress/data'
import { STEPS_STORE_NAME } from '../../../../../data/steps'
import BenchmarkPicker from './components/BenchmarkPicker'
import AddStepButton from './components/AddStepButton'
import Paper from '@material-ui/core/Paper'
import './steps-types'

const Step = ({ ID, data }) => {

  const classNames = [
    data.step_group,
    data.step_type,
    data.step_order,
    ID,
  ]

  return (
    <Box style={ { padding: 10 } }>
      <Card style={ { width: 250, padding: 10 } }
            className={ classNames.join(' ') }>
        { data.step_title }
      </Card>
      <ChartLine from={ data.step_order } to={ parseInt(data.step_order) + 1 }/>
    </Box>
  )
}

const ChartLine = ({ from, to }) => {
  return (
    <LineTo
      from={ from }
      to={ to }
      borderWidth={ 3 }
      borderStyle={ 'solid' }
      borderColor={ '#e5e5e5' }
    />
  )
}

export default (props) => {

  const { funnel } = props
  const { ID, data } = funnel

  const stepQuery = {
    where: {
      funnel_id: ID,
    },
    limit: 999
  };

  const { fetchItems } = useDispatch( STEPS_STORE_NAME )
  const { steps } = useSelect((select) => {

    const store = select(STEPS_STORE_NAME)

    return {
      steps: store.getItems(stepQuery),
    }

  }, [])

  if ( ! steps ){
    return '...loading';
  }

  let maxOrder = Math.max(...steps.map(step => parseInt(step.data.step_order)))
  let order = 1

  const chart = []

  while (order <= maxOrder) {
    chart.push(steps.filter(step => parseInt(step.data.step_order) === order))
    order++
  }

  // console.log( chart )

  return (
    <>
      {
        chart.length === 0 && (
          <Box display={ 'flex' } justifyContent={ 'center' }>
            <Paper style={{width:500}}>
              <BenchmarkPicker/>
            </Paper>
          </Box>
        )
      }
      {
        chart.map(__steps => {
          return (
            <Box display={ 'flex' } justifyContent={ 'center' }>
              {
                __steps.map(_step => <Step { ..._step }/>)
              }
            </Box>
          )
        })
      }
    </>
  )

}