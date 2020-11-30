"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _blocks = require("@wordpress/blocks");

var _i18n = require("@wordpress/i18n");

var _blockCard = _interopRequireDefault(require("../block-card"));

var _blockPreview = _interopRequireDefault(require("../block-preview"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function InserterPreviewPanel(_ref) {
  var item = _ref.item;
  var hoveredItemBlockType = (0, _blocks.getBlockType)(item.name);
  return (0, _element.createElement)("div", {
    className: "block-editor-inserter__preview-container"
  }, (0, _element.createElement)("div", {
    className: "block-editor-inserter__preview"
  }, (0, _blocks.isReusableBlock)(item) || hoveredItemBlockType.example ? (0, _element.createElement)("div", {
    className: "block-editor-inserter__preview-content"
  }, (0, _element.createElement)(_blockPreview.default, {
    __experimentalPadding: 16,
    viewportWidth: 500,
    blocks: hoveredItemBlockType.example ? (0, _blocks.getBlockFromExample)(item.name, {
      attributes: _objectSpread(_objectSpread({}, hoveredItemBlockType.example.attributes), item.initialAttributes),
      innerBlocks: hoveredItemBlockType.example.innerBlocks
    }) : (0, _blocks.createBlock)(item.name, item.initialAttributes)
  })) : (0, _element.createElement)("div", {
    className: "block-editor-inserter__preview-content-missing"
  }, (0, _i18n.__)('No Preview Available.'))), !(0, _blocks.isReusableBlock)(item) && (0, _element.createElement)(_blockCard.default, {
    blockType: item
  }));
}

var _default = InserterPreviewPanel;
exports.default = _default;
//# sourceMappingURL=preview-panel.js.map