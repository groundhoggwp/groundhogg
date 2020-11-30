"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _objectWithoutProperties2 = _interopRequireDefault(require("@babel/runtime/helpers/objectWithoutProperties"));

/**
 * WordPress dependencies
 */
function TreeGridRow(_ref, ref) {
  var children = _ref.children,
      level = _ref.level,
      positionInSet = _ref.positionInSet,
      setSize = _ref.setSize,
      isExpanded = _ref.isExpanded,
      props = (0, _objectWithoutProperties2.default)(_ref, ["children", "level", "positionInSet", "setSize", "isExpanded"]);
  return (// Disable reason: Due to an error in the ARIA 1.1 specification, the
    // aria-posinset and aria-setsize properties are not supported on row
    // elements. This is being corrected in ARIA 1.2. Consequently, the
    // linting rule fails when validating this markup.
    //
    // eslint-disable-next-line jsx-a11y/role-supports-aria-props
    (0, _element.createElement)("tr", (0, _extends2.default)({}, props, {
      ref: ref,
      role: "row",
      "aria-level": level,
      "aria-posinset": positionInSet,
      "aria-setsize": setSize,
      "aria-expanded": isExpanded
    }), children)
  );
}

var _default = (0, _element.forwardRef)(TreeGridRow);

exports.default = _default;
//# sourceMappingURL=row.js.map