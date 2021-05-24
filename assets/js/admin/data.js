(function ($) {

  /**
   * Fetch stuff from the API
   * @param route
   * @param params
   */
  async function apiFetch (route, params) {
    const response = await fetch(route + '?' + $.param(params), {
      headers: {
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      }
    })

    return response.json()
  }

  /**
   * Post data
   *
   * @param url
   * @param data
   * @returns {Promise<any>}
   */
  async function apiPost (url = '', data = {}) {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      },
      body: JSON.stringify(data)
    })
    return response.json()
  }

  const ObjectStore = (route, extra = {}) => ({
    items: [],
    item: {},
    route: route,

    async fetch (params) {

      var self = this

      return apiFetch(this.route, params).then(r => {
        self.items = r.items
      })
    },

    async post (data) {
      var self = this

      return apiPost(this.route, data).then(r => {
        self.items.push(r.item)
      })
    },

    async patch (id, data) {
      const response = await fetch(this.route + '/' + id, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': Groundhogg.nonces._wprest,
        },
        body: JSON.stringify(data)
      })
      return response.json()
    },

    async delete (id) {
      const response = await fetch(this.route + '/' + id, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': Groundhogg.nonces._wprest,
        }
      })
      return response.json()
    },

    ...extra
  })

  Groundhogg.api.post = apiPost
  Groundhogg.api.get = apiFetch

  Groundhogg.stores = {
    tags: ObjectStore(Groundhogg.api.routes.v4.tags, {

      limit: 100,
      offset: 0,

      /**
       * List if IDs or new tag names added by select2
       *
       * @param maybeTags
       * @returns {Promise<*>}
       */
      async validate (maybeTags) {
        var self = this

        return await apiPost(`${this.route}/validate`, maybeTags)
          .then(data => {

            if (!data.items) {
              return []
            }

            data.items.forEach(tag => {
              if (self.items.findIndex(t => t.ID === tag.ID) === -1) {
                self.items.push(tag)
              }
            })

            return data.items
          })
      },

      preloadTags () {

        var self = this

        return apiFetch(this.route, {
          limit: self.limit,
          offet: self.offset
        }).then(data => {
          if (!data.items) {
            return
          }

          Object.assign(self.items, data.items)

          if (data.items.length === self.limit) {
            self.offset += self.limit
            self.preloadTags()
          }

        })
      },
    }),
    contacts: ObjectStore(Groundhogg.api.routes.v4.contacts),
    emails: ObjectStore(Groundhogg.api.routes.v4.emails),
  }
})(jQuery)