import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View, Animated, Easing, TouchableWithoutFeedback } from 'react-native';
/**
 * WordPress dependencies
 */

import { BlockControls } from '@wordpress/block-editor';
import { useEffect, useState, useRef } from '@wordpress/element';
import { ToolbarGroup, ToolbarButton, LinkSettings } from '@wordpress/components';
import { compose, usePreferredColorSchemeStyle } from '@wordpress/compose';
import { __, sprintf } from '@wordpress/i18n';
import { link, Icon } from '@wordpress/icons';
import { withSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import { getIconBySite, getNameBySite } from './social-list';
import styles from './editor.scss';
var ANIMATION_DELAY = 300;
var ANIMATION_DURATION = 400;
var linkSettingsOptions = {
  url: {
    label: __('URL'),
    placeholder: __('Add URL'),
    autoFocus: true
  },
  linkLabel: {
    label: __('Link label'),
    placeholder: __('None')
  },
  footer: {
    label: __('Briefly describe the link to help screen reader user')
  }
};

var SocialLinkEdit = function SocialLinkEdit(_ref) {
  var attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      isSelected = _ref.isSelected,
      onFocus = _ref.onFocus,
      name = _ref.name;
  var url = attributes.url,
      _attributes$service = attributes.service,
      service = _attributes$service === void 0 ? name : _attributes$service;

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isLinkSheetVisible = _useState2[0],
      setIsLinkSheetVisible = _useState2[1];

  var _useState3 = useState(!!url),
      _useState4 = _slicedToArray(_useState3, 2),
      hasUrl = _useState4[0],
      setHasUrl = _useState4[1];

  var activeIcon = styles["wp-social-link-".concat(service)] || styles["wp-social-link"];
  var inactiveIcon = usePreferredColorSchemeStyle(styles.inactiveIcon, styles.inactiveIconDark);
  var animatedValue = useRef(new Animated.Value(0)).current;
  var IconComponent = getIconBySite(service)();
  var socialLinkName = getNameBySite(service); // When new social icon is added link sheet is opened automatically

  useEffect(function () {
    if (isSelected && !url) {
      setIsLinkSheetVisible(true);
    }
  }, []);
  useEffect(function () {
    if (!url) {
      setHasUrl(false);
      animatedValue.setValue(0);
    } else if (url) {
      animateColors();
    }
  }, [url]);
  var interpolationColors = {
    backgroundColor: animatedValue.interpolate({
      inputRange: [0, 1],
      outputRange: [inactiveIcon.backgroundColor, activeIcon.backgroundColor]
    }),
    color: animatedValue.interpolate({
      inputRange: [0, 1],
      outputRange: [inactiveIcon.color, activeIcon.color]
    }),
    stroke: ''
  };

  var _ref2 = hasUrl ? activeIcon : interpolationColors,
      backgroundColor = _ref2.backgroundColor,
      color = _ref2.color,
      stroke = _ref2.stroke;

  function animateColors() {
    Animated.sequence([Animated.delay(ANIMATION_DELAY), Animated.timing(animatedValue, {
      toValue: 1,
      duration: ANIMATION_DURATION,
      easing: Easing.circle
    })]).start(function () {
      return setHasUrl(true);
    });
  }

  function onCloseSettingsSheet() {
    setIsLinkSheetVisible(false);
  }

  function onOpenSettingsSheet() {
    setIsLinkSheetVisible(true);
  }

  function onEmptyURL() {
    animatedValue.setValue(0);
    setHasUrl(false);
  }

  function onIconPress() {
    if (isSelected) {
      setIsLinkSheetVisible(true);
    } else {
      onFocus();
    }
  }

  var accessibilityHint = url ? sprintf( // translators: %s: social link name e.g: "Instagram".
  __('%s has URL set'), socialLinkName) : sprintf( // translators: %s: social link name e.g: "Instagram".
  __('%s has no URL set'), socialLinkName);
  return createElement(View, null, isSelected && createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarButton, {
    title: sprintf( // translators: %s: social link name e.g: "Instagram".
    __('Add link to %s'), socialLinkName),
    icon: link,
    onClick: onOpenSettingsSheet,
    isActive: url
  }))), createElement(LinkSettings, {
    isVisible: isLinkSheetVisible,
    attributes: attributes,
    onEmptyURL: onEmptyURL,
    onClose: onCloseSettingsSheet,
    setAttributes: setAttributes,
    options: linkSettingsOptions,
    withBottomSheet: true
  }), createElement(TouchableWithoutFeedback, {
    onPress: onIconPress,
    accessibilityRole: 'button',
    accessibilityLabel: sprintf( // translators: %s: social link name e.g: "Instagram".
    __('%s social icon'), socialLinkName),
    accessibilityHint: accessibilityHint
  }, createElement(Animated.View, {
    style: [styles.iconContainer, {
      backgroundColor: backgroundColor
    }]
  }, createElement(Icon, {
    animated: true,
    icon: IconComponent,
    style: {
      stroke: stroke,
      color: color
    }
  }))));
};

export default compose([withSelect(function (select, _ref3) {
  var clientId = _ref3.clientId;

  var _select = select('core/block-editor'),
      getBlock = _select.getBlock;

  var block = getBlock(clientId);
  var name = block === null || block === void 0 ? void 0 : block.name.substring(17);
  return {
    name: name
  };
})])(SocialLinkEdit);
//# sourceMappingURL=edit.native.js.map