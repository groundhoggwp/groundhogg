"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _compose = require("@wordpress/compose");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/** @typedef {import('@wordpress/element').WPSyntheticEvent} WPSyntheticEvent */

/**
 * Browser dependencies
 */
var _window = window,
    FocusEvent = _window.FocusEvent,
    DOMParser = _window.DOMParser;

var WpEmbedPreview = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(WpEmbedPreview, _Component);

  var _super = _createSuper(WpEmbedPreview);

  function WpEmbedPreview() {
    var _this;

    (0, _classCallCheck2.default)(this, WpEmbedPreview);
    _this = _super.apply(this, arguments);
    _this.checkFocus = _this.checkFocus.bind((0, _assertThisInitialized2.default)(_this));
    _this.node = (0, _element.createRef)();
    return _this;
  }

  (0, _createClass2.default)(WpEmbedPreview, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      window.addEventListener('message', this.resizeWPembeds);
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      window.removeEventListener('message', this.resizeWPembeds);
    }
    /**
     * Checks for WordPress embed events signaling the height change when iframe
     * content loads or iframe's window is resized.  The event is sent from
     * WordPress core via the window.postMessage API.
     *
     * References:
     * window.postMessage: https://developer.mozilla.org/en-US/docs/Web/API/Window/postMessage
     * WordPress core embed-template on load: https://github.com/WordPress/WordPress/blob/master/wp-includes/js/wp-embed-template.js#L143
     * WordPress core embed-template on resize: https://github.com/WordPress/WordPress/blob/master/wp-includes/js/wp-embed-template.js#L187
     *
     * @param {WPSyntheticEvent} event Message event.
     */

  }, {
    key: "resizeWPembeds",
    value: function resizeWPembeds(_ref) {
      var _ref$data = _ref.data;
      _ref$data = _ref$data === void 0 ? {} : _ref$data;
      var secret = _ref$data.secret,
          message = _ref$data.message,
          value = _ref$data.value;

      if ([secret, message, value].some(function (attribute) {
        return !attribute;
      }) || message !== 'height') {
        return;
      }

      document.querySelectorAll("iframe[data-secret=\"".concat(secret, "\"")).forEach(function (iframe) {
        if (+iframe.height !== value) {
          iframe.height = value;
        }
      });
    }
    /**
     * Checks whether the wp embed iframe is the activeElement,
     * if it is dispatch a focus event.
     */

  }, {
    key: "checkFocus",
    value: function checkFocus() {
      var _document = document,
          activeElement = _document.activeElement;

      if (activeElement.tagName !== 'IFRAME' || activeElement.parentNode !== this.node.current) {
        return;
      }

      var focusEvent = new FocusEvent('focus', {
        bubbles: true
      });
      activeElement.dispatchEvent(focusEvent);
    }
  }, {
    key: "render",
    value: function render() {
      var html = this.props.html;
      var doc = new DOMParser().parseFromString(html, 'text/html');
      var iframe = doc.querySelector('iframe');
      if (iframe) iframe.removeAttribute('style');
      var blockQuote = doc.querySelector('blockquote');
      if (blockQuote) blockQuote.style.display = 'none';
      return (0, _element.createElement)("div", {
        ref: this.node,
        className: "wp-block-embed__wrapper",
        dangerouslySetInnerHTML: {
          __html: doc.body.innerHTML
        }
      });
    }
  }]);
  return WpEmbedPreview;
}(_element.Component);

var _default = (0, _compose.withGlobalEvents)({
  blur: 'checkFocus'
})(WpEmbedPreview);

exports.default = _default;
//# sourceMappingURL=wp-embed-preview.js.map