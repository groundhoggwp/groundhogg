(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
        typeof define === 'function' && define.amd ? define(['exports'], factory) :
            (factory((global.simpleeditor = {})));
}(this, (function (exports) {
    'use strict';
    var $ = window.jQuery || window.$;

    var colorPickerHtml = '   <table class="simple-editor-color-grid" role="list" cellspacing="0">  ' +
        '       <tbody>  ' +
        '       <tr>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#000000" role="option" tabindex="-1" style="background-color: #000000" title="Black"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#993300" role="option" tabindex="-1" style="background-color: #993300" title="Burnt orange"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#333300" role="option" tabindex="-1" style="background-color: #333300" title="Dark olive"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#003300" role="option" tabindex="-1" style="background-color: #003300" title="Dark green"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#003366" role="option" tabindex="-1" style="background-color: #003366" title="Dark azure"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#000080" role="option" tabindex="-1" style="background-color: #000080" title="Navy Blue"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#333399" role="option" tabindex="-1" style="background-color: #333399" title="Indigo"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#333333" role="option" tabindex="-1" style="background-color: #333333" title="Very dark gray"></div>  ' +
        '           </td>  ' +
        '       </tr>  ' +
        '       <tr>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#800000" role="option" tabindex="-1" style="background-color: #800000" title="Maroon"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FF6600" role="option" tabindex="-1" style="background-color: #FF6600" title="Orange"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#808000" role="option" tabindex="-1" style="background-color: #808000" title="Olive"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#008000" role="option" tabindex="-1" style="background-color: #008000" title="Green"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#008080" role="option" tabindex="-1" style="background-color: #008080" title="Teal"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#0000FF" role="option" tabindex="-1" style="background-color: #0000FF" title="Blue"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#666699" role="option" tabindex="-1" style="background-color: #666699" title="Grayish blue"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#808080" role="option" tabindex="-1" style="background-color: #808080" title="Gray"></div>  ' +
        '           </td>  ' +
        '       </tr>  ' +
        '       <tr>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FF0000" role="option" tabindex="-1" style="background-color: #FF0000" title="Red"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FF9900" role="option" tabindex="-1" style="background-color: #FF9900" title="Amber"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#99CC00" role="option" tabindex="-1" style="background-color: #99CC00" title="Yellow green"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#339966" role="option" tabindex="-1" style="background-color: #339966" title="Sea green"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#33CCCC" role="option" tabindex="-1" style="background-color: #33CCCC" title="Turquoise"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#3366FF" role="option" tabindex="-1" style="background-color: #3366FF" title="Royal blue"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#800080" role="option" tabindex="-1" style="background-color: #800080" title="Purple"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#999999" role="option" tabindex="-1" style="background-color: #999999" title="Medium gray"></div>  ' +
        '           </td>  ' +
        '       </tr>  ' +
        '       <tr>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FF00FF" role="option" tabindex="-1" style="background-color: #FF00FF" title="Magenta"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FFCC00" role="option" tabindex="-1" style="background-color: #FFCC00" title="Gold"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FFFF00" role="option" tabindex="-1" style="background-color: #FFFF00" title="Yellow"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#00FF00" role="option" tabindex="-1" style="background-color: #00FF00" title="Lime"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#00FFFF" role="option" tabindex="-1" style="background-color: #00FFFF" title="Aqua"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#00CCFF" role="option" tabindex="-1" style="background-color: #00CCFF" title="Sky blue"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#993366" role="option" tabindex="-1" style="background-color: #993366" title="Red violet"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FFFFFF" role="option" tabindex="-1" style="background-color: #FFFFFF" title="White"></div>  ' +
        '           </td>  ' +
        '       </tr>  ' +
        '       <tr>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FF99CC" role="option" tabindex="-1" style="background-color: #FF99CC" title="Pink"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FFCC99" role="option" tabindex="-1" style="background-color: #FFCC99" title="Peach"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#FFFF99" role="option" tabindex="-1" style="background-color: #FFFF99" title="Light yellow"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#CCFFCC" role="option" tabindex="-1" style="background-color: #CCFFCC" title="Pale green"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#CCFFFF" role="option" tabindex="-1" style="background-color: #CCFFFF" title="Pale cyan"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#99CCFF" role="option" tabindex="-1" style="background-color: #99CCFF" title="Light sky blue"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div data-color="#CC99FF" role="option" tabindex="-1" style="background-color: #CC99FF" title="Plum"></div>  ' +
        '           </td>  ' +
        '           <td class="simple-editor-grid-cell">  ' +
        '               <div style="text-align: center;" data-color="transparent" role="option" tabindex="-1" style="background-color: transparent" title="No color">X</div>  ' +
        '           </td>  ' +
        '       </tr>  ' +
        '       </tbody>  ' +
        '  </table>  ';

    var _extends = Object.assign || function (target) {
        for (var i = 1; i < arguments.length; i++) {
            var source = arguments[i];
            for (var key in source) {
                if (Object.prototype.hasOwnProperty.call(source, key)) {
                    target[key] = source[key];
                }
            }
        }
        return target;
    };

    var defaultParagraphSeparatorString = 'defaultParagraphSeparator';
    var formatBlock = 'formatBlock';
    var addEventListener = function addEventListener(parent, type, listener) {
        $(parent).on(type, listener);
        return function () {
            $(parent).off(type, listener);
        };
    };
    var appendChild = function appendChild(parent, child) {
        return $(parent).prepend(child);
    };
    var createElement = function createElement(tag) {
        return document.createElement(tag);
    };
    var queryCommandState = function queryCommandState(command) {
        return document.queryCommandState(command);
    };
    var queryCommandValue = function queryCommandValue(command) {
        return document.queryCommandValue(command);
    };

    var exec = function exec(command) {
        var value = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
        return document.execCommand(command, false, value);
    };

    function placeCaretAfterNode(node) {
        if (typeof window.getSelection !== "undefined") {
            var range = document.createRange();
            range.setStartAfter(node);
            range.collapse(true);
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
    }

    var saveSelection, restoreSelection;

    if (window.getSelection && document.createRange) {
        saveSelection = function(containerEl) {
            var doc = containerEl.ownerDocument, win = doc.defaultView;
            var range = win.getSelection().getRangeAt(0);
            var preSelectionRange = range.cloneRange();
            preSelectionRange.selectNodeContents(containerEl);
            preSelectionRange.setEnd(range.startContainer, range.startOffset);
            var start = preSelectionRange.toString().length;

            return {
                start: start,
                end: start + range.toString().length
            };
        };

        restoreSelection = function(containerEl, savedSel) {
            var doc = containerEl.ownerDocument, win = doc.defaultView;
            var charIndex = 0, range = doc.createRange();
            range.setStart(containerEl, 0);
            range.collapse(true);
            var nodeStack = [containerEl], node, foundStart = false, stop = false;

            while (!stop && (node = nodeStack.pop())) {
                if (node.nodeType == 3) {
                    var nextCharIndex = charIndex + node.length;
                    if (!foundStart && savedSel.start >= charIndex && savedSel.start <= nextCharIndex) {
                        range.setStart(node, savedSel.start - charIndex);
                        foundStart = true;
                    }
                    if (foundStart && savedSel.end >= charIndex && savedSel.end <= nextCharIndex) {
                        range.setEnd(node, savedSel.end - charIndex);
                        stop = true;
                    }
                    charIndex = nextCharIndex;
                } else {
                    var i = node.childNodes.length;
                    while (i--) {
                        nodeStack.push(node.childNodes[i]);
                    }
                }
            }

            var sel = win.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        };
    } else if (document.selection) {
        saveSelection = function(containerEl) {
            var doc = containerEl.ownerDocument, win = doc.defaultView || doc.parentWindow;
            var selectedTextRange = doc.selection.createRange();
            var preSelectionTextRange = doc.body.createTextRange();
            preSelectionTextRange.moveToElementText(containerEl);
            preSelectionTextRange.setEndPoint("EndToStart", selectedTextRange);
            var start = preSelectionTextRange.text.length;

            return {
                start: start,
                end: start + selectedTextRange.text.length
            };
        };

        restoreSelection = function(containerEl, savedSel) {
            var doc = containerEl.ownerDocument, win = doc.defaultView || doc.parentWindow;
            var textRange = doc.body.createTextRange();
            textRange.moveToElementText(containerEl);
            textRange.collapse(true);
            textRange.moveEnd("character", savedSel.end);
            textRange.moveStart("character", savedSel.start);
            textRange.select();
        };
    }



    /**
     *
     * Get currently selected element
     * @return jQuery
     */
    var getSelectionContainerElement = function (focusNode) {
        var range, sel, container;
        if (document.selection && document.selection.createRange) {
            // IE case
            range = document.selection.createRange();
            return range.parentElement();
        } else if (window.getSelection) {
            sel = window.getSelection();
            if (sel.getRangeAt) {
                if (sel.rangeCount > 0) {
                    range = sel.getRangeAt(0);
                }
            } else {
                // Old WebKit selection object has no getRangeAt, so
                // create a range from other selection properties
                range = document.createRange();
                range.setStart(sel.anchorNode, sel.anchorOffset);
                range.setEnd(sel.focusNode, sel.focusOffset);

                // Handle the case when the selection was selected backwards (from the end to the start in the document)
                if (range.collapsed !== sel.isCollapsed) {
                    range.setStart(sel.focusNode, sel.focusOffset);
                    range.setEnd(sel.anchorNode, sel.anchorOffset);
                }
            }

            if (focusNode) {
                return $(sel.focusNode.nodeType === Node.TEXT_NODE ? sel.focusNode.parentNode : sel.focusNode);
            }

            if (range) {
                container = range.commonAncestorContainer;

                // Check if the container is a text node and return its parent if so
                return $(container.nodeType === 3 ? container.parentNode : container);
            }
            return $([]);
        }
    };


    /**
     * Initializes the editor
     * @param settings
     * @return function that destroys the editor
     */
    var init = function init(settings) {
        var defaultClasses = {
            actionbar: 'simple-editor-actionbar',
            button: 'simple-editor-button',
            content: 'simple-editor-content',
            selected: 'simple-editor-button-selected',
            wrapper: 'simple-editor-button-wrapper'
        };
        var classes = _extends({}, defaultClasses, settings.classes);
        var defaultParagraphSeparator = settings[defaultParagraphSeparatorString] || 'div';
        var stopHide = false;
        var content;
        var destroyCallbacks = [];
        var lastSelection = null;
        var $element = $(settings.element);


        var actionBar = createElement('div');
        actionBar.className = classes.actionbar;
        appendChild(settings.element, actionBar);
        appendChild(settings.element, colorPickerDiv);

        var existing;
        [].forEach.call(settings.element.children, function (item) {
            if (item.contentEditable) {
                existing = item;
            }
        });
        var addable = false;
        if (!existing) {
            addable = true;
            existing = createElement('div');

            destroyCallbacks.push(function () {
                $(existing).remove();
            });
        }

        content = settings.element.content = existing;
        content.contentEditable = true;
        content.className = classes.content;

        destroyCallbacks.push(function () {
            $(content).removeClass('simple-editor-content');
        });

        destroyCallbacks.push(addEventListener(content, 'input', function (event) {
            settings.onChange.call($element, content.innerHTML);
        }));

        destroyCallbacks.push(addEventListener(content, 'keyup', function (event) {
            var firstChild = event.target.firstChild;
            if (getSelectionContainerElement().parents('ul:first').length || getSelectionContainerElement().parents('ol:first').length) {
                return;
            }
            if (firstChild && firstChild.nodeType === 3) {
                if (event.shiftKey) {
                    exec(formatBlock, '<br />');
                } else {
                    if (!getSelectionContainerElement().is('p')) {
                        //exec(formatBlock, '<' + defaultParagraphSeparator + '>');
                    }
                }
            } else if (content.innerHTML === '<br>') {
                content.innerHTML = '';
            }
        }));

        destroyCallbacks.push(addEventListener(content, 'keydown', function (event) {
            if (event.key === 'Tab') {
                event.preventDefault();
            } else if (event.key === 'Enter' && queryCommandValue(formatBlock) === 'blockquote') {
                setTimeout(function () {
                    return exec(formatBlock, '<' + defaultParagraphSeparator + '>');
                }, 0);
            }
        }));

        if (addable) {
            appendChild(settings.element, content);
        }

        var colorPickerDiv = createElement('div');
        colorPickerDiv.className = 'simple-editor-font-color';
        colorPickerDiv.innerHTML = colorPickerHtml;

        var $colorPickerDiv = $(colorPickerDiv);
        $colorPickerDiv.hide();
        appendChild(settings.element, colorPickerDiv);

        var hideColorPickerDiv = function () {
            stopHide = false;
            $colorPickerDiv.hide();
        };

        $colorPickerDiv.on('click', '.simple-editor-grid-cell div', function () {

            restoreSelection(content, lastSelection);
            var color = $(this).data('color');
            exec('foreColor', color);
            if (color === 'transparent') {
                var el = getSelectionContainerElement(true);
                el.removeAttr('color');
                settings.onChange.call($element, content.innerHTML);
            }
            hideColorPickerDiv();
        });


        destroyCallbacks.push(addEventListener(document, 'click', function (ev) {
            if (ev.target) {
                if (stopHide) {
                    stopHide = false;
                    return;
                }
                var $target = $(ev.target);
                if ($target.is('.simple-editor-font-color') || $target.parents('.simple-editor-font-color:first').length) {
                    return;
                }
                hideColorPickerDiv();
            }
        }));


        destroyCallbacks.push(function () {
            $colorPickerDiv.remove();
        });


        $element.addClass('simple-editor');

        destroyCallbacks.push(function () {
            $element.removeClass('simple-editor');
        });


        /**
         * Unwraps the element by detaching it from parent node
         */
        var unwrap = function (el) {
            var parent = el.parentNode;
            while (el.firstChild) {
                parent.insertBefore(el.firstChild, el);
            }
            parent.removeChild(el);
            settings.onChange.call($element, content.innerHTML);
        };

        /**
         * Spawns ul or ol
         * @param ordered
         */
        var spawnList = function (ordered) {
            var current = getSelectionContainerElement();
            var state = exec(ordered ? 'insertOrderedList' : 'insertUnorderedList');
            var ul = current.find(ordered ? 'ol' : 'ul');
            var textNode = ul.first('li:first').contents().get(0);
            unwrap(ul.parents('p:first').get(0));
            placeCaretAfterNode(textNode);
            return state;
        };


        var defaultActions = {
            heading1: {
                icon: '<b>H<sub>1</sub></b>',
                title: 'Heading 1',
                state: function state() {
                    return getSelectionContainerElement().is('h1');
                },
                result: function result() {
                    var elm = getSelectionContainerElement();
                    if (getSelectionContainerElement().is('h1')) {
                        unwrap(elm[0]);
                        return true;
                    }
                    return exec(formatBlock, '<h1>');
                }
            },
            heading2: {
                icon: '<b>H<sub>2</sub></b>',
                title: 'Heading 2',
                state: function state() {
                    return getSelectionContainerElement().is('h2');
                },
                result: function result() {
                    var elm = getSelectionContainerElement();
                    if (getSelectionContainerElement().is('h2')) {
                        unwrap(elm[0]);
                        return true;
                    }
                    return exec(formatBlock, '<h2>');
                }
            },
            paragraph: {
                icon: '&#182;',
                title: 'Paragraph',
                state: function state() {
                    return getSelectionContainerElement().is('p');
                },
                result: function result() {
                    var elm = getSelectionContainerElement();
                    if (getSelectionContainerElement().is('p')) {
                        unwrap(elm[0]);
                        return true;
                    }
                    return exec(formatBlock, '<p>');
                }
            },
            bold: {
                icon: '<span class="dashicons dashicons-editor-bold"></span>',
                title: 'Bold',
                state: function state() {
                    return queryCommandState('bold');
                },
                result: function result() {
                    return exec('bold');
                }
            },
            italic: {
                icon: '<span class="dashicons dashicons-editor-italic"></span>',
                title: 'Italic',
                state: function state() {
                    return queryCommandState('italic');
                },
                result: function result() {
                    return exec('italic');
                }
            },
            underline: {
                icon: '<span class="dashicons dashicons-editor-underline"></span>',
                title: 'Underline',
                state: function state() {
                    return queryCommandState('underline');
                },
                result: function result() {
                    return exec('underline');
                }
            },
            color: {
                icon: '<span class="dashicons simple-editor-color-picker-handle dashicons-editor-textcolor"></span>',
                title: 'Color',
                state: function state() {
                    return queryCommandState('foreColor');
                },
                result: function result() {
                    stopHide = true;
                    lastSelection = saveSelection(content);
                    $colorPickerDiv.css('left', $element.find('.simple-editor-color-picker-handle').parents('.simple-editor-button-wrapper:first').position().left);
                    $colorPickerDiv.show();
                    return true;
                }
            },
            strikethrough: {
                icon: '<span class="dashicons dashicons-editor-strikethrough"></span>',
                title: 'Strike-through',
                state: function state() {
                    return queryCommandState('strikeThrough');
                },
                result: function result() {
                    return exec('strikeThrough');
                }
            },
            alignLeft: {
                icon: '<span class="dashicons dashicons-editor-alignleft"></span>',
                title: 'Left alignment',
                state: function state() {
                    return queryCommandState('justifyLeft');
                },
                result: function result() {
                    return exec('justifyLeft');
                }
            },
            alignCenter: {
                icon: '<span class="dashicons dashicons-editor-aligncenter"></span>',
                title: 'Center alignment',
                state: function state() {
                    return queryCommandState('justifyCenter');
                },
                result: function result() {
                    return exec('justifyCenter');
                }
            },
            alignRight: {
                icon: '<span class="dashicons dashicons-editor-alignright"></span>',
                title: 'Right alignment',
                state: function state() {
                    return queryCommandState('justifyRight');
                },
                result: function result() {
                    return exec('justifyRight');
                }
            },
            olist: {
                icon: '<span class="dashicons dashicons-editor-ol"></span>',
                title: 'Ordered List',
                state: function state() {
                    return queryCommandState('insertOrderedList');
                },
                result: function result() {
                    return spawnList(true);
                }
            },
            ulist: {
                icon: '<span class="dashicons dashicons-editor-ul"></span>',
                title: 'Unordered List',
                state: function state() {
                    return queryCommandState('insertUnorderedList');
                },
                result: function result() {
                    return spawnList(false);
                }
            },
            link: {
                icon: '<span class="dashicons link-manager dashicons-admin-links"></span>',
                title: 'Link',
                state: function state() {
                    return getSelectionContainerElement().is('a');
                },
                result: function result() {
                    var el = getSelectionContainerElement();
                    var state = el.is('a');
                    if (state) {
                        var newhref = prompt("Edit Link", el.attr('href'));
                        el.attr('href', newhref);
                        return true;
                    }
                    wpActiveEditor = true;
                    wpLink.open();
                    $('body').on('click', '#wp-link-submit', function(event) {
                        var linkAtts = wpLink.getAttrs();//the links attributes (href, target) are stored in an object, which can be access via  wpLink.getAttrs()
                        exec('createLink', linkAtts.href);
                        wpLink.textarea = $('.active'); //to close the link dialogue, it is again expecting an wp_editor instance, so you need to give it something to set focus back to. In this case, I'm using body, but the textfield with the URL would be fine
                        wpLink.close();
                        event.preventDefault ? event.preventDefault() : event.returnValue = false;
                        event.stopPropagation();
                        return false;
                    });
                }
            },
            unlink: {
                icon: '<span class="dashicons link-manager-unlink dashicons-editor-unlink"></span>',
                title: 'Unlink',
                state: function state() {
                    var state = getSelectionContainerElement().is('a');
                    var el = $(settings.element).find('.link-manager-unlink').parents('.simple-editor-button-wrapper:first');
                    if (state) {
                        el.show();
                    } else {
                        el.hide();
                    }
                    return state;
                },
                result: function result() {
                    var state = getSelectionContainerElement().is('a');
                    if (state) {
                        unwrap(getSelectionContainerElement()[0]);
                    }
                    return true;
                }
            }
        };


        var actions = settings.actions ? settings.actions.map(function (action) {
            if (typeof action === 'string') return defaultActions[action]; else if (defaultActions[action.name]) return _extends({}, defaultActions[action.name], action);
            return action;
        }) : Object.keys(defaultActions).map(function (action) {
            return defaultActions[action];
        });

        var handlers = [];
        var runAll = function () {
            handlers.forEach(function (hand) {
                hand();
            });
            return true;
        };

        actions.reverse();
        actions.forEach(function (action) {
            var button = createElement('button');
            button.className = classes.button;
            button.innerHTML = action.icon;
            button.title = action.title;
            button.setAttribute('type', 'button');
            addEventListener(button, 'click', function () {
                return action.result() && content.focus();
            });
            var wrapper = createElement('div');
            wrapper.className = defaultClasses.wrapper;
            wrapper.appendChild(button);
            var handler;
            if (action.state) {
                handler = function handler() {
                    button.classList[action.state() ? 'add' : 'remove'](classes.selected);
                    return wrapper.classList[action.state() ? 'add' : 'remove'](classes.selected);
                };
                handlers.push(handler);
                destroyCallbacks.push(addEventListener(content, 'keyup', runAll));
                destroyCallbacks.push(addEventListener(content, 'mouseup', runAll));
                destroyCallbacks.push(addEventListener(button, 'click', runAll));
            }


            appendChild(actionBar, wrapper);

            if (handler) {
                handler();
            }
        });

        if (settings.styleWithCSS) {
            exec('styleWithCSS');
        }

        exec(defaultParagraphSeparatorString, defaultParagraphSeparator);

        return {
            destroy: function () {
                if (actionBar.parentNode) {
                    destroyCallbacks.forEach(function (destroy) {
                        destroy();
                    });
                    actionBar.parentNode.removeChild(actionBar);
                    if (settings.afterDestroy) {
                        settings.afterDestroy.call($element, settings);
                    }
                }
            }
        };
    };

    var simpleeditor = {exec: exec, init: init};

    exports.exec = exec;
    exports.init = init;
    exports['default'] = simpleeditor;

    Object.defineProperty(exports, '__esModule', {value: true});

})));

(function ($) {
    $.fn.simpleEditor = function (opts) {
        opts = opts || {};
        var wrapper = {
            callbacks: [],
            destroy: function () {
                wrapper.callbacks.forEach(function (callback) {
                    callback();
                });
            }
        };
        this.each(function () {
            var that = $(this);
            var api = that.data('simpleEditor');
            if (api) {
                wrapper.callbacks.push(function () {
                    api.destroy();
                });
                return api;
            }
            api = simpleeditor.init({
                element: that[0],
                defaultParagraphSeparator: opts.defaultParagraphSeparator,
                onChange: opts.change || function () {
                },
                afterDestroy: function () {
                    that.data('simpleEditor', null);
                }
            });
            wrapper.callbacks.push(function () {
                api.destroy();
            });
            that.data('simpleEditor', api);
            return api;
        });
        return wrapper;
    };
}(jQuery));

