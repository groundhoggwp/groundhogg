( function ($) {

  function ApiError (message, code = 'error') {
    this.name = 'ApiError'
    this.message = message
    this.code = code
  }

  ApiError.prototype = Error.prototype

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
      ...opts,
    })

    let json = await response.json()

    if (!response.ok) {
      console.log(json)
      throw new ApiError(json.message, json.code)
    }

    return json
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

    let json = await response.json()

    if (!response.ok) {
      console.log(json)
      throw new ApiError(json.message, json.code)
    }

    return json
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
      body: JSON.stringify(data),
    })

    let json = await response.json()

    if (!response.ok) {
      console.log(json)
      throw new ApiError(json.message, json.code)
    }

    return json
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
      body: JSON.stringify(data),
    })

    let json = await response.json()

    if (!response.ok) {
      console.log(json)
      throw new ApiError(json.message, json.code)
    }

    return json
  }

  const apiPostFormData = async (url, data, opts = {}) => {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      },
      body: data,
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

    if (!( data instanceof FormData )) {
      const fData = new FormData()

      for (const key in data) {
        if (data.hasOwnProperty(key)) {
          fData.append(key, data[key])
        }
      }

      data = fData
    }

    data.append('gh_admin_ajax_nonce', Groundhogg.nonces._adminajax)

    const response = await fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: data,
      ...opts,
    })

    return response.json()
  }

  const ObjectStore = (route, extra = {}) => ( {
    primaryKey: 'ID',
    getItemFromResponse: (r) => r.item,
    getItemsFromResponse: (r) => r.items,
    getTotalItemsFromResponse: (r) => r.total_items,
    items: [],
    total_items: 0,
    route: route,

    /**
     * get a specific item
     *
     * @param id
     * @returns {{}|*}
     */
    get (id) {
      return this.items.find(item => item[this.primaryKey] == id)
    },

    getItems () {
      return this.items
    },

    has (id) {
      return this.hasItem(id)
    },

    hasItem (id) {
      return this.items.some(item => item[this.primaryKey] == id)
    },

    hasItems (itemIds = []) {

      if (!itemIds || itemIds.length === 0) {
        return this.items.length > 0
      }

      for (let i = 0; i < itemIds.length; i++) {
        const itemId = itemIds[i]
        if (!this.items.find(item => {
          return item[this.primaryKey] == itemId
        })) {
          return false
        }
      }

      return true
    },

    getTotalItems () {
      return this.total_items
    },

    itemsFetched (items) {

      if (!Array.isArray(items)) {
        return
      }

      this.items = [
        ...items, // new items
        ...this.items.filter(item => !items.find(_item => _item[this.primaryKey] == item[this.primaryKey])),
      ]
    },

    find (f = () => {}) {
      return this.items.find(f)
    },

    filter (f = () => { }) {
      return this.items.filter(f)
    },

    async fetchItems (params, opts = {}) {
      return apiGet(this.route, params, opts).then(r => {
        this.total_items = this.getTotalItemsFromResponse(r)
        return this.getItemsFromResponse(r)
      }).then(items => {
        this.itemsFetched(items)
        return items
      })
    },

    /**
     * Fetches items if they are not present in the set
     *
     * @param ids
     * @param opts
     * @returns {Promise<*|({}|*)[]|[]>}
     */
    async maybeFetchItems (ids = [], opts = {}) {

      if ( ( ! ids || ids.length === 0 ) && this.hasItems() ){
        return Promise.resolve(this.items)
      }

      if (ids && ids.length > 0 && ids.every(id => this.hasItem(id))) {
        return Promise.resolve(ids.map(id => this.get(id)))
      }

      const params = {}

      if ( ids && ids.length ){
        params.ID = ids.filter(id => !this.hasItem(id))
      }

      return this.fetchItems(params, opts)
    },

    async fetchItem (id, opts = {}) {
      return apiGet(`${ this.route }/${ id }`, opts).then(r => this.getItemFromResponse(r)).then(item => {
        this.itemsFetched([
          item,
        ])
        return item
      })
    },

    async maybeFetchItem (id, opts = {}) {

      if (this.hasItem(id)) {
        return Promise.resolve(this.get(id))
      }

      return this.fetchItem(id, opts)
    },

    async create (...args) {
      return this.post(...args)
    },

    async createMany (...args) {
      return this.postMany(...args)
    },

    async post (data, opts = {}) {
      return apiPost(this.route, data, opts).then(r => this.getItemFromResponse(r)).then(item => {
        this.itemsFetched([
          item,
        ])
        return item
      })
    },

    async postMany (data, opts = {}) {
      return apiPost(this.route, data, opts).then(r => this.getItemsFromResponse(r)).then(items => {
        this.itemsFetched(items)
        return items
      })
    },

    async update (...args) {
      return this.patch(...args)
    },

    async patch (id, data, opts = {}) {
      return apiPatch(`${ this.route }/${ id }`, data, opts).then(r => this.getItemFromResponse(r)).then(item => {
        this.itemsFetched([
          item,
        ])
        return item
      })
    },

    async patchMany (items, opts = {}) {
      return apiPatch(`${ this.route }`, items, opts).then(r => this.getItemsFromResponse(r)).then(items => {
        this.itemsFetched(items)
        return items
      })
    },

    async duplicate (id, data, opts = {}) {
      return apiPost(`${ this.route }/${ id }/duplicate`, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    async patchMeta (id, data, opts = {}) {
      return apiPatch(`${ this.route }/${ id }/meta`, data, opts).then(r => this.getItemFromResponse(r)).then(item => {
        this.itemsFetched([
          item,
        ])
        return item
      })
    },

    async deleteMeta (id, data, opts = {}) {
      return apiDelete(`${ this.route }/${ id }/meta`, data, opts).then(r => this.getItemFromResponse(r)).then(item => {
        this.itemsFetched([
          item,
        ])
        return item
      })
    },

    async fetchRelationships (id, { other_type, ...rest }, opts = {}) {
      return apiGet(`${ this.route }/${ id }/relationships`, { other_type, ...rest }, opts).
        then(r => this.getItemsFromResponse(r))
    },

    async createRelationships (id, data, opts = {}) {
      return apiPost(`${ this.route }/${ id }/relationships`, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    async deleteRelationships (id, data, opts = {}) {
      return apiDelete(`${ this.route }/${ id }/relationships`, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    async delete (id) {

      if (typeof id == 'object') {
        return this.deleteMany(id)
      }

      return apiDelete(`${ this.route }/${ id }`).then(r => {

        this.items = [
          ...this.items.filter(item => item[this.primaryKey] != id),
        ]

        return r
      })
    },

    async count (params) {
      return apiGet(`${ this.route }`, {
        count: true,
        ...params,
      }).then(r => r.total_items)
    },

    async deleteMany (query) {

      return apiDelete(`${ this.route }`, query).then(r => {
        let items = this.getItemsFromResponse(r)
        this.items = [
          ...this.items.filter(item => !items.includes(item[this.primaryKey])),
        ]
        return r
      })
    },

    ...extra,
  } )

  Groundhogg.api.post = apiPost
  Groundhogg.api.postFormData = apiPostFormData
  Groundhogg.api.get = apiGet
  Groundhogg.api.patch = apiPatch
  Groundhogg.api.delete = apiDelete
  Groundhogg.api.ajax = adminAjax

  Groundhogg.stores = {

    options: {

      items: {},
      route: Groundhogg.api.routes.v4.options,

      get (opt, _default = false) {

        if (Array.isArray(opt)) {

          let opts = {}

          opt.forEach(_opt => opts[_opt] = this.items[_opt])

          return opts

        }

        return this.items[opt] ? this.items[opt] : _default
      },

      fetch (data = [], opts = {}) {

        let req = {}

        data.forEach(opt => req[opt] = 1)

        return apiGet(this.route, req, opts).then(r => {

          this.items = {
            ...this.items,
            ...r.items,
          }

          return r.items

        })
      },

      post (data = {}, opts = {}) {

        return apiPatch(this.route, data, opts).then(r => {

          this.items = {
            ...this.items,
            ...r.items,
          }

          return r.items

        })
      },

      patch (data = {}, opts = {}) {

        return apiPatch(this.route, data, opts).then(r => {

          this.items = {
            ...this.items,
            ...r.items,
          }

          return r.items

        })
      },

      delete (data = {}, opts = {}) {

        return apiDelete(this.route, data, opts).then(r => {

          data.forEach(opt => {
            delete this.items[opt]
          })

        })
      },

    },

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

        return await apiPost(`${ this.route }/validate`, maybeTags).then(r => {

          if (!r.items) {
            return []
          }

          self.items = [
            ...r.items, // new items
            ...self.items.filter(item => !r.items.find(_item => _item[this.primaryKey] == item[this.primaryKey])),
          ]

          return r.items
        })
      },

      preloadTags () {

        var self = this

        return apiGet(this.route, {
          limit: self.limit,
          offet: self.offset,
        }).then(data => {
          if (!data.items) {
            return
          }

          Object.assign(self.items, data.items)

          if (data.items.length == self.limit) {
            self.offset += self.limit
            self.preloadTags()
          }

        })
      },
    }),
    forms: ObjectStore(Groundhogg.api.routes.v4.forms),
    contacts: ObjectStore(Groundhogg.api.routes.v4.contacts, {
      async fetchFiles (id, opts = {}) {
        return apiGet(`${ this.route }/${ id }/files`, {}, opts).then(r => this.getItemsFromResponse(r))
      },
    }),
    events: ObjectStore(Groundhogg.api.routes.v4.events),
    event_queue: ObjectStore(Groundhogg.api.routes.v4.event_queue),
    page_visits: ObjectStore(Groundhogg.api.routes.v4.page_visits),
    activity: ObjectStore(Groundhogg.api.routes.v4.activity),
    campaigns: ObjectStore(Groundhogg.api.routes.v4.campaigns),
    submissions: ObjectStore(Groundhogg.api.routes.v4.submissions),
    funnels: ObjectStore(Groundhogg.api.routes.v4.funnels, {

      async addContacts ({ query, funnel_id, step_id }, opts = {}) {
        return apiPost(`${ this.route }/${ funnel_id }/start`, {
          query,
          step_id,
          funnel_id,
        }, opts).then(d => d.added)
      },

      async commit (id, data, opts = {}) {
        return apiPost(`${ this.route }/${ id }/commit`, data, opts).
          then(r => this.getItemFromResponse(r)).
          then(item => {

            this.itemsFetched([
              item,
            ])
            return item
          })
      },

      isStartingStep (funnelId, stepId, checkEdited = false) {
        return !this.getPrecedingSteps(funnelId, stepId, checkEdited).find(_step => _step.data.step_group == 'action')
      },

      getSteps (funnelId, checkEdited = false) {
        const funnel = funnelId ? this.items.find(f => f.ID == funnelId) : this.item
        return checkEdited && funnel.meta.edited ? funnel.meta.edited.steps : funnel.steps
      },

      getFunnelAndStep (funnelId, stepId, checkEdited = false) {
        const funnel = funnelId ? this.items.find(f => f.ID == funnelId) : this.item
        const step = checkEdited && funnel.meta.edited
          ? funnel.meta.edited.steps.find(s => s.ID == stepId)
          : funnel.steps.find(s => s.ID == stepId)
        return { funnel, step }
      },

      getProceedingSteps (funnelId, stepId, checkEdited = false) {
        const { step, funnel } = this.getFunnelAndStep(funnelId, stepId, checkEdited)
        return funnel.steps.filter((_step) => _step.data.step_order > step.data.step_order).
          sort((a, b) => a.data.step_order - b.data.step_order)
      },

      getPrecedingSteps (funnelId, stepId, checkEdited = false) {
        const { step, funnel } = this.getFunnelAndStep(funnelId, stepId, checkEdited)
        return funnel.steps.filter((_step) => _step.data.step_order < step.data.step_order).
          sort((a, b) => a.data.step_order - b.data.step_order)
      },
    }),
    emails: ObjectStore(Groundhogg.api.routes.v4.emails),
    broadcasts: ObjectStore(Groundhogg.api.routes.v4.broadcasts),
    notes: ObjectStore(Groundhogg.api.routes.v4.notes),
    tasks: ObjectStore(Groundhogg.api.routes.v4.tasks, {
      complete (id) {
        return apiPatch(`${ this.route }/${ id }/complete`).then(r => this.getItemFromResponse(r)).then(item => {

          this.itemsFetched([
            item,
          ])
          return item
        })
      },
      incomplete (id) {
        return apiPatch(`${ this.route }/${ id }/incomplete`).then(r => this.getItemFromResponse(r)).then(item => {

          this.itemsFetched([
            item,
          ])
          return item
        })
      },
    }),
    searches: ObjectStore(Groundhogg.api.routes.v4.searches, {
      primaryKey: 'id',
    }),
    posts: ObjectStore(Groundhogg.api.routes.posts, {
      primaryKey: 'id',
    }),
  }

  Groundhogg.createStore = (id, route = '', extra = {}) => {
    const store = ObjectStore(route, extra)
    Groundhogg.stores[id] = store
    return store
  }

} )(jQuery)
