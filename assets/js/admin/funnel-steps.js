( ($) => {

  const {
    select,
    toggle,
    tinymceElement,
    improveTinyMCE,
    andList,
    orList,
    input,
  } = Groundhogg.element

  const { sprintf, __, _x, _n } = wp.i18n

  improveTinyMCE()

  function ordinal_suffix_of (i) {

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
    },
  }

  const capitalize = (string) => {
    return string.charAt(0).toUpperCase() + string.slice(1)
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
        hours: _n('hour', 'hours', delay_amount),
        days: _n('day', 'days', delay_amount),
        weeks: _n('week', 'weeks', delay_amount),
        months: _n('month', 'months', delay_amount),
        years: _n('year', 'years', delay_amount),
      }

      preview.unshift(
        sprintf(_x('Wait at least %s and then', 'wait for a duration', 'groundhogg'),
          `<b>${ delay_amount } ${ delayTypes[delay_type] }</b>`),
      )
    }

    return capitalize(preview.join(' '))
  }

  const DelayTimer = {

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
          <div class="gh-input-group">${ select({
              className: 'delay-input re-render',
              name: 'run_on_month_type',
          }, runOnMonthTypes, run_on_month_type) }
              ${ run_on_month_type === 'specific' ? select({
                  className: 'delay-input select2__picker',
                  name: 'run_on_months',
                  multiple: true,
              }, delay_timer_i18n.months, run_on_months) : '' }
          </div>`

      //language=HTML
      const daysOfWeekOptions = `
          <div class="gh-input-group">${ select({
              className: 'delay-input',
              name: 'run_on_dow_type',
          }, delay_timer_i18n.day_of_week_determiners, run_on_dow_type) }
              ${ select({
                  className: 'select2__picker',
                  name: 'run_on_dow',
                  multiple: true,
              }, delay_timer_i18n.days_of_week, run_on_dow) }
          </div>
          ${ runOnMonthOptions }`

      //language=HTML
      const daysOfMonthOptions = `
          <div>
              ${ select({
                  className: 'select2__picker',
                  name: 'run_on_dom',
                  multiple: true,
              }, runOnDaysOfMonth, run_on_dom) }
          </div>
          ${ runOnMonthOptions }`

      //language=HTML
      return `
          <div class="display-flex column gap-10">
              <h3 class="delay-preview" style="font-weight: normal"></h3>
              <div class="row display-flex column gap-10">
                  <label class="row-label">${ __('Wait at least...', 'groundhogg') }</label>
                  <div class="gh-input-group">
                      ${ input({
                          className: 'delay-input',
                          type: 'number',
                          name: 'delay_amount',
                          value: delay_amount,
                          placeholder: 3,
                          disabled: delay_type === 'none',
                      }) }
                      ${ select({
                          className: 'delay-input re-render',
                          name: 'delay_type',
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
                          name: 'run_on_type',
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
                                  name: 'run_when',
                              },
                              runWhenTypes,
                              run_when,
                      ) }
                      ${
                              run_when === 'later'
                                      ? input({
                                          className: 'delay-input',
                                          type: 'time',
                                          name: 'run_time',
                                          value: run_time,
                                      }) : ''
                      }
                      ${
                              run_when === 'between'
                                      ? [
                                          input({
                                              className: 'delay-input',
                                              type: 'time',
                                              name: 'run_time',
                                              value: run_time,
                                          }),
                                          input({
                                              className: 'delay-input',
                                              type: 'time',
                                              name: 'run_time_to',
                                              value: run_time_to,
                                          }),
                                      ].join('')
                                      : ''
                      }
                  </div>
              </div>
              <div class="display-flex align-center gap-10">
                  <p>${ __('Run in the contact\'s timezone?', 'groundhogg') }</p>
                  ${ toggle({
                      onLabel: 'Yes',
                      offLabel: 'No',
                      id: `${ID}_send_in_timezone`,
                      name: 'send_in_timezone',
                      checked: Boolean(send_in_timezone),
                  }) }
              </div>
          </div>`
    },

    onMount ({ ID, meta }, updateStepMeta, updateStepData, getCurrentState) {
      const updatePreview = () => {

        let preview = delayTimerName({
          ...delayTimerDefaults,
          ...getCurrentState().meta,
        })

        updateStepMeta({
          delay_preview: preview
        })

        $(`#settings-${ID} .delay-preview`).html( preview )
      }

      $(`#${ID}_send_in_timezone`).on('change', (e) => {
        updateStepMeta({
          send_in_timezone: e.target.checked,
        })
      })

      $(`#settings-${ID} .select2__picker`).select2({
        width: 'auto',
      }).on('change', function (e) {
        // console.log(e)
        updateStepMeta({
          [$(this).attr('name')]: $(this).val(),
        })
        updatePreview()
      })

      $(`#settings-${ID} .delay-input`).on('change', ({ target }) => {

        const reRender = target.classList.contains('re-render')

        updateStepMeta({
          [target.name]: $(target).val(),
        }, reRender)

        if (reRender) {
          $(`#settings-${ID} [name=${ target.name }]`).focus()
        }
        else {
          updatePreview()
        }
      }).on('input', function (e) {
        updatePreview()
      })

      updatePreview()
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
          case 'admin_notification':
            this.adminNotification(active)
            break
          case 'delay_timer':
            this.delayTimer(active)
            break
        }
      })
    },

    adminNotification (step) {

      let $customEmail = $('.active .custom-settings input.custom-email')
      let $replyType = $('.active .custom-settings select.reply-to-type')

      $replyType.on('change', e => {

        switch ($replyType.val()) {
          case 'contact':
          case 'owner':
            $customEmail.addClass('hidden')
            break
          case 'custom':
            $customEmail.removeClass('hidden')
            break
        }

      })

      this.applyNote(step)
    },

    applyNote (step) {
      let id = `step_${ step.ID }_note_text`

      wp.editor.remove(id)
      tinymceElement(id, {
        quicktags: false,
      }, (content) => {
        Funnel.updateStepMeta({
          note_text: content,
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

} )(jQuery)
