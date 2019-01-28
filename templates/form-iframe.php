<?php

/**
 * This is a template for the Form iframe functionality
 */
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php do_action( 'wpgh_form_iframe_title' ); ?></title>

    <?php do_action( 'wp_head' ); ?>

</head>
<body>
<script>
    !function() {
        function e() {
            function e(e) {
                for (var n = 0; n < w.length; n++)
                    w[n].style.display = e ? "block" : "none";
                if (e) {
                    var t = w[w.length - 1]
                        , r = jQuery(t).offset().top
                        , a = f - r;
                    a < 200 && (f += 200 - a)
                }
            }
            var o = jQuery(".bodyContainer")
                , i = parseInt(o.css("border-top-width"))
                , l = parseInt(o.css("border-bottom-width"))
                , f = o.height() + i + l + 20;
            f = isNaN(i) && isNaN(l) ? o.height() + 20 : f + i + l;
            var c = jQuery("#mainContent").width();
            if (t(a, "file") ? c -= 15 : c += 30,
            document.getElementsByClassName("cal-popup").length > 0) {
                var u = [].slice.call(document.getElementsByClassName("cal-popup"))
                    , d = [].slice.call(document.getElementsByClassName("pikaday-container"))
                    , w = u.concat(d)
                    , h = jQuery(d[0]);
                if (h.length > 0 && h.parent().length > 0) {
                    var m = h.parent().offset().left;
                    e(c - m > 250 ? !0 : !1)
                }
            }
            var g = s + "_id" + r + "_h" + f + "_w" + c;
            n(g, f)
        }
        function n(e, n) {
            jQuery(window).height() == n || m || (m = !0,
                clearInterval(h),
                window.parent.postMessage(e, "*"))
        }
        function t(e, n) {
            return null != e && null != n && (e.length >= n.length && 0 == e.toLowerCase().indexOf(n.toLowerCase()))
        }
        if (!window.InfusionIframeMagicServer) {
            var r, a, o = /infFormId=(\d+)/, i = window.name + "", l = i.match(o), s = "infform", f = 200, c = "[\\?&]referrer=([^&#]*)", u = new RegExp(c), d = window.location.href, w = u.exec(d);
            a = null == w ? d : w[1];
            var h, m = !1;
            window.InfusionIframeMagicServer = {},
                InfusionIframeMagicServer.resizeFrame = e,
            l && (r = l[1],
            r && parent != window && jQuery(window).on("load", function() {
                h = setInterval(function() {
                    e()
                }, f)
            }))
        }
    }();


</script>
 <?php do_action( 'wpgh_form_iframe_content' ) ?>
</body>
</html>

