import {
  ACTION,
  BENCHMARK,
} from 'components/layout/pages/funnels/editor/steps-types/constants'

export function isBenchmark (n, graph) {
  n = graph.node(n)
  return n ? n.data.step_group === BENCHMARK : false
}

export function isAction (n, graph) {
  n = graph.node(n)
  return n ? n.data.step_group === ACTION : false
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
      { from: 'new', to: n },
      ...parents.map(parent => { return { from: parent, to: 'new' } }),
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
      { from: n, to: 'new' },
      ...children.map(child => { return { from: 'new', to: child } }),
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

  let newEdges=[]

  parents.forEach( parent => {
    children.forEach( child => {
      newEdges.push( { from: parent, to: child } )
    })
  })

  return {
    new: newEdges
  }
}
