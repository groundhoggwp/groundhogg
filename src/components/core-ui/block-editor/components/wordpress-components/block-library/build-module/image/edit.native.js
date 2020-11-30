import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import React from 'react';
import { View, TouchableWithoutFeedback } from 'react-native';
import { isEmpty, get, find, map } from 'lodash';
/**
 * WordPress dependencies
 */

import { requestMediaImport, mediaUploadSync, requestImageFailedRetryDialog, requestImageUploadCancelDialog, requestImageFullscreenPreview } from '@wordpress/react-native-bridge';
import { CycleSelectControl, Icon, PanelBody, TextControl, ToggleControl, ToolbarButton, ToolbarGroup, Image, WIDE_ALIGNMENTS } from '@wordpress/components';
import { BlockCaption, MediaPlaceholder, MediaUpload, MediaUploadProgress, MEDIA_TYPE_IMAGE, BlockControls, InspectorControls, BlockAlignmentToolbar, BlockStyles } from '@wordpress/block-editor';
import { __, sprintf } from '@wordpress/i18n';
import { getProtocol, hasQueryArg } from '@wordpress/url';
import { doAction, hasAction } from '@wordpress/hooks';
import { compose, withPreferredColorScheme } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { external, link, image as placeholderIcon, textColor, replace, expand } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import styles from './styles.scss';
import { getUpdatedLinkTargetSettings } from './utils';
import { LINK_DESTINATION_CUSTOM, DEFAULT_SIZE_SLUG } from './constants';

var getUrlForSlug = function getUrlForSlug(image, _ref) {
  var sizeSlug = _ref.sizeSlug;
  return get(image, ['media_details', 'sizes', sizeSlug, 'source_url']);
};

