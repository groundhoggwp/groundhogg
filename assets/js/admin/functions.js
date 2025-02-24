// if ( typeof jQuery === 'undefined' ){
//     var jQuery = function ( a=false ) {
//         return jQuery;
//     };
// }

/**
 * Send an ajax request to the WP admin.
 *
 * @param args [] list of args to send.
 * @param callback a callback function.
 * @param fail_callback
 */
function adminAjaxRequest (args, callback = null, fail_callback = null, overrides = {}) {
  var ajaxCall = jQuery.ajax({
    type    : 'post',
    url     : ajaxurl,
    dataType: 'json',
    data    : args,
    success : callback,
    error   : fail_callback,
    ...overrides,
  })
}

/**
 * Show a spinner
 */
function showSpinner () {
  jQuery('.spinner').css('visibility', 'visible')
}

/**
 * Hide a spinner
 */
function hideSpinner () {
  jQuery('.spinner').css('visibility', 'hidden')
}

/**
 * Add notices to the notices div.
 *
 * @param notices
 */
function handleNotices (notices) {
  if (typeof notices !== 'undefined') {
    jQuery('#groundhogg-notices').html(notices)
  }

  makeDismissible()
}

/**
 * Make notices dismissible
 */
function makeDismissible () {

  jQuery('<button type=\'button\' class=\'notice-dismiss\'><span class=\'screen-reader-text\'>Dismiss This Notice</span></button>').appendTo('.is-dismissible')
  jQuery('.notice-dismiss').on('click', function (e) {
    jQuery(this).parent().fadeOut(100, function () {
      jQuery(this).remove()
    })
  })
}
