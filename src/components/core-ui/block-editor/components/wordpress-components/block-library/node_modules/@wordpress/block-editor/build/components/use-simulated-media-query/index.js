"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = useSimulatedMediaQuery;

var _lodash = require("lodash");

var _cssMediaquery = require("css-mediaquery");

var _element = require("@wordpress/element");

var _url = require("@wordpress/url");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var ENABLED_MEDIA_QUERY = '(min-width:0px)';
var DISABLED_MEDIA_QUERY = '(min-width:999999px)';
var VALID_MEDIA_QUERY_REGEX = /\((min|max)-width:[^\(]*?\)/g;

function getStyleSheetsThatMatchHostname() {
  if (typeof window === 'undefined') {
    return [];
  }

  return (0, _lodash.filter)((0, _lodash.get)(window, ['document', 'styleSheets'], []), function (styleSheet) {
    if (!styleSheet.href) {
      return false;
    }

    return (0, _url.getProtocol)(styleSheet.href) === window.location.protocol && (0, _url.getAuthority)(styleSheet.href) === window.location.host;
  });
}

function isReplaceableMediaRule(rule) {
  if (!rule.media) {
    return false;
  } // Need to use "media.mediaText" instead of "conditionText" for IE support.


  return !!rule.media.mediaText.match(VALID_MEDIA_QUERY_REGEX);
}

function replaceRule(styleSheet, newRuleText, index) {
  styleSheet.deleteRule(index);
  styleSheet.insertRule(newRuleText, index);
}

function replaceMediaQueryWithWidthEvaluation(ruleText, widthValue) {
  return ruleText.replace(VALID_MEDIA_QUERY_REGEX, function (matchedSubstring) {
    if ((0, _cssMediaquery.match)(matchedSubstring, {
      type: 'screen',
      width: widthValue
    })) {
      return ENABLED_MEDIA_QUERY;
    }

    return DISABLED_MEDIA_QUERY;
  });
}
/**
 * Function that manipulates media queries from stylesheets to simulate a given
 * viewport width.
 *
 * @param {string}  marker CSS selector string defining start and end of
 *                         manipulable styles.
 * @param {number?} width  Viewport width to simulate. If provided null, the
 *                         stylesheets will not be modified.
 */


function useSimulatedMediaQuery(marker, width) {
  (0, _element.useEffect)(function () {
    if (!width) {
      return;
    }

    var styleSheets = getStyleSheetsThatMatchHostname();
    var originalStyles = [];
    styleSheets.forEach(function (styleSheet, styleSheetIndex) {
      var relevantSection = false;

      for (var ruleIndex = 0; ruleIndex < styleSheet.cssRules.length; ++ruleIndex) {
        var rule = styleSheet.cssRules[ruleIndex];

        if (rule.type !== window.CSSRule.STYLE_RULE && rule.type !== window.CSSRule.MEDIA_RULE) {
          continue;
        }

        if (!relevantSection && !!rule.cssText.match(new RegExp("#start-".concat(marker)))) {
          relevantSection = true;
        }

        if (relevantSection && !!rule.cssText.match(new RegExp("#end-".concat(marker)))) {
          relevantSection = false;
        }

        if (!relevantSection || !isReplaceableMediaRule(rule)) {
          continue;
        }

        var ruleText = rule.cssText;

        if (!originalStyles[styleSheetIndex]) {
          originalStyles[styleSheetIndex] = [];
        }

        originalStyles[styleSheetIndex][ruleIndex] = ruleText;
        replaceRule(styleSheet, replaceMediaQueryWithWidthEvaluation(ruleText, width), ruleIndex);
      }
    });
    return function () {
      originalStyles.forEach(function (rulesCollection, styleSheetIndex) {
        if (!rulesCollection) {
          return;
        }

        for (var ruleIndex = 0; ruleIndex < rulesCollection.length; ++ruleIndex) {
          var originalRuleText = rulesCollection[ruleIndex];

          if (originalRuleText) {
            replaceRule(styleSheets[styleSheetIndex], originalRuleText, ruleIndex);
          }
        }
      });
    };
  }, [width]);
}
//# sourceMappingURL=index.js.map