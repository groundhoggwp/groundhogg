(function ($) {

  const { createFilters } = Groundhogg.filters.functions
  const {
    searchOptionsWidget,
    regexp,
    specialChars,
    modal,
    input,
    loadingDots,
    copyObject,
    objectEquals
  } = Groundhogg.element
  const { post, get, patch, routes } = Groundhogg.api
  const { searches: SearchesStore } = Groundhogg.stores

  SearchesStore.itemsFetched(ContactSearch.searches)

  const loadFilters = (filters) => {
    window.location.href = ContactSearch.url + '&filters=' + btoa(JSON.stringify(filters))
  }

  const loadSearch = (search) => {
    window.location.href = ContactSearch.url + '&saved_search=' + search
  }

  const SearchApp = {

    filtersEnabled: false,
    savedSearchEnabled: false,
    filters: [],
    filtersApp: null,
    searchesApp: null,
    currentSearch: null,

    init () {

      const handleUpdateFilters = (filters) => {
        this.filters = filters
        getContacts(filters)

        // console.log(this.filters, this.currentSearch.query.filters)

        if (this.currentSearch) {
          if (objectEquals(this.filters, this.currentSearch.query.filters)) {
            $('#update-search').prop('disabled', true)
          } else {
            $('#update-search').prop('disabled', false)
          }
        }

      }

      this.filters = ContactSearch.filters || []
      if (this.filters && this.filters.length > 0) {
        this.filtersEnabled = true
        $('.contact-quick-search').hide()
      } else if (ContactSearch.currentSearch) {
        this.currentSearch = copyObject(ContactSearch.currentSearch)
        if (this.currentSearch.query.filters) {
          this.filters = copyObject(this.currentSearch.query.filters, [])
        }
      }

      this.filtersApp = createFilters('#search-filters', this.filters, handleUpdateFilters)
      this.initSavedSearches()
      this.mount()
    },

    initSavedSearches () {

      this.searchesApp = searchOptionsWidget({
        selector: '#searches-picker',
        options: SearchesStore.getItems(),
        filterOption: (option, search) => {
          return option.name.match(regexp(search))
        },
        renderOption: (option) => option.name,
        onClose: () => {
          this.savedSearchEnabled = false
          this.mount()
        },
        onSelect: (option) => {
          this.loadingSearch = true
          loadSearch(option.id)
        },
        noOptions: 'No matching searches...'
      })

    },

    render () {

      if (this.filtersEnabled) {
        //language=HTML
        return `
			<div class="enable-filters-wrap">
				<button class="enable-filters white"><span class="dashicons dashicons-filter"></button>
			</div>
			<div class="search-filters-wrap">
				<div id="search-filters"></div>
				<div class="search-contacts-wrap">
					<button id="search-contacts" class="button button-primary">Search</button>
					${!this.currentSearch
						? '<button id="save-search" class="button button-secondary">Save this search</button>'
						: `<button id="update-search" class="button button-secondary" ${objectEquals(this.filters, this.currentSearch.query.filters) ? 'disabled' : ''}>Update "${this.currentSearch.name}"</button>`}
				</div>
			</div>
        `
      }

      //language=HTML
      return `
		  <button class="enable-filters white" style="padding-right: 10px"><span
			  class="dashicons dashicons-filter"></span> ${ this.currentSearch ? 'Edit Filters' : 'Filter Contacts' }
		  </button>
		  ${this.savedSearchEnabled
			  ? `<div id="searches-picker"></div>`
			  : (ContactSearch.searches.length
					  ? `<button id="load-saved-search" class="has-dashicon button button-secondary"><span class="dashicons dashicons-search"></span> <span class="text">${this.loadingSearch ? 'Loading search' : 'Load saved search'}</span></button>`
					  : ''
			  )}`
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
        if (this.filtersEnabled) {
          $('.contact-quick-search').hide()
        } else {
          $('.contact-quick-search').show()
        }
        remount()
      }

      const enableSavedSearch = () => {
        this.savedSearchEnabled = !this.savedSearchEnabled
        remount()
      }

      if (this.filtersEnabled) {
        this.filtersApp.init()
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

      if (this.loadingSearch) {
        $('#load-saved-search').prop('disabled', true)
        loadingDots('#load-saved-search span.text')
      }

      $('#search-contacts').on('click', (e) => {
        $(e.target).html('Searching').prop('disabled', true)
        loadingDots('#search-contacts')
        if (this.currentSearch && objectEquals(this.filters, this.currentSearch.query.filters)) {
          loadSearch(this.currentSearch.id)
        } else {
          loadFilters(this.filters)
        }
      })

      $('#update-search').on('click', (e) => {

        const $button = $(e.target)
        $button.prop('disabled', true)
        $button.html('Updating')
        const { stop } = loadingDots('#update-search')

        SearchesStore.patch(this.currentSearch.id, {
          query: {
            filters: this.filters
          }
        }).then(search => {

          stop()
          this.currentSearch = search
          $button.html('Updated!')

          setTimeout(() => {
            this.mount()
          }, 1000)
        })
      })

      $('span#search-name').on('click', (e) => {

        const $span = $(e.target)

        $span.html(input({
          name: 'search_name',
          id: 'saved-search-name-edit',
          value: this.currentSearch.name
        }))

        $('#saved-search-name-edit').focus().on('change blur keydown', (e) => {

          if (e.type === 'keydown' && e.key !== 'Enter') {
            return
          }

          const newName = e.target.value

          $span.html(specialChars(newName))

          if (newName !== this.currentSearch.name) {
            SearchesStore.patch(this.currentSearch.id, {
              name: newName
            }).then(s => this.currentSearch = s).then(() => $span.html(specialChars(this.currentSearch.name)))
          }
        })
      })

      $('#save-search').on('click', () => {
        const {
          $modal,
          close
        } = modal({
          //language=html
          content: `
			  <h2>Name your search...</h2>
			  <p>${input({
				  id: 'search-name',
				  placeholder: 'My saved search...'
			  })}</p>
			  <button id="save" disabled class="gh-button primary">Save</button>`
        })

        $('input#search-name').on('change input', (e) => {
          this.newSearchName = e.target.value
          if (!this.newSearchName) {
            $('#save').prop('disabled', true)
          } else {
            $('#save').prop('disabled', false)
          }

        }).focus()

        $('#save.gh-button').on('click', (e) => {

          if (!this.newSearchName) {
            return
          }

          const $button = $(e.target)
          $button.prop('disabled', true)
          $button.html('Saving')
          const { stop } = loadingDots('#save.gh-button')

          SearchesStore.post({
            name: this.newSearchName,
            query: {
              filters: this.filters
            }
          }).then(search => {

            stop()
            $button.html('Saved!')

            this.currentSearch = search
            this.mount()

            setTimeout(close, 1000)
          })
        })
      })
    }
  }

  let abortHandler

  const getContacts = (filters) => {

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
    SearchApp.init()
  })

})(jQuery)