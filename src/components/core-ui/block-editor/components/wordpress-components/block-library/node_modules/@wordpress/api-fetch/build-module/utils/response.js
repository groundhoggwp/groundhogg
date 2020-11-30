/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Parses the apiFetch response.
 *
 * @param {Response} response
 * @param {boolean}  shouldParseResponse
 *
 * @return {Promise} Parsed response
 */

var parseResponse = function parseResponse(response) {
  var shouldParseResponse = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;

  if (shouldParseResponse) {
    if (response.status === 204) {
      return null;
    }

    return response.json ? response.json() : Promise.reject(response);
  }

  return response;
};

var parseJsonAndNormalizeError = function parseJsonAndNormalizeError(response) {
  var invalidJsonError = {
    code: 'invalid_json',
    message: __('The response is not a valid JSON response.')
  };

  if (!response || !response.json) {
    throw invalidJsonError;
  }

  return response.json().catch(function () {
    throw invalidJsonError;
  });
};
/**
 * Parses the apiFetch response properly and normalize response errors.
 *
 * @param {Response} response
 * @param {boolean}  shouldParseResponse
 *
 * @return {Promise} Parsed response.
 */


export var parseResponseAndNormalizeError = function parseResponseAndNormalizeError(response) {
  var shouldParseResponse = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  return Promise.resolve(parseResponse(response, shouldParseResponse)).catch(function (res) {
    return parseAndThrowError(res, shouldParseResponse);
  });
};
export function parseAndThrowError(response) {
  var shouldParseResponse = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;

  if (!shouldParseResponse) {
    throw response;
  }

  return parseJsonAndNormalizeError(response).then(function (error) {
    var unknownError = {
      code: 'unknown_error',
      message: __('An unknown error occurred.')
    };
    throw error || unknownError;
  });
}
//# sourceMappingURL=response.js.map