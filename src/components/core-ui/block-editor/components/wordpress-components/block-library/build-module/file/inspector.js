import { createElement, Fragment } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
export default function FileBlockInspector(_ref) {
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
    label: __('URL')
  }];

  if (attachmentPage) {
    linkDestinationOptions = [{
      value: href,
      label: __('Media file')
    }, {
      value: attachmentPage,
      label: __('Attachment page')
    }];
  }

  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Text link settings')
  }, createElement(SelectControl, {
    label: __('Link to'),
    value: textLinkHref,
    options: linkDestinationOptions,
    onChange: changeLinkDestinationOption
  }), createElement(ToggleControl, {
    label: __('Open in new tab'),
    checked: openInNewWindow,
    onChange: changeOpenInNewWindow
  })), createElement(PanelBody, {
    title: __('Download button settings')
  }, createElement(ToggleControl, {
    label: __('Show download button'),
    checked: showDownloadButton,
    onChange: changeShowDownloadButton
  }))));
}
//# sourceMappingURL=inspector.js.map