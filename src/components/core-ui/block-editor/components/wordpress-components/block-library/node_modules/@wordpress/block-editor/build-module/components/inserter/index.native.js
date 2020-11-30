import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dropdown, ToolbarButton, Picker } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { withSelect } from '@wordpress/data';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { isUnmodifiedDefaultBlock } from '@wordpress/blocks';
import { Icon, plusCircleFilled, insertAfter, insertBefore } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import styles from './style.scss';
import InserterMenu from './menu';
import BlockInsertionPoint from '../block-list/insertion-point';

var defaultRenderToggle = function defaultRenderToggle(_ref) {
  var onToggle = _ref.onToggle,
      disabled = _ref.disabled,
      style = _ref.style,
      onLongPress = _ref.onLongPress;
  return createElement(ToolbarButton, {
    title: __('Add block'),
    icon: createElement(Icon, {
      icon: plusCircleFilled,
      style: style,
      color: style.color
    }),
    onClick: onToggle,
    extraProps: {
      hint: __('Double tap to add a block'),
      // testID is present to disambiguate this element for native UI tests. It's not
      // usually required for components. See: https://git.io/JeQ7G.
      testID: 'add-block-button',
      onLongPress: onLongPress
    },
    isDisabled: disabled
  });
};

export var Inserter = /*#__PURE__*/function (_Component) {
  _inherits(Inserter, _Component);

  var _super = _createSuper(Inserter);

  function Inserter() {
    var _this;

    _classCallCheck(this, Inserter);

    _this = _super.apply(this, arguments);
    _this.onToggle = _this.onToggle.bind(_assertThisInitialized(_this));
    _this.renderToggle = _this.renderToggle.bind(_assertThisInitialized(_this));
    _this.renderContent = _this.renderContent.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(Inserter, [{
    key: "getInsertionOptions",
    value: function getInsertionOptions() {
      var addBeforeOption = {
        value: 'before',
        label: __('Add Block Before'),
        icon: insertBefore
      };
      var replaceCurrentOption = {
        value: 'replace',
        label: __('Replace Current Block'),
        icon: plusCircleFilled
      };
      var addAfterOption = {
        value: 'after',
        label: __('Add Block After'),
        icon: insertAfter
      };
      var addToBeginningOption = {
        value: 'before',
        label: __('Add To Beginning'),
        icon: insertBefore
      };
      var addToEndOption = {
        value: 'after',
        label: __('Add To End'),
        icon: insertAfter
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
        return createElement(BlockInsertionPoint, null);
      }

      var style = getStylesFromColorScheme(styles.addBlockButton, styles.addBlockButtonDark);

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

      return createElement(Fragment, null, renderToggle({
        onToggle: onPress,
        isOpen: isOpen,
        disabled: disabled,
        style: style,
        onLongPress: onLongPress
      }), createElement(Picker, {
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
      return createElement(InserterMenu, {
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
      return createElement(Dropdown, {
        onToggle: this.onToggle,
        headerTitle: __('Add a block'),
        renderToggle: this.renderToggle,
        renderContent: this.renderContent
      });
    }
  }]);

  return Inserter;
}(Component);
export default compose([withSelect(function (select, _ref4) {
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
  var isSelectedUnmodifiedDefaultBlock = isAnyBlockSelected ? isUnmodifiedDefaultBlock(getBlock(end)) : undefined;

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
}), withPreferredColorScheme])(Inserter);
//# sourceMappingURL=index.native.js.map