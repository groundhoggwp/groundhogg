( function ($, nonces, endpoints, gh) {

  const {
    currentUser,
    isSuperAdmin,
  } = Groundhogg

  Groundhogg.user = {
    getCurrentUser     : () => {
      return currentUser
    },
    userHasCap         : (cap) => {
      return currentUser.allcaps[cap] || currentUser.caps[cap] || isSuperAdmin
    },
    getOwner           : (id) => {
      return Groundhogg.filters.owners.find(u => u.ID == id)
    },
    getOwnerDisplayName: (id) => {
      return Groundhogg.filters.owners.find(u => u.ID == id).data.display_name
    },
  }

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
    return $(selector).select2(args)
  }

  $.fn.ghPicker = function ({
    endpoint,
    getResults = (r) => r.items,
    getParams = (q) => ( {
      ...q,
      search: q.term,
    } ),
    ...rest
  }) {

    this.select2({
      tokenSeparators: [
        '/',
        ',',
        ';',
      ],
      delay          : 100,
      ajax           : {
        url: endpoint,
        // delay: 250,
        dataType      : 'json',
        data          : getParams,
        beforeSend    : function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce)
        },
        processResults: function (data, page) {
          return {
            results: getResults(data, page),
          }
        },
      },
      ...rest,
    })

    return this
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
   * @param select2opts
   * @returns {*|define.amd.jQuery}
   */
  function apiPicker (
    selector,
    endpoint,
    multiple = false,
    tags = false,
    getResults = (d) => d.results,
    getParams = (q) => ( {
      ...q,
      search: q.term,
    } ),
    select2opts = {},
  ) {

    return $(selector).select2({
      tags           : tags,
      multiple       : multiple,
      tokenSeparators: [
        '/',
        ',',
        ';',
      ],
      delay          : 100,
      ajax           : {
        url: endpoint,
        // delay: 250,
        dataType      : 'json',
        data          : getParams,
        beforeSend    : function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce)
        },
        processResults: function (data, page) {
          return {
            results: getResults(data, page),
          }
        },
      },
      ...select2opts,
    })
  }

  function linkPicker (selector) {
    let $input = $(selector)

    return $input.autocomplete({
      source   : function (request, response) {
        $.ajax({
          url     : ajaxurl,
          method  : 'post',
          dataType: 'json',
          data    : {
            action             : 'wp-link-ajax',
            _ajax_linking_nonce: nonces._ajax_linking_nonce,
            term               : request.term,
          },
          success : function (data) {
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
          select  : (e, ui) => {
            $input.value(ui.item.value).trigger('change')
          },

        })
      },
      minLength: 0,
    })
  }

  function userMetaPicker (selector) {
    let $input = $(selector)

    return $input.autocomplete({
      source   : function (request, response) {
        $.ajax({
          url     : ajaxurl,
          method  : 'post',
          dataType: 'json',
          data    : {
            action: 'user_meta_picker',
            nonce : nonces._meta_nonce,
            term  : request.term,
          },
          success : function (data) {
            response(data)
            $(selector).removeClass('ui-autocomplete-loading')
          },
          select  : (e, ui) => {
            $input.value(ui.item.value).trigger('change')
          },
        })
      },
      minLength: 0,
    })
  }

  function metaPicker (selector) {
    let $input = $(selector)

    return $input.autocomplete({
      source   : function (request, response) {
        $.ajax({
          url     : ajaxurl,
          method  : 'post',
          dataType: 'json',
          data    : {
            action: 'gh_meta_picker',
            nonce : nonces._meta_nonce,
            term  : request.term,
          },
          success : function (data) {
            response(data)
            $(selector).removeClass('ui-autocomplete-loading')
          },
          select  : (e, ui) => {
            $input.value(ui.item.value).trigger('change')
          },
        })
      },
      minLength: 0,
    })
  }

  function metaValuePicker (selector, meta_key) {

    let $input = $(selector)

    return $input.autocomplete({
      source   : function (request, response) {
        $.ajax({
          url     : ajaxurl,
          method  : 'post',
          dataType: 'json',
          data    : {
            action: 'gh_meta_value_picker',
            nonce : nonces._meta_nonce,
            term  : request.term,
            meta_key,
          },
          success : function (data) {
            response(data)
            $(selector).removeClass('ui-autocomplete-loading')
          },
          select  : (e, ui) => {
            $input.value(ui.item.value).trigger('change')
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
   * @param opts
   */
  function tagPicker (selector, multiple = true, onReceiveItems = (items) => {}, ...opts) {
    return apiPicker(selector, gh.api.routes.v4.tags, multiple, Groundhogg.user.userHasCap('add_tags'),
      (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ( {
          id  : item.ID,
          text: `${ item.data.tag_name }`,
        } ))
      },
      (query) => {
        return {
          search: query.term,
          limit : 50,
        }
      }, ...opts)
  }

  /**
   * Api based contact picker
   *
   * @param selector
   * @param onReceiveItems
   * @param opts
   */
  function contactPicker (selector, onReceiveItems = (items) => {}, ...opts) {
    return apiPicker(selector, gh.api.routes.v4.contacts, false, false,
      (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ( {
          id  : item.ID,
          text: `${ item.data.first_name } ${ item.data.last_name } (${ item.data.email })`,
        } ))
      },
      (query) => {
        return {
          search: query.term,
          limit : 50,
        }
      }, ...opts)
  }

  /**
   * Api based tag picker
   *
   * @param selector
   * @param multiple
   * @param onReceiveItems
   * @param opts
   */
  function campaignPicker (selector, multiple = true, onReceiveItems = (items) => {}, ...opts) {
    return apiPicker(selector, gh.api.routes.v4.campaigns, multiple, true,
      (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ( {
          id  : item.ID,
          text: `${ item.data.name }`,
        } ))
      },
      (query) => {
        return {
          search: query.term,
        }
      }, ...opts)
  }

  /**
   * Api based email picker
   *
   * @param selector
   * @param multiple
   * @param onReceiveItems
   * @param queryOpts
   * @param opts
   */
  function emailPicker (selector, multiple = false, onReceiveItems = (items) => {}, queryOpts = {}, ...opts) {
    return apiPicker(selector, gh.api.routes.v4.emails, multiple, false, (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ( {
          id  : item.ID,
          text: `${ item.data.title } (${ item.data.status })`,
        } ))
      },
      (query) => {
        return {
          search: query.term,
          ...queryOpts,
        }
      }, ...opts)
  }

  /**
   * Api based funnel picker
   *
   * @param selector
   * @param multiple
   * @param onReceiveItems
   * @param queryOpts
   * @param opts
   */
  function funnelPicker (selector, multiple = false, onReceiveItems = (items) => {}, queryOpts = {}, ...opts) {
    return apiPicker(selector, gh.api.routes.v4.funnels, multiple, false, (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ( {
          id  : item.ID,
          text: `${ item.data.title }`,
        } ))
      },
      (query) => {
        return {
          search: query.term,
          ...queryOpts,
        }
      }, ...opts)
  }

  /**
   * Api based broadcast picker
   *
   * @param selector
   * @param multiple
   * @param onReceiveItems
   * @param queryOpts
   * @param opts
   */
  function broadcastPicker (selector, multiple = false, onReceiveItems = (items) => {}, queryOpts = {}, ...opts) {
    return apiPicker(selector, gh.api.routes.v4.broadcasts, multiple, false, (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ( {
          id  : item.ID,
          text: `${ item.object.data.title } (${ item.date_sent_pretty })`,
        } ))
      },
      (query) => {
        return {
          search: query.term,
          ...queryOpts,
        }
      }, ...opts)
  }

  /**
   * Api based email picker
   *
   * @param selector
   * @param onReceiveItems
   * @param queryOpts
   * @param opts
   */
  function searchesPicker (selector, onReceiveItems = (items) => {}, queryOpts = {}, ...opts) {
    return apiPicker(selector, gh.api.routes.v4.searches, false, false, (data) => {

        onReceiveItems(data.items)

        return data.items.map(item => ( {
          id  : item.id,
          text: item.name,
        } ))
      },
      (query) => {
        return {
          search: query.term,
          ...queryOpts,
        }
      }, ...opts)
  }

  const Select2Picker = (selectEl) => {

    let pickerId = `${ selectEl.id }-picker`

    // don't double init a picker
    if (selectEl.previousElementSibling && selectEl.previousElementSibling.id === pickerId) {
      return
    }

    const convertOpt = selector => [...selectEl.querySelectorAll(selector)].map(opt => ( {
      id  : opt.value,
      text: opt.innerHTML,
    } )).filter( opt => opt.id && opt.text )

    let picker = MakeEl.ItemPicker({
      id          : pickerId,
      fetchOptions: async (search) => {

        let opts = convertOpt('option[value]:not(:empty)')


        if (search) {
          opts = opts.filter(item => item.id.match(search) || item.text.match(search))
        }

        return opts
      },
      selected    : convertOpt('option[selected]'),
      multiple    : selectEl.multiple,
      tags        : selectEl.dataset.tags,
      clearable   : selectEl.multiple || selectEl.dataset.clearable,
      noneSelected: selectEl.dataset.placeholder ?? 'Any...',
      onCreate    : async opt => {
        selectEl.appendChild(MakeEl.makeEl('options', {
          value   : opt,
          selected: true,
        }, opt))
        return {
          id  : opt,
          text: opt,
        }
      },
      onChange    : items => {

        if (!selectEl.multiple) {
          items = [items]
        }

        let selected = items.filter( item => item ).map(item => item.id)

        for (let option of selectEl.options) {
          option.selected = selected.includes(option.value)
        }

        $(selectEl).trigger('change') // must use jQuery for backwards compatibility
        selectEl.dispatchEvent(new Event('change'))

      },
    })

    selectEl.classList.add('hidden', 'picker-initialized')
    selectEl.pickerInitialized = true
    selectEl.insertAdjacentElement('beforebegin', picker)
  }

  function buildPickers () {
    picker('.gh-select2', {})
    tagPicker('.gh-tag-picker', true)
    tagPicker('.gh-single-tag-picker', false)
    emailPicker('.gh-email-picker', false)
    emailPicker('.gh-email-picker-multiple', true)
    apiPicker('.gh-sms-picker', endpoints.sms, false, false)
    contactPicker('.gh-contact-picker')
    contactPicker('.gh-contact-picker-multiple', (items) => {}, {
      multiple: true,
    })
    apiPicker('.gh-benchmark-picker', endpoints.benchmarks, false, false)
    apiPicker('.gh-metakey-picker', endpoints.metakeys, false, false)
    linkPicker('.gh-link-picker')
    metaPicker('.gh-meta-picker')

    document.querySelectorAll('select.gh-select-2-picker').forEach(select => Select2Picker(select))
  }

  $(function () {
    buildPickers()
  })

  $(document).on('gh-init-pickers', function () {
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

  function moveChildren (source, target) {
    while (source.firstChild) {
      target.appendChild(source.firstChild)
    }
  }

  $(document).on('click', '.gh-open-modal', e => {

    e.preventDefault()

    let a = e.currentTarget
    let source = document.querySelector(a.getAttribute('href'))
    let modalProps = JSON.parse(a.dataset.modalProps)
    // let modalProps = {}
    const {
      title = 'Modal',

      ...restModalProps
    } = modalProps

    MakeEl.Modal({
      ...restModalProps,
      onOpen : ({ modal }) => {
        let target = modal.querySelector('.source-content')
        moveChildren(source, target)
      },
      onClose: modal => {
        let target = modal.querySelector('.source-content')
        moveChildren(target, source)
      },
    }, ({ close }) => MakeEl.Fragment([
      MakeEl.Div({ className: 'gh-header modal-header' }, [
        MakeEl.H3({}, 'Modal header'),
        MakeEl.Button({
          className: 'gh-button icon secondary text',
          onClick  : close,
        }, MakeEl.Dashicon('no-alt')),
      ]),
      MakeEl.Div({ className: 'source-content' }),
    ]))

  })

  gh.pickers = {
    picker,
    tagPicker,
    emailPicker,
    apiPicker,
    linkPicker,
    metaPicker,
    userMetaPicker,
    campaignPicker,
    searchesPicker,
    funnelPicker,
    broadcastPicker,
    metaValuePicker,
    contactPicker,
  }

  // Map functions to Groundhogg object.
  gh.nonces = nonces
  gh.endpoints = endpoints

  if (!gh.functions) {
    gh.functions = {}
  }

  /**
   * Set a cookie
   *
   * @param cname
   * @param cvalue
   * @param duration in seconds
   */
  gh.functions.setCookie = (cname, cvalue, duration) => {
    var d = new Date()
    d.setTime(d.getTime() + ( duration * 1000 ))
    var expires = 'expires=' + d.toUTCString()
    document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/'
  }

  /**
   * Retrieve a cookie
   *
   * @param cname name of the cookie
   * @param none default value
   * @returns {string|null}
   */
  gh.functions.getCookie = (cname, none = null) => {
    var name = cname + '='
    var ca = document.cookie.split(';')
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i]
      while (c.charAt(0) == ' ') {
        c = c.substring(1)
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length)
      }
    }
    return none
  }

  function utf8_to_b64 (str) {
    return window.btoa(unescape(encodeURIComponent(str)))
  }

  const base64_json_encode = (stuff) => {
    return utf8_to_b64(JSON.stringify(stuff)).replaceAll('+', '-').replaceAll('/', '_').replaceAll('=', '')
  }

  const assoc2array = (obj, a = 'id', b = 'text') => {
    let array = []
    Object.keys(obj).forEach(key => {
      array.push({
        [a]: key,
        [b]: obj[key],
      })
    })

    return array
  }

  const jsonCopy = stuff => JSON.parse(JSON.stringify(stuff))

  function setNestedValue (obj, path, value) {
    const keys = path.split('.')
    let current = obj

    // Iterate over the keys, except for the last one
    for (let i = 0; i < keys.length - 1; i++) {
      const key = keys[i]

      // If the key doesn't exist, create an empty object
      if (!current[key]) {
        current[key] = {}
      }

      current = current[key]
    }

    // Set the value at the final key
    current[keys[keys.length - 1]] = value
  }

  function getNestedValue (obj, path) {
    const keys = path.split('.')
    let current = obj

    for (let i = 0; i < keys.length; i++) {

      if (!current.hasOwnProperty(keys[i])) {
        return undefined
      }

      current = current[keys[i]]
    }

    return current
  }

  const debounce = (callback, wait) => {
    let timeoutId = null
    return (...args) => {
      window.clearTimeout(timeoutId)
      timeoutId = window.setTimeout(() => {
        callback(...args)
      }, wait)
    }
  }

  const maybeCall = (maybeFunc, ...args) => {
    if (maybeFunc instanceof Function) {
      return maybeFunc(...args)
    }

    return maybeFunc
  }

  const dismissNotice = (id) => Groundhogg.api.ajax({
    action: 'gh_dismiss_notice',
    notice: id,
  })

  gh.functions.utf8_to_b64 = utf8_to_b64
  gh.functions.base64_json_encode = base64_json_encode
  gh.functions.assoc2array = assoc2array
  gh.functions.jsonCopy = jsonCopy
  gh.functions.setNestedValue = setNestedValue
  gh.functions.getNestedValue = getNestedValue
  gh.functions.debounce = debounce
  gh.functions.maybeCall = maybeCall
  gh.functions.dismissNotice = dismissNotice

  $(document).on('click', 'button.hide-panel', e => {

    let btn = e.currentTarget
    let id = btn.dataset.id
    btn.parentElement.remove()

    dismissNotice(id)
  })

  var check, timeout

  /**
   * Only allow to check for nonce refresh every 30 seconds.
   */
  function schedule () {
    check = false
    window.clearTimeout(timeout)
    timeout = window.setTimeout(function () { check = true }, 300000)
  }

  $(function () {
    schedule()
  }).on('heartbeat-send.groundhogg-refresh-nonces', function (e, data) {

    if (check) {
      data['groundhogg-refresh-nonces'] = true
    }

  }).on('heartbeat-tick.groundhogg-refresh-nonces', function (e, data) {
    let newNonces = data.groundhogg_nonces

    if (newNonces) {
      Object.keys(newNonces).forEach(nonce => {
        groundhogg_nonces[nonce] = newNonces[nonce]
        Groundhogg.nonces[nonce] = newNonces[nonce]
      })
    }
  })

  $(document).on('click', '[data-gh-href]', e => {
    console.log('clicked!', e.currentTarget.dataset)
    window.open(e.currentTarget.dataset.ghHref, '_self')
  })

} )(jQuery, groundhogg_nonces, groundhogg_endpoints, Groundhogg)
