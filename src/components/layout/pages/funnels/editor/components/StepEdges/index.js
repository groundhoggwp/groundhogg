import Xarrow from 'react-xarrows'
import {
  ACTION, BENCHMARK,
  CONDITION,
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import { makeStyles } from '@material-ui/core/styles'

const useStyles = makeStyles((theme) => ({
  edgeLabel: {
    background: '#ffffff',
    padding: theme.spacing(1),
    border: '1px solid',
    borderRadius: 3,
  },
  edgeNo: {
    background: '#F8D7DA',
    borderColor: '#f5c6cb',
    color: '#721c24'
  },
  edgeYes: {
    background: '#d4edda',
    borderColor: '#c3e6cb',
    color: '#155724'
  }
}))

export default ({ data, meta, ID }) => {

  const { parent_steps, child_steps, step_group } = data
  const { edgeLabel, edgeYes, edgeNo } = useStyles();

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

  let parents, children;

  parents = Object.values( parent_steps );
  children = Object.values( child_steps );

  if ( parents.length > 1) {

    arrows.push({
      ...arrowStyle,
      start: `add-step-top-${ ID }`,
      end: `step-card-${ ID }`,
      // headSize: 0,
    })

    parents.forEach( parent => {

      arrows.push( {
        ...arrowStyle,
        start: `add-step-bottom-${ parent }`,
        end: `add-step-top-${ ID }`,
        headSize: 0,
      } )

    } )

  } else {
    parents.forEach( parent => {
      arrows.push( {
        ...arrowStyle,
        start: `add-step-bottom-${ parent }`,
        end: `step-card-${ ID }`,
      } )
    } )
  }

  switch (step_group) {
    case ACTION:


      arrows.push({
        ...arrowStyle,
        start: `step-card-${ ID }`,
        end: `add-step-bottom-${ ID }`,
        headSize: 0,
      })

      if (!children.length) {
        arrows.push({
          ...arrowStyle,
          start: `add-step-bottom-${ ID }`,
          end: `step-exit`,
        })
      }

      break
    case CONDITION:

      arrows.push({
        ...arrowStyle,
        start: `step-card-${ ID }`,
        end: `add-step-no-${ ID }`,
        endAnchor: ['top', 'middle'],
        headSize: 0,
        label: {
          middle: (
            <div className={[edgeLabel, edgeNo].join( ' ' )}>
              No
            </div>
          )
        }
      })

      arrows.push({
        ...arrowStyle,
        start: `step-card-${ ID }`,
        end: `add-step-yes-${ ID }`,
        endAnchor: ['top', 'middle'],
        headSize: 0,label: {
          middle: (
            <div className={[edgeLabel, edgeYes].join( ' ' )}>
              Yes
            </div>
          )
        }

      })

      arrows.push({
        ...arrowStyle,
        startAnchor: ['bottom', 'middle'],
        start: `add-step-no-${ ID }`,
        end: child_steps.no ? `step-card-${ child_steps.no }` : 'step-exit',
      })

      arrows.push({
        ...arrowStyle,
        startAnchor: ['bottom', 'middle'],
        start: `add-step-yes-${ ID }`,
        end: child_steps.yes ? `step-card-${ child_steps.yes }` : 'step-exit',
      })


      break
    case BENCHMARK:

      arrows.push({
        ...arrowStyle,
        start: `step-card-${ ID }`,
        end: `add-step-bottom-${ ID }`,
        headSize: 0,
      })

      arrows.push({
        ...arrowStyle,
        start: `step-card-${ ID }`,
        end: `add-step-right-${ ID }`,
        headSize: 0,
        startAnchor: ['right', 'middle'],
        endAnchor: ['left', 'middle'],
      })

      if (!children.length) {
        arrows.push({
          ...arrowStyle,
          start: `add-step-bottom-${ ID }`,
          end: `step-exit`,
        })
      }

      break
  }

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