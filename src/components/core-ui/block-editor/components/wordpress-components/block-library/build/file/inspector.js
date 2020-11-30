"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = FileBlockInspector;

var _element = require("@wordpress/element");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _blockEditor = require("@wordpress/block-editor");

/**
 * WordPress dependencies
 */
function FileBlockInspector(_ref) {
  var hrefs = _ref.hrefs,
      openInNewWindow = _ref.openInNewWindow,
      showDownloadButton = _ref.showDownloadButton,
      changeLinkDestinationOption = _ref.changeLinkDestinationOption,
      changeOpenInNewWindow = _ref.changeOpenInNewWindow,
      changeShowDownloadButton = _ref.changeShowDownloadButton;
  var href = hrefs.href,
      textLinkHref = hrefs.textLinkHref,
      attachmentPage = hrefs.attachmentPage;
  var linkDestinationOptions = [{
    value: href,
    label: (0, _i18n.__)('URL')
  }];

  if (attachmentPage) {
    linkDestinationOptions = [{
      value: href,
      label: (0, _i18n.__)('Media file')
    }, {
      value: attachmentPage,
      label: (0, _i18n.__)('Attachment page')
    }];
  }

  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.InspectorControls, null, (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Text link settings')
  }, (0, _element.createElement)(_components.SelectControl, {
    label: (0, _i18n.__)('Link to'),
    value: textLinkHref,
    options: linkDestinationOptions,
    onChange: changeLinkDestinationOption
  }), (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Open in new tab'),
    checked: openInNewWindow,
    onChange: changeOpenInNewWindow
  })), (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Download button settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    label: (0, _i18n.__)('Show download button'),
    checked: showDownloadButton,
    onChange: changeShowDownloadButton
  }))));
}
//# sourceMappingURL=inspector.js.map