import Xarrow from 'react-xarrows'
import Fab from '@material-ui/core/Fab'
import AddIcon from '@material-ui/icons/Add'
import AddStepButton from '../components/AddStepButton'
import {
  isBenchmark,
  numChildren,
  numParents,
} from 'components/layout/pages/funnels/editor/functions'

export const ACTION = 'action'
export const ACTIONS = 'actions'
export const BENCHMARK = 'benchmark'
export const BENCHMARKS = 'benchmarks'
export const CONDITION = 'condition'
export const CONDITIONS = 'conditions'

const ARROW_HEAD_SIZE = 5

export const ARROW_STYLE = {
  startAnchor: ['bottom', 'middle'],
  endAnchor: ['top', 'middle'],
  strokeWidth: 2,
  path: 'smooth',
  color: '#cbcbcb',
  curveness: 1,
  headSize: ARROW_HEAD_SIZE,
}

export const ADD_STEP_BUTTON_X_OFFSET = 20
export const ADD_STEP_BUTTON_Y_OFFSET = 30
export const CARD_WIDTH = 250
export const CARD_HEIGHT = 136

export const ACTION_TYPE_DEFAULTS = {

  Edges: ({ data, meta, ID, graph }) => {
    // Benchmarks should only ever have 1 child...
    // can have multiple parents though!

    const { parent_steps, child_steps, step_group } = data
    let children = Object.values(child_steps)
    let parents = Object.values(parent_steps)

    const arrows = []

    // This will always be present
    arrows.push({
      ...ARROW_STYLE,
      start: `step-card-${ ID }`,
      end: `add-step-below-${ ID }`,
      headSize: 0,
    })

    // Nodes from the exit point of this node to the children
    // There should only ever be 1 child
    children.forEach(child => {
      arrows.push({
        ...ARROW_STYLE,
        start: `add-step-below-${ ID }`,
        end: numParents(child, graph) > 1
          ? `add-step-above-${ child }`
          : `step-card-${ child }`,
        headSize: numParents(child, graph) > 1 ? 0 : ARROW_HEAD_SIZE,
      })
    })

    if (parents.length > 1) {

      // If there are multiple parent we need an edge from the add step button
      // to the top of the card

      arrows.push({
        ...ARROW_STYLE,
        start: `add-step-above-${ ID }`,
        end: `step-card-${ ID }`,
      })
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
  },

  Targets: ({ data, meta, ID, graph, xOffset }) => {

    const targets = []

    const { parent_steps, child_steps, step_group } = data
    let children = Object.values(child_steps)
    let parents = Object.values(parent_steps)

    let thisNode = graph.node(ID)

    // If there are multiple parents a target must be placed above
    if (parents.length > 1) {

      let allowedGroups = [
        ACTIONS,
        CONDITIONS,
        BENCHMARKS,
      ]

      // cannot include benchmarks if the parents have benchmarks in them...
      // other steps are legal
      if (parents.filter(id => {
        return isBenchmark( id, graph );
      }).length) {
        allowedGroups = allowedGroups.filter(group => group !== BENCHMARKS)
      }

      targets.push({
        id: `add-step-above-${ ID }`,
        groups: allowedGroups,
        parents: parent_steps,
        children: [ID],
        position: {
          // Todo calculate correct value here
          x: thisNode.x + ( CARD_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET,
          y: thisNode.y - ( ADD_STEP_BUTTON_Y_OFFSET * 2 ),
        },
      })
    }

    let allowedGroups = [
      ACTIONS,
      CONDITIONS,
      BENCHMARKS,
    ]

    // cannot include benchmarks if the parents have benchmarks in them...
    // other steps are legal
    if (children.filter(id => {
      return isBenchmark( id, graph )
    }).length) {
      allowedGroups = allowedGroups.filter(group => group !== BENCHMARKS)
    }

    targets.push({
      id: `add-step-below-${ ID }`,
      groups: allowedGroups,
      parents: [ID],
      children: child_steps,
      position: {
        x: thisNode.x + ( CARD_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET,
        y: thisNode.y + CARD_HEIGHT + ADD_STEP_BUTTON_Y_OFFSET,
      },
    })
    return (
      <>
        {
          targets.map(({ id, position, groups, parents, children }) =>
            <AddStepButton
              id={ id }
              groups={ groups }
              parents={ parents }
              children={ children }
              position={ {
                x: position.x + xOffset,
                y: position.y,
              } }
            />)
        }
      </>
    )
  },

}

export const BENCHMARK_TYPE_DEFAULTS = {

  Edges: ({ data, meta, ID, graph }) => {

    // Benchmarks should only ever have 1 child...
    // can have multiple parents though!

    const { parent_steps, child_steps, step_group } = data
    let children = Object.values(child_steps)
    let parents = Object.values(parent_steps)

    const arrows = []

    // This will always be present
    arrows.push({
      ...ARROW_STYLE,
      start: `step-card-${ ID }`,
      end: `add-step-below-${ ID }`,
      headSize: 0,
    })

    // Nodes from the exit point of this node to the children
    // There should only ever be 1 child
    children.forEach(child => {
      arrows.push({
        ...ARROW_STYLE,
        start: `add-step-below-${ ID }`,
        end: numParents(child, graph) > 1
          ? `add-step-above-${ child }`
          : `step-card-${ child }`,
        headSize: numParents(child, graph) > 1 ? 0 : ARROW_HEAD_SIZE,
      })
    })

    if (parents.length > 1) {

      // If there are multiple parent we need an edge from the add step button
      // to the top of the card

      arrows.push({
        ...ARROW_STYLE,
        start: `add-step-below-${ ID }`,
        end: `step-card-${ ID }`,
      })
    }
    else {

      // The add beside only applicable in cases where there is at most 1
      // parent step
      arrows.push({
        ...ARROW_STYLE,
        start: `step-card-${ ID }`,
        end: `add-step-beside-${ ID }`,
        headSize: 0,
        startAnchor: ['right', 'middle'],
        endAnchor: ['left', 'middle'],
      })
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
  },

  Targets: ({ data, meta, ID, graph, xOffset }) => {

    const targets = []

    const { parent_steps, child_steps, step_group } = data
    let children = Object.values(child_steps)
    let parents = Object.values(parent_steps)

    let thisNode = graph.node(ID)

    // If there are multiple parents a target must be placed above
    if (parents.length > 1) {

      targets.push({
        id: `add-step-above-${ ID }`,
        groups: [
          ACTIONS,
          CONDITIONS,
        ],
        parents: parent_steps,
        children: [ID],
        position: {
          x: thisNode.x + ( CARD_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET,
          y: thisNode.y - ( ADD_STEP_BUTTON_Y_OFFSET * 2 ),
        },
      })
    }
    else {
      targets.push({
        id: `add-step-beside-${ ID }`,
        groups: [
          BENCHMARKS,
        ],
        parents: parent_steps,
        children: child_steps,
        position: {
          // Todo calculate correct value here
          x: thisNode.x + CARD_WIDTH + ADD_STEP_BUTTON_Y_OFFSET,
          y: thisNode.y + ( CARD_HEIGHT / 2 ) - ADD_STEP_BUTTON_X_OFFSET,
        },
      })
    }

    targets.push({
      id: `add-step-below-${ ID }`,
      groups: [
        ACTIONS,
        CONDITIONS,
      ],
      parents: [ID],
      children: child_steps,
      position: {
        x: thisNode.x + ( CARD_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET,
        y: thisNode.y + CARD_HEIGHT + ADD_STEP_BUTTON_Y_OFFSET,
      },
    })

    return (
      <>
        {
          targets.map(({ id, position, groups, parents, children }) =>
            <AddStepButton
              id={ id }
              groups={ groups }
              parents={ parents }
              children={ children }
              position={ {
                x: position.x + xOffset,
                y: position.y,
              } }
            />)
        }
      </>
    )
  },

}