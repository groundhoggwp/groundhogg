import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { VisuallyHidden, Button } from '@wordpress/components';
import { Icon, search, closeSmall } from '@wordpress/icons';
import { useRef } from '@wordpress/element';

function InserterSearchForm(_ref) {
  var className = _ref.className,
      _onChange = _ref.onChange,
      value = _ref.value,
      placeholder = _ref.placeholder;
  var instanceId = useInstanceId(InserterSearchForm);
  var searchInput = useRef(); // Disable reason (no-autofocus): The inserter menu is a modal display, not one which
  // is always visible, and one which already incurs this behavior of autoFocus via
  // Popover's focusOnMount.

  /* eslint-disable jsx-a11y/no-autofocus */

  return createElement("div", {
    className: classnames('block-editor-inserter__search', className)
  }, createElement(VisuallyHidden, {
    as: "label",
    htmlFor: "block-editor-inserter__search-".concat(instanceId)
  }, placeholder), createElement("input", {
    ref: searchInput,
    className: "block-editor-inserter__search-input",
    id: "block-editor-inserter__search-".concat(instanceId),
    type: "search",
    placeholder: placeholder,
    autoFocus: true,
    onChange: function onChange(event) {
      return _onChange(event.target.value);
    },
    autoComplete: "off",
    value: value || ''
  }), createElement("div", {
    className: "block-editor-inserter__search-icon"
  }, !!value && createElement(Button, {
    icon: closeSmall,
    label: __('Reset search'),
    onClick: function onClick() {
      _onChange('');

      searchInput.current.focus();
    }
  }), !value && createElement(Icon, {
    icon: search
  })));
  /* eslint-enable jsx-a11y/no-autofocus */
}

export default InserterSearchForm;
//# sourceMappingURL=search-form.js.map