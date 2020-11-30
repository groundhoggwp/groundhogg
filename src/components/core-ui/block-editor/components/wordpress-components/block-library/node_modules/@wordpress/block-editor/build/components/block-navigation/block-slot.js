"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.BlockNavigationBlockFill = exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classnames = _interopRequireDefault(require("classnames"));

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

var _block = require("../block-list/block");

var _blockSelectButton = _interopRequireDefault(require("./block-select-button"));

var _utils = require("./utils");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var getSlotName = function getSlotName(clientId) {
  return "BlockNavigationBlock-".concat(clientId);
};

function BlockNavigationBlockSlot(props, ref) {
  var instanceId = (0, _compose.useInstanceId)(BlockNavigationBlockSlot);
  var clientId = props.block.clientId;
  return (0, _element.createElement)(_components.Slot, {
    name: getSlotName(clientId)
  }, function (fills) {
    if (!fills.length) {
      return (0, _element.createElement)(_blockSelectButton.default, (0, _extends2.default)({
        ref: ref
      }, props));
    }

    var className = props.className,
        block = props.block,
        isSelected = props.isSelected,
        position = props.position,
        siblingBlockCount = props.siblingBlockCount,
        level = props.level,
        tabIndex = props.tabIndex,
        onFocus = props.onFocus;
    var name = block.name;
    var blockType = (0, _blocks.getBlockType)(name);
    var descriptionId = "block-navigation-block-slot__".concat(instanceId);
    var blockPositionDescription = (0, _utils.getBlockPositionDescription)(position, siblingBlockCount, level);
    var forwardedFillProps = {
      // Ensure that the component in the slot can receive
      // keyboard navigation.
      tabIndex: tabIndex,
      onFocus: onFocus,
      ref: ref,
      // Give the element rendered in the slot a description
      // that describes its position.
      'aria-describedby': descriptionId
    };
    return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("div", {
      className: (0, _classnames.default)('block-editor-block-navigation-block-slot', className)
    }, (0, _element.createElement)(_blockIcon.default, {
      icon: blockType.icon,
      showColors: true
    }), _element.Children.map(fills, function (fill) {
      return (0, _element.cloneElement)(fill, _objectSpread(_objectSpread({}, fill.props), forwardedFillProps));
    }), isSelected && (0, _element.createElement)(_components.VisuallyHidden, null, (0, _i18n.__)('(selected block)')), (0, _element.createElement)("div", {
      className: "block-editor-block-navigation-block-slot__description",
      id: descriptionId
    }, blockPositionDescription)));
  });
}

var _default = (0, _element.forwardRef)(BlockNavigationBlockSlot);

exports.default = _default;

var BlockNavigationBlockFill = function BlockNavigationBlockFill(props) {
  var _useContext = (0, _element.useContext)(_block.BlockListBlockContext),
      clientId = _useContext.clientId;

  return (0, _element.createElement)(_components.Fill, (0, _extends2.default)({}, props, {
    name: getSlotName(clientId)
  }));
};

exports.BlockNavigationBlockFill = BlockNavigationBlockFill;
//# sourceMappingURL=block-slot.js.map