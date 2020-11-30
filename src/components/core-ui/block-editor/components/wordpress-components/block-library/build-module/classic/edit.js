import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _classCallCheck from "@babel/runtime/helpers/esm/classCallCheck";
import _createClass from "@babel/runtime/helpers/esm/createClass";
import _assertThisInitialized from "@babel/runtime/helpers/esm/assertThisInitialized";
import _inherits from "@babel/runtime/helpers/esm/inherits";
import _possibleConstructorReturn from "@babel/runtime/helpers/esm/possibleConstructorReturn";
import _getPrototypeOf from "@babel/runtime/helpers/esm/getPrototypeOf";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

/**
 * External dependencies
 */
import { debounce } from 'lodash';
/**
 * WordPress dependencies
 */

import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BACKSPACE, DELETE, F10, isKeyboardEvent } from '@wordpress/keycodes';
/**
 * Internal dependencies
 */

import ConvertToBlocksButton from './convert-to-blocks-button';
var _window = window,
    wp = _window.wp;

function isTmceEmpty(editor) {
  // When tinyMce is empty the content seems to be:
  // <p><br data-mce-bogus="1"></p>
  // avoid expensive checks for large documents
  var body = editor.getBody();

  if (body.childNodes.length > 1) {
    return false;
  } else if (body.childNodes.length === 0) {
    return true;
  }

  if (body.childNodes[0].childNodes.length > 1) {
    return false;
  }

  return /^\n?$/.test(body.innerText || body.textContent);
}

