"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
var _exportNames = {
  __experimentalAlignmentHookSettingsProvider: true,
  storeConfig: true,
  SETTINGS_DEFAULTS: true
};
Object.defineProperty(exports, "__experimentalAlignmentHookSettingsProvider", {
  enumerable: true,
  get: function get() {
    return _hooks.AlignmentHookSettingsProvider;
  }
});
Object.defineProperty(exports, "storeConfig", {
  enumerable: true,
  get: function get() {
    return _store.storeConfig;
  }
});
Object.defineProperty(exports, "SETTINGS_DEFAULTS", {
  enumerable: true,
  get: function get() {
    return _defaults.SETTINGS_DEFAULTS;
  }
});

require("@wordpress/blocks");

require("@wordpress/rich-text");

require("@wordpress/viewport");

require("@wordpress/keyboard-shortcuts");

require("@wordpress/notices");

var _hooks = require("./hooks");

var _components = require("./components");

Object.keys(_components).forEach(function (key) {
  if (key === "default" || key === "__esModule") return;
  if (Object.prototype.hasOwnProperty.call(_exportNames, key)) return;
  Object.defineProperty(exports, key, {
    enumerable: true,
    get: function get() {
      return _components[key];
    }
  });
});

var _utils = require("./utils");

Object.keys(_utils).forEach(function (key) {
  if (key === "default" || key === "__esModule") return;
  if (Object.prototype.hasOwnProperty.call(_exportNames, key)) return;
  Object.defineProperty(exports, key, {
    enumerable: true,
    get: function get() {
      return _utils[key];
    }
  });
});

var _store = require("./store");

var _defaults = require("./store/defaults");
//# sourceMappingURL=index.js.map