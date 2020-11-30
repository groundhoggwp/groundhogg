import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { isFunction } from 'lodash';
/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

import { Icon, SelectControl } from '../';
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
/**
 * Internal dependencies
 */

import sizesTable, { findSizeBySlug } from './sizes';
export function DimensionControl(props) {
  var label = props.label,
      value = props.value,
      _props$sizes = props.sizes,
      sizes = _props$sizes === void 0 ? sizesTable : _props$sizes,
      icon = props.icon,
      onChange = props.onChange,
      _props$className = props.className,
      className = _props$className === void 0 ? '' : _props$className;

  var onChangeSpacingSize = function onChangeSpacingSize(val) {
    var theSize = findSizeBySlug(sizes, val);

    if (!theSize || value === theSize.slug) {
      onChange(undefined);
    } else if (isFunction(onChange)) {
      onChange(theSize.slug);
    }
  };

  var formatSizesAsOptions = function formatSizesAsOptions(theSizes) {
    var options = theSizes.map(function (_ref) {
      var name = _ref.name,
          slug = _ref.slug;
      return {
        label: name,
        value: slug
      };
    });
    return [{
      label: __('Default'),
      value: ''
    }].concat(options);
  };

  var selectLabel = createElement(Fragment, null, icon && createElement(Icon, {
    icon: icon
  }), label);
  return createElement(SelectControl, {
    className: classnames(className, 'block-editor-dimension-control'),
    label: selectLabel,
    hideLabelFromVision: false,
    value: value,
    onChange: onChangeSpacingSize,
    options: formatSizesAsOptions(sizes)
  });
}
export default DimensionControl;
//# sourceMappingURL=index.js.map