/**
 * Titles for steps, the same as their PHP generate_step_title(), so the flow editor can show them while a change is
 * being saved. The server's title replaces them once it's saved.
 *
 * Titles that need names that aren't loaded yet, like a tag's, are undefined, and the editor keeps the last title.
 *
 * No DOM access here, so the tests can check them against the server's, see tests/js/step-titles.test.js
 */
( function (root, factory) {

  const create = factory()

  if (typeof module === 'object' && module.exports) {
    module.exports = create
    return
  }

  root.Groundhogg = root.Groundhogg || {}
  root.Groundhogg.StepTitles = create(root.wp?.i18n ?? {})

} )(typeof self !== 'undefined' ? self : this, function () {

  /**
   * @param __ function translates text
   * @param _x function translates text with context
   * @param sprintf function
   * @param names Object lookups for names by ID: { tag, email, funnel }, each returns undefined when not loaded
   */
  return ({
    __ = text => text,
    _x = text => text,
    sprintf = (format, ...args) => {
      let i = 0
      return format.replace(/%(\d+\$)?s/g, (match, position) => args[position ? parseInt(position) - 1 : i++])
    },
  } = {}) => {

    const bold = text => `<b>${ text }</b>`

    // like esc_html()
    const escHTML = text => String(text).
      replace(/&/g, '&amp;').
      replace(/</g, '&lt;').
      replace(/>/g, '&gt;').
      replace(/"/g, '&quot;').
      replace(/'/g, '&#039;')

    // like andList() and orList() in PHP
    const joinList = (items, format) => {

      if (!items.length) {
        return ''
      }

      if (items.length === 1) {
        return items[0]
      }

      return sprintf(format, items.slice(0, -1).join(', '), items[items.length - 1])
    }

    const andList = items => joinList(items, _x('%1$s and %2$s', 'and preceding the last item in a list', 'groundhogg'))

    const orList = items => joinList(items, _x('%1$s or %2$s', 'or preceding the last item in a list', 'groundhogg'))

    const ids = value => Array.isArray(value) ? value.map(id => parseInt(id)).filter(Boolean) : []

    /**
     * The bold names of the tags in a setting, or undefined if any aren't loaded
     */
    const tagNames = (tags, names) => {

      const list = ids(tags).map(id => names.tag?.(id))

      return list.some(name => name === undefined) ? undefined : list.map(bold)
    }

    const tagTitle = ({
      none,
      many,
      list,
    }) => ({ tags }, names = {}) => {

      const bolded = tagNames(tags, names)

      if (bolded === undefined) {
        return undefined
      }

      if (!bolded.length) {
        return none
      }

      if (bolded.length >= 4) {
        return sprintf(many, bold(bolded.length))
      }

      return sprintf(list, andList(bolded))
    }

    const tagTriggerTitle = ({
      none,
      one,
      anyMany,
      allMany,
      all,
    }) => ({
      tags,
      condition,
    }, names = {}) => {

      const bolded = tagNames(tags, names)

      if (bolded === undefined) {
        return undefined
      }

      if (!bolded.length) {
        return none
      }

      if (bolded.length === 1) {
        return sprintf(one, orList(bolded))
      }

      if (bolded.length >= 4) {
        return condition === 'all' ? sprintf(allMany, bold(bolded.length)) : sprintf(anyMany, bold(bolded.length))
      }

      return condition === 'all' ? sprintf(all, andList(bolded)) : sprintf(one, orList(bolded))
    }

    // like int_to_letters(), 0 is A, 26 is AA
    const intToLetters = num => {
      let letters = ''
      while (num >= 0) {
        letters = String.fromCharCode(65 + ( num % 26 )) + letters
        num = Math.floor(num / 26) - 1
      }
      return letters
    }

    const branchMap = branches => branches && typeof branches === 'object' && !Array.isArray(branches) ? branches : {}

    // like isset_not_empty()
    const filled = value => value !== undefined && value !== null && value !== '' && value !== 0 && value !== '0' && value !== false && !( Array.isArray(value) && !value.length )

    /**
     * The branches of the premium branching logic types, the same as their get_branches(), get_branch_name(), and
     * get_branch_classes() in includes/steps/premium/logic, which Pro's step types extend
     *
     * @return { key, name, classes }[]
     */
    const branches = {

      split_path: ({ branches }) => [
        ...Object.keys(branchMap(branches)).map((key, i) => ( {
          key,
          name   : filled(branchMap(branches)[key]?.name) ? branchMap(branches)[key].name : intToLetters(i),
          classes: '',
        } )),
        {
          key    : 'else',
          name   : 'Else',
          classes: '',
        },
      ],

      weighted_distribution: ({ branches }) => {

        const map = branchMap(branches)
        const total = Object.values(map).reduce((sum, branch) => sum + ( parseFloat(branch?.weight) || 0 ), 0)

        return Object.keys(map).map((key, i) => {

          const weight = filled(map[key]?.weight) ? map[key].weight : ''
          const name = `${ intToLetters(i) } (${ weight })`

          return {
            key,
            name   : total === 100 ? `${ name }%` : name,
            classes: '',
          }
        })
      },

      split_test: ({
        winner = '',
        weight = 0,
      }, id) => ['a', 'b'].map(key => {

        if (filled(winner)) {
          const won = `${ id }-${ key }` === winner
          return {
            key,
            name   : won ? __('Winner (100%)', 'groundhogg') : __('Loser (0%)', 'groundhogg'),
            classes: won ? 'green' : 'red',
          }
        }

        return {
          key,
          name   : `${ key.toUpperCase() } (${ key === 'a' ? weight : 100 - weight }%)`,
          classes: '',
        }
      }),
    }

    return {

      andList,
      orList,
      intToLetters,
      branches,

      apply_tag: tagTitle({
        none: __('Apply tags', 'groundhogg'),
        many: __('Apply %s tags', 'groundhogg'),
        list: __('Apply %s', 'groundhogg'),
      }),

      remove_tag: tagTitle({
        none: __('Remove tags', 'groundhogg'),
        many: __('Remove %s tags', 'groundhogg'),
        list: __('Remove %s', 'groundhogg'),
      }),

      tag_applied: tagTriggerTitle({
        none   : __('A tag is applied', 'groundhogg'),
        one    : __('%s is applied', 'groundhogg'),
        anyMany: __('Any of %s tags are applied', 'groundhogg'),
        allMany: __('%s tags are applied', 'groundhogg'),
        all    : __('%s are applied', 'groundhogg'),
      }),

      tag_removed: tagTriggerTitle({
        none   : __('A tag is removed', 'groundhogg'),
        one    : __('%s is removed', 'groundhogg'),
        anyMany: __('Any of %s tags are removed', 'groundhogg'),
        allMany: __('%s tags are removed', 'groundhogg'),
        all    : __('%s are removed', 'groundhogg'),
      }),

      if_else: ({
        include_display = '',
        exclude_display = '',
      }) => [include_display, exclude_display].filter(Boolean).join(' AND NOT '),

      send_email: ({ email_id }, names = {}) => {

        if (!parseInt(email_id)) {
          return 'Send an email'
        }

        const title = names.email?.(parseInt(email_id))

        // a deleted email is 'Send an email' too, the server says so
        return title === undefined ? undefined : sprintf(__('Send %s', 'groundhogg'), bold(escHTML(title)))
      },

      create_task: ({ summary }) => summary ? sprintf('Create task %s', bold(summary)) : 'Create a new task',

      admin_notification: ({ send_to = [] }) => {

        // like wp_parse_list()
        const list = Array.isArray(send_to) ? send_to : String(send_to).split(/[\s,]+/).filter(Boolean)

        return 'Notify ' + andList(list.map(email => {
          switch (email) {
            case '{email}':
              return bold('the contact')
            case '{owner_email}':
              return bold('the contact owner')
            default:
              return `<code>${ email }</code>`
          }
        }))
      },

      web_form: ({ form_name = '' }) => sprintf(__('Submits %s', 'groundhogg'), bold(form_name)),

      email_confirmed: () => 'Opt-in status is <b>Confirmed</b>',

      task_completed: ({
        tasks = [],
        condition = 'any',
      }, names = {}) => {

        const ids = Array.isArray(tasks) ? tasks : []

        if (!ids.length) {
          return 'A task is completed'
        }

        const summaries = ids.map(id => names.task?.(parseInt(id)))

        if (summaries.some(summary => summary === undefined)) {
          return undefined
        }

        const bolded = summaries.map(summary => bold(summary || 'New Task'))

        return bolded.length > 1 && condition === 'all'
          ? sprintf('When %s are completed', andList(bolded))
          : sprintf('When %s is completed', orList(bolded))
      },

      add_to_flow: ({ funnel_id }, names = {}) => {

        if (!parseInt(funnel_id)) {
          return __('Add to flow', 'groundhogg')
        }

        const title = names.funnel?.(parseInt(funnel_id))

        return title === undefined ? undefined : sprintf(__('Add to %s', 'groundhogg'), bold(escHTML(title)))
      },
    }
  }
})
