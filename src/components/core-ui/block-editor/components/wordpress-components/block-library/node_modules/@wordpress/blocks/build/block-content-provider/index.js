"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.withBlockContentContext = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _compose = require("@wordpress/compose");

var _api = require("../api");

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var _createContext = (0, _element.createContext)(function () {}),
    Consumer = _createContext.Consumer,
    Provider = _createContext.Provider;
/**
 * An internal block component used in block content serialization to inject
 * nested block content within the `save` implementation of the ancestor
 * component in which it is nested. The component provides a pre-bound
 * `BlockContent` component via context, which is used by the developer-facing
 * `InnerBlocks.Content` component to render block content.
 *
 * @example
 *
 * ```jsx
 * <BlockContentProvider innerBlocks={ innerBlocks }>
 * 	{ blockSaveElement }
 * </BlockContentProvider>
 * ```
 *
 * @param {Object}    props             Component props.
 * @param {WPElement} props.children    Block save result.
 * @param {Array}     props.innerBlocks Block(s) to serialize.
 *
 * @return {WPComponent} Element with BlockContent injected via context.
 */


var BlockContentProvider = function BlockContentProvider(_ref) {
  var children = _ref.children,
      innerBlocks = _ref.innerBlocks;

  var BlockContent = function BlockContent() {
    // Value is an array of blocks, so defer to block serializer
    var html = (0, _api.serialize)(innerBlocks, {
      isInnerBlocks: true
    }); // Use special-cased raw HTML tag to avoid default escaping

    return (0, _element.createElement)(_element.RawHTML, null, html);
  };

  return (0, _element.createElement)(Provider, {
    value: BlockContent
  }, children);
};
/**
 * A Higher Order Component used to inject BlockContent using context to the
 * wrapped component.
 *
 * @return {WPComponent} Enhanced component with injected BlockContent as prop.
 */


var withBlockContentContext = (0, _compose.createHigherOrderComponent)(function (OriginalComponent) {
  return function (props) {
    return (0, _element.createElement)(Consumer, null, function (context) {
      return (0, _element.createElement)(OriginalComponent, (0, _extends2.default)({}, props, {
        BlockContent: context
      }));
    });
  };
}, 'withBlockContentContext');
exports.withBlockContentContext = withBlockContentContext;
var _default = BlockContentProvider;
exports.default = _default;
//# sourceMappingURL=index.js.map