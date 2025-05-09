( ($) => {

  const {
    searchOptionsWidget,
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
    Ellipses,
    Dashicon,
    InputGroup,
    ToolTip,
  } = MakeEl

  const {
    formatNumber,
    formatTime,
    formatDate,
    formatDateTime,
  } = Groundhogg.formatting
  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n
  const { base64_json_encode } = Groundhogg.functions

  const AllComparisons = {
    equals                  : _x('Equals', 'comparison', 'groundhogg'),
    not_equals              : _x('Not equals', 'comparison', 'groundhogg'),
    contains                : _x('Contains', 'comparison', 'groundhogg'),
    not_contains            : _x('Does not contain', 'comparison', 'groundhogg'),
    starts_with             : _x('Starts with', 'comparison', 'groundhogg'),
    ends_with               : _x('Ends with', 'comparison', 'groundhogg'),
    does_not_start_with     : _x('Does not start with', 'comparison', 'groundhogg'),
    does_not_end_with       : _x('Does not end with', 'comparison', 'groundhogg'),
    less_than               : _x('Less than', 'comparison', 'groundhogg'),
    less_than_or_equal_to   : _x('Less than or equal to', 'comparison', 'groundhogg'),
    greater_than            : _x('Greater than', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('Greater than or equal to', 'comparison', 'groundhogg'),
    empty                   : _x('Is empty', 'comparison', 'groundhogg'),
    not_empty               : _x('Is not empty', 'comparison', 'groundhogg'),
    // null: _x('Is null', 'comparison', 'groundhogg'),
    // not_null: _x('Is not null', 'comparison', 'groundhogg'),
  }

  const StringComparisons = {
    equals             : _x('Equals', 'comparison', 'groundhogg'),
    not_equals         : _x('Not equals', 'comparison', 'groundhogg'),
    contains           : _x('Contains', 'comparison', 'groundhogg'),
    not_contains       : _x('Does not contain', 'comparison', 'groundhogg'),
    starts_with        : _x('Starts with', 'comparison', 'groundhogg'),
    ends_with          : _x('Ends with', 'comparison', 'groundhogg'),
    does_not_start_with: _x('Does not start with', 'comparison', 'groundhogg'),
    does_not_end_with  : _x('Does not end with', 'comparison', 'groundhogg'),
    empty              : _x('Is empty', 'comparison', 'groundhogg'),
    not_empty          : _x('Is not empty', 'comparison', 'groundhogg'),
  }

  const NumericComparisons = {
    equals                  : _x('Equals', 'comparison', 'groundhogg'),
    not_equals              : _x('Not equals', 'comparison', 'groundhogg'),
    less_than               : _x('Less than', 'comparison', 'groundhogg'),
    less_than_or_equal_to   : _x('Less than or equal to', 'comparison',
      'groundhogg'),
    greater_than            : _x('Greater than', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('Greater than or equal to', 'comparison',
      'groundhogg'),
  }

  const ComparisonsTitleGenerators = {
    equals                  : (k, v) => sprintf(
      _x('%1$s equals %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    not_equals              : (k, v) => sprintf(
      _x('%1$s does not equal %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    contains                : (k, v) => sprintf(
      _x('%1$s contains %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    not_contains            : (k, v) => sprintf(
      _x('%1$s does not contain %2$s',
        '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    starts_with             : (k, v) => sprintf(
      _x('%1$s starts with %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    ends_with               : (k, v) => sprintf(
      _x('%1$s ends with %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    does_not_start_with     : (k, v) => sprintf(
      _x('%1$s does not start with %2$s',
        '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    does_not_end_with       : (k, v) => sprintf(
      _x('%1$s does not end with %2$s',
        '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    less_than               : (k, v) => sprintf(
      _x('%1$s is less than %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    less_than_or_equal_to   : (k, v) => sprintf(
      _x('%1$s is less than or equal to %2$s',
        '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    greater_than            : (k, v) => sprintf(
      _x('%1$s is greater than %2$s',
        '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    greater_than_or_equal_to: (k, v) => sprintf(
      _x('%1$s is greater than or equal to %2$s',
        '%1 is a key and %2 is user defined value', 'groundhogg'), k, v),
    in                      : (k, v) => sprintf(
      _x('%1$s is %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    not_in                  : (k, v) => sprintf(
      _x('%1$s is not %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    empty                   : (k, v) => sprintf(
      _x('%1$s is empty', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    not_empty               : (k, v) => sprintf(
      _x('%1$s is not empty', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    includes                : (k, v) => sprintf(
      _x('%1$s includes %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    excludes                : (k, v) => sprintf(
      _x('%1$s excludes %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    before                  : (k, v) => sprintf(
      _x('%1$s is before %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    day_of                  : (k, v) => sprintf(
      _x('%1$s on %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    after                   : (k, v) => sprintf(
      _x('%1$s is after %2$s', '%1 is a key and %2 is user defined value',
        'groundhogg'), k, v),
    between                 : (k, v, v2) => sprintf(
      _x('%1$s is between %2$s and %3$s',
        '%1 is a key and %2 and %3 are user defined values', 'groundhogg'), k,
      v, v2),
  }

  const pastDateRanges = {
    'any'       : __('At any time', 'groundhogg'),
    'today'     : __('Today', 'groundhogg'),
    'yesterday' : __('Yesterday', 'groundhogg'),
    'this_week' : __('This week', 'groundhogg'),
    'last_week' : __('Last week', 'groundhogg'),
    'this_month': __('This month', 'groundhogg'),
    'last_month': __('Last month', 'groundhogg'),
    'this_year' : __('This year', 'groundhogg'),
    '24_hours'  : __('In the last 24 hours', 'groundhogg'),
    '7_days'    : __('In the last 7 days', 'groundhogg'),
    '14_days'   : __('In the last 14 days', 'groundhogg'),
    '30_days'   : __('In the last 30 days', 'groundhogg'),
    '60_days'   : __('In the last 60 days', 'groundhogg'),
    '90_days'   : __('In the last 90 days', 'groundhogg'),
    '365_days'  : __('In the last 365 days', 'groundhogg'),
    'x_days'    : __('In the last X days', 'groundhogg'),
    'before'    : __('Before', 'groundhogg'),
    'after'     : __('After', 'groundhogg'),
    'between'   : __('Between', 'groundhogg'),
    'day_of'    : __('Day of', 'groundhogg'),
  }

  const futureDateRanges = {
    'any'          : __('At any time', 'groundhogg'),
    'today'        : __('Today', 'groundhogg'),
    'tomorrow'     : __('Tomorrow', 'groundhogg'),
    'this_week'    : __('This week', 'groundhogg'),
    'this_month'   : __('This month', 'groundhogg'),
    'this_year'    : __('This year', 'groundhogg'),
    'next_24_hours': __('In the next 24 hours', 'groundhogg'),
    'next_7_days'  : __('In the next 7 days', 'groundhogg'),
    'next_14_days' : __('In the next 14 days', 'groundhogg'),
    'next_30_days' : __('In the next 30 days', 'groundhogg'),
    'next_60_days' : __('In the next 60 days', 'groundhogg'),
    'next_90_days' : __('In the next 90 days', 'groundhogg'),
    'next_365_days': __('In the next 365 days', 'groundhogg'),
    'next_x_days'  : __('In the next X days', 'groundhogg'),
    'before'       : __('Before', 'groundhogg'),
    'after'        : __('After', 'groundhogg'),
    'between'      : __('Between', 'groundhogg'),
    'day_of'       : __('Day of', 'groundhogg'),
  }

  const allDateRanges = {
    ...pastDateRanges,
    ...futureDateRanges,
  }

  const activityFilterComparisons = {
    equals                  : _x('Exactly', 'comparison', 'groundhogg'),
    less_than               : _x('Less than', 'comparison', 'groundhogg'),
    greater_than            : _x('More than', 'comparison', 'groundhogg'),
    less_than_or_equal_to   : _x('At most', 'comparison', 'groundhogg'),
    greater_than_or_equal_to: _x('At least', 'comparison', 'groundhogg'),
  }

  const filterCountComparisons = {
    equals                  : (v) => sprintf(_n('%s time', '%s times', parseInt(v), 'groundhogg'), v),
    less_than               : (v) => sprintf(_n('less than %s time', 'less than %s times', parseInt(v), 'groundhogg'), v),
    less_than_or_equal_to   : (v) => sprintf(_n('at most %s time', 'at most %s times', parseInt(v), 'groundhogg'), v),
    greater_than            : (v) => sprintf(_n('more than %s time', 'more than %s times', parseInt(v), 'groundhogg'), v),
    greater_than_or_equal_to: (v) => sprintf(_n('at least %s time', 'at least %s times', parseInt(v), 'groundhogg'), v),
  }

  const moreComparisonTitleGenerators = {
    all_checked: (prefix, options) => sprintf(
      __('%2$s is checked for %1$s', 'groundhogg-better-meta'), prefix,
      andList(options.map(b => bold(b)))),
    not_checked: (prefix, options) => sprintf(
      __('%2$s is not checked for %1$s', 'groundhogg-better-meta'), prefix,
      andList(options.map(b => bold(b)))),
    all_in     : (prefix, options) => sprintf(
      __('%2$s is selected for %1$s', 'groundhogg-better-meta'), prefix,
      andList(options.map(b => bold(b)))),
    all_not_in : (prefix, options) => sprintf(
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
  const createGroup = (id, name) => ( {
    id,
    name,
  } )

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
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createFilter = (
    type, name, group,
    {
      edit = () => null,
      display = () => null,
      preload = () => {},
    },
    defaults = {}) => ( {
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
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createStringFilter = (
    type, name, group,
    {
      edit = () => null,
      display = () => null,
      preload = () => {},
    } = {},
    defaults = {}) => createFilter(
    type,
    name,
    group,
    {
      edit   : ({
        value,
        compare,
        updateFilter,
        ...rest
      }) => Fragment([
        edit({
          ...rest,
          updateFilter,
        }),
        Select({
          id      : 'filter-compare',
          name    : 'filter_compare',
          options : StringComparisons,
          selected: compare,
          onChange: e => updateFilter({
            compare: e.target.value,
          }, true),
        }),
        [
          'empty',
          'not_empty',
        ].includes(compare) ? null : Input({
          id      : 'filter-value',
          value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        }),
      ]),
      display: ({
        compare,
        value,
        ...rest
      }) => {
        return Fragment(
          ComparisonsTitleGenerators[compare](bold(name), bold(value)))
      },
      preload,
    },
    {
      value  : '',
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
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createNumberFilter = (
    type, name, group,
    {
      edit = () => null,
      display = () => null,
      preload = () => {},
    } = {},
    defaults = {}) => createFilter(
    type,
    name,
    group,
    {
      edit   : ({
        value,
        compare,
        updateFilter,
        ...rest
      }) => Fragment([
        edit({
          ...rest,
          updateFilter,
        }),
        Select({
          id      : 'filter-compare',
          name    : 'filter_compare',
          options : NumericComparisons,
          selected: compare,
          onChange: e => updateFilter({
            compare: e.target.value,
          }),
        }),
        Input({
          type    : 'number',
          id      : 'filter-value',
          value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        }),
      ]),
      display: ({
        compare,
        value,
        ...rest
      }) => {
        return Fragment(
          ComparisonsTitleGenerators[compare](bold(name), bold(formatNumber(value))))
      },
      preload,
    },
    {
      value  : '',
      compare: 'equals',
      ...defaults,
    },
  )

  /**
   * Create a filter that allow you to compare a time value
   *
   * @param type
   * @param name
   * @param group
   * @param edit
   * @param display
   * @param preload
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createTimeFilter = (
    type, name, group,
    {
      edit = () => null,
      display = () => null,
      preload = () => {},
    } = {},
    defaults = {}) => createFilter(
    type,
    name,
    group,
    {
      edit   : ({
        value,
        compare,
        updateFilter,
        ...rest
      }) => Fragment([
        edit({
          ...rest,
          updateFilter,
        }),
        Select({
          id      : 'filter-compare',
          name    : 'filter_compare',
          options : NumericComparisons,
          selected: compare,
          onChange: e => updateFilter({
            compare: e.target.value,
          }),
        }),
        Input({
          type    : 'time',
          id      : 'filter-value',
          value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        }),
      ]),
      display: ({
        compare,
        value,
        ...rest
      }) => {
        return Fragment(
          ComparisonsTitleGenerators[compare](bold(name), bold(formatTime(value))))
      },
      preload,
    },
    {
      value  : '',
      compare: 'equals',
      ...defaults,
    },
  )

  const dateFilterFactory = (
    type, name, group,
    {
      edit = () => null,
      display = () => null,
      preload = () => {},
    } = {},
    defaults = {},
    dateRanges = {},
  ) => createFilter(type,
    name, group, {
      edit   : ({
        date_range,
        compare = 'is',
        before,
        after,
        updateFilter,
        days = 0,
        ...rest
      }) => Fragment([
        edit({
          ...rest,
          updateFilter,
        }),
        InputGroup([
          Select({
            id      : 'filter-compare',
            name    : 'compare',
            options : {
              is    : 'Is',
              is_not: 'Is not',
            },
            selected: compare,
            onChange: e => updateFilter({
              compare: e.target.value,
            }),
          }),
          Select({
            id      : 'filter-date-range',
            name    : 'date_range',
            options : dateRanges,
            selected: date_range,
            onChange: e => updateFilter({
              date_range: e.target.value,
            }, true),
          }),
        ]),
        [
          'after',
          'between',
          'day_of',
        ].includes(date_range) ? Input({
          type    : 'date',
          value   : after.split(' ')[0],
          id      : 'filter-after',
          onChange: e => updateFilter({
            after: e.target.value,
          }),
        }) : null,
        date_range === 'before' || date_range === 'between' ? Input({
          type    : 'date',
          value   : before.split(' ')[0],
          id      : 'filter-before',
          onChange: e => updateFilter({
            before: e.target.value,
          }),
        }) : null,
        date_range === 'x_days' || date_range === 'next_x_days' ? Input({
          type    : 'number',
          value   : days,
          name    : 'days',
          id      : 'filter-days',
          onChange: e => updateFilter({
            days: parseInt(e.target.value),
          }),
        }) : null,
      ]),
      display: ({
        compare = 'is',
        date_range,
        after,
        before,
        days = 0,
        ...rest
      }) => {

        let prefix = display(rest)
        if (!prefix || prefix.length === 0) {
          prefix = bold(name)
        }

        if (compare === 'is_not') {
          prefix += ' is not'
        }

        switch (date_range) {
          case 'between':
            return ComparisonsTitleGenerators.between(prefix,
              formatDate(after), formatDate(before))
          case 'after':
          case 'day_of':
            return ComparisonsTitleGenerators[date_range](prefix,
              formatDate(after))
          case 'before':
            return ComparisonsTitleGenerators.before(prefix,
              formatDate(before))
          default:
            return sprintf('%s %s', prefix, dateRanges[date_range ?? 'any']?.replace('X', days).toLowerCase())
        }
      },
      preload,
    }, {
      ...defaults,
      before : '',
      after  : '',
      days   : 0,
      compare: 'is',
    })

  /**
   * Create a filter that compares against previous dates
   *
   * @param type
   * @param name
   * @param group
   * @param callbacks
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createDateFilter = (
    type, name, group, callbacks, defaults = {},
  ) => dateFilterFactory(type, name, group, callbacks, {
    date_range: '24_hours',
    ...defaults,
  }, allDateRanges)

  /**
   * Create a filter that compares against previous dates
   *
   * @param type
   * @param name
   * @param group
   * @param callbacks
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createPastDateFilter = (
    type, name, group, callbacks, defaults = {},
  ) => dateFilterFactory(type, name, group, callbacks, {
    date_range: '24_hours',
    ...defaults,
  }, pastDateRanges)

  /**
   * Create a filter that compares a value to given dates in the future
   *
   * @param type
   * @param name
   * @param group
   * @param callbacks
   * @param defaults
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createFutureDateFilter = (
    type, name, group, callbacks, defaults = {},
  ) => dateFilterFactory(type, name, group, callbacks, {
    date_range: 'next_24_hours',
    ...defaults,
  }, futureDateRanges)

  /**
   * Create a select filter, which is just a comparison filter with fixed
   * values
   *
   * @param type
   * @param name
   * @param group
   * @param options
   * @returns {{defaults: {}, edit: (function(): null), display: (function():
   *   null), name, type, preload: preload, group}}
   */
  const createSelectFilter = (type, name, group, options) => createFilter(
    type,
    name,
    group,
    {
      display: ({ value }) => sprintf('%s is %s', bold(name),
        bold(options[value])),
      edit   : ({
        value,
        updateFilter,
      }) => Select({
        id      : 'filter-select',
        options,
        selected: value,
        onChange: e => updateFilter({ value: e.target.value }),
      }),
    },
    {
      value: Object.keys(options)[0],
    },
  )

  /**
   * Simple options picker
   *
   * @param field
   * @param options
   * @param updateFilter
   * @returns {*}
   * @constructor
   */
  const OptionsPicker = ({
    field,
    options,
    updateFilter,
  }) => ItemPicker({
    id          : 'filter-options',
    noneSelected: 'Type to search...',
    selected    : options.map(opt => ( {
      id  : opt,
      text: opt,
    } )),
    fetchOptions: async search => field.options.filter(opt => opt.match(new RegExp(search, 'i'))).
      map(opt => ( {
        id: opt,
        text: opt,
      } )),
    onChange    : items => updateFilter({
      options: items.map(item => item.id),
    }),
  })

  /**
   * When given a field property the factory will auto generate filters
   *
   * @type {{date: (function({id: *, label: *, group: *}): {defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload,
   *   group}), number: (function({id: *, label: *, group: *}): {defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload:
   *   preload, group}), datetime: (function({id: *, label: *, group: *}): {defaults: {}, edit: (function(): null), display: (function(): null), name, type,
   *   preload: preload, group}), checkboxes: (function({id: *, label: *, group: *, [p: string]: *}): {defaults: {}, edit: (function(): null), display:
   *   (function(): null), name, type, preload: preload, group}), textarea: (function({id: *, label: *, group: *}): {defaults: {}, edit: (function(): null),
   *   display: (function(): null), name, type, preload: preload, group}), tel: (function({id: *, label: *, group: *}): {defaults: {}, edit: (function():
   *   null), display: (function(): null), name, type, preload: preload, group}), text: (function({id: *, label: *, group: *}): {defaults: {}, edit:
   *   (function(): null), display: (function(): null), name, type, preload: preload, group}), time: (function({id: *, label: *, group: *}): {defaults: {},
   *   edit: (function(): null), display: (function(): null), name, type, preload: preload, group}), url: (function({id: *, label: *, group: *}): {defaults:
   *   {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}), dropdown: (function({id: *, label: *, group: *, [p:
   *   string]: *}): {defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}), custom_email: (function({id:
   *   *, label: *, group: *}): {defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload: preload, group}), radio:
   *   (function({id: *, label: *, group: *, [p: string]: *}): {defaults: {}, edit: (function(): null), display: (function(): null), name, type, preload:
   *   preload, group})}}
   */
  const filterFactory = {

    text        : ({
      id,
      label,
      group,
    }) => createStringFilter(id, label, group),
    url         : ({
      id,
      label,
      group,
    }) => createStringFilter(id, label, group),
    custom_email: ({
      id,
      label,
      group,
    }) => createStringFilter(id, label, group),
    tel         : ({
      id,
      label,
      group,
    }) => createStringFilter(id, label, group),
    textarea    : ({
      id,
      label,
      group,
    }) => createStringFilter(id, label, group),
    number      : ({
      id,
      label,
      group,
    }) => createNumberFilter(id, label, group),
    date        : ({
      id,
      label,
      group,
    }) => createDateFilter(id, label, group),
    datetime    : ({
      id,
      label,
      group,
    }) => createDateFilter(id, label, group),
    time        : ({
      id,
      label,
      group,
    }) => createTimeFilter(id, label, group),

    radio: ({
      id,
      label,
      group,
      ...field
    }) => createFilter(id, label, group, {
      edit   : ({
        options,
        compare,
        updateFilter,
      }) => Fragment([
        Select({
          id      : 'filter-compare',
          selected: compare,
          options : {
            in    : _x('Is one of', 'comparison, groundhogg'),
            not_in: _x('Is not one of', 'comparison', 'groundhogg'),
          },
          onChange: e => updateFilter({
            compare: e.target.value,
          }),
        }),
        OptionsPicker({
          field,
          options,
          updateFilter,
        }),
      ]),
      display: ({
        options,
        compare,
      }) => ComparisonsTitleGenerators[compare](bold(label),
        orList(options.map(o => bold(o)))),
    }, {
      compare: 'in',
      options: [],
    }),

    dropdown: ({
      id,
      label,
      group,
      ...field
    }) => createFilter(id, label, group, {
      edit   : ({
        options,
        compare,
        updateFilter,
      }) => Fragment([
        Select({
          id      : 'filter-compare',
          selected: compare,
          options : field.multiple ? {
            all_in    : __('Has all selected'),
            all_not_in: __('Does not have all selected'),
          } : {
            in    : _x('Is one of', 'comparison, groundhogg'),
            not_in: _x('Is not one of', 'comparison', 'groundhogg'),
          },
          onChange: e => updateFilter({
            compare: e.target.value,
          }),
        }),
        OptionsPicker({
          field,
          options,
          updateFilter,
        }),
      ]),
      display: ({
        options,
        compare,
      }) => {
        if (ComparisonsTitleGenerators[compare]) {
          return ComparisonsTitleGenerators[compare](bold(label), orList(options.map(o => bold(o))))
        }
        return moreComparisonTitleGenerators[compare](bold(label), options)
      },
    }, {
      compare: field.multiple ? 'all_in' : 'in',
      options: [],
    }),

    checkboxes: ({
      id,
      label,
      group,
      ...field
    }) => createFilter(id, label, group, {
      edit   : ({
        options,
        compare,
        updateFilter,
      }) => Fragment([
        Select({
          id      : 'filter-compare',
          selected: compare,
          options : {
            all_checked: __('Is Checked', 'groundhogg-better-meta'),
            not_checked: __('Is Not Checked', 'groundhogg-better-meta'),
          },
          onChange: e => updateFilter({
            compare: e.target.value,
          }),
        }),
        OptionsPicker({
          field,
          options,
          updateFilter,
        }),
      ]),
      display: ({
        options = [],
        compare = '',
      }) => moreComparisonTitleGenerators[compare](bold(label), options),
    }, {
      compare: 'all_checked',
      options: [],
    }),
  }

  /**
   * Create a filters registry
   *
   * @param groups
   * @param filters
   * @returns {{getFilter({type: *}): *, edit(*, *): *, preloadFilters(*):
   *   Promise<Awaited<unknown>[]>, displayName(*):
   *   *, registerGroup(), filterName(*): *, groups: {}, filters: {},
   *   registerFilter(), preloadFilter(*): *}}
   * @constructor
   */
  const FilterRegistry = ({
    groups = [],
    filters = [],
  } = {}) => ( {

    groups: groups.reduce((carr, curr) => {
      carr[curr.id] = curr.name
      return carr
    }, {}),

    filters: filters.reduce((filters, filter) => {
      filters[filter.type] = filter
      return filters
    }, {}),

    registerGroup (group, name) {
      if (group && name) {
        this.groups[group] = name
      }
      else {
        this.groups[group.id] = group.name
      }
    },

    registerFilter (filter) {
      this.filters[filter.type] = filter
    },

    displayName (filter) {

      let name = this.getFilter(filter).display(filter)

      if (!name) {
        name = this.getFilter(filter).name
      }

      return name
    },

    displayFilters (filters) {
      return filters.map(group => group.map(filter => {
        return Div({}, this.display(filter)).innerHTML
      }).join(' and ')).join(' or ')
    },

    filterName (filter) {
      return this.getFilter(filter).name
    },

    display (filter) {
      return this.getFilter(filter).display(filter)
    },

    edit (filter, updateFilter) {
      return this.getFilter(filter).
        edit({
          ...filter,
          updateFilter,
        })
    },

    getFilter ({ type }) {
      return this.filters[type] ?? {}
    },

    hasFilter ({ type }) {
      return type in this.filters
    },

    preloadFilter (filter) {
      return this.getFilter(filter).preload(filter)
    },

    preloadFilters (filters) {

      const promises = []

      filters.forEach(filterGroup => filterGroup.forEach(filter => {
        try {
          const promise = this.preloadFilter(filter)
          if (promise) {
            promises.push(promise)
          }
        }
        catch (err) {}
      }))

      return Promise.all(promises)

    },

    registerFromProperties (properties) {

      const {
        tabs,
        fields,
        groups,
      } = properties

      Object.values(tabs).forEach(t => {

        Object.values(groups).filter(f => f.tab === t.id).forEach(s => {

          let groupId = `${ t.id }-${ s.id }`

          this.registerGroup(groupId, `${ t.name }: ${ s.name }`)

          Object.values(fields).filter(f => f.group === s.id).forEach(f => {

            if (f.type in filterFactory) {
              this.registerFilter(filterFactory[f.type]({
                ...f,
                group: groupId,
              }))
            }

          })

        })

      })

    },

    registerFromConfig ({
      stringColumns = {},
      numberColumns = {},
      dateColumns = {},
      futureDateColumns = {},
      pastDateColumns = {},
      selectColumns = {},
      name = '',
      group = 'table',
    }) {

      this.registerGroup(group, name)

      for (let column in stringColumns) {
        this.registerFilter(
          createStringFilter(column, stringColumns[column], group))
      }

      for (let column in numberColumns) {
        this.registerFilter(
          createNumberFilter(column, numberColumns[column], group))
      }

      for (let column in selectColumns) {
        this.registerFilter(
          createSelectFilter(column, selectColumns[column][0], group, selectColumns[column][1]))
      }

      for (let column in dateColumns) {
        this.registerFilter(
          createDateFilter(column, dateColumns[column], group), {
            display: () => bold(name),
          })
      }

      for (let column in futureDateColumns) {
        this.registerFilter(
          createFutureDateFilter(column, futureDateColumns[column], group), {
            display: () => bold(name),
          })
      }

      for (let column in pastDateColumns) {
        this.registerFilter(
          createPastDateFilter(column, pastDateColumns[column], group), {
            display: () => bold(name),
          })
      }

      return this
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

    // make sure array...
    if ( ! filters ){
      filters = []
    }

    // parse the filters to make sure they have ids...
    filters.forEach(filterGroup => filterGroup.forEach(filter => {
      if (!filter.id) {
        filter.id = uuid()
      }
    }))

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

    const State = Groundhogg.createState({
      preloaded   : false,
      activeFilter: null,
    })

    /**
     * Updates the current state of the filters
     *
     * @param newState
     * @param doMorph
     */
    const setState = (newState, doMorph = true) => {
      State.set(newState)
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
    const FilterBroken = (filter, group, index, err) => {

      let message

      if (filterRegistry.hasFilter(filter)) {
        message = err instanceof Error ? err.message : sprintf(
          __('This %s filter is corrupted', 'groundhogg'),
          bold(filterRegistry.filterName(filter)))
      }
      else {
        message = sprintf(__('This %s filter is not available.', 'groundhogg'),
          bold(filter.type))
      }

      return Div({
        id       : `filter-${ filter.id }`,
        className: 'filter filter-view filter-broken',
        tabindex : 0,
        onClick  : e => {
          if (clickedIn(e, '.delete-filter')) {
            return
          }

          editFilter(filter.id)
        },
      }, [
        Span({
          className: 'filter-name text',
        }, message),
        Button({
          type     : 'button',
          id       : `delete-${ group }-${ index }`,
          className: 'delete-filter',
          onClick  : e => {
            e.preventDefault()
            deleteFilter(group, index)
          },
        }, Dashicon('no-alt')),
      ])
    }

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
      id       : `filter-${ filter.id }`,
      onClick  : e => {
        if (clickedIn(e, '.delete-filter')) {
          return
        }

        editFilter(filter.id)
      },
      className: 'filter filter-view',
      tabindex : 0,
    }, [
      Span({
        className: 'filter-name text',
        // onClick: e => {
        //   editFilter(filter.id)
        // }
      }, filterRegistry.displayName(filter)),
      Button({
        type     : 'button',
        id       : `delete-${ group }-${ index }`,
        className: 'delete-filter',
        onClick  : e => deleteFilter(group, index),
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
          morphdom(document.getElementById(`filter-${ id }-settings`),
            FilterSettings())
        }
        catch (e) {}
      }

      /**
       * Updates temporary filter state
       *
       * @param newSettings
       * @param doMorph
       */
      const updateTempFilterSettings = (newSettings, doMorph = false) => {
        tempFilterSettings = {
          ...tempFilterSettings,
          ...newSettings,
        }

        if (doMorph) {
          morphFilter()
        }
      }

      /**
       * Renders the filter settings
       *
       * @returns HTMLElement
       * @constructor
       */
      const FilterSettings = () => Div({
        id       : `filter-${ id }-settings`,
        className: 'settings',
      }, filterRegistry.edit(tempFilterSettings, updateTempFilterSettings))

      return Div({
        id       : `edit-filter-${ filter.id }`,
        className: `filter filter-edit-wrap filter-${ filter.type }`,
        tabindex : 0,
      }, Div({
        className: 'filter-edit',
      }, [

        Div({
          className: 'header',
        }, [
          bold(filterRegistry.filterName(filter)),
          Button({
            type     : 'button',
            className: 'close-edit',
            onClick  : e => editFilter(null),
          }, Dashicon('no-alt')),
        ]),

        FilterSettings(),

        Div({
          className: 'actions',
        }, [

          Button({
            type     : 'button',
            id       : `delete-${ group }-${ index }`,
            className: 'delete delete-filter',
            onClick  : e => deleteFilter(group, index),
          }, Dashicon('trash')),

          Button({
            type     : 'button',
            id       : `commit-${ group }-${ index }`,
            className: 'commit commit-filter',
            onClick  : e => updateFilter(tempFilterSettings, group, index),
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
      id       : `group-${id}-${ group }`,
      className: 'group',
    }, [
      ...filters.map((filter, index) => {
        try {
          if (State.activeFilter === filter.id) {
            return EditFilter(filter, group, index)
          }
          return Filter(filter, group, index)
        }
        catch (err) {
          return FilterBroken(filter, group, index, err)
        }

      }),
      Button({
        type     : 'button',
        id       : `add-filter-to-${id}-${ group }`,
        className: 'add-filter gh-has-tooltip',
        onClick  : e => {

          let options = Object.values(filterRegistry.filters)
          let groups = filterRegistry.groups

          searchOptionsWidget({
            // selector: '.add-filter-wrap',
            position    : 'fixed',
            target      : e.currentTarget,
            options,
            groups,
            onSelect    : (option) => {

              let newFilter = {
                type: option.type,
                ...option.defaults,
              }

              console.log(newFilter)

              addFilter(newFilter, group)
            },
            filterOption: (option, search) => {
              return option.name.match(regexp(search))
            },
            renderOption: (option) => option.name,
            noOptions   : __('No matching filters...', 'groundhogg'),
          }).mount()

        },
      }, [
        Dashicon(group === 0 && ! filters.length ? 'filter' : 'plus-alt2'),
        ToolTip(__('Add a filter', 'groundhogg'), 'right'),
      ]),
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
      id       : `after-${id}-${ after }`,
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
      id       : `${ id }-loading`,
      className: 'filters-loading',
    }, Ellipses(__('Loading'))))

    /**
     * The wrapper for all the filters
     *
     * @returns HTMLElement
     * @constructor
     */
    const FiltersEditor = () => {

      if (!State.get('preloaded')) {

        filterRegistry.preloadFilters(filters).
          finally(() => setState({ preloaded: true }))

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

  /**
   * Display filters in text
   *
   * @param filters
   * @param filterRegistry
   * @returns {*}
   * @constructor
   */
  const FilterDisplay = ({
    filters,
    filterRegistry,
  }) => {

    const renderFilters = () => Span({}, filters.map(row => {

      return row.map(filter => {

        let result = filterRegistry.displayName(filter)

        return Span({}, result).innerHTML

      }).join(' <i>AND</i> ')

    }).join(' <br/><i>OR</i> '))

    try {
      return renderFilters()
    }
    catch (err) {
      // need to preload
    }

    let el = Span({}, ['Loading...'])

    filterRegistry.preloadFilters(filters).finally(r => {
      morphdom(el, renderFilters())
    })

    return el
  }

  Groundhogg.filters.Filters = Filters
  Groundhogg.filters.FilterDisplay = FilterDisplay
  Groundhogg.filters.FilterRegistry = FilterRegistry
  Groundhogg.filters.createFilter = createFilter
  Groundhogg.filters.createGroup = createGroup
  Groundhogg.filters.createStringFilter = createStringFilter
  Groundhogg.filters.createNumberFilter = createNumberFilter
  Groundhogg.filters.createTimeFilter = createTimeFilter
  Groundhogg.filters.createPastDateFilter = createPastDateFilter
  Groundhogg.filters.createFutureDateFilter = createFutureDateFilter
  Groundhogg.filters.createDateFilter = createDateFilter
  Groundhogg.filters.createSelectFilter = createSelectFilter
  Groundhogg.filters.comparisons = {
    ComparisonsTitleGenerators,
    AllComparisons,
    StringComparisons,
    NumericComparisons,
    pastDateRanges,
    futureDateRanges,
    allDateRanges,
    moreComparisonTitleGenerators,
  }

  if (window.GroundhoggTableFilters) {

    const {
      id = '',
      filters = [],
      ...TableFilterConfig
    } = GroundhoggTableFilters

    const TableFilterRegistry = FilterRegistry({})

    TableFilterRegistry.registerFromConfig(TableFilterConfig)

    GroundhoggTableFilters.FilterRegistry = TableFilterRegistry

    $(() => {

      let tableFiltersEl = document.getElementById('table-filters')

      if (tableFiltersEl) {
        tableFiltersEl.replaceWith(Filters({
          id,
          filterRegistry: TableFilterRegistry,
          filters,
          onChange      : filters => document.querySelector(
            'form.search-form input[name="include_filters"]').value = base64_json_encode(filters),
        }))
      }

    })
  }

} )(jQuery)
