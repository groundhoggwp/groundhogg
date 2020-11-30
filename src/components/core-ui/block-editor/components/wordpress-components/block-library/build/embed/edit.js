"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _util = require("./util");

var _index = require("./index");

var _embedControls = _interopRequireDefault(require("./embed-controls"));

var _embedLoading = _interopRequireDefault(require("./embed-loading"));

var _embedPlaceholder = _interopRequireDefault(require("./embed-placeholder"));

var _embedPreview = _interopRequireDefault(require("./embed-preview"));

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function getResponsiveHelp(checked) {
  return checked ? (0, _i18n.__)('This embed will preserve its aspect ratio when the browser is resized.') : (0, _i18n.__)('This embed may not preserve its aspect ratio when the browser is resized.');
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
    title: _index.settings.title,
    icon: _index.settings.icon
  };

  var _ref = (0, _util.getEmbedInfoByProvider)(providerNameSlug) || defaultEmbedInfo,
      icon = _ref.icon,
      title = _ref.title;

  var _useState = (0, _element.useState)(attributesUrl),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      url = _useState2[0],
      setURL = _useState2[1];

  var _useState3 = (0, _element.useState)(false),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isEditingURL = _useState4[0],
      setIsEditingURL = _useState4[1];

  var _useDispatch = (0, _data.useDispatch)('core/data'),
      invalidateResolution = _useDispatch.invalidateResolution;

  var _useSelect = (0, _data.useSelect)(function (select) {
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
    return _objectSpread(_objectSpread({}, attributes), (0, _util.getAttributesFromPreview)(preview, title, className, responsive, allowResponsive));
  };

  var toggleResponsive = function toggleResponsive() {
    var allowResponsive = attributes.allowResponsive,
        className = attributes.className;
    var html = preview.html;
    var newAllowResponsive = !allowResponsive;
    setAttributes({
      allowResponsive: newAllowResponsive,
      className: (0, _util.getClassNames)(html, className, responsive && newAllowResponsive)
    });
  };

  (0, _element.useEffect)(function () {
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

  (0, _element.useEffect)(function () {
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
        var upgradedBlock = (0, _util.createUpgradedEmbedBlock)(props, getMergedAttributes());

        if (upgradedBlock) {
          onReplace(upgradedBlock);
        }
      }
    }
  }, [preview, isEditingURL]);

  if (fetching) {
    return (0, _element.createElement)(_embedLoading.default, null);
  } // translators: %s: type of embed e.g: "YouTube", "Twitter", etc. "Embed" is used when no specific type exists


  var label = (0, _i18n.sprintf)((0, _i18n.__)('%s URL'), title); // No preview, or we can't embed the current URL, or we've clicked the edit button.

  var showEmbedPlaceholder = !preview || cannotEmbed || isEditingURL;

  if (showEmbedPlaceholder) {
    return (0, _element.createElement)(_embedPlaceholder.default, {
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
        return (0, _util.fallback)(url, onReplace);
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

  var className = (0, _classnames.default)(classFromPreview, props.className);
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_embedControls.default, {
    showEditButton: preview && !cannotEmbed,
    themeSupportsResponsive: themeSupportsResponsive,
    blockSupportsResponsive: responsive,
    allowResponsive: allowResponsive,
    getResponsiveHelp: getResponsiveHelp,
    toggleResponsive: toggleResponsive,
    switchBackToURLInput: function switchBackToURLInput() {
      return setIsEditingURL(true);
    }
  }), (0, _element.createElement)(_embedPreview.default, {
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

var _default = EmbedEdit;
exports.default = _default;
//# sourceMappingURL=edit.js.map