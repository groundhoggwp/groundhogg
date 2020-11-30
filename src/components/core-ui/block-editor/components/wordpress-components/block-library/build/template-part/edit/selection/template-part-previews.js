"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = TemplateParts;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _data = require("@wordpress/data");

var _blocks = require("@wordpress/blocks");

var _keycodes = require("@wordpress/keycodes");

var _i18n = require("@wordpress/i18n");

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _lodash = require("lodash");

/**
 * WordPress dependencies
 */

/**
 * External dependencies
 */
function PreviewPlaceholder() {
  return (0, _element.createElement)("div", {
    className: "wp-block-template-part__selection-preview-item is-placeholder"
  });
}

function TemplatePartItem(_ref) {
  var templatePart = _ref.templatePart,
      setAttributes = _ref.setAttributes,
      onClose = _ref.onClose;
  var id = templatePart.id,
      slug = templatePart.slug,
      theme = templatePart.meta.theme; // The 'raw' property is not defined for a brief period in the save cycle.
  // The fallback prevents an error in the parse function while saving.

  var content = templatePart.content.raw || '';
  var blocks = (0, _element.useMemo)(function () {
    return (0, _blocks.parse)(content);
  }, [content]);

  var _useDispatch = (0, _data.useDispatch)('core/notices'),
      createSuccessNotice = _useDispatch.createSuccessNotice;

  var onClick = (0, _element.useCallback)(function () {
    setAttributes({
      postId: id,
      slug: slug,
      theme: theme
    });
    createSuccessNotice((0, _i18n.sprintf)(
    /* translators: %s: template part title. */
    (0, _i18n.__)('Template Part "%s" inserted.'), slug), {
      type: 'snackbar'
    });
    onClose();
  }, [id, slug, theme]);
  return (0, _element.createElement)("div", {
    className: "wp-block-template-part__selection-preview-item",
    role: "button",
    onClick: onClick,
    onKeyDown: function onKeyDown(event) {
      if (_keycodes.ENTER === event.keyCode || _keycodes.SPACE === event.keyCode) {
        onClick();
      }
    },
    tabIndex: 0,
    "aria-label": templatePart.slug
  }, (0, _element.createElement)(_blockEditor.BlockPreview, {
    blocks: blocks
  }), (0, _element.createElement)("div", {
    className: "wp-block-template-part__selection-preview-item-title"
  }, templatePart.slug));
}

function PanelGroup(_ref2) {
  var title = _ref2.title,
      icon = _ref2.icon,
      children = _ref2.children;
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)("div", {
    className: "wp-block-template-part__selection-panel-group-header"
  }, (0, _element.createElement)("span", {
    className: "wp-block-template-part__selection-panel-group-title"
  }, title), (0, _element.createElement)(_components.Icon, {
    icon: icon
  })), (0, _element.createElement)("div", {
    className: "wp-block-template-part__selection-panel-group-content"
  }, children));
}

function TemplatePartsByTheme(_ref3) {
  var templateParts = _ref3.templateParts,
      setAttributes = _ref3.setAttributes,
      onClose = _ref3.onClose;
  var templatePartsByTheme = (0, _element.useMemo)(function () {
    return Object.values((0, _lodash.groupBy)(templateParts, 'meta.theme'));
  }, [templateParts]);
  var currentShownTPs = (0, _compose.useAsyncList)(templateParts);
  return templatePartsByTheme.map(function (templatePartList) {
    return (0, _element.createElement)(PanelGroup, {
      key: templatePartList[0].meta.theme,
      title: templatePartList[0].meta.theme
    }, templatePartList.map(function (templatePart) {
      return currentShownTPs.includes(templatePart) ? (0, _element.createElement)(TemplatePartItem, {
        key: templatePart.id,
        templatePart: templatePart,
        setAttributes: setAttributes,
        onClose: onClose
      }) : (0, _element.createElement)(PreviewPlaceholder, {
        key: templatePart.id
      });
    }));
  });
}

