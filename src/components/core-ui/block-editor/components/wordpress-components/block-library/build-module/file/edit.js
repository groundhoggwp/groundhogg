import _extends from "@babel/runtime/helpers/esm/extends";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
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
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { getBlobByURL, isBlobURL, revokeBlobURL } from '@wordpress/blob';
import { Animate, ClipboardButton, withNotices } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { BlockControls, BlockIcon, MediaPlaceholder, MediaReplaceFlow, RichText } from '@wordpress/block-editor';
import { Component } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { file as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import FileBlockInspector from './inspector';

var FileEdit = /*#__PURE__*/function (_Component) {
  _inherits(FileEdit, _Component);

  var _super = _createSuper(FileEdit);

  function FileEdit() {
    var _this;

    _classCallCheck(this, FileEdit);

    _this = _super.apply(this, arguments);
    _this.onSelectFile = _this.onSelectFile.bind(_assertThisInitialized(_this));
    _this.confirmCopyURL = _this.confirmCopyURL.bind(_assertThisInitialized(_this));
    _this.resetCopyConfirmation = _this.resetCopyConfirmation.bind(_assertThisInitialized(_this));
    _this.changeLinkDestinationOption = _this.changeLinkDestinationOption.bind(_assertThisInitialized(_this));
    _this.changeOpenInNewWindow = _this.changeOpenInNewWindow.bind(_assertThisInitialized(_this));
    _this.changeShowDownloadButton = _this.changeShowDownloadButton.bind(_assertThisInitialized(_this));
    _this.onUploadError = _this.onUploadError.bind(_assertThisInitialized(_this));
    _this.state = {
      hasError: false,
      showCopyConfirmation: false
    };
    return _this;
  }

  _createClass(FileEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      var _this$props = this.props,
          attributes = _this$props.attributes,
          mediaUpload = _this$props.mediaUpload,
          noticeOperations = _this$props.noticeOperations,
          setAttributes = _this$props.setAttributes;
      var downloadButtonText = attributes.downloadButtonText,
          href = attributes.href; // Upload a file drag-and-dropped into the editor

      if (isBlobURL(href)) {
        var file = getBlobByURL(href);
        mediaUpload({
          filesList: [file],
          onFileChange: function onFileChange(_ref) {
            var _ref2 = _slicedToArray(_ref, 1),
                media = _ref2[0];

            return _this2.onSelectFile(media);
          },
          onError: function onError(message) {
            _this2.setState({
              hasError: true
            });

            noticeOperations.createErrorNotice(message);
          }
        });
        revokeBlobURL(href);
      }

      if (downloadButtonText === undefined) {
        setAttributes({
          downloadButtonText: _x('Download', 'button label')
        });
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      // Reset copy confirmation state when block is deselected
      if (prevProps.isSelected && !this.props.isSelected) {
        this.setState({
          showCopyConfirmation: false
        });
      }
    }
  }, {
    key: "onSelectFile",
    value: function onSelectFile(media) {
      if (media && media.url) {
        this.setState({
          hasError: false
        });
        this.props.setAttributes({
          href: media.url,
          fileName: media.title,
          textLinkHref: media.url,
          id: media.id
        });
      }
    }
  }, {
    key: "onUploadError",
    value: function onUploadError(message) {
      var noticeOperations = this.props.noticeOperations;
      this.setState({
        hasError: true
      });
      noticeOperations.removeAllNotices();
      noticeOperations.createErrorNotice(message);
    }
  }, {
    key: "confirmCopyURL",
    value: function confirmCopyURL() {
      this.setState({
        showCopyConfirmation: true
      });
    }
  }, {
    key: "resetCopyConfirmation",
    value: function resetCopyConfirmation() {
      this.setState({
        showCopyConfirmation: false
      });
    }
  }, {
    key: "changeLinkDestinationOption",
    value: function changeLinkDestinationOption(newHref) {
      // Choose Media File or Attachment Page (when file is in Media Library)
      this.props.setAttributes({
        textLinkHref: newHref
      });
    }
  }, {
    key: "changeOpenInNewWindow",
    value: function changeOpenInNewWindow(newValue) {
      this.props.setAttributes({
        textLinkTarget: newValue ? '_blank' : false
      });
    }
  }, {
    key: "changeShowDownloadButton",
    value: function changeShowDownloadButton(newValue) {
      this.props.setAttributes({
        showDownloadButton: newValue
      });
    }
  }, {
    key: "render",
    value: function render() {
      var _this3 = this;

      var _this$props2 = this.props,
          className = _this$props2.className,
          isSelected = _this$props2.isSelected,
          attributes = _this$props2.attributes,
          setAttributes = _this$props2.setAttributes,
          noticeUI = _this$props2.noticeUI,
          media = _this$props2.media;
      var id = attributes.id,
          fileName = attributes.fileName,
          href = attributes.href,
          textLinkHref = attributes.textLinkHref,
          textLinkTarget = attributes.textLinkTarget,
          showDownloadButton = attributes.showDownloadButton,
          downloadButtonText = attributes.downloadButtonText;
      var _this$state = this.state,
          hasError = _this$state.hasError,
          showCopyConfirmation = _this$state.showCopyConfirmation;
      var attachmentPage = media && media.link;

      if (!href || hasError) {
        return createElement(MediaPlaceholder, {
          icon: createElement(BlockIcon, {
            icon: icon
          }),
          labels: {
            title: __('File'),
            instructions: __('Upload a file or pick one from your media library.')
          },
          onSelect: this.onSelectFile,
          notices: noticeUI,
          onError: this.onUploadError,
          accept: "*"
        });
      }

      var classes = classnames(className, {
        'is-transient': isBlobURL(href)
      });
      return createElement(Fragment, null, createElement(FileBlockInspector, _extends({
        hrefs: {
          href: href,
          textLinkHref: textLinkHref,
          attachmentPage: attachmentPage
        }
      }, {
        openInNewWindow: !!textLinkTarget,
        showDownloadButton: showDownloadButton,
        changeLinkDestinationOption: this.changeLinkDestinationOption,
        changeOpenInNewWindow: this.changeOpenInNewWindow,
        changeShowDownloadButton: this.changeShowDownloadButton
      })), createElement(BlockControls, null, createElement(MediaReplaceFlow, {
        mediaId: id,
        mediaURL: href,
        accept: "*",
        onSelect: this.onSelectFile,
        onError: this.onUploadError
      })), createElement(Animate, {
        type: isBlobURL(href) ? 'loading' : null
      }, function (_ref3) {
        var animateClassName = _ref3.className;
        return createElement("div", {
          className: classnames(classes, animateClassName)
        }, createElement("div", {
          className: 'wp-block-file__content-wrapper'
        }, createElement("div", {
          className: "wp-block-file__textlink"
        }, createElement(RichText, {
          tagName: "div" // must be block-level or else cursor disappears
          ,
          value: fileName,
          placeholder: __('Write file name…'),
          withoutInteractiveFormatting: true,
          onChange: function onChange(text) {
            return setAttributes({
              fileName: text
            });
          }
        })), showDownloadButton && createElement("div", {
          className: 'wp-block-file__button-richtext-wrapper'
        }, createElement(RichText, {
          tagName: "div" // must be block-level or else cursor disappears
          ,
          className: 'wp-block-file__button',
          value: downloadButtonText,
          withoutInteractiveFormatting: true,
          placeholder: __('Add text…'),
          onChange: function onChange(text) {
            return setAttributes({
              downloadButtonText: text
            });
          }
        }))), isSelected && createElement(ClipboardButton, {
          isSecondary: true,
          text: href,
          className: 'wp-block-file__copy-url-button',
          onCopy: _this3.confirmCopyURL,
          onFinishCopy: _this3.resetCopyConfirmation,
          disabled: isBlobURL(href)
        }, showCopyConfirmation ? __('Copied!') : __('Copy URL')));
      }));
    }
  }]);

  return FileEdit;
}(Component);

export default compose([withSelect(function (select, props) {
  var _select = select('core'),
      getMedia = _select.getMedia;

  var _select2 = select('core/block-editor'),
      getSettings = _select2.getSettings;

  var _getSettings = getSettings(),
      mediaUpload = _getSettings.mediaUpload;

  var id = props.attributes.id;
  return {
    media: id === undefined ? undefined : getMedia(id),
    mediaUpload: mediaUpload
  };
}), withNotices])(FileEdit);
//# sourceMappingURL=edit.js.map