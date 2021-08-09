(function ($) {

  const {
    input,
    select,
    regexp,
    specialChars,
    clickInsideElement,
    orList,
    andList,
    searchOptionsWidget,
    loadingDots,
    tooltip,
    bold
  } = Groundhogg.element

  const { broadcastPicker, funnelPicker, tagPicker, emailPicker, linkPicker } = Groundhogg.pickers

  const { broadcasts: BroadcastsStore, emails: EmailsStore, tags: TagsStore, funnels: FunnelsStore } = Groundhogg.stores

  const { sprintf, __, _x, _n } = wp.i18n

  const Filters = {
    has (type) {
      return typeof this.types[type] !== 'undefined'
    },
    view () {},
    edit () {},
    types: {},
    groups: {}
  }

  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting

  const renderFilterView = (filter, filterGroupIndex, filterIndex) => {

    if (!Filters.has(filter.type)) {
      return ''
    }

    //language=HTML
    return `
		<div class="filter filter-view" data-key="${filterIndex}" data-group="${filterGroupIndex}" tabindex="0">
			${Filters.types[filter.type].view(filter, filterGroupIndex, filterIndex)}
			<button class="delete-filter"><span class="dashicons dashicons-no-alt"></span></button>
		</div>`
  }

  const renderFilterEdit = (filter, filterGroupIndex, filterIndex) => {

    if (!Filters.has(filter.type)) {
      return ''
    }

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
  const registerFilter = (type, group = 'general', name = '', opts = {}) => {

    if (typeof name === 'object') {
      let tmpOpts = name
      name = opts
      opts = tmpOpts
    }

    Filters.types[type] = {
      type,
      group,
      name,
      view (filter) {},
      edit (filter) {},
      onMount (filter) {},
      onDemount (filter) {},
      preload () {},
      defaults: {},
      ...opts
    }
  }

  const AllComparisons = {
    equals: _x('Equals', 'comparison', 'groundhogg'),
    not_equals: _x('Not equals', 'comparison', 'groundhogg'),
    contains: _x('Contains', 'comparison', 'groundhogg'),
    not_contains: _x('Does not contain', 'comparison', 'groundhogg'),
    starts_with: _x('Starts with', 'comparison', 'groundhogg'),
    ends_with: _x('Ends with', 'comparison', 'groundhogg'),
    does_not_start_with: _x('Does not start with', 'comparison', 'groundhogg'),
    does_not_end_with: _x('Does not end with', 'comparison', 'groundhogg'),
    less_than: _x('Less than', 'comparison', 'groundhogg'),
    less_than_or_equal_to: _x('Less than or equal to', 'comparison', 'groundhogg'),
    greater_than: _x('Greater than', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('Greater than or equal to', 'comparison', 'groundhogg'),
    empty: _x('Is empty', 'comparison', 'groundhogg'),
    not_empty: _x('Is not empty', 'comparison', 'groundhogg')
  }

  const StringComparisons = {
    equals: _x('Equals', 'comparison', 'groundhogg'),
    not_equals: _x('Not equals', 'comparison', 'groundhogg'),
    contains: _x('Contains', 'comparison', 'groundhogg'),
    not_contains: _x('Does not contain', 'comparison', 'groundhogg'),
    starts_with: _x('Starts with', 'comparison', 'groundhogg'),
    ends_with: _x('Ends with', 'comparison', 'groundhogg'),
    does_not_start_with: _x('Does not start with', 'comparison', 'groundhogg'),
    does_not_end_with: _x('Does not end with', 'comparison', 'groundhogg'),
    empty: _x('Is empty', 'comparison', 'groundhogg'),
    not_empty: _x('Is not empty', 'comparison', 'groundhogg')
  }

  const NumericComparisons = {
    equals: _x('Equals', 'comparison', 'groundhogg'),
    not_equals: _x('Not equals', 'comparison', 'groundhogg'),
    less_than: _x('Less than', 'comparison', 'groundhogg'),
    less_than_or_equal_to: _x('Less than or equal to', 'comparison', 'groundhogg'),
    greater_than: _x('Greater than', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('Greater than or equal to', 'comparison', 'groundhogg'),
  }

  const ComparisonsTitleGenerators = {
    equals: (k, v) => sprintf(_x('%1$s equals %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_equals: (k, v) => sprintf(_x('%1$s does not equal %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    contains: (k, v) => sprintf(_x('%1$s contains %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_contains: (k, v) => sprintf(_x('%1$s does not contain %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    starts_with: (k, v) => sprintf(_x('%1$s starts with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    ends_with: (k, v) => sprintf(_x('%1$s ends with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    does_not_start_with: (k, v) => sprintf(_x('%1$s does not start with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    does_not_end_with: (k, v) => sprintf(_x('%1$s does not end with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    less_than: (k, v) => sprintf(_x('%1$s is less than %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    less_than_or_equal_to: (k, v) => sprintf(_x('%1$s is less than or equal to %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    greater_than: (k, v) => sprintf(_x('%1$s is greater than %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    greater_than_or_equal_to: (k, v) => sprintf(_x('%1$s is greater than or equal to %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    in: (k, v) => sprintf(_x('%1$s is %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_in: (k, v) => sprintf(_x('%1$s is not %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    empty: (k, v) => sprintf(_x('%1$s is empty', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_empty: (k, v) => sprintf(_x('%1$s is not empty', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    includes: (k, v) => sprintf(_x('%1$s includes %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    excludes: (k, v) => sprintf(_x('%1$s excludes %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
  }

  const createFilters = (el = '', filters = [], onChange = (f) => {console.log(f)}) => ({
    onChange,
    filters,
    el,
    initFlag: false,
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
          filters.push(self.currentGroup === j && self.currentFilter === k ? renderFilterEdit(this.usingTempFilters ? this.tempFilterSettings : filter, j, k) : renderFilterView(filter, j, k))
        })
        filters.push(self.isAddingFilterToGroup === j ? `<div class="add-filter-wrap"></div>` : `<button data-group="${j}" class="add-filter">
				  <span class="dashicons dashicons-plus-alt2"></span>
			  </button>`)
        groups.push(filters.join(''))
      })

      const separator = `<div class="or-separator"><span class="or-circle">${_x('Or...', 'search filters separator', 'groundhogg')}</span></div>`

      //language=HTML
      return `
		  <div id="search-filters-editor">
			  ${groups.length > 0 ? `${groups.map((group, i) => `<div class="group" data-key="${i}">${group}</div>`).join(separator)}
			  ${separator}` : ''}
			  <div class="group" data-group="${groups.length}">
				  ${self.isAddingFilterToGroup === groups.length ? `<div class="add-filter-wrap"></div>` : `<button data-group="${groups.length}" class="add-filter">
				  <span class="dashicons dashicons-plus-alt2"></span>
			  </button>`}
			  </div>
		  </div>`
    },

    init () {
      if (this.initFlag) {
        this.mount()
      } else {
        this.initFlag = true
        this.preload()
      }
    },

    mount () {
      $(el).html(this.render())
      this.eventHandlers()
    },

    async preload () {
      const promises = []

      this.filters.forEach(group => {
        group.forEach(filter => {
          const { type } = filter

          const filterType = Filters.types[type]

          if (!filterType) {
            return
          }

          let p = filterType.preload(filter)

          if (!p) {
            return
          }

          // multiple promises
          if (Array.isArray(p) && p.length > 0) {
            promises.push(...p)

          }
          // Just the one promise
          else {
            promises.push(p)
          }

        })
      })

      if (promises.length === 0) {
        this.mount()
        return
      }

      $(el).html(`<p><span id="search-loading-dots-pill">${_x('Loading', 'as in waiting for the page to load', 'groundhogg')}<span id="search-loading-dots"></span></span></p>`)

      const { stop: stopDots } = loadingDots('#search-loading-dots')

      if (promises.length > 0) {
        await Promise.all(promises)
      }

      stopDots()
      this.mount()
    },

    demount () {

    },

    eventHandlers () {

      var self = this

      const reMount = (useTempFilters = false) => {

        if (useTempFilters) {
          this.usingTempFilters = true
        }

        self.mount()

        this.usingTempFilters = false
      }

      const getFilterSettings = (group, key) => {

        console.log({
          group, key
        })

        return {
          ...this.filters[group][key]
        }
      }

      const setActiveFilter = (group, filter, addingToGroup = false) => {
        self.currentFilter = filter
        self.currentGroup = group
        self.isAddingFilterToGroup = addingToGroup

        reMount()
      }

      const addFilter = (opts, group, setActive = true) => {
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


        if ( setActive ){
          setActiveFilter(group, self.filters[group].length - 1)
        }
      }

      const updateFilter = (opts, shouldReMount = false) => {

        this.tempFilterSettings = {
          ...this.tempFilterSettings,
          ...opts
        }

        if (shouldReMount) {
          reMount(true)
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

        // remove the filter
        self.filters[group].splice(key, 1)

        // If the group is empty, remove it as well
        if (self.filters.length > 1 && self.filters[group].length === 0) {
          self.filters.splice(group, 1)
        }

        setActiveFilter(false, false)

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
        noOptions: __('No matching filters...', 'groundhogg')
      })

      if (this.isAddingFilterToGroup !== false) {
        this.filterPicker.mount()
      }

      const mountFilterEdit = () => {
        const $filterEdit = $('.filter-edit')

        $filterEdit.parent().width($filterEdit.width())

        // console.log( this.tempFilterSettings )

        const { type } = this.tempFilterSettings

        Filters.types[type].onMount(this.tempFilterSettings, updateFilter)

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

        $filterEdit.on('click', (e) => {
          const clickedOnEditClose = clickInsideElement(e, '.close-edit')
          const clickedOnDeleteFilter = clickInsideElement(e, '.delete')
          const clickedOnCommitChanges = clickInsideElement(e, '.commit')

          if (clickedOnEditClose) {
            setActiveFilter(false, false)
          } else if (clickedOnCommitChanges) {
            commitFilter()
          } else if (clickedOnDeleteFilter) {
            deleteFilter()
          }
        })
      }

      if (this.currentGroup !== false && this.currentFilter !== false) {

        if (!this.usingTempFilters) {
          this.tempFilterSettings = getFilterSettings(this.currentGroup, this.currentFilter)
        }

        mountFilterEdit()
      }

      $(`${el} .filter-view`).on('keydown', function (e) {

        switch (e.key) {
          case 'Enter':
          case 'Space':
            setActiveFilter($(this).data('group'), $(this).data('key'))
            break
        }
      })

      tooltip('.add-filter', {
        content: __('Add a filter', 'groundhogg'),
        position: 'right'
      })

      $(`${el} #search-filters-editor`).on('click', function (e) {

        // console.log(e)

        const clickedOnAddFilter = clickInsideElement(e, 'button.add-filter')
        const clickedOnAddFilterSearch = clickInsideElement(e, 'div.add-filter-wrap')
        const clickedOnFilterView = clickInsideElement(e, '.filter.filter-view')
        const clickedOnFilterEdit = clickInsideElement(e, '.filter-edit')

        if (clickedOnAddFilter) {

          setActiveFilter(false, false, parseInt(clickedOnAddFilter.dataset.group))

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

        } else if (clickedOnAddFilterSearch) {

        } else {
          // setActiveFilter(false, false)
        }

      })

      $(`${el} .group`).sortable({
        connectWith: '.group',
        placeholder: 'filter-placeholder',
        cancel: '.add-filter, .filter-edit-wrap',
        start: (e, ui) => {
          // ui.placeholder.height(ui.item.height())
          ui.placeholder.width(ui.item.width())
        },
        receive: (e, ui) => {

          console.log({
            e, ui
          })

          const filterId = parseInt(ui.item.data('key'))
          const fromGroupId = parseInt(ui.item.data('group'))
          const toGroupId = parseInt($(e.target).data('key'))

          console.log({
            filterId,
            fromGroupId,
            toGroupId
          })

          const tempFilter = getFilterSettings( fromGroupId, filterId )

          deleteFilter(fromGroupId, filterId)

          addFilter(tempFilter, toGroupId, false )

          reMount()
        },
        update: (e, ui) => {},
      }).disableSelection()
    },
  })

  const dateRanges = {
    'any': __('At any time', 'groundhogg'),
    '24_hours': __('In the last 24 hours', 'groundhogg'),
    '7_days': __('In the last 7 days', 'groundhogg'),
    '30_days': __('In the last 30 days', 'groundhogg'),
    '60_days': __('In the last 60 days', 'groundhogg'),
    '90_days': __('In the last 90 days', 'groundhogg'),
    '365_days': __('In the last 365 days', 'groundhogg'),
    'before': __('Before', 'groundhogg'),
    'after': __('After', 'groundhogg'),
    'between': __('Between', 'groundhogg'),
  }

  const standardActivityDateFilterOnMount = (filter, updateFilter) => {
    $('#filter-date-range, #filter-before, #filter-after').on('change', function (e) {
      const $el = $(this)
      updateFilter({
        [$el.prop('name')]: $el.val()
      })

      if ($el.prop('name') === 'date_range') {

        const $before = $('#filter-before')
        const $after = $('#filter-after')

        switch ($el.val()) {
          case 'between':
            $before.removeClass('hidden')
            $after.removeClass('hidden')
            break
          case 'after':
            $after.removeClass('hidden')
            $before.addClass('hidden')
            break
          case 'before':
            $before.removeClass('hidden')
            $after.addClass('hidden')
            break
          default:
            $before.addClass('hidden')
            $after.addClass('hidden')
            break
        }
      }
    })
  }

  const standardActivityDateTitle = (prepend, { date_range, before, after }) => {

    switch (date_range) {
      default:
        return `${prepend} ${dateRanges[date_range].toLowerCase()}`
      case 'between':
        return `${prepend} ${sprintf(_x('between %1$s and %2$s', 'where %1 and %2 are dates', 'groundhogg'), `<b>${formatDate(after)}</b>`, `<b>${formatDate(before)}</b>`)}`
      case 'before':
        return `${prepend} ${sprintf(_x('before %s', '%s is a date', 'groundhogg'), `<b>${formatDate(before)}</b>`)}`
      case 'after':
        return `${prepend} ${sprintf(_x('after %s', '%s is a date', 'groundhogg'), `<b>${formatDate(after)}</b>`)}`
    }
  }

  const standardActivityDateDefaults = {
    date_range: 'any',
    before: '',
    after: ''
  }

  const standardActivityDateOptions = ({ date_range = '24_hours', after = '', before = '' }) => {

    return ` ${select({
      id: 'filter-date-range',
      name: 'date_range'
    }, dateRanges, date_range)}

		  ${input({
      type: 'date',
      value: after.split(' ')[0],
      id: 'filter-after',
      className: `date ${['between', 'after'].includes(date_range) ? '' : 'hidden'}`,
      name: 'after'
    })}

		  ${input({
      type: 'date',
      value: before.split(' ')[0],
      id: 'filter-before',
      className: `value ${['between', 'before'].includes(date_range) ? '' : 'hidden'}`,
      name: 'before'
    })}`
  }

//  REGISTER ALL FILTERS HERE
  const BasicTextFilter = (name) => ({
    name,
    view ({ compare, value }) {
      return ComparisonsTitleGenerators[compare](`<b>${name}</b>`, `<b>"${value}"</b>`)
    },
    edit ({ compare, value }) {
      // language=html
      return `${select({
		  id: 'filter-compare',
		  name: 'compare',
	  }, AllComparisons, compare)} ${input({
		  id: 'filter-value',
		  name: 'value',
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

  registerFilterGroup('contact', _x('Contact', 'noun referring to a person in the crm', 'groundhogg'))
  registerFilterGroup('location', _x('Contact Location', 'contact is a noun referring to a person', 'groundhogg'))
  registerFilterGroup('user', __('User', 'groundhogg'))
  registerFilterGroup('activity', _x('Activity', 'noun referring to a persons past activities', 'groundhogg'))

  registerFilter('first_name', 'contact', {
    ...BasicTextFilter(__('First Name', 'groundhogg'))
  })

  registerFilter('last_name', 'contact', {
    ...BasicTextFilter(__('Last Name', 'groundhogg'))
  })

  registerFilter('email', 'contact', {
    ...BasicTextFilter(__('Email Address', 'groundhogg')),
  })

  const phoneTypes = {
    primary: __('Primary Phone', 'groundhogg'),
    mobile: __('Mobile Phone', 'groundhogg'),
    company: __('Company Phone', 'groundhogg')
  }

  registerFilter('phone', 'contact', {
    name: __('Phone Number', 'groundhogg'),
    view ({ phone_type = 'primary', compare, value }) {
      return ComparisonsTitleGenerators[compare](`<b>${phoneTypes[phone_type]}</b>`, `<b>"${value}"</b>`)
    },
    edit ({ phone_type, compare, value }) {
      // language=html
      return `${select({
		  id: 'filter-phone-type',
		  name: 'phone_type',
	  }, phoneTypes, phone_type)}
	  ${select({
		  id: 'filter-compare',
		  name: 'compare',
	  }, AllComparisons, compare)} ${input({
		  id: 'filter-value',
		  name: 'value',
		  value
	  })}`
    },
    onMount (filter, updateFilter) {
      // console.log(filter)

      $('#filter-phone-type, #filter-compare, #filter-value').on('change', function (e) {
        // console.log(e)
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val()
        })
      })
    },
    defaults: {
      phone_type: 'primary',
      compare: 'equals',
      value: ''
    }
  })

  // registerFilter('primary_phone', 'contact', {}, 'Primary Phone')
  // registerFilter('mobile_phone', 'contact', {}, 'Mobile Phone')
  // registerFilter('birthday', 'contact', {}, 'Birthday')

  registerFilter('date_created', 'contact', __('Date Created', 'groundhogg'), {
    view (filter) {
      //language=HTMl
      return standardActivityDateTitle('<b>Created</b>', filter)
    },
    edit (filter) {
      // language=html
      return standardActivityDateOptions(filter)
    },
    onMount (filter, updateFilter) {
      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults
    }
  })

  const { optin_status, owners, countries, roles } = Groundhogg.filters

  registerFilter('optin_status', 'contact', __('Optin Status', 'groundhogg'), {
    view ({ compare, value }) {
      const func = compare === 'in' ? orList : andList
      return ComparisonsTitleGenerators[compare](`<b>${__('Optin Status', 'groundhogg')}</b>`, func(value.map(v => `<b>${optin_status[v]}</b>`)))
    },
    edit ({ compare, value }) {

      // language=html
      return `
		  ${select({
			  id: 'filter-compare',
			  name: 'compare',
			  class: '',
		  }, {
			  in: _x('Is one of', 'comparison, groundhogg'),
			  not_in: _x('Is not one of', 'comparison', 'groundhogg')
		  }, compare)}
		  ${select({
				  id: 'filter-value',
				  name: 'value',
				  class: 'gh-select2',
				  multiple: true
			  },
			  Object.keys(optin_status).map(k => ({ value: k, text: optin_status[k] })),
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
  })

  const userDisplay = (user) => {
    return `${user.data.user_login} (${user.data.user_email})`
  }

  registerFilter('owner', 'contact', __('Owner', 'groundhogg'), {
    view ({ compare, value }) {

      const ownerName = (ID) => {
        let user = owners.find(owner => owner.ID == ID)
        return userDisplay(user)
      }

      const func = compare === 'in' ? orList : andList
      return ComparisonsTitleGenerators[compare](`<b>${__('Contact Owner', 'groundhogg')}</b>`, func(value.map(v => `<b>${ownerName(v)}</b>`)))

    },
    edit ({ compare, value }) {

      // language=html
      return `
		  ${select({
			  id: 'filter-compare',
			  name: 'compare',
		  }, {
			  in: _x('Is one of', 'comparison, groundhogg'),
			  not_in: _x('Is not one of', 'comparison', 'groundhogg')
		  }, compare)}

		  ${select({
				  id: 'filter-value',
				  name: 'value',
				  multiple: true,
			  },
			  owners.map(u => ({ value: u.ID, text: userDisplay(u) })),
			  value.map(id => parseInt(id))
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
      /*  value: '',
        value2: ''*/
    }
  })

  registerFilter('tags', 'contact', _x('Tags', 'noun referring to contact segments', 'groundhogg'), {
    view ({ tags = [], compare, compare2 }) {

      if (!tags) {
        return 'tags'
      }

      const tagNames = tags.map(id =>
        `<b>${TagsStore.get(parseInt(id)).data.tag_name}</b>`)
      const func = compare2 === 'any' ? orList : andList

      return ComparisonsTitleGenerators[compare](`<b>${_x('Tags', 'noun referring to contact segments', 'groundhogg')}</b>`, func(tagNames))
    },
    edit ({ tags, compare, compare2 }) {

      tags = tags.map(id => parseInt(id))

      // language=html
      return `${select({
		  id: 'filter-compare',
		  name: 'compare',
	  }, {
		  includes: _x('Includes', 'comparison', 'groundhogg'),
		  excludes: _x('Excludes', 'comparison', 'groundhogg'),
	  }, compare)}

	  ${select({
		  id: 'filter-compare2',
		  name: 'compare2',
	  }, {
		  any: __('Any', 'groundhogg'),
		  all: __('All', 'groundhogg'),
	  }, compare2)
	  }

	  ${select({
			  id: 'filter-tags',
			  name: 'tags',
			  className: 'tag-picker',
			  multiple: true,
		  }
		  ,
		  tags.map(id => ({
			  value: id,
			  text: TagsStore.get(id).data.tag_name
		  })), tags
	  )}`
    },
    onMount (filter, updateFilter) {

      tagPicker('#filter-tags', true, (items) => {
        TagsStore.itemsFetched(items)
      }).on('change', (e) => {
        updateFilter({
          tags: $(e.target).val()
        })
      })

      $('#filter-compare, #filter-compare2').on('change', function (e) {
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val()
        })
      })
    },
    defaults: {
      compare: 'includes',
      compare2: 'any',
      tags: []
    },
    preload: ({ tags }) => {
      return TagsStore.fetchItems({
        tag_id: tags
      })
    }
  })

  registerFilter('meta', 'contact', __('Custom meta', 'groundhogg'), {
    view ({ meta, compare, value }) {
      return ComparisonsTitleGenerators[compare](`<b>${meta}</b>`, `<b>"${value}"</b>`)
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
		  }, AllComparisons, compare)} ${input({
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
  })

  registerFilter('is_user', 'user', __('Has User Account', 'groundhogg'), {
    view () {
      return __('Has a user account', 'groundhogg')
    },
    edit () {
      // language=html
      return ''
    },
    onMount (filter, updateFilter) {},
    defaults: {}
  })

  registerFilter('user_role_is', 'user', __('User role is', 'groundhogg'), {
    view ({ role = 'subscriber' }) {
      return sprintf(__('User role is %s', 'groundhogg'), bold(role ? roles[role].name : ''))
    },
    edit ({ role }) {

      // language=html
      return `${select({
        id: 'filter-role',
        name: 'role',
      }, Object.keys(roles).map(r => ({ text: roles[r].name, value: r })), role)}`
    },
    onMount (filter, updateFilter) {

      $('#filter-role').select2({
        placeholder: __('Select a role', 'groundhogg')
      }).on('change', function (e) {
        const $el = $(this)
        updateFilter({
          role: $el.val()
        })
      })
    },
    defaults: {
      role: 'subscriber'
    },
  })

  registerFilter('country', 'location', __('Country', 'groundhogg'), {
    view ({ country }) {
      return sprintf(__('Country is %s', 'groundhogg'), bold(countries[country]))
    },
    edit ({ country }) {
      // language=html
      return `
		  ${select({
			  id: 'filter-country',
			  name: 'country',
		  }, countries, country)}`
    },
    onMount (filter, updateFilter) {

      $('#filter-country').select2().on('change', function (e) {
        const $el = $(this)
        updateFilter({
          country: $el.val()
        })
      })
    },
    defaults: {
      country: '',
    }
  })

//filter by Email Opened
  registerFilter('email_opened', 'activity', __('Email Opened', 'groundhogg'), {
    view ({ email_id, date_range, before, after }) {
      const emailName = email_id ? EmailsStore.get(email_id).data.title : 'any email'
      return standardActivityDateTitle(sprintf(_x('Opened %s', '%s is an email', 'groundhogg'), `<b>${emailName}</b>`), {
        date_range,
        before,
        after
      })
    },
    edit ({ email_id, date_range, before, after }) {

      const pickerOptions = email_id ? {
        [email_id]: EmailsStore.get(email_id).data.title
      } : {}

      // language=html
      return `
		  ${select({
			  id: 'filter-email',
			  name: 'email_id',
		  }, pickerOptions, email_id)}

		  ${standardActivityDateOptions({
			  date_range, after, before
		  })}`
    },
    onMount (filter, updateFilter) {
      emailPicker('#filter-email', false, (items) => {
        EmailsStore.itemsFetched(items)
      }, {}, {
        placeholder: __('Please select an email or leave blank for any email', 'groundhogg')
      }).on('change', (e) => {
        updateFilter({
          email_id: parseInt(e.target.value)
        })
      })

      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
      email_id: 0,
    },
    preload: ({ email_id }) => {
      if (email_id) {
        return EmailsStore.fetchItem(email_id)
      }
    }
  })

//filter by Email Opened
  registerFilter('email_link_clicked', 'activity', __('Email Link Clicked', 'groundhogg'), {
    view ({ email_id, link, date_range, before, after }) {

      const emailName = email_id ? EmailsStore.get(email_id).data.title : 'any email'

      prepend = sprintf(link ? __('Clicked %1$s in %2$s', 'groundhogg') : __('Clicked any link in %2$s', 'groundhogg'), `<b>${link}</b>`, `<b>${emailName}</b>`)

      return standardActivityDateTitle(prepend, {
        date_range,
        before,
        after
      })
    },
    edit ({ email_id, date_range, before, after }) {

      const pickerOptions = email_id ? {
        [email_id]: EmailsStore.get(email_id).data.title
      } : {}

      // language=html
      return `
		  ${select({
			  id: 'filter-email',
			  name: 'email_id',
		  }, pickerOptions, email_id)}

		  ${input({
			  id: 'filter-link',
			  name: 'link',
			  autocomplete: 'off',
			  placeholder: __('Start typing to select a link or leave blank for any link', 'groundhogg')
		  })}

		  ${standardActivityDateOptions({
			  date_range, before, after
		  })}`
    },
    onMount (filter, updateFilter) {
      emailPicker('#filter-email', false, (items) => {
        EmailsStore.itemsFetched(items)
      }, {}, {
        placeholder: __('Please select an email or leave blank for any email', 'groundhogg')
      }).on('change', (e) => {
        updateFilter({
          email_id: parseInt(e.target.value)
        })
      })

      linkPicker('#filter-link').on('change input blur', ({ target }) => {
        updateFilter({
          link: target.value
        })
      })

      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
      link: '',
      email_id: 0,
    },
    preload: ({ email_id }) => {
      if (email_id) {
        return EmailsStore.fetchItem(email_id)
      }
    }
  })

  registerFilter('confirmed_email', 'activity', __('Confirmed Email Address', 'groundhogg'), {
    view ({ date_range, before, after }) {
      return standardActivityDateTitle(`<b>${__('Confirmed Email Address', 'groundhogg')}</b>`, {
        date_range,
        before,
        after
      })
    },
    edit ({ date_range, before, after }) {
      return standardActivityDateOptions({ date_range, before, after })
    },
    onMount (filter, updateFilter) {
      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
    }
  })

//filter by User Logged In
  registerFilter('logged_in', 'activity', __('Logged In', 'groundhogg'), {
    view ({ date_range, before, after }) {
      return standardActivityDateTitle(`<b>${__('Logged in', 'groundhogg')}</b>`, { date_range, before, after })
    },
    edit ({ date_range, before, after }) {
      return standardActivityDateOptions({ date_range, before, after })
    },
    onMount (filter, updateFilter) {
      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
    }
  })

  registerFilter('logged_out', 'activity', __('Logged Out', 'groundhogg'), {
    view ({ date_range, before, after }) {
      return standardActivityDateTitle(`<b>${__('Logged out', 'groundhogg')}</b>`, { date_range, before, after })
    },
    edit ({ date_range, before, after }) {
      return standardActivityDateOptions({ date_range, before, after })
    },
    onMount (filter, updateFilter) {
      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
    }
  })

//filter by User Not Logged In
  registerFilter('not_logged_in', 'activity', __('Has Not Logged In', 'groundhogg'), {
    view ({ date_range, before, after }) {
      return standardActivityDateTitle(`<b>${__('Has not logged in', 'groundhogg')}</b>`, { date_range, before, after })
    },
    edit ({ date_range, before, after }) {
      return standardActivityDateOptions({ date_range, before, after })
    },
    onMount (filter, updateFilter) {
      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
    }
  })

//filter by User Was Active
  registerFilter('was_active', 'activity', __('Was Active', 'groundhogg'), {
    view ({ date_range, before, after }) {
      return standardActivityDateTitle(`<b>${__('Was active', 'groundhogg')}</b>`, { date_range, before, after })
    },
    edit ({ date_range, before, after }) {
      return standardActivityDateOptions({ date_range, before, after })
    },
    onMount (filter, updateFilter) {
      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
    }
  })

//filter By User Was Not Active
  registerFilter('was_not_active', 'activity', __('Was Inactive', 'groundhogg'), {
    view ({ date_range, before, after }) {
      return standardActivityDateTitle(`<b>${__('Was inactive', 'groundhogg')}</b>`, { date_range, before, after })
    },
    edit ({ date_range, before, after }) {
      return standardActivityDateOptions({ date_range, before, after })
    },
    onMount (filter, updateFilter) {
      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      ...standardActivityDateDefaults,
    }
  })

// Other Filters to Add
// Location (Country,Province)
// Phones (Primary,Mobile)
// Tags

  registerFilterGroup('funnels', _x('Funnel', 'noun meaning automation', 'groundhogg'))

  registerFilter('funnel_history', 'funnels', __('Funnel History', 'groundhogg'), {
    view ({ status, funnel_id, step_id, date_range, before, after }) {

      let prepend

      if (funnel_id) {

        const funnel = FunnelsStore.get(funnel_id)
        const step = funnel.steps.find(s => s.ID === step_id)

        prepend = status === 'complete' ?
          sprintf(step ? __('Completed %2$s in %1$s', 'groundhogg') : __('Completed any step in %1$s', 'groundhogg'), `<b>${funnel.data.title}</b>`, step ? `<b>${step.data.step_title}</b>` : '')
          : sprintf(step ? __('Will complete %2$s in %1$s', 'groundhogg') : __('Will complete any step in %1$s', 'groundhogg'), `<b>${funnel.data.title}</b>`, step ? `<b>${step.data.step_title}</b>` : '')

        if (status === 'waiting') {
          return prepend
        }

      } else {
        prepend = __('Completed any step in any funnel', 'groundhogg')
      }

      return standardActivityDateTitle(prepend, {
        date_range,
        before,
        after
      })
    },
    edit ({ funnel_id, step_id, date_range, before, after }) {

      return `
      ${select({
          id: 'filter-funnel',
          name: 'funnel_id'
        }, FunnelsStore.getItems().map(f => ({ value: f.ID, text: f.data.title })),
        funnel_id)}
      ${select({
        id: 'filter-step',
        name: 'step_id'
      }, funnel_id ? FunnelsStore.get(funnel_id).steps.map(s => ({
        value: s.ID,
        text: s.data.step_title
      })) : [], step_id)}
      ${standardActivityDateOptions({ date_range, before, after })}`
    },
    onMount (filter, updateFilter) {
      funnelPicker('#filter-funnel', false, (items) => {
        FunnelsStore.itemsFetched(items)
      }, {}, {
        placeholder: __('Select a funnel', 'groundhogg')
      }).on('select2:select', ({ target }) => {
        updateFilter({
          funnel_id: parseInt($(target).val()),
          step_id: 0
        }, true)
      })

      $('#filter-step').select2({
        placeholder: __('Select a step or leave empty for any step', 'groundhogg')
      }).on('select2:select', ({ target }) => {
        updateFilter({
          step_id: parseInt($(target).val())
        })
      })

      standardActivityDateFilterOnMount(filter, updateFilter)
    },
    defaults: {
      funnel_id: 0,
      step_id: 0,
      status: 'complete',
      ...standardActivityDateDefaults,
    },
    preload: ({ funnel_id }) => {
      if (funnel_id) {
        return FunnelsStore.fetchItem(funnel_id)
      }
    }
  })

  registerFilterGroup('broadcast', _x('Broadcast', 'noun meaning email blast', 'groundhogg'))

  registerFilter('broadcast_received', 'broadcast', __('Received Broadcast', 'groundhogg'), {
    view ({ broadcast_id, status }) {

      if (!broadcast_id) {
        return __('Received any broadcast', 'groundhogg')
      }

      const broadcast = BroadcastsStore.get(broadcast_id)

      return status === 'complete' ?
        sprintf(broadcast ? __('Received %1$s on %2$s', 'groundhogg') : __('Will receive a broadcast', 'groundhogg'), `<b>${broadcast.object.data.title}</b>`, `<b>${formatDateTime(broadcast.data.send_time * 1000)}</b>`)
        : sprintf(broadcast ? __('Will receive %1$s on %2$s', 'groundhogg') : __('Received a broadcast', 'groundhogg'), `<b>${broadcast.object.data.title}</b>`, `<b>${formatDateTime(broadcast.data.send_time * 1000)}</b>`)
    },
    edit ({ broadcast_id }) {

      return select({
          id: 'filter-broadcast',
          name: 'broadcast_id'
        }, BroadcastsStore.getItems().map(b => ({ value: b.ID, text: `${b.object.data.title} (${b.date_sent_pretty})` })),
        broadcast_id)
    },
    onMount (filter, updateFilter) {
      broadcastPicker('#filter-broadcast', false, (items) => {
        BroadcastsStore.itemsFetched(items)
      }, {}, {
        placeholder: __('Select a broadcast', 'groundhogg')
      }).on('select2:select', ({ target }) => {
        updateFilter({
          broadcast_id: parseInt($(target).val()),
        })
      })
    },
    defaults: {
      broadcast_id: 0,
      status: 'complete',
    },
    preload: ({ broadcast_id }) => {
      if (broadcast_id) {
        return BroadcastsStore.fetchItem(broadcast_id)
      }
    }
  })

  registerFilter('broadcast_opened', 'broadcast', __('Opened Broadcast', 'groundhogg'), {
    view ({ broadcast_id }) {

      if (!broadcast_id) {
        return __('Opened any broadcast', 'groundhogg')
      }

      const broadcast = BroadcastsStore.get(broadcast_id)

      return sprintf(broadcast ? __('Opened %1$s after %2$s', 'groundhogg') : __('Will receive a broadcast', 'groundhogg'), `<b>${broadcast.object.data.title}</b>`, `<b>${formatDateTime(broadcast.data.send_time * 1000)}</b>`)

    },
    edit ({ broadcast_id }) {

      return select({
          id: 'filter-broadcast',
          name: 'broadcast_id'
        }, BroadcastsStore.getItems().map(b => ({ value: b.ID, text: `${b.object.data.title} (${b.date_sent_pretty})` })),
        broadcast_id)
    },
    onMount (filter, updateFilter) {
      broadcastPicker('#filter-broadcast', false, (items) => {
        BroadcastsStore.itemsFetched(items)
      }, {}, {
        placeholder: __('Select a broadcast', 'groundhogg')
      }).on('select2:select', ({ target }) => {
        updateFilter({
          broadcast_id: parseInt($(target).val()),
        })
      })
    },
    defaults: {
      broadcast_id: 0,
    },
    preload: ({ broadcast_id }) => {
      if (broadcast_id) {
        return BroadcastsStore.fetchItem(broadcast_id)
      }
    }
  })

  registerFilter('broadcast_link_clicked', 'broadcast', __('Broadcast Link Clicked', 'groundhogg'), {
    view ({ broadcast_id, link }) {

      if (!broadcast_id && !link) {
        return __('Clicked any link in any broadcast', 'groundhogg')
      }

      if (!broadcast_id && link) {
        return sprintf(__('Clicked %s in any broadcast', 'groundhogg'), bold(link))
      }

      const broadcast = BroadcastsStore.get(broadcast_id)

      if (broadcast_id && !link) {
        return sprintf(__('Clicked any link in %1$s after %2$s', 'groundhogg'), bold(broadcast.object.data.title), bold(formatDateTime(broadcast.data.send_time * 1000)))
      }

      return sprintf(__('Clicked %1$s in %2$s after %3$s', 'groundhogg'), bold(link), bold(broadcast.object.data.title), bold(formatDateTime(broadcast.data.send_time * 1000)))
    },
    edit ({ broadcast_id, link }) {

      // language=html
      return `
		  ${select({
				  id: 'filter-broadcast',
				  name: 'broadcast_id'
			  }, BroadcastsStore.getItems().map(b => ({
				  value: b.ID,
				  text: `${b.object.data.title} (${b.date_sent_pretty})`
			  })),
			  broadcast_id)}

		  ${input({
			  id: 'filter-link',
			  name: 'link',
			  value: link,
			  autocomplete: 'off',
			  placeholder: __('Start typing to select a link or leave blank for any link', 'groundhogg')
		  })}`
    },
    onMount (filter, updateFilter) {
      broadcastPicker('#filter-broadcast', false, (items) => {
        BroadcastsStore.itemsFetched(items)
      }, {}, {
        placeholder: __('Please select a broadcast or leave blank for any broadcast', 'groundhogg')
      }).on('change', (e) => {
        updateFilter({
          broadcast_id: parseInt(e.target.value)
        })
      })

      linkPicker('#filter-link').on('change input blur', ({ target }) => {
        updateFilter({
          link: target.value
        })
      })
    },
    defaults: {
      link: '',
      broadcast_id: 0,
    },
    preload: ({ broadcast_id }) => {
      if (broadcast_id) {
        return BroadcastsStore.fetchItem(broadcast_id)
      }
    }
  })

  Groundhogg.filters.functions = {
    createFilters,
    registerFilter,
    registerFilterGroup,
    ComparisonsTitleGenerators,
    AllComparisons,
    NumericComparisons,
    StringComparisons,
    standardActivityDateOptions,
    standardActivityDateTitle,
    standardActivityDateDefaults,
    standardActivityDateFilterOnMount
  }

})
(jQuery)