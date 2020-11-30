"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _blockTitle = _interopRequireDefault(require("../block-title"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Block breadcrumb component, displaying the hierarchy of the current block selection as a breadcrumb.
 *
 * @return {WPElement} Block Breadcrumb.
 */
function BlockBreadcrumb() {
  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      selectBlock = _useDispatch.selectBlock,
      clearSelectedBlock = _useDispatch.clearSelectedBlock;

  var _useSelect = (0, _data.useSelect)(function (select) {
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


  return (0, _element.createElement)("ul", {
    className: "block-editor-block-breadcrumb",
    role: "list",
    "aria-label": (0, _i18n.__)('Block breadcrumb')
  }, (0, _element.createElement)("li", {
    className: !hasSelection ? 'block-editor-block-breadcrumb__current' : undefined,
    "aria-current": !hasSelection ? 'true' : undefined
  }, hasSelection && (0, _element.createElement)(_components.Button, {
    className: "block-editor-block-breadcrumb__button",
    isTertiary: true,
    onClick: clearSelectedBlock
  }, (0, _i18n.__)('Document')), !hasSelection && (0, _i18n.__)('Document')), parents.map(function (parentClientId) {
    return (0, _element.createElement)("li", {
      key: parentClientId
    }, (0, _element.createElement)(_components.Button, {
      className: "block-editor-block-breadcrumb__button",
      isTertiary: true,
      onClick: function onClick() {
        return selectBlock(parentClientId);
      }
    }, (0, _element.createElement)(_blockTitle.default, {
      clientId: parentClientId
    })));
  }), !!clientId && (0, _element.createElement)("li", {
    className: "block-editor-block-breadcrumb__current",
    "aria-current": "true"
  }, (0, _element.createElement)(_blockTitle.default, {
    clientId: clientId
  })))
  /* eslint-enable jsx-a11y/no-redundant-roles */
  ;
}

var _default = BlockBreadcrumb;
exports.default = _default;
//# sourceMappingURL=index.js.map