import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { ToolbarGroup } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import LevelIcon from './level-icon';
export default function LevelToolbar(_ref) {
  var level = _ref.level,
      onChange = _ref.onChange;
  return createElement(ToolbarGroup, {
    isCollapsed: true,
    icon: createElement(LevelIcon, {
      level: level
    }),
    controls: [0, 1, 2, 3, 4, 5, 6].map(function (currentLevel) {
      var isActive = currentLevel === level;
      return {
        icon: createElement(LevelIcon, {
          level: currentLevel,
          isPressed: isActive
        }),
        title: currentLevel === 0 ? __('Paragraph') : // translators: %s: heading level e.g: "1", "2", "3"
        sprintf(__('Heading %d'), currentLevel),
        isActive: isActive,
        onClick: function onClick() {
          return onChange(currentLevel);
        }
      };
    }),
    label: __('Change heading level')
  });
}
//# sourceMappingURL=level-toolbar.js.map