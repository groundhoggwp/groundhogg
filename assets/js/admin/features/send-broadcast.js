( function ($) {

  const {
    modal,
    select,
    stepNav,
    stepNavHandler,
    input,
    errorDialog,
    setFrameContent,
    progressBar,
    tooltip,
    loadingDots,
    adminPageURL,
    bold,
    toggle,
    icons,
    dialog,
  } = Groundhogg.element
  const { emailPicker, searchesPicker } = Groundhogg.pickers
  const {
    emails: EmailsStore,
    searches: SearchesStore,
    contacts: ContactsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores
  const { routes, post } = Groundhogg.api
  const { createFilters } = Groundhogg.filters.functions
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n

  const SendBroadcast = (selector, {
    email = false,
    ...rest
  } = {}, {
    onScheduled = () => {},
  }) => {

    let state = {
      email_id: email ? email.ID : false,
      when: 'now',
      which: 'filters',
      date: moment().format('YYYY-MM-DD'),
      time: '09:00:00',
      query: {},
      total_contacts: 0,
      ...rest,
    }

    const setState = (newState) => {
      state = {
        ...state,
        ...newState,
      }

      console.log(state)
    }

    const elPrefix = 'gh-broadcast'

    if (email) {
      EmailsStore.itemsFetched([email])
    }

    const preview = () => {

      // language=HTML
      return `
          <div class="gh-row">
              <div class="gh-col">
                  <iframe id="${ elPrefix }-email-preview"></iframe>
              </div>
          </div>`
    }

    const showFrame = () => {
      if (state.email_id) {
        setFrameContent($(`#${ elPrefix }-email-preview`)[0], EmailsStore.get(state.email_id).context.built)
      }
    }

    const step1 = () => {

      // language=HTML
      return `
          <div class="gh-rows-and-columns">
              <div class="gh-row">
                  <div class="gh-col">
                      <label
                              for="${ elPrefix }-email"><b>${ __('Which email do you want to send?', 'groundhogg') }</b></label>
                      ${ select({
                          name: 'email',
                          id: `${ elPrefix }-email`,
                          options: [
                              { text: '', value: '' },
                              ...EmailsStore.getItems().map(e => ( { text: e.data.title, value: e.ID } )),
                          ],
                          selected: state.email_id,
                      }) }
                  </div>
              </div>
              ${ state.email_id ? preview() : '' }
              <div class="gh-row">
                  <div class="gh-col">
                      <button class="gh-next-step gh-button primary" ${ state.email_id ? '' : 'disabled' }>
                          ${ __('Next', 'groundhogg') } &rarr;
                      </button>
                  </div>
              </div>
          </div>
      `
    }

    const step2 = () => {

      const laterSettings = () => {
        // language=HTML
        return `
            <div class="gh-row">
                <div class="gh-col">
                    <label for="${ elPrefix }-date"><b>${ __('Set the date and time...', 'groundhogg') }</b></label>
                    <div class="gh-input-group">
                        ${ input({
                            id: `${ elPrefix }-date`,
                            type: 'date',
                            name: 'date',
                            value: state.date,
                            min: moment().format('YYYY-MM-DD'),
                        }) }
                        ${ input({
                            id: `${ elPrefix }-time`,
                            type: 'time',
                            name: 'time',
                            value: state.time,
                        }) }
                    </div>
                </div>
            </div>
            <div class="gh-row">
                <div class="gh-col">
                    <label>${ __('Send in the contact\'s local time?', 'groundhogg') } ${ toggle({
                        onLabel: __('Yes'),
                        offLabel: __('No'),
                        id: `${ elPrefix }-local-time`,
                        name: 'send_in_local_time',
                        checked: state.send_in_local_time,
                    }) }</label>
                </div>
            </div>`
      }

      // language=HTML
      return `
          <div class="gh-rows-and-columns">
              <div class="gh-row">
                  <div class="gh-col">
                      <label
                              for="${ elPrefix }-when"><b>${ __('When should this email be sent?',
                              'groundhogg') }</b></label>
                      <div class="gh-radio-group">
                          <label>${ input({
                              type: 'radio',
                              className: 'change-when',
                              name: 'gh_send_when',
                              value: 'now',
                              checked: state.when === 'now',
                          }) } ${ __('Now', 'groundhogg') }</label>
                          <label>${ input({
                              type: 'radio',
                              name: 'gh_send_when',
                              className: 'change-when',
                              value: 'later',
                              checked: state.when === 'later',
                          }) } ${ __('Later', 'groundhogg') }</label>
                      </div>

                  </div>
              </div>
              ${ state.when === 'later' ? laterSettings() : '' }
              <div class="gh-row">
                  <div class="gh-col">
                      <button class="gh-next-step gh-button primary"
                              ${ state.when === 'later' && ( !state.date || !state.time ) ? 'disabled' : '' }>
                          ${ __('Next', 'groundhogg') } &rarr;
                      </button>
                  </div>
              </div>`
    }

    const step3 = () => {

      const { total_contacts } = state

      const totalAndNext = () => {
        //language=HTML
        return `
            <div class="gh-row">
                <div class="gh-col">
                    <div id="${ elPrefix }-total-contacts">
                        <p>
                            ${ sprintf(_n('Send to %s contact', 'Send to %s contacts', total_contacts, 'groundhogg'),
                                    bold(formatNumber(total_contacts))) }
                        </p>
                    </div>
                </div>
            </div>
            <div class="gh-row">
                <div class="gh-col">
                    <button class="gh-next-step gh-button primary" ${ total_contacts ? '' : 'disabled' }>
                        ${ __('Next', 'groundhogg') }
                        &rarr;
                    </button>
                </div>
            </div>`
        // language=HTML
      }

      if (state.which === 'from_table') {
        return `<div class="gh-rows-and-columns">${ totalAndNext() }</div>`
      }

      // language=HTML
      return `
          <div class="gh-rows-and-columns">
              <div class="gh-row">
                  <div class="gh-col">
                      <label
                              for="${ elPrefix }-search-which"><b>${ __('Select contacts to receive this email...',
                              'groundhogg') }</b></label>
                      <div class="gh-radio-group">
                          <label>${ input({
                              type: 'radio',
                              className: 'change-search-which',
                              name: 'gh_send_search_which',
                              value: 'filters',
                              checked: state.which === 'filters',
                          }) } ${ __('Search for Contacts', 'groundhogg') }</label>
                          <label>${ input({
                              type: 'radio',
                              name: 'gh_send_search_which',
                              className: 'change-search-which',
                              value: 'searches',
                              checked: state.which === 'searches',
                          }) } ${ __('Use a Saved Search', 'groundhogg') }</label>
                      </div>
                  </div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <div id="${ elPrefix }-search-method">
                          ${ state.which === 'searches' ? select({
                              id: `${ elPrefix }-search-method-searches`,
                              name: 'searches',
                          }) : '' }
                      </div>
                  </div>
              </div>
              ${ totalAndNext() }
          </div>`
    }

    const step4 = () => {

      const email = EmailsStore.get(state.email_id)

      const {
        total_contacts,
        date,
        time,
      } = state

      let review = state.when === 'later'
        ? _n('Send %1$s to %2$s contact on %3$s', 'Send %1$s to %2$s contacts on %3$s', total_contacts, 'groundhogg')
        : _n('Send %1$s to %2$s contact <b>immediately</b>.', 'Send %1$s to %2$s contacts <b>immediately</b>',
          total_contacts, 'groundhogg')

      // language=HTML
      return `
          <div class="gh-rows-and-columns">
              <div class="gh-row">
                  <div class="gh-col">
              <span class="gh-text md">
                  ${ sprintf(review, bold(email.data.title), bold(formatNumber(total_contacts)),
                          state.when === 'later' ? bold(formatDateTime(date + ' ' + time)) : '') }
              </span>
                  </div>
              </div>
              ${ state.email_id ? preview() : '' }
              <div class="gh-row">
                  <div class="gh-col">
                      <button id="${ elPrefix }-confirm" class="gh-button primary">
                          ${ state.when === 'later' ? __('Confirm and Schedule', 'groundhogg') : __('Confirm and Send',
                                  'groundhogg') }
                      </button>
                  </div>
              </div>
          </div>`
    }

    $(selector).html(`<div id="gh-send-broadcast-form"></div>`)

    const {
      $el,
      nextStep,
      lastStep,
      setStep,
    } = stepNavHandler('#gh-send-broadcast-form', {
      currentStep: 0,
      steps: [
        step1,
        step2,
        step3,
        step4,
      ],
      showNav: true,
      labels: [
        __('Email', 'groundhogg'),
        __('Schedule', 'groundhogg'),
        __('Contacts', 'groundhogg'),
        __('Review', 'groundhogg'),
      ],
      onStepChange: (step, {
        nextStep,
        lastStep,
        setStep,
      }) => {

        switch (step) {
          case 0:

            emailPicker(`#${ elPrefix }-email`, false, (items) => {EmailsStore.itemsFetched(items)}, {
              status: 'ready',
            }, {
              placeholder: 'Select an email to send...',
            }).on('change', ({ target }) => {
              setState({
                email_id: parseInt(target.value),
              })
              setStep(0)

              // $('.gh-next-step').prop('disabled', !state.email_id)

              showFrame()
            })

            showFrame()

            break
          case 1:
            $('.change-when').on('change', ({ target }) => {
              setState({
                when: $(target).val(),
              })
              setStep(1)
            })

            const updateButton = () => {

              const isValid = state.when === 'now' || moment().isBefore(`${ state.date } ${ state.time }`)

              $('.gh-next-step').prop('disabled', !isValid)
            }

            $(`#${ elPrefix }-date`).on('change', ({ target }) => {
              setState({ date: target.value })
              updateButton()
            })

            $(`#${ elPrefix }-time`).on('change', ({ target }) => {
              setState({ time: target.value })
              updateButton()
            })

            $(`#${ elPrefix }-local-time`).on('change', ({ target }) => {
              setState({ send_in_local_time: target.checked })
            })

            break
          case 2:

            const updateTotal = () => {

              const query = {
                ...state.query,
              }

              if (EmailsStore.get(state.email_id).meta.message_type === 'marketing') {
                query.marketable = 'yes'
              }

              ContactsStore.count(query).then(total => {
                $(`#${ elPrefix }-total-contacts`).
                  html(`<p>${ sprintf(_n('Send to %s contact', 'Send to %s contacts', total, 'groundhogg'),
                    bold(formatNumber(total))) }</p>`)
                $('.gh-next-step').prop('disabled', total === 0)
                setState({
                  total_contacts: total,
                })
              })
            }

            $('.change-search-which').on('change', ({ target }) => {
              setState({
                which: $(target).val(),
                query: {},
              })
              setStep(2)
            })

            if (state.which === 'filters') {
              createFilters(`#${ elPrefix }-search-method`, state.query.filters, (filters) => {
                setState({
                  query: {
                    filters,
                  },
                })
                updateTotal()
              }).mount()
            }
            else {

              SearchesStore.fetchItems().then(() => {

                $(`#${ elPrefix }-search-method-searches`).select2({
                  placeholder: __('Select a saved search...', 'groundhogg'),
                  data: [
                    { id: '', text: '' },
                    ...SearchesStore.getItems().map(s => ( { id: s.id, text: s.name } )),
                  ],
                }).on('select2:select', ({ target }) => {
                  setState({
                    query: {
                      saved_search: $(target).val(),
                    },
                  })
                  updateTotal()
                })

              })
            }

            updateTotal()

            break

          case 3:

            showFrame()

            $(`#${ elPrefix }-confirm`).on('click', ({ currentTarget }) => {

              $(currentTarget).prop('disabled', true)

              const {
                query = {},
                total_contacts = 0,
                when = 'now',
                date = '',
                time = '',
                send_in_local_time = false,
              } = state

              post(routes.v4.broadcasts, {
                object_id: state.email_id,
                object_type: 'email',
                query,
                date,
                time,
                send_now: when === 'now',
                send_in_local_time,
              }).then(r => r.item).then(b => {

                const scheduling = () => {
                  // language=HTML
                  return `
                      <h2 id="broadcast-progress-header">${ __('Scheduling', 'groundhogg') }</h2>
                      <p class="pill orange"><b>${ __('Do not close this window while the broadcast is scheduling!',
                              'groundhogg') }</b></p>
                      <div id="broadcast-progress"></div>`
                }

                $('#gh-send-broadcast-form').html(scheduling())

                const { stop: stopDots } = loadingDots('#broadcast-progress-header')
                const { setProgress } = progressBar('#broadcast-progress')

                const schedule = () => {
                  post(`${ routes.v4.broadcasts }/${ b.ID }/schedule`).then(({ finished, scheduled }) => {
                    setProgress(scheduled / total_contacts)
                    if (!finished) {
                      schedule()
                    }
                    else {
                      setTimeout(() => {
                        stopDots()
                        dialog({
                          message: __('Broadcast scheduled!', 'groundhogg'),
                        })

                        onScheduled()

                      }, 500)
                    }
                  }).catch(() => {
                    errorDialog({
                      message: __('Something went wrong...', 'groundhogg'),
                    })
                  })
                }

                schedule()
              }).catch(() => {
                errorDialog({
                  message: __('Something went wrong...', 'groundhogg'),
                })
                setStep(3)
              })

            })

            break
        }

        $('.gh-next-step').on('click', nextStep)
      },
    })

  }

  Groundhogg.SendBroadcast = SendBroadcast

  $(() => {

    $('#gh-schedule-broadcast').on('click', (e) => {
      e.preventDefault()
      Modal({}, () => Groundhogg.BroadcastScheduler())
    })

    if (typeof GroundhoggNewBroadcast !== 'undefined') {
      document.getElementById('gh-broadcast-form-inline').append( Groundhogg.BroadcastScheduler({
        object: GroundhoggNewBroadcast.email,
        onScheduled: () => {
          window.location.href = adminPageURL('gh_broadcasts', { status: 'scheduled' })
        },
      }))
    }
  })

  const {
    Div,
    Button,
    Modal,
    Textarea,
    ItemPicker,
    Fragment,
    Input,
    Iframe,
    makeEl,
    ButtonToggle,
    Span,
    Toggle,
    Dashicon,
  } = MakeEl

  const initialState = {
    step: 'object',
    steps: [],
    object: null,
    when: 'later',
    campaigns: [],
    searchMethod: 'filters', // 'filters' or 'search'
    searchMethods: [],
    totalContacts: 0,
    date: moment().format('YYYY-MM-DD'),
    time: moment().add(1, 'hour').format('HH:00:00'),
    broadcast: null,
  }

  const getSearchMethods = () => {
    return [
      ...getState().searchMethods ?? [],
      {
        id: 'filters',
        text: __('Search for contacts using filters.', 'groundhogg'),
        query: () => ( {
          filters: getState().include_filters,
          exclude_filters: getState().exclude_filters,
        } ),
      },
      {
        id: 'all-contacts',
        text: __('All contacts.', 'groundhogg'),
        query: () => ( {} ),
      },
      {
        id: 'all-my-contacts',
        text: __('All contacts assigned to me.', 'groundhogg'),
        query: () => ( {
          owner_id: Groundhogg.currentUser.ID,
        } ),
      },
      {
        id: 'confirmed-contacts',
        text: __('All confirmed contacts.', 'groundhogg'),
        query: () => ( {
          optin_status: 2,
        } ),
      },
      ...SearchesStore.getItems().map(({ id, name }) => ( {
        id,
        text: sprintf(__('Saved search %s', 'groundhogg'), bold(name)),
        query: () => ( {
          saved_search: id,
        } ),
      } )),
    ]
  }

  let State = {
    ...initialState,
  }

  const getQuery = () => {
    let query = {}

    const {
      searchMethod = 'filters',
    } = getState()

    query = getSearchMethods().find(({ id }) => id === searchMethod).query()

    if (getObject() && getObject().data.message_type !== 'transactional') {
      query.marketable = 'yes'
    }

    return query
  }

  /**
   * Update the total contact size
   *
   * @returns {Promise<T>}
   */
  const updateTotalContacts = (morph = true) => {
    return ContactsStore.count(getQuery()).then(total => {
      setState({
        totalContacts: total,
      }, morph)
    })
  }

  const getState = () => State
  const setState = (newState, morph = true) => {
    State = {
      ...State,
      ...newState,
    }

    if (morph) {
      try {
        morphdom(document.getElementById('broadcast-scheduler'), BroadcastScheduler())
      }
      catch (e) {
        console.log(e)
      }
    }
  }

  const getObject = () => getState().object

  /**
   *
   * @return {*}
   * @constructor
   */
  const FromPreview = () => Div({
    className: 'from-preview display-flex gap-20 has-box-shadow',
  }, [
    // Profile pick
    makeEl('img', {
      src: getObject().context.from_avatar,
      className: 'from-avatar',
      height: 40,
      width: 40,
      style: {
        borderRadius: '50%',
      },
    }),
    Div({
      className: 'subject-and-from',
    }, [
      // Subject Line
      `<h2>${ getObject().data.subject }</h2>`,
      // From Name & Email
      `<span class="from-name">${ getObject().context.from_name }</span> <span class="from-email">&lt;${ getObject().context.from_email }&gt;</span>`,
      // From Email
    ]),
  ])

  /**
   *
   * @return {*}
   * @constructor
   */
  const EmailPreview = () => {
    return Div({
      className: 'email-preview display-flex column',
      style: {
        overflow: 'hidden',
        border: '1px solid #ccc',
        borderRadius: '5px',
      },
    }, [
      FromPreview(),
      Iframe({
        id: `broadcast-email-preview`,
        height: 400,
        style: {
          width: '100%',
        },
      }, getObject().context.built),
    ])
  }

  const Steps = {
    'object': {
      name: __('Email', 'groundhogg'),
      icon: icons.email,
      requirements: () => true,
      render: () => {

        return Fragment([
          Div({
            className: 'display-flex column gap-10',
          }, [
            `<p>${ __('Select an email to send...', 'groundhogg') }</p>`,
            Div({
              className: 'display-flex gap-10',
            }, [
              ItemPicker({
                id: `broadcast-select-email`,
                noneSelected: __('Select an email to send...', 'groundhogg'),
                selected: getObject() ? { id: getObject().ID, text: getObject().data.title } : [],
                multiple: false,
                style: {
                  flexGrow: 1,
                },
                fetchOptions: (search) => {
                  return EmailsStore.fetchItems({
                    search,
                    status: 'ready',
                  }).
                    then(emails => emails.map(({ ID, data }) => ( { id: ID, text: data.title } )))
                },
                onChange: item => {
                  if (!item) {
                    setState({
                      object: null,
                    })
                    return
                  }

                  let email = EmailsStore.get(item.id)

                  setState({
                    object: email,
                    campaigns: email.campaigns,
                  })
                },
              }),
              getObject() ? Button({
                id: 'go-to-campaigns',
                className: 'gh-button primary',
                style: {
                  alignSelf: 'flex-end',
                },
                onClick: e => {
                  setState({
                    step: 'campaigns',
                  })
                },
              }, sprintf('%s &rarr;', __('Campaigns', 'groundhogg'))) : null,
            ]),
          ]),
          getObject() ? EmailPreview() : null,
        ])
      },
    },
    'campaigns': {
      name: __('Campaigns', 'groundhogg'),
      requirements: () => getObject(),
      icon: Dashicon('flag'),
      render: () => {
        return Fragment([
          `<p>${ __('Use campaigns to organize your broadcasts! Select one or more campaigns...', 'groundhogg') }</p>`,
          ItemPicker({
            id: 'broadcast-campaigns',
            noneSelected: __('Select a campaign...', 'groundhogg'),
            tags: true,
            selected: getState().campaigns.map(({ ID, data }) => ( { id: ID, text: data.name } )),
            fetchOptions: async (search) => {
              let campaigns = await CampaignsStore.fetchItems({
                search,
                limit: 20,
              })

              return campaigns.map(({ ID, data }) => ( { id: ID, text: data.name } ))
            },
            createOption: async (id) => {
              let campaign = await CampaignsStore.create({
                data: {
                  name: id,
                },
              })

              return { id: campaign.ID, text: campaign.data.name }
            },
            onChange: items => setState({
              campaigns: items.map(({ id }) => CampaignsStore.get(id)),
            }),
          }),
          Button({
            id: 'go-to-schedule',
            className: 'gh-button primary',
            style: {
              alignSelf: 'flex-end',
            },
            onClick: e => {
              setState({
                step: 'schedule',
              })
            },
          }, sprintf('%s &rarr;', __('Schedule', 'groundhogg'))),
        ])
      },
    },
    'schedule': {
      name: __('Schedule', 'groundhogg'),
      icon: Dashicon('calendar'),
      requirements: () => getObject(),
      render: () => {

        return Fragment([
          Div({
            className: 'space-between',
          }, [
            `<p>${ __('When do you want the broadcast to go out?', 'groundhogg') }</p>`,
            ButtonToggle({
              id: 'send-when',
              options: [
                { id: 'later', text: 'Later' },
                { id: 'now', text: 'Now' },
              ],
              selected: getState().when,
              onChange: when => setState({ when }),
            }),
          ]),
          getState().when === 'later' ? Div({
            className: 'gh-input-group',
          }, [
            Input({
              type: 'date',
              id: 'send-date',
              name: 'date',
              value: getState().date || '',
              min: moment().format('YYYY-MM-DD'),
              onChange: e => setState({
                date: e.target.value,
              }),
            }),
            Input({
              type: 'time',
              id: 'send-time',
              name: 'time',
              value: getState().time || '',
              onChange: e => setState({
                time: e.target.value,
              }),
            }),
          ]) : null,
          getState().when === 'later' ? Div({
            className: 'display-flex gap-10 align-center',
          }, [
            `<p>${ __('Send in the contact\'s local time?', 'groundhogg') }</p>`,
            Toggle({
              id: 'send-in-local',
              checked: getState().send_in_local_time,
              onChange: e => setState({
                send_in_local_time: e.target.checked,
              }),
            }),
          ]) : null,
          Button({
            id: 'go-to-contacts',
            className: 'gh-button primary',
            disabled: getState().when === 'later' && moment().isAfter(`${ getState().date } ${ getState().time }`),
            style: {
              alignSelf: 'flex-end',
            },
            onClick: e => {
              setState({
                step: 'contacts',
              })
            },
          }, sprintf('%s &rarr;', __('Contacts', 'groundhogg'))),
        ])
      },
    },
    'contacts': {
      name: __('Contacts', 'groundhogg'),
      requirements: () => getObject() && ( getState().when === 'now' || ( getState().time && getState().date ) ),
      icon: icons.contact,
      render: () => {

        return Fragment([
          `<p>${ __('Select contacts to receive this broadcast...', 'groundhogg') }</p>`,
          ItemPicker({
            id: 'select-search-method',
            multiple: false,
            selected: getSearchMethods().find(({ id }) => id === getState().searchMethod),
            fetchOptions: async search => {
              return getSearchMethods().filter(({ text }) => text.match(new RegExp(search, 'i')))
            },
            onChange: (item) => {

              if (!item) {
                setState({
                  searchMethod: 'filters',
                })
                updateTotalContacts()
                return
              }

              let { id } = item

              setState({
                searchMethod: id,
              })

              updateTotalContacts()
            },
          }),
          getState().searchMethod === 'filters' ? Div({
            id: 'broadcast-include-filters',
            onCreate: el => {
              setTimeout(() => {
                createFilters(
                  '#broadcast-include-filters', getState().include_filters, (include_filters) => {
                    setState({
                      include_filters,
                    }, false)
                    updateTotalContacts()
                  }).init()
              })
            },
          }) : null,
          getState().searchMethod === 'filters' ? Div({
            id: 'broadcast-exclude-filters',
            onCreate: el => {
              setTimeout(() => {
                createFilters(
                  '#broadcast-exclude-filters', getState().exclude_filters, (exclude_filters) => {
                    setState({
                      exclude_filters,
                    }, false)
                    updateTotalContacts()
                  }).init()
              })
            },
          }) : null,
          `<p>${ sprintf(__('%s contacts will receive this broadcast.', 'groundhogg'),
            formatNumber(getState().totalContacts)) }</p>`,
          Button({
            id: 'go-to-review',
            className: 'gh-button primary',
            disabled: !getState().totalContacts,
            style: {
              alignSelf: 'flex-end',
            },
            onClick: e => {
              setState({
                step: 'review',
              })
            },
          }, sprintf('%s &rarr;', __('Review', 'groundhogg'))),
        ])
      },
    },
    'review': {
      name: 'Review',
      icon: Dashicon('thumbs-up'),
      requirements: () => getObject() && ( getState().when === 'now' || ( getState().time && getState().date ) ) &&
        getState().totalContacts,
      render: () => {

        let preview

        if (getState().when === 'now') {
          preview = sprintf(__('Send %1$s to %2$s contacts <b>now</b>!', 'groundhogg'), bold(getObject().data.title),
            bold(formatNumber(
              getState().totalContacts)))
        }
        else {
          preview = sprintf(__('Send %1$s to %2$s contacts on %3$s.', 'groundhogg'), bold(getObject().data.title),
            bold(formatNumber(
              getState().totalContacts)), formatDateTime(`${ getState().date } ${ getState().time }`))
        }

        return Fragment([
          `<p>${ preview }</p>`,
          getObject() ? EmailPreview() : null,
          Button({
            id: 'confirm-and-schedule',
            className: 'gh-button primary medium',
            onClick: e => {

              e.target.innerHTML = `<span class="gh-spinner"></span>`

              const {
                when = 'now',
                date = '',
                time = '',
                send_in_local_time = false,
                campaigns = [],
              } = getState()

              post(routes.v4.broadcasts, {
                object_id: getObject().ID,
                object_type: 'email',
                query: getQuery(),
                date,
                time,
                send_now: when === 'now',
                send_in_local_time,
                campaigns: campaigns.map(({ ID }) => ID),
              }).then(r => {

                setState({
                  step: 'scheduled',
                  broadcast: r.item,
                })

              }).catch(err => {
                dialog({
                  message: err.message,
                  type: 'error',
                })

                console.log(err)

                switch (err.code) {
                  case 'invalid_date':
                    setState({
                      step: 'schedule',
                    })
                    break
                  default:
                    setState({})
                    break
                }
              })

            },
          }, __('Confirm and schedule!', 'groundhogg')),
        ])

      },
    },
    'scheduled': {
      name: __('Scheduled'),
      icon: Dashicon('megaphone'),
      requirements: () => getState().broadcast,
      render: () => {
        return Fragment([
          `<p>${ __('🎉 Your broadcast is being scheduled in the background!', 'groundhogg') }</p>`,
          // `<p>${__('', 'groundhogg')}</p>`,
          Button({
            id: 're-schedule',
            className: 'gh-button primary',
            style: {
              alignSelf: 'flex-start',
            },
            onClick: e => {
              setState({
                ...initialState,
              })
            },
          }, sprintf('&larr; %s', __('Schedule another broadcast', 'groundhogg'))),
        ])
      },
    },
  }

  /**
   *
   * Facilitates the merging of step overrides
   *
   * @returns {{}}
   */
  const getSteps = () => {

    const merged = {}
    const overrides = getState().steps

    for (let step in Steps) {

      if (overrides.hasOwnProperty(step)) {
        merged[step] = {
          ...Steps[step],
          ...overrides[step],
        }
      }
      else {
        merged[step] = Steps[step]
      }
    }

    return merged
  }

  const BroadcastScheduler = () => {

    const order = ['object', 'campaigns', 'schedule', 'contacts', 'review', 'scheduled']

    return Div({
      id: 'broadcast-scheduler',
      className: 'display-flex column gap-10',
      style: {
        width: '500px',
        maxWidth: '100%',
      },
    }, [
      getState().step !== 'scheduled' ? Div({
        className: 'gh-step-nav',
        style: {
          marginBottom: '20px',
        },
      }, [
        ...order.map(step => Button({
          id: `select-${ step }`,
          className: `gh-button icon ${ getState().step === step ? 'primary' : 'secondary' }`,
          disabled: !getSteps()[step].requirements(),
          onClick: e => {
            setState({
              step,
            })
          },
        }, getSteps()[step].icon)).reduce((steps, step, i) => {

          if (i > 0) {
            steps.push(makeEl('hr', { className: 'gh-step-nav-join' }))
          }

          steps.push(step)

          return steps
        }, []),
      ]) : null,
      getSteps()[getState().step].render({ getState, getObject, setState, getQuery }),
    ])
  }

  Groundhogg.BroadcastScheduler = (newState = {}) => {

    // Preload searches
    SearchesStore.maybeFetchItems()

    setState({
      ...initialState,
      ...newState,
    }, false)

    // Preload the contact count
    if (getState().searchMethod !== 'filters') {
      updateTotalContacts(false)
    }

    return BroadcastScheduler()
  }

} )(jQuery)
