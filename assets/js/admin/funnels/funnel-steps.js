( ($) => {

  const {
    select,
    toggle,
    tinymceElement,
    improveTinyMCE,
    andList,
    orList,
    input,
    inputWithReplacements,
    textAreaWithReplacements,
    confirmationModal,
    setFrameContent,
    icons,
    moreMenu,
    dangerConfirmationModal,
  } = Groundhogg.element

  const { createFilters } = Groundhogg.filters.functions
  const {
    ContactFilterDisplay,
    ContactFilters,
  } = Groundhogg.filters

  const {
    linkPicker,
  } = Groundhogg.pickers

  const {
    emails: EmailsStore,
    tags  : TagsStore,
  } = Groundhogg.stores

  const {
    Div,
    Button,
    ModalFrame,
    ItemPicker,
    Iframe,
    makeEl,
    Dashicon,
    H2,
    Pg,
    Fragment,
    Input,
  } = MakeEl

  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n

  improveTinyMCE()

  function ordinal_suffix_of (i) {

    if (i === 'last') {
      return 'Last'
    }

    var j = i % 10,
      k = i % 100
    if (j == 1 && k != 11) {
      return i + 'st'
    }
    if (j == 2 && k != 12) {
      return i + 'nd'
    }
    if (j == 3 && k != 13) {
      return i + 'rd'
    }
    return i + 'th'
  }

  const delayTimerDefaults = {
    delay_amount     : 3,
    delay_type       : 'days',
    run_on_type      : 'any',
    run_when         : 'now',
    run_time         : '09:00:00',
    send_in_timezone : false,
    run_time_to      : '17:00:00',
    run_on_dow_type  : 'any', // Run on days of week type
    run_on_dow       : [], // Run on days of week
    run_on_month_type: 'any', // Run on month type
    run_on_months    : [], // Run on months
    run_on_dom       : [], // Run on days of month
  }

  const delay_timer_i18n = {
    delay_duration_types   : {
      minutes: __('Minutes'),
      hours  : __('Hours'),
      days   : __('Days'),
      weeks  : __('Weeks'),
      months : __('Months'),
      years  : __('Years'),
      none   : __('No delay', 'groundhogg'),
    },
    day_of_week_determiners: {
      any   : __('Any'),
      first : __('First'),
      second: __('Second'),
      third : __('Third'),
      fourth: __('Fourth'),
      last  : __('Last'),
    },
    days_of_week           : {
      monday   : __('Monday'),
      tuesday  : __('Tuesday'),
      wednesday: __('Wednesday'),
      thursday : __('Thursday'),
      friday   : __('Friday'),
      saturday : __('Saturday'),
      sunday   : __('Sunday'),
    },
    months                 : {
      january  : __('January'),
      february : __('February'),
      march    : __('March'),
      april    : __('April'),
      may      : __('May'),
      june     : __('June'),
      july     : __('July'),
      august   : __('August'),
      september: __('September'),
      october  : __('October'),
      november : __('November'),
      december : __('December'),
    },
  }

  const capitalize = (string) => {
    return string.charAt(0).toUpperCase() + string.slice(1)
  }

  const runOnTypes = {
    any         : 'Any day',
    weekday     : 'Weekday',
    weekend     : 'Weekend',
    day_of_week : 'Day of week',
    day_of_month: 'Day of month',
  }

  const runOnDaysOfMonth = {}

  for (let i = 1; i < 32; i++) {
    runOnDaysOfMonth[i] = i
  }

  runOnDaysOfMonth.last = 'last'

  const runOnMonthTypes = {
    any     : 'Of any month',
    specific: 'Of specific month(s)',
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
        timeStyle: 'short',
      }).format(new Date(`2021-01-01 ${ time }`))
    }

    switch (run_when) {
      case 'now':
        preview.unshift(_x('at any time', 'groundhogg'))
        break
      case 'later':
        preview.unshift(sprintf(_x('at %s', 'at a specific time', 'groundhogg'), `<b>${ formatTime(run_time) }</b>`))
        break
      case 'between':
        preview.unshift(
          sprintf(_x('between %1$s and %2$s', 'within a time from', 'groundhogg'), `<b>${ formatTime(run_time) }</b>`,
            `<b>${ formatTime(run_time_to) }</b>`))
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
        let dowList = orList(run_on_dow.map((i) => `<b>${ delay_timer_i18n.days_of_week[i] }</b>`))
        days = run_on_dow_type === 'any'
               ? sprintf(_x('any %s', 'any - day of the week', 'groundhogg'), dowList)
               : sprintf(_x('the %1$s %2$s', 'the - determiner - day of week', 'groundhogg'),
            delay_timer_i18n.day_of_week_determiners[run_on_dow_type].toLowerCase(), dowList)
        months = run_on_month_type === 'specific' ? orList(
          run_on_months.map((i) => `<b>${ delay_timer_i18n.months[i] }</b>`)) : `<b>${ __('any month',
          'groundhogg') }</b>`
        preview.unshift(sprintf(
          _x('run on %1$s of %2$s', 'verb meaning to start on process - on a specific day of a specific month',
            'groundhogg'), days, months))
        break
      case 'day_of_month':
        days = run_on_dom.length > 0
               ? sprintf(_x('the %s', 'the - ordinal day of month', 'groundhogg'), orList(
            run_on_dom.map((i) => `<b>${ i === 'last' ? __('last day', 'groundhogg') : ordinal_suffix_of(i) }</b>`)))
               : `<b>${ __('any day', 'groundhogg') }</b>`
        months = run_on_month_type === 'specific' ? orList(
          run_on_months.map((i) => `<b>${ delay_timer_i18n.months[i] }</b>`)) : `<b>${ __('any month',
          'groundhogg') }</b>`
        preview.unshift(sprintf(
          _x('run on %1$s of %2$s', 'verb meaning to start on process - on a specific day of a specific month',
            'groundhogg'), days, months))
        break
    }

    if (delay_type !== 'none') {

      delay_amount = parseInt(delay_amount)

      const delayTypes = {
        minutes: _n('minute', 'minutes', delay_amount),
        hours  : _n('hour', 'hours', delay_amount),
        days   : _n('day', 'days', delay_amount),
        weeks  : _n('week', 'weeks', delay_amount),
        months : _n('month', 'months', delay_amount),
        years  : _n('year', 'years', delay_amount),
      }

      preview.unshift(
        sprintf(_x('Wait at least %s and then', 'wait for a duration', 'groundhogg'),
          `<b>${ delay_amount } ${ delayTypes[delay_type] }</b>`),
      )
    }

    return capitalize(preview.join(' '))
  }

  const DelayTimer = {

    edit ({
      ID,
      data,
      meta,
    }) {
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
        delay_preview = '',
      } = {
        ...delayTimerDefaults,
        ...meta,
        ...Funnel.getActiveStep().meta,
      }

      //language=HTML
      const runOnMonthOptions = `
          <div class="gh-input-group">${ select({
              className: 'delay-input re-render',
              name     : 'run_on_month_type',
          }, runOnMonthTypes, run_on_month_type) }
              ${ run_on_month_type === 'specific' ? select({
                  className: 'delay-input select2__picker',
                  name     : 'run_on_months',
                  multiple : true,
              }, delay_timer_i18n.months, run_on_months) : '' }
          </div>`

      //language=HTML
      const daysOfWeekOptions = `
          <div class="gh-input-group">${ select({
              className: 'delay-input',
              name     : 'run_on_dow_type',
          }, delay_timer_i18n.day_of_week_determiners, run_on_dow_type) }
              ${ select({
                  className: 'select2__picker',
                  name     : 'run_on_dow',
                  multiple : true,
              }, delay_timer_i18n.days_of_week, run_on_dow) }
          </div>
          ${ runOnMonthOptions }`

      //language=HTML
      const daysOfMonthOptions = `
          <div>
              ${ select({
                  className: 'select2__picker',
                  name     : 'run_on_dom',
                  multiple : true,
              }, runOnDaysOfMonth, run_on_dom) }
          </div>
          ${ runOnMonthOptions }`

      //language=HTML
      return `
          <div class="display-flex column gap-10">
              <h3 class="delay-preview" style="font-weight: normal">${ delayTimerName({
                  ...delayTimerDefaults,
                  ...meta,
              }) }</h3>
              <div class="row display-flex column gap-10">
                  <label class="row-label">${ __('Wait at least...', 'groundhogg') }</label>
                  <div class="gh-input-group">
                      ${ input({
                          className  : 'delay-input',
                          type       : 'number',
                          name       : 'delay_amount',
                          value      : delay_amount,
                          placeholder: 3,
                          disabled   : delay_type === 'none',
                      }) }
                      ${ select({
                          className: 'delay-input re-render',
                          name     : 'delay_type',
                      }, delay_timer_i18n.delay_duration_types, delay_type) }
                  </div>
              </div>
              <div class="row display-flex column gap-10">
                  <label
                          class="row-label">${ _x('Then run on...', 'meaning to run a process on a certain date',
                          'groundhogg') }</label>
                  <div class="display-flex gap-10">
                      ${ select({
                          className: 'delay-input re-render',
                          name     : 'run_on_type',
                      }, runOnTypes, run_on_type) }
                  </div>
                  ${ run_on_type === 'day_of_week' ? daysOfWeekOptions : '' }
                  ${ run_on_type === 'day_of_month' ? daysOfMonthOptions : '' }
              </div>
              <div class="row display-flex column gap-10">
                  <label class="row-label">${ _x('Then run at...', 'meaning to run a process at a certain time',
                          'groundhogg') }</label>
                  <div class="gh-input-group">
                      ${ select({
                          className: 'delay-input re-render',
                          name     : 'run_when',
                          options  : runWhenTypes,
                          selected : run_when,
                      }) }
                      ${ run_when === 'later'
                         ? input({
                                  className: 'delay-input',
                                  type     : 'time',
                                  name     : 'run_time',
                                  value    : run_time,
                              }) : '' }
                      ${ run_when === 'between'
                         ? [
                             input({
                                 className: 'delay-input',
                                 type     : 'time',
                                 name     : 'run_time',
                                 value    : run_time,
                             }),
                             input({
                                 className: 'delay-input',
                                 type     : 'time',
                                 name     : 'run_time_to',
                                 value    : run_time_to,
                             }),
                         ].join('')
                         : '' }
                  </div>
              </div>
              <div class="display-flex align-center gap-10">
                  <p>${ __('Run in the contact\'s timezone?', 'groundhogg') }</p>
                  ${ toggle({
                      onLabel : 'Yes',
                      offLabel: 'No',
                      id      : `${ ID }_send_in_timezone`,
                      name    : 'send_in_timezone',
                      checked : Boolean(send_in_timezone),
                  }) }
              </div>
          </div>`
    },

    onMount ({
      ID,
      meta,
    }, updateStepMeta, updateStepData, getCurrentState) {
      const updatePreview = () => {

        let preview = delayTimerName({
          ...delayTimerDefaults,
          ...getCurrentState().meta,
        })

        updateStepMeta({
          delay_preview: preview,
        })

        $(`#settings-${ ID } .delay-preview`).html(preview)
      }

      $(`#${ ID }_send_in_timezone`).on('change', (e) => {
        updateStepMeta({
          send_in_timezone: e.target.checked,
        })
      })

      $(`#settings-${ ID } .select2__picker`).select2({
        width: 'auto',
      }).on('change', function (e) {
        // console.log(e)
        updateStepMeta({
          [$(this).attr('name')]: $(this).val(),
        })
        updatePreview()
      })

      $(`#settings-${ ID } .delay-input`).on('change', ({ target }) => {

        const reRender = target.classList.contains('re-render')

        updateStepMeta({
          [target.name]: $(target).val(),
        }, reRender)

        if (reRender) {
          $(`#settings-${ ID } [name=${ target.name }]`).focus()
        }
        else {
          updatePreview()
        }
      }).on('input change', function (e) {
        updatePreview()
      })
    },
  }

  const WebForm = {
    edit ({ meta }) {
      // language=html
      const redirectToURL = `<label class="row-label">${ __('Redirect to this URL...', 'groundhogg') }</label>
      ${ inputWithReplacements({
          name     : 'success_page',
          className: 'full-width',
          value    : meta.success_page || '',
      }) }`

      // language=html
      const stayOnPage = `<label class="row-label">${ __('Show this message...', 'groundhogg') }</label>
      ${ textAreaWithReplacements({
          name     : 'success_message',
          className: 'full-width',
          value    : meta.success_message || '',
      }) }`

      //language=HTML
      return `
          <div class="edit-form"></div>
          <div class="after-submit gh-panel ${ meta.enable_ajax ? 'ajax-enabled' : '' }">
              <div class="gh-panel-header">
                  <h2>After submit...</h2>
              </div>
              <div class="inside display-flex column gap-10">
                  <div class="display-flex gap-10 align-center">
                      <p>${ __('Stay on page after submitting?', 'groundhogg') }</p>
                      ${ toggle({
                          name    : 'enable_ajax',
                          checked : Boolean(meta.enable_ajax),
                          onLabel : _x('YES', 'toggle switch', 'groundhogg'),
                          offLabel: _x('NO', 'toggle switch', 'groundhogg'),
                      }) }
                  </div>
                  <div class="success-message">
                      ${ stayOnPage }
                  </div>
                  <div class="success-redirect">
                      ${ redirectToURL }
                  </div>
              </div>
          </div>
          <div class="form-style gh-panel">
              <div class="gh-panel-header">
                  <h2>${ __('Form Style', 'groundhogg') }</h2>
              </div>
              <div class="inside display-flex gap-10">
                  <div class="display-flex column gap-10">
                      <label for="form-theme">${ __('Theme') }</label>
                      ${ select({
                          id      : 'form-theme',
                          name    : 'form_theme',
                          options : {
                              default: _x('Theme Default', 'form theme', 'groundhogg'),
                              simple : _x('Simple', 'form theme', 'groundhogg'),
                              modern : _x('Modern', 'form theme', 'groundhogg'),
                              classic: _x('Classic', 'form theme', 'groundhogg'),
                          },
                          selected: meta.theme ?? 'default',
                      }) }
                  </div>
                  <div class="display-flex column gap-10">
                      <label for="form-accent-color">${ __('Accent Color') }</label>
                      ${ input({
                          id       : 'form-accent',
                          name     : 'form_accent_color',
                          type     : 'color',
                          className: 'color-picker',
                          value    : meta.accent_color,
                      }) }
                  </div>
              </div>
          </div>
      `
    },
    onMount ({
      ID,
      meta,
    }, updateStepMeta) {

      const parent = `#settings-${ ID }`

      linkPicker(`${ parent } input[name=success_page]`).on('change', (e) => {
        updateStepMeta({
          success_page: e.target.value,
        })
      })

      $(`${ parent } textarea[name=success_message]`).on('change', (e) => {
        updateStepMeta({
          success_message: e.target.value,
        })
      })

      const $panel = $(`${ parent } .after-submit`)

      $(`${ parent } input[name=enable_ajax]`).on('change', (e) => {

        updateStepMeta({
          enable_ajax: e.target.checked,
        })

        if (e.target.checked) {
          $panel.addClass('ajax-enabled')
        }
        else {
          $panel.removeClass('ajax-enabled')
        }
      })

      $(`${ parent } select[name=form_theme]`).on('change', e => {
        updateStepMeta({
          theme: e.target.value,
        })
      })

      $(`${ parent } input[name=form_accent_color]`).on('change', e => {
        updateStepMeta({
          accent_color: e.target.value,
        })
      })

      const formBuilder = Groundhogg.FormBuilder(`${ parent } div.edit-form`, meta.form, (form) => {
        updateStepMeta({
          form,
        })
      })

      formBuilder.mount()

    },
  }

  const FunnelSteps = {

    init () {

      $(document).on('step-active', e => {

        let active = Funnel.getActiveStep()

        switch (active.data.step_type) {
          case 'apply_note':
            this.applyNote(active)
            break
          case 'create_task':
            this.createTask(active)
            break
          case 'admin_notification':
            this.adminNotification(active)
            break
          // case 'delay_timer':
          //   this.delayTimer(active)
          //   break
          case 'web_form':
            this.webForm(active)
            break
          case 'form_fill':
            this.formFill(active)
            break
        }
      })
    },

    formFill ({ ID }) {

      let id = `step_${ ID }_upgrade_form`
      const $btn = $(`#${ id }`)

      if ($btn.data('flag')) {
        return
      }

      $btn.data('flag', true)

      $btn.on('click', e => {

        confirmationModal({
          alert      : `<p>${ __('Once you upgrade to this form to the new form builder there is no going back.',
            'groundhogg') }</p>`,
          confirmText: __('Upgrade Form', 'groundhogg'),
          onConfirm  : () => {

            $(`#step_${ ID }_upgrade_form_confirm`).val('confirm')
            Funnel.save()

          },
        })

      })

    },

    webForm (step) {

      let id = `step_${ step.ID }_web_form_builder`

      const mount = (step) => {
        $(`#${ id }`).html(WebForm.edit(step))

        WebForm.onMount(step, (meta, reRender) => {

          let step = Funnel.updateStepMeta(meta)

          if (reRender) {
            mount(step)
          }

        }, () => {}, () => Funnel.getActiveStep())
      }

      mount(step)

    },

    adminNotification (step) {
      this.applyNote(step)
    },

    applyNote (step) {

      let id = `step_${ step.ID }_note_text`

      wp.editor.remove(id)
      tinymceElement(id, {
        replacements : true,
        noteTemplates: true,
        quicktags    : false,
      }, (content) => {
        Funnel.updateStepMeta({
          note_text: content,
        })
      })
    },

    createTask (step) {
      let id = `step_${ step.ID }_task_content`

      wp.editor.remove(id)
      tinymceElement(id, {
        replacements : true,
        taskTemplates: true,
        quicktags    : false,
      }, (content) => {
        Funnel.updateStepMeta({
          content,
        })
      })
    },

    delayTimer (step) {
      let id = `step_${ step.ID }_delay_timer_settings`

      const mount = (step) => {
        $(`#${ id }`).html(DelayTimer.edit(step))

        DelayTimer.onMount(step, (meta, reRender) => {

          let step = Funnel.updateStepMeta(meta)

          if (reRender) {
            mount(step)
          }

        }, () => {}, () => Funnel.getActiveStep())
      }

      mount(step)

    },
  }

  FunnelSteps.init()

  const TagPicker = ({
    id,
    tagIds,
    onChange = () => {},
  }) => {

    return ItemPicker({
      id,
      noneSelected: 'Select tags...',
      tags        : true,
      selected    : tagIds.filter(id => TagsStore.has(id)).map(id => {
        let tag = TagsStore.get(id)
        return {
          id,
          text: tag.data.tag_name,
        }
      }),
      fetchOptions: async (search) => {
        let tags = await TagsStore.fetchItems({
          search,
          limit: 30,
        })

        return tags.map(({
          ID,
          data,
        }) => ( {
          id  : ID,
          text: data.tag_name,
        } ))
      },
      createOption: async (id) => {
        let tag = await TagsStore.create({
          data: {
            tag_name: id,
          },
        })

        return {
          id  : tag.ID,
          text: tag.data.tag_name,
        }
      },
      onChange    : items => onChange(items.map(({ id }) => id)),
    })
  }

  const tagPickerCallback = async ({
    ID,
    meta,
    data,
  }) => {

    let picker = document.getElementById(`step_${ ID }_tags`)

    let { tags = [] } = meta

    if (!tags) {
      tags = []
    }

    if (picker) {

      picker.closest('.gh-panel').classList.add('ignore-morph')

      // Might have to preload
      if (tags.length) {
        await TagsStore.maybeFetchItems(tags)
      }

      picker.replaceWith(TagPicker({
        id      : `step-tags-${ ID }`,
        tagIds  : tags,
        onChange: tags => Funnel.updateStepMeta({
          tags,
        }),
      }))
    }
  }

  function sortByOrder (order, items) {
    // Create a lookup map for quick index access
    const orderMap = new Map(order.map((id, index) => [
      id,
      index,
    ]))

    return items.sort((a, b) => ( orderMap.get(a.id) ?? Infinity ) - ( orderMap.get(b.id) ?? Infinity ))
  }

  Funnel.registerStepCallbacks('delay_timer', {
    onActive: async ({
      ID,
      meta,
      data,
    }) => {

      let id = `step_${ ID }_delay_timer_settings`

      const DelayTimerSettings = (updateMeta) => {

        const timerSettings = {
          ...delayTimerDefaults,
          ...meta,
        }

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
          delay_preview = '',
        } = timerSettings

        const runWhenTypes = {
          now  : __('Any time', 'groundhogg'),
          later: __('Specific time', 'groundhogg'),
        }

        if ([
          'minutes',
          'hours',
          'none',
        ].includes(delay_type)) {
          runWhenTypes.between = __('Between', 'groundhogg')
        }

        const runOnMonthOptions = () => MakeEl.InputGroup([
          MakeEl.Select({
            id      : `run-on-month-type-${ ID }`,
            name    : 'run_on_month_type',
            options : runOnMonthTypes,
            selected: run_on_month_type,
            onChange: e => updateMeta({
              run_on_month_type: e.target.value,
            }),
          }),
          run_on_month_type === 'specific' ? ItemPicker({
            id          : `run-on-months-${ ID }`,
            selected    : sortByOrder(Object.keys(delay_timer_i18n.months), run_on_months.map(m => ( {
              id  : m,
              text: delay_timer_i18n.months[m],
            } ))),
            fetchOptions: async (search) => {
              return Groundhogg.functions.assoc2array(delay_timer_i18n.months).filter(item => item.text.match(search))
            },
            onChange    : months => {
              updateMeta({
                run_on_months: months.map(m => m.id),
              })
            },
          }) : null,
        ])

        const daysOfWeekOptions = () => MakeEl.InputGroup([
          MakeEl.Select({
            id      : `run-on-dow-type-${ ID }`,
            name    : 'run_on_dow_type',
            options : delay_timer_i18n.day_of_week_determiners,
            selected: run_on_dow_type,
            onChange: e => updateMeta({
              run_on_dow_type: e.target.value,
            }),
          }),
          ItemPicker({
            id          : `run-on-dow-${ ID }`,
            selected    : sortByOrder(Object.keys(delay_timer_i18n.days_of_week), run_on_dow.map(dow => ( {
              id  : dow,
              text: delay_timer_i18n.days_of_week[dow],
            } ))),
            fetchOptions: async (search) => {
              return Groundhogg.functions.assoc2array(delay_timer_i18n.days_of_week).filter(item => item.text.match(search))
            },
            onChange    : dow => {
              updateMeta({
                run_on_dow: dow.map(d => d.id),
              })
            },
          }),
        ])

        const daysOfMonthOptions = () => ItemPicker({
          id          : `run-on-dom-${ ID }`,
          selected    : sortByOrder(Object.keys(runOnDaysOfMonth), run_on_dom.map(dom => ( {
            id  : `${ dom }`,
            text: ordinal_suffix_of(dom),
          } ))),
          fetchOptions: async (search) => {
            return Groundhogg.functions.assoc2array(runOnDaysOfMonth).map(dom => ( {
              id  : `${ dom.id }`,
              text: ordinal_suffix_of(dom.text),
            } )).filter(item => item.text.match(search))
          },
          onChange    : dom => {
            updateMeta({
              run_on_dom: dom.map(d => d.id),
            })
          },
        })

        return Div({
          className: 'display-flex column gap-10',
        }, [
          MakeEl.H3({
            className: 'delay-preview',
            style    : {
              fontWeight: 'normal',
            },
          }, delayTimerName(timerSettings)),
          // Delay amount
          Div({ className: 'row display-flex gap-10 column' }, [
            MakeEl.Label({ className: 'row-label' }, __('Wait at least...', 'groundhogg')),
            MakeEl.InputGroup([
              Input({
                value   : delay_amount,
                name    : 'delay_amount',
                type    : 'number',
                min     : 0,
                disabled: delay_type === 'none',
                onChange: e => updateMeta({
                  delay_amount: e.target.value,
                }),
              }),
              MakeEl.Select({
                name    : 'delay_type',
                options : delay_timer_i18n.delay_duration_types,
                selected: delay_type,
                onChange: e => updateMeta({
                  delay_type: e.target.value,
                }),
              }),
            ]),
          ]),
          Div({ className: 'row display-flex gap-10 column' }, [
            MakeEl.Label({ className: 'row-label' }, _x('Then run on...', 'meaning to run a process on a certain date', 'groundhogg')),
            Div({ className: 'display-flex gap-10' }, [
              MakeEl.Select({
                name    : 'run_on_type',
                options : runOnTypes,
                selected: run_on_type,
                onChange: e => updateMeta({
                  run_on_type: e.target.value,
                }),
              }),
              run_on_type === 'day_of_week' ? daysOfWeekOptions() : null,
              run_on_type === 'day_of_month' ? daysOfMonthOptions() : null,
            ]),
            run_on_type === 'day_of_week' || run_on_type === 'day_of_month' ? runOnMonthOptions() : null,
          ]),
          Div({ className: 'row display-flex gap-10 column' }, [
            MakeEl.Label({ className: 'row-label' }, _x('Then run at...', 'meaning to run a process at a certain time', 'groundhogg')),
            MakeEl.InputGroup([
              MakeEl.Select({
                name    : 'run_when',
                options : runWhenTypes,
                selected: run_when,
                onChange: e => updateMeta({
                  run_when: e.target.value,
                }),
              }),
              run_when === 'later' ? Input({
                className: 'delay-input',
                type     : 'time',
                name     : 'run_time',
                value    : run_time,
                onChange : e => updateMeta({ run_time: e.target.value }),
              }) : null,
              run_when === 'between' ? MakeEl.Fragment([
                Input({
                  className: 'delay-input',
                  type     : 'time',
                  name     : 'run_time',
                  value    : run_time,
                  onChange : e => updateMeta({ run_time: e.target.value }),
                }),
                Input({
                  className: 'delay-input',
                  type     : 'time',
                  name     : 'run_time_to',
                  value    : run_time_to,
                  onChange : e => updateMeta({ run_time_to: e.target.value }),
                })
              ]) : null,
            ]),
          ]),
          Div({ className: 'display-flex align-center gap-10' }, [
            Pg({},  __('Run in the contact\'s timezone?', 'groundhogg') ),
            MakeEl.Toggle({
              onLabel : 'Yes',
              offLabel: 'No',
              id      : `${ ID }_send_in_timezone`,
              name    : 'send_in_timezone',
              checked : Boolean(send_in_timezone),
              onChange: e => updateMeta({
                send_in_timezone: e.target.checked
              })
            })
          ])
        ])
      }

      morphdom(document.getElementById(id), Div({ id }, morph => {
        const updateMeta = (newMeta) => {
          meta = {
            ...meta,
            ...newMeta,
          }

          Funnel.updateStepMeta({
            ...newMeta,
            delay_preview: delayTimerName({
              ...delayTimerDefaults,
              ...meta,
            }),
          })
          morph()
        }

        return DelayTimerSettings(updateMeta)
      }))

    },
  })

  Funnel.registerStepCallbacks('apply_tag', {
    onActive: tagPickerCallback,
  })

  Funnel.registerStepCallbacks('tag_applied', {
    onActive: tagPickerCallback,
  })

  Funnel.registerStepCallbacks('tag_removed', {
    onActive: tagPickerCallback,
  })

  Funnel.registerStepCallbacks('remove_tag', {
    onActive: tagPickerCallback,
  })

  Funnel.registerStepCallbacks('if_else', {
    onActive: ({
      ID,
      meta,
    }) => {

      const {
        include_filters = [],
        exclude_filters = [],
      } = meta

      morphdom(document.getElementById(`step_${ ID }_include_filters`), ContactFilters(`step_${ ID }_include_filters`, include_filters, filters => {
        Funnel.updateStepMeta({
          include_filters: filters,
          include_display: ContactFilterDisplay(filters).innerHTML,
        })
      }))

      morphdom(document.getElementById(`step_${ ID }_exclude_filters`), ContactFilters(`step_${ ID }_exclude_filters`, exclude_filters, filters => {
        Funnel.updateStepMeta({
          exclude_filters: filters,
          exclude_display: ContactFilterDisplay(filters).innerHTML,
        })
      }))
    },
  })

  Funnel.registerStepCallbacks('send_email', {
    onActive   : async ({
      ID,
      meta,
      data,
    }) => {

      let id = `step_${ ID }_send_email`
      let { email_id } = meta

      if (email_id) {
        await EmailsStore.maybeFetchItem(email_id)
      }

      const morphPreview = () => {
        let previewPanel = document.getElementById(id)
        morphdom(previewPanel, Preview())
      }

      const getEmail = () => EmailsStore.get(email_id)
      const hasEmail = () => EmailsStore.has(email_id)

      const openEmailEditor = email => {
        ModalFrame({
          closeOnOverlayClick: false,
          onOpen             : ({ close }) => {
            Groundhogg.EmailEditor({
              email,
              onSave : email => {
                email_id = email.ID
                Funnel.updateStepMeta({
                  email_id,
                })
                setTimeout(morphPreview, 100)
              },
              onClose: close,
            })
          },
        }, Div({ id: 'email-editor' }))
      }

      const EmailPicker = ItemPicker({
        id          : `step-${ ID }-email-picker`,
        noneSelected: 'Search for an email...',
        selected    : email_id ? {
          id  : email_id,
          text: getEmail().data.title,
        } : [],
        multiple    : false,
        fetchOptions: (search) => {
          return EmailsStore.fetchItems({ search }).
            then(emails => emails.map(({
              ID,
              data,
            }) => ( {
              id  : ID,
              text: data.title,
            } )))
        },
        onChange    : item => {

          email_id = item ? item.id : false

          Funnel.updateStepMeta({
            email_id,
          })

          setTimeout(morphPreview, 100)

          // morphPreview()

        },
        style       : {
          minWidth: '50%',
        },
      })

      const Preview = () => {
        return Div({
            id,
            className: 'gh-panel email-preview ignore-morph',
            style    : {
              backgroundColor: '#fff',
              overflow       : 'hidden',
            },
          }, [
            Div({
              className: 'space-between has-box-shadow',
              style    : {
                paddingLeft : '20px',
                paddingRight: '10px',
                minHeight   : '62px',
              },
            }, [
              email_id && !getEmail() ? '<h2>Loading...</h2>' : EmailPicker,
              Div({
                className: 'display-flex',
              }, [

                !hasEmail() ? null : Button({
                  id       : `step_${ ID }_edit_email`,
                  className: 'gh-button primary text gap-10 display-flex',
                  onClick  : e => {
                    openEmailEditor(getEmail())
                  },
                }, [
                  Dashicon('edit'),
                  __('Edit'),
                ]),
                email_id ? null : Button({
                  id       : `step_${ ID }_create_email`,
                  className: 'gh-button primary text gap-10 display-flex',
                  onClick  : e => {
                    openEmailEditor({})
                  },
                }, [
                  Dashicon('plus-alt2'),
                  __('Create new email'),
                ]),
                !hasEmail() ? null : Button({
                  id       : `step_${ ID }_email_more`,
                  className: 'gh-button secondary text icon',
                  onClick  : e => {
                    moreMenu(`#step_${ ID }_email_more`, [
                      {
                        key     : 'edit',
                        text    : __('Edit'),
                        onSelect: () => openEmailEditor(getEmail()),
                      },
                      {
                        key     : 'add',
                        text    : __('Create New Email'),
                        onSelect: () => {
                          openEmailEditor({})
                        },
                      },
                    ])
                  },
                }, icons.verticalDots),
              ]),
            ]),
            !email_id ? null : Div({
              className: 'from-preview display-flex gap-20 has-box-shadow',
            }, [
              // Profile pick
              getEmail() ? makeEl('img', {
                src      : getEmail().context.from_avatar,
                className: 'from-avatar',
                height   : 40,
                width    : 40,
                style    : {
                  borderRadius: '50%',
                },
              }) : Div({
                className: 'skeleton-loading',
                style    : {
                  width       : '40px',
                  height      : '40px',
                  borderRadius: '50%',
                },
              }),
              getEmail() ? Div({
                className: 'subject-and-from',
              }, [
                // Subject Line
                `<h2>${ getEmail().data.subject }</h2>`,
                // From Name & Email
                `<span class="from-name">${ getEmail().context.from_name }</span> <span class="from-email">&lt;${ getEmail().context.from_email }&gt;</span>`,
                // From Email
              ]) : Div({
                className: 'skeleton-loading',
                style    : {
                  width   : 'auto',
                  flexGrow: '1',
                  height  : '40px',
                  // borderRadius: '50%',
                },
              }),
            ]),
            !email_id ? null : ( getEmail() ? Iframe({
              id    : `step-${ ID }-preview-${ email_id }`,
              height: 500,
              style : {
                width : '100%',
                height: '500px',
              },
            }, getEmail().context.built) : Div({
              className: 'skeleton-loading',
              style    : {
                height: '460px',
                margin: '20px',
              },
            }) ),
          ],
        )
      }

      morphPreview()
    },
    onDuplicate: ({
      ID,
      data,
      meta,
    }, res, rej) => {

      // Email id was not set
      if (!meta.email_id) {
        res({})
      }

      confirmationModal({
        alert      : `<p>${ __('Do you also want to make a new copy of the email template?', 'groundhogg') }</p>`,
        confirmText: __('Yes, make a copy!', 'groundhogg'),
        closeText  : __('No, use the original.', 'groundhogg'),
        onConfirm  : e => {
          res({
            duplicate_email: true,
          })
        },
        onCancel   : e => {
          res({})
        },
      })

    },
  })

} )(jQuery)
