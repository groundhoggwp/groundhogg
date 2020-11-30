"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getBlockContentSchema = getBlockContentSchema;
exports.isPlain = isPlain;
exports.deepFilterNodeList = deepFilterNodeList;
exports.deepFilterHTML = deepFilterHTML;
exports.getSibling = getSibling;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _dom = require("@wordpress/dom");

var _ = require("..");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Given raw transforms from blocks, merges all schemas into one.
 *
 * @param {Array}  transforms            Block transforms, of the `raw` type.
 * @param {Object} phrasingContentSchema The phrasing content schema.
 * @param {Object} isPaste               Whether the context is pasting or not.
 *
 * @return {Object} A complete block content schema.
 */
function getBlockContentSchema(transforms, phrasingContentSchema, isPaste) {
  var schemas = transforms.map(function (_ref) {
    var isMatch = _ref.isMatch,
        blockName = _ref.blockName,
        schema = _ref.schema;
    var hasAnchorSupport = (0, _.hasBlockSupport)(blockName, 'anchor');
    schema = (0, _lodash.isFunction)(schema) ? schema({
      phrasingContentSchema: phrasingContentSchema,
      isPaste: isPaste
    }) : schema; // If the block does not has anchor support and the transform does not
    // provides an isMatch we can return the schema right away.

    if (!hasAnchorSupport && !isMatch) {
      return schema;
    }

    return (0, _lodash.mapValues)(schema, function (value) {
      var attributes = value.attributes || []; // If the block supports the "anchor" functionality, it needs to keep its ID attribute.

      if (hasAnchorSupport) {
        attributes = [].concat((0, _toConsumableArray2.default)(attributes), ['id']);
      }

      return _objectSpread({}, value, {
        attributes: attributes,
        isMatch: isMatch ? isMatch : undefined
      });
    });
  });
  return _lodash.mergeWith.apply(void 0, [{}].concat((0, _toConsumableArray2.default)(schemas), [function (objValue, srcValue, key) {
    switch (key) {
      case 'children':
        {
          if (objValue === '*' || srcValue === '*') {
            return '*';
          }

          return _objectSpread({}, objValue, {}, srcValue);
        }

      case 'attributes':
      case 'require':
        {
          return [].concat((0, _toConsumableArray2.default)(objValue || []), (0, _toConsumableArray2.default)(srcValue || []));
        }

      case 'isMatch':
        {
          // If one of the values being merge is undefined (matches everything),
          // the result of the merge will be undefined.
          if (!objValue || !srcValue) {
            return undefined;
          } // When merging two isMatch functions, the result is a new function
          // that returns if one of the source functions returns true.


          return function () {
            return objValue.apply(void 0, arguments) || srcValue.apply(void 0, arguments);
          };
        }
    }
  }]));
}
/**
 * Checks wether HTML can be considered plain text. That is, it does not contain
 * any elements that are not line breaks.
 *
 * @param {string} HTML The HTML to check.
 *
 * @return {boolean} Wether the HTML can be considered plain text.
 */


function isPlain(HTML) {
  return !/<(?!br[ />])/i.test(HTML);
}
/**
 * Given node filters, deeply filters and mutates a NodeList.
 *
 * @param {NodeList} nodeList The nodeList to filter.
 * @param {Array}    filters  An array of functions that can mutate with the provided node.
 * @param {Document} doc      The document of the nodeList.
 * @param {Object}   schema   The schema to use.
 */


function deepFilterNodeList(nodeList, filters, doc, schema) {
  Array.from(nodeList).forEach(function (node) {
    deepFilterNodeList(node.childNodes, filters, doc, schema);
    filters.forEach(function (item) {
      // Make sure the node is still attached to the document.
      if (!doc.contains(node)) {
        return;
      }

      item(node, doc, schema);
    });
  });
}
/**
 * Given node filters, deeply filters HTML tags.
 * Filters from the deepest nodes to the top.
 *
 * @param {string} HTML    The HTML to filter.
 * @param {Array}  filters An array of functions that can mutate with the provided node.
 * @param {Object} schema  The schema to use.
 *
 * @return {string} The filtered HTML.
 */


function deepFilterHTML(HTML) {
  var filters = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  var schema = arguments.length > 2 ? arguments[2] : undefined;
  var doc = document.implementation.createHTMLDocument('');
  doc.body.innerHTML = HTML;
  deepFilterNodeList(doc.body.childNodes, filters, doc, schema);
  return doc.body.innerHTML;
}
/**
 * Gets a sibling within text-level context.
 *
 * @param {Element} node  The subject node.
 * @param {string}  which "next" or "previous".
 */


function getSibling(node, which) {
  var sibling = node["".concat(which, "Sibling")];

  if (sibling && (0, _dom.isPhrasingContent)(sibling)) {
    return sibling;
  }

  var parentNode = node.parentNode;

  if (!parentNode || !(0, _dom.isPhrasingContent)(parentNode)) {
    return;
  }

  return getSibling(parentNode, which);
}
//# sourceMappingURL=utils.js.map