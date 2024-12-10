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
        'X-WP-Nonce': wpApiSettings.nonce,
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
        'X-WP-Nonce': wpApiSettings.nonce,
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
        'X-WP-Nonce': wpApiSettings.nonce,
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
        'X-WP-Nonce': wpApiSettings.nonce,
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
        'X-WP-Nonce': wpApiSettings.nonce,
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
    getItemsFromResponse: (r) => r.items ?? [],
    getTotalItemsFromResponse: (r) => r.total_items,
    items: [],
    total_items: 0,
    route: route,

    /**
     * This stores result sets for queries using the JSON query sent and the IDs of the items
     * It doesn't store the whole object but instead when retrieved uses the ID to get the relevant object from the store
     *
     * {
     *   "some json query" : [1, 2, 3, 4]...
     * }
     */
    cache: {},

    /**
     * Get a result set from the cache
     *
     * @param query
     * @returns {*|*[]}
     */
    getResultsFromCache (query = {}) {
      let [results=[],totalItems=0] = this.cache[JSON.stringify(query)] ?? []
      this.total_items = totalItems
      return results.map(id => this.get(id))
    },

    /**
     * Clear the results cache
     *
     * @param key
     */
    clearResultsCache (key = '') {
      this.cache = {}
    },

    /**
     * Set a result set in the cache
     *
     * @param query
     * @param results
     * @param totalItems
     */
    setInResultsCache (query, results = [], totalItems = 0 ) {
      this.cache[JSON.stringify(query)] = [results.map(item => item[this.primaryKey]), totalItems]
    },

    /**
     * If the cache has cached results
     *
     * @param query
     * @returns {boolean}
     */
    hasCachedResults (query) {
      return JSON.stringify(query) in this.cache
    },

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

    /**
     * Adds and merges the items that were fetch into the stored items
     *
     * @param items
     */
    itemsFetched (items) {

      if (!Array.isArray(items)) {
        return
      }

      this.items = [
        ...items, // new items
        ...this.items.filter(item => !items.find(
          _item => _item[this.primaryKey] == item[this.primaryKey])),
      ]
    },

    /**
     * Clear items from this store
     */
    clearItems () {
      this.items = []
    },

    /**
     * Find an item based on a predicate
     *
     * @param f
     * @returns {*}
     */
    find (f = () => {}) {
      return this.items.find(f)
    },

    /**
     * Filter the items
     *
     * @param f
     * @returns {*[]}
     */
    filter (f = () => {}) {
      return this.items.filter(f)
    },

    /**
     * Find items that match a specific query
     *
     * @param params
     * @param opts
     * @returns {Promise<*|*[]>}
     */
    async fetchItems (params, opts = {}) {

      if (this.hasCachedResults(params)) {
        return this.getResultsFromCache(params)
      }

      return apiGet(this.route, params, opts).then(r => {
        this.total_items = this.getTotalItemsFromResponse(r)
        return this.getItemsFromResponse(r)
      }).then(items => {
        this.itemsFetched(items)
        this.setInResultsCache(params, items, this.total_items)
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

      const {
        param = this.primaryKey,
        ...otherOpts
      } = opts

      // No items requested, return all present items
      if (( !ids || ids.length === 0 ) && this.hasItems()) {
        return this.items
      }

      // All items are present
      if (ids && ids.length > 0 && ids.every(id => this.hasItem(id))) {
        return ids.map(id => this.get(id))
      }

      // Fetch some of the items
      if (!ids || !ids.length) {
        return this.fetchItems({}, opts)
      }

      // Only fetch missing items
      let missingIds = ids.filter(id => !this.hasItem(id))

      const params = {
        [param]: missingIds,
        limit: missingIds.length,
      }

      return this.fetchItems(params, otherOpts)
    },

    /**
     * Fetch a specific item by ID
     *
     * @param id
     * @param opts
     * @returns {Promise<*>}
     */
    async fetchItem (id, opts = {}) {
      return apiGet(`${ this.route }/${ id }`, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    /**
     * If the item is in the store, return it right away
     *
     * @param id
     * @param opts
     * @returns {Promise<Awaited<{}|*>|*>}
     */
    async maybeFetchItem (id, opts = {}) {

      if (this.hasItem(id)) {
        return this.get(id)
      }

      return this.fetchItem(id, opts)
    },

    /**
     * Alias for post
     *
     * @param args
     * @returns {Promise<*>}
     */
    async create (...args) {
      return this.post(...args)
    },

    /**
     * Alias for PostMany
     *
     * @param args
     * @returns {Promise<*>}
     */
    async createMany (...args) {
      return this.postMany(...args)
    },

    /**
     * Create a new item
     * Also clears the results cache
     *
     * @param data
     * @param opts
     * @returns {Promise<*>}
     */
    async post (data, opts = {}) {
      return apiPost(this.route, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          // Clear the results cache when adding new items
          this.clearResultsCache()
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    /**
     * Create many items
     *
     * @param data
     * @param opts
     * @returns {Promise<*>}
     */
    async postMany (data, opts = {}) {
      return apiPost(this.route, data, opts).
        then(r => this.getItemsFromResponse(r)).
        then(items => {
          this.clearResultsCache()
          this.itemsFetched(items)
          return items
        })
    },

    /**
     * Alias for patch
     *
     * @param args
     * @returns {Promise<*>}
     */
    async update (...args) {
      return this.patch(...args)
    },

    /**
     * Update an item with new data
     *
     * @param id
     * @param data
     * @param opts
     * @returns {Promise<*>}
     */
    async patch (id, data, opts = {}) {
      return apiPatch(`${ this.route }/${ id }`, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    /**
     * Update many items with new data
     *
     * @param items
     * @param opts
     * @returns {Promise<*>}
     */
    async patchMany (items, opts = {}) {
      return apiPatch(`${ this.route }`, items, opts).
        then(r => this.getItemsFromResponse(r)).
        then(items => {
          this.itemsFetched(items)
          return items
        })
    },

    /**
     * Duplicate an item
     *
     * @param id
     * @param data
     * @param opts
     * @returns {Promise<*>}
     */
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

    /**
     * Update the meta of an item
     *
     * @param id
     * @param data
     * @param opts
     * @returns {Promise<*>}
     */
    async patchMeta (id, data, opts = {}) {
      return apiPatch(`${ this.route }/${ id }/meta`, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    /**
     * Delete the meta of an item
     *
     * @param id
     * @param data
     * @param opts
     * @returns {Promise<*>}
     */
    async deleteMeta (id, data, opts = {}) {
      return apiDelete(`${ this.route }/${ id }/meta`, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          return item
        })
    },

    async fetchRelationships (id, { other_type, ...rest }, opts = {}) {
      return apiGet(`${ this.route }/${ id }/relationships`,
        { other_type, ...rest }, opts).
        then(r => this.getItemsFromResponse(r))
    },

    async createRelationships (id, data, opts = {}) {
      return apiPost(`${ this.route }/${ id }/relationships`, data, opts).
        then(r => this.getItemFromResponse(r)).
        then(item => {
          this.itemsFetched([
            item,
          ])
          this.clearResultsCache()
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
          this.clearResultsCache()
          return item
        })
    },

    /**
     * Get the total count of items that match a query
     *
     * @param params
     * @returns {Promise<any>}
     */
    async count (params) {
      return apiGet(`${ this.route }`, {
        count: true,
        ...params,
      }).then(r => r.total_items)
    },

    /**
     * Delete an item
     *
     * @param id
     * @returns {Promise<*>}
     */
    async delete (id) {

      this.clearResultsCache()

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

    /**
     * Delete many items
     *
     * @param query
     * @returns {Promise<*>}
     */
    async deleteMany (query) {

      this.clearResultsCache()

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
  Groundhogg.api.ApiError = ApiError

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
            ...self.items.filter(item => !r.items.find(
              _item => _item[this.primaryKey] == item[this.primaryKey])),
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
        return apiGet(`${ this.route }/${ id }/files`, {}, opts).
          then(r => this.getItemsFromResponse(r))
      },
    }),
    events: ObjectStore(Groundhogg.api.routes.v4.events),
    event_queue: ObjectStore(Groundhogg.api.routes.v4.event_queue),
    page_visits: ObjectStore(Groundhogg.api.routes.v4.page_visits),
    activity: ObjectStore(Groundhogg.api.routes.v4.activity),
    campaigns: ObjectStore(Groundhogg.api.routes.v4.campaigns),
    submissions: ObjectStore(Groundhogg.api.routes.v4.submissions),
    funnels: ObjectStore(Groundhogg.api.routes.v4.funnels, {

      async addContacts ({ query, funnel_id, step_id, ...rest }, opts = {}) {
        return apiPost(`${ this.route }/${ funnel_id }/start`, {
          query,
          step_id,
          funnel_id,
          ...rest,
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
        return !this.getPrecedingSteps(funnelId, stepId, checkEdited).
          find(_step => _step.data.step_group == 'action')
      },

      getSteps (funnelId, checkEdited = false) {
        const funnel = funnelId
          ? this.items.find(f => f.ID == funnelId)
          : this.item
        return checkEdited && funnel.meta.edited
          ? funnel.meta.edited.steps
          : funnel.steps
      },

      getFunnelAndStep (funnelId, stepId, checkEdited = false) {
        const funnel = funnelId
          ? this.items.find(f => f.ID == funnelId)
          : this.item
        const step = checkEdited && funnel.meta.edited
          ? funnel.meta.edited.steps.find(s => s.ID == stepId)
          : funnel.steps.find(s => s.ID == stepId)
        return { funnel, step }
      },

      getProceedingSteps (funnelId, stepId, checkEdited = false) {
        const { step, funnel } = this.getFunnelAndStep(funnelId, stepId,
          checkEdited)
        return funnel.steps.filter(
            (_step) => _step.data.step_order > step.data.step_order).
          sort((a, b) => a.data.step_order - b.data.step_order)
      },

      getPrecedingSteps (funnelId, stepId, checkEdited = false) {
        const { step, funnel } = this.getFunnelAndStep(funnelId, stepId,
          checkEdited)
        return funnel.steps.filter(
            (_step) => _step.data.step_order < step.data.step_order).
          sort((a, b) => a.data.step_order - b.data.step_order)
      },
    }),
    emails: ObjectStore(Groundhogg.api.routes.v4.emails, {
      send (id, data) {
        return apiPost(`${ this.route }/${ id }/send`, data)
      },
    }),
    broadcasts: ObjectStore(Groundhogg.api.routes.v4.broadcasts),
    notes: ObjectStore(Groundhogg.api.routes.v4.notes),
    tasks: ObjectStore(Groundhogg.api.routes.v4.tasks, {
      complete (id) {
        return apiPatch(`${ this.route }/${ id }/complete`).
          then(r => this.getItemFromResponse(r)).
          then(item => {

            this.itemsFetched([
              item,
            ])
            return item
          })
      },
      incomplete (id) {
        return apiPatch(`${ this.route }/${ id }/incomplete`).
          then(r => this.getItemFromResponse(r)).
          then(item => {

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
    email_log: ObjectStore(Groundhogg.api.routes.v4.email_log),
  }

  Groundhogg.createStore = (id, route = '', extra = {}) => {
    const store = ObjectStore(route, extra)
    Groundhogg.stores[id] = store
    return store
  }

  /**
   * Create a manageable state object
   *
   * @param initialState
   * @returns {{set(*): void, initial: {}, get(string=): (*|boolean|{}), clear(): void, reset(): void, state: {}, has(string=): boolean}}
   */
  const createState = (initialState = {}) => new Proxy({

    initial: {
      ...initialState
    },

    state: {
      ...initialState,
    },

    /**
     * Add props to the state
     *
     * @param newState
     */
    set (newState) {
      this.state = {
        ...this.state,
        ...newState,
      }
    },

    /**
     * Clear the state
     */
    clear () {
      this.state = {}
    },

    /**
     * Reset the State to it's initial
     */
    reset() {
      this.set({
        ...this.initial
      })
    },

    /**
     * Get a specific key from the state
     *
     * @param key
     * @returns {*|boolean|{}}
     */
    get (key = '') {

      if (key) {
        return this.state[key]
      }

      return this.state
    },

    /**
     * If the state has a specific key
     *
     * @param key
     * @returns {boolean}
     */
    has (key = '') {
      if (key) {
        return key in this.state
      }

      return Object.keys(this.state).length > 0
    },
  }, {
    set (manager, key, val) { // to intercept property writing

      if (key === 'state') {
        return Reflect.set(manager, key, val)
      }

      return Reflect.set(Reflect.get(manager, 'state'), key, val)
    },
    get (manager, key, receiver) {

      if (key === 'state') {
        return Reflect.get(manager, key)
      }

      let state = Reflect.get(manager, 'state')

      if (Reflect.has(state, key)) {
        return Reflect.get(state, key)
      }

      return Reflect.get(manager, key)
    },
  })

  const stateMap = new WeakMap();

  /**
   * Like createState, but with memory
   *
   * @param initialState
   * @param caller
   * @returns {any}
   */
  function useState(initialState, caller = false) {

    if ( ! caller ){
      // Get the current function that is calling useState
      caller = useState.caller;
    }

    // Check if this function already has state in the map
    if (!stateMap.has(caller)) {
      // If not, initialize the state and store it in the WeakMap
      stateMap.set(caller, createState(initialState));
    }

    // Return the stored state for this function
    return stateMap.get(caller);
  }

  /**
   * Create a registry that contacts items based on IDs
   *
   * @returns {{add(*, *), getItems(), get(*), has(*), remove(*)}}
   * @param initialItems
   */
  const createRegistry = (initialItems = {}) => new Proxy({
    items: {
      ...initialItems,
    },

    /**
     * Add props to the state
     *
     * @param key
     * @param newItem
     */
    add (key, newItem) {
      this.items[key] = newItem
    },

    /**
     * Clear the state
     */
    clear () {
      this.items = {}
    },

    /**
     * Get a specific key from the registry
     *
     * @param key
     * @returns {*|boolean|{}}
     */
    get (key = '') {

      if (key) {
        return this.items[key]
      }

      return this.items
    },

    keys () {
      return Object.keys(this.items)
    },

    filter( func ) {
      return this.keys().filter( key => func( this[key], key ) )
    },

    map( func ) {
      return this.keys().map( key => func( this[key], key ) )
    },

    /**
     * If the registry has a specific key
     *
     * @param key
     * @returns {boolean}
     */
    has (key = '') {
      if (key) {
        return key in this.items
      }

      return Object.keys(this.items).length > 0
    },
  }, {
    set (manager, key, val) { // to intercept property writing

      if (key === 'items') {
        return Reflect.set(manager, key, val)
      }

      return Reflect.set(Reflect.get(manager, 'items'), key, val)
    },
    get (manager, key, receiver) {

      if (key === 'items') {
        return Reflect.get(manager, key)
      }

      let items = Reflect.get(manager, 'items')

      if (Reflect.has(items, key)) {
        return Reflect.get(items, key)
      }

      return Reflect.get(manager, key)
    },
  })

  Groundhogg.createState = createState
  Groundhogg.useState = useState
  Groundhogg.createRegistry = createRegistry

} )(jQuery)
