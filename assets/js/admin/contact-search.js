(function ($, searches) {

  const { createFilters } = Groundhogg.filters.functions
  const { searchOptionsWidget, regexp } = Groundhogg.element

  const updateUrl = (filters) => {
    // window.history.pushState({ filters }, document.title, page.url + '?' + $.param({ filters }))
  }

  const ContactSearch = {

    filtersEnabled: false,
    savedSearchEnabled: false,
    filters: [],
    filtersApp: null,
    searchesApp: null,

    init () {

      const handleUpdateFilters = (filters) => {
        console.log(filters)
        this.filters = filters
        getContacts(filters)
        // updateUrl(filters)
      }

      this.filters = Groundhogg.filters.current
      if (searches.length === 0) {
        this.filtersEnabled = true
      }
      this.filtersApp = createFilters('#search-filters', this.filters, handleUpdateFilters)
      this.initSavedSearches()
      this.mount()
    },

    initSavedSearches () {

      this.searchesApp = searchOptionsWidget({
        selector: '#searches-picker',
        options: searches,
        filterOption: (option, search) => {
          return option.name.match(regexp(search))
        },
        renderOption: (option) => option.name,
        onClose: () => {
          this.savedSearchEnabled = false
          this.mount()
        },
        noOptions: 'No matching searches...'
      })

    },

    render () {

      if (this.filtersEnabled) {
        //language=HTML
        return `
			<button class="enable-filters white"><span class="dashicons dashicons-filter"></button>
			<div id="search-filters"></div>
			<button id="search-contacts" class="button button-primary">Search</button>`
      }

      //language=HTML
      return `
		  <button class="enable-filters white"><span class="dashicons dashicons-filter"></span></button>
		  ${this.savedSearchEnabled ? `<div id="searches-picker"></div>` : (searches.length ? `<button id="load-saved-search" class="button button-secondary">Load saved search</button>` : '')}`
    },

    mount () {
      $('#search-panel .filters').html(this.render())
      this.addListeners()
    },

    addListeners () {

      var self = this

      const remount = () => {
        this.mount()
      }

      const enableFilters = () => {
        this.filtersEnabled = !this.filtersEnabled
        remount()
      }

      const enableSavedSearch = () => {
        this.savedSearchEnabled = !this.savedSearchEnabled
        remount()
      }

      if (this.filtersEnabled) {
        this.filtersApp.mount()
      }

      if (this.savedSearchEnabled) {
        this.searchesApp.mount()
      }

      $('.enable-filters').on('click', function () {
        enableFilters()
      })

      $('#load-saved-search').on('click', function () {
        enableSavedSearch()
      })

      $('#search-contacts').on('click', function () {
        console.log(self.filters)
        window.location.href = page.url + '&' + $.param({ filters: self.filters })
      })

    }

  }

  let abortHandler

  const getContacts = (filters) => {
    const { post, get, routes } = Groundhogg.api

    if (abortHandler) {
      abortHandler.abort()
    }

    abortHandler = new AbortController()
    const { signal } = abortHandler

    get(routes.v4.contacts, {
      filters
    }, {
      // credentials: 'same-origin',
      signal
    }).then(data => {
      $('#search-contacts').html(`Show ${data.total_items} contacts`)
    })
  }

  $(function () {
    ContactSearch.init()
  })

})(jQuery, SavedSearches)