"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var _createSlotFill = (0, _components.createSlotFill)('BlockSettingsMenuControls'),
    BlockSettingsMenuControls = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

var BlockSettingsMenuControlsSlot = function BlockSettingsMenuControlsSlot(_ref) {
  var fillProps = _ref.fillProps,
      _ref$clientIds = _ref.clientIds,
      clientIds = _ref$clientIds === void 0 ? null : _ref$clientIds;
  var selectedBlocks = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlocksByClientId = _select.getBlocksByClientId,
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds;

    var ids = clientIds !== null ? clientIds : getSelectedBlockClientIds();
    return (0, _lodash.map)((0, _lodash.compact)(getBlocksByClientId(ids)), function (block) {
      return block.name;
    });
  }, [clientIds]);
  return (0, _element.createElement)(Slot, {
    fillProps: _objectSpread(_objectSpread({}, fillProps), {}, {
      selectedBlocks: selectedBlocks
    })
  }, function (fills) {
    return !(0, _lodash.isEmpty)(fills) && (0, _element.createElement)(_components.MenuGroup, null, fills);
  });
};

BlockSettingsMenuControls.Slot = BlockSettingsMenuControlsSlot;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/block-settings-menu-controls/README.md
 */

var _default = BlockSettingsMenuControls;
exports.default = _default;
//# sourceMappingURL=index.js.map