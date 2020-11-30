import _extends from "@babel/runtime/helpers/esm/extends";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _objectWithoutProperties from "@babel/runtime/helpers/esm/objectWithoutProperties";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
import { compact, uniq } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import { external, Icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import VisuallyHidden from '../visually-hidden';
export function ExternalLink(_ref, ref) {
  var href = _ref.href,
      children = _ref.children,
      className = _ref.className,
      _ref$rel = _ref.rel,
      rel = _ref$rel === void 0 ? '' : _ref$rel,
      additionalProps = _objectWithoutProperties(_ref, ["href", "children", "className", "rel"]);

  rel = uniq(compact([].concat(_toConsumableArray(rel.split(' ')), ['external', 'noreferrer', 'noopener']))).join(' ');
  var classes = classnames('components-external-link', className);
  return createElement("a", _extends({}, additionalProps, {
    className: classes,
    href: href // eslint-disable-next-line react/jsx-no-target-blank
    ,
    target: "_blank",
    rel: rel,
    ref: ref
  }), children, createElement(VisuallyHidden, {
    as: "span"
  },
  /* translators: accessibility text */
  __('(opens in a new tab)')), createElement(Icon, {
    icon: external,
    className: "components-external-link__icon"
  }));
}
export default forwardRef(ExternalLink);
//# sourceMappingURL=index.js.map