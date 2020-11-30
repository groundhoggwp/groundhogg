import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { parse } from '@wordpress/blocks';
import { useMemo, useCallback } from '@wordpress/element';
import { ENTER, SPACE } from '@wordpress/keycodes';
import { __, sprintf } from '@wordpress/i18n';
import { BlockPreview } from '@wordpress/block-editor';
import { Icon } from '@wordpress/components';
import { useAsyncList } from '@wordpress/compose';
/**
 * External dependencies
 */

import { groupBy, uniq, deburr } from 'lodash';

function PreviewPlaceholder() {
  return createElement("div", {
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
  var blocks = useMemo(function () {
    return parse(content);
  }, [content]);

  var _useDispatch = useDispatch('core/notices'),
      createSuccessNotice = _useDispatch.createSuccessNotice;

  var onClick = useCallback(function () {
    setAttributes({
      postId: id,
      slug: slug,
      theme: theme
    });
    createSuccessNotice(sprintf(
    /* translators: %s: template part title. */
    __('Template Part "%s" inserted.'), slug), {
      type: 'snackbar'
    });
    onClose();
  }, [id, slug, theme]);
  return createElement("div", {
    className: "wp-block-template-part__selection-preview-item",
    role: "button",
    onClick: onClick,
    onKeyDown: function onKeyDown(event) {
      if (ENTER === event.keyCode || SPACE === event.keyCode) {
        onClick();
      }
    },
    tabIndex: 0,
    "aria-label": templatePart.slug
  }, createElement(BlockPreview, {
    blocks: blocks
  }), createElement("div", {
    className: "wp-block-template-part__selection-preview-item-title"
  }, templatePart.slug));
}

function PanelGroup(_ref2) {
  var title = _ref2.title,
      icon = _ref2.icon,
      children = _ref2.children;
  return createElement(Fragment, null, createElement("div", {
    className: "wp-block-template-part__selection-panel-group-header"
  }, createElement("span", {
    className: "wp-block-template-part__selection-panel-group-title"
  }, title), createElement(Icon, {
    icon: icon
  })), createElement("div", {
    className: "wp-block-template-part__selection-panel-group-content"
  }, children));
}

function TemplatePartsByTheme(_ref3) {
  var templateParts = _ref3.templateParts,
      setAttributes = _ref3.setAttributes,
      onClose = _ref3.onClose;
  var templatePartsByTheme = useMemo(function () {
    return Object.values(groupBy(templateParts, 'meta.theme'));
  }, [templateParts]);
  var currentShownTPs = useAsyncList(templateParts);
  return templatePartsByTheme.map(function (templatePartList) {
    return createElement(PanelGroup, {
      key: templatePartList[0].meta.theme,
      title: templatePartList[0].meta.theme
    }, templatePartList.map(function (templatePart) {
      return currentShownTPs.includes(templatePart) ? createElement(TemplatePartItem, {
        key: templatePart.id,
        templatePart: templatePart,
        setAttributes: setAttributes,
        onClose: onClose
      }) : createElement(PreviewPlaceholder, {
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
  var filteredTPs = useMemo(function () {
    // Filter based on value.
    // Remove diacritics and convert to lowercase to normalize.
    var normalizedFilterValue = deburr(filterValue).toLowerCase();
    var searchResults = templateParts.filter(function (_ref5) {
      var slug = _ref5.slug,
          theme = _ref5.meta.theme;
      return slug.toLowerCase().includes(normalizedFilterValue) || // Since diacritics can be used in theme names, remove them for the comparison.
      deburr(theme).toLowerCase().includes(normalizedFilterValue);
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


      return deburr(a.meta.theme).toLowerCase().indexOf(normalizedFilterValue) - deburr(b.meta.theme).toLowerCase().indexOf(normalizedFilterValue);
    });
    return searchResults;
  }, [filterValue, templateParts]);
  var currentShownTPs = useAsyncList(filteredTPs);
  return filteredTPs.map(function (templatePart) {
    return createElement(PanelGroup, {
      key: templatePart.id,
      title: templatePart.meta.theme
    }, currentShownTPs.includes(templatePart) ? createElement(TemplatePartItem, {
      key: templatePart.id,
      templatePart: templatePart,
      setAttributes: setAttributes,
      onClose: onClose
    }) : createElement(PreviewPlaceholder, {
      key: templatePart.id
    }));
  });
}

export default function TemplateParts(_ref6) {
  var setAttributes = _ref6.setAttributes,
      filterValue = _ref6.filterValue,
      onClose = _ref6.onClose;
  var templateParts = useSelect(function (select) {
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
      combinedTemplateParts.push.apply(combinedTemplateParts, _toConsumableArray(publishedTemplateParts));
    }

    if (themeTemplateParts) {
      combinedTemplateParts.push.apply(combinedTemplateParts, _toConsumableArray(themeTemplateParts));
    }

    return uniq(combinedTemplateParts);
  }, []);

  if (!templateParts || !templateParts.length) {
    return null;
  }

  if (filterValue) {
    return createElement(TemplatePartSearchResults, {
      templateParts: templateParts,
      setAttributes: setAttributes,
      filterValue: filterValue,
      onClose: onClose
    });
  }

  return createElement(TemplatePartsByTheme, {
    templateParts: templateParts,
    setAttributes: setAttributes,
    onClose: onClose
  });
}
//# sourceMappingURL=template-part-previews.js.map