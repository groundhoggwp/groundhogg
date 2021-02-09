(function (gh) {
  gh = {
    ...gh,
    previousFormImpressions: [],

    setCookie: function (cname, cvalue, exdays) {
      var d = new Date()
      d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000))
      var expires = 'expires=' + d.toUTCString()
      document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/'
    },

    getCookie: function (cname) {
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
      return null
    },

    sendAjax: function ({ url, type, data, success, error }) {
      var self = this

      var xhr = new XMLHttpRequest()

      xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
          if (xhr.status === 200) {
            if (typeof success === 'function') {
              success(xhr.response)
            }
          } else {
            if (typeof error === 'function') {
              error(xhr.response)
            }
          }
        }
      }

      xhr.open(type, url, true)

      xhr.setRequestHeader('Content-Type', 'application/json')
      xhr.setRequestHeader('X-WP-Nonce', self._wpnonce)

      xhr.send(JSON.stringify(data))
    },

    pageView: function () {
      var self = this

      // If tracking is not enabled or there is no tracking regex for pages.
      if (!this.tracking_enabled || !this.tracked_pages_regex) {
        return
      }

      // create a regex based on the URLs provided.
      var regex = new RegExp(this.tracked_pages_regex)

      // Compare against the base URL of the site
      var location = window.location.href.replace(self.base_url, '')

      // If the page matches one of the global regex options
      if (location.match(regex)) {
        self.sendAjax({
          type: 'post',
          url: self.page_view_endpoint,
          data: { ref: location, _ghnonce: self._ghnonce }
        })
      }
    },

    logFormImpressions: function () {
      var self = this
      var forms = document.querySelectorAll('.gh-form')
      forms.forEach(function (form, i) {
        var fId = form.querySelector('input[name="gh_submit_form"]').value
        self.formImpression(fId)
      })
    },

    formImpression: function (id) {
      var self = this

      if (!id) {
        return
      }

      if (this.previousFormImpressions.indexOf(id) !== -1) {
        return
      }

      self.sendAjax({
        type: 'post',
        url: self.form_impression_endpoint,
        data: { ref: window.location.href, form_id: id, _ghnonce: self._ghnonce },
        success: function (response) {
          self.previousFormImpressions.push([id])
          self.setCookie(self.cookies.form_impression, self.previousFormImpressions.join(), 3)
        },
        error: function (response) {}
      })
    },

    init: function () {

      if (this.cookies_enabled) {
        var referer = this.getCookie(this.cookies.lead_source)

        if (!referer) {
          this.setCookie(this.cookies.lead_source, document.referrer, 3)
        }

        var previousFormImpressions = this.getCookie(this.cookies.form_impressions)

        if (!previousFormImpressions) {
          previousFormImpressions = ''
        }

        this.logFormImpressions()
        this.previousFormImpressions = previousFormImpressions.split(',')
      }

      this.pageView()
    }
  }

  gh.init()

})(Groundhogg)

