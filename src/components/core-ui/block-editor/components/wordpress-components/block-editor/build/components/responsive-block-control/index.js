"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _label = _interopRequireDefault(require("./label"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function ResponsiveBlockControl(props) {
  var title = props.title,
      property = props.property,
      toggleLabel = props.toggleLabel,
      onIsResponsiveChange = props.onIsResponsiveChange,
      renderDefaultControl = props.renderDefaultControl,
      renderResponsiveControls = props.renderResponsiveControls,
      _props$isResponsive = props.isResponsive,
      isResponsive = _props$isResponsive === void 0 ? false : _props$isResponsive,
      _props$defaultLabel = props.defaultLabel,
      defaultLabel = _props$defaultLabel === void 0 ? {
    id: 'all',

    /* translators: 'Label. Used to signify a layout property (eg: margin, padding) will apply uniformly to all screensizes.' */
    label: (0, _i18n.__)('All')
  } : _props$defaultLabel,
      _props$viewports = props.viewports,
      viewports = _props$viewports === void 0 ? [{
    id: 'small',
    label: (0, _i18n.__)('Small screens')
  }, {
    id: 'medium',
    label: (0, _i18n.__)('Medium screens')
  }, {
    id: 'large',
    label: (0, _i18n.__)('Large screens')
  }] : _props$viewports;

  if (!title || !property || !renderDefaultControl) {
    return null;
  }

  var toggleControlLabel = toggleLabel || (0, _i18n.sprintf)(
  /* translators: 'Toggle control label. Should the property be the same across all screen sizes or unique per screen size.'. %s property value for the control (eg: margin, padding...etc) */
  (0, _i18n.__)('Use the same %s on all screensizes.'), property);
  /* translators: 'Help text for the responsive mode toggle control.' */

  var toggleHelpText = (0, _i18n.__)('Toggle between using the same value for all screen sizes or using a unique value per screen size.');
  var defaultControl = renderDefaultControl((0, _element.createElement)(_label.default, {
    property: property,
    viewport: defaultLabel
  }), defaultLabel);

  var defaultResponsiveControls = function defaultResponsiveControls() {
    return viewports.map(function (viewport) {
      return (0, _element.createElement)(_element.Fragment, {
        key: viewport.id
      }, renderDefaultControl((0, _element.createElement)(_label.default, {
        property: property,
        viewport: viewport
      }), viewport));
    });
  };

  return (0, _element.createElement)("fieldset", {
    className: "block-editor-responsive-block-control"
  }, (0, _element.createElement)("legend", {
    className: "block-editor-responsive-block-control__title"
  }, title), (0, _element.createElement)("div", {
    className: "block-editor-responsive-block-control__inner"
  }, (0, _element.createElement)(_components.ToggleControl, {
    className: "block-editor-responsive-block-control__toggle",
    label: toggleControlLabel,
    checked: !isResponsive,
    onChange: onIsResponsiveChange,
    help: toggleHelpText
  }), (0, _element.createElement)("div", {
    className: (0, _classnames.default)('block-editor-responsive-block-control__group', {
      'is-responsive': isResponsive
    })
  }, !isResponsive && defaultControl, isResponsive && (renderResponsiveControls ? renderResponsiveControls(viewports) : defaultResponsiveControls()))));
}

var _default = ResponsiveBlockControl;
exports.default = _default;
//# sourceMappingURL=index.js.map