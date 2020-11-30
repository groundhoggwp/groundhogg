import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import _extends from "@babel/runtime/helpers/esm/extends";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { size } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { speak } from '@wordpress/a11y';
import { __, _x, sprintf } from '@wordpress/i18n';
import { Dropdown, Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose, ifCondition } from '@wordpress/compose';
import { createBlock } from '@wordpress/blocks';
import { plus } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import InserterMenu from './menu';
import QuickInserter from './quick-inserter';

var defaultRenderToggle = function defaultRenderToggle(_ref) {
  var onToggle = _ref.onToggle,
      disabled = _ref.disabled,
      isOpen = _ref.isOpen,
      blockTitle = _ref.blockTitle,
      hasSingleBlockType = _ref.hasSingleBlockType,
      _ref$toggleProps = _ref.toggleProps,
      toggleProps = _ref$toggleProps === void 0 ? {} : _ref$toggleProps;
  var label;

  if (hasSingleBlockType) {
    label = sprintf( // translators: %s: the name of the block when there is only one
    _x('Add %s', 'directly add the only allowed block'), blockTitle);
  } else {
    label = _x('Add block', 'Generic label for block inserter button');
  }

  var onClick = toggleProps.onClick,
      rest = _objectWithoutProperties(toggleProps, ["onClick"]); // Handle both onClick functions from the toggle and the parent component


  function handleClick(event) {
    if (onToggle) {
      onToggle(event);
    }

    if (onClick) {
      onClick(event);
    }
  }

  return createElement(Button, _extends({
    icon: plus,
    label: label,
    tooltipPosition: "bottom",
    onClick: handleClick,
    className: "block-editor-inserter__toggle",
    "aria-haspopup": !hasSingleBlockType ? 'true' : false,
    "aria-expanded": !hasSingleBlockType ? isOpen : false,
    disabled: disabled
  }, rest));
};

