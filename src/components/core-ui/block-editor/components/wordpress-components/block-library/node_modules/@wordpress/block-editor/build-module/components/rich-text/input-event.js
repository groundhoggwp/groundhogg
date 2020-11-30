import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
export var __unstableRichTextInputEvent = /*#__PURE__*/function (_Component) {
  _inherits(__unstableRichTextInputEvent, _Component);

  var _super = _createSuper(__unstableRichTextInputEvent);

  function __unstableRichTextInputEvent() {
    var _this;

    _classCallCheck(this, __unstableRichTextInputEvent);

    _this = _super.apply(this, arguments);
    _this.onInput = _this.onInput.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(__unstableRichTextInputEvent, [{
    key: "onInput",
    value: function onInput(event) {
      if (event.inputType === this.props.inputType) {
        this.props.onInput();
      }
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      document.addEventListener('input', this.onInput, true);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      document.removeEventListener('input', this.onInput, true);
    }
  }, {
    key: "render",
    value: function render() {
      return null;
    }
  }]);

  return __unstableRichTextInputEvent;
}(Component);
//# sourceMappingURL=input-event.js.map