"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.Inserter = void 0;

var _element = require("@wordpress/element");

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

var _blocks = require("@wordpress/blocks");

var _icons = require("@wordpress/icons");

var _style = _interopRequireDefault(require("./style.scss"));

var _menu = _interopRequireDefault(require("./menu"));

var _insertionPoint = _interopRequireDefault(require("../block-list/insertion-point"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var defaultRenderToggle = function defaultRenderToggle(_ref) {
  var onToggle = _ref.onToggle,
      disabled = _ref.disabled,
      style = _ref.style,
      onLongPress = _ref.onLongPress;
  return (0, _element.createElement)(_components.ToolbarButton, {
    title: (0, _i18n.__)('Add block'),
    icon: (0, _element.createElement)(_icons.Icon, {
      icon: _icons.plusCircleFilled,
      style: style,
      color: style.color
    }),
    onClick: onToggle,
    extraProps: {
      hint: (0, _i18n.__)('Double tap to add a block'),
      // testID is present to disambiguate this element for native UI tests. It's not
      // usually required for components. See: https://git.io/JeQ7G.
      testID: 'add-block-button',
      onLongPress: onLongPress
    },
    isDisabled: disabled
  });
};

var Inserter = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(Inserter, _Component);

  var _super = _createSuper(Inserter);

  function Inserter() {
    var _this;

    (0, _classCallCheck2.default)(this, Inserter);
    _this = _super.apply(this, arguments);
    _this.onToggle = _this.onToggle.bind((0, _assertThisInitialized2.default)(_this));
    _this.renderToggle = _this.renderToggle.bind((0, _assertThisInitialized2.default)(_this));
    _this.renderContent = _this.renderContent.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(Inserter, [{
    key: "getInsertionOptions",
    value: function getInsertionOptions() {
      var addBeforeOption = {
        value: 'before',
        label: (0, _i18n.__)('Add Block Before'),
        icon: _icons.insertBefore
      };
      var replaceCurrentOption = {
        value: 'replace',
        label: (0, _i18n.__)('Replace Current Block'),
        icon: _icons.plusCircleFilled
      };
      var addAfterOption = {
        value: 'after',
        label: (0, _i18n.__)('Add Block After'),
        icon: _icons.insertAfter
      };
      var addToBeginningOption = {
        value: 'before',
        label: (0, _i18n.__)('Add To Beginning'),
        icon: _icons.insertBefore
      };
      var addToEndOption = {
        value: 'after',
        label: (0, _i18n.__)('Add To End'),
        icon: _icons.insertAfter
      };
      var _this$props = this.props,
          isAnyBlockSelected = _this$props.isAnyBlockSelected,
          isSelectedBlockReplaceable = _this$props.isSelectedBlockReplaceable;

      if (isAnyBlockSelected) {
        if (isSelectedBlockReplaceable) {
          return [addBeforeOption, replaceCurrentOption, addAfterOption];
        }

        return [addBeforeOption, addAfterOption];
      }

      return [addToBeginningOption, addToEndOption];
    }
  }, {
    key: "getInsertionIndex",
    value: function getInsertionIndex(insertionType) {
      var _this$props2 = this.props,
          insertionIndexDefault = _this$props2.insertionIndexDefault,
          insertionIndexBefore = _this$props2.insertionIndexBefore,
          insertionIndexAfter = _this$props2.insertionIndexAfter;

      if (insertionType === 'before' || insertionType === 'replace') {
        return insertionIndexBefore;
      }

      if (insertionType === 'after') {
        return insertionIndexAfter;
      }

      return insertionIndexDefault;
    }
  }, {
    key: "shouldReplaceBlock",
    value: function shouldReplaceBlock(insertionType) {
      var isSelectedBlockReplaceable = this.props.isSelectedBlockReplaceable;

      if (insertionType === 'replace') {
        return true;
      }

      if (insertionType === 'default' && isSelectedBlockReplaceable) {
        return true;
      }

      return false;
    }
  }, {
    key: "onToggle",
    value: function onToggle(isOpen) {
      var onToggle = this.props.onToggle; // Surface toggle callback to parent component

      if (onToggle) {
        onToggle(isOpen);
      }
    }
    /**
     * Render callback to display Dropdown toggle element.
     *
     * @param {Object}   options
     * @param {Function} options.onToggle Callback to invoke when toggle is
     *                                    pressed.
     * @param {boolean}  options.isOpen   Whether dropdown is currently open.
     *
     * @return {WPElement} Dropdown toggle element.
     */

  }, {
    key: "renderToggle",
    value: function renderToggle(_ref2) {
      var _this2 = this;

      var onToggle = _ref2.onToggle,
          isOpen = _ref2.isOpen;
      var _this$props3 = this.props,
          disabled = _this$props3.disabled,
          _this$props3$renderTo = _this$props3.renderToggle,
          renderToggle = _this$props3$renderTo === void 0 ? defaultRenderToggle : _this$props3$renderTo,
          getStylesFromColorScheme = _this$props3.getStylesFromColorScheme,
          showSeparator = _this$props3.showSeparator;

      if (showSeparator && isOpen) {
        return (0, _element.createElement)(_insertionPoint.default, null);
      }

      var style = getStylesFromColorScheme(_style.default.addBlockButton, _style.default.addBlockButtonDark);

      var onPress = function onPress() {
        _this2.setState({
          destinationRootClientId: _this2.props.destinationRootClientId,
          shouldReplaceBlock: _this2.shouldReplaceBlock('default'),
          insertionIndex: _this2.getInsertionIndex('default')
        }, onToggle);
      };

      var onLongPress = function onLongPress() {
        if (_this2.picker) {
          _this2.picker.presentPicker();
        }
      };

      var onPickerSelect = function onPickerSelect(insertionType) {
        _this2.setState({
          destinationRootClientId: _this2.props.destinationRootClientId,
          shouldReplaceBlock: _this2.shouldReplaceBlock(insertionType),
          insertionIndex: _this2.getInsertionIndex(insertionType)
        }, onToggle);
      };

      return (0, _element.createElement)(_element.Fragment, null, renderToggle({
        onToggle: onPress,
        isOpen: isOpen,
        disabled: disabled,
        style: style,
        onLongPress: onLongPress
      }), (0, _element.createElement)(_components.Picker, {
        ref: function ref(instance) {
          return _this2.picker = instance;
        },
        options: this.getInsertionOptions(),
        onChange: onPickerSelect,
        hideCancelButton: true
      }));
    }
    /**
     * Render callback to display Dropdown content element.
     *
     * @param {Object}   options
     * @param {Function} options.onClose Callback to invoke when dropdown is
     *                                   closed.
     * @param {boolean}  options.isOpen  Whether dropdown is currently open.
     *
     * @return {WPElement} Dropdown content element.
     */

  }, {
    key: "renderContent",
    value: function renderContent(_ref3) {
      var onClose = _ref3.onClose,
          isOpen = _ref3.isOpen;
      var _this$props4 = this.props,
          clientId = _this$props4.clientId,
          isAppender = _this$props4.isAppender;
      var _this$state = this.state,
          destinationRootClientId = _this$state.destinationRootClientId,
          shouldReplaceBlock = _this$state.shouldReplaceBlock,
          insertionIndex = _this$state.insertionIndex;
      return (0, _element.createElement)(_menu.default, {
        isOpen: isOpen,
        onSelect: onClose,
        onDismiss: onClose,
        rootClientId: destinationRootClientId,
        clientId: clientId,
        isAppender: isAppender,
        shouldReplaceBlock: shouldReplaceBlock,
        insertionIndex: insertionIndex
      });
    }
  }, {
    key: "render",
    value: function render() {
      return (0, _element.createElement)(_components.Dropdown, {
        onToggle: this.onToggle,
        headerTitle: (0, _i18n.__)('Add a block'),
        renderToggle: this.renderToggle,
        renderContent: this.renderContent
      });
    }
  }]);
  return Inserter;
}(_element.Component);

exports.Inserter = Inserter;

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref4) {
  var clientId = _ref4.clientId,
      isAppender = _ref4.isAppender,
      rootClientId = _ref4.rootClientId;

  var _select = select('core/block-editor'),
      getBlockRootClientId = _select.getBlockRootClientId,
      getBlockSelectionEnd = _select.getBlockSelectionEnd,
      getBlockOrder = _select.getBlockOrder,
      getBlockIndex = _select.getBlockIndex,
      getBlock = _select.getBlock;

  var end = getBlockSelectionEnd(); // `end` argument (id) can refer to the component which is removed
  // due to pressing `undo` button, that's why we need to check
  // if `getBlock( end) is valid, otherwise `null` is passed

  var isAnyBlockSelected = !isAppender && end && getBlock(end);
  var destinationRootClientId = isAnyBlockSelected ? getBlockRootClientId(end) : rootClientId;
  var selectedBlockIndex = getBlockIndex(end, destinationRootClientId);
  var endOfRootIndex = getBlockOrder(rootClientId).length;
  var isSelectedUnmodifiedDefaultBlock = isAnyBlockSelected ? (0, _blocks.isUnmodifiedDefaultBlock)(getBlock(end)) : undefined;

  function getDefaultInsertionIndex() {
    var _select2 = select('core/block-editor'),
        getSettings = _select2.getSettings;

    var _getSettings = getSettings(),
        shouldInsertAtTheTop = _getSettings.__experimentalShouldInsertAtTheTop; // if post title is selected insert as first block


    if (shouldInsertAtTheTop) {
      return 0;
    } // If the clientId is defined, we insert at the position of the block.


    if (clientId) {
      return getBlockIndex(clientId, rootClientId);
    } // If there is a selected block,


    if (isAnyBlockSelected) {
      // and the last selected block is unmodified (empty), it will be replaced
      if (isSelectedUnmodifiedDefaultBlock) {
        return selectedBlockIndex;
      } // we insert after the selected block.


      return selectedBlockIndex + 1;
    } // Otherwise, we insert at the end of the current rootClientId


    return endOfRootIndex;
  }

  var insertionIndexBefore = isAnyBlockSelected ? selectedBlockIndex : 0;
  var insertionIndexAfter = isAnyBlockSelected ? selectedBlockIndex + 1 : endOfRootIndex;
  return {
    destinationRootClientId: destinationRootClientId,
    insertionIndexDefault: getDefaultInsertionIndex(),
    insertionIndexBefore: insertionIndexBefore,
    insertionIndexAfter: insertionIndexAfter,
    isAnyBlockSelected: isAnyBlockSelected,
    isSelectedBlockReplaceable: isSelectedUnmodifiedDefaultBlock
  };
}), _compose.withPreferredColorScheme])(Inserter);

exports.default = _default;
//# sourceMappingURL=index.native.js.map