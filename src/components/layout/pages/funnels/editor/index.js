import { Card } from '@material-ui/core'
import Box from '@material-ui/core/Box'
import LineTo from 'react-lineto'

const Step = ({ data }) => {
  return (
    <Box style={{padding:10}}>
      <Card style={ { maxWidth: 250, padding:10 } }>
        { data.step_title }
      </Card>
    </Box>
  )
}

const ChartLine = ({from, to}) => {
  return (
    <LineTo
      from={from}
      to={to}
      borderWidth={3}
      borderStyle={'solid'}
      borderColor={'#e5e5e5'}
    />
  )
}

export default (props) => {

  const { funnel } = props
  const { data, steps, meta, stats } = funnel

  let maxOrder = Math.max( ...steps.map( step => parseInt( step.data.step_order ) ) )
  let order = 1;

  const chart = [];

  while ( order <= maxOrder ){
    chart.push( steps.filter( step => parseInt( step.data.step_order ) === order ) )
    order++;
  }

  // console.log( chart )

  return (
    <>
      {
        chart.map( __steps => {
          return (
            <Box display={'flex'} justifyContent={'center'}>
              {
                __steps.map( _step => <Step {..._step}/>)
              }
            </Box>
          )
        })
      }
      {
        steps.map( _step => {
          return (
            <>
              {
                _step.data.next_steps.map( __stepId => <ChartLine from={_step.ID} to={__stepId}/>)
              }
            </>
          )
        } )
      }
    </>
  )

}