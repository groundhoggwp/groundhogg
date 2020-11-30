"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockPreview = BlockPreview;
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _provider = _interopRequireDefault(require("../provider"));

var _live = _interopRequireDefault(require("./live"));

var _auto = _interopRequireDefault(require("./auto"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function BlockPreview(_ref) {
  var blocks = _ref.blocks,
      _ref$__experimentalPa = _ref.__experimentalPadding,
      __experimentalPadding = _ref$__experimentalPa === void 0 ? 0 : _ref$__experimentalPa,
      _ref$viewportWidth = _ref.viewportWidth,
      viewportWidth = _ref$viewportWidth === void 0 ? 700 : _ref$viewportWidth,
      _ref$__experimentalLi = _ref.__experimentalLive,
      __experimentalLive = _ref$__experimentalLi === void 0 ? false : _ref$__experimentalLi,
      __experimentalOnClick = _ref.__experimentalOnClick;

  var settings = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getSettings();
  }, []);
  var renderedBlocks = (0, _element.useMemo)(function () {
    return (0, _lodash.castArray)(blocks);
  }, [blocks]);

  if (!blocks || blocks.length === 0) {
    return null;
  }

  return (0, _element.createElement)(_provider.default, {
    value: renderedBlocks,
    settings: settings
  }, __experimentalLive ? (0, _element.createElement)(_live.default, {
    onClick: __experimentalOnClick
  }) : (0, _element.createElement)(_auto.default, {
    viewportWidth: viewportWidth,
    __experimentalPadding: __experimentalPadding
  }));
}
/**
 * BlockPreview renders a preview of a block or array of blocks.
 *
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/block-preview/README.md
 *
 * @param {Object} preview options for how the preview should be shown
 * @param {Array|Object} preview.blocks A block instance (object) or an array of blocks to be previewed.
 * @param {number} preview.viewportWidth Width of the preview container in pixels. Controls at what size the blocks will be rendered inside the preview. Default: 700.
 *
 * @return {WPComponent} The component to be rendered.
 */


var _default = (0, _element.memo)(BlockPreview);

exports.default = _default;
//# sourceMappingURL=index.js.map