var ClassicEdit = /*#__PURE__*/function (_Component) {
  _inherits(ClassicEdit, _Component);

  var _super = _createSuper(ClassicEdit);

  function ClassicEdit(props) {
    var _this;

    _classCallCheck(this, ClassicEdit);

    _this = _super.call(this, props);
    _this.initialize = _this.initialize.bind(_assertThisInitialized(_this));
    _this.onSetup = _this.onSetup.bind(_assertThisInitialized(_this));
    _this.focus = _this.focus.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(ClassicEdit, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      var _window$wpEditorL10n$ = window.wpEditorL10n.tinymce,
          baseURL = _window$wpEditorL10n$.baseURL,
          suffix = _window$wpEditorL10n$.suffix;
      window.tinymce.EditorManager.overrideDefaults({
        base_url: baseURL,
        suffix: suffix
      });

      if (document.readyState === 'complete') {
        this.initialize();
      } else {
        document.addEventListener('readystatechange', function () {
          if (document.readyState === 'complete') {
            _this2.initialize();
          }
        });
      }
    }
  }, {
    key: "componentWillUnmount",
    value: function componentWillUnmount() {
      window.addEventListener('DOMContentLoaded', this.initialize);
      wp.oldEditor.remove("editor-".concat(this.props.clientId));
    }
  }, {
    key: "componentDidUpdate",
    value: function componentDidUpdate(prevProps) {
      var _this$props = this.props,
          clientId = _this$props.clientId,
          content = _this$props.attributes.content;
      var editor = window.tinymce.get("editor-".concat(clientId));
      var currentContent = editor === null || editor === void 0 ? void 0 : editor.getContent();

      if (prevProps.attributes.content !== content && currentContent !== content) {
        editor.setContent(content || '');
      }
    }
  }, {
    key: "initialize",
    value: function initialize() {
      var clientId = this.props.clientId;
      var settings = window.wpEditorL10n.tinymce.settings;
      wp.oldEditor.initialize("editor-".concat(clientId), {
        tinymce: _objectSpread(_objectSpread({}, settings), {}, {
          inline: true,
          content_css: false,
          fixed_toolbar_container: "#toolbar-".concat(clientId),
          setup: this.onSetup
        })
      });
    }
  }, {
    key: "onSetup",
    value: function onSetup(editor) {
      var _this3 = this;

      var _this$props2 = this.props,
          content = _this$props2.attributes.content,
          setAttributes = _this$props2.setAttributes;
      var bookmark;
      this.editor = editor;

      if (content) {
        editor.on('loadContent', function () {
          return editor.setContent(content);
        });
      }

      editor.on('blur', function () {
        bookmark = editor.selection.getBookmark(2, true); // There is an issue with Chrome and the editor.focus call in core at https://core.trac.wordpress.org/browser/trunk/src/js/_enqueues/lib/link.js#L451.
        // This causes a scroll to the top of editor content on return from some content updating dialogs so tracking
        // scroll position until this is fixed in core.

        var scrollContainer = document.querySelector('.interface-interface-skeleton__content');
        var scrollPosition = scrollContainer.scrollTop;
        setAttributes({
          content: editor.getContent()
        });
        editor.once('focus', function () {
          if (bookmark) {
            editor.selection.moveToBookmark(bookmark);

            if (scrollContainer.scrollTop !== scrollPosition) {
              scrollContainer.scrollTop = scrollPosition;
            }
          }
        });
        return false;
      });
      editor.on('mousedown touchstart', function () {
        bookmark = null;
      });
      var debouncedOnChange = debounce(function () {
        var value = editor.getContent();

        if (value !== editor._lastChange) {
          editor._lastChange = value;
          setAttributes({
            content: value
          });
        }
      }, 250);
      editor.on('Paste Change input Undo Redo', debouncedOnChange); // We need to cancel the debounce call because when we remove
      // the editor (onUnmount) this callback is executed in
      // another tick. This results in setting the content to empty.

      editor.on('remove', debouncedOnChange.cancel);
      editor.on('keydown', function (event) {
        if (isKeyboardEvent.primary(event, 'z')) {
          // Prevent the gutenberg undo kicking in so TinyMCE undo stack works as expected
          event.stopPropagation();
        }

        if ((event.keyCode === BACKSPACE || event.keyCode === DELETE) && isTmceEmpty(editor)) {
          // delete the block
          _this3.props.onReplace([]);

          event.preventDefault();
          event.stopImmediatePropagation();
        }

        var altKey = event.altKey;
        /*
         * Prevent Mousetrap from kicking in: TinyMCE already uses its own
         * `alt+f10` shortcut to focus its toolbar.
         */

        if (altKey && event.keyCode === F10) {
          event.stopPropagation();
        }
      });
      editor.on('init', function () {
        var rootNode = _this3.editor.getBody(); // Create the toolbar by refocussing the editor.


        if (rootNode.ownerDocument.activeElement === rootNode) {
          rootNode.blur();

          _this3.editor.focus();
        }
      });
    }
  }, {
    key: "focus",
    value: function focus() {
      if (this.editor) {
        this.editor.focus();
      }
    }
  }, {
    key: "onToolbarKeyDown",
    value: function onToolbarKeyDown(event) {
      // Prevent WritingFlow from kicking in and allow arrows navigation on the toolbar.
      event.stopPropagation(); // Prevent Mousetrap from moving focus to the top toolbar when pressing `alt+f10` on this block toolbar.

      event.nativeEvent.stopImmediatePropagation();
    }
  }, {
    key: "render",
    value: function render() {
      var clientId = this.props.clientId; // Disable reasons:
      //
      // jsx-a11y/no-static-element-interactions
      //  - the toolbar itself is non-interactive, but must capture events
      //    from the KeyboardShortcuts component to stop their propagation.

      /* eslint-disable jsx-a11y/no-static-element-interactions */

      return createElement(Fragment, null, createElement(BlockControls, null, createElement(ToolbarGroup, null, createElement(ConvertToBlocksButton, {
        clientId: clientId
      }))), createElement("div", {
        key: "toolbar",
        id: "toolbar-".concat(clientId),
        className: "block-library-classic__toolbar",
        onClick: this.focus,
        "data-placeholder": __('Classic'),
        onKeyDown: this.onToolbarKeyDown
      }), createElement("div", {
        key: "editor",
        id: "editor-".concat(clientId),
        className: "wp-block-freeform block-library-rich-text__tinymce"
      }));
      /* eslint-enable jsx-a11y/no-static-element-interactions */
    }
  }]);

  return ClassicEdit;
}(Component);

export { ClassicEdit as default };
//# sourceMappingURL=edit.js.map