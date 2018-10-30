(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
        typeof define === 'function' && define.amd ? define(['exports'], factory) :
            (factory((global.simpleeditor = {})));
}(this, (function (exports) {
    'use strict';
    var $ = window.jQuery || window.$;
    var editorId = 0;
    var nID = 0;

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
        saveSelection = function (containerEl) {
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

        restoreSelection = function (containerEl, savedSel) {
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
        saveSelection = function (containerEl) {
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

        restoreSelection = function (containerEl, savedSel) {
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
     * Gets the exact selection of the text that user selected
     * @return {string}
     */
    var getSelectionText = function () {
        var text = "";
        if (window.getSelection) {
            text = window.getSelection().toString();
        } else if (document.selection && document.selection.type != "Control") {
            text = document.selection.createRange().text;
        }
        return text;
    };

    /**
     *
     * Get currently selected element
     * @return jQuery
     */
    var getSelectionContainerElement = function (focusNode, raw) {
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
                if (raw) {
                    return sel.focusNode;
                }
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
        editorId++;
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
        var lastSelectionBeforeLinkEdit = null;
        var $element = $(settings.element);


        var actionBar = createElement('div');
        actionBar.className = classes.actionbar;
        //appendChild(settings.element.parentNode, actionBar);
        $element.before($(actionBar));
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
        $(content).addClass(classes.content);

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

        var ddropdown = createElement("div");
        ddropdown.className = 'simple-editor-dropdown';
        ddropdown.innerHTML = '<div class="simple-editor-dropdown-inner"></div>';
        var dropdown = $(ddropdown);
        dropdown.hide();
        appendChild(settings.element, ddropdown);

        dropdown.on('click', '.simple-editor-dropdown-item', function (ev) {
            var target = $(this).attr('data-target');
            var action = defaultActions[target];
            ev.preventDefault();
            var t = action.result() && content.focus();
            runAll();
            return t;
        });

        var appearDropdown = function (ref, items) {
            stopHide = true;
            dropdown.find('.simple-editor-dropdown-inner').html(items);
            dropdown.css('left', ref.position().left);
            dropdown.css('top', 0);
            dropdown.show();
        };

        var hideColorPickerDiv = function () {
            stopHide = false;
            $colorPickerDiv.hide();
            dropdown.hide();
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
            $('.color-preview').css('background', color);
            hideColorPickerDiv();
        });


        destroyCallbacks.push(addEventListener(document, 'click', function (ev) {
            if (ev.target) {
                if (stopHide) {
                    stopHide = false;
                    return;
                }
                var $target = $(ev.target);
                if ($target.is('.simple-editor-dropdown') || $target.parents('.simple-editor-dropdown:first').length || $target.is('.simple-editor-font-color') || $target.parents('.simple-editor-font-color:first').length) {
                    return;
                }
                hideColorPickerDiv();
            }
        }));


        destroyCallbacks.push(function () {
            $colorPickerDiv.remove();
            dropdown.remove();
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


        var makeResponsive = function (icon, title, groups) {
            var resp = {
                groups: groups,
                icon: icon.join(" "),
                title: title,
                getIcon: function () {
                    resp.groups.forEach(function (value) {
                        var act = defaultActions[value];
                        if ($(act.wrapper).hasClass(classes.selected)) {
                            $(resp.wrapper).find('button').html($(act.wrapper).find('button').html() + " " + icon[1]);
                        }
                    });
                },
                extraClass: 'simple-editor-responsive-button',
                state: function state() {
                    var allTruth = false;
                    resp.groups.forEach(function (value) {
                        var act = defaultActions[value];
                        if (act.state()) {
                            allTruth = true;
                        }
                    });
                    return allTruth;
                },
                result: function result() {
                    var html = [];
                    resp.groups.forEach(function (value) {
                        html.push('<div class="simple-editor-dropdown-item" data-target="' + value + '">' + defaultActions[value].wrapper.outerHTML + '</div>');
                    });
                    appearDropdown($(resp.wrapper), html.join(""));
                }
            };
            return resp;
        };

        var makeHeading = function (level) {
            var el = 'h' + level;
            return {
                icon: '<b>H<sub>' + level + '</sub></b>',
                extraClass: 'simple-editor-hsc',
                title: 'Heading ' + level,
                state: function state() {
                    return getSelectionContainerElement().is(el) || getSelectionContainerElement().parents(el + ':first').length;
                },
                result: function result() {
                    var elm = getSelectionContainerElement();
                    if (getSelectionContainerElement().is(el) || getSelectionContainerElement().parents(el + ':first').length) {
                        //unwrap(elm[0]);
                        return true;
                    }

                    var n = exec(formatBlock, '<' + el + '>');
                    if (window.wpghTextBlock) {
                        wpghTextBlock.h1Font.trigger('change');
                        wpghTextBlock.h1Size.trigger('change');
                    }

                    return n;
                }
            };
        };

        var defaultActions = {
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
                icon: '<span class="dashicons simple-editor-color-picker-handle dashicons-editor-textcolor"><span class="color-preview"></span></span>',
                title: 'Color',
                state: function state() {
                    return queryCommandState('foreColor');
                },
                result: function result() {
                    stopHide = true;
                    lastSelection = saveSelection(content);
                    $colorPickerDiv.css('left', $('.simple-editor-color-picker-handle').parents('.simple-editor-button-wrapper:first').position().left);
                    $colorPickerDiv.css('top', 0);
                    $colorPickerDiv.show();
                    // console.log({e: $colorPickerDiv.show()});
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
                extraClass: 'simple-editor-hsc',
                state: function state() {
                    return queryCommandState('justifyLeft');
                },
                result: function result() {
                    return exec('justifyLeft');
                }
            },
            alignRight: {
                icon: '<span class="dashicons dashicons-editor-alignright"></span>',
                title: 'Right alignment',
                extraClass: 'simple-editor-hsc',
                state: function state() {
                    return queryCommandState('justifyRight');
                },
                result: function result() {
                    return exec('justifyRight');
                }
            },
            alignCenter: {
                icon: '<span class="dashicons dashicons-editor-aligncenter"></span>',
                title: 'Center alignment',
                extraClass: 'simple-editor-hsc',
                state: function state() {
                    return queryCommandState('justifyCenter');
                },
                result: function result() {
                    return exec('justifyCenter');
                }
            },
            alignJustify: {
                icon: '<span class="dashicons dashicons-editor-justify"></span>',
                title: 'Justify alignment',
                extraClass: 'simple-editor-hsc',
                state: function state() {
                    return queryCommandState('justifyFull');
                },
                result: function result() {
                    return exec('justifyFull');
                }
            },
            heading1: makeHeading("1"),
            heading2: makeHeading("2"),
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
            paragraph: {
                icon: '&#182;',
                title: 'Paragraph',
                extraClass: 'simple-editor-hsc',
                state: function state() {
                    return getSelectionContainerElement().is('p') || getSelectionContainerElement().parents('p:first').length;
                },
                result: function result() {
                    var elm = getSelectionContainerElement();
                    if (getSelectionContainerElement().is('p') || getSelectionContainerElement().parents('p:first').length) {
                        //unwrap(elm[0]);
                        return true;
                    }
                    $(elm).attr('style', '');
                    return exec(formatBlock, '<p>');
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
                    var action = prompt;
                    // if wpLink is available use that
                    if (window.wpLink) {
                        action = function () {
                            lastSelectionBeforeLinkEdit = saveSelection(content);
                            var tmptextid = 'textarea-simple-editor' + editorId;
                            var $tmptextarea = $('#' + tmptextid);
                            if (!$tmptextarea.length) {
                                $tmptextarea = $(createElement('textarea'));
                                $tmptextarea.attr('id', tmptextid);
                                $tmptextarea.hide();
                                appendChild(settings.element, $tmptextarea);
                                destroyCallbacks.push(function () {
                                    $tmptextarea.remove();
                                });
                            }

                            var existing = '';
                            var title = getSelectionText() || '';
                            var selected = getSelectionContainerElement();

                            if (state) {
                                existing = el.attr('href');
                                title = el.text();
                            }

                            var appear = function () {
                                $('#wp-link-url').val(existing);
                            };

                            $(document).on('wplink-open', appear);

                            wpLink.open(tmptextid, existing, title);

                            var unbind = function () {
                                $(document).off('wplink-open', appear);
                                $('body').off('click', '#wp-link-submit', submit).off('click', '#wp-link-cancel, #wp-link-close', close);
                                settings.onChange.call($element, content.innerHTML);
                            };

                            var submit = function (event) {
                                var linkAtts = $.parseHTML($tmptextarea.val());
                                $tmptextarea.val('');
                                var item = null;
                                $(linkAtts).each(function () {
                                    item = $(this);
                                });

                                if (item) {
                                    var newHref = item.attr('href');
                                    restoreSelection(content, lastSelectionBeforeLinkEdit);

                                    var text = item.contents().get(0).nodeValue;
                                    if (state) {
                                        el.attr('href', newHref);
                                        el.text(text);
                                    } else {
                                        exec('createLink', newHref);
                                        if (selected.is('a')) {
                                            selected.text(text);
                                        } else {
                                            selected.find('a').text(text);
                                        }
                                    }
                                }

                                unbind();
                                wpLink.close();
                                event.preventDefault ? event.preventDefault() : event.returnValue = false;
                                event.stopPropagation();
                                return false;
                            };

                            var close = function (event) {
                                restoreSelection(content, lastSelectionBeforeLinkEdit);
                                unbind();
                                wpLink.close();
                                event.preventDefault ? event.preventDefault() : event.returnValue = false;
                                event.stopPropagation();
                                return false;
                            };


                            $('body').on('click', '#wp-link-submit', submit).on('click', '#wp-link-cancel, #wp-link-close', close);

                        };
                    } else {
                        action = function () {
                            if (state) {
                                var newhref = prompt("Edit Link", el.attr('href'));
                                el.attr('href', newhref);
                                return true;
                            }
                            var url = window.prompt('Enter the link URL');
                            if (url) exec('createLink', url);
                        };
                    }

                    action();
                    return true;

                }
            },
            unlink: {
                icon: '<span class="dashicons link-manager-unlink dashicons-editor-unlink"></span>',
                title: 'Unlink',
                extraClass: 'simple-editor-unlink-c',
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
            },
            responsiveHeadings: makeResponsive(['<b>H<sub>1</sub></b>', '<span class="dashicons dicon-sm dashicons-arrow-down-alt2"></span>'], 'Headings', ["paragraph", "heading1", "heading2"]),
            responsiveAlign: makeResponsive(['<span class="dashicons dashicons-editor-alignleft"></span>', '<span class="dashicons dicon-sm dashicons-arrow-down-alt2"></span>'], 'Alignments', ["alignLeft", "alignRight", "alignCenter", "alignJustify"])
        };


        var defaultOrder = Object.keys(defaultActions);


        var actions = settings.actions ? settings.actions.map(function (action) {
            if (typeof action === 'string') return defaultActions[action]; else if (defaultActions[action.name]) return _extends({}, defaultActions[action.name], action);
            return action;
        }) : defaultOrder.map(function (action) {
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
            nID++;
            action.nextId = nID;
            var button = createElement('button');
            var clx = 'simple-editor-action-t-' + action.nextId;
            button.className = classes.button;
            button.innerHTML = action.icon;
            button.title = action.title;
            button.setAttribute('type', 'button');
            addEventListener(button, 'click', function () {
                return action.result() && content.focus();
            });
            var wrapper = createElement('div');
            wrapper.className = defaultClasses.wrapper + ' ' + clx;

            if (action.extraClass) {
                wrapper.className += ' ' + action.extraClass;
            }
            wrapper.appendChild(button);
            var handler;
            if (action.state) {
                handler = function handler() {
                    $('.' + clx).each(function () {
                        var el = $(this)[action.state() ? 'addClass' : 'removeClass'](classes.selected);
                        el.find('button')[action.state() ? 'addClass' : 'removeClass'](classes.selected);
                    });

                    if (action.getIcon) {
                        action.getIcon();
                    }

                    //button.classList[action.state() ? 'add' : 'remove'](classes.selected);
                    //return wrapper.classList[action.state() ? 'add' : 'remove'](classes.selected);
                };
                handlers.push(handler);
                destroyCallbacks.push(addEventListener(content, 'keyup', runAll));
                destroyCallbacks.push(addEventListener(content, 'mouseup', runAll));
                destroyCallbacks.push(addEventListener(button, 'click', runAll));
            }

            action.wrapper = wrapper;


            appendChild(actionBar, wrapper);

            if (handler) {
                handler();
            }

            if (action.getIcon) {
                action.getIcon();
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
                actions: opts.actions,
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