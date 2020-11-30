import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */

import Tooltip from './tooltip';
/**
 * External dependencies
 */

import { logUserEvent, userEvents, requestStarterPageTemplatesTooltipShown, setStarterPageTemplatesTooltipShown } from '@wordpress/react-native-bridge';
import { Animated, Dimensions, Keyboard } from 'react-native';
/**
 * Internal dependencies
 */

import Button from './button';
import Container from './container';
import getDefaultTemplates from './default-templates';
import ModalPreview from './preview'; // Used to hide the picker if there's no enough space in the window

var PICKER_HEIGHT_OFFSET = 150;

var __experimentalPageTemplatePicker = function __experimentalPageTemplatePicker(_ref) {
  var _ref$templates = _ref.templates,
      templates = _ref$templates === void 0 ? getDefaultTemplates() : _ref$templates,
      visible = _ref.visible;

  var _useDispatch = useDispatch('core/editor'),
      editPost = _useDispatch.editPost;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/editor'),
        getEditedPostAttribute = _select.getEditedPostAttribute;

    return {
      title: getEditedPostAttribute('title')
    };
  }),
      title = _useSelect.title;

  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      templatePreview = _useState2[0],
      setTemplatePreview = _useState2[1];

  var _useState3 = useState(visible),
      _useState4 = _slicedToArray(_useState3, 2),
      pickerVisible = _useState4[0],
      setPickerVisible = _useState4[1];

  var _useState5 = useState(false),
      _useState6 = _slicedToArray(_useState5, 2),
      tooltipVisible = _useState6[0],
      setTooltipVisible = _useState6[1];

  var contentOpacity = useRef(new Animated.Value(0)).current;
  useEffect(function () {
    if (shouldShowPicker() && visible && !pickerVisible) {
      setPickerVisible(true);
    }

    startPickerAnimation(visible);
    shouldShowTooltip();
    Keyboard.addListener('keyboardDidShow', onKeyboardDidShow);
    Keyboard.addListener('keyboardDidHide', onKeyboardDidHide);
    return function () {
      Keyboard.removeListener('keyboardDidShow', onKeyboardDidShow);
      Keyboard.removeListener('keyboardDidHide', onKeyboardDidHide);
    };
  }, [visible]);
  useEffect(function () {
    if (tooltipVisible && templatePreview) {
      setTooltipVisible(false);
    }
  }, [templatePreview]);

  var onKeyboardDidShow = function onKeyboardDidShow() {
    if (visible) {
      startPickerAnimation(shouldShowPicker());
    }
  };

  var onKeyboardDidHide = function onKeyboardDidHide() {
    if (visible) {
      setPickerVisible(true);
      startPickerAnimation(true);
    }
  };

  var shouldShowPicker = function shouldShowPicker() {
    // On smaller devices on landscape we hide the picker
    // so it doesn't overlap with the editor's content
    var windowHeight = Dimensions.get('window').height;
    return PICKER_HEIGHT_OFFSET < windowHeight / 3;
  };

  var shouldShowTooltip = function shouldShowTooltip() {
    requestStarterPageTemplatesTooltipShown(function (tooltipShown) {
      if (!tooltipShown) {
        setTooltipVisible(true);
        setStarterPageTemplatesTooltipShown(true);
      }
    });
  };

  var onApply = function onApply() {
    editPost({
      title: title || templatePreview.name,
      blocks: templatePreview.blocks
    });
    logUserEvent(userEvents.editorSessionTemplateApply, {
      template: templatePreview.key
    });
    setTemplatePreview(undefined);
  };

  var onTooltipHidden = function onTooltipHidden() {
    setTooltipVisible(false);
  };

  var startPickerAnimation = function startPickerAnimation(isVisible) {
    Animated.timing(contentOpacity, {
      toValue: isVisible ? 1 : 0,
      duration: 300,
      useNativeDriver: true
    }).start(function () {
      if (!isVisible) {
        setPickerVisible(isVisible);
      }
    });
  };

  if (!pickerVisible) {
    return null;
  }

  return createElement(Animated.View, {
    style: [{
      opacity: contentOpacity
    }]
  }, tooltipVisible && createElement(Tooltip, {
    onTooltipHidden: onTooltipHidden
  }), createElement(Container, null, templates.map(function (template) {
    return createElement(Button, {
      key: template.key,
      icon: template.icon,
      label: template.name,
      onPress: function onPress() {
        logUserEvent(userEvents.editorSessionTemplatePreview, {
          template: template.key
        });
        setTemplatePreview(template);
      }
    });
  })), createElement(ModalPreview, {
    template: templatePreview,
    onDismiss: function onDismiss() {
      return setTemplatePreview(undefined);
    },
    onApply: onApply
  }));
};

export default __experimentalPageTemplatePicker;
//# sourceMappingURL=picker.native.js.map