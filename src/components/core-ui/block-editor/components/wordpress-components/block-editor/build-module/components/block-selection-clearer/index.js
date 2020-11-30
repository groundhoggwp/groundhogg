import _extends from "@babel/runtime/helpers/esm/extends";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement } from "@wordpress/element";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { omit } from 'lodash';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

var BlockSelectionClearer = /*#__PURE__*/function (_Component) {
  _inherits(BlockSelectionClearer, _Component);

  var _super = _createSuper(BlockSelectionClearer);

  function BlockSelectionClearer() {
    var _this;

    _classCallCheck(this, BlockSelectionClearer);

    _this = _super.apply(this, arguments);
    _this.bindContainer = _this.bindContainer.bind(_assertThisInitialized(_this));
    _this.clearSelectionIfFocusTarget = _this.clearSelectionIfFocusTarget.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(BlockSelectionClearer, [{
    key: "bindContainer",
    value: function bindContainer(ref) {
      this.container = ref;
    }
    /**
     * Clears the selected block on focus if the container is the target of the
     * focus. This assumes no other descendents have received focus until event
     * has bubbled to the container.
     *
     * @param {FocusEvent} event Focus event.
     */

  }, {
    key: "clearSelectionIfFocusTarget",
    value: function clearSelectionIfFocusTarget(event) {
      var _this$props = this.props,
          hasSelectedBlock = _this$props.hasSelectedBlock,
          hasMultiSelection = _this$props.hasMultiSelection,
          clearSelectedBlock = _this$props.clearSelectedBlock;
      var hasSelection = hasSelectedBlock || hasMultiSelection;

      if (event.target === this.container && hasSelection) {
        clearSelectedBlock();
      }
    }
  }, {
    key: "render",
    value: function render() {
      return createElement("div", _extends({
        tabIndex: -1,
        onFocus: this.clearSelectionIfFocusTarget,
        ref: this.bindContainer
      }, omit(this.props, ['clearSelectedBlock', 'hasSelectedBlock', 'hasMultiSelection'])));
    }
  }]);

  return BlockSelectionClearer;
}(Component);

export default compose([withSelect(function (select) {
  var _select = select('core/block-editor'),
      hasSelectedBlock = _select.hasSelectedBlock,
      hasMultiSelection = _select.hasMultiSelection;

  return {
    hasSelectedBlock: hasSelectedBlock(),
    hasMultiSelection: hasMultiSelection()
  };
}), withDispatch(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      clearSelectedBlock = _dispatch.clearSelectedBlock;

  return {
    clearSelectedBlock: clearSelectedBlock
  };
})])(BlockSelectionClearer);
//# sourceMappingURL=index.js.map