function TemplatePartSearchResults(_ref4) {
  var templateParts = _ref4.templateParts,
      setAttributes = _ref4.setAttributes,
      filterValue = _ref4.filterValue,
      onClose = _ref4.onClose;
  var filteredTPs = (0, _element.useMemo)(function () {
    // Filter based on value.
    // Remove diacritics and convert to lowercase to normalize.
    var normalizedFilterValue = (0, _lodash.deburr)(filterValue).toLowerCase();
    var searchResults = templateParts.filter(function (_ref5) {
      var slug = _ref5.slug,
          theme = _ref5.meta.theme;
      return slug.toLowerCase().includes(normalizedFilterValue) || // Since diacritics can be used in theme names, remove them for the comparison.
      (0, _lodash.deburr)(theme).toLowerCase().includes(normalizedFilterValue);
    }); // Order based on value location.

    searchResults.sort(function (a, b) {
      // First prioritize index found in slug.
      var indexInSlugA = a.slug.toLowerCase().indexOf(normalizedFilterValue);
      var indexInSlugB = b.slug.toLowerCase().indexOf(normalizedFilterValue);

      if (indexInSlugA !== -1 && indexInSlugB !== -1) {
        return indexInSlugA - indexInSlugB;
      } else if (indexInSlugA !== -1) {
        return -1;
      } else if (indexInSlugB !== -1) {
        return 1;
      } // Second prioritize index found in theme.
      // Since diacritics can be used in theme names, remove them for the comparison.


      return (0, _lodash.deburr)(a.meta.theme).toLowerCase().indexOf(normalizedFilterValue) - (0, _lodash.deburr)(b.meta.theme).toLowerCase().indexOf(normalizedFilterValue);
    });
    return searchResults;
  }, [filterValue, templateParts]);
  var currentShownTPs = (0, _compose.useAsyncList)(filteredTPs);
  return filteredTPs.map(function (templatePart) {
    return (0, _element.createElement)(PanelGroup, {
      key: templatePart.id,
      title: templatePart.meta.theme
    }, currentShownTPs.includes(templatePart) ? (0, _element.createElement)(TemplatePartItem, {
      key: templatePart.id,
      templatePart: templatePart,
      setAttributes: setAttributes,
      onClose: onClose
    }) : (0, _element.createElement)(PreviewPlaceholder, {
      key: templatePart.id
    }));
  });
}

function TemplateParts(_ref6) {
  var setAttributes = _ref6.setAttributes,
      filterValue = _ref6.filterValue,
      onClose = _ref6.onClose;
  var templateParts = (0, _data.useSelect)(function (select) {
    var _select$getCurrentThe;

    var publishedTemplateParts = select('core').getEntityRecords('postType', 'wp_template_part', {
      status: ['publish'],
      per_page: -1
    });
    var currentTheme = (_select$getCurrentThe = select('core').getCurrentTheme()) === null || _select$getCurrentThe === void 0 ? void 0 : _select$getCurrentThe.textdomain;
    var themeTemplateParts = select('core').getEntityRecords('postType', 'wp_template_part', {
      theme: currentTheme,
      status: ['publish', 'auto-draft'],
      per_page: -1
    });
    var combinedTemplateParts = [];

    if (publishedTemplateParts) {
      combinedTemplateParts.push.apply(combinedTemplateParts, (0, _toConsumableArray2.default)(publishedTemplateParts));
    }

    if (themeTemplateParts) {
      combinedTemplateParts.push.apply(combinedTemplateParts, (0, _toConsumableArray2.default)(themeTemplateParts));
    }

    return (0, _lodash.uniq)(combinedTemplateParts);
  }, []);

  if (!templateParts || !templateParts.length) {
    return null;
  }

  if (filterValue) {
    return (0, _element.createElement)(TemplatePartSearchResults, {
      templateParts: templateParts,
      setAttributes: setAttributes,
      filterValue: filterValue,
      onClose: onClose
    });
  }

  return (0, _element.createElement)(TemplatePartsByTheme, {
    templateParts: templateParts,
    setAttributes: setAttributes,
    onClose: onClose
  });
}
//# sourceMappingURL=template-part-previews.js.map