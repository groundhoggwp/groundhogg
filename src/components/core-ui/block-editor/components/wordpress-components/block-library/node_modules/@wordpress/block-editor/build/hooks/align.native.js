"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "AlignmentHookSettingsProvider", {
  enumerable: true,
  get: function get() {
    return _align.AlignmentHookSettingsProvider;
  }
});

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _hooks = require("@wordpress/hooks");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _align = require("./align.js");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var ALIGNMENTS = ['left', 'center', 'right'];
// Used to filter out blocks that don't support wide/full alignment on mobile
(0, _hooks.addFilter)('blocks.registerBlockType', 'core/react-native-editor/align', function (settings, name) {
  if (!_components.WIDE_ALIGNMENTS.supportedBlocks.includes(name) && (0, _blocks.hasBlockSupport)(settings, 'align')) {
    var blockAlign = settings.supports.align;
    settings.supports = _objectSpread(_objectSpread({}, settings.supports), {}, {
      align: Array.isArray(blockAlign) ? _lodash.without.apply(void 0, [blockAlign].concat((0, _toConsumableArray2.default)(Object.values(_components.WIDE_ALIGNMENTS.alignments)))) : blockAlign,
      alignWide: false
    });
    settings.attributes = _objectSpread(_objectSpread({}, settings.attributes), {}, {
      align: {
        type: 'string',
        // Allow for '' since it is used by updateAlignment function
        // in withToolbarControls for special cases with defined default values.
        enum: [].concat(ALIGNMENTS, [''])
      }
    });
  }

  return settings;
});
//# sourceMappingURL=align.native.js.map