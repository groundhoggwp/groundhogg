"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * Return `true` if the given path is http/https.
 *
 * @param  {string}  filePath path
 *
 * @return {boolean} is remote path.
 */
function isRemotePath(filePath) {
  return /^(?:https?:)?\/\//.test(filePath);
}
/**
 * Return `true` if the given filePath is an absolute url.
 *
 * @param  {string}  filePath path
 *
 * @return {boolean} is absolute path.
 */


function isAbsolutePath(filePath) {
  return /^\/(?!\/)/.test(filePath);
}
/**
 * Whether or not the url should be inluded.
 *
 * @param  {Object} meta url meta info
 *
 * @return {boolean} is valid.
 */


function isValidURL(meta) {
  // ignore hashes or data uris
  if (meta.value.indexOf('data:') === 0 || meta.value.indexOf('#') === 0) {
    return false;
  }

  if (isAbsolutePath(meta.value)) {
    return false;
  } // do not handle the http/https urls if `includeRemote` is false


  if (isRemotePath(meta.value)) {
    return false;
  }

  return true;
}
/**
 * Get the absolute path of the url, relative to the basePath
 *
 * @param  {string} str          the url
 * @param  {string} baseURL      base URL
 *
 * @return {string}              the full path to the file
 */


function getResourcePath(str, baseURL) {
  return new URL(str, baseURL).toString();
}
/**
 * Process the single `url()` pattern
 *
 * @param  {string} baseURL  the base URL for relative URLs
 * @return {Promise}         the Promise
 */


function processURL(baseURL) {
  return function (meta) {
    return _objectSpread(_objectSpread({}, meta), {}, {
      newUrl: 'url(' + meta.before + meta.quote + getResourcePath(meta.value, baseURL) + meta.quote + meta.after + ')'
    });
  };
}
/**
 * Get all `url()`s, and return the meta info
 *
 * @param  {string} value decl.value
 *
 * @return {Array}        the urls
 */


function getURLs(value) {
  var reg = /url\((\s*)(['"]?)(.+?)\2(\s*)\)/g;
  var match;
  var URLs = [];

  while ((match = reg.exec(value)) !== null) {
    var meta = {
      source: match[0],
      before: match[1],
      quote: match[2],
      value: match[3],
      after: match[4]
    };

    if (isValidURL(meta)) {
      URLs.push(meta);
    }
  }

  return URLs;
}
/**
 * Replace the raw value's `url()` segment to the new value
 *
 * @param  {string} raw  the raw value
 * @param  {Array}  URLs the URLs to replace
 *
 * @return {string}     the new value
 */


function replaceURLs(raw, URLs) {
  URLs.forEach(function (item) {
    raw = raw.replace(item.source, item.newUrl);
  });
  return raw;
}

var rewrite = function rewrite(rootURL) {
  return function (node) {
    if (node.type === 'declaration') {
      var updatedURLs = getURLs(node.value).map(processURL(rootURL));
      return _objectSpread(_objectSpread({}, node), {}, {
        value: replaceURLs(node.value, updatedURLs)
      });
    }

    return node;
  };
};

var _default = rewrite;
exports.default = _default;
//# sourceMappingURL=url-rewrite.js.map