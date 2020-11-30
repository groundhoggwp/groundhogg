"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.createTable = createTable;
exports.getFirstRow = getFirstRow;
exports.getCellAttribute = getCellAttribute;
exports.updateSelectedCell = updateSelectedCell;
exports.isCellSelected = isCellSelected;
exports.insertRow = insertRow;
exports.deleteRow = deleteRow;
exports.insertColumn = insertColumn;
exports.deleteColumn = deleteColumn;
exports.toggleSection = toggleSection;
exports.isEmptyTableSection = isEmptyTableSection;
exports.isEmptyRow = isEmptyRow;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var INHERITED_COLUMN_ATTRIBUTES = ['align'];
/**
 * Creates a table state.
 *
 * @param {Object} options
 * @param {number} options.rowCount    Row count for the table to create.
 * @param {number} options.columnCount Column count for the table to create.
 *
 * @return {Object} New table state.
 */

function createTable(_ref) {
  var rowCount = _ref.rowCount,
      columnCount = _ref.columnCount;
  return {
    body: (0, _lodash.times)(rowCount, function () {
      return {
        cells: (0, _lodash.times)(columnCount, function () {
          return {
            content: '',
            tag: 'td'
          };
        })
      };
    })
  };
}
/**
 * Returns the first row in the table.
 *
 * @param {Object} state Current table state.
 *
 * @return {Object} The first table row.
 */


function getFirstRow(state) {
  if (!isEmptyTableSection(state.head)) {
    return state.head[0];
  }

  if (!isEmptyTableSection(state.body)) {
    return state.body[0];
  }

  if (!isEmptyTableSection(state.foot)) {
    return state.foot[0];
  }
}
/**
 * Gets an attribute for a cell.
 *
 * @param {Object} state 		 Current table state.
 * @param {Object} cellLocation  The location of the cell
 * @param {string} attributeName The name of the attribute to get the value of.
 *
 * @return {*} The attribute value.
 */


function getCellAttribute(state, cellLocation, attributeName) {
  var sectionName = cellLocation.sectionName,
      rowIndex = cellLocation.rowIndex,
      columnIndex = cellLocation.columnIndex;
  return (0, _lodash.get)(state, [sectionName, rowIndex, 'cells', columnIndex, attributeName]);
}
/**
 * Returns updated cell attributes after applying the `updateCell` function to the selection.
 *
 * @param {Object}   state      The block attributes.
 * @param {Object}   selection  The selection of cells to update.
 * @param {Function} updateCell A function to update the selected cell attributes.
 *
 * @return {Object} New table state including the updated cells.
 */


function updateSelectedCell(state, selection, updateCell) {
  if (!selection) {
    return state;
  }

  var tableSections = (0, _lodash.pick)(state, ['head', 'body', 'foot']);
  var selectionSectionName = selection.sectionName,
      selectionRowIndex = selection.rowIndex;
  return (0, _lodash.mapValues)(tableSections, function (section, sectionName) {
    if (selectionSectionName && selectionSectionName !== sectionName) {
      return section;
    }

    return section.map(function (row, rowIndex) {
      if (selectionRowIndex && selectionRowIndex !== rowIndex) {
        return row;
      }

      return {
        cells: row.cells.map(function (cellAttributes, columnIndex) {
          var cellLocation = {
            sectionName: sectionName,
            columnIndex: columnIndex,
            rowIndex: rowIndex
          };

          if (!isCellSelected(cellLocation, selection)) {
            return cellAttributes;
          }

          return updateCell(cellAttributes);
        })
      };
    });
  });
}
/**
 * Returns whether the cell at `cellLocation` is included in the selection `selection`.
 *
 * @param {Object} cellLocation An object containing cell location properties.
 * @param {Object} selection    An object containing selection properties.
 *
 * @return {boolean} True if the cell is selected, false otherwise.
 */


function isCellSelected(cellLocation, selection) {
  if (!cellLocation || !selection) {
    return false;
  }

  switch (selection.type) {
    case 'column':
      return selection.type === 'column' && cellLocation.columnIndex === selection.columnIndex;

    case 'cell':
      return selection.type === 'cell' && cellLocation.sectionName === selection.sectionName && cellLocation.columnIndex === selection.columnIndex && cellLocation.rowIndex === selection.rowIndex;
  }
}
/**
 * Inserts a row in the table state.
 *
 * @param {Object} state               Current table state.
 * @param {Object} options
 * @param {string} options.sectionName Section in which to insert the row.
 * @param {number} options.rowIndex    Row index at which to insert the row.
 * @param {number} options.columnCount Column count for the table to create.
 *
 * @return {Object} New table state.
 */


