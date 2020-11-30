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

var _util = require("./util");

var _dedupe = _interopRequireDefault(require("classnames/dedupe"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

var _blocks = require("@wordpress/blocks");

var _wpEmbedPreview = _interopRequireDefault(require("./wp-embed-preview"));

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

var EmbedPreview = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(EmbedPreview, _Component);

  var _super = _createSuper(EmbedPreview);

  function EmbedPreview() {
    var _this;

    (0, _classCallCheck2.default)(this, EmbedPreview);
    _this = _super.apply(this, arguments);
    _this.hideOverlay = _this.hideOverlay.bind((0, _assertThisInitialized2.default)(_this));
    _this.state = {
      interactive: false
    };
    return _this;
  }

  (0, _createClass2.default)(EmbedPreview, [{
    key: "hideOverlay",
    value: function hideOverlay() {
      // This is called onMouseUp on the overlay. We can't respond to the `isSelected` prop
      // changing, because that happens on mouse down, and the overlay immediately disappears,
      // and the mouse event can end up in the preview content. We can't use onClick on
      // the overlay to hide it either, because then the editor misses the mouseup event, and
      // thinks we're multi-selecting blocks.
      this.setState({
        interactive: true
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          preview = _this$props.preview,
          previewable = _this$props.previewable,
          url = _this$props.url,
          type = _this$props.type,
          caption = _this$props.caption,
          onCaptionChange = _this$props.onCaptionChange,
          isSelected = _this$props.isSelected,
          className = _this$props.className,
          icon = _this$props.icon,
          label = _this$props.label,
          insertBlocksAfter = _this$props.insertBlocksAfter;
      var scripts = preview.scripts;
      var interactive = this.state.interactive;
      var html = 'photo' === type ? (0, _util.getPhotoHtml)(preview) : preview.html;
      var parsedHost = new URL(url).host.split('.');
      var parsedHostBaseUrl = parsedHost.splice(parsedHost.length - 2, parsedHost.length - 1).join('.');
      var iframeTitle = (0, _i18n.sprintf)( // translators: %s: host providing embed content e.g: www.youtube.com
      (0, _i18n.__)('Embedded content from %s'), parsedHostBaseUrl);
      var sandboxClassnames = (0, _dedupe.default)(type, className, 'wp-block-embed__wrapper'); // Disabled because the overlay div doesn't actually have a role or functionality
      // as far as the user is concerned. We're just catching the first click so that
      // the block can be selected without interacting with the embed preview that the overlay covers.

      /* eslint-disable jsx-a11y/no-static-element-interactions */

      var embedWrapper = 'wp-embed' === type ? (0, _element.createElement)(_wpEmbedPreview.default, {
        html: html
      }) : (0, _element.createElement)("div", {
        className: "wp-block-embed__wrapper"
      }, (0, _element.createElement)(_components.SandBox, {
        html: html,
        scripts: scripts,
        title: iframeTitle,
        type: sandboxClassnames,
        onFocus: this.hideOverlay
      }), !interactive && (0, _element.createElement)("div", {
        className: "block-library-embed__interactive-overlay",
        onMouseUp: this.hideOverlay
      }));
      /* eslint-enable jsx-a11y/no-static-element-interactions */

      return (0, _element.createElement)("figure", {
        className: (0, _dedupe.default)(className, 'wp-block-embed', {
          'is-type-video': 'video' === type
        })
      }, previewable ? embedWrapper : (0, _element.createElement)(_components.Placeholder, {
        icon: (0, _element.createElement)(_blockEditor.BlockIcon, {
          icon: icon,
          showColors: true
        }),
        label: label
      }, (0, _element.createElement)("p", {
        className: "components-placeholder__error"
      }, (0, _element.createElement)("a", {
        href: url
      }, url)), (0, _element.createElement)("p", {
        className: "components-placeholder__error"
      }, (0, _i18n.sprintf)(
      /* translators: %s: host providing embed content e.g: www.youtube.com */
      (0, _i18n.__)("Embedded content from %s can't be previewed in the editor."), parsedHostBaseUrl))), (!_blockEditor.RichText.isEmpty(caption) || isSelected) && (0, _element.createElement)(_blockEditor.RichText, {
        tagName: "figcaption",
        placeholder: (0, _i18n.__)('Write caption…'),
        value: caption,
        onChange: onCaptionChange,
        inlineToolbar: true,
        __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
          return insertBlocksAfter((0, _blocks.createBlock)('core/paragraph'));
        }
      }));
    }
  }], [{
    key: "getDerivedStateFromProps",
    value: function getDerivedStateFromProps(nextProps, state) {
      if (!nextProps.isSelected && state.interactive) {
        // We only want to change this when the block is not selected, because changing it when
        // the block becomes selected makes the overlap disappear too early. Hiding the overlay
        // happens on mouseup when the overlay is clicked.
        return {
          interactive: false
        };
      }

      return null;
    }
  }]);
  return EmbedPreview;
}(_element.Component);

var _default = EmbedPreview;
exports.default = _default;
//# sourceMappingURL=embed-preview.js.map