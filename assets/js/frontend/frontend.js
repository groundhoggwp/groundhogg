(function (gh) {

  const {

    cookies_enabled,
    cookies,
    page_view_endpoint,
    tracking_enabled,
    base_url,
    form_impression_endpoint,

  } = gh

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
        'X-WP-Nonce': gh._wprest,
      },
      body: JSON.stringify(data),
      ...opts,
    }).then(r => r.json())
  }

  const DURATION = {
    HOUR: 60 * 60 * 1000,
    MINUTE: 60 * 1000,
    DAY: 24 * 60 * 60 * 1000
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

  const defaultPageTracking = {
    pages: [],
    pagesAndTimes: [],
  }

  /**
   * Fetch recently visited pages
   * @return {any}
   */
  const getVisitedPages = () => {
    return JSON.parse(getCookie('groundhogg-page-visits', JSON.stringify(defaultPageTracking)))
  }

  /**
   * Remember the page visit in the cookie
   * @param wasTracked
   */
  const rememberPageVisit = (wasTracked = false) => {
    const url = new URL(window.location.href)

    const pagesVisited = getVisitedPages()

    pagesVisited.pages.push(url.pathname)
    pagesVisited.pagesAndTimes.push({
      page: url.href,
      time: Date.now() / 1000,
      tracked: wasTracked
    })

    setCookie('groundhogg-page-visits', JSON.stringify(pagesVisited), DURATION.HOUR)
  }

  gh = {

    ...gh,
    previousFormImpressions: [],

    pageView () {

      const url = new URL(window.location.href)

      if (cookies_enabled) {

        // Don't run if we recently tracked this page visit
        if (getVisitedPages().pages.includes(url.pathname)) {
          return
        }

      }

      if (this.isLoggedIn || this.hasContactTrackingCookie) {
        apiPost(page_view_endpoint, {
          ref: url.href
        })

        rememberPageVisit(true)
      } else {
        rememberPageVisit(false)
      }
    },

    logFormImpressions () {
      var self = this
      var forms = document.querySelectorAll('.gh-form')
      forms.forEach(function (form, i) {
        var fId = form.querySelector('input[name="gh_submit_form"]').value
        self.formImpression(fId)
      })
    },

    formImpression (id) {

      if (!id || this.previousFormImpressions.indexOf(id) !== -1) {
        return
      }

      apiPost(form_impression_endpoint, {
        ref: window.location.href,
        form_id: id
      }).then(() => {
        this.previousFormImpressions.push(id)
        setCookie(cookies.form_impressions, this.previousFormImpressions.join(), 3 * DURATION.DAY)
      })
    },

    init () {

      this.hasContactTrackingCookie = getCookie(cookies.tracking) !== null
      this.isLoggedIn = document.body.classList.contains('logged-in')

      if (cookies_enabled) {

        var referer = getCookie(cookies.lead_source)

        if (!referer) {
          setCookie(cookies.lead_source, document.referrer, 3 * DURATION.DAY)
        }

        this.previousFormImpressions = getCookie(cookies.form_impressions, '').split(',')

        this.logFormImpressions()
      }

      if (tracking_enabled) {
        this.pageView()
      }
    }
  }

  window.addEventListener('load', () => {
    gh.init()
  })

})(Groundhogg)

