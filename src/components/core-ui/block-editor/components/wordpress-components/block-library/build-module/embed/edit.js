import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Internal dependencies
 */
import { createUpgradedEmbedBlock, getClassNames, fallback as _fallback, getAttributesFromPreview, getEmbedInfoByProvider } from './util';
import { settings } from './index';
import EmbedControls from './embed-controls';
import EmbedLoading from './embed-loading';
import EmbedPlaceholder from './embed-placeholder';
import EmbedPreview from './embed-preview';
/**
 * External dependencies
 */

import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

function getResponsiveHelp(checked) {
  return checked ? __('This embed will preserve its aspect ratio when the browser is resized.') : __('This embed may not preserve its aspect ratio when the browser is resized.');
}

var EmbedEdit = function EmbedEdit(props) {
  var _props$attributes = props.attributes,
      providerNameSlug = _props$attributes.providerNameSlug,
      previewable = _props$attributes.previewable,
      responsive = _props$attributes.responsive,
      attributesUrl = _props$attributes.url,
      attributes = props.attributes,
      isSelected = props.isSelected,
      onReplace = props.onReplace,
      setAttributes = props.setAttributes,
      insertBlocksAfter = props.insertBlocksAfter;
  var defaultEmbedInfo = {
    title: settings.title,
    icon: settings.icon
  };

  var _ref = getEmbedInfoByProvider(providerNameSlug) || defaultEmbedInfo,
      icon = _ref.icon,
      title = _ref.title;

  var _useState = useState(attributesUrl),
      _useState2 = _slicedToArray(_useState, 2),
      url = _useState2[0],
      setURL = _useState2[1];

  var _useState3 = useState(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isEditingURL = _useState4[0],
      setIsEditingURL = _useState4[1];

  var _useDispatch = useDispatch('core/data'),
      invalidateResolution = _useDispatch.invalidateResolution;

  var _useSelect = useSelect(function (select) {
    var _embedPreview$data;

    var _select = select('core'),
        getEmbedPreview = _select.getEmbedPreview,
        isPreviewEmbedFallback = _select.isPreviewEmbedFallback,
        isRequestingEmbedPreview = _select.isRequestingEmbedPreview,
        getThemeSupports = _select.getThemeSupports;

    if (!attributesUrl) {
      return {
        fetching: false,
        cannotEmbed: false
      };
    }

    var embedPreview = getEmbedPreview(attributesUrl);
    var previewIsFallback = isPreviewEmbedFallback(attributesUrl); // The external oEmbed provider does not exist. We got no type info and no html.

    var badEmbedProvider = (embedPreview === null || embedPreview === void 0 ? void 0 : embedPreview.html) === false && (embedPreview === null || embedPreview === void 0 ? void 0 : embedPreview.type) === undefined; // Some WordPress URLs that can't be embedded will cause the API to return
    // a valid JSON response with no HTML and `data.status` set to 404, rather
    // than generating a fallback response as other embeds do.

    var wordpressCantEmbed = (embedPreview === null || embedPreview === void 0 ? void 0 : (_embedPreview$data = embedPreview.data) === null || _embedPreview$data === void 0 ? void 0 : _embedPreview$data.status) === 404;
    var validPreview = !!embedPreview && !badEmbedProvider && !wordpressCantEmbed;
    return {
      preview: validPreview ? embedPreview : undefined,
      fetching: isRequestingEmbedPreview(attributesUrl),
      themeSupportsResponsive: getThemeSupports()['responsive-embeds'],
      cannotEmbed: !validPreview || previewIsFallback
    };
  }, [attributesUrl]),
      preview = _useSelect.preview,
      fetching = _useSelect.fetching,
      themeSupportsResponsive = _useSelect.themeSupportsResponsive,
      cannotEmbed = _useSelect.cannotEmbed;
  /**
   * @return {Object} Attributes derived from the preview, merged with the current attributes.
   */


  var getMergedAttributes = function getMergedAttributes() {
    var allowResponsive = attributes.allowResponsive,
        className = attributes.className;
    return _objectSpread(_objectSpread({}, attributes), getAttributesFromPreview(preview, title, className, responsive, allowResponsive));
  };

  var toggleResponsive = function toggleResponsive() {
    var allowResponsive = attributes.allowResponsive,
        className = attributes.className;
    var html = preview.html;
    var newAllowResponsive = !allowResponsive;
    setAttributes({
      allowResponsive: newAllowResponsive,
      className: getClassNames(html, className, responsive && newAllowResponsive)
    });
  };

  useEffect(function () {
    if (!(preview === null || preview === void 0 ? void 0 : preview.html) || !cannotEmbed || fetching) {
      return;
    } // At this stage, we're not fetching the preview and know it can't be embedded,
    // so try removing any trailing slash, and resubmit.


    var newURL = attributesUrl.replace(/\/$/, '');
    setURL(newURL);
    setIsEditingURL(false);
    setAttributes({
      url: newURL
    });
  }, [preview === null || preview === void 0 ? void 0 : preview.html, attributesUrl]); // Handle incoming preview

  useEffect(function () {
    if (preview && !isEditingURL) {
      // Even though we set attributes that get derived from the preview,
      // we don't access them directly because for the initial render,
      // the `setAttributes` call will not have taken effect. If we're
      // rendering responsive content, setting the responsive classes
      // after the preview has been rendered can result in unwanted
      // clipping or scrollbars. The `getAttributesFromPreview` function
      // that `getMergedAttributes` uses is memoized so that we're not
      // calculating them on every render.
      setAttributes(getMergedAttributes());

      if (onReplace) {
        var upgradedBlock = createUpgradedEmbedBlock(props, getMergedAttributes());

        if (upgradedBlock) {
          onReplace(upgradedBlock);
        }
      }
    }
  }, [preview, isEditingURL]);

  if (fetching) {
    return createElement(EmbedLoading, null);
  } // translators: %s: type of embed e.g: "YouTube", "Twitter", etc. "Embed" is used when no specific type exists


  var label = sprintf(__('%s URL'), title); // No preview, or we can't embed the current URL, or we've clicked the edit button.

  var showEmbedPlaceholder = !preview || cannotEmbed || isEditingURL;

  if (showEmbedPlaceholder) {
    return createElement(EmbedPlaceholder, {
      icon: icon,
      label: label,
      onSubmit: function onSubmit(event) {
        if (event) {
          event.preventDefault();
        }

        setIsEditingURL(false);
        setAttributes({
          url: url
        });
      },
      value: url,
      cannotEmbed: cannotEmbed,
      onChange: function onChange(event) {
        return setURL(event.target.value);
      },
      fallback: function fallback() {
        return _fallback(url, onReplace);
      },
      tryAgain: function tryAgain() {
        invalidateResolution('core', 'getEmbedPreview', [url]);
      }
    });
  } // Even though we set attributes that get derived from the preview,
  // we don't access them directly because for the initial render,
  // the `setAttributes` call will not have taken effect. If we're
  // rendering responsive content, setting the responsive classes
  // after the preview has been rendered can result in unwanted
  // clipping or scrollbars. The `getAttributesFromPreview` function
  // that `getMergedAttributes` uses is memoized so that we're not


  var _getMergedAttributes = getMergedAttributes(),
      caption = _getMergedAttributes.caption,
      type = _getMergedAttributes.type,
      allowResponsive = _getMergedAttributes.allowResponsive,
      classFromPreview = _getMergedAttributes.className;

  var className = classnames(classFromPreview, props.className);
  return createElement(Fragment, null, createElement(EmbedControls, {
    showEditButton: preview && !cannotEmbed,
    themeSupportsResponsive: themeSupportsResponsive,
    blockSupportsResponsive: responsive,
    allowResponsive: allowResponsive,
    getResponsiveHelp: getResponsiveHelp,
    toggleResponsive: toggleResponsive,
    switchBackToURLInput: function switchBackToURLInput() {
      return setIsEditingURL(true);
    }
  }), createElement(EmbedPreview, {
    preview: preview,
    previewable: previewable,
    className: className,
    url: url,
    type: type,
    caption: caption,
    onCaptionChange: function onCaptionChange(value) {
      return setAttributes({
        caption: value
      });
    },
    isSelected: isSelected,
    icon: icon,
    label: label,
    insertBlocksAfter: insertBlocksAfter
  }));
};

export default EmbedEdit;
//# sourceMappingURL=edit.js.map