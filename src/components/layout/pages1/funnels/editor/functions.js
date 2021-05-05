import {
  ACTION,
  BENCHMARK, EXIT,
} from 'components/layout/pages/funnels/editor/steps-types/constants'

export const NEW_STEP = 'new';

export function isExit ( n ) {
  return n === EXIT;
}

export function isBenchmark (n, graph) {
  n = graph.node(n)
  return n && ! isExit(n) ? n.data.step_group === BENCHMARK : false
}

export function isAction (n, graph) {
  n = graph.node(n)
  return n && ! isExit(n) ? n.data.step_group === ACTION : false
}

export function numParents (n, graph) {
  return getParents(n, graph).length
}

export function numChildren (n, graph) {
  return getChildren(n, graph).length
}

export function getParents (n, graph) {
  return graph.edges().filter(e => e.w == n).map(e => e.v)
}

export function getChildren (n, graph) {
  return graph.edges().filter(e => e.v == n).map(e => e.w)
}

/**
 * This will generate an array of edges to create in the event a node is added
 * above the given one Thi will only be used in the event that this node has
 * multiple parents...
 */
export function getEdgeChangesAbove (n, graph) {
  let parents = getParents(n, graph)

  return {
    new: [
      { from: NEW_STEP, to: n },
      ...parents.map(parent => { return { from: parent, to: NEW_STEP } }),
    ],
    delete: parents.map(parent => { return { from: parent, to: n } }),
  }
}

/**
 * This will generate an array of edges to create in the event a node is added
 * above the given one Thi will only be used in the event that this node has
 * multiple parents...
 */
export function getEdgeChangesBelow (n, graph) {
  let children = getChildren(n, graph)

  return {
    new: [
      { from: n, to: NEW_STEP },
      ...children.map(child => { return { from: NEW_STEP, to: child } }),
    ],
    delete: children.map(child => { return { from: n, to: child } }),
  }
}

/**
 * This will generate an array of edges to create in the event a node is added
 * above the given one Thi will only be used in the event that this node has
 * multiple parents...
 */
export function getEdgeChangesBeside (n, graph) {
  let children = getChildren(n, graph)
  let parents = getParents(n, graph)

  return {
    new: [
      ...parents.map( parent => { return { from: parent, to: NEW_STEP } } ),
      ...children.map( child => { return { from: NEW_STEP, to: child } } )
    ]
  }
}
