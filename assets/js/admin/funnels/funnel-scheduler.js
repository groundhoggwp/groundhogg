( function ($) {

  const {
    bold,
    icons,
    dialog,
  } = Groundhogg.element

  const {
    funnels: FunnelsStore,
    searches: SearchesStore,
    contacts: ContactsStore,
  } = Groundhogg.stores

  const { routes, post } = Groundhogg.api
  const { createFilters } = Groundhogg.filters.functions
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Div,
    Button,
    ItemPicker,
    Fragment,
    Input,
    makeEl,
    ButtonToggle,
    Span,
    Dashicon,
  } = MakeEl

  const initialState = {
    step: 'funnel',
    when: 'now',
    steps:[],
    campaigns: [],
    searchMethod: 'filters', // 'filters' or 'search'
    searchMethods: [],
    totalContacts: 0,
    date: moment().format('YYYY-MM-DD'),
    time: moment().add(1, 'hour').format('HH:00:00'),
    finished: false,
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

    // if (getObject() && getObject().data.message_type !== 'transactional') {
    //   query.marketable = true
    // }

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
        morphdom(document.getElementById('funnel-scheduler'), FunnelScheduler())
      }
      catch (e) {
        console.log(e)
      }
    }
  }

  const getFunnel = () => getState().funnel

  const getStep = () => getState().funnelStep

  const Steps = {
    'funnel': {
      name: __('Funnel', 'groundhogg'),
      icon: icons.funnel,
      requirements: () => true,
      render: () => {

        return Fragment([
          Div({
            className: 'display-flex column gap-10',
          }, [
            `<p>${ __('Select which funnel to start...', 'groundhogg') }</p>`,
            ItemPicker({
              id: `select-a-funnel`,
              noneSelected: __('Select a funnel...', 'groundhogg'),
              selected: getFunnel() ? { id: getFunnel().ID, text: getFunnel().data.title } : [],
              multiple: false,
              style: {
                flexGrow: 1,
              },
              fetchOptions: (search) => {
                return FunnelsStore.fetchItems({
                  search,
                  status: 'active',
                }).
                  then(funnels => funnels.map(({ ID, data }) => ( { id: ID, text: data.title } )))
              },
              onChange: item => {
                if (!item) {
                  setState({
                    funnel: null,
                  })
                  return
                }

                let funnel = FunnelsStore.get(item.id)

                setState({
                  funnel,
                  funnelStep: funnel.steps[0]
                })
              },
            }),
            getFunnel() ? ItemPicker({
              id: `select-a-step`,
              noneSelected: __('Select a step...', 'groundhogg'),
              selected: getStep() ? { id: getStep().ID, text: getStep().data.step_title } : [],
              multiple: false,
              style: {
                flexGrow: 1,
              },
              fetchOptions: (search) => {
                return Promise.resolve( getFunnel().steps.map( ({ ID, data }) => ( { id: ID, text: data.step_title } ) ) )
              },
              onChange: item => {
                if (!item) {
                  setState({
                    funnelStep: null,
                  })
                  return
                }

                let funnelStep = getFunnel().steps.find( step => step.ID === item.id )

                setState({
                  funnelStep,
                })
              },
            }) : null,
            getFunnel() && getStep() ? Button({
              id: 'go-to-next',
              className: 'gh-button primary',
              style: {
                alignSelf: 'flex-end',
              },
              onClick: e => {
                setState({
                  step: 'schedule',
                })
              },
            }, sprintf('%s &rarr;', __('Schedule', 'groundhogg'))) : null,
          ]),
        ])
      },
    },
    'schedule': {
      name: __('Schedule', 'groundhogg'),
      icon: Dashicon('calendar'),
      requirements: () => getFunnel(),
      render: () => {

        return Fragment([
          Div({
            className: 'space-between',
          }, [
            `<p>${ __('When do you want the funnel to start?', 'groundhogg') }</p>`,
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
      requirements: () => getFunnel() && getStep() && ( getState().when === 'now' || ( getState().time && getState().date ) ),
      icon: icons.contact,
      render: () => {

        return Fragment([
          `<p>${ __('Select contacts to add to the funnel...', 'groundhogg') }</p>`,
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
            id: 'funnel-include-filters',
            onCreate: el => {
              setTimeout(() => {
                createFilters(
                  '#funnel-include-filters', getState().include_filters, (include_filters) => {
                    setState({
                      include_filters,
                    }, false)
                    updateTotalContacts()
                  }).init()
              })
            },
          }) : null,
          getState().searchMethod === 'filters' ? Div({
            id: 'funnel-exclude-filters',
            onCreate: el => {
              setTimeout(() => {
                createFilters(
                  '#funnel-exclude-filters', getState().exclude_filters, (exclude_filters) => {
                    setState({
                      exclude_filters,
                    }, false)
                    updateTotalContacts()
                  }).init()
              })
            },
          }) : null,
          `<p>${ sprintf(__('%s contacts will be added to the funnel.', 'groundhogg'),
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
      requirements: () => getFunnel() && getStep() && ( getState().when === 'now' || ( getState().time && getState().date ) ) &&
        getState().totalContacts,
      render: () => {

        let preview

        if (getState().when === 'now') {
          preview = sprintf(__('Add %1$s contacts to %2$s at step %3$s <b>now</b>!', 'groundhogg'),
            bold(formatNumber(getState().totalContacts)),
            bold(getFunnel().data.title),
            bold(getStep().data.step_title))
        }
        else {
          preview = sprintf(__('Add %1$s contacts to %2$s at step %3$s on %4$s.', 'groundhogg'),
            bold(formatNumber(getState().totalContacts)),
            bold(getFunnel().data.title),
            bold(getStep().data.step_title),
            formatDateTime(`${ getState().date } ${ getState().time }`))
        }

        return Fragment([
          `<p>${ preview }</p>`,
          Button({
            id: 'confirm-and-schedule',
            className: 'gh-button primary medium',
            onClick: e => {

              e.target.innerHTML = `<span class="gh-spinner"></span>`

              const {
                when = 'now',
                date = '',
                time = '',
              } = getState()

              FunnelsStore.addContacts({
                funnel_id: getFunnel().ID,
                step_id: getStep().ID,
                query: getQuery(),
                now: when === 'now',
                date,
                time,
              }).then( r => {

                setState({
                  step: 'scheduled',
                  finished: true,
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
      icon: Dashicon('yes'),
      requirements: () => getState().finished,
      render: () => {
        return Fragment([
          `<p>${ __('🎉 Your contacts will added to the funnel in the background!', 'groundhogg') }</p>`,
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
          }, sprintf('&larr; %s', __('Start over', 'groundhogg'))),
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

  const FunnelScheduler = () => {

    const order = ['funnel', 'schedule', 'contacts', 'review', 'scheduled']

    return Div({
      id: 'funnel-scheduler',
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
      getSteps()[getState().step].render({ getState, getFunnel, getStep, setState, getQuery }),
    ])
  }

  Groundhogg.FunnelScheduler = (newState = {}) => {

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

    return FunnelScheduler()
  }

} )(jQuery)
