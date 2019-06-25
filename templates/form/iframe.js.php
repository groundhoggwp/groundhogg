<?php
/**
 * Responsive Form Iframe JS Template
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.20
 */
header("Content-Type: application/javascript");
header("Cache-Control: max-age=604800, public");

?>
/**
 * Responsive Form Iframe JS Template
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.20
 */

(function() {
    if (window.ghFormClient) {
        return;
    }

    window.ghFormClient = {};
    ghFormClient.addForm = addForm;
    ghFormClient.receiveMessage = receiveMessage;

    var formId = 0;
    var idPrefix = 'groundhogg_form_';
    var forms = [];
    var isIframeLoading = false;
    var hasIframeToLoad = false;

    var referrer;
    if (parent) {
        referrer = parent.document.URL;
    } else {
        referrer = document.URL;
    }

    function addEvent( event, callback ){
        if (!window.addEventListener) { // This listener will not be valid in < IE9
            window.attachEvent("on" + event, callback);
        } else { // For all other browsers other than < IE9
            window.addEventListener( event, callback, false);
        }
    }

    addEvent( 'message', receiveMessage );
    addEvent( 'resize', resizeAllFrames );
    addEvent( 'load', resizeAllFrames );

    function receiveMessage(event) {
        var data = event.data;
        resizeForm(data);
    }

    function resizeForm(data) {

        if (data.height) {
            var f = document.getElementById( idPrefix + 'Iframe_' + data.id );
            if (f) {
                f.height = data.height;
                f.width = data.width;
                f.style.height = data.height;
                f.style.width = data.width;
                formLoaded(data.id);
            }
            loadNextForm();
        }
    }

    function resizeAllFrames() {
        for (var i = 0; i < forms.length; i++ ){
            var ifrm = getIframe( forms[i].id );
            var height = ifrm.contentWindow.postMessage({action:'getFrameSize',id:forms[i].id}, "*");
        }
    }

    function addForm(url) {
        hasIframeToLoad = true;
        formId = forms.length;
        forms.push({url:url, id:formId, iframeLoaded:false, iframeLoading:false});

        if ( document.currentScript ){
            var d = document.createElement( 'div' );
            d.id = idPrefix + 'Div_' + formId;
            document.currentScript.parentNode.insertBefore( d, document.currentScript );
        } else {
            document.write('<div id="' + idPrefix + 'Div_' + formId + '"></div>');
        }


        if (formId == 0) {
            addFormIframe(formId, url);
        }
    }

    function addFormIframe(id, url) {
        var div = document.getElementById(idPrefix + 'Div_' + id);
        if (div) {
            isIframeLoading = true;

            var queryStr = "";
            var indexQueryStr = referrer.indexOf("?");

            if (indexQueryStr > -1) {
                queryStr = "&" + referrer.substring(indexQueryStr + 1);
            }

            forms[id].iframeLoading = true;

            div.innerHTML = '<iframe id="' + idPrefix + 'Iframe_' + id + '" name="infFormId=' + id + '&url=' + escape(location.href) +
                '" allowtransparency="true" src="' + url + '?referrer=' + escape(referrer) + escape( queryStr ) +
                '" frameborder="0" scrolling="no" style="overflow:hidden; border:none; width:100%;' +
                '" height="450px"></iframe>';
        }
    }

    /**
     * Get a form Iframe
     * @param id
     */
    function getIframe( id ){
        var ID = idPrefix + 'Iframe_' + id;
        return document.getElementById( ID );
    }

    function startsWith(str, pattern) {
        if (str == null || pattern == null) return false;
        return str.length >= pattern.length && str.toLowerCase().indexOf(pattern.toLowerCase()) == 0;
    }

    function loadNextForm() {
        if (hasIframeToLoad && !isIframeLoading) {
            for (var i = 0; i < forms.length; i++) {
                var form = forms[i];
                if (!form.iframeLoaded && !form.iframeLoading) {
                    var div = document.getElementById(idPrefix + 'Div_' + form.id);
                    var ifr = document.getElementById(idPrefix + 'Iframe_' + form.id);
                    if (div && !ifr) {
                        addFormIframe(form.id, form.url);
                        break;
                    }
                }
            }
            if (!isIframeLoading) {
                hasIframeToLoad = false;
            }
        }
    }

    function formLoaded(id) {
        forms[id].iframeLoaded = true;
        forms[id].iframeLoading = false;
        isIframeLoading = false;
    }

})();

ghFormClient.addForm('<?php echo site_url( sprintf( 'gh/forms/%s/', urlencode( \Groundhogg\encrypt( get_query_var( 'form_id' ) ) ) ) ); ?>' );