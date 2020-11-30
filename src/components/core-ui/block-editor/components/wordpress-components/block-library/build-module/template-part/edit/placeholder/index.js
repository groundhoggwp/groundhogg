import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";
import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import { Placeholder, Dropdown, Button } from '@wordpress/components';
import { blockDefault } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import TemplatePartSelection from '../selection';
export default function TemplatePartPlaceholder(_ref) {
  var setAttributes = _ref.setAttributes;

  var _useDispatch = useDispatch('core'),
      saveEntityRecord = _useDispatch.saveEntityRecord;

  var onCreate = useCallback( /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    var title, slug, templatePart;
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            title = 'Untitled Template Part';
            slug = cleanForSlug(title);
            _context.next = 4;
            return saveEntityRecord('postType', 'wp_template_part', {
              title: title,
              status: 'publish',
              slug: slug,
              meta: {
                theme: 'custom'
              }
            });

          case 4:
            templatePart = _context.sent;
            setAttributes({
              postId: templatePart.id,
              slug: templatePart.slug,
              theme: templatePart.meta.theme
            });

          case 6:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  })), [setAttributes]);
  return createElement(Placeholder, {
    icon: blockDefault,
    label: __('Template Part'),
    instructions: __('Create a new template part or pick an existing one from the list.')
  }, createElement(Dropdown, {
    contentClassName: "wp-block-template-part__placeholder-preview-dropdown-content",
    position: "bottom right left",
    renderToggle: function renderToggle(_ref3) {
      var isOpen = _ref3.isOpen,
          onToggle = _ref3.onToggle;
      return createElement(Fragment, null, createElement(Button, {
        isPrimary: true,
        onClick: onToggle,
        "aria-expanded": isOpen
      }, __('Choose existing')), createElement(Button, {
        isTertiary: true,
        onClick: onCreate
      }, __('New template part')));
    },
    renderContent: function renderContent(_ref4) {
      var onClose = _ref4.onClose;
      return createElement(TemplatePartSelection, {
        setAttributes: setAttributes,
        onClose: onClose
      });
    }
  }));
}
//# sourceMappingURL=index.js.map