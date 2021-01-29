import LocalOfferIcon from '@material-ui/icons/LocalOffer'
import {
  ACTIONS, ADD_STEP_BUTTON_X_OFFSET, ADD_STEP_BUTTON_Y_OFFSET, ARROW_HEAD_SIZE,
  ARROW_STYLE,
  BENCHMARK, CARD_HEIGHT,
  CARD_WIDTH,
  CONDITION,
} from '../../constants'
import { registerStepType } from 'data/step-type-registry'
import Xarrow from 'react-xarrows'
import { makeStyles } from '@material-ui/core/styles'
import { NODE_HEIGHT, NODE_WIDTH } from 'components/layout/pages/funnels/editor'
import {
  BENCHMARKS,
  CONDITIONS, EXIT,
} from 'components/layout/pages/funnels/editor/steps-types/constants'
import Fab from '@material-ui/core/Fab'
import AddIcon from '@material-ui/icons/Add'
import AddStepButton
  from 'components/layout/pages/funnels/editor/components/AddStepButton'
import {
  getChildren, getEdgeChangesAbove,
  getParents,
  isBenchmark, NEW_STEP, numParents,
} from 'components/layout/pages/funnels/editor/functions'

const STEP_TYPE = 'yes_no_condition'

const useStyles = makeStyles((theme) => ( {
  edgeLabel: {
    background: '#ffffff',
    padding: theme.spacing(1),
    border: '1px solid',
    borderRadius: 3,
  },
  edgeNo: {
    background: '#F8D7DA',
    borderColor: '#f5c6cb',
    color: '#721c24',
  },
  edgeYes: {
    background: '#d4edda',
    borderColor: '#c3e6cb',
    color: '#155724',
  },
} ))

