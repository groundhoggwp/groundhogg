"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.html = html;
Object.defineProperty(exports, "attr", {
  enumerable: true,
  get: function get() {
    return _hpq.attr;
  }
});
Object.defineProperty(exports, "prop", {
  enumerable: true,
  get: function get() {
    return _hpq.prop;
  }
});
Object.defineProperty(exports, "text", {
  enumerable: true,
  get: function get() {
    return _hpq.text;
  }
});
Object.defineProperty(exports, "query", {
  enumerable: true,
  get: function get() {
    return _hpq.query;
  }
});
Object.defineProperty(exports, "node", {
  enumerable: true,
  get: function get() {
    return _node.matcher;
  }
});
Object.defineProperty(exports, "children", {
  enumerable: true,
  get: function get() {
    return _children.matcher;
  }
});

var _hpq = require("hpq");

var _node = require("./node");

var _children = require("./children");

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
function html(selector, multilineTag) {
  return function (domNode) {
    var match = domNode;

    if (selector) {
      match = domNode.querySelector(selector);
    }

    if (!match) {
      return '';
    }

    if (multilineTag) {
      var value = '';
      var length = match.children.length;

      for (var index = 0; index < length; index++) {
        var child = match.children[index];

        if (child.nodeName.toLowerCase() !== multilineTag) {
          continue;
        }

        value += child.outerHTML;
      }

      return value;
    }

    return match.innerHTML;
  };
}
//# sourceMappingURL=matchers.js.map