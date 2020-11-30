import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { Component } from '@wordpress/element';
import { InspectorControls, BlockControls, RichText, PanelColorSettings, createCustomColorsHOC, BlockIcon, AlignmentToolbar } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Button, DropdownMenu, PanelBody, Placeholder, TextControl, ToggleControl, ToolbarGroup, ToolbarItem } from '@wordpress/components';
import { alignLeft, alignRight, alignCenter, blockTable as icon, tableColumnAfter, tableColumnBefore, tableColumnDelete, tableRowAfter, tableRowBefore, tableRowDelete, table } from '@wordpress/icons';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */

import { createTable, updateSelectedCell, getCellAttribute, insertRow, deleteRow, insertColumn, deleteColumn, toggleSection, isEmptyTableSection } from './state';
var BACKGROUND_COLORS = [{
  color: '#f3f4f5',
  name: 'Subtle light gray',
  slug: 'subtle-light-gray'
}, {
  color: '#e9fbe5',
  name: 'Subtle pale green',
  slug: 'subtle-pale-green'
}, {
  color: '#e7f5fe',
  name: 'Subtle pale blue',
  slug: 'subtle-pale-blue'
}, {
  color: '#fcf0ef',
  name: 'Subtle pale pink',
  slug: 'subtle-pale-pink'
}];
var ALIGNMENT_CONTROLS = [{
  icon: alignLeft,
  title: __('Align column left'),
  align: 'left'
}, {
  icon: alignCenter,
  title: __('Align column center'),
  align: 'center'
}, {
  icon: alignRight,
  title: __('Align column right'),
  align: 'right'
}];
var withCustomBackgroundColors = createCustomColorsHOC(BACKGROUND_COLORS);
export var TableEdit = /*#__PURE__*/function (_Component) {
  _inherits(TableEdit, _Component);

  var _super = _createSuper(TableEdit);

  function TableEdit() {
    var _this;

    _classCallCheck(this, TableEdit);

    _this = _super.apply(this, arguments);
    _this.onCreateTable = _this.onCreateTable.bind(_assertThisInitialized(_this));
    _this.onChangeFixedLayout = _this.onChangeFixedLayout.bind(_assertThisInitialized(_this));
    _this.onChange = _this.onChange.bind(_assertThisInitialized(_this));
    _this.onChangeInitialColumnCount = _this.onChangeInitialColumnCount.bind(_assertThisInitialized(_this));
    _this.onChangeInitialRowCount = _this.onChangeInitialRowCount.bind(_assertThisInitialized(_this));
    _this.renderSection = _this.renderSection.bind(_assertThisInitialized(_this));
    _this.getTableControls = _this.getTableControls.bind(_assertThisInitialized(_this));
    _this.onInsertRow = _this.onInsertRow.bind(_assertThisInitialized(_this));
    _this.onInsertRowBefore = _this.onInsertRowBefore.bind(_assertThisInitialized(_this));
    _this.onInsertRowAfter = _this.onInsertRowAfter.bind(_assertThisInitialized(_this));
    _this.onDeleteRow = _this.onDeleteRow.bind(_assertThisInitialized(_this));
    _this.onInsertColumn = _this.onInsertColumn.bind(_assertThisInitialized(_this));
    _this.onInsertColumnBefore = _this.onInsertColumnBefore.bind(_assertThisInitialized(_this));
    _this.onInsertColumnAfter = _this.onInsertColumnAfter.bind(_assertThisInitialized(_this));
    _this.onDeleteColumn = _this.onDeleteColumn.bind(_assertThisInitialized(_this));
    _this.onToggleHeaderSection = _this.onToggleHeaderSection.bind(_assertThisInitialized(_this));
    _this.onToggleFooterSection = _this.onToggleFooterSection.bind(_assertThisInitialized(_this));
    _this.onChangeColumnAlignment = _this.onChangeColumnAlignment.bind(_assertThisInitialized(_this));
    _this.getCellAlignment = _this.getCellAlignment.bind(_assertThisInitialized(_this));
    _this.state = {
      initialRowCount: 2,
      initialColumnCount: 2,
      selectedCell: null
    };
    return _this;
  }
  /**
   * Updates the initial column count used for table creation.
   *
   * @param {number} initialColumnCount New initial column count.
   */


  _createClass(TableEdit, [{
    key: "onChangeInitialColumnCount",
    value: function onChangeInitialColumnCount(initialColumnCount) {
      this.setState({
        initialColumnCount: initialColumnCount
      });
    }
    /**
     * Updates the initial row count used for table creation.
     *
     * @param {number} initialRowCount New initial row count.
     */

  }, {
    key: "onChangeInitialRowCount",
    value: function onChangeInitialRowCount(initialRowCount) {
      this.setState({
        initialRowCount: initialRowCount
      });
    }
    /**
     * Creates a table based on dimensions in local state.
     *
     * @param {Object} event Form submit event.
     */

  }, {
    key: "onCreateTable",
    value: function onCreateTable(event) {
      event.preventDefault();
      var setAttributes = this.props.setAttributes;
      var _this$state = this.state,
          initialRowCount = _this$state.initialRowCount,
          initialColumnCount = _this$state.initialColumnCount;
      initialRowCount = parseInt(initialRowCount, 10) || 2;
      initialColumnCount = parseInt(initialColumnCount, 10) || 2;
      setAttributes(createTable({
        rowCount: initialRowCount,
        columnCount: initialColumnCount
      }));
    }
    /**
     * Toggles whether the table has a fixed layout or not.
     */

  }, {
    key: "onChangeFixedLayout",
    value: function onChangeFixedLayout() {
      var _this$props = this.props,
          attributes = _this$props.attributes,
          setAttributes = _this$props.setAttributes;
      var hasFixedLayout = attributes.hasFixedLayout;
      setAttributes({
        hasFixedLayout: !hasFixedLayout
      });
    }
    /**
     * Changes the content of the currently selected cell.
     *
     * @param {Array} content A RichText content value.
     */

  }, {
    key: "onChange",
    value: function onChange(content) {
      var selectedCell = this.state.selectedCell;

      if (!selectedCell) {
        return;
      }

      var _this$props2 = this.props,
          attributes = _this$props2.attributes,
          setAttributes = _this$props2.setAttributes;
      setAttributes(updateSelectedCell(attributes, selectedCell, function (cellAttributes) {
        return _objectSpread(_objectSpread({}, cellAttributes), {}, {
          content: content
        });
      }));
    }
    /**
     * Align text within the a column.
     *
     * @param {string} align The new alignment to apply to the column.
     */

  }, {
    key: "onChangeColumnAlignment",
    value: function onChangeColumnAlignment(align) {
      var selectedCell = this.state.selectedCell;

      if (!selectedCell) {
        return;
      } // Convert the cell selection to a column selection so that alignment
      // is applied to the entire column.


      var columnSelection = {
        type: 'column',
        columnIndex: selectedCell.columnIndex
      };
      var _this$props3 = this.props,
          attributes = _this$props3.attributes,
          setAttributes = _this$props3.setAttributes;
      var newAttributes = updateSelectedCell(attributes, columnSelection, function (cellAttributes) {
        return _objectSpread(_objectSpread({}, cellAttributes), {}, {
          align: align
        });
      });
      setAttributes(newAttributes);
    }
    /**
     * Get the alignment of the currently selected cell.
     *
     * @return {string} The new alignment to apply to the column.
     */

  }, {
    key: "getCellAlignment",
    value: function getCellAlignment() {
      var selectedCell = this.state.selectedCell;

      if (!selectedCell) {
        return;
      }

      var attributes = this.props.attributes;
      return getCellAttribute(attributes, selectedCell, 'align');
    }
    /**
     * Add or remove a `head` table section.
     */

  }, {
    key: "onToggleHeaderSection",
    value: function onToggleHeaderSection() {
      var _this$props4 = this.props,
          attributes = _this$props4.attributes,
          setAttributes = _this$props4.setAttributes;
      setAttributes(toggleSection(attributes, 'head'));
    }
    /**
     * Add or remove a `foot` table section.
     */

  }, {
    key: "onToggleFooterSection",
    value: function onToggleFooterSection() {
      var _this$props5 = this.props,
          attributes = _this$props5.attributes,
          setAttributes = _this$props5.setAttributes;
      setAttributes(toggleSection(attributes, 'foot'));
    }
    /**
     * Inserts a row at the currently selected row index, plus `delta`.
     *
     * @param {number} delta Offset for selected row index at which to insert.
     */

  }, {
    key: "onInsertRow",
    value: function onInsertRow(delta) {
      var selectedCell = this.state.selectedCell;

      if (!selectedCell) {
        return;
      }

      var _this$props6 = this.props,
          attributes = _this$props6.attributes,
          setAttributes = _this$props6.setAttributes;
      var sectionName = selectedCell.sectionName,
          rowIndex = selectedCell.rowIndex;
      var newRowIndex = rowIndex + delta;
      setAttributes(insertRow(attributes, {
        sectionName: sectionName,
        rowIndex: newRowIndex
      })); // Select the first cell of the new row

      this.setState({
        selectedCell: {
          sectionName: sectionName,
          rowIndex: newRowIndex,
          columnIndex: 0,
          type: 'cell'
        }
      });
    }
    /**
     * Inserts a row before the currently selected row.
     */

  }, {
    key: "onInsertRowBefore",
    value: function onInsertRowBefore() {
      this.onInsertRow(0);
    }
    /**
     * Inserts a row after the currently selected row.
     */

  }, {
    key: "onInsertRowAfter",
    value: function onInsertRowAfter() {
      this.onInsertRow(1);
    }
    /**
     * Deletes the currently selected row.
     */

  }, {
    key: "onDeleteRow",
    value: function onDeleteRow() {
      var selectedCell = this.state.selectedCell;

      if (!selectedCell) {
        return;
      }

      var _this$props7 = this.props,
          attributes = _this$props7.attributes,
          setAttributes = _this$props7.setAttributes;
      var sectionName = selectedCell.sectionName,
          rowIndex = selectedCell.rowIndex;
      this.setState({
        selectedCell: null
      });
      setAttributes(deleteRow(attributes, {
        sectionName: sectionName,
        rowIndex: rowIndex
      }));
    }
    /**
     * Inserts a column at the currently selected column index, plus `delta`.
     *
     * @param {number} delta Offset for selected column index at which to insert.
     */

  }, {
    key: "onInsertColumn",
    value: function onInsertColumn() {
      var delta = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
      var selectedCell = this.state.selectedCell;

      if (!selectedCell) {
        return;
      }

      var _this$props8 = this.props,
          attributes = _this$props8.attributes,
          setAttributes = _this$props8.setAttributes;
      var columnIndex = selectedCell.columnIndex;
      var newColumnIndex = columnIndex + delta;
      setAttributes(insertColumn(attributes, {
        columnIndex: newColumnIndex
      })); // Select the first cell of the new column

      this.setState({
        selectedCell: {
          rowIndex: 0,
          columnIndex: newColumnIndex,
          type: 'cell'
        }
      });
    }
    /**
     * Inserts a column before the currently selected column.
     */

  }, {
    key: "onInsertColumnBefore",
    value: function onInsertColumnBefore() {
      this.onInsertColumn(0);
    }
    /**
     * Inserts a column after the currently selected column.
     */

  }, {
    key: "onInsertColumnAfter",
    value: function onInsertColumnAfter() {
      this.onInsertColumn(1);
    }
    /**
     * Deletes the currently selected column.
     */

  }, {
    key: "onDeleteColumn",
    value: function onDeleteColumn() {
      var selectedCell = this.state.selectedCell;

      if (!selectedCell) {
        return;
      }

      var _this$props9 = this.props,
          attributes = _this$props9.attributes,
          setAttributes = _this$props9.setAttributes;
      var sectionName = selectedCell.sectionName,
          columnIndex = selectedCell.columnIndex;
      this.setState({
        selectedCell: null
      });
      setAttributes(deleteColumn(attributes, {
        sectionName: sectionName,
        columnIndex: columnIndex
      }));
    }
    /**
     * Creates an onFocus handler for a specified cell.
     *
     * @param {Object} cellLocation Object with `section`, `rowIndex`, and
     *                              `columnIndex` properties.
     *
     * @return {Function} Function to call on focus.
     */

  }, {
    key: "createOnFocus",
    value: function createOnFocus(cellLocation) {
      var _this2 = this;

      return function () {
        _this2.setState({
          selectedCell: _objectSpread(_objectSpread({}, cellLocation), {}, {
            type: 'cell'
          })
        });
      };
    }
    /**
     * Gets the table controls to display in the block toolbar.
     *
     * @return {Array} Table controls.
     */

  }, {
    key: "getTableControls",
    value: function getTableControls() {
      var selectedCell = this.state.selectedCell;
      return [{
        icon: tableRowBefore,
        title: __('Insert row before'),
        isDisabled: !selectedCell,
        onClick: this.onInsertRowBefore
      }, {
        icon: tableRowAfter,
        title: __('Insert row after'),
        isDisabled: !selectedCell,
        onClick: this.onInsertRowAfter
      }, {
        icon: tableRowDelete,
        title: __('Delete row'),
        isDisabled: !selectedCell,
        onClick: this.onDeleteRow
      }, {
        icon: tableColumnBefore,
        title: __('Insert column before'),
        isDisabled: !selectedCell,
        onClick: this.onInsertColumnBefore
      }, {
        icon: tableColumnAfter,
        title: __('Insert column after'),
        isDisabled: !selectedCell,
        onClick: this.onInsertColumnAfter
      }, {
        icon: tableColumnDelete,
        title: __('Delete column'),
        isDisabled: !selectedCell,
        onClick: this.onDeleteColumn
      }];
    }
    /**
     * Renders a table section.
     *
     * @param {Object} options
     * @param {string} options.name Section type: head, body, or foot.
     * @param {Array}  options.rows The rows to render.
     *
     * @return {Object} React element for the section.
     */

  }, {
    key: "renderSection",
    value: function renderSection(_ref) {
      var _this3 = this;

      var name = _ref.name,
          rows = _ref.rows;

      if (isEmptyTableSection(rows)) {
        return null;
      }

      var Tag = "t".concat(name);
      return createElement(Tag, null, rows.map(function (_ref2, rowIndex) {
        var cells = _ref2.cells;
        return createElement("tr", {
          key: rowIndex
        }, cells.map(function (_ref3, columnIndex) {
          var content = _ref3.content,
              CellTag = _ref3.tag,
              scope = _ref3.scope,
              align = _ref3.align;
          var cellLocation = {
            sectionName: name,
            rowIndex: rowIndex,
            columnIndex: columnIndex
          };
          var cellClasses = classnames(_defineProperty({}, "has-text-align-".concat(align), align), 'wp-block-table__cell-content');
          var placeholder = '';

          if (name === 'head') {
            placeholder = __('Header label');
          } else if (name === 'foot') {
            placeholder = __('Footer label');
          }

          return createElement(RichText, {
            tagName: CellTag,
            key: columnIndex,
            className: cellClasses,
            scope: CellTag === 'th' ? scope : undefined,
            value: content,
            onChange: _this3.onChange,
            unstableOnFocus: _this3.createOnFocus(cellLocation),
            placeholder: placeholder
          });
        }));
      }));
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate() {
      var isSelected = this.props.isSelected;
      var selectedCell = this.state.selectedCell;

      if (!isSelected && selectedCell) {
        this.setState({
          selectedCell: null
        });
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      var _this$props10 = this.props,
          attributes = _this$props10.attributes,
          className = _this$props10.className,
          backgroundColor = _this$props10.backgroundColor,
          setBackgroundColor = _this$props10.setBackgroundColor,
          setAttributes = _this$props10.setAttributes,
          insertBlocksAfter = _this$props10.insertBlocksAfter;
      var _this$state2 = this.state,
          initialRowCount = _this$state2.initialRowCount,
          initialColumnCount = _this$state2.initialColumnCount;
      var hasFixedLayout = attributes.hasFixedLayout,
          caption = attributes.caption,
          head = attributes.head,
          body = attributes.body,
          foot = attributes.foot;
      var isEmpty = isEmptyTableSection(head) && isEmptyTableSection(body) && isEmptyTableSection(foot);
      var Section = this.renderSection;

      if (isEmpty) {
        return createElement(Placeholder, {
          label: __('Table'),
          icon: createElement(BlockIcon, {
            icon: icon,
            showColors: true
          }),
          instructions: __('Insert a table for sharing data.')
        }, createElement("form", {
          className: "blocks-table__placeholder-form",
          onSubmit: this.onCreateTable
        }, createElement(TextControl, {
          type: "number",
          label: __('Column count'),
          value: initialColumnCount,
          onChange: this.onChangeInitialColumnCount,
          min: "1",
          className: "blocks-table__placeholder-input"
        }), createElement(TextControl, {
          type: "number",
          label: __('Row count'),
          value: initialRowCount,
          onChange: this.onChangeInitialRowCount,
          min: "1",
          className: "blocks-table__placeholder-input"
        }), createElement(Button, {
          className: "blocks-table__placeholder-button",
          isPrimary: true,
          type: "submit"
        }, __('Create Table'))));
      }

      var tableClasses = classnames(backgroundColor.class, {
        'has-fixed-layout': hasFixedLayout,
        'has-background': !!backgroundColor.color
      });
      return createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ToolbarItem, null, function (toggleProps) {
        return createElement(DropdownMenu, {
          hasArrowIndicator: true,
          icon: table,
          toggleProps: toggleProps,
          label: __('Edit table'),
          controls: _this4.getTableControls()
        });
      })), createElement(AlignmentToolbar, {
        label: __('Change column alignment'),
        alignmentControls: ALIGNMENT_CONTROLS,
        value: this.getCellAlignment(),
        onChange: function onChange(nextAlign) {
          return _this4.onChangeColumnAlignment(nextAlign);
        },
        onHover: this.onHoverAlignment
      })), createElement(InspectorControls, null, createElement(PanelBody, {
        title: __('Table settings'),
        className: "blocks-table-settings"
      }, createElement(ToggleControl, {
        label: __('Fixed width table cells'),
        checked: !!hasFixedLayout,
        onChange: this.onChangeFixedLayout
      }), createElement(ToggleControl, {
        label: __('Header section'),
        checked: !!(head && head.length),
        onChange: this.onToggleHeaderSection
      }), createElement(ToggleControl, {
        label: __('Footer section'),
        checked: !!(foot && foot.length),
        onChange: this.onToggleFooterSection
      })), createElement(PanelColorSettings, {
        title: __('Color settings'),
        initialOpen: false,
        colorSettings: [{
          value: backgroundColor.color,
          onChange: setBackgroundColor,
          label: __('Background color'),
          disableCustomColors: true,
          colors: BACKGROUND_COLORS
        }]
      })), createElement("figure", {
        className: className
      }, createElement("table", {
        className: tableClasses
      }, createElement(Section, {
        name: "head",
        rows: head
      }), createElement(Section, {
        name: "body",
        rows: body
      }), createElement(Section, {
        name: "foot",
        rows: foot
      })), createElement(RichText, {
        tagName: "figcaption",
        placeholder: __('Write caption…'),
        value: caption,
        onChange: function onChange(value) {
          return setAttributes({
            caption: value
          });
        } // Deselect the selected table cell when the caption is focused.
        ,
        unstableOnFocus: function unstableOnFocus() {
          return _this4.setState({
            selectedCell: null
          });
        },
        __unstableOnSplitAtEnd: function __unstableOnSplitAtEnd() {
          return insertBlocksAfter(createBlock('core/paragraph'));
        }
      })));
    }
  }]);

  return TableEdit;
}(Component);
export default withCustomBackgroundColors('backgroundColor')(TableEdit);
//# sourceMappingURL=edit.js.map