const stepAtts = {

  type: STEP_TYPE,

  group: CONDITION,

  name: 'Yes/No',

  icon: <LocalOfferIcon/>,

  view: ({ data, meta, stats }) => {
    return <></>
  },

  edit: ({ data, meta, stats }) => {
    return <></>
  },

  edgesFilter: (edges) => {
    // return edges

    let to = null

    edges.new = edges.new.filter(e => {
      if (e.from === NEW_STEP) {
        to = e.to
        return false
      }
      return true
    })

    edges.new.push({
      from: NEW_STEP,
      to: to,
      path: 'yes',
    })

    edges.new.push({
      from: NEW_STEP,
      to: to,
      path: 'no',
    })

    return edges
  },

  Edges: ({ data, meta, ID, graph, child_edges }) => {
    // Benchmarks should only ever have 1 child...
    // can have multiple parents though!

    const { edgeLabel, edgeYes, edgeNo } = useStyles()

    let parents = getParents(ID, graph)

    let yesNodeEdge = child_edges.find(e => e.path === 'yes')
    let noNodeEdge = child_edges.find(e => e.path === 'no')

    let yesNode = graph.node(yesNodeEdge ? yesNodeEdge.to_id : EXIT)
    let noNode = graph.node(noNodeEdge ? noNodeEdge.to_id : EXIT)

    const arrows = []

    if (parents.length > 1) {

      // If there are multiple parents we need an edge from the add step button
      // to the top of the card

      arrows.push({
        ...ARROW_STYLE,
        start: `add-step-above-${ ID }`,
        end: `step-card-${ ID }`,
      })
    }

    arrows.push({
      ...ARROW_STYLE,
      start: `step-card-${ ID }`,
      end: `add-step-no-${ ID }`,
      endAnchor: ['top', 'middle'],
      headSize: 0,
      label: {
        middle: (
          <div className={ [edgeLabel, edgeNo].join(' ') }>
            No
          </div>
        ),
      },
    })

    arrows.push({
      ...ARROW_STYLE,
      start: `step-card-${ ID }`,
      end: `add-step-yes-${ ID }`,
      endAnchor: ['top', 'middle'],
      headSize: 0,
      label: {
        middle: (
          <div className={ [edgeLabel, edgeYes].join(' ') }>
            Yes
          </div>
        ),
      },
    })

    arrows.push({
      ...ARROW_STYLE,
      startAnchor: ['bottom', 'middle'],
      start: `add-step-no-${ ID }`,
      end: numParents(noNode.ID, graph) > 1
        ? `add-step-above-${ noNode.ID }`
        : `step-card-${ noNode.ID }`,
      headSize: numParents(noNode.ID, graph) > 1 ?  0 : ARROW_HEAD_SIZE
    })

    arrows.push({
      ...ARROW_STYLE,
      startAnchor: ['bottom', 'middle'],
      start: `add-step-yes-${ ID }`,
      end: numParents(yesNode.ID, graph) > 1
        ? `add-step-above-${ yesNode.ID }`
        : `step-card-${ yesNode.ID }`,
      headSize: numParents(noNode.ID, graph) > 1 ?  0 : ARROW_HEAD_SIZE
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
  },

  /**
   * Renders the positions of the add step buttons relevant for this step.
   *
   * @param data
   * @param meta
   * @param ID
   * @param graph
   * @param xOffset
   * @param child_edges
   * @returns {*}
   */
  Targets: ({ data, meta, ID, graph, xOffset, child_edges }) => {

    let parents = getParents(ID, graph)
    let children = getChildren(ID, graph)

    let thisNode = graph.node(ID)

    let yesNodeEdge = child_edges.find(e => e.path === 'yes')
    let noNodeEdge = child_edges.find(e => e.path === 'no')

    let yesNode = graph.node(yesNodeEdge ? yesNodeEdge.to_id : EXIT)
    let noNode = graph.node(noNodeEdge ? noNodeEdge.to_id : EXIT)

    let yesPosY, yesPosX, noPosY, noPosX

    yesPosY = noPosY = thisNode.y + CARD_HEIGHT +
      ( ADD_STEP_BUTTON_Y_OFFSET * 1.5 )

    if (yesNode.x === noNode.x) {
      // case 1: yes/no are the same node
      yesPosX = thisNode.x - ADD_STEP_BUTTON_X_OFFSET
      noPosX = thisNode.x + NODE_WIDTH - ADD_STEP_BUTTON_X_OFFSET

    }
    else if (yesNode.x === thisNode.x && noNode.x !== thisNode.x) {
      // case 2: yes is 2 levels down, no is 1 level down
      noPosX = noNode.x + ( NODE_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET
      yesPosX = noPosX + NODE_WIDTH

    }
    else if (noNode.x === thisNode.x && yesNode.x !== thisNode.x) {
      // case 3: no is 2 levels down, yes is 1 level down
      yesPosX = yesNode.x + ( NODE_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET
      noPosX = yesPosX + NODE_WIDTH
    }
    else {
      // cas3 4: yes, no are different and are both down 1 level
      noPosX = noNode.x + ( NODE_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET
      yesPosX = yesNode.x + ( NODE_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET
    }

    const targets = []

    // Add the YES target
    targets.push({
      id: `add-step-yes-${ ID }`,
      groups: [
        ACTIONS,
        CONDITIONS,
      ],
      edges: {
        new: [
          { from: ID, to: NEW_STEP, path: 'yes' },
          { from: NEW_STEP, to: yesNode.ID },
        ],
        delete: [{ from: ID, to: yesNode.ID, path: 'yes' }],
      },
      position: {
        x: yesPosX,
        y: yesPosY,
      },
    })

    // Add the NO target
    targets.push({
      id: `add-step-no-${ ID }`,
      groups: [
        ACTIONS,
        CONDITIONS,
      ],
      edges: {
        new: [
          { from: ID, to: NEW_STEP, path: 'no' },
          { from: NEW_STEP, to: noNode.ID },
        ],
        delete: [{ from: ID, to: noNode.ID, path: 'no' }],
      },
      position: {
        x: noPosX,
        y: noPosY,
      },
    })

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
        return isBenchmark(id, graph)
      }).length) {
        allowedGroups = allowedGroups.filter(group => group !== BENCHMARKS)
      }

      targets.push({
        id: `add-step-above-${ ID }`,
        groups: allowedGroups,
        edges: getEdgeChangesAbove(ID, graph),
        position: {
          x: thisNode.x + ( CARD_WIDTH / 2 ) - ADD_STEP_BUTTON_X_OFFSET,
          y: thisNode.y - ( ADD_STEP_BUTTON_Y_OFFSET * 2 ),
        },
      })
    }

    return (
      <>
        {
          targets.map(({ id, position, groups, edges }) =>
            <AddStepButton
              id={ id }
              groups={ groups }
              edges={ edges }
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

registerStepType(STEP_TYPE, stepAtts)