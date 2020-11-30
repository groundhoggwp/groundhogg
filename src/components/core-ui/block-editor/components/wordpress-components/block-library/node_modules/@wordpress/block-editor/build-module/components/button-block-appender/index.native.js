import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */

import { withPreferredColorScheme } from '@wordpress/compose';
import { Button } from '@wordpress/components';
import { Icon, plusCircleFilled } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import Inserter from '../inserter';
import styles from './styles.scss';

function ButtonBlockAppender(_ref) {
  var rootClientId = _ref.rootClientId,
      getStylesFromColorScheme = _ref.getStylesFromColorScheme,
      showSeparator = _ref.showSeparator,
      _ref$isFloating = _ref.isFloating,
      isFloating = _ref$isFloating === void 0 ? false : _ref$isFloating,
      onAddBlock = _ref.onAddBlock;

  var appenderStyle = _objectSpread(_objectSpread({}, styles.appender), getStylesFromColorScheme(styles.appenderLight, styles.appenderDark));

  var addBlockButtonStyle = getStylesFromColorScheme(styles.addBlockButton, isFloating ? styles.floatingAddBlockButtonDark : styles.addBlockButtonDark);
  return createElement(Fragment, null, createElement(Inserter, {
    rootClientId: rootClientId,
    renderToggle: function renderToggle(_ref2) {
      var onToggle = _ref2.onToggle,
          disabled = _ref2.disabled,
          isOpen = _ref2.isOpen;
      return createElement(Button, {
        onClick: onAddBlock || onToggle,
        "aria-expanded": isOpen,
        disabled: disabled,
        fixedRatio: false
      }, createElement(View, {
        style: [appenderStyle, isFloating && styles.floatingAppender]
      }, createElement(Icon, {
        icon: plusCircleFilled,
        style: addBlockButtonStyle,
        color: addBlockButtonStyle.color,
        size: addBlockButtonStyle.size
      })));
    },
    isAppender: true,
    showSeparator: showSeparator
  }));
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/button-block-appender/README.md
 */


export default withPreferredColorScheme(ButtonBlockAppender);
//# sourceMappingURL=index.native.js.map