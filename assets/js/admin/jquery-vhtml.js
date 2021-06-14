(function ($) {

  const isEqualNode = (a, b) => {
    return a.cloneNode(false).isEqualNode(b.cloneNode(false))
  }

  const traverseDom = (node, newNode, parentNode) => {

    // if ( ! node || ! newNode || node.nodeType !== Node.ELEMENT_NODE || newNode.nodeType !== Node.ELEMENT_NODE) {
    //   return
    // }

    console.log(node, newNode)

    if (!node && newNode) {

      parentNode.appendChild(newNode)

    } else if (node && !newNode) {

      parentNode.removeChild(node)

    } else if (isEqualNode(node, newNode) && node.hasChildNodes() && newNode.hasChildNodes()) {

      node.childNodes.forEach((child, i) => {
        traverseDom(child, newNode.childNodes[i], node)
      })

      newNode.childNodes.forEach((child, i) => {
        traverseDom(node.childNodes[i], child, node)
      })

    } else if (!isEqualNode(node, newNode)) {
      parentNode.replaceChild(newNode, node)
    }
  }

  $.fn.vhtml = function (toRender) {

    const node = this[0]

    const el = node.cloneNode(false)
    el.innerHTML = toRender

    traverseDom(node, el)

    return this
  }

})(jQuery)