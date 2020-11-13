export function numChildren( node, graph ){
  let n = graph.node(node);
  return n && n !== 'exit' ? n.numChildren() : 0;
}

export function numParents( node, graph ){
  let n = graph.node(node);
  return n && n !== 'exit' ? n.numParents() : 0;
}

export function isBenchmark ( node, graph ) {
  let n = graph.node(node);
  return n && n !== 'exit' ? n.isBenchmark() : false;
}