/**
 * WordPress dependencies
 */
import { unwrap } from '@wordpress/dom';

function isList(node) {
  return node.nodeName === 'OL' || node.nodeName === 'UL';
}

function shallowTextContent(element) {
  return Array.from(element.childNodes).map(function (_ref) {
    var _ref$nodeValue = _ref.nodeValue,
        nodeValue = _ref$nodeValue === void 0 ? '' : _ref$nodeValue;
    return nodeValue;
  }).join('');
}

export default function listReducer(node) {
  if (!isList(node)) {
    return;
  }

  var list = node;
  var prevElement = node.previousElementSibling; // Merge with previous list if:
  // * There is a previous list of the same type.
  // * There is only one list item.

  if (prevElement && prevElement.nodeName === node.nodeName && list.children.length === 1) {
    // Move all child nodes, including any text nodes, if any.
    while (list.firstChild) {
      prevElement.appendChild(list.firstChild);
    }

    list.parentNode.removeChild(list);
  }

  var parentElement = node.parentNode; // Nested list with empty parent item.

  if (parentElement && parentElement.nodeName === 'LI' && parentElement.children.length === 1 && !/\S/.test(shallowTextContent(parentElement))) {
    var parentListItem = parentElement;
    var prevListItem = parentListItem.previousElementSibling;
    var parentList = parentListItem.parentNode;

    if (prevListItem) {
      prevListItem.appendChild(list);
      parentList.removeChild(parentListItem);
    } else {
      parentList.parentNode.insertBefore(list, parentList);
      parentList.parentNode.removeChild(parentList);
    }
  } // Invalid: OL/UL > OL/UL.


  if (parentElement && isList(parentElement)) {
    var _prevListItem = node.previousElementSibling;

    if (_prevListItem) {
      _prevListItem.appendChild(node);
    } else {
      unwrap(node);
    }
  }
}
//# sourceMappingURL=list-reducer.js.map