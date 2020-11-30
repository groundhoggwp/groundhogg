"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _data = require("@wordpress/data");

var _element = require("@wordpress/element");

var _withRegistryProvider = _interopRequireDefault(require("./with-registry-provider"));

var _useBlockSync = _interopRequireDefault(require("./use-block-sync"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/** @typedef {import('@wordpress/data').WPDataRegistry} WPDataRegistry */
function BlockEditorProvider(props) {
  var children = props.children,
      settings = props.settings;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      updateSettings = _useDispatch.updateSettings;

  (0, _element.useEffect)(function () {
    updateSettings(settings);
  }, [settings]); // Syncs the entity provider with changes in the block-editor store.

  (0, _useBlockSync.default)(props);
  return children;
}

var _default = (0, _withRegistryProvider.default)(BlockEditorProvider);

exports.default = _default;
//# sourceMappingURL=index.native.js.map