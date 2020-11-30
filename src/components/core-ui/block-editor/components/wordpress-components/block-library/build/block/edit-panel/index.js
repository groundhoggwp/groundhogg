"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = ReusableBlockEditPanel;

var _element = require("@wordpress/element");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _keycodes = require("@wordpress/keycodes");

/**
 * WordPress dependencies
 */

/** @typedef {import('@wordpress/element').WPComponent} WPComponent */

/**
 * ReusableBlockEditPanel props.
 *
 * @typedef WPReusableBlockEditPanelProps
 *
 * @property {boolean}                 isEditDisabled Is editing the reusable
 *                                                    block disabled.
 * @property {boolean}                 isEditing      Is the reusable block
 *                                                    being edited.
 * @property {boolean}                 isSaving       Is the reusable block
 *                                                    being saved.
 * @property {()=>void}                onCancel       Callback to run when
 *                                                    editing is canceled.
 * @property {(newTitle:string)=>void} onChangeTitle  Callback to run when the
 *                                                    title input value is
 *                                                    changed.
 * @property {()=>void}                onEdit         Callback to run when
 *                                                    editing begins.
 * @property {()=>void}                onSave         Callback to run when
 *                                                    saving.
 * @property {string}                  title          Title of the reusable
 *                                                    block.
 */

/**
 * Panel for enabling the editing and saving of a reusable block.
 *
 * @param {WPReusableBlockEditPanelProps} props Component props.
 *
 * @return {WPComponent} The panel.
 */
function ReusableBlockEditPanel(_ref) {
  var isEditDisabled = _ref.isEditDisabled,
      isEditing = _ref.isEditing,
      isSaving = _ref.isSaving,
      onCancel = _ref.onCancel,
      onChangeTitle = _ref.onChangeTitle,
      onEdit = _ref.onEdit,
      onSave = _ref.onSave,
      title = _ref.title;
  var instanceId = (0, _compose.useInstanceId)(ReusableBlockEditPanel);
  var titleField = (0, _element.useRef)();
  var editButton = (0, _element.useRef)();
  var wasEditing = (0, _compose.usePrevious)(isEditing);
  var wasSaving = (0, _compose.usePrevious)(isSaving); // Select the title input when the form opens.

  (0, _element.useEffect)(function () {
    if (!wasEditing && isEditing) {
      titleField.current.select();
    }
  }, [isEditing]); // Move focus back to the Edit button after pressing the Escape key or Save.

  (0, _element.useEffect)(function () {
    if ((wasEditing || wasSaving) && !isEditing && !isSaving) {
      editButton.current.focus();
    }
  }, [isEditing, isSaving]);

  function handleFormSubmit(event) {
    event.preventDefault();
    onSave();
  }

  function handleTitleChange(event) {
    onChangeTitle(event.target.value);
  }

  function handleTitleKeyDown(event) {
    if (event.keyCode === _keycodes.ESCAPE) {
      event.stopPropagation();
      onCancel();
    }
  }

  return (0, _element.createElement)(_element.Fragment, null, !isEditing && !isSaving && (0, _element.createElement)("div", {
    className: "reusable-block-edit-panel"
  }, (0, _element.createElement)("b", {
    className: "reusable-block-edit-panel__info"
  }, title), (0, _element.createElement)(_components.Button, {
    ref: editButton,
    isSecondary: true,
    className: "reusable-block-edit-panel__button",
    disabled: isEditDisabled,
    onClick: onEdit
  }, (0, _i18n.__)('Edit'))), (isEditing || isSaving) && (0, _element.createElement)("form", {
    className: "reusable-block-edit-panel",
    onSubmit: handleFormSubmit
  }, (0, _element.createElement)("label", {
    htmlFor: "reusable-block-edit-panel__title-".concat(instanceId),
    className: "reusable-block-edit-panel__label"
  }, (0, _i18n.__)('Name:')), (0, _element.createElement)("input", {
    ref: titleField,
    type: "text",
    disabled: isSaving,
    className: "reusable-block-edit-panel__title",
    value: title,
    onChange: handleTitleChange,
    onKeyDown: handleTitleKeyDown,
    id: "reusable-block-edit-panel__title-".concat(instanceId)
  }), (0, _element.createElement)(_components.Button, {
    type: "submit",
    isSecondary: true,
    isBusy: isSaving,
    disabled: !title || isSaving,
    className: "reusable-block-edit-panel__button"
  }, (0, _i18n.__)('Save'))));
}
//# sourceMappingURL=index.js.map