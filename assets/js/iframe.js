(function() {
    if (window.InfusionIframeMagicClient) {
        return;
    }

    window.InfusionIframeMagicClient = {};
    InfusionIframeMagicClient.addForm = addForm;
    InfusionIframeMagicClient.receiveMessage = receiveMessage;

    var formId = 0;
    var idPrefix = 'infForm';
    var msgDataRE = /^infform_id(\d+)_h(\d+)(\.\d+)?_w(\d+)(\.\d+)?$/;
    var forms = [];
    var isIframeLoading = false;
    var hasIframeToLoad = false;

    var referrer;
    if (parent) {
        referrer = parent.document.URL;
    } else {
        referrer = document.URL;
    }

    if (startsWith(referrer, "file")) {
        document.write("<style>body {margin:0;}</style>");
    }

    if (!window.addEventListener) { // This listener will not be valid in < IE9
        window.attachEvent("onmessage", receiveMessage);
    } else { // For all other browsers other than < IE9
        window.addEventListener("message", receiveMessage, false);
    }

    function receiveMessage(event) {
        var data = event.data;
        resizeForm(data);
    }

    function resizeForm(data) {
        alert('hello');
        var found = data.match(msgDataRE);
        console.log(found);
        if (found) {
            console.log('width', found[4]);
            var f = document.getElementById(idPrefix + 'Iframe_' + found[1]);
            if (f) {
                f.height = found[2];
                f.width = found[4];
                f.style.height = found[2];
                f.style.width = found[4];
                formLoaded(found[1]);
            }
            loadNextForm();
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
//InfusionIframeMagicClient.addForm('https://js935.infusionsoft.com/app/form/7a5da3f3de8ddec53059c7bbef1346e1?iFrame=true');

InfusionIframeMagicClient.addForm('http://localhost/wp/?ghFormIframe=true&formId=12&iFrame=true');

// InfusionIframeMagicClient.addForm(document.location.hostname +'../../templates/from-iframe.php');

