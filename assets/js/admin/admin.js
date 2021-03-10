( function ($, nonces, endpoints, gh) {

  // Serialize better
  $.fn.serializeFormJSON = function () {

    var o = {}
    var a = this.serializeArray()
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]]
        }
        o[this.name].push(this.value || '')
      }
      else {
        o[this.name] = this.value || ''
      }
    })
    return o
  }

  function picker (selector, args) {
    $(selector).select2(args)
  }

  function apiPicker (selector, endpoint, multiple, tags) {
    $(selector).select2({
      tags: tags,
      multiple: multiple,
      tokenSeparators: ['/', ',', ';'],
      ajax: {
        url: endpoint,
        dataType: 'json',
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', nonces._wprest)
        },
        results: function (data, page) {
          return {
            results: data.results,
          }
        },
      },
    })
  }

  function linkPicker (selector) {
    $(selector).autocomplete({
      source: function (request, response) {
        $.ajax({
          url: ajaxurl,
          method: 'post',
          dataType: 'json',
          data: {
            action: 'wp-link-ajax',
            _ajax_linking_nonce: nonces._ajax_linking_nonce,
            term: request.term,
          },
          success: function (data) {
            var $return = []
            for (var item in data) {
              if (data.hasOwnProperty(item)) {
                item = data[item]
                $return.push({
                  label: item.title + ' (' + item.info + ')',
                  value: item.permalink,
                })
              }
            }
            response($return)
          },
        })
      },
      minLength: 0,
    })
  }

  function metaPicker (selector) {
    $(selector).autocomplete({
      source: function (request, response) {
        $.ajax({
          url: ajaxurl,
          method: 'post',
          dataType: 'json',
          data: {
            action: 'gh_meta_picker',
            nonce: nonces._meta_nonce,
            term: request.term,
          },
          success: function (data) {
            response(data)
            $(selector).removeClass('ui-autocomplete-loading')
          },
        })
      },
      minLength: 0,
    })
  }

  function buildPickers () {
    picker('.gh-select2', {})
    apiPicker('.gh-tag-picker', endpoints.tags, true, true)
    apiPicker('.gh-single-tag-picker', endpoints.tags, false, false)
    apiPicker('.gh-single-tag-picker', endpoints.tags, false, false)
    apiPicker('.gh-email-picker', endpoints.emails, false, false)
    apiPicker('.gh-email-picker-multiple', endpoints.emails, true, false)
    apiPicker('.gh-sms-picker', endpoints.sms, false, false)
    apiPicker('.gh-contact-picker', endpoints.contacts, false, false)
    apiPicker('.gh-contact-picker-multiple', endpoints.contacts, true, false)
    apiPicker('.gh-benchmark-picker', endpoints.benchmarks, false, false)
    apiPicker('.gh-metakey-picker', endpoints.metakeys, false, false)
    linkPicker('.gh-link-picker')
    metaPicker('.gh-meta-picker')
  }

  $(function () {
    buildPickers()
  })

  $(document).on('new-step gh-init-pickers', function () {
    buildPickers()
  })

  $(document).on('click', '.dropdown-button .button.dropdown', function (){
    var $button = $(this)
    $button.next().toggleClass( 'show' );
    $( "<div class='dropdown-overlay'></div>" ).insertAfter( $button );
  } );

  $(document).on('click', '.dropdown-button .dropdown-overlay', function (){
    var $overlay = $(this)
    $overlay.next().toggleClass( 'show' );
    $overlay.remove();
  } );

  gh.pickers = {}

  // Map functions to Groundhogg object.
  gh.pickers.picker = picker
  gh.pickers.apiPicker = apiPicker
  gh.pickers.linkPicker = linkPicker
  gh.pickers.metaPicker = metaPicker
  gh.nonces = nonces
  gh.endpoints = endpoints

} )(jQuery, groundhogg_nonces, groundhogg_endpoints, Groundhogg)