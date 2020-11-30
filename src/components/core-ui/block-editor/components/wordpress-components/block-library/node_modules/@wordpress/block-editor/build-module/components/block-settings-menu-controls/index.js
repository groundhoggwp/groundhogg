import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { compact, isEmpty, map } from 'lodash';
/**
 * WordPress dependencies
 */

import { createSlotFill, MenuGroup } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

var _createSlotFill = createSlotFill('BlockSettingsMenuControls'),
    BlockSettingsMenuControls = _createSlotFill.Fill,
    Slot = _createSlotFill.Slot;

var BlockSettingsMenuControlsSlot = function BlockSettingsMenuControlsSlot(_ref) {
  var fillProps = _ref.fillProps,
      _ref$clientIds = _ref.clientIds,
      clientIds = _ref$clientIds === void 0 ? null : _ref$clientIds;
  var selectedBlocks = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getBlocksByClientId = _select.getBlocksByClientId,
        getSelectedBlockClientIds = _select.getSelectedBlockClientIds;

    var ids = clientIds !== null ? clientIds : getSelectedBlockClientIds();
    return map(compact(getBlocksByClientId(ids)), function (block) {
      return block.name;
    });
  }, [clientIds]);
  return createElement(Slot, {
    fillProps: _objectSpread(_objectSpread({}, fillProps), {}, {
      selectedBlocks: selectedBlocks
    })
  }, function (fills) {
    return !isEmpty(fills) && createElement(MenuGroup, null, fills);
  });
};

BlockSettingsMenuControls.Slot = BlockSettingsMenuControlsSlot;
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/block-settings-menu-controls/README.md
 */

export default BlockSettingsMenuControls;
//# sourceMappingURL=index.js.map