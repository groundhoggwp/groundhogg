import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useInstanceId, usePrevious } from '@wordpress/compose';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ESCAPE } from '@wordpress/keycodes';
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

export default function ReusableBlockEditPanel(_ref) {
  var isEditDisabled = _ref.isEditDisabled,
      isEditing = _ref.isEditing,
      isSaving = _ref.isSaving,
      onCancel = _ref.onCancel,
      onChangeTitle = _ref.onChangeTitle,
      onEdit = _ref.onEdit,
      onSave = _ref.onSave,
      title = _ref.title;
  var instanceId = useInstanceId(ReusableBlockEditPanel);
  var titleField = useRef();
  var editButton = useRef();
  var wasEditing = usePrevious(isEditing);
  var wasSaving = usePrevious(isSaving); // Select the title input when the form opens.

  useEffect(function () {
    if (!wasEditing && isEditing) {
      titleField.current.select();
    }
  }, [isEditing]); // Move focus back to the Edit button after pressing the Escape key or Save.

  useEffect(function () {
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
    if (event.keyCode === ESCAPE) {
      event.stopPropagation();
      onCancel();
    }
  }

  return createElement(Fragment, null, !isEditing && !isSaving && createElement("div", {
    className: "reusable-block-edit-panel"
  }, createElement("b", {
    className: "reusable-block-edit-panel__info"
  }, title), createElement(Button, {
    ref: editButton,
    isSecondary: true,
    className: "reusable-block-edit-panel__button",
    disabled: isEditDisabled,
    onClick: onEdit
  }, __('Edit'))), (isEditing || isSaving) && createElement("form", {
    className: "reusable-block-edit-panel",
    onSubmit: handleFormSubmit
  }, createElement("label", {
    htmlFor: "reusable-block-edit-panel__title-".concat(instanceId),
    className: "reusable-block-edit-panel__label"
  }, __('Name:')), createElement("input", {
    ref: titleField,
    type: "text",
    disabled: isSaving,
    className: "reusable-block-edit-panel__title",
    value: title,
    onChange: handleTitleChange,
    onKeyDown: handleTitleKeyDown,
    id: "reusable-block-edit-panel__title-".concat(instanceId)
  }), createElement(Button, {
    type: "submit",
    isSecondary: true,
    isBusy: isSaving,
    disabled: !title || isSaving,
    className: "reusable-block-edit-panel__button"
  }, __('Save'))));
}
//# sourceMappingURL=index.js.map