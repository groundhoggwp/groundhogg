import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * WordPress term object from REST API.
 * Categories ref: https://developer.wordpress.org/rest-api/reference/categories/
 * Tags ref: https://developer.wordpress.org/rest-api/reference/tags/
 *
 * @typedef {Object} WPTerm
 * @property {number} id Unique identifier for the term.
 * @property {number} count Number of published posts for the term.
 * @property {string} description HTML description of the term.
 * @property {string} link URL of the term.
 * @property {string} name HTML title for the term.
 * @property {string} slug An alphanumeric identifier for the term unique to its type.
 * @property {string} taxonomy Type attribution for the term.
 * @property {Object} meta Meta fields
 * @property {number} [parent] The parent term ID.
 */

/**
 * The object used in Query block that contains info and helper mappings
 * from an array of WPTerm.
 *
 * @typedef {Object} QueryTermsInfo
 * @property {WPTerm[]} terms The array of terms.
 * @property {Object<string, WPTerm>} mapById Object mapping with the term id as key and the term as value.
 * @property {Object<string, WPTerm>} mapByName Object mapping with the term name as key and the term as value.
 * @property {string[]} names Array with the terms' names.
 */

/**
 * Returns a helper object with mapping from WPTerms.
 *
 * @param {WPTerm[]} terms The terms to extract of helper object.
 * @return {QueryTermsInfo} The object with the terms information.
 */
export var getTermsInfo = function getTermsInfo(terms) {
  return _objectSpread({
    terms: terms
  }, terms === null || terms === void 0 ? void 0 : terms.reduce(function (accumulator, term) {
    var mapById = accumulator.mapById,
        mapByName = accumulator.mapByName,
        names = accumulator.names;
    mapById[term.id] = term;
    mapByName[term.name] = term;
    names.push(term.name);
    return accumulator;
  }, {
    mapById: {},
    mapByName: {},
    names: []
  }));
};
//# sourceMappingURL=utils.js.map