import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { forEach } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { AlignmentToolbar, BlockControls, InspectorControls, RichText, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

function PostAuthorEdit(_ref) {
  var isSelected = _ref.isSelected,
      context = _ref.context,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes;
  var postType = context.postType,
      postId = context.postId;

  var _useSelect = useSelect(function (select) {
    var _getEditedEntityRecor;

    var _select = select('core'),
        getEditedEntityRecord = _select.getEditedEntityRecord,
        getUser = _select.getUser,
        getUsers = _select.getUsers;

    var _authorId = (_getEditedEntityRecor = getEditedEntityRecord('postType', postType, postId)) === null || _getEditedEntityRecor === void 0 ? void 0 : _getEditedEntityRecor.author;

    return {
      authorId: _authorId,
      authorDetails: _authorId ? getUser(_authorId) : null,
      authors: getUsers({
        who: 'authors'
      })
    };
  }, [postType, postId]),
      authorId = _useSelect.authorId,
      authorDetails = _useSelect.authorDetails,
      authors = _useSelect.authors;

  var _useDispatch = useDispatch('core'),
      editEntityRecord = _useDispatch.editEntityRecord;

  var textAlign = attributes.textAlign,
      showAvatar = attributes.showAvatar,
      showBio = attributes.showBio,
      byline = attributes.byline;
  var avatarSizes = [];

  if (authorDetails) {
    forEach(authorDetails.avatar_urls, function (url, size) {
      avatarSizes.push({
        value: size,
        label: "".concat(size, " x ").concat(size)
      });
    });
  }

  var blockWrapperProps = useBlockWrapperProps({
    className: classnames(_defineProperty({}, "has-text-align-".concat(textAlign), textAlign))
  });
  return createElement(Fragment, null, createElement(InspectorControls, null, createElement(PanelBody, {
    title: __('Author Settings')
  }, createElement(SelectControl, {
    label: __('Author'),
    value: authorId,
    options: authors.map(function (_ref2) {
      var id = _ref2.id,
          name = _ref2.name;
      return {
        value: id,
        label: name
      };
    }),
    onChange: function onChange(nextAuthorId) {
      editEntityRecord('postType', postType, postId, {
        author: nextAuthorId
      });
    }
  }), createElement(ToggleControl, {
    label: __('Show avatar'),
    checked: showAvatar,
    onChange: function onChange() {
      return setAttributes({
        showAvatar: !showAvatar
      });
    }
  }), showAvatar && createElement(SelectControl, {
    label: __('Avatar size'),
    value: attributes.avatarSize,
    options: avatarSizes,
    onChange: function onChange(size) {
      setAttributes({
        avatarSize: Number(size)
      });
    }
  }), createElement(ToggleControl, {
    label: __('Show bio'),
    checked: showBio,
    onChange: function onChange() {
      return setAttributes({
        showBio: !showBio
      });
    }
  }))), createElement(BlockControls, null, createElement(AlignmentToolbar, {
    value: textAlign,
    onChange: function onChange(nextAlign) {
      setAttributes({
        textAlign: nextAlign
      });
    }
  })), createElement("div", blockWrapperProps, showAvatar && authorDetails && createElement("div", {
    className: "wp-block-post-author__avatar"
  }, createElement("img", {
    width: attributes.avatarSize,
    src: authorDetails.avatar_urls[attributes.avatarSize],
    alt: authorDetails.name
  })), createElement("div", {
    className: "wp-block-post-author__content"
  }, (!RichText.isEmpty(byline) || isSelected) && createElement(RichText, {
    className: "wp-block-post-author__byline",
    multiline: false,
    placeholder: __('Write byline …'),
    value: byline,
    onChange: function onChange(value) {
      return setAttributes({
        byline: value
      });
    }
  }), createElement("p", {
    className: "wp-block-post-author__name"
  }, (authorDetails === null || authorDetails === void 0 ? void 0 : authorDetails.name) || __('Post Author')), showBio && createElement("p", {
    className: "wp-block-post-author__bio"
  }, authorDetails === null || authorDetails === void 0 ? void 0 : authorDetails.description))));
}

export default PostAuthorEdit;
//# sourceMappingURL=edit.js.map