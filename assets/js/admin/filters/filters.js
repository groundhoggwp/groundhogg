( ($) => {

  const {
    searchOptionsWidget,
    loadingDots,
    adminPageURL,
    bold,
    uuid,
    regexp,
    andList,
    clickedIn,
    orList,
  } = Groundhogg.element

  const {
    Div,
    Button,
    ItemPicker,
    Fragment,
    Input,
    Select,
    Span,
    Dashicon,
    ToolTip,
  } = MakeEl

  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n
  const { base64_json_encode } = Groundhogg.functions

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
    less_than_or_equal_to: _x('Less than or equal to', 'comparison',
      'groundhogg'),
    greater_than: _x('Greater than', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('Greater than or equal to', 'comparison',
      'groundhogg'),
    empty: _x('Is empty', 'comparison', 'groundhogg'),
    not_empty: _x('Is not empty', 'comparison', 'groundhogg'),
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
    not_empty: _x('Is not empty', 'comparison', 'groundhogg'),
  }

  const NumericComparisons = {
    equals: _x('Equals', 'comparison', 'groundhogg'),
    not_equals: _x('Not equals', 'comparison', 'groundhogg'),
    less_than: _x('Less than', 'comparison', 'groundhogg'),
    less_than_or_equal_to: _x('Less than or equal to', 'comparison',
      'groundhogg'),
    greater_than: _x('Greater than', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('Greater than or equal to', 'comparison',
      'groundhogg'),
  }

  const ComparisonsTitleGenerators = {
    equals: (k, v) => sprintf(
      _x('%1$s equals %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_equals: (k, v) => sprintf(
      _x('%1$s does not equal %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    contains: (k, v) => sprintf(
      _x('%1$s contains %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_contains: (k, v) => sprintf(
      _x('%1$s does not contain %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    starts_with: (k, v) => sprintf(
      _x('%1$s starts with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    ends_with: (k, v) => sprintf(
      _x('%1$s ends with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    does_not_start_with: (k, v) => sprintf(
      _x('%1$s does not start with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    does_not_end_with: (k, v) => sprintf(
      _x('%1$s does not end with %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    less_than: (k, v) => sprintf(
      _x('%1$s is less than %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    less_than_or_equal_to: (k, v) => sprintf(
      _x('%1$s is less than or equal to %2$s',
        '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    greater_than: (k, v) => sprintf(
      _x('%1$s is greater than %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    greater_than_or_equal_to: (k, v) => sprintf(
      _x('%1$s is greater than or equal to %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    in: (k, v) => sprintf(
      _x('%1$s is %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_in: (k, v) => sprintf(
      _x('%1$s is not %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    empty: (k, v) => sprintf(
      _x('%1$s is empty', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    not_empty: (k, v) => sprintf(
      _x('%1$s is not empty', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    includes: (k, v) => sprintf(
      _x('%1$s includes %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    excludes: (k, v) => sprintf(
      _x('%1$s excludes %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    before: (k, v) => sprintf(
      _x('%1$s is before %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    after: (k, v) => sprintf(
      _x('%1$s is after %2$s', '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    between: (k, v, v2) => sprintf(
      _x('%1$s is between %2$s and %3$s', '%1 is a key and %2 and %3 are user defined values', 'groundhogg'), k, v, v2),
  }

  const pastDateRanges = {
    'any': __('At any time', 'groundhogg'),
    'today': __('Today', 'groundhogg'),
    'this_week': __('This week', 'groundhogg'),
    'this_month': __('This month', 'groundhogg'),
    'this_year': __('This year', 'groundhogg'),
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

  const futureDateRanges = {
    'f_any': __('At any time', 'groundhogg'),
    'f_today': __('Today', 'groundhogg'),
    'f_this_week': __('This week', 'groundhogg'),
    'f_this_month': __('This month', 'groundhogg'),
    'f_this_year': __('This year', 'groundhogg'),
    'f_24_hours': __('In the next 24 hours', 'groundhogg'),
    'f_7_days': __('In the next 7 days', 'groundhogg'),
    'f_30_days': __('In the next 30 days', 'groundhogg'),
    'f_60_days': __('In the next 60 days', 'groundhogg'),
    'f_90_days': __('In the next 90 days', 'groundhogg'),
    'f_365_days': __('In the next 365 days', 'groundhogg'),
    'before': __('Before', 'groundhogg'),
    'after': __('After', 'groundhogg'),
    'between': __('Between', 'groundhogg'),
  }

  const activityFilterComparisons = {
    equals: _x('Exactly', 'comparison', 'groundhogg'),
    less_than: _x('Less than', 'comparison', 'groundhogg'),
    greater_than: _x('More than', 'comparison', 'groundhogg'),
    less_than_or_equal_to: _x('At most', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('At least', 'comparison', 'groundhogg'),
  }

  const filterCountComparisons = {
    equals: (v) => sprintf(_n('%s time', '%s times', parseInt(v), 'groundhogg'),
      v),
    less_than: (v) => sprintf(
      _n('less than %s time', 'less than %s times', parseInt(v), 'groundhogg'),
      v),
    less_than_or_equal_to: (v) => sprintf(
      _n('at most %s time', 'at most %s times', parseInt(v), 'groundhogg'), v),
    greater_than: (v) => sprintf(
      _n('more than %s time', 'more than %s times', parseInt(v), 'groundhogg'),
      v),
    greater_than_or_equal_to: (v) => sprintf(
      _n('at least %s time', 'at least %s times', parseInt(v), 'groundhogg'),
      v),
  }

  const moreComparisonTitleGenerators = {
    all_checked: (prefix, options) => sprintf(
      __('%2$s is checked for %1$s', 'groundhogg-better-meta'), prefix,
      andList(options.map(b => bold(b)))),
    not_checked: (prefix, options) => sprintf(
      __('%2$s is not checked for %1$s', 'groundhogg-better-meta'), prefix,
      andList(options.map(b => bold(b)))),
    all_in: (prefix, options) => sprintf(
      __('%2$s is selected for %1$s', 'groundhogg-better-meta'), prefix,
      andList(options.map(b => bold(b)))),
    all_not_in: (prefix, options) => sprintf(
      __('%2$s is not selected for %1$s', 'groundhogg-better-meta'), prefix,
      andList(options.map(b => bold(b)))),
  }

  /**
   * Creates a filter group
   *
   * @param id
   * @param name
   * @returns {{name, id}}
   */
  const createGroup = (id, name) => ( { id, name } )

  /**
   * Create a filter base function
   *
   * @param type
   * @param name
   * @param group
   * @param edit
   * @param display
   * @param preload
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}}
   */
  const createFilter = (
    type, name, group, { edit = () => null, display = () => null, preload = () => {} }, defaults = {}) => ( {
    type,
    name,
    group,
    edit,
    display,
    preload,
    defaults,
  } )

  /**
   * Create a string comparison filter
   *
   * @param type
   * @param name
   * @param group
   * @param edit
   * @param display
   * @param preload
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}}
   */
  const createStringFilter = (
    type, name, group, { edit = () => null, display = () => null, preload = () => {} } = {},
    defaults = {}) => createFilter(
    type,
    name,
    group,
    {
      edit: ({ value, compare, updateFilter, ...rest }) => Fragment([
        edit({ ...rest, updateFilter }),
        Select({
          id: 'filter-compare',
          name: 'filter_compare',
          options: StringComparisons,
          selected: compare,
          onChange: e => updateFilter({
            compare: e.target.value,
          }),
        }),
        Input({
          id: 'filter-value',
          value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        }),
      ]),
      display: ({ compare, value, ...rest }) => {
        return Fragment(ComparisonsTitleGenerators[compare](bold(name), bold(value)))
      },
      preload,
    },
    {
      value: '',
      compare: 'equals',
      ...defaults,
    },
  )

  /**
   * Create a filter that allow you to compare a number value
   *
   * @param type
   * @param name
   * @param group
   * @param edit
   * @param display
   * @param preload
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}}
   */
  const createNumberFilter = (
    type, name, group, { edit = () => null, display = () => null, preload = () => {} } = {},
    defaults = {}) => createFilter(
    type,
    name,
    group,
    {
      edit: ({ value, compare, updateFilter, ...rest }) => Fragment([
        edit({ ...rest, updateFilter }),
        Select({
          id: 'filter-compare',
          name: 'filter_compare',
          options: NumericComparisons,
          selected: compare,
          onChange: e => updateFilter({
            compare: e.target.value,
          }),
        }),
        Input({
          type: 'number',
          id: 'filter-value',
          value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        }),
      ]),
      display: ({ compare, value, ...rest }) => {
        return Fragment(ComparisonsTitleGenerators[compare](bold(name), bold(value)))
      },
      preload,
    },
    {
      value: '',
      compare: 'equals',
      ...defaults,
    },
  )

  /**
   * Create a filter that compares against previous dates
   *
   * @param type
   * @param name
   * @param group
   * @param edit
   * @param display
   * @param preload
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}}
   */
  const createPastDateFilter = (
    type, name, group, { edit = () => null, display = () => null, preload = () => {} } = {},
    defaults = {}) => createFilter(type,
    name, group, {
      edit: ({ date_range, before, after, updateFilter, ...rest }) => Fragment([
        edit({ ...rest, updateFilter }),
        Select({
          id: 'filter-date-range',
          name: 'date_range',
          options: pastDateRanges,
          selected: date_range,
          onChange: e => updateFilter({
            date_range: e.target.value,
          }),
        }),
        date_range === 'after' || date_range === 'between' ? Input({
          type: 'date',
          value: after.split(' ')[0],
          id: 'filter-after',
          onChange: e => updateFilter({
            after: e.target.value,
          }),
        }) : null,
        date_range === 'before' || date_range === 'between' ? Input({
          type: 'date',
          value: before.split(' ')[0],
          id: 'filter-before',
          onChange: e => updateFilter({
            before: e.target.value,
          }),
        }) : null,
      ]),
      display: ({ date_range, after, before, ...rest }) => {

        switch (date_range) {
          case 'between':
            return ComparisonsTitleGenerators.between(bold(name), formatDate(after), formatDate(before))
          case 'after':
            return ComparisonsTitleGenerators.after(bold(name), formatDate(after))
          case 'before':
            return ComparisonsTitleGenerators.before(bold(name), formatDate(before))
          default:
            return sprintf('%s %s', bold(name), pastDateRanges[date_range].toLowerCase())
        }
      },
      preload,
    }, {
      ...defaults,
      date_range: '24_hours',
      before: '',
      after: '',
    })

  /**
   * Create a filter that compares a value to given dates in the future
   *
   * @param type
   * @param name
   * @param group
   * @param edit
   * @param display
   * @param preload
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}}
   */
  const createFutureDateFilter = (
    type, name, group, { edit = () => null, display = () => null, preload = () => {} } = {},
    defaults = {}) => createFilter(type,
    name, group, {
      edit: ({ date_range, before, after, updateFilter, ...rest }) => Fragment([
        edit({ ...rest, updateFilter }),
        Select({
          id: 'filter-date-range',
          name: 'date_range',
          options: futureDateRanges,
          selected: date_range,
          onChange: e => updateFilter({
            date_range: e.target.value,
          }),
        }),
        date_range === 'after' || date_range === 'between' ? Input({
          type: 'date',
          value: after.split(' ')[0],
          id: 'filter-after',
          onChange: e => updateFilter({
            after: e.target.value,
          }),
        }) : null,
        date_range === 'before' || date_range === 'between' ? Input({
          type: 'date',
          value: before.split(' ')[0],
          id: 'filter-before',
          onChange: e => updateFilter({
            before: e.target.value,
          }),
        }) : null,
      ]),
      display: ({ date_range, after, before, ...rest }) => {

        switch (date_range) {
          case 'between':
            return ComparisonsTitleGenerators.between(bold(name), formatDate(after), formatDate(before))
          case 'after':
            return ComparisonsTitleGenerators.after(bold(name), formatDate(after))
          case 'before':
            return ComparisonsTitleGenerators.before(bold(name), formatDate(before))
          default:
            return sprintf('%s %s', bold(name), futureDateRanges[date_range].toLowerCase())
        }
      },
      preload,
    }, {
      ...defaults,
      date_range: 'f_24_hours',
      before: '',
      after: '',
    })

  /**
   * Create a select filter, which is just a comparison filter with fixed values
   *
   * @param type
   * @param name
   * @param group
   * @param options
   * @returns {{defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}}
   */
  const createSelectFilter = (type, name, group, options ) => createFilter(
    type,
    name,
    group,
    {
      display: ({ value }) => sprintf('%s is %s', bold(name), bold(options[value])),
      edit: ({ value, updateFilter }) => Select({
        id: 'filter-select',
        options,
        selected: value,
        onChange: e => updateFilter({ value: e.target.value }),
      }),
    },
    {
      value: options[Object.keys(options)[0]]
    }
  )

  /**
   * Create a filters registry
   *
   * @param groups
   * @param filters
   * @returns {{getFilter({type: *}): *, edit(*, *): *, preloadFilters(*): Promise<Awaited<unknown>[]>, displayName(*):
   *   *, registerGroup(), filterName(*): *, groups: {}, filters: {}, registerFilter(), preloadFilter(*): *}}
   * @constructor
   */
  const FilterRegistry = ({
    groups = [],
    filters = [],
  } = {}) => ( {

    groups,
    filters,

    registerGroup (group) {
      this.groups.push(group)
    },

    registerFilter (filter) {
      if (this.getFilter(filter)) {
        return
      }

      this.filters.push(filter)
    },

    displayName (filter) {

      let name = this.getFilter(filter).display(filter)

      if (!name) {
        name = this.getFilter(filter).name
      }

      return name
    },

    filterName (filter) {
      return this.getFilter(filter).name
    },

    edit (filter, updateFilter) {
      return this.getFilter(filter).edit({ ...filter, updateFilter })
    },

    getFilter ({ type }) {
      return this.filters.find(f => f.type === type)
    },

    preloadFilter (filter) {
      return this.getFilter(filter).preload(filter)
    },

    preloadFilters (filters) {

      const promises = []

      filters.forEach(filterGroup => filterGroup.forEach(filter => {
        const promise = this.preloadFilter(filter)
        if (promise) {
          promises.push(promise)
        }
      }))

      return Promise.all(promises)

    },
  } )

  /**
   * Create a filters editor
   *
   * @param id
   * @param filterRegistry
   * @param filters
   * @param onChange
   * @returns HTMLElement
   * @constructor
   */
  const Filters = ({
    id,
    filterRegistry = FilterRegistry({}),
    filters = [],
    onChange = (filters) => {},
  }) => {

    /**
     * Morhps the filters
     */
    const morph = () => {
      try {
        morphdom(document.getElementById(id), FiltersEditor())
      }
      catch (e) {
        // not in the dom yet
        console.log(e)
      }
    }

    let State = {
      preloaded: false,
      activeFilter: null,
    }

    /**
     * Updates the current state of the filters
     *
     * @param newState
     * @param doMorph
     */
    const setState = (newState, doMorph = true) => {
      State = {
        ...State,
        ...newState,
      }

      console.log(State)

      if (doMorph) {
        morph()
      }
    }

    /**
     * A broken filter pill
     *
     * @param filter
     * @param group
     * @param index
     * @returns HTMLElement
     * @constructor
     */
    const FilterBroken = (filter, group, index) => Div({
      id: `filter-${ filter.id }`,
      className: 'filter filter-view filter-broken',
      tabindex: 0,
      onClick: e => {
        if (clickedIn(e, '.delete-filter')) {
          return
        }

        editFilter(filter.id)
      },
    }, [
      Span({
        className: 'filter-name text',
      }, sprintf(
        __('This %s filter is corrupted', 'groundhogg'),
        bold(filterRegistry.filterName(filter)))),
      Button({
        id: `delete-${ group }-${ index }`,
        className: 'delete-filter',
        onClick: e => {
          e.preventDefault()
          deleteFilter(group, index)
        },
      }, Dashicon('no-alt')),
    ])

    /**
     * The filter pill
     *
     * @param filter
     * @param group
     * @param index
     * @returns HTMLElement
     * @constructor
     */
    const Filter = (filter, group, index) => Div({
      id: `filter-${ filter.id }`,
      onClick: e => {
        if (clickedIn(e, '.delete-filter')) {
          return
        }

        editFilter(filter.id)
      },
      className: 'filter filter-view',
      tabindex: 0,
    }, [
      Span({
        className: 'filter-name text',
        // onClick: e => {
        //   editFilter(filter.id)
        // }
      }, filterRegistry.displayName(filter)),
      Button({
        id: `delete-${ group }-${ index }`,
        className: 'delete-filter',
        onClick: e => deleteFilter(group, index),
      }, Dashicon('no-alt')),
    ])

    /**
     * Returns the wrapper element for the filter settings
     *
     * @param filter
     * @param group
     * @param index
     * @returns HTMLElement
     * @constructor
     */
    const EditFilter = (filter, group, index) => {

      let tempFilterSettings = {
        ...filter,
      }

      /**
       * ONly morphs the filter settings and not all the filters
       */
      const morphFilter = () => {
        try {
          morphdom(document.getElementById(`filter-${ id }-settings`), FilterSettings())
        }
        catch (e) {
          console.log(e)
        }
      }

      /**
       * Updates temporary filter state
       *
       * @param newSettings
       */
      const updateTempFilterSettings = (newSettings) => {
        tempFilterSettings = {
          ...tempFilterSettings,
          ...newSettings,
        }

        morphFilter()
      }

      /**
       * Renders the filter settings
       *
       * @returns HTMLElement
       * @constructor
       */
      const FilterSettings = () => Div({
        id: `filter-${ id }-settings`,
        className: 'settings',
      }, filterRegistry.edit(tempFilterSettings, updateTempFilterSettings))

      return Div({
        id: `edit-filter-${ filter.id }`,
        className: 'filter filter-edit-wrap',
        tabindex: 0,
      }, Div({
        className: 'filter-edit',
      }, [

        Div({
          className: 'header',
        }, [
          bold(filterRegistry.filterName(filter)),
          Button({
            className: 'close-edit',
            onClick: e => editFilter(null),
          }, Dashicon('no-alt')),
        ]),

        FilterSettings(),

        Div({
          className: 'actions',
        }, [

          Button({
            id: `delete-${ group }-${ index }`,
            className: 'delete delete-filter',
            onClick: e => deleteFilter(group, index),
          }, Dashicon('trash')),

          Button({
            id: `commit-${ group }-${ index }`,
            className: 'commit commit-filter',
            onClick: e => updateFilter(tempFilterSettings, group, index),
          }, Dashicon('yes')),
        ]),

      ]))
    }

    /**
     * Creates a filter group, functions as AND
     *
     * @param filters
     * @param group
     * @returns HTMLElement
     * @constructor
     */
    const FilterGroup = (filters, group) => Div({
      id: `group-${ group }`,
      className: 'group',
    }, [
      ...filters.map((filter, index) => {

        try {

          if (State.activeFilter === filter.id) {
            return EditFilter(filter, group, index)
          }

          return Filter(filter, group, index)
        }
        catch (e) {
          console.log(e)
          return FilterBroken(filter, group, index)
        }

      }),
      Button({
        id: `add-filter-to-${ group }`,
        className: 'add-filter gh-has-tooltip',
        onClick: e => {

          let options = filterRegistry.filters
          let groups = filterRegistry.groups.reduce((carr, curr) => {
            carr[curr.id] = curr.name
            return carr
          }, {})

          searchOptionsWidget({
            // selector: '.add-filter-wrap',
            position: 'fixed',
            target: e.currentTarget,
            options,
            groups,
            onSelect: (option) => {
              addFilter({
                type: option.type,
                ...option.defaults,
              }, group)
            },
            filterOption: (option, search) => {
              return option.name.match(regexp(search))
            },
            renderOption: (option) => option.name,
            noOptions: __('No matching filters...', 'groundhogg'),
          }).mount()

        },
      }, [ Dashicon('plus-alt2'), ToolTip( __( 'Add a filter', 'groundhogg' ), 'right' ) ] ),
    ])

    /**
     * Adds a new filter
     *
     * @param filter
     * @param group
     */
    const addFilter = (filter, group) => {
      filter = {
        id: uuid(),
        ...filter,
      }

      if (filters[group]) {
        filters[group].push(filter)
      }
      else {
        filters.push([filter])
      }

      editFilter(filter.id)

      onChange(filters)
    }

    /**
     * Deletes a filter
     *
     * @param group
     * @param index
     */
    const deleteFilter = (group, index) => {
      filters[group].splice(index, 1)
      if (!filters[group].length) {
        filters.splice(group, 1)
      }

      editFilter(null)

      onChange(filters)
    }

    /**
     * Updates the filter with new info
     *
     * @param newFilter
     * @param group
     * @param index
     */
    const updateFilter = (newFilter, group, index) => {
      filters[group][index] = {
        ...filters[group][index],
        ...newFilter,
      }

      editFilter(null)

      onChange(filters)
    }

    /**
     * Sets the active filter in the state
     *
     * @param id
     */
    const editFilter = (id) => {
      setState({
        activeFilter: id,
      })
    }

    /**
     * Shows the ---OR----
     *
     * @param after
     * @returns HTMLElement
     * @constructor
     */
    const GroupSeparator = (after) => Div({
      id: `after-${ after }`,
      className: 'or-separator',
    }, Span({
      className: 'or-circle',
    }, _x('Or...', 'search filters separator', 'groundhogg')))

    /**
     * Loading statement
     *
     * @returns HTMLElement
     * @constructor
     */
    const FiltersLoading = () => Div({
      id,
      className: `search-filters`,
    }, Span({
      className: 'filters-loading',
    }, __('Loading')))

    /**
     * The wrapper for all the filters
     *
     * @returns HTMLElement
     * @constructor
     */
    const FiltersEditor = () => {

      if (!State.preloaded) {

        filterRegistry.preloadFilters(filters).then(() => setState({ preloaded: true }))

        return FiltersLoading()
      }

      const groups = []

      filters.forEach((filterGroup, i) => {
        groups.push(FilterGroup(filterGroup, i))
        groups.push(GroupSeparator(i))
      })

      return Div({
        id,
        className: `search-filters-editor`,
      }, [
        ...groups,
        FilterGroup([], filters.length),
      ])
    }

    return FiltersEditor()
  }

  if (!Groundhogg.filters) {
    Groundhogg.filters = {}
  }

  Groundhogg.filters.Filters = Filters
  Groundhogg.filters.FilterRegistry = FilterRegistry
  Groundhogg.filters.createFilter = createFilter
  Groundhogg.filters.createStringFilter = createStringFilter
  Groundhogg.filters.createNumberFilter = createNumberFilter
  Groundhogg.filters.createDateFilter = createPastDateFilter
  Groundhogg.filters.createSelectFilter = createSelectFilter

  if (window.GroundhoggTableFilters) {

    const {
      id = '',
      name = '',
      stringColumns = {},
      numberColumns = {},
      dateColumns = {},
      futureDateColumns = {},
      selectColumns = {},
      filters = [],
    } = GroundhoggTableFilters

    const TableFilterRegistry = FilterRegistry({})

    TableFilterRegistry.registerGroup(createGroup('table', name))

    for (let column in stringColumns) {
      TableFilterRegistry.registerFilter(createStringFilter(column, stringColumns[column], 'table'))
    }

    for (let column in numberColumns) {
      TableFilterRegistry.registerFilter(createNumberFilter(column, numberColumns[column], 'table'))
    }

    for (let column in selectColumns) {
      TableFilterRegistry.registerFilter(createSelectFilter(column, selectColumns[column][0], 'table', selectColumns[column][1]))
    }

    for (let column in dateColumns) {
      TableFilterRegistry.registerFilter(createPastDateFilter(column, dateColumns[column], 'table'), {
        display: () => bold(name),
      })
    }

    for (let column in futureDateColumns) {
      TableFilterRegistry.registerFilter(createFutureDateFilter(column, futureDateColumns[column], 'table'), {
        display: () => bold(name),
      })
    }

    GroundhoggTableFilters.FilterRegistry = TableFilterRegistry

    $(() => {
      document.getElementById('table-filters').replaceWith(Filters({
        id,
        filterRegistry: TableFilterRegistry,
        filters,
        onChange: filters => document.querySelector(
          'form.search-form input[name="include_filters"]').value = base64_json_encode(filters),
      }))
    })
  }

} )(jQuery)