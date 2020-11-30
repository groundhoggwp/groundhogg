import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress dependencies
 */
import { BlockEditorProvider, BlockList } from '@wordpress/block-editor';
import { ModalHeaderBar } from '@wordpress/components';
import { usePreferredColorSchemeStyle } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { subscribeAndroidModalClosed } from '@wordpress/react-native-bridge';
/**
 * External dependencies
 */

import { Modal, Platform, View, SafeAreaView } from 'react-native';
/**
 * Internal dependencies
 */

import styles from './styles.scss'; // We are replicating this here because the one in @wordpress/block-editor always
// tries to scale the preview and we would need a lot of cross platform code to handle
// sizes, when we actually want to show the preview at full width.
//
// We can make it work here first, then figure out the right way to consolidate
// both implementations

var BlockPreview = function BlockPreview(_ref) {
  var blocks = _ref.blocks;
  var currentSettings = useSelect(function (select) {
    return select('core/block-editor').getSettings();
  });

  var settings = _objectSpread(_objectSpread({}, currentSettings), {}, {
    readOnly: true
  });

  var header = createElement(View, {
    style: styles.previewHeader
  });
  return createElement(BlockEditorProvider, {
    value: blocks,
    settings: settings
  }, createElement(View, {
    style: {
      flex: 1
    }
  }, createElement(BlockList, {
    header: header
  })));
};

BlockPreview.displayName = 'BlockPreview';

var ModalPreview = function ModalPreview(props) {
  var template = props.template,
      onDismiss = props.onDismiss,
      onApply = props.onApply;
  var previewContainerStyle = usePreferredColorSchemeStyle(styles.previewContainer, styles.previewContainerDark);
  var androidModalClosedSubscription = useRef();
  useEffect(function () {
    if (Platform.OS === 'android') {
      androidModalClosedSubscription.current = subscribeAndroidModalClosed(function () {
        onDismiss();
      });
    }

    return function () {
      if (androidModalClosedSubscription && androidModalClosedSubscription.current) {
        androidModalClosedSubscription.current.remove();
      }
    };
  }, []);

  if (template === undefined) {
    return null;
  }

  var leftButton = createElement(ModalHeaderBar.CloseButton, {
    onPress: onDismiss
  });
  var rightButton = createElement(ModalHeaderBar.Button, {
    onPress: onApply,
    title: __('Apply'),
    isPrimary: true
  });
  return createElement(Modal, {
    visible: !!template,
    animationType: "slide",
    onRequestClose: onDismiss,
    supportedOrientations: ['portrait', 'landscape']
  }, createElement(SafeAreaView, {
    style: previewContainerStyle
  }, createElement(ModalHeaderBar, {
    leftButton: leftButton,
    rightButton: rightButton,
    title: template.name,
    subtitle: __('Template Preview')
  }), createElement(Preview, {
    blocks: template.blocks
  })));
};

ModalPreview.displayName = 'TemplatePreview';
export var Preview = function Preview(props) {
  var blocks = props.blocks;
  var previewContentStyle = usePreferredColorSchemeStyle(styles.previewContent, styles.previewContentDark);

  if (blocks === undefined) {
    return null;
  }

  return createElement(View, {
    style: previewContentStyle
  }, createElement(BlockPreview, {
    blocks: blocks
  }));
};
export default ModalPreview;
//# sourceMappingURL=preview.native.js.map