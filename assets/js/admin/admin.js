(function ($, nonces, endpoints, gh) {

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
      } else {
        o[this.name] = this.value || ''
      }
    })
    return o
  }

  function picker (selector, args) {
    return $(selector).select2(args)
  }

  /**
   * This is an API picker!
   *
   * @param selector
   * @param endpoint
   * @param multiple
   * @param tags
   * @param getResults
   * @param getParams
   * @returns {*|define.amd.jQuery}
   */
  function apiPicker (
    selector,
    endpoint,
    multiple = false,
    tags = false,
    getResults = (d) => d.results,
    getParams = (p) => p
  ) {

    return $(selector).select2({
      tags: tags,
      multiple: multiple,
      tokenSeparators: ['/', ',', ';'],
      ajax: {
        url: endpoint,
        // delay: 250,
        dataType: 'json',
        data: getParams,
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', nonces._wprest)
        },
        processResults: function (data, page) {
          return {
            results: getResults(data, page)
          }
        },
      },
    })
  }

  function linkPicker (selector) {
    return $(selector).autocomplete({
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
    return $(selector).autocomplete({
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

  /**
   * Api based tag picker
   *
   * @param selector
   * @param multiple
   * @param onReceiveItems
   */
  function tagPicker (selector, multiple = true, onReceiveItems = (items) => {}) {
    return apiPicker(selector, gh.api.routes.v4.tags, multiple, true,
      (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ({
          id: item.ID,
          text: `${item.data.tag_name}`
        }))
      },
      (query) => {
        return {
          search: query.term
        }
      })
  }

  /**
   * Api based tag picker
   *
   * @param selector
   * @param multiple
   * @param onReceiveItems
   */
  function campaignPicker (selector, multiple = true, onReceiveItems = (items) => {}) {
    return apiPicker(selector, gh.api.routes.v4.campaigns, multiple, true,
      (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ({
          id: item.ID,
          text: `${item.data.name}`
        }))
      },
      (query) => {
        return {
          search: query.term
        }
      })
  }

  /**
   * Api based email picker
   *
   * @param selector
   * @param multiple
   * @param onReceiveItems
   */
  function emailPicker (selector, multiple = false, onReceiveItems = (items) => {}) {
    return apiPicker(selector, gh.api.routes.v4.emails, multiple, true, (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ({
          id: item.ID,
          text: `${item.data.title} (${item.data.status})`
        }))
      },
      (query) => {
        return {
          search: query.term
        }
      })
  }

  function buildPickers () {
    picker('.gh-select2', {})
    tagPicker('.gh-tag-picker', true)
    tagPicker('.gh-single-tag-picker', false)
    emailPicker('.gh-email-picker', false)
    emailPicker('.gh-email-picker-multiple', true)
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

  $(document).on('click', '.dropdown-button .button.dropdown', function () {
    var $button = $(this)
    $button.next().toggleClass('show')
    $('<div class=\'dropdown-overlay\'></div>').insertAfter($button)
  })

  $(document).on('click', '.dropdown-button .dropdown-overlay', function () {
    var $overlay = $(this)
    $overlay.next().toggleClass('show')
    $overlay.remove()
  })

  gh.pickers = {
    picker,
    tagPicker,
    emailPicker,
    apiPicker,
    linkPicker,
    metaPicker,
    campaignPicker
  }

  // Map functions to Groundhogg object.
  gh.nonces = nonces
  gh.endpoints = endpoints

})(jQuery, groundhogg_nonces, groundhogg_endpoints, Groundhogg)
