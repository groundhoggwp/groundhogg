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
  const {
    emailPicker,
    searchesPicker,
  } = Groundhogg.pickers
  const {
    emails   : EmailsStore,
    searches : SearchesStore,
    contacts : ContactsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores
  const {
    routes,
    post,
  } = Groundhogg.api
  const { createFilters } = Groundhogg.filters.functions
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
    Label,
    Span,
    Toggle,
    Dashicon,
  } = MakeEl

  const initialState = {
    step         : 'object',
    steps        : [],
    object       : null,
    when         : 'later',
    campaigns    : [],
    searchMethod : 'filters', // 'filters' or 'search'
    searchMethods: [],
    totalContacts: 0,
    date         : moment().format('YYYY-MM-DD'),
    time         : moment().add(1, 'hour').format('HH:00:00'),
    broadcast    : null,
    segment_type : 'fixed',
  }

  const getSearchMethods = () => {
    return [
      ...getState().searchMethods ?? [],
      {
        id   : 'filters',
        text : __('Search for contacts using filters.', 'groundhogg'),
        query: () => ( {
          filters        : getState().include_filters,
          exclude_filters: getState().exclude_filters,
        } ),
      },
      {
        id   : 'all-contacts',
        text : __('All contacts.', 'groundhogg'),
        query: () => ( {} ),
      },
      {
        id   : 'all-my-contacts',
        text : __('All contacts assigned to me.', 'groundhogg'),
        query: () => ( {
          owner_id: Groundhogg.currentUser.ID,
        } ),
      },
      {
        id   : 'confirmed-contacts',
        text : __('All confirmed contacts.', 'groundhogg'),
        query: () => ( {
          optin_status: 2,
        } ),
      },
      ...SearchesStore.getItems().
        map(({
          id,
          name,
        }) => ( {
          id,
          text : sprintf(__('Saved search %s', 'groundhogg'), bold(name)),
          query: () => ( {
            saved_search: id,
          } ),
        } )),
    ]
  }

  const State = Groundhogg.createState({ ...initialState })

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

    State.set({
      ...newState,
    })

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
      src      : getObject().context.from_avatar,
      className: 'from-avatar',
      height   : 40,
      width    : 40,
      style    : {
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
      style    : {
        overflow    : 'hidden',
        border      : '1px solid #ccc',
        borderRadius: '5px',
      },
    }, [
      FromPreview(),
      Iframe({
        id    : `broadcast-email-preview`,
        height: 400,
        style : {
          width: '100%',
        },
      }, getObject().context.built),
    ])
  }

  const Steps = {
    'object'   : {
      name        : __('Email', 'groundhogg'),
      icon        : icons.email,
      requirements: () => true,
      render      : () => {

        return Fragment([
          Div({
            className: 'display-flex column gap-10',
          }, [
            `<p>${ __('Select an email to send...', 'groundhogg') }</p>`,
            Div({
              className: 'display-flex gap-10',
            }, [
              ItemPicker({
                id          : `broadcast-select-email`,
                noneSelected: __('Select an email to send...', 'groundhogg'),
                selected    : getObject() ? {
                  id  : getObject().ID,
                  text: getObject().data.title,
                } : [],
                multiple    : false,
                style       : {
                  flexGrow: 1,
                },
                fetchOptions: (search) => {
                  return EmailsStore.fetchItems({
                      search,
                      status: 'ready',
                    }).
                    then(emails => emails.map(({
                      ID,
                      data,
                    }) => ( {
                      id  : ID,
                      text: data.title,
                    } )))
                },
                onChange    : item => {
                  if (!item) {
                    setState({
                      object: null,
                    })
                    return
                  }

                  let email = EmailsStore.get(item.id)

                  setState({
                    object   : email,
                    campaigns: email.campaigns,
                  })
                },
              }),
              getObject() ? Button({
                id       : 'go-to-campaigns',
                className: 'gh-button primary',
                style    : {
                  alignSelf: 'flex-end',
                },
                onClick  : e => {
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
      name        : __('Campaigns', 'groundhogg'),
      requirements: () => getObject(),
      icon        : Dashicon('flag'),
      render      : () => {
        return Fragment([
          `<p>${ __('Use campaigns to organize your broadcasts! Select one or more campaigns...', 'groundhogg') }</p>`,
          ItemPicker({
            id          : 'broadcast-campaigns',
            noneSelected: __('Select a campaign...', 'groundhogg'),
            tags        : true,
            selected    : getState().
              campaigns.
              map(({
                ID,
                data,
              }) => ( {
                id  : ID,
                text: data.name,
              } )),
            fetchOptions: async (search) => {
              let campaigns = await CampaignsStore.fetchItems({
                search,
                limit: 20,
              })

              return campaigns.map(({
                ID,
                data,
              }) => ( {
                id  : ID,
                text: data.name,
              } ))
            },
            createOption: async (id) => {
              let campaign = await CampaignsStore.create({
                data: {
                  name: id,
                },
              })

              return {
                id  : campaign.ID,
                text: campaign.data.name,
              }
            },
            onChange    : items => setState({
              campaigns: items.map(({ id }) => CampaignsStore.get(id)),
            }),
          }),
          Button({
            id       : 'go-to-schedule',
            className: 'gh-button primary',
            style    : {
              alignSelf: 'flex-end',
            },
            onClick  : e => {
              setState({
                step: 'schedule',
              })
            },
          }, sprintf('%s &rarr;', __('Schedule', 'groundhogg'))),
        ])
      },
    },
    'schedule' : {
      name        : __('Schedule', 'groundhogg'),
      icon        : Dashicon('calendar'),
      requirements: () => getObject(),
      render      : () => {

        return Fragment([
          Div({
            className: 'space-between',
          }, [
            `<p>${ __('When do you want the broadcast to go out?', 'groundhogg') }</p>`,
            ButtonToggle({
              id      : 'send-when',
              options : [
                {
                  id  : 'later',
                  text: 'Later',
                },
                {
                  id  : 'now',
                  text: 'Now',
                },
              ],
              selected: getState().when,
              onChange: when => setState({ when }),
            }),
          ]),
          getState().when === 'later' ? Div({
            className: 'gh-input-group',
          }, [
            Input({
              type    : 'date',
              id      : 'send-date',
              name    : 'date',
              value   : getState().date || '',
              min     : moment().format('YYYY-MM-DD'),
              onChange: e => setState({
                date: e.target.value,
              }),
            }),
            Input({
              type    : 'time',
              id      : 'send-time',
              name    : 'time',
              value   : getState().time || '',
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
              id      : 'send-in-local',
              checked : getState().send_in_local_time,
              onChange: e => setState({
                send_in_local_time: e.target.checked,
              }),
            }),
          ]) : null,
          Button({
            id       : 'go-to-contacts',
            className: 'gh-button primary',
            disabled : getState().when === 'later' && moment().isAfter(`${ getState().date } ${ getState().time }`),
            style    : {
              alignSelf: 'flex-end',
            },
            onClick  : e => {
              setState({
                step: 'contacts',
              })
            },
          }, sprintf('%s &rarr;', __('Contacts', 'groundhogg'))),
        ])
      },
    },
    'contacts' : {
      name        : __('Contacts', 'groundhogg'),
      requirements: () => getObject() && ( getState().when === 'now' || ( getState().time && getState().date ) ),
      icon        : icons.contact,
      render      : () => {

        return Fragment([
          `<p>${ __('Select contacts to receive this broadcast...', 'groundhogg') }</p>`,
          ItemPicker({
            id          : 'select-search-method',
            multiple    : false,
            selected    : getSearchMethods().find(({ id }) => id === getState().searchMethod),
            fetchOptions: async search => {
              return getSearchMethods().filter(({ text }) => text.match(new RegExp(search, 'i')))
            },
            onChange    : (item) => {

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
            id      : 'broadcast-include-filters',
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
            id      : 'broadcast-exclude-filters',
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
          `<div style="font-size: 14px">${ sprintf(__('%s contacts will receive this broadcast.', 'groundhogg'),
            bold(formatNumber(getState().totalContacts))) }</div>`,
          State.when === 'later' ? Fragment([
            Div({
              className: 'display-flex column gap-5',
            }, [
              `<p>Which contacts should be included at the time of sending?</p>`,
              Label({
                style: {
                  fontsize: '14px'
                }
              }, [
                Input({
                  type: 'radio',
                  name: 'segment_type',
                  checked: State.segment_type === 'fixed',
                  onChange: e => {
                    if ( e.target.checked ){
                      State.set({
                        segment_type: 'fixed'
                      })
                    }
                  }

                }),
                '<b>Fixed Segment:</b> <i>Contacts</i> currently <i>within the segment</i>.'
              ] ),
              Label({
                style: {
                  fontsize: '14px'
                }
              }, [
                Input({
                  type: 'radio',
                  name: 'segment_type',
                  checked: State.segment_type === 'dynamic',
                  onChange: e => {
                    if ( e.target.checked ){
                      State.set({
                        segment_type: 'dynamic'
                      })
                    }
                  }
                }),
                '<b>Dynamic Segment:</b> <i>Contacts within the segment</i> at the time of sending.'
              ] ),
            ]),
          ]) : null,
          // State.when === 'later' ? makeEl('i', {}, [
          //   State.segment_type  === 'fixed' ? "Contacts <b>currently</b> within this segment." : "Contacts within the segment <b>at the time of sending</b>."
          // ] ) : null,
          Button({
            id       : 'go-to-review',
            className: 'gh-button primary',
            disabled : !getState().totalContacts,
            style    : {
              alignSelf: 'flex-end',
            },
            onClick  : e => {
              setState({
                step: 'review',
              })
            },
          }, sprintf('%s &rarr;', __('Review', 'groundhogg'))),
        ])
      },
    },
    'review'   : {
      name        : 'Review',
      icon        : Dashicon('thumbs-up'),
      requirements: () => getObject() && ( getState().when === 'now' || ( getState().time && getState().date ) ) &&
        getState().totalContacts,
      render      : () => {

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
            id       : 'confirm-and-schedule',
            className: 'gh-button primary medium',
            onClick  : e => {

              e.target.innerHTML = `<span class="gh-spinner"></span>`

              const {
                when = 'now',
                date = '',
                time = '',
                send_in_local_time = false,
                campaigns = [],
                segment_type = 'fixed',
              } = getState()

              post(routes.v4.broadcasts, {
                object_id  : getObject().ID,
                object_type: 'email',
                query      : getQuery(),
                date,
                time,
                send_now   : when === 'now',
                send_in_local_time,
                campaigns  : campaigns.map(({ ID }) => ID),
                segment_type,
              }).then(r => {

                setState({
                  step     : 'scheduled',
                  broadcast: r.item,
                })

              }).catch(err => {
                dialog({
                  message: err.message,
                  type   : 'error',
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
      name        : __('Scheduled'),
      icon        : Dashicon('megaphone'),
      requirements: () => getState().broadcast,
      render      : () => {
        return Fragment([
          `<p>${ __('🎉 Your broadcast is being scheduled in the background!', 'groundhogg') }</p>`,
          // `<p>${__('', 'groundhogg')}</p>`,
          Button({
            id       : 're-schedule',
            className: 'gh-button primary',
            style    : {
              alignSelf: 'flex-start',
            },
            onClick  : e => {
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

    const order = [
      'object',
      'campaigns',
      'schedule',
      'contacts',
      'review',
      'scheduled',
    ]

    return Div({
      id       : 'broadcast-scheduler',
      className: 'display-flex column gap-10',
      style    : {
        width   : '500px',
        maxWidth: '100%',
      },
    }, [
      getState().step !== 'scheduled' ? Div({
        className: 'gh-step-nav',
        style    : {
          marginBottom: '20px',
        },
      }, [
        ...order.map(step => Button({
          id       : `select-${ step }`,
          className: `gh-button icon ${ getState().step === step ? 'primary' : 'secondary' }`,
          disabled : !getSteps()[step].requirements(),
          onClick  : e => {
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
      getSteps()[getState().step].render({
        getState,
        getObject,
        setState,
        getQuery,
      }),
    ])
  }

  Groundhogg.BroadcastScheduler = (newState = {}) => {

    // Preload searches
    SearchesStore.maybeFetchItems()

    State.reset()

    setState({
      ...newState,
    }, false)

    // Preload the contact count
    if (getState().searchMethod !== 'filters') {
      updateTotalContacts(false)
    }

    return BroadcastScheduler()
  }

  // backwards compatibility support
  Groundhogg.SendBroadcast = (selector, {
    email = false,
    ...rest
  } = {}, {
    onScheduled = () => {},
  }) => {
    document.querySelector(selector).append(Groundhogg.BroadcastScheduler({
      object: email,
      onScheduled,
    }))
  }

  $(() => {

    $('#gh-schedule-broadcast').on('click', (e) => {
      e.preventDefault()
      Modal({}, () => Groundhogg.BroadcastScheduler())
    })

    if (typeof GroundhoggNewBroadcast !== 'undefined') {
      document.getElementById('gh-broadcast-form-inline').append(Groundhogg.BroadcastScheduler({
        object     : GroundhoggNewBroadcast.email,
        onScheduled: () => {
          window.location.href = adminPageURL('gh_broadcasts', { status: 'scheduled' })
        },
      }))
    }
  })

} )(jQuery)
