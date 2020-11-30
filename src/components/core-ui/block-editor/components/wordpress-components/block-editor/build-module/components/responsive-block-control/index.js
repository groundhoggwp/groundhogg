import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
/**
 * Internal dependencies
 */

import ResponsiveBlockControlLabel from './label';

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
    label: __('All')
  } : _props$defaultLabel,
      _props$viewports = props.viewports,
      viewports = _props$viewports === void 0 ? [{
    id: 'small',
    label: __('Small screens')
  }, {
    id: 'medium',
    label: __('Medium screens')
  }, {
    id: 'large',
    label: __('Large screens')
  }] : _props$viewports;

  if (!title || !property || !renderDefaultControl) {
    return null;
  }

  var toggleControlLabel = toggleLabel || sprintf(
  /* translators: 'Toggle control label. Should the property be the same across all screen sizes or unique per screen size.'. %s property value for the control (eg: margin, padding...etc) */
  __('Use the same %s on all screensizes.'), property);
  /* translators: 'Help text for the responsive mode toggle control.' */

  var toggleHelpText = __('Toggle between using the same value for all screen sizes or using a unique value per screen size.');

  var defaultControl = renderDefaultControl(createElement(ResponsiveBlockControlLabel, {
    property: property,
    viewport: defaultLabel
  }), defaultLabel);

  var defaultResponsiveControls = function defaultResponsiveControls() {
    return viewports.map(function (viewport) {
      return createElement(Fragment, {
        key: viewport.id
      }, renderDefaultControl(createElement(ResponsiveBlockControlLabel, {
        property: property,
        viewport: viewport
      }), viewport));
    });
  };

  return createElement("fieldset", {
    className: "block-editor-responsive-block-control"
  }, createElement("legend", {
    className: "block-editor-responsive-block-control__title"
  }, title), createElement("div", {
    className: "block-editor-responsive-block-control__inner"
  }, createElement(ToggleControl, {
    className: "block-editor-responsive-block-control__toggle",
    label: toggleControlLabel,
    checked: !isResponsive,
    onChange: onIsResponsiveChange,
    help: toggleHelpText
  }), createElement("div", {
    className: classnames('block-editor-responsive-block-control__group', {
      'is-responsive': isResponsive
    })
  }, !isResponsive && defaultControl, isResponsive && (renderResponsiveControls ? renderResponsiveControls(viewports) : defaultResponsiveControls()))));
}

export default ResponsiveBlockControl;
//# sourceMappingURL=index.js.map