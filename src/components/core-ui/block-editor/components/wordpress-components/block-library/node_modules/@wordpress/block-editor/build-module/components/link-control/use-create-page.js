import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState, useRef } from '@wordpress/element';
export default function useCreatePage(handleCreatePage) {
  var cancelableCreateSuggestion = useRef();

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isCreatingPage = _useState2[0],
      setIsCreatingPage = _useState2[1];

  var _useState3 = useState(null),
      _useState4 = _slicedToArray(_useState3, 2),
      errorMessage = _useState4[0],
      setErrorMessage = _useState4[1];

  var createPage = /*#__PURE__*/function () {
    var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(suggestionTitle) {
      return _regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              setIsCreatingPage(true);
              setErrorMessage(null);
              _context.prev = 2;
              // Make cancellable in order that we can avoid setting State
              // if the component unmounts during the call to `createSuggestion`
              cancelableCreateSuggestion.current = makeCancelable( // Using Promise.resolve to allow createSuggestion to return a
              // non-Promise based value.
              Promise.resolve(handleCreatePage(suggestionTitle)));
              _context.next = 6;
              return cancelableCreateSuggestion.current.promise;

            case 6:
              return _context.abrupt("return", _context.sent);

            case 9:
              _context.prev = 9;
              _context.t0 = _context["catch"](2);

              if (!(_context.t0 && _context.t0.isCanceled)) {
                _context.next = 13;
                break;
              }

              return _context.abrupt("return");

            case 13:
              setErrorMessage(_context.t0.message || __('An unknown error occurred during creation. Please try again.'));
              throw _context.t0;

            case 15:
              _context.prev = 15;
              setIsCreatingPage(false);
              return _context.finish(15);

            case 18:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[2, 9, 15, 18]]);
    }));

    return function createPage(_x) {
      return _ref.apply(this, arguments);
    };
  }();
  /**
   * Handles cancelling any pending Promises that have been made cancelable.
   */


  useEffect(function () {
    return function () {
      // componentDidUnmount
      if (cancelableCreateSuggestion.current) {
        cancelableCreateSuggestion.current.cancel();
      }
    };
  }, []);
  return {
    createPage: createPage,
    isCreatingPage: isCreatingPage,
    errorMessage: errorMessage
  };
}
/**
 * Creates a wrapper around a promise which allows it to be programmatically
 * cancelled.
 * See: https://reactjs.org/blog/2015/12/16/ismounted-antipattern.html
 *
 * @param {Promise} promise the Promise to make cancelable
 */

var makeCancelable = function makeCancelable(promise) {
  var hasCanceled_ = false;
  var wrappedPromise = new Promise(function (resolve, reject) {
    promise.then(function (val) {
      return hasCanceled_ ? reject({
        isCanceled: true
      }) : resolve(val);
    }, function (error) {
      return hasCanceled_ ? reject({
        isCanceled: true
      }) : reject(error);
    });
  });
  return {
    promise: wrappedPromise,
    cancel: function cancel() {
      hasCanceled_ = true;
    }
  };
};
//# sourceMappingURL=use-create-page.js.map