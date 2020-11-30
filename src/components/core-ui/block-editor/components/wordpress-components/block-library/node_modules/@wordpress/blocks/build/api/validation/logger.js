"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.createLogger = createLogger;
exports.createQueuedLogger = createQueuedLogger;

function createLogger() {
  /**
   * Creates a log handler with block validation prefix.
   *
   * @param {Function} logger Original logger function.
   *
   * @return {Function} Augmented logger function.
   */
  function createLogHandler(logger) {
    var log = function log(message) {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      return logger.apply(void 0, ['Block validation: ' + message].concat(args));
    }; // In test environments, pre-process the sprintf message to improve
    // readability of error messages. We'd prefer to avoid pulling in this
    // dependency in runtime environments, and it can be dropped by a combo
    // of Webpack env substitution + UglifyJS dead code elimination.


    if (process.env.NODE_ENV === 'test') {
      log = function log() {
        var _require;

        return (// eslint-disable-next-line import/no-extraneous-dependencies
          logger((_require = require('sprintf-js')).sprintf.apply(_require, arguments))
        );
      };
    }

    return log;
  }

  return {
    // eslint-disable-next-line no-console
    error: createLogHandler(console.error),
    // eslint-disable-next-line no-console
    warning: createLogHandler(console.warn),
    getItems: function getItems() {
      return [];
    }
  };
}

function createQueuedLogger() {
  /**
   * The list of enqueued log actions to print.
   *
   * @type {Array}
   */
  var queue = [];
  var logger = createLogger();
  return {
    error: function error() {
      for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
        args[_key2] = arguments[_key2];
      }

      queue.push({
        log: logger.error,
        args: args
      });
    },
    warning: function warning() {
      for (var _len3 = arguments.length, args = new Array(_len3), _key3 = 0; _key3 < _len3; _key3++) {
        args[_key3] = arguments[_key3];
      }

      queue.push({
        log: logger.warning,
        args: args
      });
    },
    getItems: function getItems() {
      return queue;
    }
  };
}
//# sourceMappingURL=logger.js.map