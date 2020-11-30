import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { find, isEmpty, each, map } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { useRef, useState, useCallback } from '@wordpress/element';
import { ToolbarButton, Button, NavigableMenu, MenuItem, ToggleControl, TextControl, SVG, Path } from '@wordpress/components';
import { LEFT, RIGHT, UP, DOWN, BACKSPACE, ENTER } from '@wordpress/keycodes';
import { link as linkIcon, close } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import URLPopover from './index';
var LINK_DESTINATION_NONE = 'none';
var LINK_DESTINATION_CUSTOM = 'custom';
var LINK_DESTINATION_MEDIA = 'media';
var LINK_DESTINATION_ATTACHMENT = 'attachment';
var NEW_TAB_REL = ['noreferrer', 'noopener'];
var icon = createElement(SVG, {
  viewBox: "0 0 24 24",
  xmlns: "http://www.w3.org/2000/svg"
}, createElement(Path, {
  d: "M0,0h24v24H0V0z",
  fill: "none"
}), createElement(Path, {
  d: "m19 5v14h-14v-14h14m0-2h-14c-1.1 0-2 0.9-2 2v14c0 1.1 0.9 2 2 2h14c1.1 0 2-0.9 2-2v-14c0-1.1-0.9-2-2-2z"
}), createElement(Path, {
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

  var _useState = useState(false),
      _useState2 = _slicedToArray(_useState, 2),
      isOpen = _useState2[0],
      setIsOpen = _useState2[1];

  var openLinkUI = useCallback(function () {
    setIsOpen(true);
  });

  var _useState3 = useState(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isEditingLink = _useState4[0],
      setIsEditingLink = _useState4[1];

  var _useState5 = useState(null),
      _useState6 = _slicedToArray(_useState5, 2),
      urlInput = _useState6[0],
      setUrlInput = _useState6[1];

  var autocompleteRef = useRef(null);

  var stopPropagation = function stopPropagation(event) {
    event.stopPropagation();
  };

  var stopPropagationRelevantKeys = function stopPropagationRelevantKeys(event) {
    if ([LEFT, DOWN, RIGHT, UP, BACKSPACE, ENTER].indexOf(event.keyCode) > -1) {
      // Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
      event.stopPropagation();
    }
  };

  var startEditLink = useCallback(function () {
    if (linkDestination === LINK_DESTINATION_MEDIA || linkDestination === LINK_DESTINATION_ATTACHMENT) {
      setUrlInput('');
    }

    setIsEditingLink(true);
  });
  var stopEditLink = useCallback(function () {
    setIsEditingLink(false);
  });
  var closeLinkUI = useCallback(function () {
    setUrlInput(null);
    stopEditLink();
    setIsOpen(false);
  });

  var removeNewTabRel = function removeNewTabRel(currentRel) {
    var newRel = currentRel;

    if (currentRel !== undefined && !isEmpty(newRel)) {
      if (!isEmpty(newRel)) {
        each(NEW_TAB_REL, function (relVal) {
          var regExp = new RegExp('\\b' + relVal + '\\b', 'gi');
          newRel = newRel.replace(regExp, '');
        }); // Only trim if NEW_TAB_REL values was replaced.

        if (newRel !== currentRel) {
          newRel = newRel.trim();
        }

        if (isEmpty(newRel)) {
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

  var onFocusOutside = useCallback(function () {
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
  var onSubmitLinkChange = useCallback(function () {
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
  var onLinkRemove = useCallback(function () {
    onChangeUrl({
      linkDestination: LINK_DESTINATION_NONE,
      href: ''
    });
  });

  var getLinkDestinations = function getLinkDestinations() {
    return [{
      linkDestination: LINK_DESTINATION_MEDIA,
      title: __('Media File'),
      url: mediaType === 'image' ? mediaUrl : undefined,
      icon: icon
    }, {
      linkDestination: LINK_DESTINATION_ATTACHMENT,
      title: __('Attachment Page'),
      url: mediaType === 'image' ? mediaLink : undefined,
      icon: createElement(SVG, {
        viewBox: "0 0 24 24",
        xmlns: "http://www.w3.org/2000/svg"
      }, createElement(Path, {
        d: "M0 0h24v24H0V0z",
        fill: "none"
      }), createElement(Path, {
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
      linkDestinationInput = (find(linkDestinations, function (destination) {
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

  var advancedOptions = createElement(Fragment, null, createElement(ToggleControl, {
    label: __('Open in new tab'),
    onChange: onSetNewTab,
    checked: linkTarget === '_blank'
  }), createElement(TextControl, {
    label: __('Link Rel'),
    value: removeNewTabRel(rel) || '',
    onChange: onSetLinkRel,
    onKeyPress: stopPropagation,
    onKeyDown: stopPropagationRelevantKeys
  }), createElement(TextControl, {
    label: __('Link CSS Class'),
    value: linkClass || '',
    onKeyPress: stopPropagation,
    onKeyDown: stopPropagationRelevantKeys,
    onChange: onSetLinkClass
  }));
  var linkEditorValue = urlInput !== null ? urlInput : url;
  var urlLabel = (find(getLinkDestinations(), ['linkDestination', linkDestination]) || {}).title;
  return createElement(Fragment, null, createElement(ToolbarButton, {
    icon: linkIcon,
    className: "components-toolbar__control",
    label: url ? __('Edit link') : __('Insert link'),
    "aria-expanded": isOpen,
    onClick: openLinkUI
  }), isOpen && createElement(URLPopover, {
    onFocusOutside: onFocusOutside(),
    onClose: closeLinkUI,
    renderSettings: function renderSettings() {
      return advancedOptions;
    },
    additionalControls: !linkEditorValue && createElement(NavigableMenu, null, map(getLinkDestinations(), function (link) {
      return createElement(MenuItem, {
        key: link.linkDestination,
        icon: link.icon,
        onClick: function onClick() {
          setUrlInput(null);
          onSetHref(link.url);
          stopEditLink();
        }
      }, link.title);
    }))
  }, (!url || isEditingLink) && createElement(URLPopover.LinkEditor, {
    className: "block-editor-format-toolbar__link-container-content",
    value: linkEditorValue,
    onChangeInputValue: setUrlInput,
    onKeyDown: stopPropagationRelevantKeys,
    onKeyPress: stopPropagation,
    onSubmit: onSubmitLinkChange(),
    autocompleteRef: autocompleteRef
  }), url && !isEditingLink && createElement(Fragment, null, createElement(URLPopover.LinkViewer, {
    className: "block-editor-format-toolbar__link-container-content",
    onKeyPress: stopPropagation,
    url: url,
    onEditLinkClick: startEditLink,
    urlLabel: urlLabel
  }), createElement(Button, {
    icon: close,
    label: __('Remove link'),
    onClick: onLinkRemove
  }))));
};

export { ImageURLInputUI as __experimentalImageURLInputUI };
//# sourceMappingURL=image-url-input-ui.js.map