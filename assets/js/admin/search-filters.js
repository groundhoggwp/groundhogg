(function ($) {

  const {
    input,
    select,
    regexp,
    specialChars,
    clickInsideElement,
    orList,
    andList,
    searchOptionsWidget
  } = Groundhogg.element

  const Filters = {
    view () {},
    edit () {},
    types: {},
    groups: {}
  }

  const renderFilterView = (filter, filterGroupIndex, filterIndex) => {
    //language=HTML
    return `
		<div class="filter filter-view" data-key="${filterIndex}" data-group="${filterGroupIndex}" tabindex="0">
			${Filters.types[filter.type].view(filter, filterGroupIndex, filterIndex)}
			<button class="delete-filter"><span class="dashicons dashicons-no-alt"></span></button>
		</div>`
  }

  const renderFilterEdit = (filter, filterGroupIndex, filterIndex) => {
    //language=HTML
    return `
		<div class="filter filter-edit-wrap" data-key="${filterIndex}" data-group="${filterGroupIndex}">
			<div class="filter-edit" tabindex="0">
				<div class="header">
					<b>${Filters.types[filter.type].name}</b>
					<button class="close-edit"><span class="dashicons dashicons-no-alt"></span></button>
				</div>
				<div class="settings">
					${Filters.types[filter.type].edit(filter, filterGroupIndex, filterIndex)}
				</div>
				<div class="actions">
					<button class="delete"><span class="dashicons dashicons-trash"></span></button>
					<button class="commit"><span class="dashicons dashicons-yes"></span></button>
				</div>
			</div>
		</div>`
  }

  const registerFilterGroup = (group, name) => {
    Filters.groups[group] = name
  }

  /**
   * Register a new filter
   *
   * @param type
   * @param group
   * @param opts
   * @param name
   */
  const registerFilter = (type, group = 'general', opts = {}, name = '') => {
    Filters.types[type] = {
      type,
      group,
      name,
      view (filter) {},
      edit (filter) {},
      onMount (filter) {},
      onDemount (filter) {},
      defaults: {},
      ...opts
    }
  }

  const Comparisons = {
    equals: 'Equals',
    not_equals: 'Not equals',
    contains: 'Contains',
    not_contains: 'Does not contain',
    starts_with: 'Starts with',
    ends_with: 'Ends with',
    does_not_start_with: 'Does not start with',
    does_not_end_with: 'Does not end with',
    less_than: 'Less than',
    greater_than: 'Greater than',
    empty: 'Is empty',
    not_empty: 'Is not empty'
  }

  const createFilters = (el, filters, onChange) => ({
    onChange,
    filters,
    el,
    currentGroup: false,
    currentFilter: false,
    isAddingFilterToGroup: false,
    tempFilterSettings: {},
    selectFiltersWidget: null,

    render () {
      var self = this

      const groups = []

      this.filters.forEach((filterGroup, j) => {
        const filters = []
        filterGroup.forEach((filter, k) => {
          filters.push(self.currentGroup === j && self.currentFilter === k ? renderFilterEdit(filter, j, k) : renderFilterView(filter, j, k))
        })
        filters.push(self.isAddingFilterToGroup === j ? `<div class="add-filter-wrap"></div>` : `<button data-group="${j}" class="add-filter">
				  <span class="dashicons dashicons-plus-alt2"></span>
			  </button>`)
        groups.push(filters.join(''))
      })

      const separator = `<div class="or-separator"><span class="or-circle">Or...</span></div>`

      //language=HTML
      return `
		  <div id="search-filters-editor">
			  ${groups.length > 0 ? `${groups.map(group => `<div class="group">${group}</div>`).join(separator)}
			  ${separator}` : ''}
			  <div class="group" data-group="${groups.length}">
				  ${self.isAddingFilterToGroup === groups.length ? `<div class="add-filter-wrap"></div>` : `<button data-group="${groups.length}" class="add-filter">
				  <span class="dashicons dashicons-plus-alt2"></span>
			  </button>`}
			  </div>
		  </div>`
    },

    mount () {
      $(el).html(this.render())
      this.eventHandlers()
    },

    demount () {

    },

    eventHandlers () {

      var self = this

      const reMount = () => {
        self.mount()
      }

      const getFilterSettings = (group, key) => {
        return self.filters[group][key]
      }

      const setActiveFilter = (group, filter) => {
        self.currentFilter = filter
        self.currentGroup = group
        self.isAddingFilterToGroup = false

        reMount()
      }

      const addFilter = (opts, group) => {
        group = group >= 0 ? group : this.isAddingFilterToGroup

        if (self.filters.length === 0) {
          group = 0
          self.filters.push([])
        } else if (!self.filters[group]) {
          self.filters.push([])
          group = self.filters.length - 1
        }

        self.filters[group].push({
          ...opts
        })

        onChange(self.filters)

        setActiveFilter(group, self.filters[group].length - 1)
      }

      const updateFilter = (opts) => {

        console.log(opts)

        this.tempFilterSettings = {
          ...this.tempFilterSettings,
          ...opts
        }

        return this.tempFilterSettings
      }

      const commitFilter = (group, key) => {
        group = group >= 0 ? group : self.currentGroup
        key = key >= 0 ? key : self.currentFilter

        self.filters[group][key] = {
          ...self.filters[group][key],
          ...self.tempFilterSettings
        }

        this.tempFilterSettings = {}

        onChange(self.filters)

        setActiveFilter(false, false)
      }

      const deleteFilter = (group, key) => {
        group = group >= 0 ? group : self.currentGroup
        key = key >= 0 ? key : self.currentFilter

        console.log({
          group,
          key,
        })

        // remove the filter
        self.filters[group].splice(key, 1)

        // If the group is empty, remove it as well
        if (group !== 0 && self.filters[group].length === 0) {
          self.filters.splice(group, 1)
        }

        onChange(self.filters)

        reMount()
      }

      this.filterPicker = searchOptionsWidget({
        selector: '.add-filter-wrap',
        options: Object.values(Filters.types),
        groups: Filters.groups,
        onSelect: (option) => {
          addFilter({
            type: option.type,
            ...option.defaults
          })
        },
        filterOption: (option, search) => {
          return option.name.match(regexp(search))
        },
        renderOption: (option) => option.name,
        onClose: () => {
          this.isAddingFilterToGroup = false
          this.mount()
        },
        noOptions: 'No matching filters...'
      })

      if (this.isAddingFilterToGroup !== false) {
        this.filterPicker.mount()
      }

      if (this.currentGroup !== false && this.currentFilter !== false) {
        const $filterEdit = $('.filter-edit')

        $filterEdit.parent().width($filterEdit.width())

        const filterSettings = getFilterSettings(this.currentGroup, this.currentFilter)
        this.tempFilterSettings = filterSettings
        const { type } = filterSettings

        Filters.types[type].onMount(filterSettings, updateFilter)

        $filterEdit.on('keydown', (e) => {

          const { key } = e

          switch (key) {
            case 'Esc':
            case 'Escape':
              setActiveFilter(false, false)
              break
            case 'Enter':
              // Todo should only do this on inputs which support it
              // commitFilter()
              break
          }

        }).focus()
      }

      $(`${el} .filter-view`).on('keydown', function (e) {

        console.log(this)

        switch (e.key) {
          case 'Enter':
          case 'Space':
            setActiveFilter($(this).data('group'), $(this).data('key'))
            break
        }
      })

      $(`${el} #search-filters-editor`).on('click', function (e) {

        // console.log(e)

        const clickedOnAddFilter = clickInsideElement(e, 'button.add-filter')
        const clickedOnAddFilterSearch = clickInsideElement(e, 'div.add-filter-wrap')
        const clickedOnFilterView = clickInsideElement(e, '.filter.filter-view')
        const clickedOnFilterEdit = clickInsideElement(e, '.filter-edit')

        if (clickedOnAddFilter) {

          self.isAddingFilterToGroup = parseInt(clickedOnAddFilter.dataset.group)
          self.currentFilter = false
          self.currentGroup = false
          reMount()

        } else if (clickedOnFilterView) {

          const clickedOnFilterDelete = clickInsideElement(e, '.delete-filter')

          const filter = parseInt(clickedOnFilterView.dataset.key)
          const group = parseInt(clickedOnFilterView.dataset.group)

          if (clickedOnFilterDelete) {
            deleteFilter(group, filter)
          } else {
            setActiveFilter(group, filter)
          }

        } else if (clickedOnFilterEdit) {

          const clickedOnEditClose = clickInsideElement(e, '.close-edit')
          const clickedOnDeleteFilter = clickInsideElement(e, '.delete')
          const clickedOnCommitChanges = clickInsideElement(e, '.commit')

          if (clickedOnEditClose) {
            setActiveFilter(false, false)
          } else if (clickedOnCommitChanges) {
            commitFilter()
          } else if (clickedOnDeleteFilter) {
            deleteFilter()
          } else {

          }

        } else if (clickedOnAddFilterSearch) {

        } else {
          self.currentFilter = false
          self.currentGroup = false

          reMount()
        }

      })
    }

  })

  Groundhogg.filters.functions = {
    createFilters
  }

//  REGISTER ALL FILTERS HERE
  const BasicTextFilter = (name) => ({
    name,
    view ({ compare, value }, filterGroupIndex, filterIndex) {
      switch (compare) {
        case 'empty':
        case 'not_empty':
          return `<b>${name}</b> ${Comparisons[compare].toLowerCase()}`
        default:
          return `<b>${name}</b> ${Comparisons[compare].toLowerCase()} <b>${specialChars(value)}</b>`
      }
    },
    edit ({ compare, value }, filterGroupIndex, filterIndex) {
      // language=html
      return `${select({
		  id: 'filter-compare',
		  name: 'compare',
		  dataGroup: filterIndex,
		  dataKey: filterIndex,
	  }, Comparisons, compare)} ${input({
		  id: 'filter-value',
		  name: 'value',
		  dataGroup: filterIndex,
		  dataKey: filterIndex,
		  value
	  })}`
    },
    onMount (filter, updateFilter) {
      // console.log(filter)

      $('#filter-compare, #filter-value').on('change', function (e) {
        // console.log(e)
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val()
        })
      })
    },
    defaults: {
      compare: 'equals',
      value: ''
    }
  })

  registerFilterGroup('contact', 'Contact')

  registerFilter('first_name', 'contact', {
    ...BasicTextFilter('First Name')
  })

  registerFilter('last_name', 'contact', {
    ...BasicTextFilter('Last Name')
  })

  registerFilter('email', 'contact', {
    ...BasicTextFilter('Email Address')
  })

  registerFilter('date_created', 'contact', {
    view ({ compare, value, value2 }, filterGroupIndex, filterIndex) {
      //language=HTMl
      switch (compare) {
        case 'before':
          return `<b>Date created</b> is before <b>${value}</b>`
        case 'after':
          return `<b>Date created</b> is after <b>${value}</b>`
        case 'between':
          return `<b>Date created</b> is between <b>${value}</b> and <b>${value2}</b>`
      }
    },
    edit ({ compare, value, value2 }, filterGroupIndex, filterIndex) {
      // language=html
      return `${select({
		  id: 'filter-compare',
		  name: 'compare'
	  }, {
		  before: 'Before',
		  after: 'After',
		  between: 'Between'
	  }, compare)} ${input({
		  type: 'date',
		  value: value,
		  id: 'filter-value',
		  name: 'value'
	  })} ${input({
		  type: 'date',
		  value: value2,
		  id: 'filter-value2',
		  className: 'hidden',
		  name: 'value2'
	  })}`
    },
    onMount (filter, updateFilter) {
      $('#filter-compare, #filter-value, #filter-value2').on('change', function (e) {
        const $el = $(this)
        const { compare } = updateFilter({
          [$el.prop('name')]: $el.val()
        })

        if (compare === 'between') {
          $('#filter-value2').removeClass('hidden')
        } else {
          $('#filter-value2').addClass('hidden')
        }
      })
    },
    defaults: {
      compare: 'before',
      value: '',
      value2: ''
    }
  }, 'Date Created')

  registerFilter('meta', 'contact', {
    view ({ meta, compare, value }) {
      //language=HTMl
      switch (compare) {
        case 'empty':
        case 'not_empty':
          return `<b>${meta}</b> ${Comparisons[compare].toLowerCase()}`
        default:
          return `<b>${meta}</b> ${Comparisons[compare].toLowerCase()} <b>${specialChars(value)}</b>`
      }
    },
    edit ({ meta, compare, value }, filterGroupIndex, filterIndex) {
      // language=html
      return `
		  ${input({
			  id: 'filter-meta',
			  name: 'meta',
			  className: 'meta-picker',
			  dataGroup: filterIndex,
			  dataKey: filterIndex,
			  value: meta
		  })}
		  ${select({
			  id: 'filter-compare',
			  name: 'compare',
			  dataGroup: filterIndex,
			  dataKey: filterIndex,
		  }, Comparisons, compare)} ${input({
			  id: 'filter-value',
			  name: 'value',
			  dataGroup: filterIndex,
			  dataKey: filterIndex,
			  value
		  })}`
    },
    onMount (filter, updateFilter) {

      const { metaPicker } = Groundhogg.pickers

      metaPicker('#filter-meta')

      $('#filter-compare, #filter-value, #filter-meta').on('change', function (e) {
        const $el = $(this)
        const { compare } = updateFilter({
          [$el.prop('name')]: $el.val()
        })
      })
    },
    defaults: {
      meta: '',
      compare: 'equals',
      value: ''
    }
  }, 'Custom meta')

  const { optin_status, owners, meta_keys } = Groundhogg.filters

  registerFilter('optin_status', 'contact', {
    view ({ compare, value }) {
      switch (compare) {
        default:
        case 'in':
          return `<b>Optin status</b> is one of ${orList(value.map(v => `<b>${optin_status[v]}</b>`))}`
        case 'not_in':
          return `<b>Optin status</b> is not one of ${orList(value.map(v => `<b>${optin_status[v]}</b>`))}`
      }
    },
    edit ({ compare, value }, filterGroupIndex, filterIndex) {
      // language=html
      return `
		  ${select({
			  id: 'filter-compare',
			  name: 'compare',
			  class: '',
		  }, {
			  in: 'Is one of',
			  not_in: 'Is not one of'
		  }, compare)}
		  ${select({
				  id: 'filter-value',
				  name: 'value',
				  class: 'gh-select2',
				  multiple: true
			  },
			  optin_status,
			  value
		  )} `
    },
    onMount (filter, updateFilter) {
      $('#filter-value').select2()
      $('#filter-value, #filter-compare').on('change', function (e) {
        const $el = $(this)
        // console.log($el.val())
        updateFilter({
          [$el.prop('name')]: $el.val()
        })
      })
    },
    defaults: {
      compare: 'in',
      value: []
    }
  }, 'Optin Status')

  registerFilter('owner', 'contact', {
    view ({ compare, value }) {

      const ownerName = (ID) => {
        let user = owners.find(owner => owner.ID == ID)
        return `${user.data.user_login} (${user.data.user_email})`
      }

      //language=HTMl
      switch (compare) {
        default:
        case 'in':
          return `<b>Optin status</b> is one of ${orList(value.map(v => `<b>${ownerName(v)}</b>`))}`
        case 'not_in':
          return `<b>Optin status</b> is not one of ${orList(value.map(v => `<b>${ownerName(v)}</b>`))}`
      }
    },
    edit ({ compare, value }, filterGroupIndex, filterIndex) {

      var values = {}
      $.map(owners, function (user, index) {
        values[user.data.ID] = `${user.data.user_login} (${user.data.user_email})`
      })
      // language=html
      return `
		  ${select({
			  id: 'filter-compare',
			  name: 'compare',
		  }, {
			  in: 'Is one of',
			  not_in: 'Is not one of'
		  }, compare)}

		  ${select({
				  id: 'filter-value',
				  name: 'value',
				  multiple: true,
			  },
			  values,
			  value
		  )} `
    },
    onMount (filter, updateFilter) {
      $('#filter-value').select2()
      $('#filter-value, #filter-compare').on('change', function (e) {
        const $el = $(this)
        // console.log($el.val())
        updateFilter({
          [$el.prop('name')]: $el.val()
        })
      })
    },
    defaults: {
      compare: 'equals',
      value: []
      /*  value: '',
        value2: ''*/
    }
  }, 'Owner')

  //filter by meta data
  registerFilter('meta_data', 'contact', {
    view ({ compare, value, value2 }, filterGroupIndex, filterIndex) {
      //language=HTMl
      switch (compare) {/*
          case 'before':
            return `is before <b>${value}</b>`
          case 'after':
            return `is after <b>${value}</b>`
          case 'between':
            return `is between <b>${value}</b> and <b>${value2}</b>`*/
      }
      return `selected value <b>${value}</b>`
    },
    edit ({ compare, value }, filterGroupIndex, filterIndex) {

      var defaultValues = {}
      $.map(meta_keys, function (value, index) {
        defaultValues[index] = value
      })
      //console.log(defaultValues);
      // language=html
      return `${select({
		  id: 'filter-compare',
		  name: 'compare'
	  }, {
		  equal: 'Equal',
		  notequal: 'Not Equal',
		  greaterthan: 'Greater Than',
		  lessthan: 'Less Than',
		  contains: 'Contains',
		  doesnotcontain: 'Does Not Contains',
	  }, compare)} ${select({
			  id: 'meta_key',
			  name: 'value',
			  class: 'meta_key',
		  },
		  defaultValues
	  )} ${input({
		  id: 'filter-value',
		  name: 'value',
		  value: ''
	  })}`
    },
    onMount (filter, updateFilter) {
      $('#filter-value, #meta_key, #filter-value').on('change', function (e) {
        const $el = $(this)
        // console.log($el.val())
        updateFilter({
          [$el.prop('name')]: $el.val()
        })
      })
    },
    defaults: {
      compare: 'equals',
      /*  value: '',
        value2: ''*/
    }
  }, 'Meta Data')

  //  Filter by Optin Status
  //  Filter by Contact Owner
  //  Filter by Tags (complex)
  //  Filter by Meta Data (complex)

})(jQuery)