var Inserter = /*#__PURE__*/function (_Component) {
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
      var onToggle = _ref2.onToggle,
          isOpen = _ref2.isOpen;
      var _this$props = this.props,
          disabled = _this$props.disabled,
          blockTitle = _this$props.blockTitle,
          hasSingleBlockType = _this$props.hasSingleBlockType,
          toggleProps = _this$props.toggleProps,
          hasItems = _this$props.hasItems,
          _this$props$renderTog = _this$props.renderToggle,
          renderToggle = _this$props$renderTog === void 0 ? defaultRenderToggle : _this$props$renderTog;
      return renderToggle({
        onToggle: onToggle,
        isOpen: isOpen,
        disabled: disabled || !hasItems,
        blockTitle: blockTitle,
        hasSingleBlockType: hasSingleBlockType,
        toggleProps: toggleProps
      });
    }
    /**
     * Render callback to display Dropdown content element.
     *
     * @param {Object}   options
     * @param {Function} options.onClose Callback to invoke when dropdown is
     *                                   closed.
     *
     * @return {WPElement} Dropdown content element.
     */

  }, {
    key: "renderContent",
    value: function renderContent(_ref3) {
      var onClose = _ref3.onClose;
      var _this$props2 = this.props,
          rootClientId = _this$props2.rootClientId,
          clientId = _this$props2.clientId,
          isAppender = _this$props2.isAppender,
          showInserterHelpPanel = _this$props2.showInserterHelpPanel,
          selectBlockOnInsert = _this$props2.__experimentalSelectBlockOnInsert,
          isQuick = _this$props2.__experimentalIsQuick;

      if (isQuick) {
        return createElement(QuickInserter, {
          onSelect: onClose,
          rootClientId: rootClientId,
          clientId: clientId,
          isAppender: isAppender,
          selectBlockOnInsert: selectBlockOnInsert
        });
      }

      return createElement(InserterMenu, {
        onSelect: onClose,
        rootClientId: rootClientId,
        clientId: clientId,
        isAppender: isAppender,
        showInserterHelpPanel: showInserterHelpPanel,
        __experimentalSelectBlockOnInsert: selectBlockOnInsert
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props3 = this.props,
          position = _this$props3.position,
          hasSingleBlockType = _this$props3.hasSingleBlockType,
          insertOnlyAllowedBlock = _this$props3.insertOnlyAllowedBlock,
          isQuick = _this$props3.__experimentalIsQuick;

      if (hasSingleBlockType) {
        return this.renderToggle({
          onToggle: insertOnlyAllowedBlock
        });
      }

      return createElement(Dropdown, {
        className: "block-editor-inserter",
        contentClassName: classnames('block-editor-inserter__popover', {
          'is-quick': isQuick
        }),
        position: position,
        onToggle: this.onToggle,
        expandOnMobile: true,
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
      rootClientId = _ref4.rootClientId;

  var _select = select('core/block-editor'),
      getBlockRootClientId = _select.getBlockRootClientId,
      hasInserterItems = _select.hasInserterItems,
      __experimentalGetAllowedBlocks = _select.__experimentalGetAllowedBlocks;

  var _select2 = select('core/blocks'),
      getBlockVariations = _select2.getBlockVariations;

  rootClientId = rootClientId || getBlockRootClientId(clientId) || undefined;

  var allowedBlocks = __experimentalGetAllowedBlocks(rootClientId);

  var hasSingleBlockType = size(allowedBlocks) === 1 && size(getBlockVariations(allowedBlocks[0].name, 'inserter')) === 0;
  var allowedBlockType = false;

  if (hasSingleBlockType) {
    allowedBlockType = allowedBlocks[0];
  }

  return {
    hasItems: hasInserterItems(rootClientId),
    hasSingleBlockType: hasSingleBlockType,
    blockTitle: allowedBlockType ? allowedBlockType.title : '',
    allowedBlockType: allowedBlockType,
    rootClientId: rootClientId
  };
}), withDispatch(function (dispatch, ownProps, _ref5) {
  var select = _ref5.select;
  return {
    insertOnlyAllowedBlock: function insertOnlyAllowedBlock() {
      var rootClientId = ownProps.rootClientId,
          clientId = ownProps.clientId,
          isAppender = ownProps.isAppender;
      var hasSingleBlockType = ownProps.hasSingleBlockType,
          allowedBlockType = ownProps.allowedBlockType,
          selectBlockOnInsert = ownProps.__experimentalSelectBlockOnInsert;

      if (!hasSingleBlockType) {
        return;
      }

      function getInsertionIndex() {
        var _select3 = select('core/block-editor'),
            getBlockIndex = _select3.getBlockIndex,
            getBlockSelectionEnd = _select3.getBlockSelectionEnd,
            getBlockOrder = _select3.getBlockOrder; // If the clientId is defined, we insert at the position of the block.


        if (clientId) {
          return getBlockIndex(clientId, rootClientId);
        } // If there a selected block, we insert after the selected block.


        var end = getBlockSelectionEnd();

        if (!isAppender && end) {
          return getBlockIndex(end, rootClientId) + 1;
        } // Otherwise, we insert at the end of the current rootClientId


        return getBlockOrder(rootClientId).length;
      }

      var _dispatch = dispatch('core/block-editor'),
          insertBlock = _dispatch.insertBlock;

      var blockToInsert = createBlock(allowedBlockType.name);
      insertBlock(blockToInsert, getInsertionIndex(), rootClientId, selectBlockOnInsert);

      if (!selectBlockOnInsert) {
        var message = sprintf( // translators: %s: the name of the block that has been added
        __('%s block added'), allowedBlockType.title);
        speak(message);
      }
    }
  };
}), // The global inserter should always be visible, we are using ( ! isAppender && ! rootClientId && ! clientId ) as
// a way to detect the global Inserter.
ifCondition(function (_ref6) {
  var hasItems = _ref6.hasItems,
      isAppender = _ref6.isAppender,
      rootClientId = _ref6.rootClientId,
      clientId = _ref6.clientId;
  return hasItems || !isAppender && !rootClientId && !clientId;
})])(Inserter);
//# sourceMappingURL=index.js.map