export var ImageEdit = /*#__PURE__*/function (_React$Component) {
  _inherits(ImageEdit, _React$Component);

  var _super = _createSuper(ImageEdit);

  function ImageEdit(props) {
    var _this;

    _classCallCheck(this, ImageEdit);

    _this = _super.call(this, props);
    _this.state = {
      isCaptionSelected: false
    };
    _this.finishMediaUploadWithSuccess = _this.finishMediaUploadWithSuccess.bind(_assertThisInitialized(_this));
    _this.finishMediaUploadWithFailure = _this.finishMediaUploadWithFailure.bind(_assertThisInitialized(_this));
    _this.mediaUploadStateReset = _this.mediaUploadStateReset.bind(_assertThisInitialized(_this));
    _this.onSelectMediaUploadOption = _this.onSelectMediaUploadOption.bind(_assertThisInitialized(_this));
    _this.updateMediaProgress = _this.updateMediaProgress.bind(_assertThisInitialized(_this));
    _this.updateAlt = _this.updateAlt.bind(_assertThisInitialized(_this));
    _this.updateImageURL = _this.updateImageURL.bind(_assertThisInitialized(_this));
    _this.onSetLinkDestination = _this.onSetLinkDestination.bind(_assertThisInitialized(_this));
    _this.onSetNewTab = _this.onSetNewTab.bind(_assertThisInitialized(_this));
    _this.onSetSizeSlug = _this.onSetSizeSlug.bind(_assertThisInitialized(_this));
    _this.onImagePressed = _this.onImagePressed.bind(_assertThisInitialized(_this));
    _this.onFocusCaption = _this.onFocusCaption.bind(_assertThisInitialized(_this));
    _this.updateAlignment = _this.updateAlignment.bind(_assertThisInitialized(_this));
    _this.accessibilityLabelCreator = _this.accessibilityLabelCreator.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(ImageEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this$props = this.props,
          attributes = _this$props.attributes,
          setAttributes = _this$props.setAttributes; // This will warn when we have `id` defined, while `url` is undefined.
      // This may help track this issue: https://github.com/wordpress-mobile/WordPress-Android/issues/9768
      // where a cancelled image upload was resulting in a subsequent crash.

      if (attributes.id && !attributes.url) {
        // eslint-disable-next-line no-console
        console.warn('Attributes has id with no url.');
      } // Detect any pasted image and start an upload


      if (!attributes.id && attributes.url && getProtocol(attributes.url) === 'file:') {
        requestMediaImport(attributes.url, function (id, url) {
          if (url) {
            setAttributes({
              id: id,
              url: url
            });
          }
        });
      } // Make sure we mark any temporary images as failed if they failed while
      // the editor wasn't open


      if (attributes.id && attributes.url && getProtocol(attributes.url) === 'file:') {
        mediaUploadSync();
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      // this action will only exist if the user pressed the trash button on the block holder
      if (hasAction('blocks.onRemoveBlockCheckUpload') && this.state.isUploadInProgress) {
        doAction('blocks.onRemoveBlockCheckUpload', this.props.attributes.id);
      }
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(previousProps) {
      if (!previousProps.image && this.props.image) {
        var _this$props2 = this.props,
            image = _this$props2.image,
            attributes = _this$props2.attributes;
        var url = getUrlForSlug(image, attributes) || image.source_url;
        this.props.setAttributes({
          url: url
        });
      }
    }
  }, {
    key: "accessibilityLabelCreator",
    value: function accessibilityLabelCreator(caption) {
      return isEmpty(caption) ?
      /* translators: accessibility text. Empty image caption. */
      'Image caption. Empty' : sprintf(
      /* translators: accessibility text. %s: image caption. */
      __('Image caption. %s'), caption);
    }
  }, {
    key: "onImagePressed",
    value: function onImagePressed() {
      var _this$props3 = this.props,
          attributes = _this$props3.attributes,
          image = _this$props3.image;

      if (this.state.isUploadInProgress) {
        requestImageUploadCancelDialog(attributes.id);
      } else if (attributes.id && getProtocol(attributes.url) === 'file:') {
        requestImageFailedRetryDialog(attributes.id);
      } else if (!this.state.isCaptionSelected) {
        requestImageFullscreenPreview(attributes.url, image && image.source_url);
      }

      this.setState({
        isCaptionSelected: false
      });
    }
  }, {
    key: "updateMediaProgress",
    value: function updateMediaProgress(payload) {
      var setAttributes = this.props.setAttributes;

      if (payload.mediaUrl) {
        setAttributes({
          url: payload.mediaUrl
        });
      }

      if (!this.state.isUploadInProgress) {
        this.setState({
          isUploadInProgress: true
        });
      }
    }
  }, {
    key: "finishMediaUploadWithSuccess",
    value: function finishMediaUploadWithSuccess(payload) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        url: payload.mediaUrl,
        id: payload.mediaServerId
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "finishMediaUploadWithFailure",
    value: function finishMediaUploadWithFailure(payload) {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        id: payload.mediaId
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "mediaUploadStateReset",
    value: function mediaUploadStateReset() {
      var setAttributes = this.props.setAttributes;
      setAttributes({
        id: null,
        url: null
      });
      this.setState({
        isUploadInProgress: false
      });
    }
  }, {
    key: "updateAlt",
    value: function updateAlt(newAlt) {
      this.props.setAttributes({
        alt: newAlt
      });
    }
  }, {
    key: "updateImageURL",
    value: function updateImageURL(url) {
      this.props.setAttributes({
        url: url,
        width: undefined,
        height: undefined
      });
    }
  }, {
    key: "updateAlignment",
    value: function updateAlignment(nextAlign) {
      var extraUpdatedAttributes = Object.values(WIDE_ALIGNMENTS.alignments).includes(nextAlign) ? {
        width: undefined,
        height: undefined
      } : {};
      this.props.setAttributes(_objectSpread(_objectSpread({}, extraUpdatedAttributes), {}, {
        align: nextAlign
      }));
    }
  }, {
    key: "onSetLinkDestination",
    value: function onSetLinkDestination(href) {
      this.props.setAttributes({
        linkDestination: LINK_DESTINATION_CUSTOM,
        href: href
      });
    }
  }, {
    key: "onSetNewTab",
    value: function onSetNewTab(value) {
      var updatedLinkTarget = getUpdatedLinkTargetSettings(value, this.props.attributes);
      this.props.setAttributes(updatedLinkTarget);
    }
  }, {
    key: "onSetSizeSlug",
    value: function onSetSizeSlug(sizeSlug) {
      var image = this.props.image;
      var url = getUrlForSlug(image, {
        sizeSlug: sizeSlug
      });

      if (!url) {
        return null;
      }

      this.props.setAttributes({
        url: url,
        width: undefined,
        height: undefined,
        sizeSlug: sizeSlug
      });
    }
  }, {
    key: "onSelectMediaUploadOption",
    value: function onSelectMediaUploadOption(media) {
      var _this$props$attribute = this.props.attributes,
          id = _this$props$attribute.id,
          url = _this$props$attribute.url;
      var mediaAttributes = {
        id: media.id,
        url: media.url,
        caption: media.caption
      };
      var additionalAttributes; // Reset the dimension attributes if changing to a different image.

      if (!media.id || media.id !== id) {
        additionalAttributes = {
          width: undefined,
          height: undefined,
          sizeSlug: DEFAULT_SIZE_SLUG
        };
      } else {
        // Keep the same url when selecting the same file, so "Image Size" option is not changed.
        additionalAttributes = {
          url: url
        };
      }

      this.props.setAttributes(_objectSpread(_objectSpread({}, mediaAttributes), additionalAttributes));
    }
  }, {
    key: "onFocusCaption",
    value: function onFocusCaption() {
      if (this.props.onFocus) {
        this.props.onFocus();
      }

      if (!this.state.isCaptionSelected) {
        this.setState({
          isCaptionSelected: true
        });
      }
    }
  }, {
    key: "getPlaceholderIcon",
    value: function getPlaceholderIcon() {
      return createElement(Icon, _extends({
        icon: placeholderIcon
      }, this.props.getStylesFromColorScheme(styles.iconPlaceholder, styles.iconPlaceholderDark)));
    }
  }, {
    key: "getWidth",
    value: function getWidth() {
      var attributes = this.props.attributes;
      var align = attributes.align,
          width = attributes.width;
      return Object.values(WIDE_ALIGNMENTS.alignments).includes(align) ? '100%' : width;
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this;

      var isCaptionSelected = this.state.isCaptionSelected;
      var _this$props4 = this.props,
          attributes = _this$props4.attributes,
          isSelected = _this$props4.isSelected,
          image = _this$props4.image,
          imageSizes = _this$props4.imageSizes,
          clientId = _this$props4.clientId;
      var align = attributes.align,
          url = attributes.url,
          alt = attributes.alt,
          href = attributes.href,
          id = attributes.id,
          linkTarget = attributes.linkTarget,
          sizeSlug = attributes.sizeSlug,
          className = attributes.className;
      var sizeOptions = map(imageSizes, function (_ref2) {
        var name = _ref2.name,
            slug = _ref2.slug;
        return {
          value: slug,
          name: name
        };
      });
      var sizeOptionsValid = find(sizeOptions, ['value', DEFAULT_SIZE_SLUG]);

      var getToolbarEditButton = function getToolbarEditButton(open) {
        return createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarButton, {
          title: __('Edit image'),
          icon: replace,
          onClick: open
        })), createElement(BlockAlignmentToolbar, {
          value: align,
          onChange: _this2.updateAlignment
        }));
      };

      var getInspectorControls = function getInspectorControls() {
        return createElement(InspectorControls, null, createElement(PanelBody, {
          title: __('Image settings')
        }), createElement(PanelBody, {
          style: styles.panelBody
        }, image && createElement(BlockStyles, {
          clientId: clientId,
          url: url
        })), createElement(PanelBody, null, createElement(TextControl, {
          icon: link,
          label: __('Link To'),
          value: href || '',
          valuePlaceholder: __('Add URL'),
          onChange: _this2.onSetLinkDestination,
          autoCapitalize: "none",
          autoCorrect: false,
          keyboardType: "url"
        }), createElement(ToggleControl, {
          icon: external,
          label: __('Open in new tab'),
          checked: linkTarget === '_blank',
          onChange: _this2.onSetNewTab
        }), image && sizeOptionsValid && createElement(CycleSelectControl, {
          icon: expand,
          label: __('Size'),
          value: sizeSlug || DEFAULT_SIZE_SLUG,
          onChangeValue: function onChangeValue(newValue) {
            return _this2.onSetSizeSlug(newValue);
          },
          options: sizeOptions
        }), createElement(TextControl, {
          icon: textColor,
          label: __('Alt Text'),
          value: alt || '',
          valuePlaceholder: __('None'),
          onChangeValue: _this2.updateAlt
        })));
      };

      if (!url) {
        return createElement(View, {
          style: styles.content
        }, createElement(MediaPlaceholder, {
          allowedTypes: [MEDIA_TYPE_IMAGE],
          onSelect: this.onSelectMediaUploadOption,
          icon: this.getPlaceholderIcon(),
          onFocus: this.props.onFocus
        }));
      }

      var alignToFlex = {
        left: 'flex-start',
        center: 'center',
        right: 'flex-end',
        full: 'center',
        wide: 'center'
      };

      var getImageComponent = function getImageComponent(openMediaOptions, getMediaOptions) {
        return createElement(Fragment, null, createElement(TouchableWithoutFeedback, {
          accessible: !isSelected,
          onPress: _this2.onImagePressed,
          onLongPress: openMediaOptions,
          disabled: !isSelected
        }, createElement(View, {
          style: styles.content
        }, getInspectorControls(), getMediaOptions(), !_this2.state.isCaptionSelected && getToolbarEditButton(openMediaOptions), createElement(MediaUploadProgress, {
          coverUrl: url,
          mediaId: id,
          onUpdateMediaProgress: _this2.updateMediaProgress,
          onFinishMediaUploadWithSuccess: _this2.finishMediaUploadWithSuccess,
          onFinishMediaUploadWithFailure: _this2.finishMediaUploadWithFailure,
          onMediaUploadStateReset: _this2.mediaUploadStateReset,
          renderContent: function renderContent(_ref3) {
            var isUploadInProgress = _ref3.isUploadInProgress,
                isUploadFailed = _ref3.isUploadFailed,
                retryMessage = _ref3.retryMessage;
            return createElement(Image, {
              align: align && alignToFlex[align],
              alt: alt,
              isSelected: isSelected && !isCaptionSelected,
              isUploadFailed: isUploadFailed,
              isUploadInProgress: isUploadInProgress,
              onSelectMediaUploadOption: _this2.onSelectMediaUploadOption,
              openMediaOptions: openMediaOptions,
              retryMessage: retryMessage,
              url: url,
              shapeStyle: styles[className],
              width: _this2.getWidth()
            });
          }
        }))), createElement(BlockCaption, {
          clientId: _this2.props.clientId,
          isSelected: _this2.state.isCaptionSelected,
          accessible: true,
          accessibilityLabelCreator: _this2.accessibilityLabelCreator,
          onFocus: _this2.onFocusCaption,
          onBlur: _this2.props.onBlur // always assign onBlur as props
          ,
          insertBlocksAfter: _this2.props.insertBlocksAfter
        }));
      };

      return createElement(MediaUpload, {
        allowedTypes: [MEDIA_TYPE_IMAGE],
        isReplacingMedia: true,
        onSelect: this.onSelectMediaUploadOption,
        render: function render(_ref4) {
          var open = _ref4.open,
              getMediaOptions = _ref4.getMediaOptions;
          return getImageComponent(open, getMediaOptions);
        }
      });
    }
  }], [{
    key: "getDerivedStateFromProps",
    value: function getDerivedStateFromProps(props, state) {
      // Avoid a UI flicker in the toolbar by insuring that isCaptionSelected
      // is updated immediately any time the isSelected prop becomes false
      return {
        isCaptionSelected: props.isSelected && state.isCaptionSelected
      };
    }
  }]);

  return ImageEdit;
}(React.Component);
export default compose([withSelect(function (select, props) {
  var _select = select('core'),
      getMedia = _select.getMedia;

  var _select2 = select('core/block-editor'),
      getSettings = _select2.getSettings;

  var _props$attributes = props.attributes,
      id = _props$attributes.id,
      url = _props$attributes.url,
      isSelected = props.isSelected;

  var _getSettings = getSettings(),
      imageSizes = _getSettings.imageSizes;

  var isNotFileUrl = id && getProtocol(url) !== 'file:';
  var shouldGetMedia = isSelected && isNotFileUrl || // Edge case to update the image after uploading if the block gets unselected
  // Check if it's the original image and not the resized one with queryparams
  !isSelected && isNotFileUrl && url && !hasQueryArg(url, 'w');
  return {
    image: shouldGetMedia ? getMedia(id) : null,
    imageSizes: imageSizes
  };
}), withPreferredColorScheme])(ImageEdit);
//# sourceMappingURL=edit.native.js.map