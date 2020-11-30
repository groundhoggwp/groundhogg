import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import BlockTitle from '../block-title';
/**
 * Block breadcrumb component, displaying the hierarchy of the current block selection as a breadcrumb.
 *
 * @return {WPElement} Block Breadcrumb.
 */

function BlockBreadcrumb() {
  var _useDispatch = useDispatch('core/block-editor'),
      selectBlock = _useDispatch.selectBlock,
      clearSelectedBlock = _useDispatch.clearSelectedBlock;

  var _useSelect = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getSelectionStart = _select.getSelectionStart,
        getSelectedBlockClientId = _select.getSelectedBlockClientId,
        getBlockParents = _select.getBlockParents;

    var selectedBlockClientId = getSelectedBlockClientId();
    return {
      parents: getBlockParents(selectedBlockClientId),
      clientId: selectedBlockClientId,
      hasSelection: !!getSelectionStart().clientId
    };
  }, []),
      clientId = _useSelect.clientId,
      parents = _useSelect.parents,
      hasSelection = _useSelect.hasSelection;
  /*
   * Disable reason: The `list` ARIA role is redundant but
   * Safari+VoiceOver won't announce the list otherwise.
   */

  /* eslint-disable jsx-a11y/no-redundant-roles */


  return createElement("ul", {
    className: "block-editor-block-breadcrumb",
    role: "list",
    "aria-label": __('Block breadcrumb')
  }, createElement("li", {
    className: !hasSelection ? 'block-editor-block-breadcrumb__current' : undefined,
    "aria-current": !hasSelection ? 'true' : undefined
  }, hasSelection && createElement(Button, {
    className: "block-editor-block-breadcrumb__button",
    isTertiary: true,
    onClick: clearSelectedBlock
  }, __('Document')), !hasSelection && __('Document')), parents.map(function (parentClientId) {
    return createElement("li", {
      key: parentClientId
    }, createElement(Button, {
      className: "block-editor-block-breadcrumb__button",
      isTertiary: true,
      onClick: function onClick() {
        return selectBlock(parentClientId);
      }
    }, createElement(BlockTitle, {
      clientId: parentClientId
    })));
  }), !!clientId && createElement("li", {
    className: "block-editor-block-breadcrumb__current",
    "aria-current": "true"
  }, createElement(BlockTitle, {
    clientId: clientId
  })))
  /* eslint-enable jsx-a11y/no-redundant-roles */
  ;
}

export default BlockBreadcrumb;
//# sourceMappingURL=index.js.map