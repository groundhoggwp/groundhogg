"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _serverSideRender = _interopRequireDefault(require("@wordpress/server-side-render"));

var _i18n = require("@wordpress/i18n");

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * Minimum number of comments a user can show using this block.
 *
 * @type {number}
 */
var MIN_COMMENTS = 1;
/**
 * Maximum number of comments a user can show using this block.
 *
 * @type {number}
 */

var MAX_COMMENTS = 100;

var LatestComments = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(LatestComments, _Component);

  var _super = _createSuper(LatestComments);

  function LatestComments() {
    var _this;

    (0, _classCallCheck2.default)(this, LatestComments);
    _this = _super.apply(this, arguments);
    _this.setCommentsToShow = _this.setCommentsToShow.bind((0, _assertThisInitialized2.default)(_this)); // Create toggles for each attribute; we create them here rather than
    // passing `this.createToggleAttribute( 'displayAvatar' )` directly to
    // `onChange` to avoid re-renders.

    _this.toggleDisplayAvatar = _this.createToggleAttribute('displayAvatar');
    _this.toggleDisplayDate = _this.createToggleAttribute('displayDate');
    _this.toggleDisplayExcerpt = _this.createToggleAttribute('displayExcerpt');
    return _this;
  }

  (0, _createClass2.default)(LatestComments, [{
    key: "createToggleAttribute",
    value: function createToggleAttribute(propName) {
      var _this2 = this;

      return function () {
        var value = _this2.props.attributes[propName];
        var setAttributes = _this2.props.setAttributes;
        setAttributes((0, _defineProperty2.default)({}, propName, !value));
      };
    }
  }, {
    key: "setCommentsToShow",
    value: function setCommentsToShow(commentsToShow) {
      this.props.setAttributes({
        commentsToShow: commentsToShow
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props$attribute = this.props.attributes,
          commentsToShow = _this$props$attribute.commentsToShow,
          displayAvatar = _this$props$attribute.displayAvatar,
          displayDate = _this$props$attribute.displayDate,
          displayExcerpt = _this$props$attribute.displayExcerpt;
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Latest comments settings')
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Display avatar'),
        checked: displayAvatar,
        onChange: this.toggleDisplayAvatar
      }), (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Display date'),
        checked: displayDate,
        onChange: this.toggleDisplayDate
      }), (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Display excerpt'),
        checked: displayExcerpt,
        onChange: this.toggleDisplayExcerpt
      }), (0, _element.createElement)(_components.RangeControl, {
        label: (0, _i18n.__)('Number of comments'),
        value: commentsToShow,
        onChange: this.setCommentsToShow,
        min: MIN_COMMENTS,
        max: MAX_COMMENTS,
        required: true
      }))), (0, _element.createElement)(_components.Disabled, null, (0, _element.createElement)(_serverSideRender.default, {
        block: "core/latest-comments",
        attributes: this.props.attributes
      })));
    }
  }]);
  return LatestComments;
}(_element.Component);

var _default = LatestComments;
exports.default = _default;
//# sourceMappingURL=edit.js.map