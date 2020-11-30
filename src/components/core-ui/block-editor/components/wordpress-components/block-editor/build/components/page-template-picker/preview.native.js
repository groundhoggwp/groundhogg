"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.Preview = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _blockEditor = require("@wordpress/block-editor");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _reactNativeBridge = require("@wordpress/react-native-bridge");

var _reactNative = require("react-native");

var _styles = _interopRequireDefault(require("./styles.scss"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

// We are replicating this here because the one in @wordpress/block-editor always
// tries to scale the preview and we would need a lot of cross platform code to handle
// sizes, when we actually want to show the preview at full width.
//
// We can make it work here first, then figure out the right way to consolidate
// both implementations
var BlockPreview = function BlockPreview(_ref) {
  var blocks = _ref.blocks;
  var currentSettings = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').getSettings();
  });

  var settings = _objectSpread(_objectSpread({}, currentSettings), {}, {
    readOnly: true
  });

  var header = (0, _element.createElement)(_reactNative.View, {
    style: _styles.default.previewHeader
  });
  return (0, _element.createElement)(_blockEditor.BlockEditorProvider, {
    value: blocks,
    settings: settings
  }, (0, _element.createElement)(_reactNative.View, {
    style: {
      flex: 1
    }
  }, (0, _element.createElement)(_blockEditor.BlockList, {
    header: header
  })));
};

BlockPreview.displayName = 'BlockPreview';

var ModalPreview = function ModalPreview(props) {
  var template = props.template,
      onDismiss = props.onDismiss,
      onApply = props.onApply;
  var previewContainerStyle = (0, _compose.usePreferredColorSchemeStyle)(_styles.default.previewContainer, _styles.default.previewContainerDark);
  var androidModalClosedSubscription = (0, _element.useRef)();
  (0, _element.useEffect)(function () {
    if (_reactNative.Platform.OS === 'android') {
      androidModalClosedSubscription.current = (0, _reactNativeBridge.subscribeAndroidModalClosed)(function () {
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

  var leftButton = (0, _element.createElement)(_components.ModalHeaderBar.CloseButton, {
    onPress: onDismiss
  });
  var rightButton = (0, _element.createElement)(_components.ModalHeaderBar.Button, {
    onPress: onApply,
    title: (0, _i18n.__)('Apply'),
    isPrimary: true
  });
  return (0, _element.createElement)(_reactNative.Modal, {
    visible: !!template,
    animationType: "slide",
    onRequestClose: onDismiss,
    supportedOrientations: ['portrait', 'landscape']
  }, (0, _element.createElement)(_reactNative.SafeAreaView, {
    style: previewContainerStyle
  }, (0, _element.createElement)(_components.ModalHeaderBar, {
    leftButton: leftButton,
    rightButton: rightButton,
    title: template.name,
    subtitle: (0, _i18n.__)('Template Preview')
  }), (0, _element.createElement)(Preview, {
    blocks: template.blocks
  })));
};

ModalPreview.displayName = 'TemplatePreview';

var Preview = function Preview(props) {
  var blocks = props.blocks;
  var previewContentStyle = (0, _compose.usePreferredColorSchemeStyle)(_styles.default.previewContent, _styles.default.previewContentDark);

  if (blocks === undefined) {
    return null;
  }

  return (0, _element.createElement)(_reactNative.View, {
    style: previewContentStyle
  }, (0, _element.createElement)(BlockPreview, {
    blocks: blocks
  }));
};

exports.Preview = Preview;
var _default = ModalPreview;
exports.default = _default;
//# sourceMappingURL=preview.native.js.map