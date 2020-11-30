import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { Button, Disabled, PanelBody, Placeholder, RangeControl, TextControl, ToggleControl, ToolbarGroup } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { grid, list, edit, rss } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
var DEFAULT_MIN_ITEMS = 1;
var DEFAULT_MAX_ITEMS = 10;
export default function RSSEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;

  var _useState = useState(!attributes.feedURL),
      _useState2 = _slicedToArray(_useState, 2),
      isEditing = _useState2[0],
      setIsEditing = _useState2[1];

  var blockLayout = attributes.blockLayout,
      columns = attributes.columns,
      displayAuthor = attributes.displayAuthor,
      displayDate = attributes.displayDate,
      displayExcerpt = attributes.displayExcerpt,
      excerptLength = attributes.excerptLength,
      feedURL = attributes.feedURL,
      itemsToShow = attributes.itemsToShow;

  function toggleAttribute(propName) {
    return function () {
      var value = attributes[propName];
      setAttributes(_defineProperty({}, propName, !value));
    };
  }

  function onSubmitURL(event) {
    event.preventDefault();

    if (feedURL) {
      setIsEditing(false);
    }
  }

  if (isEditing) {
    return createElement(Placeholder, {
      icon: rss,
      label: "RSS"
    }, createElement("form", {
      onSubmit: onSubmitURL,
      className: "wp-block-rss__placeholder-form"
    }, createElement(TextControl, {
      placeholder: __('Enter URL here…'),
      value: feedURL,
      onChange: function onChange(value) {
        return setAttributes({
          feedURL: value
        });
      },
      className: "wp-block-rss__placeholder-input"
    }), createElement(Button, {
      isPrimary: true,
      type: "submit"
    }, __('Use URL'))));
  }

  var toolbarControls = [{
    icon: edit,
    title: __('Edit RSS URL'),
    onClick: function onClick() {
      return setIsEditing(true);
    }
  }, {
    icon: list,
    title: __('List view'),
    onClick: function onClick() {
      return setAttributes({
        blockLayout: 'list'
      });
    },
    isActive: blockLayout === 'list'
  }, {
    icon: grid,
    title: __('Grid view'),
    onClick: function onClick() {
      return setAttributes({
        blockLayout: 'grid'
      });
    },
    isActive: blockLayout === 'grid'
  }];
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, {
    controls: toolbarControls
  })), createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('RSS settings')
  }, createElement(RangeControl, {
    label: __('Number of items'),
    value: itemsToShow,
    onChange: function onChange(value) {
      return setAttributes({
        itemsToShow: value
      });
    },
    min: DEFAULT_MIN_ITEMS,
    max: DEFAULT_MAX_ITEMS,
    required: true
  }), createElement(ToggleControl, {
    label: __('Display author'),
    checked: displayAuthor,
    onChange: toggleAttribute('displayAuthor')
  }), createElement(ToggleControl, {
    label: __('Display date'),
    checked: displayDate,
    onChange: toggleAttribute('displayDate')
  }), createElement(ToggleControl, {
    label: __('Display excerpt'),
    checked: displayExcerpt,
    onChange: toggleAttribute('displayExcerpt')
  }), displayExcerpt && createElement(RangeControl, {
    label: __('Max number of words in excerpt'),
    value: excerptLength,
    onChange: function onChange(value) {
      return setAttributes({
        excerptLength: value
      });
    },
    min: 10,
    max: 100,
    required: true
  }), blockLayout === 'grid' && createElement(RangeControl, {
    label: __('Columns'),
    value: columns,
    onChange: function onChange(value) {
      return setAttributes({
        columns: value
      });
    },
    min: 2,
    max: 6,
    required: true
  }))), createElement(Disabled, null, createElement(ServerSideRender, {
    block: "core/rss",
    attributes: attributes
  })));
}
//# sourceMappingURL=edit.js.map