function insertRow(state, _ref2) {
  var sectionName = _ref2.sectionName,
      rowIndex = _ref2.rowIndex,
      columnCount = _ref2.columnCount;
  var firstRow = getFirstRow(state);
  var cellCount = columnCount === undefined ? (0, _lodash.get)(firstRow, ['cells', 'length']) : columnCount; // Bail early if the function cannot determine how many cells to add.

  if (!cellCount) {
    return state;
  }

  return (0, _defineProperty2.default)({}, sectionName, [].concat((0, _toConsumableArray2.default)(state[sectionName].slice(0, rowIndex)), [{
    cells: (0, _lodash.times)(cellCount, function (index) {
      var firstCellInColumn = (0, _lodash.get)(firstRow, ['cells', index], {});
      var inheritedAttributes = (0, _lodash.pick)(firstCellInColumn, INHERITED_COLUMN_ATTRIBUTES);
      return _objectSpread(_objectSpread({}, inheritedAttributes), {}, {
        content: '',
        tag: sectionName === 'head' ? 'th' : 'td'
      });
    })
  }], (0, _toConsumableArray2.default)(state[sectionName].slice(rowIndex))));
}
/**
 * Deletes a row from the table state.
 *
 * @param {Object} state               Current table state.
 * @param {Object} options
 * @param {string} options.sectionName Section in which to delete the row.
 * @param {number} options.rowIndex    Row index to delete.
 *
 * @return {Object} New table state.
 */


function deleteRow(state, _ref4) {
  var sectionName = _ref4.sectionName,
      rowIndex = _ref4.rowIndex;
  return (0, _defineProperty2.default)({}, sectionName, state[sectionName].filter(function (row, index) {
    return index !== rowIndex;
  }));
}
/**
 * Inserts a column in the table state.
 *
 * @param {Object} state               Current table state.
 * @param {Object} options
 * @param {number} options.columnIndex Column index at which to insert the column.
 *
 * @return {Object} New table state.
 */


function insertColumn(state, _ref6) {
  var columnIndex = _ref6.columnIndex;
  var tableSections = (0, _lodash.pick)(state, ['head', 'body', 'foot']);
  return (0, _lodash.mapValues)(tableSections, function (section, sectionName) {
    // Bail early if the table section is empty.
    if (isEmptyTableSection(section)) {
      return section;
    }

    return section.map(function (row) {
      // Bail early if the row is empty or it's an attempt to insert past
      // the last possible index of the array.
      if (isEmptyRow(row) || row.cells.length < columnIndex) {
        return row;
      }

      return {
        cells: [].concat((0, _toConsumableArray2.default)(row.cells.slice(0, columnIndex)), [{
          content: '',
          tag: sectionName === 'head' ? 'th' : 'td'
        }], (0, _toConsumableArray2.default)(row.cells.slice(columnIndex)))
      };
    });
  });
}
/**
 * Deletes a column from the table state.
 *
 * @param {Object} state               Current table state.
 * @param {Object} options
 * @param {number} options.columnIndex Column index to delete.
 *
 * @return {Object} New table state.
 */


function deleteColumn(state, _ref7) {
  var columnIndex = _ref7.columnIndex;
  var tableSections = (0, _lodash.pick)(state, ['head', 'body', 'foot']);
  return (0, _lodash.mapValues)(tableSections, function (section) {
    // Bail early if the table section is empty.
    if (isEmptyTableSection(section)) {
      return section;
    }

    return section.map(function (row) {
      return {
        cells: row.cells.length >= columnIndex ? row.cells.filter(function (cell, index) {
          return index !== columnIndex;
        }) : row.cells
      };
    }).filter(function (row) {
      return row.cells.length;
    });
  });
}
/**
 * Toggles the existance of a section.
 *
 * @param {Object} state       Current table state.
 * @param {string} sectionName Name of the section to toggle.
 *
 * @return {Object} New table state.
 */


function toggleSection(state, sectionName) {
  // Section exists, replace it with an empty row to remove it.
  if (!isEmptyTableSection(state[sectionName])) {
    return (0, _defineProperty2.default)({}, sectionName, []);
  } // Get the length of the first row of the body to use when creating the header.


  var columnCount = (0, _lodash.get)(state, ['body', 0, 'cells', 'length'], 1); // Section doesn't exist, insert an empty row to create the section.

  return insertRow(state, {
    sectionName: sectionName,
    rowIndex: 0,
    columnCount: columnCount
  });
}
/**
 * Determines whether a table section is empty.
 *
 * @param {Object} section Table section state.
 *
 * @return {boolean} True if the table section is empty, false otherwise.
 */


function isEmptyTableSection(section) {
  return !section || !section.length || (0, _lodash.every)(section, isEmptyRow);
}
/**
 * Determines whether a table row is empty.
 *
 * @param {Object} row Table row state.
 *
 * @return {boolean} True if the table section is empty, false otherwise.
 */


function isEmptyRow(row) {
  return !(row.cells && row.cells.length);
}
//# sourceMappingURL=state.js.map