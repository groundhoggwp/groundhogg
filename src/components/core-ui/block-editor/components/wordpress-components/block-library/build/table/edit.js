"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = exports.TableEdit = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _icons = require("@wordpress/icons");

var _blocks = require("@wordpress/blocks");

var _state = require("./state");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = (0, _getPrototypeOf2.default)(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = (0, _getPrototypeOf2.default)(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return (0, _possibleConstructorReturn2.default)(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

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
  icon: _icons.alignLeft,
  title: (0, _i18n.__)('Align column left'),
  align: 'left'
}, {
  icon: _icons.alignCenter,
  title: (0, _i18n.__)('Align column center'),
  align: 'center'
}, {
  icon: _icons.alignRight,
  title: (0, _i18n.__)('Align column right'),
  align: 'right'
}];
var withCustomBackgroundColors = (0, _blockEditor.createCustomColorsHOC)(BACKGROUND_COLORS);

var TableEdit = /*#__PURE__*/function (_Component) {
  (0, _inherits2.default)(TableEdit, _Component);

  var _super = _createSuper(TableEdit);

  function TableEdit() {
    var _this;

    (0, _classCallCheck2.default)(this, TableEdit);
    _this = _super.apply(this, arguments);
    _this.onCreateTable = _this.onCreateTable.bind((0, _assertThisInitialized2.default)(_this));
    _this.onChangeFixedLayout = _this.onChangeFixedLayout.bind((0, _assertThisInitialized2.default)(_this));
    _this.onChange = _this.onChange.bind((0, _assertThisInitialized2.default)(_this));
    _this.onChangeInitialColumnCount = _this.onChangeInitialColumnCount.bind((0, _assertThisInitialized2.default)(_this));
    _this.onChangeInitialRowCount = _this.onChangeInitialRowCount.bind((0, _assertThisInitialized2.default)(_this));
    _this.renderSection = _this.renderSection.bind((0, _assertThisInitialized2.default)(_this));
    _this.getTableControls = _this.getTableControls.bind((0, _assertThisInitialized2.default)(_this));
    _this.onInsertRow = _this.onInsertRow.bind((0, _assertThisInitialized2.default)(_this));
    _this.onInsertRowBefore = _this.onInsertRowBefore.bind((0, _assertThisInitialized2.default)(_this));
    _this.onInsertRowAfter = _this.onInsertRowAfter.bind((0, _assertThisInitialized2.default)(_this));
    _this.onDeleteRow = _this.onDeleteRow.bind((0, _assertThisInitialized2.default)(_this));
    _this.onInsertColumn = _this.onInsertColumn.bind((0, _assertThisInitialized2.default)(_this));
    _this.onInsertColumnBefore = _this.onInsertColumnBefore.bind((0, _assertThisInitialized2.default)(_this));
    _this.onInsertColumnAfter = _this.onInsertColumnAfter.bind((0, _assertThisInitialized2.default)(_this));
    _this.onDeleteColumn = _this.onDeleteColumn.bind((0, _assertThisInitialized2.default)(_this));
    _this.onToggleHeaderSection = _this.onToggleHeaderSection.bind((0, _assertThisInitialized2.default)(_this));
    _this.onToggleFooterSection = _this.onToggleFooterSection.bind((0, _assertThisInitialized2.default)(_this));
    _this.onChangeColumnAlignment = _this.onChangeColumnAlignment.bind((0, _assertThisInitialized2.default)(_this));
    _this.getCellAlignment = _this.getCellAlignment.bind((0, _assertThisInitialized2.default)(_this));
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


  (0, _createClass2.default)(TableEdit, [{
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
      setAttributes((0, _state.createTable)({
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
      setAttributes((0, _state.updateSelectedCell)(attributes, selectedCell, function (cellAttributes) {
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
      var newAttributes = (0, _state.updateSelectedCell)(attributes, columnSelection, function (cellAttributes) {
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
      return (0, _state.getCellAttribute)(attributes, selectedCell, 'align');
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
      setAttributes((0, _state.toggleSection)(attributes, 'head'));
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
      setAttributes((0, _state.toggleSection)(attributes, 'foot'));
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
      setAttributes((0, _state.insertRow)(attributes, {
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
      setAttributes((0, _state.deleteRow)(attributes, {
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
      setAttributes((0, _state.insertColumn)(attributes, {
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
      setAttributes((0, _state.deleteColumn)(attributes, {
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
        icon: _icons.tableRowBefore,
        title: (0, _i18n.__)('Insert row before'),
        isDisabled: !selectedCell,
        onClick: this.onInsertRowBefore
      }, {
        icon: _icons.tableRowAfter,
        title: (0, _i18n.__)('Insert row after'),
        isDisabled: !selectedCell,
        onClick: this.onInsertRowAfter
      }, {
        icon: _icons.tableRowDelete,
        title: (0, _i18n.__)('Delete row'),
        isDisabled: !selectedCell,
        onClick: this.onDeleteRow
      }, {
        icon: _icons.tableColumnBefore,
        title: (0, _i18n.__)('Insert column before'),
        isDisabled: !selectedCell,
        onClick: this.onInsertColumnBefore
      }, {
        icon: _icons.tableColumnAfter,
        title: (0, _i18n.__)('Insert column after'),
        isDisabled: !selectedCell,
        onClick: this.onInsertColumnAfter
      }, {
        icon: _icons.tableColumnDelete,
        title: (0, _i18n.__)('Delete column'),
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

      if ((0, _state.isEmptyTableSection)(rows)) {
        return null;
      }

      var Tag = "t".concat(name);
      return (0, _element.createElement)(Tag, null, rows.map(function (_ref2, rowIndex) {
        var cells = _ref2.cells;
        return (0, _element.createElement)("tr", {
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
          var cellClasses = (0, _classnames2.default)((0, _defineProperty2.default)({}, "has-text-align-".concat(align), align), 'wp-block-table__cell-content');
          var placeholder = '';

          if (name === 'head') {
            placeholder = (0, _i18n.__)('Header label');
          } else if (name === 'foot') {
            placeholder = (0, _i18n.__)('Footer label');
          }

          return (0, _element.createElement)(_blockEditor.RichText, {
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
      var isEmpty = (0, _state.isEmptyTableSection)(head) && (0, _state.isEmptyTableSection)(body) && (0, _state.isEmptyTableSection)(foot);
      var Section = this.renderSection;

      if (isEmpty) {
        return (0, _element.createElement)(_components.Placeholder, {
          label: (0, _i18n.__)('Table'),
          icon: (0, _element.createElement)(_blockEditor.BlockIcon, {
            icon: _icons.blockTable,
            showColors: true
          }),
          instructions: (0, _i18n.__)('Insert a table for sharing data.')
        }, (0, _element.createElement)("form", {
          className: "blocks-table__placeholder-form",
          onSubmit: this.onCreateTable
        }, (0, _element.createElement)(_components.TextControl, {
          type: "number",
          label: (0, _i18n.__)('Column count'),
          value: initialColumnCount,
          onChange: this.onChangeInitialColumnCount,
          min: "1",
          className: "blocks-table__placeholder-input"
        }), (0, _element.createElement)(_components.TextControl, {
          type: "number",
          label: (0, _i18n.__)('Row count'),
          value: initialRowCount,
          onChange: this.onChangeInitialRowCount,
          min: "1",
          className: "blocks-table__placeholder-input"
        }), (0, _element.createElement)(_components.Button, {
          className: "blocks-table__placeholder-button",
          isPrimary: true,
          type: "submit"
        }, (0, _i18n.__)('Create Table'))));
      }

      var tableClasses = (0, _classnames2.default)(backgroundColor.class, {
        'has-fixed-layout': hasFixedLayout,
        'has-background': !!backgroundColor.color
      });
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, (0, _element.createElement)(_components.ToolbarGroup, null, (0, _element.createElement)(_components.ToolbarItem, null, function (toggleProps) {
        return (0, _element.createElement)(_components.DropdownMenu, {
          hasArrowIndicator: true,
          icon: _icons.table,
          toggleProps: toggleProps,
          label: (0, _i18n.__)('Edit table'),
          controls: _this4.getTableControls()
        });
      })), (0, _element.createElement)(_blockEditor.AlignmentToolbar, {
        label: (0, _i18n.__)('Change column alignment'),
        alignmentControls: ALIGNMENT_CONTROLS,
        value: this.getCellAlignment(),
        onChange: function onChange(nextAlign) {
          return _this4.onChangeColumnAlignment(nextAlign);
        },
        onHover: this.onHoverAlignment
      })), (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
        title: (0, _i18n.__)('Table settings'),
        className: "blocks-table-settings"
      }, (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Fixed width table cells'),
        checked: !!hasFixedLayout,
        onChange: this.onChangeFixedLayout
      }), (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Header section'),
        checked: !!(head && head.length),
        onChange: this.onToggleHeaderSection
      }), (0, _element.createElement)(_components.ToggleControl, {
        label: (0, _i18n.__)('Footer section'),
        checked: !!(foot && foot.length),
        onChange: this.onToggleFooterSection
      })), (0, _element.createElement)(_blockEditor.PanelColorSettings, {
        title: (0, _i18n.__)('Color settings'),
        initialOpen: false,
        colorSettings: [{
          value: backgroundColor.color,
          onChange: setBackgroundColor,
          label: (0, _i18n.__)('Background color'),
          disableCustomColors: true,
          colors: BACKGROUND_COLORS
        }]
      })), (0, _element.createElement)("figure", {
        className: className
      }, (0, _element.createElement)("table", {
        className: tableClasses
      }, (0, _element.createElement)(Section, {
        name: "head",
        rows: head
      }), (0, _element.createElement)(Section, {
        name: "body",
        rows: body
      }), (0, _element.createElement)(Section, {
        name: "foot",
        rows: foot
      })), (0, _element.createElement)(_blockEditor.RichText, {
        tagName: "figcaption",
        placeholder: (0, _i18n.__)('Write caption…'),
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
          return insertBlocksAfter((0, _blocks.createBlock)('core/paragraph'));
        }
      })));
    }
  }]);
  return TableEdit;
}(_element.Component);

exports.TableEdit = TableEdit;

var _default = withCustomBackgroundColors('backgroundColor')(TableEdit);

exports.default = _default;
//# sourceMappingURL=edit.js.map