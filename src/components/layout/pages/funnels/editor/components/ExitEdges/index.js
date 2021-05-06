import Xarrow from 'react-xarrows'
import {
  ACTION, BENCHMARK,
  CONDITION,
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import { makeStyles } from '@material-ui/core/styles'

export default ({ endingSteps }) => {

  const arrows = []

  const arrowStyle = {
    startAnchor: ['bottom', 'middle'],
    endAnchor: ['top', 'middle'],
    strokeWidth: 2,
    path: 'smooth',
    color: '#cbcbcb',
    curveness: 1,
    headSize: 5,
  }

  let numEndSteps = endingSteps.length

  if (numEndSteps > 1 || endingSteps[0].data.step_group === CONDITION) {
    arrows.push({
      ...arrowStyle,
      start: `add-step-top-exit`,
      end: `step-exit`,
    })
  }

  endingSteps.forEach((step) => {

    if ( step.data.step_group === CONDITION ){
      return;
    }

    arrows.push({
      ...arrowStyle,
      start: `add-step-bottom-${ step.ID }`,
      end: numEndSteps > 1 ? 'add-step-top-exit' : 'step-exit',
      headSize: 0,
    })
  })

  return (
    <>
      {
        arrows.map((arrow, i) => <Xarrow
          key={ i }
          { ...arrow }
        />)
      }
    </>
  )
}