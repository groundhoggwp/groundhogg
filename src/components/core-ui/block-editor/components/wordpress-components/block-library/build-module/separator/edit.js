import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { HorizontalRule } from '@wordpress/components';
import { withColors } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import SeparatorSettings from './separator-settings';

function SeparatorEdit(_ref) {
  var color = _ref.color,
      setColor = _ref.setColor,
      className = _ref.className;
  return createElement(Fragment, null, createElement(HorizontalRule, {
    className: classnames(className, _defineProperty({
      'has-background': color.color
    }, color.class, color.class)),
    style: {
      backgroundColor: color.color,
      color: color.color
    }
  }), createElement(SeparatorSettings, {
    color: color,
    setColor: setColor
  }));
}

export default withColors('color', {
  textColor: 'color'
})(SeparatorEdit);
//# sourceMappingURL=edit.js.map