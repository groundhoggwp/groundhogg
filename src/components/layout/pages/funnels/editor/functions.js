import {
  ACTION,
  BENCHMARK,
} from 'components/layout/pages/funnels/editor/steps-types/constants'

export function isBenchmark ( n, graph ) {
  n = graph.node(n);
  return n ? n.data.step_group === BENCHMARK : false;
}

export function isAction ( n, graph ) {
  n = graph.node(n);
  return n ? n.data.step_group === ACTION : false;
}

export function numParents ( n, graph ) {
  return getParents( n, graph ).length;
}

export function numChildren ( n, graph ) {
  return getChildren( n, graph ).length;
}

export function getParents ( n, graph ) {
  return graph.edges().filter( e => e.w == n ).map( e => e.v );
}

export function getChildren ( n, graph ) {
  return graph.edges().filter( e => e.v == n ).map( e => e.w );
}