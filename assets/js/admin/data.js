(function ($) {

  /**
   * Fetch stuff from the API
   * @param route
   * @param params
   * @param opts
   */
  async function apiGet (route, params = {}, opts = {}) {

    const response = await fetch(route + '?' + $.param(params), {
      ...opts,
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
   * @param opts
   * @returns {Promise<any>}
   */
  async function apiPost (url = '', data = {}, opts = {}) {
    const response = await fetch(url, {
      ...opts,
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

    /**
     * get a specific item
     *
     * @param id
     * @returns {{}|*}
     */
    get (id) {

      let item

      if (this.item.ID === id) {
        item = this.item
      } else {
        item = this.items.find(item => item.ID === id)
      }

      return item
    },

    getItems () {
      return this.items
    },

    hasItem (id) {
      return this.item.ID === id || this.items.find(item => item.ID === id)
    },

    hasItems (itemIds) {

      if (!itemIds) {
        return this.items.length > 0
      }

      for (let i = 0; i < itemIds.length; i++) {
        const itemId = itemIds[i]
        if (!this.items.find(item => {
          return item.ID === itemId
        })) {
          return false
        }
      }

      return true
    },

    async fetchItems (params) {

      var self = this

      return apiGet(this.route, params).then(r => {
        self.items = [
          ...r.items, // new items
          ...self.items.filter(item => !r.items.find(_item => _item.ID === item.ID))
        ]
        return r
      })
    },

    itemsFetched ( items ) {
      this.items = [
        ...items, // new items
        ...this.items.filter(item => !items.find(_item => _item.ID === item.ID))
      ]
    },

    async fetchItem (id) {

      var self = this

      return apiGet(`${this.route}/${id}`).then(r => {
        self.item = r.item
        self.items = [
          r.item,
          ...self.items.filter( item => item.ID !== r.item.ID ),
        ]
        return r
      })
    },

    async post (data) {
      var self = this

      return apiPost(this.route, data).then(r => {
        self.items.push(r.item)
        return r
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
  Groundhogg.api.get = apiGet

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
          .then(r => {

            if (!r.items) {
              return []
            }

            self.items = [
              ...r.items, // new items
              ...self.items.filter(item => !r.items.find(_item => _item.ID === item.ID))
            ]

            return r.items
          })
      },

      preloadTags () {

        var self = this

        return apiGet(this.route, {
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