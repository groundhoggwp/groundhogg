"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = phrasingContentReducer;

var _lodash = require("lodash");

var _dom = require("@wordpress/dom");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function phrasingContentReducer(node, doc) {
  // In jsdom-jscore, 'node.style' can be null.
  // TODO: Explore fixing this by patching jsdom-jscore.
  if (node.nodeName === 'SPAN' && node.style) {
    var _node$style = node.style,
        fontWeight = _node$style.fontWeight,
        fontStyle = _node$style.fontStyle,
        textDecorationLine = _node$style.textDecorationLine,
        textDecoration = _node$style.textDecoration,
        verticalAlign = _node$style.verticalAlign;

    if (fontWeight === 'bold' || fontWeight === '700') {
      (0, _dom.wrap)(doc.createElement('strong'), node);
    }

    if (fontStyle === 'italic') {
      (0, _dom.wrap)(doc.createElement('em'), node);
    } // Some DOM implementations (Safari, JSDom) don't support
    // style.textDecorationLine, so we check style.textDecoration as a
    // fallback.


    if (textDecorationLine === 'line-through' || (0, _lodash.includes)(textDecoration, 'line-through')) {
      (0, _dom.wrap)(doc.createElement('s'), node);
    }

    if (verticalAlign === 'super') {
      (0, _dom.wrap)(doc.createElement('sup'), node);
    } else if (verticalAlign === 'sub') {
      (0, _dom.wrap)(doc.createElement('sub'), node);
    }
  } else if (node.nodeName === 'B') {
    node = (0, _dom.replaceTag)(node, 'strong');
  } else if (node.nodeName === 'I') {
    node = (0, _dom.replaceTag)(node, 'em');
  } else if (node.nodeName === 'A') {
    // In jsdom-jscore, 'node.target' can be null.
    // TODO: Explore fixing this by patching jsdom-jscore.
    if (node.target && node.target.toLowerCase() === '_blank') {
      node.rel = 'noreferrer noopener';
    } else {
      node.removeAttribute('target');
      node.removeAttribute('rel');
    }
  }
}
//# sourceMappingURL=phrasing-content-reducer.js.map