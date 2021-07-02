(function ($) {

  /**
   * Fetch stuff from the API
   * @param route
   * @param params
   * @param opts
   */
  async function apiGet (route, params = {}, opts = {}) {

    const response = await fetch(route + '?' + $.param(params), {
      headers: {
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      },
      ...opts
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
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      },
      body: JSON.stringify(data),
      ...opts,
    })
    return response.json()
  }

  /**
   * Post data
   *
   * @param data
   * @param opts
   * @returns {Promise<any>}
   */
  async function adminAjax (data = {}, opts = {}) {

    const fData = new FormData()

    for (const key in data) {
      if (data.hasOwnProperty(key)) {
        fData.append(key, data[key])
      }
    }

    const response = await fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: fData,
      ...opts,
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
  async function apiPatch (url = '', data = {}, opts = {}) {
    const response = await fetch(url, {
      ...opts,
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      },
      body: JSON.stringify(data)
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
  async function apiDelete (url = '', data = {}, opts = {}) {
    const response = await fetch(url, {
      ...opts,
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      },
      body: JSON.stringify(data)
    })
    return response.json()
  }

  const ObjectStore = (route, extra = {}) => ({
    primaryKey: 'ID',
    getItemFromResponse: (r) => r.item,
    getItemsFromResponse: (r) => r.items,
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

      if (this.item[this.primaryKey] === id) {
        item = this.item
      } else {
        item = this.items.find(item => item[this.primaryKey] === id)
      }

      return item
    },

    getItems () {
      return this.items
    },

    hasItem (id) {
      return this.item[this.primaryKey] === id || this.items.find(item => item[this.primaryKey] === id)
    },

    hasItems (itemIds) {

      if (!itemIds) {
        return this.items.length > 0
      }

      for (let i = 0; i < itemIds.length; i++) {
        const itemId = itemIds[i]
        if (!this.items.find(item => {
          return item[this.primaryKey] === itemId
        })) {
          return false
        }
      }

      return true
    },

    itemsFetched (items) {
      this.items = [
        ...items, // new items
        ...this.items.filter(item => !items.find(_item => _item[this.primaryKey] === item[this.primaryKey]))
      ]
    },

    async fetchItems (params) {
      return apiGet(this.route, params)
        .then(r => this.getItemsFromResponse(r))
        .then(items => {
          this.itemsFetched(items)
          return items
        })
    },

    async fetchItem (id) {
      return apiGet(`${this.route}/${id}`)
        .then(r => this.getItemFromResponse(r))
        .then(item => {
          this.item = item
          this.itemsFetched([
            item
          ])
          return item
        })
    },

    async post (data, opts = {}) {
      return apiPost(this.route, data, opts)
        .then(r => this.getItemFromResponse(r))
        .then(item => {
          this.itemsFetched([
            item
          ])
          return item
        })
    },

    async patch (id, data, opts = {}) {
      return apiPatch(`${this.route}/${id}`, data, opts)
        .then(r => this.getItemFromResponse(r))
        .then(item => {
          this.item = item
          this.itemsFetched([
            item
          ])
          return item
        })
    },

    async delete (id) {
      return apiDelete(`${this.route}/${id}`)
        .then(r => {

          if (this.item[this.primaryKey] === id) {
            this.item = {}
          }

          this.items = [
            ...this.items.filter(item => item[this.primaryKey] !== id),
          ]
        })
    },

    ...extra,
  })

  Groundhogg.api.post = apiPost
  Groundhogg.api.get = apiGet
  Groundhogg.api.patch = apiPatch
  Groundhogg.api.delete = apiDelete
  Groundhogg.api.ajax = adminAjax

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
              ...self.items.filter(item => !r.items.find(_item => _item[this.primaryKey] === item[this.primaryKey]))
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
    campaigns: ObjectStore(Groundhogg.api.routes.v4.campaigns),
    emails: ObjectStore(Groundhogg.api.routes.v4.emails),
    searches: ObjectStore(Groundhogg.api.routes.v4.searches, {
      primaryKey: 'id'
    }),
  }

  Groundhogg.createStore = (id, route = '', extra = {}) => {
    const store = ObjectStore(route, extra)
    Groundhogg.stores[id] = store
    return store
  }

})(jQuery)