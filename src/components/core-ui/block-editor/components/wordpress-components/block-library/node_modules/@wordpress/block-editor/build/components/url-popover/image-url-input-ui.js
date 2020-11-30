"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.__experimentalImageURLInputUI = void 0;

var _element = require("@wordpress/element");

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _keycodes = require("@wordpress/keycodes");

var _icons = require("@wordpress/icons");

var _index = _interopRequireDefault(require("./index"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var LINK_DESTINATION_NONE = 'none';
var LINK_DESTINATION_CUSTOM = 'custom';
var LINK_DESTINATION_MEDIA = 'media';
var LINK_DESTINATION_ATTACHMENT = 'attachment';
var NEW_TAB_REL = ['noreferrer', 'noopener'];
var icon = (0, _element.createElement)(_components.SVG, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg"
}, (0, _element.createElement)(_components.Path, {
  d: "M0,0h24v24H0V0z",
  fill: "none"
}), (0, _element.createElement)(_components.Path, {
  d: "m19 5v14h-14v-14h14m0-2h-14c-1.1 0-2 0.9-2 2v14c0 1.1 0.9 2 2 2h14c1.1 0 2-0.9 2-2v-14c0-1.1-0.9-2-2-2z"
}), (0, _element.createElement)(_components.Path, {
  d: "m14.14 11.86l-3 3.87-2.14-2.59-3 3.86h12l-3.86-5.14z"
}));

var ImageURLInputUI = function ImageURLInputUI(_ref) {
  var linkDestination = _ref.linkDestination,
      onChangeUrl = _ref.onChangeUrl,
      url = _ref.url,
      _ref$mediaType = _ref.mediaType,
      mediaType = _ref$mediaType === void 0 ? 'image' : _ref$mediaType,
      mediaUrl = _ref.mediaUrl,
      mediaLink = _ref.mediaLink,
      linkTarget = _ref.linkTarget,
      linkClass = _ref.linkClass,
      rel = _ref.rel;

  var _useState = (0, _element.useState)(false),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isOpen = _useState2[0],
      setIsOpen = _useState2[1];

  var openLinkUI = (0, _element.useCallback)(function () {
    setIsOpen(true);
  });

  var _useState3 = (0, _element.useState)(false),
      _useState4 = (0, _slicedToArray2.default)(_useState3, 2),
      isEditingLink = _useState4[0],
      setIsEditingLink = _useState4[1];

  var _useState5 = (0, _element.useState)(null),
      _useState6 = (0, _slicedToArray2.default)(_useState5, 2),
      urlInput = _useState6[0],
      setUrlInput = _useState6[1];

  var autocompleteRef = (0, _element.useRef)(null);

  var stopPropagation = function stopPropagation(event) {
    event.stopPropagation();
  };

  var stopPropagationRelevantKeys = function stopPropagationRelevantKeys(event) {
    if ([_keycodes.LEFT, _keycodes.DOWN, _keycodes.RIGHT, _keycodes.UP, _keycodes.BACKSPACE, _keycodes.ENTER].indexOf(event.keyCode) > -1) {
      // Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
      event.stopPropagation();
    }
  };

  var startEditLink = (0, _element.useCallback)(function () {
    if (linkDestination === LINK_DESTINATION_MEDIA || linkDestination === LINK_DESTINATION_ATTACHMENT) {
      setUrlInput('');
    }

    setIsEditingLink(true);
  });
  var stopEditLink = (0, _element.useCallback)(function () {
    setIsEditingLink(false);
  });
  var closeLinkUI = (0, _element.useCallback)(function () {
    setUrlInput(null);
    stopEditLink();
    setIsOpen(false);
  });

  var removeNewTabRel = function removeNewTabRel(currentRel) {
    var newRel = currentRel;

    if (currentRel !== undefined && !(0, _lodash.isEmpty)(newRel)) {
      if (!(0, _lodash.isEmpty)(newRel)) {
        (0, _lodash.each)(NEW_TAB_REL, function (relVal) {
          var regExp = new RegExp('\\b' + relVal + '\\b', 'gi');
          newRel = newRel.replace(regExp, '');
        }); // Only trim if NEW_TAB_REL values was replaced.

        if (newRel !== currentRel) {
          newRel = newRel.trim();
        }

        if ((0, _lodash.isEmpty)(newRel)) {
          newRel = undefined;
        }
      }
    }

    return newRel;
  };

  var getUpdatedLinkTargetSettings = function getUpdatedLinkTargetSettings(value) {
    var newLinkTarget = value ? '_blank' : undefined;
    var updatedRel;

    if (!newLinkTarget && !rel) {
      updatedRel = undefined;
    } else {
      updatedRel = removeNewTabRel(rel);
    }

    return {
      linkTarget: newLinkTarget,
      rel: updatedRel
    };
  };

  var onFocusOutside = (0, _element.useCallback)(function () {
    return function (event) {
      // The autocomplete suggestions list renders in a separate popover (in a portal),
      // so onFocusOutside fails to detect that a click on a suggestion occurred in the
      // LinkContainer. Detect clicks on autocomplete suggestions using a ref here, and
      // return to avoid the popover being closed.
      var autocompleteElement = autocompleteRef.current;

      if (autocompleteElement && autocompleteElement.contains(event.target)) {
        return;
      }

      setIsOpen(false);
      setUrlInput(null);
      stopEditLink();
    };
  });
  var onSubmitLinkChange = (0, _element.useCallback)(function () {
    return function (event) {
      if (urlInput) {
        onChangeUrl({
          href: urlInput
        });
      }

      stopEditLink();
      setUrlInput(null);
      event.preventDefault();
    };
  });
  var onLinkRemove = (0, _element.useCallback)(function () {
    onChangeUrl({
      linkDestination: LINK_DESTINATION_NONE,
      href: ''
    });
  });

  var getLinkDestinations = function getLinkDestinations() {
    return [{
      linkDestination: LINK_DESTINATION_MEDIA,
      title: (0, _i18n.__)('Media File'),
      url: mediaType === 'image' ? mediaUrl : undefined,
      icon: icon
    }, {
      linkDestination: LINK_DESTINATION_ATTACHMENT,
      title: (0, _i18n.__)('Attachment Page'),
      url: mediaType === 'image' ? mediaLink : undefined,
      icon: (0, _element.createElement)(_components.SVG, {
        viewBox: "0 0 24 24",
        xmlns: "http://www.w3.org/2000/svg"
      }, (0, _element.createElement)(_components.Path, {
        d: "M0 0h24v24H0V0z",
        fill: "none"
      }), (0, _element.createElement)(_components.Path, {
        d: "M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"
      }))
    }];
  };

  var onSetHref = function onSetHref(value) {
    var linkDestinations = getLinkDestinations();
    var linkDestinationInput;

    if (!value) {
      linkDestinationInput = LINK_DESTINATION_NONE;
    } else {
      linkDestinationInput = ((0, _lodash.find)(linkDestinations, function (destination) {
        return destination.url === value;
      }) || {
        linkDestination: LINK_DESTINATION_CUSTOM
      }).linkDestination;
    }

    onChangeUrl({
      linkDestination: linkDestinationInput,
      href: value
    });
  };

  var onSetNewTab = function onSetNewTab(value) {
    var updatedLinkTarget = getUpdatedLinkTargetSettings(value);
    onChangeUrl(updatedLinkTarget);
  };

  var onSetLinkRel = function onSetLinkRel(value) {
    onChangeUrl({
      rel: value
    });
  };

  var onSetLinkClass = function onSetLinkClass(value) {
    onChangeUrl({
      linkClass: value
    });
  };

  var advancedOptions = (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Open in new tab'),
    onChange: onSetNewTab,
    checked: linkTarget === '_blank'
  }), (0, _element.createElement)(_components.TextControl, {
    label: (0, _i18n.__)('Link Rel'),
    value: removeNewTabRel(rel) || '',
    onChange: onSetLinkRel,
    onKeyPress: stopPropagation,
    onKeyDown: stopPropagationRelevantKeys
  }), (0, _element.createElement)(_components.TextControl, {
    label: (0, _i18n.__)('Link CSS Class'),
    value: linkClass || '',
    onKeyPress: stopPropagation,
    onKeyDown: stopPropagationRelevantKeys,
    onChange: onSetLinkClass
  }));
  var linkEditorValue = urlInput !== null ? urlInput : url;
  var urlLabel = ((0, _lodash.find)(getLinkDestinations(), ['linkDestination', linkDestination]) || {}).title;
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.ToolbarButton, {
    icon: _icons.link,
    className: "components-toolbar__control",
    label: url ? (0, _i18n.__)('Edit link') : (0, _i18n.__)('Insert link'),
    "aria-expanded": isOpen,
    onClick: openLinkUI
  }), isOpen && (0, _element.createElement)(_index.default, {
    onFocusOutside: onFocusOutside(),
    onClose: closeLinkUI,
    renderSettings: function renderSettings() {
      return advancedOptions;
    },
    additionalControls: !linkEditorValue && (0, _element.createElement)(_components.NavigableMenu, null, (0, _lodash.map)(getLinkDestinations(), function (link) {
      return (0, _element.createElement)(_components.MenuItem, {
        key: link.linkDestination,
        icon: link.icon,
        onClick: function onClick() {
          setUrlInput(null);
          onSetHref(link.url);
          stopEditLink();
        }
      }, link.title);
    }))
  }, (!url || isEditingLink) && (0, _element.createElement)(_index.default.LinkEditor, {
    className: "block-editor-format-toolbar__link-container-content",
    value: linkEditorValue,
    onChangeInputValue: setUrlInput,
    onKeyDown: stopPropagationRelevantKeys,
    onKeyPress: stopPropagation,
    onSubmit: onSubmitLinkChange(),
    autocompleteRef: autocompleteRef
  }), url && !isEditingLink && (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_index.default.LinkViewer, {
    className: "block-editor-format-toolbar__link-container-content",
    onKeyPress: stopPropagation,
    url: url,
    onEditLinkClick: startEditLink,
    urlLabel: urlLabel
  }), (0, _element.createElement)(_components.Button, {
    icon: _icons.close,
    label: (0, _i18n.__)('Remove link'),
    onClick: onLinkRemove
  }))));
};

exports.__experimentalImageURLInputUI = ImageURLInputUI;
//# sourceMappingURL=image-url-input-ui.js.map