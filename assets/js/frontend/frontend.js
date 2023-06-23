(function (gh) {

  const {

    unnecessary_cookies_disabled,
    has_accepted_cookies,
    settings,
    cookies,
    routes,
    base_url,
    nonces,
  } = gh

  const { tracking, ajax } = routes
  const {
    consent_cookie_name = 'viewed_cookie_policy',
    consent_cookie_value = 'yes',
  } = settings
  const {
    tracking: tracking_cookie,
    lead_source,
    form_impressions,
    page_visits,
  } = cookies
  let { _wprest } = nonces

  /**
   * Post data
   *
   * @param url
   * @param data
   * @param opts
   * @returns {Promise<any>}
   */
  function unauthenticatedApiPost (url = '', data = {}, opts = {}) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
      ...opts,
    }).then(r => r.json()).catch(err => {
      console.log(err)
    })
  }

  /**
   * Post data
   *
   * @param url
   * @param data
   * @param opts
   * @returns {Promise<any>}
   */
  function apiPost (url = '', data = {}, opts = {}) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': _wprest,
      },
      body: JSON.stringify(data),
      ...opts,
    }).then(r => {
      if (r.ok) {
        return r
      }
      return unauthenticatedApiPost(url, data, opts)
    }).then(r => r.json()).catch(err => {
      console.log(err)
    })
  }

  /**
   * Post data
   *
   * @param data
   * @param opts
   * @returns {Promise<any>}
   */
  async function adminAjax (data = {}, opts = {}) {

    if (!(data instanceof FormData)) {
      const fData = new FormData()

      for (const key in data) {
        if (data.hasOwnProperty(key)) {
          fData.append(key, data[key])
        }
      }

      data = fData
    }

    const response = await fetch(ajax, {
      method: 'POST',
      credentials: 'same-origin',
      body: data,
      ...opts,
    })

    return response.json()
  }

  const DURATION = {
    HOUR: 60 * 60 * 1000,
    MINUTE: 60 * 1000,
    DAY: 24 * 60 * 60 * 1000,
  }

  /**
   * Set a cookie
   *
   * @param cname
   * @param cvalue
   * @param duration
   */
  const setCookie = (cname, cvalue, duration) => {
    var d = new Date()
    d.setTime(d.getTime() + (duration))
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
  const getCookie = (cname, none = null) => {
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

  const defaultPageTracking = []
  /**
   * Fetch recently visited pages
   * @return {any}
   */
  const getVisitedPages = () => {
    return JSON.parse(
      getCookie(page_visits, JSON.stringify(defaultPageTracking)))
  }

  /**
   * Remember the page visit in the cookie
   * @param wasTracked
   */
  const rememberPageVisit = (wasTracked = false) => {

    // Don't set cookie if cookies are disabled
    if (unnecessary_cookies_disabled) {
      return
    }

    const url = new URL(window.location.href)

    let pagesVisited = getVisitedPages()

    if (!Array.isArray(pagesVisited)) {
      pagesVisited = []
    }

    // don't let the cookie get too big
    if (pagesVisited.length >= 50) {
      pagesVisited.shift()
    }

    let unix = Math.floor(Date.now() / 1000)
    let visited = pagesVisited.find(p => p[0] === url.pathname)

    if (visited) {

      if (visited[1].length >= 5) {
        visited[1].shift()
      }

      visited[1].push([unix, wasTracked ? 1 : 0])

    } else {

      pagesVisited.push([
        url.pathname,
        [[unix, wasTracked ? 1 : 0]],
      ])

    }

    setCookie(page_visits, JSON.stringify(pagesVisited), DURATION.HOUR)
  }

  gh = {

    ...gh,
    previousFormImpressions: [],
    trackingFlag: false,

    pageView () {

      const apiPageView = (ref) => {
        let func = this.isLoggedIn ? apiPost : unauthenticatedApiPost

        func(tracking + '/pages/', {
          ref,
        })
      }

      if (this.isLoggedIn || this.hasContactTrackingCookie) {

        apiPageView(window.location.href)

        rememberPageVisit(true)
      } else {
        rememberPageVisit(false)
      }
    },

    logFormImpressions () {
      let self = this
      let forms = document.querySelectorAll('.gh-form')
      forms.forEach(function (form, i) {
        let formId = form.dataset.id

        if (formId) {
          self.formImpression(formId)
          return
        }

        let field = form.querySelector('input[name="gh_submit_form"]')

        if (field) {
          self.formImpression(field.value)
        }
      })
    },

    formImpression (id) {

      if (!id || this.previousFormImpressions.indexOf(id) !== -1) {
        return
      }

      let func = this.isLoggedIn ? apiPost : unauthenticatedApiPost

      func(tracking + '/forms/', {
        ref: window.location.href,
        form_id: id,
      }).then(() => {
        this.previousFormImpressions.push(id)
        setCookie(form_impressions, this.previousFormImpressions.join(),
          3 * DURATION.DAY)
      })
    },

    init () {

      this.hasContactTrackingCookie = getCookie(tracking_cookie) !== null
      this.isLoggedIn = document.body.classList.contains('logged-in')
      this.has_accepted_cookies = has_accepted_cookies

      // Cookies have not been accepted yet, quit out
      if (!has_accepted_cookies && !this.checkCookieConsent()) {

        // Listen for cookie acceptance
        document.addEventListener('click', () => {
          setTimeout(() => {
            this.onCookiesAccept()
          }, 100)
        })

        return
      }

      this.doTracking()
    },

    doTracking () {

      if (this.initFlag) {
        return
      }

      this.initFlag = true

      // Set "unnecessary" cookies
      if (!unnecessary_cookies_disabled) {

        var referer = getCookie(lead_source)

        if (!referer) {
          setCookie(lead_source, document.referrer, 3 * DURATION.DAY)
        }

        this.previousFormImpressions = getCookie(form_impressions, '').
        split(',')

        this.logFormImpressions()
      }

      try {
        this.pageView()
      } catch (e) {
        console.log(e)
      }
    },

    onCookiesAccept () {

      if (this.initFlag) {
        return
      }

      this.has_accepted_cookies = this.checkCookieConsent()

      if (this.has_accepted_cookies) {
        this.doTracking()
      }
    },

    checkCookieConsent () {
      return getCookie(consent_cookie_name || 'viewed_cookie_policy') ===
        (consent_cookie_value || 'yes')
    },
  }

  window.addEventListener('load', () => {
    gh.init()
  })

  Groundhogg.adminAjax = adminAjax
  Groundhogg.apiPost = apiPost
  Groundhogg.unauthenticatedApiPost = unauthenticatedApiPost

})(Groundhogg)

