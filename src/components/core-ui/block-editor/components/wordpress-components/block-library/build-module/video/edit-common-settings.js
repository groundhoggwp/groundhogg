import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';

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
    return checked ? __('Note: Autoplaying videos may cause usability issues for some visitors.') : null;
  };

  var toggleAttribute = function toggleAttribute(attribute) {
    return function (newValue) {
      setAttributes(_defineProperty({}, attribute, newValue));
    };
  };

  return createElement(Fragment, null, createElement(ToggleControl, {
    label: __('Autoplay'),
    onChange: toggleAttribute('autoplay'),
    checked: autoplay,
    help: getAutoplayHelp
  }), createElement(ToggleControl, {
    label: __('Loop'),
    onChange: toggleAttribute('loop'),
    checked: loop
  }), createElement(ToggleControl, {
    label: __('Muted'),
    onChange: toggleAttribute('muted'),
    checked: muted
  }), createElement(ToggleControl, {
    label: __('Playback controls'),
    onChange: toggleAttribute('controls'),
    checked: controls
  }), createElement(ToggleControl, {
    label: __('Play inline'),
    onChange: toggleAttribute('playsInline'),
    checked: playsInline
  }), createElement(SelectControl, {
    label: __('Preload'),
    value: preload,
    onChange: function onChange(value) {
      return setAttributes({
        preload: value
      });
    },
    options: [{
      value: 'auto',
      label: __('Auto')
    }, {
      value: 'metadata',
      label: __('Metadata')
    }, {
      value: 'none',
      label: __('None')
    }]
  }));
};

export default VideoSettings;
//# sourceMappingURL=edit-common-settings.js.map