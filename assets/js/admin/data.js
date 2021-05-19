(function ($) {

  /**
   * Fetch stuff from the API
   * @param route
   * @param params
   */
  async function apiFetch (route, params) {
    const response = fetch( route + '?' + $.params( params ), {
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

  const ObjectStore = (route, extra={}) => ({
    items: [],
    route: route,

    async fetch (params) {

      var self = this;

      return apiFetch( this.route, params ).then( r => {
        self.items = r.items
      })
    },

    async post (data) {
      var self = this;

      return apiPost( this.route, data ).then( r => {
        self.items.push( r.item )
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
  Groundhogg.api.get  = apiFetch

  Groundhogg.stores = {
    tags: ObjectStore(Groundhogg.api.routes.v4.tags),
    contacts: ObjectStore(Groundhogg.api.routes.v4.contacts),
    emails: ObjectStore(Groundhogg.api.routes.v4.emails),
  }
})(jQuery)