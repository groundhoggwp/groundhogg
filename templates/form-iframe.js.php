<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-28
 * Time: 12:34 PM
 */

header("Content-Type: application/javascript");
header("Cache-Control: max-age=604800, public");

?>
(function() {
    if (window.ghFormClient) {
        return;
    }

    window.ghFormClient = {};
    ghFormClient.addForm = addForm;
    ghFormClient.receiveMessage = receiveMessage;

    var formId = 0;
    var idPrefix = 'ghForm';
    var forms = [];
    var isIframeLoading = false;
    var hasIframeToLoad = false;

    var referrer;
    if (parent) {
        referrer = parent.document.URL;
    } else {
        referrer = document.URL;
    }
    //
    //if (startsWith(referrer, "file")) {
    //    document.write("<style>body {margin:0;}</style>");
    //}

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
        document.write('<div id="' + idPrefix + 'Div_' + formId + '"></div>');
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
                '" allowtransparency="true" src="' + url + '&referrer=' + escape(referrer) + queryStr +
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

ghFormClient.addForm('<?php printf('%s?ghFormIframe=1&formId=%s', site_url(), intval( $_GET[ 'formId' ] ) ) ?>');