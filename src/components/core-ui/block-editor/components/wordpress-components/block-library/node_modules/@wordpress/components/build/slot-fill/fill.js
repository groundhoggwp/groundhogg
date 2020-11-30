"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _lodash = require("lodash");

var _context = require("./context");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var occurrences = 0;

function FillComponent(_ref) {
  var name = _ref.name,
      children = _ref.children,
      registerFill = _ref.registerFill,
      unregisterFill = _ref.unregisterFill;
  var slot = (0, _context.useSlot)(name);
  var ref = (0, _element.useRef)({
    name: name,
    children: children
  });

  if (!ref.current.occurrence) {
    ref.current.occurrence = ++occurrences;
  }

  (0, _element.useLayoutEffect)(function () {
    registerFill(name, ref.current);
    return function () {
      return unregisterFill(name, ref.current);
    };
  }, []);
  (0, _element.useLayoutEffect)(function () {
    ref.current.children = children;

    if (slot) {
      slot.forceUpdate();
    }
  }, [children]);
  (0, _element.useLayoutEffect)(function () {
    if (name === ref.current.name) {
      // ignore initial effect
      return;
    }

    unregisterFill(ref.current.name, ref.current);
    ref.current.name = name;
    registerFill(name, ref.current);
  }, [name]);

  if (!slot || !slot.node) {
    return null;
  } // If a function is passed as a child, provide it with the fillProps.


  if ((0, _lodash.isFunction)(children)) {
    children = children(slot.props.fillProps);
  }

  return (0, _element.createPortal)(children, slot.node);
}

var Fill = function Fill(props) {
  return (0, _element.createElement)(_context.Consumer, null, function (_ref2) {
    var registerFill = _ref2.registerFill,
        unregisterFill = _ref2.unregisterFill;
    return (0, _element.createElement)(FillComponent, (0, _extends2.default)({}, props, {
      registerFill: registerFill,
      unregisterFill: unregisterFill
    }));
  });
};

var _default = Fill;
exports.default = _default;
//# sourceMappingURL=fill.js.map