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
 * Internal dependencies
 */
import { getPhotoHtml } from './util';
/**
 * External dependencies
 */

import classnames from 'classnames/dedupe';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { Placeholder, SandBox } from '@wordpress/components';
import { RichText, BlockIcon } from '@wordpress/block-editor';
import { Component } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import WpEmbedPreview from './wp-embed-preview';

var EmbedPreview = /*#__PURE__*/function (_Component) {
  _inherits(EmbedPreview, _Component);

  var _super = _createSuper(EmbedPreview);

  function EmbedPreview() {
    var _this;

    _classCallCheck(this, EmbedPreview);

    _this = _super.apply(this, arguments);
    _this.hideOverlay = _this.hideOverlay.bind(_assertThisInitialized(_this));
    _this.state = {
      interactive: false
    };
    return _this;
  }

  _createClass(EmbedPreview, [{
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
      var html = 'photo' === type ? getPhotoHtml(preview) : preview.html;
      var parsedHost = new URL(url).host.split('.');
      var parsedHostBaseUrl = parsedHost.splice(parsedHost.length - 2, parsedHost.length - 1).join('.');
      var iframeTitle = sprintf( // translators: %s: host providing embed content e.g: www.youtube.com
      __('Embedded content from %s'), parsedHostBaseUrl);
      var sandboxClassnames = classnames(type, className, 'wp-block-embed__wrapper'); // Disabled because the overlay div doesn't actually have a role or functionality
      // as far as the user is concerned. We're just catching the first click so that
      // the block can be selected without interacting with the embed preview that the overlay covers.

      /* eslint-disable jsx-a11y/no-static-element-interactions */

      var embedWrapper = 'wp-embed' === type ? createElement(WpEmbedPreview, {
        html: html
      }) : createElement("div", {
        className: "wp-block-embed__wrapper"
      }, createElement(SandBox, {
        html: html,
        scripts: scripts,
        title: iframeTitle,
        type: sandboxClassnames,
        onFocus: this.hideOverlay
      }), !interactive && createElement("div", {
        className: "block-library-embed__interactive-overlay",
        onMouseUp: this.hideOverlay
      }));
      /* eslint-enable jsx-a11y/no-static-element-interactions */

      return createElement("figure", {
        className: classnames(className, 'wp-block-embed', {
          'is-type-video': 'video' === type
        })
      }, previewable ? embedWrapper : createElement(Placeholder, {
        icon: createElement(BlockIcon, {
          icon: icon,
          showColors: true
        }),
        label: label
      }, createElement("p", {
        className: "components-placeholder__error"
      }, createElement("a", {
        href: url
      }, url)), createElement("p", {
        className: "components-placeholder__error"
      }, sprintf(
      /* translators: %s: host providing embed content e.g: www.youtube.com */
      __("Embedded content from %s can't be previewed in the editor."), parsedHostBaseUrl))), (!RichText.isEmpty(caption) || isSelected) && createElement(RichText, {
        tagName: "figcaption",
        placeholder: __('Write caption…'),
        value: caption,
        onChange: onCaptionChange,
        inlineToolbar: true,
        __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
          return insertBlocksAfter(createBlock('core/paragraph'));
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
}(Component);

export default EmbedPreview;
//# sourceMappingURL=embed-preview.js.map