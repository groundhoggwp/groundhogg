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
  const { _wprest } = nonces
  const { consent_cookie_name = 'viewed_cookie_policy', consent_cookie_value = 'yes' } = settings
  const { tracking: tracking_cookie, lead_source, form_impressions, page_visits } = cookies

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
    }).then(r => r.json())
  }

  /**
   * Post data
   *
   * @param data
   * @param opts
   * @returns {Promise<any>}
   */
  async function adminAjax (data = {}, opts = {}) {

    if (! ( data instanceof FormData )) {
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
    return JSON.parse(getCookie(page_visits, JSON.stringify(defaultPageTracking)))
  }

  /**
   * Remember the page visit in the cookie
   * @param wasTracked
   */
  const rememberPageVisit = (wasTracked = false) => {

    // Don't set cookie if cookies are disabled
    if ( unnecessary_cookies_disabled ){
      return;
    }

    const url = new URL(window.location.href)

    const pagesVisited = getVisitedPages()

    pagesVisited.pages.push(url.pathname)
    pagesVisited.pagesAndTimes.push({
      page: url.href,
      time: Date.now() / 1000,
      tracked: wasTracked
    })

    setCookie(page_visits, JSON.stringify(pagesVisited), DURATION.HOUR)
  }

  gh = {

    ...gh,
    previousFormImpressions: [],
    trackingFlag: false,

    pageView () {

      const url = new URL(window.location.href)

      if ( ! unnecessary_cookies_disabled ) {

        // Don't run if we recently tracked this page visit
        if (getVisitedPages().pages.includes(url.pathname)) {
          return
        }

      }

      if (this.isLoggedIn || this.hasContactTrackingCookie) {
        apiPost(tracking + '/pages/', {
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

      apiPost(tracking + '/forms/', {
        ref: window.location.href,
        form_id: id
      }).then(() => {
        this.previousFormImpressions.push(id)
        setCookie(form_impressions, this.previousFormImpressions.join(), 3 * DURATION.DAY)
      })
    },

    init () {

      this.hasContactTrackingCookie = getCookie(tracking_cookie) !== null
      this.isLoggedIn = document.body.classList.contains('logged-in')
      this.has_accepted_cookies = has_accepted_cookies

      // Cookies have not been accepted yet, quit out
      if ( ! has_accepted_cookies && ! this.checkCookieConsent() ) {

        // Listen for cookie acceptance
        document.addEventListener( 'click', () => {
          setTimeout( () => {
            this.onCookiesAccept();
          }, 100 )
        } );

        return
      }

      this.doTracking()
    },

    doTracking () {

      if ( this.initFlag ){
        return;
      }

      this.initFlag = true;

      // Set "unnecessary" cookies
      if (!unnecessary_cookies_disabled) {

        var referer = getCookie(lead_source)

        if (!referer) {
          setCookie(lead_source, document.referrer, 3 * DURATION.DAY)
        }

        this.previousFormImpressions = getCookie(form_impressions, '').split(',')

        this.logFormImpressions()
      }

      this.pageView()
    },

    onCookiesAccept () {

      if ( this.initFlag ){
        return;
      }

      this.has_accepted_cookies = this.checkCookieConsent();

      if ( this.has_accepted_cookies ){
        this.doTracking()
      }
    },

    checkCookieConsent(){
      return getCookie( consent_cookie_name || 'viewed_cookie_policy' ) === ( consent_cookie_value || 'yes' )
    }
  }

  window.addEventListener('load', () => {
    gh.init()
  })
  
  Groundhogg.adminAjax = adminAjax
  Groundhogg.apiPost = apiPost

})(Groundhogg)

