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
    any_of                  : _x('Is any of', 'comparison', 'groundhogg'),
    none_of                 : _x('Is none of', 'comparison', 'groundhogg'),
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
    any_of             : _x('Is any of', 'comparison', 'groundhogg'),
    none_of            : _x('Is none of', 'comparison', 'groundhogg'),
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
    any_of             : _x('Is any of', 'comparison', 'groundhogg'),
    none_of            : _x('Is none of', 'comparison', 'groundhogg'),
  }

  const ComparisonsTitleGenerators = {
    equals                  : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value*/
      _x('%1$s equals %2$s', 'comparison', 'groundhogg'), k, v),
    not_equals              : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s does not equal %2$s', 'comparison', 'groundhogg'), k, v),
    contains                : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s contains %2$s', 'comparison', 'groundhogg'), k, v),
    not_contains            : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s does not contain %2$s', 'comparison', 'groundhogg'), k, v),
    starts_with             : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s starts with %2$s', 'comparison', 'groundhogg'), k, v),
    ends_with               : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s ends with %2$s', 'comparison', 'groundhogg'), k, v),
    does_not_start_with     : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s does not start with %2$s', 'comparison', 'groundhogg'), k, v),
    does_not_end_with       : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s does not end with %2$s', 'comparison', 'groundhogg'), k, v),
    less_than               : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is less than %2$s', 'comparison', 'groundhogg'), k, v),
    less_than_or_equal_to   : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is less than or equal to %2$s', 'comparison', 'groundhogg'), k, v),
    greater_than            : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is greater than %2$s', 'comparison', 'groundhogg'), k, v),
    greater_than_or_equal_to: (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is greater than or equal to %2$s', 'comparison', 'groundhogg'), k, v),
    in                      : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is %2$s', 'comparison', 'groundhogg'), k, v),
    not_in                  : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is not %2$s', 'comparison', 'groundhogg'), k, v),
    any_of                      : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is any of %2$s', 'comparison', 'groundhogg'), k, v),
    none_of                  : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is none of %2$s', 'comparison', 'groundhogg'), k, v),
    empty                   : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is empty', 'comparison', 'groundhogg'), k, v),
    not_empty               : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is not empty', 'comparison', 'groundhogg'), k, v),
    includes                : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s includes %2$s', 'comparison', 'groundhogg'), k, v),
    excludes                : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s excludes %2$s', 'comparison', 'groundhogg'), k, v),
    before                  : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is before %2$s', 'comparison', 'groundhogg'), k, v),
    not_before                  : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is not before %2$s', 'comparison', 'groundhogg'), k, v),
    day_of                  : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s on %2$s', 'comparison', 'groundhogg'), k, v),
    not_day_of                  : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is not on %2$s', 'comparison', 'groundhogg'), k, v),
    after                   : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is after %2$s', 'comparison', 'groundhogg'), k, v),
    not_after                   : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is not after %2$s', 'comparison', 'groundhogg'), k, v),
    between                 : (k, v, v2) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value, 3: another user-defined value */
      _x('%1$s is between %2$s and %3$s', 'comparison', 'groundhogg'), k, v, v2),
    not_between                 : (k, v, v2) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value, 3: another user-defined value */
      _x('%1$s is not between %2$s and %3$s', 'comparison', 'groundhogg'), k, v, v2),
    is                      : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is %2$s', 'comparison', 'groundhogg'), k, v),
    is_not                      : (k, v) => sprintf(
      /* translators: 1: a field key, 2: a user-defined value */
      _x('%1$s is %2$s', 'comparison', 'groundhogg'), k, v),
  }

  const pastDateRanges = {
    'any'         : _x('At any time', 'as in a date-range', 'groundhogg'),
    'today'       : _x('Today', 'as in a date-range', 'groundhogg'),
    'yesterday'   : _x('Yesterday', 'as in a date-range', 'groundhogg'),
    'this_week'   : _x('This week', 'as in a date-range', 'groundhogg'),
    'last_week'   : _x('Last week', 'as in a date-range', 'groundhogg'),
    'this_month'  : _x('This month', 'as in a date-range', 'groundhogg'),
    'last_month'  : _x('Last month', 'as in a date-range', 'groundhogg'),
    'this_quarter': _x('This quarter', 'as in a date-range', 'groundhogg'),
    'last_quarter': _x('Last quarter', 'as in a date-range', 'groundhogg'),
    'this_year'   : _x('This year', 'as in a date-range', 'groundhogg'),
    'last_year'   : _x('Last year', 'as in a date-range', 'groundhogg'),
    '24_hours'    : _x('In the last 24 hours', 'as in a date-range', 'groundhogg'),
    '7_days'      : _x('In the last 7 days', 'as in a date-range', 'groundhogg'),
    '14_days'     : _x('In the last 14 days', 'as in a date-range', 'groundhogg'),
    '30_days'     : _x('In the last 30 days', 'as in a date-range', 'groundhogg'),
    '60_days'     : _x('In the last 60 days', 'as in a date-range', 'groundhogg'),
    '90_days'     : _x('In the last 90 days', 'as in a date-range', 'groundhogg'),
    '365_days'    : _x('In the last 365 days', 'as in a date-range', 'groundhogg'),
    'x_days'      : _x('In the last X days', 'as in a date-range', 'groundhogg'),
    'before'      : _x('Before', 'as in a date-range', 'groundhogg'),
    'after'       : _x('After', 'as in a date-range', 'groundhogg'),
    'between'     : _x('Between', 'as in a date-range', 'groundhogg'),
    'day_of'      : _x('Day of', 'as in a date-range', 'groundhogg'),
  }

  const futureDateRanges = {
    'any'          : _x('At any time', 'as in a date-range', 'groundhogg'),
    'today'        : _x('Today', 'as in a date-range', 'groundhogg'),
    'tomorrow'     : _x('Tomorrow', 'as in a date-range', 'groundhogg'),
    'this_week'    : _x('This week', 'as in a date-range', 'groundhogg'),
    'next_week'    : _x('Next week', 'as in a date-range', 'groundhogg'),
    'this_month'   : _x('This month', 'as in a date-range', 'groundhogg'),
    'next_month'   : _x('Next month', 'as in a date-range', 'groundhogg'),
    'this_quarter' : _x('This quarter', 'as in a date-range', 'groundhogg'),
    'next_quarter' : _x('Next quarter', 'as in a date-range', 'groundhogg'),
    'this_year'    : _x('This year', 'as in a date-range', 'groundhogg'),
    'next_year'    : _x('Next year', 'as in a date-range', 'groundhogg'),
    'next_24_hours': _x('In the next 24 hours', 'as in a date-range', 'groundhogg'),
    'next_7_days'  : _x('In the next 7 days', 'as in a date-range', 'groundhogg'),
    'next_14_days' : _x('In the next 14 days', 'as in a date-range', 'groundhogg'),
    'next_30_days' : _x('In the next 30 days', 'as in a date-range', 'groundhogg'),
    'next_60_days' : _x('In the next 60 days', 'as in a date-range', 'groundhogg'),
    'next_90_days' : _x('In the next 90 days', 'as in a date-range', 'groundhogg'),
    'next_365_days': _x('In the next 365 days', 'as in a date-range', 'groundhogg'),
    'next_x_days'  : _x('In the next X days', 'as in a date-range', 'groundhogg'),
    'before'       : _x('Before', 'as in a date-range', 'groundhogg'),
    'after'        : _x('After', 'as in a date-range', 'groundhogg'),
    'between'      : _x('Between', 'as in a date-range', 'groundhogg'),
    'day_of'       : _x('Day of', 'as in a date-range', 'groundhogg'),
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
    /* translators: %s: number of occurrences */
    equals                  : (v) => sprintf(_n('%s time', '%s times', parseInt(v), 'groundhogg'), v),
    /* translators: %s: number of occurrences */
    less_than               : (v) => sprintf(_n('less than %s time', 'less than %s times', parseInt(v), 'groundhogg'), v),
    /* translators: %s: number of occurrences */
    less_than_or_equal_to   : (v) => sprintf(_n('at most %s time', 'at most %s times', parseInt(v), 'groundhogg'), v),
    /* translators: %s: number of occurrences */
    greater_than            : (v) => sprintf(_n('more than %s time', 'more than %s times', parseInt(v), 'groundhogg'), v),
    /* translators: %s: number of occurrences */
    greater_than_or_equal_to: (v) => sprintf(_n('at least %s time', 'at least %s times', parseInt(v), 'groundhogg'), v),
  }

  const moreComparisonTitleGenerators = {
    all_checked: (prefix, options) => sprintf(
      /* translators: 2: list of checked options, 1: a field key */
      __('%2$s is checked for %1$s', 'groundhogg'), prefix,
      andList(options.map(b => bold(b)))),
    not_checked: (prefix, options) => sprintf(
      /* translators: 2: list of checked options, 1: a field key */
      __('%2$s is not checked for %1$s', 'groundhogg'), prefix,
      andList(options.map(b => bold(b)))),
    all_in     : (prefix, options) => sprintf(
      /* translators: 2: list of checked options, 1: a field key */
      __('%2$s is selected for %1$s', 'groundhogg'), prefix,
      andList(options.map(b => bold(b)))),
    all_not_in : (prefix, options) => sprintf(
      /* translators: 2: list of checked options, 1: a field key */
      __('%2$s is not selected for %1$s', 'groundhogg'), prefix,
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
  const createMixedFilter = (
    type, name, group,
    {
      edit = () => null,
      display = () => bold(name),
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
          options : AllComparisons,
          selected: compare,
          onChange: e => updateFilter({
            compare: e.target.value,
          }, true),
        }),
        [
          'empty',
          'not_empty',
          'any_of',
          'none_of'
        ].includes(compare) ? null : Input({
          id      : 'filter-value',
          value: Array.isArray(value) ? ( value[0] ?? '' ) : value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        }),
        [
          'any_of',
          'none_of'
        ].includes(compare) ? OfPicker({value, updateFilter}) : null,
      ]),
      display: ({
        compare,
        value,
        ...rest
      }) => {

        if ( Array.isArray(value) && ['any_of', 'none_of'].includes(compare)) {

          let values = value.map( v => bold(v) )
          let list = compare === 'any_of' ? orList(values) : andList(values)

          return Fragment([
            display(rest),
            ComparisonsTitleGenerators[compare]('', list )
          ])
        }

        return Fragment([
          display(rest),
          ComparisonsTitleGenerators[compare]('', bold(value))
        ])
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
      display = () => bold(name),
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
          'any_of',
          'none_of'
        ].includes(compare) ? null : Input({
          id      : 'filter-value',
          value: Array.isArray(value) ? ( value[0] ?? '' ) : value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        }),
        [
          'any_of',
          'none_of'
        ].includes(compare) ? OfPicker({value, updateFilter}) : null,
      ]),
      display: ({
        compare,
        value,
        ...rest
      }) => {

        if ( Array.isArray(value) && ['any_of', 'none_of'].includes(compare)) {

          let values = value.map( v => bold(v) )
          let list = compare === 'any_of' ? orList(values) : andList(values)

          return Fragment([
            display(rest),
            ComparisonsTitleGenerators[compare]('', list )
          ])
        }

        return Fragment([
          display(rest),
          ComparisonsTitleGenerators[compare]('', bold(value))
        ])
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
      display = () => bold(name),
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
          }, true),
        }),
        [
          'any_of',
          'none_of'
        ].includes(compare) ? OfPicker({
          value,
          updateFilter,
          isValidSelection: (v) => `${parseInt(v)}` === v,
        }) : Input({
          type    : 'number',
          id      : 'filter-value',
          value: Array.isArray(value) ? ( value[0] ?? '' ) : value,
          onChange: e => updateFilter({
            value: e.target.value,
          }),
        })
      ]),
      display: ({
        compare,
        value,
        ...rest
      }) => {

        if ( Array.isArray(value) && ['any_of', 'none_of'].includes(compare)) {

          let values = value.map( v => bold(formatNumber(v)) )
          let list = compare === 'any_of' ? orList(values) : andList(values)

          return Fragment([
            display(rest),
            ComparisonsTitleGenerators[compare]('', list )
          ])
        }

        return Fragment([
          display(rest),
          ComparisonsTitleGenerators[compare]('', bold(formatNumber(value)))
        ])
      },
      preload,
    },
    {
      value  : '',
      compare: 'equals',
      ...defaults,
    },
  )

  const OfPicker = ({
    value,
    updateFilter,
    isValidSelection = v => Boolean(v),
  }) => ItemPicker({
    id: 'filter-values',
    noneSelected: 'Select values',
    placeholder: 'Select values',
    selected: Array.isArray(value) ? value.map( v => ({id: `${v}`, text: `${v}`})) : ( value ? [{id: `${value}`, text: `${value}`}] : [] ),
    onChange: (values) => updateFilter({
      value: values.map(v => v.id),
    }),
    multiple: true,
    isValidSelection,
    fetchOptions: async search => [{id: search, text: search}],
  })

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
              is    : _x( 'Is', 'comparison', 'groundhogg' ),
              is_not: _x( 'Is not', 'comparison', 'groundhogg' ),
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
          switch (date_range) {
            case 'between':
              return ComparisonsTitleGenerators.not_between(prefix, formatDate(after), formatDate(before))
            case 'after':
              return ComparisonsTitleGenerators.not_after(prefix, formatDate(after))
            case 'day_of':
              return ComparisonsTitleGenerators.not_day_of(prefix, formatDate(after))
            case 'before':
              return ComparisonsTitleGenerators.not_before(prefix, formatDate(before))
            default:
              return ComparisonsTitleGenerators.is_not( prefix, dateRanges[date_range ?? 'any']?.replace('X', days).toLowerCase())
          }
        }

        switch (date_range) {
          case 'between':
            return ComparisonsTitleGenerators.between(prefix, formatDate(after), formatDate(before))
          case 'after':
            return ComparisonsTitleGenerators.after(prefix, formatDate(after))
          case 'day_of':
            return ComparisonsTitleGenerators.day_of(prefix, formatDate(after))
          case 'before':
            return ComparisonsTitleGenerators.before(prefix, formatDate(before))
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
      /* translators: 1: a field key, 2: a user-defined value */
      display: ({ value }) => ComparisonsTitleGenerators.is( bold(name), bold(options[value])),
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
    noneSelected: __( 'Type to search...', 'groundhogg' ),
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
            all_in    : __('Has all selected', 'groundhogg'),
            all_not_in: __('Does not have all selected', 'groundhogg'),
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
            all_checked: _x('Is Checked', 'comparison', 'groundhogg'),
            not_checked: _x('Is Not Checked', 'comparison', 'groundhogg'),
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
          /* translators: %s: a filter type like "optin_status" */
          __('This %s filter is corrupted', 'groundhogg'),
          bold(filterRegistry.filterName(filter)))
      }
      else {
        /* translators: %s: a filter type like "optin_status" */
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
          console.log(err)
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
    }, Ellipses(_x('Loading', 'as in waiting for something to load', 'groundhogg'))))

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

      }).join(` <i>${_x('AND', 'as in a query', 'groundhogg')}</i> `)

    }).join(` <br/><i>${_x('OR', 'as in a query', 'groundhogg')}</i> `))

    try {
      return renderFilters()
    }
    catch (err) {
      // need to preload
    }

    let el = Span({className:'loading-dots'}, [_x('Loading', 'as in waiting to for something to load', 'groundhogg')])

    filterRegistry.preloadFilters(filters).finally(r => {
      morphdom(el, renderFilters())
    })

    return el
  }

  // basically does base64 json encode but also removed IDs since we don't need them for backend processing
  const urlEncodeFilters = filters => base64_json_encode(filters.map(group => group.map(({id, ...filter}) => filter)))

  Groundhogg.filters.Filters = Filters
  Groundhogg.filters.FilterDisplay = FilterDisplay
  Groundhogg.filters.FilterRegistry = FilterRegistry
  Groundhogg.filters.createFilter = createFilter
  Groundhogg.filters.createGroup = createGroup
  Groundhogg.filters.createMixedFilter = createMixedFilter
  Groundhogg.filters.createStringFilter = createStringFilter
  Groundhogg.filters.createNumberFilter = createNumberFilter
  Groundhogg.filters.createTimeFilter = createTimeFilter
  Groundhogg.filters.createPastDateFilter = createPastDateFilter
  Groundhogg.filters.createFutureDateFilter = createFutureDateFilter
  Groundhogg.filters.createDateFilter = createDateFilter
  Groundhogg.filters.createSelectFilter = createSelectFilter
  Groundhogg.filters.urlEncodeFilters = urlEncodeFilters
  Groundhogg.filters.comparisons = {
    ComparisonsTitleGenerators,
    AllComparisons,
    StringComparisons,
    NumericComparisons,
    pastDateRanges,
    futureDateRanges,
    allDateRanges,
    moreComparisonTitleGenerators,
    filterCountComparisons
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
            'form.search-form input[name="include_filters"]').value = urlEncodeFilters(filters),
        }))
      }

    })
  }

} )(jQuery)
