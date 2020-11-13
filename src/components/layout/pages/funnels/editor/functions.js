export function numChildren( node, graph ){
  let n = graph.node(node);
  if ( n && n !== 'exit'){
    return Object.values( n.data.child_steps ).length
  }
  return 0;
}

export function numParents( node, graph ){
  let n = graph.node(node);
  if ( n && n !== 'exit'){
    return Object.values( n.data.parent_steps ).length
  }
  return 0;
}