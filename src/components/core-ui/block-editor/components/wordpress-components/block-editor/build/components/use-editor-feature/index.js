"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useEditorFeature;

var _lodash = require("lodash");

var _data = require("@wordpress/data");

var _blockEdit = require("../block-edit");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var deprecatedFlags = {
  'color.palette': function colorPalette(settings) {
    return settings.colors === undefined ? undefined : settings.colors;
  },
  'color.gradients': function colorGradients(settings) {
    return settings.gradients === undefined ? undefined : settings.gradients;
  },
  'color.custom': function colorCustom(settings) {
    return settings.disableCustomColors === undefined ? undefined : !settings.disableCustomColors;
  },
  'color.customGradient': function colorCustomGradient(settings) {
    return settings.disableCustomGradients === undefined ? undefined : !settings.disableCustomGradients;
  },
  'typography.fontSizes': function typographyFontSizes(settings) {
    return settings.fontSizes === undefined ? undefined : settings.fontSizes;
  },
  'typography.customFontSize': function typographyCustomFontSize(settings) {
    return settings.disableCustomFontSizes === undefined ? undefined : !settings.disableCustomFontSizes;
  },
  'typography.customLineHeight': function typographyCustomLineHeight(settings) {
    return settings.enableCustomLineHeight;
  },
  'spacing.units': function spacingUnits(settings) {
    if (settings.enableCustomUnits === undefined) {
      return;
    }

    if (settings.enableCustomUnits === true) {
      return ['px', 'em', 'rem', 'vh', 'vw'];
    }

    return settings.enableCustomUnits;
  }
};
/**
 * Hook that retrieves the setting for the given editor feature.
 * It works with nested objects using by finding the value at path.
 *
 * @param {string} featurePath  The path to the feature.
 *
 * @return {any} Returns the value defined for the setting.
 *
 * @example
 * ```js
 * const isEnabled = useEditorFeature( 'typography.dropCap' );
 * ```
 */

function useEditorFeature(featurePath) {
  var _useBlockEditContext = (0, _blockEdit.useBlockEditContext)(),
      blockName = _useBlockEditContext.name;

  var setting = (0, _data.useSelect)(function (select) {
    var _get;

    // 1 - Use deprecated settings, if available.
    var settings = select('core/block-editor').getSettings();
    var deprecatedSettingsValue = deprecatedFlags[featurePath] ? deprecatedFlags[featurePath](settings) : undefined;

    if (deprecatedSettingsValue !== undefined) {
      return deprecatedSettingsValue;
    } // 2 - Use __experimental features otherwise.
    // We cascade to the global value if the block one is not available.
    //
    // TODO: make it work for blocks that define multiple selectors
    // such as core/heading or core/post-title.


    var globalPath = "__experimentalFeatures.global.".concat(featurePath);
    var blockPath = "__experimentalFeatures.".concat(blockName, ".").concat(featurePath);
    return (_get = (0, _lodash.get)(settings, blockPath)) !== null && _get !== void 0 ? _get : (0, _lodash.get)(settings, globalPath);
  }, [blockName, featurePath]);
  return setting;
}
//# sourceMappingURL=index.js.map