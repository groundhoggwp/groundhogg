(function ($) {

  const {
    tags: TagsStore,
    emails: EmailsStore,
  } = Groundhogg.stores

  const {
    get: apiGet,
    post: apiPost,
    delete: apiDelete,
    patch: apiPatch,
    routes
  } = Groundhogg.api

  const { v4: apiRoutes } = routes

  const {
    tinymceElement,
    mappableFields,
    orList,
    andList,
    specialChars,
    select,
    input,
    textarea,
    toggle,
    textAreaWithReplacementsAndEmojis,
    setFrameContent,
    inputWithReplacements,
    inputWithReplacementsAndEmojis,
    ordinal_suffix_of,
    copyObject
  } = Groundhogg.element

  const { sprintf, __, _x, _n } = wp.i18n

  const { formBuilder, rawStepTypes } = Groundhogg

  const { linkPicker, emailPicker, tagPicker, apiPicker } = Groundhogg.pickers

  $.fn.serializeFormJSON = function () {
    var o = {}
    var a = this.serializeArray()
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]]
        }
        o[this.name].push(this.value || '')
      } else {
        o[this.name] = this.value || ''
      }
    })
    return o
  }

  const StepPacks = {
    packs: {},
    add (id, name = '', svg = '') {
      this.packs[id] = {
        id,
        name,
        svg,
      }
    },
    get (id) {
      return this.packs[id]
    },
  }

  StepPacks.add('core', __('Groundhogg', 'groundhogg'))

  /**
   * Handler for the tag picker step when a condition is also present.
   *
   * @param step
   * @param updateStepMeta
   */
  const tagWithConditionOnMount = (step, updateStepMeta) => {
    tagOnMount(step, updateStepMeta)

    $('#condition').change(function (e) {
      updateStepMeta({
        condition: $(this).val(),
      })
    })
  }

  /**
   * Handler for the tag picker step
   *
   * @param step
   * @param updateStepMeta
   * @returns {*|define.amd.jQuery}
   */
  const tagOnMount = (step, updateStepMeta) => {
    return tagPicker('#tags', true, (items) => {
      TagsStore.itemsFetched(items)
    }, {
      width: '100%',
      placeholder: __('Select one or more tags...', 'groundhogg')
    }).on('change', function (e) {
      const tags = $(this).val()
      const newTags = tags.filter((tag) => !TagsStore.hasItem(parseInt(tag)))

      if (newTags.length > 0) {
        TagsStore.validate(tags).then((tags) => {
          updateStepMeta({
            tags: tags.map((tag) => tag.ID),
          })
        })
      } else {
        updateStepMeta({
          tags: tags.map((tag) => parseInt(tag)),
        })
      }
    })
  }

  const fieldMappingTable = ({ fields = {}, fieldMap = {} }) => {
    const mappable = fields.map(({ id, label }, i) => {
      return `<tr>
				<td><code>${specialChars(id)}</code></td>
				<td><code>${specialChars(label)}</code></td>
				<td>${mappableFields(
        {
          dataKey: id,
          className: 'mappable-field',
        },
        fieldMap && fieldMap.hasOwnProperty(id)
          ? fieldMap[id]
          : Groundhogg.fields.mappable.hasOwnProperty(id)
          ? Groundhogg.fields.mappable[id]
          : []
      )}
				</td>
			</tr>`
    })

    //language=HTML
    return `
		<table class="mapping-table">
			<thead>
			<tr>
				<th>Field ID</th>
				<th>Field Label</th>
				<th>Map to</th>
			</tr>
			</thead>
			<tbody>${mappable.join('')}</tbody>
		</table>`
  }

  const fieldMappingTableOnMount = (updateStepMeta, getCurrentState) => {
    $('.mappable-field')
      .select2()
      .on('change', function (e) {
        const { meta } = getCurrentState()
        updateStepMeta({
          field_map: {
            ...meta.field_map,
            [$(this).data('key')]: $(this).val(),
          },
        })
      })
  }

  /**
   * Form Picker
   *
   * @param selector
   * @param type
   * @param onReceiveItems
   * @returns {*|define.amd.jQuery}
   */
  const formPicker = (selector, type, onReceiveItems = (i) => {}) => {
    return apiPicker(
      selector,
      `${apiRoutes.funnels}/form-integration`,
      false,
      false,
      (d) => {
        onReceiveItems(d.forms)
        return d.forms.map((form) => ({ id: form.id, text: form.name }))
      },
      (q) => ({ ...q, type })
    )
  }

  const formsCache = {
    _cache: {},

    set (type, forms) {
      this._cache[type] = forms
    },

    getAll (type) {
      return this._cache[type]
    },

    hasType (type) {
      return typeof this._cache[type] !== 'undefined'
    },

    get (type, id) {
      return this.hasType(type)
        ? this._cache[type].find((f) => f.id === id)
        : false
    },

    fetch (type) {
      return apiGet(`${apiRoutes.funnels}/form-integration`, {
        type,
      }).then((d) => this.set(type, d.forms))
    },
  }

  /**
   * Form all form integration steps
   *
   * @param type
   * @param getForm
   * @param rest
   * @returns {*&{onMount(*, *): void, formsCache: {}, edit({meta: *}): string, title()}}
   * @constructor
   */
  const FormIntegration = ({ type, ...rest }) => ({
    type,
    defaults: {
      form_id: 0,
      field_map: {},
    },
    title ({ meta }) {
      const { form_id } = meta
      const form = formsCache.get(type, form_id)

      if (form) {
        return `Submits <b>${form.name}</b>`
      } else {
        return `Submits <b></b>`
      }
    },
    edit ({ meta }) {
      const { form_id, field_map } = meta
      const form = formsCache.get(type, form_id)

      //language=HTML
      return `
		  <div class="panel">
			  <div class="row">
				  <label class="row-label">Select a form...</label>
				  ${select(
					  {
						  id: 'form-id',
						  name: 'form_id',
					  },
					  formsCache.hasType(type)
						  ? formsCache.getAll(type).map((form) => ({
							  value: form.id,
							  text: form.name,
						  }))
						  : [],
					  form_id
				  )}
			  </div>
			  <div class="row">
				  <div id="field-mapping">
					  ${
						  form
							  ? fieldMappingTable({
								  fields: form.fields,
								  fieldMap: field_map,
							  })
							  : ''
					  }
				  </div>
			  </div>
		  </div>`
    },
    onMount (step, updateStepMeta, updateStepData, getCurrentState) {
      formPicker('#form-id', type, (items) => {
        formsCache.set(type, items)
      }).on('change', (e) => {
        updateStepMeta(
          {
            form_id: parseInt(e.target.value),
          },
          true
        )
      })

      fieldMappingTableOnMount(updateStepMeta, getCurrentState)
    },
    preload ({ meta }) {
      if (meta.form_id) {
        return formsCache.fetch(type)
      }
    },
    ...rest,
  })

  const preloadTags = ({ meta, export: exported }) => {
    if (!exported) {
      return TagsStore.fetchItems({
        tag_id: meta.tags
      })
    }

    TagsStore.itemsFetched(exported.tags)
  }

  const delayTimerDefaults = {
    delay_amount: 3,
    delay_type: 'days',
    run_on_type: 'any',
    run_when: 'now',
    run_time: '09:00:00',
    send_in_timezone: false,
    run_time_to: '17:00:00',
    run_on_dow_type: 'any', // Run on days of week type
    run_on_dow: [], // Run on days of week
    run_on_month_type: 'any', // Run on month type
    run_on_months: [], // Run on months
    run_on_dom: [], // Run on days of month
  }

  const delay_timer_i18n = {
    delay_duration_types: {
      minutes: __('Minutes'),
      hours: __('Hours'),
      days: __('Days'),
      weeks: __('Weeks'),
      months: __('Months'),
      years: __('Years'),
      none: __('No delay', 'groundhogg'),
    },
    day_of_week_determiners: {
      any: __('Any'),
      first: __('First'),
      second: __('Second'),
      third: __('Third'),
      fourth: __('Fourth'),
      last: __('Last'),
    },
    days_of_week: {
      monday: __('Monday'),
      tuesday: __('Tuesday'),
      wednesday: __('Wednesday'),
      thursday: __('Thursday'),
      friday: __('Friday'),
      saturday: __('Saturday'),
      sunday: __('Sunday'),
    },
    months: {
      january: __('January'),
      february: __('February'),
      march: __('March'),
      april: __('April'),
      may: __('May'),
      june: __('June'),
      july: __('July'),
      august: __('August'),
      september: __('September'),
      october: __('October'),
      november: __('November'),
      december: __('December'),
    }
  }

  const delayTimerName = ({
    delay_amount,
    delay_type,
    run_on_type,
    run_when,
    run_time,
    send_in_timezone,
    run_time_to,
    run_on_dow_type, // Run on days of week type
    run_on_dow = [], // Run on days of week
    run_on_month_type, // Run on month type
    run_on_months = [], // Run on months
    run_on_dom = [], // Run on days of month
  }) => {
    const preview = []

    const formatTime = (time) => {
      return Intl.DateTimeFormat(Groundhogg.locale, {
        timeStyle: 'short'
      }).format(new Date(`2021-01-01 ${time}`))
    }

    switch (run_when) {
      case 'now':
        preview.unshift(_x('at any time', 'groundhogg'))
        break
      case 'later':
        preview.unshift(sprintf(_x('at %s', 'at a specific time', 'groundhogg'), `<b>${formatTime(run_time)}</b>`))
        break
      case 'between':
        preview.unshift(sprintf(_x('between %1$s and %2$s', 'within a time from', 'groundhogg'), `<b>${formatTime(run_time)}</b>`, `<b>${formatTime(run_time_to)}</b>`))
        break
    }

    let days, months

    switch (run_on_type) {
      default:
      case 'any':
        preview.unshift(_x('run', 'verb meaning to start a process', 'groundhogg'))
        break
      case 'weekday':
        preview.unshift(_x('run on <b>a weekday</b>', 'verb meaning to start a process - on a weekday', 'groundhogg'))
        break
      case 'weekend':
        preview.unshift(_x('run on <b>a weekend</b>', 'verb meaning to start a process - on a weekend', 'groundhogg'))
        break
      case 'day_of_week':
        let dowList = orList(run_on_dow.map((i) => `<b>${delay_timer_i18n.days_of_week[i]}</b>`))
        days = run_on_dow_type === 'any' ? sprintf(_x('any %s', 'any - day of the week', 'groundhogg'), dowList) : sprintf(_x('the %1$s %2$s', 'the - determiner - day of week', 'groundhogg'), delay_timer_i18n.day_of_week_determiners[run_on_dow_type].toLowerCase(), dowList)
        months = run_on_month_type === 'specific' ? orList(run_on_months.map((i) => `<b>${delay_timer_i18n.months[i]}</b>`)) : `<b>${__('any month', 'groundhogg')}</b>`
        preview.unshift(sprintf(_x('run on %1$s of %2$s', 'verb meaning to start on process - on a specific day of a specific month', 'groundhogg'), days, months))
        break
      case 'day_of_month':
        days = run_on_dom.length > 0 ? sprintf(_x('the %s', 'the - ordinal day of month', 'groundhogg'), orList(run_on_dom.map((i) => `<b>${i === 'last' ? __('last day', 'groundhogg') : ordinal_suffix_of(i)}</b>`))) : `<b>${__('any day', 'groundhogg')}</b>`
        months = run_on_month_type === 'specific' ? orList(run_on_months.map((i) => `<b>${delay_timer_i18n.months[i]}</b>`)) : `<b>${__('any month', 'groundhogg')}</b>`
        preview.unshift(sprintf(_x('run on %1$s of %2$s', 'verb meaning to start on process - on a specific day of a specific month', 'groundhogg'), days, months))
        break
    }

    if (delay_type !== 'none') {

      delay_amount = parseInt(delay_amount)

      const delayTypes = {
        minutes: _n('minute', 'minutes', delay_amount),
        hours: _n('hour', 'hours', delay_amount),
        days: _n('day', 'days', delay_amount),
        weeks: _n('week', 'weeks', delay_amount),
        months: _n('month', 'months', delay_amount),
        years: _n('year', 'years', delay_amount),
      }

      preview.unshift(
        sprintf(_x('Wait at least %s and then', 'wait for a duration', 'groundhogg'), `<b>${delay_amount} ${delayTypes[delay_type]}</b>`)
      )
    }

    return preview.join(' ')
  }

  const StepTypes = {

    async preloadSteps (steps) {

      const promises = []

      steps.forEach(s => {

        let p = this.getType(s.data.step_type).preload(s)

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

      return Promise.all(promises)
    },

    register (type, opts) {
      this[type] = {
        type: type,
        ...opts,
      }
    },

    registerFormIntegration (type, opts) {
      this.register(
        type,
        FormIntegration({
          type,
          ...opts,
        })
      )
    },

    getType (type) {
      if (!this.hasOwnProperty(type)) {
        return Object.assign({}, this.default, this.error)
      }

      return Object.assign({}, this.default, this[type])
    },

    /**
     * Merge PHP registered step types with JS registered steps
     */
    setup () {
      for (var prop in rawStepTypes) {
        if (
          Object.hasOwnProperty.call(rawStepTypes, prop) &&
          Object.hasOwnProperty.call(StepTypes, prop)
        ) {
          Object.assign(StepTypes[prop], {
            ...rawStepTypes[prop],
          })
        }
      }
    },

    error: {
      svg: `
		  <svg viewBox="0 0 20 15" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path d="M10.733.534a1 1 0 00-1.656 0L.462 13.25a1 1 0 00.828 1.56h17.23a1 1 0 00.827-1.56L10.733.534z"
			        fill="#E91F4F"/>
			  <path
				  d="M10.48 9.322a.092.092 0 00-.011.036c0 .008-.004.016-.012.024-.016.016-.076.024-.18.024h-.888c-.032 0-.048-.008-.048-.024-.024-.024-.036-.092-.036-.204l-.168-5.496c-.008-.168 0-.268.024-.3.024-.032.092-.048.204-.048h1.068c.112 0 .176.016.192.048.024.032.032.132.024.3l-.168 5.496v.144zm-.587 2.496a.794.794 0 01-.6-.252.8.8 0 01-.24-.589.84.84 0 01.24-.6.794.794 0 01.6-.252c.24 0 .44.085.6.252a.82.82 0 01.252.6.78.78 0 01-.252.588.794.794 0 01-.6.253z"
				  fill="#fff"/>
		  </svg>`,
      name: 'Error',
      type: 'error',
      title ({ data }) {
        return `<b>${data.step_type}</b> settings not found`
      },
      edit ({ ID, data, meta }) {
        //language=HTML
        return `
			<div class="panel">
				<p>The settings for this step could not be found. This may be because you deactivated an extension or
					integration which registered this step type.</p>
				<p>Reactivate the plugin or delete this step to continue.</p>
			</div>`
      },
    },

    /**
     * Step type default fallbacks
     */
    default: {
      pack: 'core',
      // language=html
      promiseController: null,
      title ({ ID, data, meta }) {
        return data.step_title
      },
      edit ({ ID, data, meta }) {
        //language=HTML
        return `
			<div class="panel">
				<form id="settings-form" method="post" action="">
					<div id="dynamic-step-settings">
						<div class="gh-loader"></div>
					</div>
				</form>
			</div>`
      },
      onMount (step, updateStepMeta) {
        const self = this

        self.promiseController = new AbortController()
        const { signal } = self.promiseController

        apiPost(`${apiRoutes.steps}/html`, step, {
          signal,
        })
          .then((r) => {
            $('#dynamic-step-settings').html(r.html)
            $(document).trigger('gh-init-pickers')
            const $form = $('#settings-form')
            $form
              .on('submit', function (e) {
                e.preventDefault()
                return false
              })
              .on('change', function (e) {
                e.preventDefault()
                const meta = $(this).serializeFormJSON()
                // console.log(meta)
                updateStepMeta(meta)
              })
            self.promiseController = null
          })
          .catch(() => {})
      },
      onDemount () {
        if (this.promiseController) {
          this.promiseController.abort()
        }
      },
      validate: function (step, addError, addWarning) {},
      preload (step) {},
      defaults: {},
    },

    apply_note: {
      defaults: {
        note_text: '',
      },

      //language=HTML
      svg: `
		  <svg viewBox="0 0 42 37" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path d="M41.508 31.654h-10m5-5v10" stroke="currentColor" stroke-width="2"/>
			  <path
				  d="M27.508 11.988h1a1 1 0 00-.293-.708l-.707.708zm-7.084-7.084l.708-.707a1 1 0 00-.708-.293v1zm0 7.084h-1v1h1v-1zm7.084 17.416h-1 1zm-18.834 2h17.834v-2H8.674v2zm-2-25.5v23.5h2v-23.5h-2zm21.834 23.5V11.988h-2v17.416h2zm-8.084-25.5H8.674v2h11.75v-2zm7.79 7.376l-7.082-7.083-1.415 1.414 7.084 7.084 1.414-1.415zm-8.79-6.376v7.084h2V4.904h-2zm1 8.084h7.084v-2h-7.084v2zm-8.5 5.666h11.334v-2H11.925v2zm0 5.667h11.334v-2H11.925v2zm14.584 7.083a2 2 0 002-2h-2v2zm-17.834-2h-2a2 2 0 002 2v-2zm0-23.5v-2a2 2 0 00-2 2h2z"
				  fill="currentColor"/>
		  </svg>`,
      title ({ meta }) {
        const { note_text } = meta

        if (note_text) {
          return sprintf(_x('Add %s', 'add - note text', 'groundhogg'), `<i>${note_text.replace(/(<([^>]+)>)/gi, '').substring(0, 30)}...</i>`)
        }

        return __('Add a note', 'groundhogg')
      },
      edit ({ meta }) {
        const { note_text = '' } = meta

        //language=html
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label"
					       for="note_text">${__('Add the following note the the contact...', 'groundhogg')}</label>
					${textarea({
						id: 'note_text',
						className: 'wp-editor-area',
						value: note_text
					})}
				</div>
			</div>`
      },
      onMount (step, updateStepMeta) {
        let saveTimer

        tinymceElement(
          'note_text',
          {
            tinymce: true,
            quicktags: true,
          },
          (content) => {
            // Reset timer.
            clearTimeout(saveTimer)

            // Only save after a second.
            saveTimer = setTimeout(function () {
              updateStepMeta({
                note_text: content,
              })
            }, 300)
          }
        )
      },
      onDemount () {
        wp.editor.remove('note_text')
      },
    },

    admin_notification: {
      //language=HTML
      svg: `
		  <svg viewBox="0 0 31 43" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <mask id="a" fill="#fff">
				  <path d="M16.956 12.576a1.368 1.368 0 11-2.737 0h2.737z"/>
			  </mask>
			  <path
				  d="M16.956 12.576h1.5v-1.5h-1.5v1.5zm-2.737 0v-1.5h-1.5v1.5h1.5zm1.237 0c0-.072.059-.131.131-.131v3a2.868 2.868 0 002.869-2.869h-3zm.131-.131c.073 0 .132.059.132.131h-3a2.868 2.868 0 002.868 2.869v-3zm-1.368 1.631H16.955v-1.5-1.5h-.001-.001-.002-.003-.001-.001-.001-.001-.001-.001-.001-.004-.001-.004-.002-.001-.002-.001-.002-.002-.001-.002-.002-.003-.002-.002-.002-.002-.002-.002-.002-.005-.002-.002-.005-.002-.005-.005-.005-.003-.005-.003-.003-.011-.003-.003-.003-.003-.016-.02-.007-.007H16.76 16.683 14.22v3z"
				  fill="currentColor" mask="url(#a)"/>
			  <path
				  d="M20.376 11.208v.75A.75.75 0 0021 10.792l-.624.416zm-1.369-2.053h-.75a.75.75 0 00.126.417l.624-.417zm-6.842 0l.624.417a.75.75 0 00.126-.417h-.75zm-1.368 2.053l-.624-.416a.75.75 0 00.624 1.166v-.75zm2.118-4.79a2.671 2.671 0 012.671-2.67v-1.5a4.171 4.171 0 00-4.17 4.17h1.5zm2.671-2.67a2.671 2.671 0 012.671 2.67h1.5a4.171 4.171 0 00-4.17-4.17v1.5zm-3.42 8.21h6.841v-1.5h-6.842v1.5zm6.841 0h1.369v-1.5h-1.369v1.5zm.75-2.803V6.42h-1.5v2.736h1.5zM21 10.792L19.63 8.74l-1.248.833 1.369 2.052L21 10.792zM11.415 6.42v2.736h1.5V6.42h-1.5zm.126 2.32l-1.368 2.053 1.248.832 1.368-2.052-1.248-.833zm-.744 3.22h1.368v-1.5h-1.368v1.5zm5.54-8.961V.945h-1.5v2.053h1.5z"
				  fill="currentColor"/>
			  <path d="M29.413 14.097L1.08 25.43l8.5 2.5 19.833-13.833zm0 0l-12.75 26.916-2.5-8.5 15.25-18.416z"
			        stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
		  </svg>`,
      defaults: {
        to: '{owner_email}',
        from: '{owner_email}',
        reply_to: '{email}',
        subject: 'Notification from "{first}"',
        note_text: '',
      },
      title ({ meta }) {
        const { to } = meta

        return `Send notification to ${andList(
          to.split(',').map((address) => `<b>${address.trim()}</b>`)
        )}`
      },
      edit ({ meta }) {

        const {
          note_text = '',
          to = '',
          from = '',
          reply_to = '',
          subject = ''
        } = meta
        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="to">Send this notification to...</label>
					${inputWithReplacements({
						type: 'text',
						id: 'to',
						name: 'to',
						className: 'regular-text',
						value: to,
					})}
					<p class="description">Comma separated list of emails addresses.</p>
				</div>
				<div class="row">
					<label class="row-label" for="from">This notification should be sent from...</label>
					${inputWithReplacements({
						type: 'text',
						id: 'from',
						name: 'from',
						className: 'regular-text',
						value: from,
					})}
					<p class="description">A single email address which you'd like the notification to come from.</p>
				</div>
				<div class="row">
					<label class="row-label" for="reply-to">Replies should go to...</label>
					${inputWithReplacements({
						type: 'text',
						id: 'reply-to',
						name: 'reply_to',
						className: 'regular-text',
						value: reply_to,
					})}
					<p class="description">A single email address which replies to this notification should be sent
						to.</p>
				</div>
				<div class="row">
					<label class="row-label" for="subject">Subject line</label>
					${inputWithReplacementsAndEmojis({
						type: 'text',
						id: 'subject',
						name: 'subject',
						className: 'regular-text',
						value: subject,
					})}
					<p class="description">The subject line of the notification.</p>
				</div>
				<div class="row">
					${textarea({
						id: 'note_text',
						className: 'wp-editor-area',
						value: note_text
					})}
				</div>
			</div>`
      },
      onMount (step, updateStepMeta) {
        $('#subject, #reply-to, #from, #to').on('change', function (e) {
          const $this = $(this)
          updateStepMeta({
            [$this.prop('name')]: $this.val(),
          })
        })

        let saveTimer

        tinymceElement(
          'note_text',
          {
            tinymce: true,
            quicktags: true,
          },
          (content) => {
            // console.log(content)

            // Reset timer.
            clearTimeout(saveTimer)

            // Only save after a second.
            saveTimer = setTimeout(function () {
              updateStepMeta({
                note_text: content,
              })
            }, 300)
          }
        )
      },
      onDemount () {
        wp.editor.remove('note_text')
      },
    },

    /**
     * Account created
     */
    account_created: {
      defaults: {
        role: [],
      },

      //language=HTML
      svg: `
		  <svg viewBox="0 0 32 31" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path
				  d="M1.473 29.667l-.96-.284a1 1 0 00.96 1.284v-1zm25.5 0v1a1 1 0 00.959-1.284l-.96.284zM14.223 14.5a6.083 6.083 0 01-6.084-6.083h-2a8.083 8.083 0 008.084 8.083v-2zM8.139 8.417a6.083 6.083 0 016.084-6.084v-2a8.083 8.083 0 00-8.084 8.084h2zM2.431 29.95c1.59-5.368 6.297-9.201 11.792-9.201v-2c-6.471 0-11.894 4.505-13.71 10.633l1.918.568zm11.792-9.201c5.495 0 10.2 3.833 11.79 9.2l1.918-.567c-1.815-6.128-7.237-10.633-13.708-10.633v2zm-12.75 9.917h25.5v-2h-25.5v2zm12.75-28.334a6.05 6.05 0 013.04.814l1.002-1.732A8.05 8.05 0 0014.223.333v2z"
				  fill="currentColor"/>
			  <path d="M31.223 9.833H17.057M24.14 2.75v14.167" stroke="currentColor" stroke-width="2"/>
		  </svg>
      `,

      // Title
      title ({ ID, data, meta }) {
        const roles = this.context.roles

        if (meta.role && meta.role.length === 1) {
          return sprintf(__('%s is created', 'groundhogg'), `<b>${roles[meta.role[0]]}</b>`)
        } else if (meta.role && meta.role.length > 1) {
          return sprintf(__('%s is created', 'groundhogg'), orList(
            meta.role.map((role) => `<b>${roles[role]}</b>`)
          ))
        } else {
          return __('A <b>new user</b> is created', 'groundhogg')
        }
      },

      // Edit
      edit ({ ID, data, meta }) {
        let roles = this.context.roles

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="roles">${__('Select user roles.', 'groundhogg')}</label>
					${select(
						{
							id: 'roles',
							name: 'role',
							multiple: true,
						},
						roles,
						meta.role
					)}
					<p class="description">
						${__('Runs when a new user is created with any of the given roles. Leave empty to run for every new user.', 'groundhogg')}</p>
				</div>
			</div>`
      },

      // On mount
      onMount (step, updateStepMeta) {
        $('#roles')
          .select2({
            width: '100%',
            placeholder: __('Select one or more user roles...', 'groundhogg')
          })
          .on('change', function (e) {
            let roles = $(this).val()
            updateStepMeta({
              role: roles,
            })
          })
      },
    },

    /**
     * Apply a tag
     */
    apply_tag: {
      defaults: {
        tags: [],
      },

      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 37" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path
				  d="M5.682 20.946L18.848 7.78a1 1 0 01.707-.293h8.503a1 1 0 011 1v8.502a1 1 0 01-.293.707L15.598 30.863a1 1 0 01-1.414 0L5.682 22.36a1 1 0 010-1.414z"
				  stroke="currentColor" stroke-width="2"/>
			  <circle r="1.525" transform="matrix(-1 0 0 1 24.1 12.445)" stroke="currentColor" stroke-width="1.2"/>
			  <path d="M34.246 31.738h-10m5-5v10" stroke="currentColor" stroke-width="2"/>
		  </svg>`,

      title ({ ID, data, meta }) {
        let { tags } = meta

        if (tags) {
          tags = tags.map((id) => parseInt(id))
        }

        if (!tags || tags.length === 0) {
          return __('Apply a tag', 'groundhogg')
        } else if (tags.length < 4 && TagsStore.hasItems(tags)) {
          return sprintf(_x('Apply %s', 'apply tags - list of tag names', 'groundhogg'), andList(
            tags.map((id) => `<b>${TagsStore.get(id).data.tag_name}</b>`)
          ))
        } else {
          return sprintf(_x('Apply %s tags', 'apply tags - number', 'groundhogg'), `<b>${tags.length}</b>`)
        }
      },

      edit ({ ID, data, meta }) {
        let options = TagsStore.getItems().map((tag) => {
          return {
            text: tag.data.tag_name,
            value: tag.ID,
          }
        })

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">${__('Select tags to apply.', 'groundhogg')}</label>
					${select({
							name: 'tags',
							id: 'tags',
							multiple: true,
						},
						options,
						meta.tags ? meta.tags.map((id) => parseInt(id)) : []
					)}
					<p class="description">
						${__('All of the given tags will be applied to the contact.', 'groundhogg')}</p>
				</div>
			</div>`
      },

      onMount (step, updateStepMeta) {
        tagOnMount(step, updateStepMeta)
      },

      preload: preloadTags
    },

    /**
     * Remove a tag
     */
    remove_tag: {
      defaults: {
        tags: [],
      },
      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path
				  d="M5.682 20.946L18.848 7.78a1 1 0 01.707-.293h8.503a1 1 0 011 1v8.502a1 1 0 01-.293.707L15.598 30.863a1 1 0 01-1.414 0L5.682 22.36a1 1 0 010-1.414z"
				  stroke="currentColor" stroke-width="2"/>
			  <circle r="1.525" transform="matrix(-1 0 0 1 24.1 12.445)" stroke="currentColor" stroke-width="1.2"/>
			  <path d="M34.246 31.738h-10" stroke="currentColor" stroke-width="2"/>
		  </svg>`,
      title ({ ID, data, meta }) {
        let { tags } = meta
        tags = tags.map((id) => parseInt(id))

        if (tags.length === 0) {
          return __('Remove tags', 'groundhogg')
        } else if (tags.length < 4 && TagsStore.hasItems(tags)) {
          return sprintf(_x('Remove %s', 'remove - list of tag names', 'groundhogg'), andList(
            tags.map((id) => `<b>${TagsStore.get(id).data.tag_name}</b>`)
          ))
        } else {
          return sprintf(_x('Remove %s tags', 'remove tags - number', 'groundhogg'), `<b>${tags.length}</b>`)
        }
      },

      edit ({ ID, data, meta }) {
        let options = TagsStore.getItems().map((tag) => {
          return {
            text: tag.data.tag_name,
            value: tag.ID,
          }
        })

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">${__('Select tags to remove.', 'groundhogg')}</label>
					${select(
						{
							name: 'tags',
							id: 'tags',
							multiple: true,
						},
						options,
						meta.tags.map((id) => parseInt(id))
					)}
					<p class="description">
						${__('All of the given tags will be removed from the contact.', 'groundhogg')}</p>
				</div>
			</div>`
      },

      onMount (step, updateStepMeta) {
        tagOnMount(step, updateStepMeta)
      },
      preload: preloadTags
    },

    /**
     * When a tag is applied
     */
    tag_applied: {
      defaults: {
        tags: [],
      },

      //language=HTML
      svg: `
		  <svg viewBox="0 0 39 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path
				  d="M5.356 21.311L18.522 8.145a1 1 0 01.707-.293h8.503a1 1 0 011 1v8.502a1 1 0 01-.293.707L15.272 31.228a1 1 0 01-1.414 0l-8.502-8.502a1 1 0 010-1.415z"
				  stroke="currentColor" stroke-width="2"/>
			  <circle r="1.525" transform="matrix(-1 0 0 1 23.773 12.81)" stroke="currentColor" stroke-width="1.2"/>
			  <path d="M38.105 23.435l-8.5 8.5-4.25-4.25" stroke="currentColor" stroke-width="2"/>
		  </svg>`,

      title ({ ID, data, meta }) {
        let { tags = [], condition = 'any' } = meta
        tags = tags.map((id) => parseInt(id))

        if (tags.length >= 4) {
          return condition === 'all'
            ? sprintf(__('%s tags are applied', 'groundhogg'), `<b>${tags.length}</b>`)
            : sprintf(__('Any of %s tags are applied', 'groundhogg'), `<b>${tags.length}</b>`)
        } else if (
          tags.length > 1 &&
          tags.length < 4 &&
          TagsStore.hasItems(tags)
        ) {
          const tagNames = tags.map(
            (tag) => `<b>${TagsStore.get(tag).data.tag_name}</b>`
          )
          return condition === 'all'
            ? sprintf(_x('%s are applied', 'list of tags - are applied', 'groundhogg'), andList(tagNames))
            : sprintf(_x('%s is applied', 'list of tags - is applied', 'groundhogg'), orList(tagNames))
        } else if (tags.length === 1) {
          return sprintf(_x('%s is applied', 'list of tags - is applied', 'groundhogg'), `<b>${TagsStore.get(tags[0]).data.tag_name}</b>`)
        } else {
          return __('A tag is applied', 'groundhogg')
        }
      },

      edit ({ ID, data, meta }) {
        let options = TagsStore.getItems().map((tag) => {
          return {
            text: tag.data.tag_name,
            value: tag.ID,
          }
        })

        const { tags = [], condition = 'any' } = meta

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">
						${sprintf(_x('Run when %s of the following tags are applied...', '%s is any|all', 'groundhogg'), select({
							id: 'condition'
						}, {
							any: __('Any', 'groundhogg'),
							all: __('All', 'groundhogg'),
						}, condition))}
					</label>
					${select({
							name: 'tags',
							id: 'tags',
							multiple: true,
						},
						options,
						tags.map((id) => parseInt(id))
					)}
				</div>
			</div>`
      },

      onMount (step, updateStepMeta) {
        tagWithConditionOnMount(step, updateStepMeta)
      },
      preload: preloadTags
    },

    /**
     * When a tag is remvoed
     */
    tag_removed: {
      defaults: {
        tags: [],
      },

      // language=HTML
      svg: `
		  <svg viewBox="0 0 37 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path
				  d="M5.649 21.311L18.815 8.145a1 1 0 01.707-.293h8.503a1 1 0 011 1v8.502a1 1 0 01-.293.707L15.565 31.228a1 1 0 01-1.414 0l-8.502-8.502a1 1 0 010-1.415z"
				  stroke="currentColor" stroke-width="2"/>
			  <circle r="1.525" transform="matrix(-1 0 0 1 24.066 12.81)" stroke="currentColor" stroke-width="1.2"/>
			  <path
				  d="M33.703 27.6a.6.6 0 10-.848-.848l.848.848zm-4.354 2.657a.6.6 0 10.849.848l-.849-.848zm3.506.848a.6.6 0 10.848-.848l-.848.848zm-2.657-4.353a.6.6 0 10-.849.848l.849-.848zm2.657 0l-3.506 3.505.849.848 3.505-3.505-.848-.848zm.848 3.505l-3.505-3.505-.849.848 3.506 3.505.848-.848zm1.724-1.35a3.9 3.9 0 01-3.9 3.901v1.2a5.1 5.1 0 005.1-5.1h-1.2zm-3.9 3.901a3.9 3.9 0 01-3.902-3.9h-1.2a5.1 5.1 0 005.101 5.1v-1.2zm-3.902-3.9a3.9 3.9 0 013.901-3.902v-1.2a5.1 5.1 0 00-5.1 5.101h1.2zm3.901-3.902a3.9 3.9 0 013.901 3.901h1.2a5.1 5.1 0 00-5.1-5.1v1.2z"
				  fill="currentColor"/>
		  </svg>`,

      title ({ ID, data, meta }) {
        let { tags, condition } = meta
        tags = tags.map((id) => parseInt(id))

        if (tags.length >= 4) {
          return condition === 'all'
            ? sprintf(__('%s tags are removed', 'groundhogg'), `<b>${tags.length}</b>`)
            : sprintf(__('Any of %s tags are removed', 'groundhogg'), `<b>${tags.length}</b>`)
        } else if (
          tags.length > 1 &&
          tags.length < 4 &&
          TagsStore.hasItems(tags)
        ) {
          const tagNames = tags.map(
            (tag) => `<b>${TagsStore.get(tag).data.tag_name}</b>`
          )
          return condition === 'all'
            ? sprintf(_x('%s are removed', 'list of tags - are removed', 'groundhogg'), andList(tagNames))
            : sprintf(_x('%s is removed', 'list of tags - is removed', 'groundhogg'), orList(tagNames))
        } else if (tags.length === 1) {
          return sprintf(_x('%s is removed', 'list of tags - is removed', 'groundhogg'), `<b>${TagsStore.get(tags[0]).data.tag_name}</b>`)
        } else {
          return __('A tag is removed', 'groundhogg')
        }
      },

      edit ({ ID, data, meta }) {
        let options = TagsStore.getItems().map((tag) => {
          return {
            text: tag.data.tag_name,
            value: tag.ID,
          }
        })

        const { condition } = meta

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">
						${sprintf(_x('Run when %s of the following tags are removed...', '%s is any|all', 'groundhogg'), select({
							id: 'condition'
						}, {
							any: __('Any', 'groundhogg'),
							all: __('All', 'groundhogg'),
						}, condition))}</label>
					${select(
						{
							name: 'tags',
							id: 'tags',
							multiple: true,
						},
						options,
						meta.tags.map((id) => parseInt(id))
					)}
				</div>
			</div>`
      },

      onMount (step, updateStepMeta) {
        tagWithConditionOnMount(step, updateStepMeta)
      },
      preload: preloadTags
    },

    delay_timer: {
      defaults: {
        ...delayTimerDefaults,
      },

      //language=HTML
      svg: `
		  <svg viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path
				  d="M7.327 4.489c3.468 0 6.279 2.652 6.279 5.923s-2.811 5.923-6.279 5.923c-3.467 0-6.278-2.652-6.278-5.923a5.7 5.7 0 011.427-3.76m1.997 1.337l2.854 2.961M5.33 1.335h4.28"
				  stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
		  </svg>
      `,

      title ({ meta }) {
        return delayTimerName({
          ...delayTimerDefaults,
          ...meta,
        })
      },

      edit ({ ID, data, meta }) {
        const {
          delay_amount,
          delay_type,
          run_on_type,
          run_when,
          run_time,
          send_in_timezone,
          run_time_to,
          run_on_dow_type, // Run on days of week type
          run_on_dow, // Run on days of week
          run_on_month_type, // Run on month type
          run_on_months, // Run on months
          run_on_dom, // Run on days of month
        } = {
          ...delayTimerDefaults,
          ...meta,
        }

        const runOnTypes = {
          any: 'Any day',
          weekday: 'Weekday',
          weekend: 'Weekend',
          day_of_week: 'Day of week',
          day_of_month: 'Day of month',
        }

        const runWhenTypes = {
          now: __('Any time', 'groundhogg'),
          later: __('Specific time', 'groundhogg'),
        }

        if (['minutes', 'hours', 'none'].includes(delay_type)) {
          runWhenTypes.between = __('Between', 'groundhogg')
        }

        const runOnDaysOfMonth = {}

        for (let i = 1; i < 32; i++) {
          runOnDaysOfMonth[i] = i
        }

        runOnDaysOfMonth.last = 'last'

        const runOnMonthTypes = {
          any: 'Of any month',
          specific: 'Of specific month(s)',
        }

        //language=HTML
        const runOnMonthOptions = `
			<div class="gh-input-group" style="margin-top: 10px">${select({
				className: 'delay-input re-render',
				name: 'run_on_month_type'
			}, runOnMonthTypes, run_on_month_type)}
				${run_on_month_type === 'specific' ? select({
					className: 'delay-input select2',
					name: 'run_on_months',
					multiple: true,
				}, delay_timer_i18n.months, run_on_months) : ''}
			</div>`

        //language=HTML
        const daysOfWeekOptions = `
			<div class="gh-input-group" style="margin-top: 10px">${select({
				className: 'delay-input',
				name: 'run_on_dow_type'
			}, delay_timer_i18n.day_of_week_determiners, run_on_dow_type)}
				${select({
					className: 'select2',
					name: 'run_on_dow',
					multiple: true
				}, delay_timer_i18n.days_of_week, run_on_dow)}
			</div>
			${runOnMonthOptions}`

        //language=HTML
        const daysOfMonthOptions = `
			<div style="margin-top: 10px">
				${select({
					className: 'select2',
					name: 'run_on_dom',
					multiple: true,
				}, runOnDaysOfMonth, run_on_dom)}
			</div>
			${runOnMonthOptions}`

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<h3 class="delay-preview" style="font-weight: normal">${delayTimerName(
						{
							...delayTimerDefaults,
							...meta,
						}
					)}</h3>
				</div>
				<div class="row">
					<label class="row-label">${__('Wait at least...', 'groundhogg')}</label>
					<div class="gh-input-group">
						${input({
							className: 'delay-input',
							type: 'number',
							name: 'delay_amount',
							value: delay_amount,
							placeholder: 3,
							disabled: delay_type === 'none'
						})}
						${select({
							className: 'delay-input re-render',
							name: 'delay_type'
						}, delay_timer_i18n.delay_duration_types, delay_type)}
					</div>
				</div>
				<div class="row">
					<label
						class="row-label">${_x('Then run on...', 'meaning to run a process on a certain date', 'groundhogg')}</label>
					${select({
						className: 'delay-input re-render',
						name: 'run_on_type'
					}, runOnTypes, run_on_type)}
					${run_on_type === 'day_of_week' ? daysOfWeekOptions : ''}
					${run_on_type === 'day_of_month' ? daysOfMonthOptions : ''}
				</div>
				<div class="row">
					<label
						class="row-label">${_x('Then run at...', 'meaning to run a process at a certain time', 'groundhogg')}</label>
					${select({
							className: 'delay-input re-render',
							name: 'run_when',
						},
						runWhenTypes,
						run_when
					)}
					${
						run_when === 'later'
							? input({
								className: 'delay-input',
								type: 'time',
								name: 'run_time',
								value: run_time
							}) : ''
					}
					${
						run_when === 'between'
							? sprintf(_x('%s and %s', 'between start time and end time', 'groundhogg'), input({
								className: 'delay-input',
								type: 'time',
								name: 'run_time',
								value: run_time
							}), input({
								className: 'delay-input',
								type: 'time',
								name: 'run_time_to',
								value: run_time_to
							}))
							: ''
					}
				</div>
			</div>`
      },

      onMount ({ meta }, updateStepMeta, updateStepData, getCurrentState) {
        const updatePreview = () => {
          $('.delay-preview').html(
            delayTimerName({
              ...delayTimerDefaults,
              ...getCurrentState().meta,
            })
          )
        }

        $('.select2')
          .select2({
            width: 'auto'
          })
          .on('change', function (e) {
            // console.log(e)
            updateStepMeta({
              [$(this).attr('name')]: $(this).val(),
            })
            updatePreview()
          })

        $('.delay-input')
          .on('change', ({ target }) => {

            const reRender = target.classList.contains('re-render')

            updateStepMeta({
              [target.name]: $(target).val(),
            }, reRender)

            if (reRender) {
              $(`[name=${target.name}]`).focus()
            } else {
              updatePreview()
            }
          }).on('input', function (e) {
          updatePreview()
        })
      },
    },

    /**
     * Send email
     */
    send_email: {
      defaults: {
        email_id: null,
      },
      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path d="M32.007 16.695V8.487a1 1 0 00-1-1H4.674a1 1 0 00-1 1V26.32a1 1 0 001 1H17.84"
			        stroke="currentColor"
			        stroke-width="2"/>
			  <path d="M3.674 8.903l14.166 8.5 14.167-8.5M20.674 24.487h11.333m0 0l-4.25-4.25m4.25 4.25l-4.25 4.25"
			        stroke="currentColor" stroke-width="2"/>
		  </svg>`,
      title ({ ID, data, meta }) {
        const { email_id } = meta
        const email = EmailsStore.get(parseInt(email_id))
        return email_id && email ? sprintf(_x('Send %s', 'send - email title', 'groundhogg'), `<b>${email.data.title}</b>`) : __('Send <b>an email</b>', 'groundhogg')
      },
      edit ({ ID, data, meta }) {
        const { email_id } = meta
        const email = EmailsStore.get(parseInt(email_id))

        const iframePreview = (email) => {
          const { context } = email

          // language=HTML
          return `
			  <div class="panel">
				  <div class="row">
					  <h3 class="subject-line">${email.data.subject}</h3>
					  <div id="from-line">
						  <img class="avatar" alt="avatar" src="${context.avatar}"/>
						  <div class="from">
							  <span class="from-name">${context.from_name}</span>
							  <span class="from-email">&lt;${context.from_email}&gt;</span>
						  </div>
					  </div>
				  </div>
				  <iframe id="email-preview"></iframe>
			  </div>`
        }

        //language=HTML
        return `
			${email_id && email ? iframePreview(email) : ''}
			${!email_id || !email ? `
			<div class="panel">
				<div class="row with-columns">
					<div class="column">
						<label
							class="row-label">${__('Select an email to send...', 'groundhogg')}</label>
						${select({
					id: 'email-picker',
					name: 'email_id',
				},
				EmailsStore.getItems().map((item) => {
					return {
						text: item.data.title,
						value: item.ID,
					}
				}), email && email.ID
			)}
					</div>
					<div class="column">
						<label class="row-label">${_x('Or...', 'choice between two actions', 'groundhogg')}</label>
						<button id="add-new-email" class="gh-button secondary">${__('Create a new email', 'groundhogg')}</button>
					</div>
				</div>
			</div>` : ''}`
      },
      onMount ({ meta }, updateStepMeta) {

        const { email_id } = meta
        const email = EmailsStore.get(parseInt(email_id))

        const fullFrame = (frame) => {
          frame.height = frame.contentWindow.document.body.offsetHeight
          frame.style.height =
            frame.contentWindow.document.body.offsetHeight + 'px'
        }

        if (email) {

          const frame = document.querySelector('iframe#email-preview')

          setFrameContent(frame, email.context.built)
          setTimeout(() => {
            fullFrame(frame)
          }, 100)

        } else {

          emailPicker('#email-picker', false, (items) => EmailsStore.itemsFetched(items)).on('change', (e) => {

            updateStepMeta({
              email_id: e.target.value
            }, true)

          })

        }
      },
      validate ({ meta }, addError, addWarning) {
        const { email_id } = meta
        const email = EmailsStore.get(parseInt(email_id))

        if (email_id && email && email.data.status !== 'ready') {
          addWarning('Email is in draft mode. Please update status to ready!')
        }
      },
      preload ({ export: exported, meta }) {

        if (!exported) {
          return EmailsStore.fetchItem(meta.email_id)
        }

        EmailsStore.itemsFetched([
          exported.email
        ])
      }
    },

    link_click: {
      defaults: {
        redirect_to: '',
      },

      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path d="M8.594 4.671v22.305l5.329-5.219 3.525 8.607 3.278-1.23-3.688-8.718h7.14L8.593 4.67z"
			        stroke="currentColor" stroke-width="2"/>
		  </svg>`,

      title ({ meta }) {
        const { redirect_to } = meta
        if (redirect_to) {
          const targetUrl = new URL(redirect_to)
          const homeUrl = new URL(Groundhogg.managed_page.root)

          if (targetUrl.hostname === homeUrl.hostname) {
            return `Clicked to <b>${targetUrl.pathname}</b>`
          } else {
            return `Clicked to <b>${targetUrl.hostname}</b>`
          }
        } else {
          return 'Clicked a tracking link'
        }
      },

      edit ({ meta }) {
        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="copy-this">Copy this link</label>
					<input type="url" id="copy-this" class="code input regular-text"
					       value="${
						       Groundhogg.managed_page.root
					       }link/click/${'some-value'}" onfocus="this.select()"
					       readonly>
					<p class="description">Paste this link in any email or page. Once a contact clicks it the benchmark
						will be completed and the contact will be redirected to the page set below.</p>
				</div>
				<div class="row">
					<label class="row-label" for="copy">Then redirect contacts to...</label>
					${inputWithReplacements({
						type: 'url',
						id: 'redirect-to',
						name: 'redirect_to',
						className: 'regular-text',
						value: meta.redirect_to,
					})}
					<p class="description">Upon clicking the tracking link contacts will be redirected to this page.</p>
				</div>
			</div>`
      },
      onMount ({ meta }, updateStepMeta) {
        linkPicker('#redirect-to').on('change', function (e) {
          updateStepMeta({
            redirect_to: $(this).val(),
          })
        })
      },
    },

    email_confirmed: {
      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path d="M31.685 16.81V8.6a1 1 0 00-1-1H4.352a1 1 0 00-1 1v17.834a1 1 0 001 1h13.166"
			        stroke="currentColor"
			        stroke-width="2"/>
			  <path d="M3.352 9.018l14.166 8.5 14.167-8.5M33.102 20.35l-8.5 8.5-4.25-4.25" stroke="currentColor"
			        stroke-width="2"/>
		  </svg>`,
      edit ({}) {
        //language=html
        return `
			<div class="panel">
				<p>This benchmark is completed whenever a <a target="_blank"
				                                             href="https://help.groundhogg.io/article/381-how-to-confirm-an-email-address">contact
					confirms their email address.</a> It does not have any settings.</p>
			</div>`
      },
    },

    form_fill: {
      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 31" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path
				  d="M1.5 29.802a.25.25 0 01-.25-.25v-6a.25.25 0 01.25-.25h32a.25.25 0 01.25.25v6a.25.25 0 01-.25.25h-32z"
				  fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
			  <path
				  d="M1.5 7.733a.25.25 0 01-.25-.25v-6a.25.25 0 01.25-.25h32a.25.25 0 01.25.25v6a.25.25 0 01-.25.25h-32zm0 11a.25.25 0 01-.25-.25v-6a.25.25 0 01.25-.25h32a.25.25 0 01.25.25v6a.25.25 0 01-.25.25h-32z"
				  stroke="currentColor" stroke-width="1.5"/>
		  </svg>`,
      title ({ meta }) {

        const { form_name } = meta

        if (!form_name) {
          return __('Submits <b>a form</b>', 'groundhogg')
        }

        return sprintf(__('Submits %s', 'groundhogg'), `<b>${form_name}</b>`)
      },
      edit ({ meta }) {
        // language=html
        const redirectToURL = `<label class="row-label">${__('Redirect to this URL...', 'groundhogg')}</label>
		${inputWithReplacements({
			id: 'success-page',
			name: 'success_page',
			className: 'regular-text',
			value: meta.success_page || '',
		})}`

        // language=html
        const stayOnPage = `<label class="row-label">${__('Show this message...', 'groundhogg')}</label>
		${textAreaWithReplacementsAndEmojis({
			id: 'success-message',
			name: 'success_message',
			className: 'regular-text',
			value: meta.success_message || '',
		})}`

        //language=HTML
        return `
			<div class="inline-label form-name" tabindex="0">
				<label for="form-name">${_x('Form name:', 'input label', 'groundhogg')}</label>
				<div class="input-wrap">
					${input({
						name: 'form_name',
						id: 'form-name',
						placeholder: _x('My registration form', 'input placeholder value', 'groundhogg'),
						value: meta.form_name || '',
					})}
				</div>
			</div>
			<div id="edit-form"></div>
			<div class="panel">
				<div class="row">
					<p>${__('Stay on page after submitting?', 'groundhogg')} ${toggle({
						name: 'enable_ajax',
						id: 'enable-ajax',
						checked: meta.enable_ajax,
						onLabel: _x('YES', 'toggle switch', 'groundhogg'),
						offLabel: _x('NO', 'toggle switch', 'groundhogg'),
					})}</p>
				</div>
				<div class="row">
					${meta.enable_ajax ? stayOnPage : redirectToURL}
				</div>
			</div>`
      },
      onMount ({ meta }, updateStepMeta) {
        linkPicker('#success-page').on('change', (e) => {
          updateStepMeta({
            success_page: e.target.value,
          })
        })

        $('#success-message').on('change', (e) => {
          updateStepMeta({
            success_message: e.target.value,
          })
        })

        $('#enable-ajax').on('change', (e) => {
          updateStepMeta(
            {
              enable_ajax: e.target.checked,
            },
            true
          )
        })

        $('#form-name').on('change', (e) => {
          updateStepMeta({
            form_name: e.target.value,
          })
        })

        const editor = formBuilder(
          '#edit-form',
          copyObject(meta.form),
          (form) => {
            updateStepMeta({
              form,
            })
          }
        )

        editor.init()
      },
    },
  }

  Groundhogg.StepPacks = StepPacks
  Groundhogg.StepTypes = StepTypes

})(jQuery)