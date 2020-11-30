"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _compose = require("@wordpress/compose");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var BlockSelectionClearer = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(BlockSelectionClearer, _Component);

  var _super = _createSuper(BlockSelectionClearer);

  function BlockSelectionClearer() {
    var _this;

    (0, _classCallCheck2.default)(this, BlockSelectionClearer);
    _this = _super.apply(this, arguments);
    _this.bindContainer = _this.bindContainer.bind((0, _assertThisInitialized2.default)(_this));
    _this.clearSelectionIfFocusTarget = _this.clearSelectionIfFocusTarget.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(BlockSelectionClearer, [{
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
      return (0, _element.createElement)("div", (0, _extends2.default)({
        tabIndex: -1,
        onFocus: this.clearSelectionIfFocusTarget,
        ref: this.bindContainer
      }, (0, _lodash.omit)(this.props, ['clearSelectedBlock', 'hasSelectedBlock', 'hasMultiSelection'])));
    }
  }]);
  return BlockSelectionClearer;
}(_element.Component);

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select) {
  var _select = select('core/block-editor'),
      hasSelectedBlock = _select.hasSelectedBlock,
      hasMultiSelection = _select.hasMultiSelection;

  return {
    hasSelectedBlock: hasSelectedBlock(),
    hasMultiSelection: hasMultiSelection()
  };
}), (0, _data.withDispatch)(function (dispatch) {
  var _dispatch = dispatch('core/block-editor'),
      clearSelectedBlock = _dispatch.clearSelectedBlock;

  return {
    clearSelectedBlock: clearSelectedBlock
  };
})])(BlockSelectionClearer);

exports.default = _default;
//# sourceMappingURL=index.js.map