import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { get } from 'lodash';
/**
 * WordPress dependencies
 */

import { useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
export default function DefaultStylePicker(_ref) {
  var blockName = _ref.blockName;

  var _useSelect = useSelect(function (select) {
    var settings = select('core/block-editor').getSettings();
    var preferredStyleVariations = settings.__experimentalPreferredStyleVariations;
    return {
      preferredStyle: get(preferredStyleVariations, ['value', blockName]),
      onUpdatePreferredStyleVariations: get(preferredStyleVariations, ['onChange'], null),
      styles: select('core/blocks').getBlockStyles(blockName)
    };
  }, [blockName]),
      preferredStyle = _useSelect.preferredStyle,
      onUpdatePreferredStyleVariations = _useSelect.onUpdatePreferredStyleVariations,
      styles = _useSelect.styles;

  var selectOptions = useMemo(function () {
    return [{
      label: __('Not set'),
      value: ''
    }].concat(_toConsumableArray(styles.map(function (_ref2) {
      var label = _ref2.label,
          name = _ref2.name;
      return {
        label: label,
        value: name
      };
    })));
  }, [styles]);
  var selectOnChange = useCallback(function (blockStyle) {
    onUpdatePreferredStyleVariations(blockName, blockStyle);
  }, [blockName, onUpdatePreferredStyleVariations]);
  return onUpdatePreferredStyleVariations && createElement(SelectControl, {
    options: selectOptions,
    value: preferredStyle || '',
    label: __('Default Style'),
    onChange: selectOnChange
  });
}
//# sourceMappingURL=index.js.map