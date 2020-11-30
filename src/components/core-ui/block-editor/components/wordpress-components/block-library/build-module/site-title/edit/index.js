import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { RichText, AlignmentToolbar, BlockControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */

import LevelToolbar from './level-toolbar';
export default function SiteTitleEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var level = attributes.level,
      textAlign = attributes.textAlign;

  var _useEntityProp = useEntityProp('root', 'site', 'title'),
      _useEntityProp2 = _slicedToArray(_useEntityProp, 2),
      title = _useEntityProp2[0],
      setTitle = _useEntityProp2[1];

  var tagName = level === 0 ? 'p' : "h".concat(level);
  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return createElement(Fragment, null, createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  }), createElement(LevelToolbar, {
    level: level,
    onChange: function onChange(newLevel) {
      return setAttributes({
        level: newLevel
      });
    }
  })), createElement(RichText, _extends({
    tagName: tagName,
    placeholder: __('Site Title'),
    value: title,
    onChange: setTitle,
    allowedFormats: [],
    disableLineBreaks: true
  }, blockWrapperProps)));
}
//# sourceMappingURL=index.js.map