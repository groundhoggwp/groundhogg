"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = RSSEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _i18n = require("@wordpress/i18n");

var _serverSideRender = _interopRequireDefault(require("@wordpress/server-side-render"));

/**
 * WordPress dependencies
 */
var DEFAULT_MIN_ITEMS = 1;
var DEFAULT_MAX_ITEMS = 10;

function RSSEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;

  var _useState = (0, _element.useState)(!attributes.feedURL),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
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
      setAttributes((0, _defineProperty2.default)({}, propName, !value));
    };
  }

  function onSubmitURL(event) {
    event.preventDefault();

    if (feedURL) {
      setIsEditing(false);
    }
  }

  if (isEditing) {
    return (0, _element.createElement)(_components.Placeholder, {
      icon: _icons.rss,
      label: "RSS"
    }, (0, _element.createElement)("form", {
      onSubmit: onSubmitURL,
      className: "wp-block-rss__placeholder-form"
    }, (0, _element.createElement)(_components.TextControl, {
      placeholder: (0, _i18n.__)('Enter URL here…'),
      value: feedURL,
      onChange: function onChange(value) {
        return setAttributes({
          feedURL: value
        });
      },
      className: "wp-block-rss__placeholder-input"
    }), (0, _element.createElement)(_components.Button, {
      isPrimary: true,
      type: "submit"
    }, (0, _i18n.__)('Use URL'))));
  }

  var toolbarControls = [{
    icon: _icons.edit,
    title: (0, _i18n.__)('Edit RSS URL'),
    onClick: function onClick() {
      return setIsEditing(true);
    }
  }, {
    icon: _icons.list,
    title: (0, _i18n.__)('List view'),
    onClick: function onClick() {
      return setAttributes({
        blockLayout: 'list'
      });
    },
    isActive: blockLayout === 'list'
  }, {
    icon: _icons.grid,
    title: (0, _i18n.__)('Grid view'),
    onClick: function onClick() {
      return setAttributes({
        blockLayout: 'grid'
      });
    },
    isActive: blockLayout === 'grid'
  }];
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, {
    controls: toolbarControls
  })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('RSS settings')
  }, (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Number of items'),
    value: itemsToShow,
    onChange: function onChange(value) {
      return setAttributes({
        itemsToShow: value
      });
    },
    min: DEFAULT_MIN_ITEMS,
    max: DEFAULT_MAX_ITEMS,
    required: true
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Display author'),
    checked: displayAuthor,
    onChange: toggleAttribute('displayAuthor')
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Display date'),
    checked: displayDate,
    onChange: toggleAttribute('displayDate')
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Display excerpt'),
    checked: displayExcerpt,
    onChange: toggleAttribute('displayExcerpt')
  }), displayExcerpt && (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Max number of words in excerpt'),
    value: excerptLength,
    onChange: function onChange(value) {
      return setAttributes({
        excerptLength: value
      });
    },
    min: 10,
    max: 100,
    required: true
  }), blockLayout === 'grid' && (0, _element.createElement)(_components.RangeControl, {
    label: (0, _i18n.__)('Columns'),
    value: columns,
    onChange: function onChange(value) {
      return setAttributes({
        columns: value
      });
    },
    min: 2,
    max: 6,
    required: true
  }))), (0, _element.createElement)(_components.Disabled, null, (0, _element.createElement)(_serverSideRender.default, {
    block: "core/rss",
    attributes: attributes
  })));
}
//# sourceMappingURL=edit.js.map