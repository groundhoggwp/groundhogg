"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

/**
 * WordPress dependencies
 */
var VideoSettings = function VideoSettings(_ref) {
  var setAttributes = _ref.setAttributes,
      attributes = _ref.attributes;
  var autoplay = attributes.autoplay,
      controls = attributes.controls,
      loop = attributes.loop,
      muted = attributes.muted,
      playsInline = attributes.playsInline,
      preload = attributes.preload;

  var getAutoplayHelp = function getAutoplayHelp(checked) {
    return checked ? (0, _i18n.__)('Note: Autoplaying videos may cause usability issues for some visitors.') : null;
  };

  var toggleAttribute = function toggleAttribute(attribute) {
    return function (newValue) {
      setAttributes((0, _defineProperty2.default)({}, attribute, newValue));
    };
  };

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Autoplay'),
    onChange: toggleAttribute('autoplay'),
    checked: autoplay,
    help: getAutoplayHelp
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Loop'),
    onChange: toggleAttribute('loop'),
    checked: loop
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Muted'),
    onChange: toggleAttribute('muted'),
    checked: muted
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Playback controls'),
    onChange: toggleAttribute('controls'),
    checked: controls
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Play inline'),
    onChange: toggleAttribute('playsInline'),
    checked: playsInline
  }), (0, _element.createElement)(_components.SelectControl, {
    label: (0, _i18n.__)('Preload'),
    value: preload,
    onChange: function onChange(value) {
      return setAttributes({
        preload: value
      });
    },
    options: [{
      value: 'auto',
      label: (0, _i18n.__)('Auto')
    }, {
      value: 'metadata',
      label: (0, _i18n.__)('Metadata')
    }, {
      value: 'none',
      label: (0, _i18n.__)('None')
    }]
  }));
};

var _default = VideoSettings;
exports.default = _default;
//# sourceMappingURL=edit-common-settings.js.map