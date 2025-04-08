( () => {

  const {
    Pg,
    Div,
    ItemPicker,
    Button,
    Span,
    Toggle,
    Label,
    Dashicon,
    ToolTip,
    ProgressBar
  } = MakeEl

  const {
    icons,
  } = Groundhogg.element

  const { ajax } = Groundhogg.api

  const { __, sprintf, _x, _n } = wp.i18n

  const State = Groundhogg.createState({
    flow      : [],
    options   : [],
    index     : 0,
    current   : null,
    simulating: false,
    scrollLog : true,
    contactId : null,
    dry       : true,
  })

  let contactId = localStorage.getItem('gh-simulate-contact-id')

  if (contactId) {
    State.set({
      contactId: parseInt(contactId),
    })
  }
  else {

    // get the contact ID of the current user...
    Groundhogg.stores.contacts.fetchItems({
      users_include: [Groundhogg.user.getCurrentUser().ID],
    }).then(items => {

      if (items.length) {
        State.set({
          contactId: items[0].ID,
        })
      }
    })

  }

  const startTimeline = () => {

    const interval = setInterval(() => {
      if (State.index >= State.flow.length) {
        clearInterval(interval)
        State.set({
          simulating: false,
        })

        // fetch the contact from the API in the event that it's properties or tags were updated.
        Groundhogg.stores.contacts.fetchItem(State.contactId).then(morph)

        morph()
        return
      }

      if (Number.isInteger(State.flow[State.index])) {
        focusStep(State.flow[State.index])
      }

      State.set({
        index: State.index + 1,
      })

      morph()

    }, 500) // 2000ms = 2 seconds

  }

  const simulate = () => {

    State.set({
      simulating: true,
    })

    morph()

    ajax({
      action : 'gh_flow_simulate',
      from   : State.current, // the step ID to start from
      contact: State.contactId, // todo select which contact record
      dry    : State.dry,
    }).then(r => {

      State.set({
        flow   : [
          ...State.flow,
          ...r.flow,
        ],
        options: r.options,
      })

      startTimeline()

    }).catch(err => {
      Groundhogg.element.errorDialog({
        message: err.message,
      })
    })

  }

  const SimStep = (step) => {
    return Div({
      id       : `flow-log-${ step.ID }`,
      className: [
        'flow-log-item',
        step.data.step_group,
        step.data.step_type,
      ].join(' '),
      onClick  : e => {
        focusStep(step.ID)
      },
    }, [

      Div({
        className: 'display-flex gap-10',
      }, [
        Groundhogg.rawStepTypes[step.data.step_type].svg,
        Div({
          className: 'display-flex column',
        }, [
          Span({ className: 'step-title' }, StepTitle(step)),
          Span({ className: 'step-name' }, Groundhogg.rawStepTypes[step.data.step_type].name),
        ]),
      ]),

    ])
  }

  const StepTitle = (step) => {
    return Span({ className: 'step-title' }, document.querySelector(`#step-${ step.ID } .step-title`).innerHTML)
  }

  const ContinueStep = (step) => Div({
    id       : `flow-sim-continue-from-${ step.ID }`,
    className: 'flow-continue-item',
    onClick  : e => {
      State.set({
        current: step.ID,
      })
      State.flow.push('Trigger selected...')
      simulate()
    },
  }, SimStep(step))

  const focusStep = id => document.getElementById(`step-${ id }`).scrollIntoView({
    behavior: 'smooth',
    block   : 'center',
    inline  : 'center',
  })

  const stepToOpt = step => {

    return {
      id  : step.ID,
      text: Span({
        className: `gh-text ${ step.data.step_group }`,
      }, step.data.step_title),
    }
  }

  const handleChangeContact = () => Groundhogg.components.selectContactModal({
    onSelect: contact => {
      localStorage.setItem('gh-simulate-contact-id', contact.ID)
      State.set({
        contactId: contact.ID,
      })
      morph()
    },
  })

  const ContactPreview = () => Div({
    className: 'gh-panel outlined',
  }, [
    // show the selected contact, or select a contact
    State.contactId ? Groundhogg.components.ContactListItem(Groundhogg.stores.contacts.get(State.contactId), {
      extra: item => Div({
        className: 'display-flex flex-end',
      }, [
        Button({
          id       : `contact-edit-${ item.ID }`,
          className: 'gh-button secondary text icon',
          onClick  : e => {
            Groundhogg.components.quickEditContactModal({
              contact: item,
              onEdit : (item) => {
                Groundhogg.element.dialog({
                  message: __('Contact updated!', 'groundhogg'),
                })
                morph()
              },
            })
          },
        }, [
          Dashicon('edit'),
          ToolTip('Quick edit', 'top'),
        ]),
        Button({
          id       : `contact-swap-${ item.ID }`,
          className: 'gh-button secondary text icon',
          onClick  : handleChangeContact,
        }, [
          Dashicon('id'),
          ToolTip('Change contact', 'top'),
        ]),
      ]),
    }) : Button({
      id     : 'select-contact-for-simulator',
      onClick: handleChangeContact,
    }, 'Select a contact'),
  ])

  const FlowSimulator = () => {

    if (State.contactId && !Groundhogg.stores.contacts.has(State.contactId)) {
      Groundhogg.stores.contacts.maybeFetchItem(State.contactId).then(morph)
      return Div({ id: 'flow-simulator' }, [
        Div({
          className: 'skeleton-loading',
        }),
      ])
    }

    // select where to start
    // dry run
    // start the simulation
    // timeline of the simulation
    // prompts to continue at triggers
    // focus, highlight, and center each step as the simulation runs

    return Div({ id: 'flow-simulator' }, [

      Div({
        className: 'start-from display-flex column gap-10',
      }, [

        ContactPreview(),

        Div({
          className: 'display-flex gap-5',
        }, [
          //dry run
          Toggle({
            id      : 'simulate-dry-run',
            checked : State.dry,
            onChange: e => State.set({
              dry: e.target.checked,
            }),
          }),
          Label({
            for: 'simulate-dry-run',
          }, 'Dry run'),
        ]),

        Div({
          className: 'display-flex gap-10',
        }, [

          ItemPicker({
            id          : 'simulate-select-step',
            noneSelected: 'Select a step to start from...',
            multiple    : false,
            selected    : State.current ? [stepToOpt(Funnel.getStep(State.current))] : [],
            fetchOptions: async (search) => {
              let regex = new RegExp(search, 'i')
              return Funnel.steps.filter(item => item.data.step_title.match(regex)).map(stepToOpt)
            },
            onChange    : item => {

              State.set({
                current: item.id,
              })

              focusStep(State.current)

            },
          }),
          Button({
            id       : 'run-simulator',
            className: 'gh-button primary',
            disabled : State.simulating || !State.contactId,
            onClick  : e => {
              State.set({
                index  : 0,
                flow   : [],
                options: [],
              })
              simulate()
            },
          }, State.simulating ? Span({ className: 'gh-spinner' }) : 'Run Simulation'),
        ]),
      ]),
      // State.simulating && State.flow.length? ProgressBar({
      //   percent: (State.index/State.flow.length) * 100
      // }) : null,
      Div({
        id      : 'flow-simulator-log',
        onScroll: e => {
          State.set({
            scrollLog: isAtBottom(e.target),
          })
        },
      }, [
        ...State.flow.slice(0, State.index).map(item => {

          if (Number.isInteger(item)) {
            let step = Funnel.getStep(item)
            if (step) {
              return SimStep(Funnel.getStep(item))
            }
          }

          return Pg({
            className: 'flow-log-item',
          }, item)
        }),
        State.index >= State.flow.length && State.options.length && State.flow.length ? Div({}, [
          Pg({}, 'Select a trigger to continue...'),
          Div({
            className: 'display-flex gap-20',
          }, [
            ...State.options.map(id => ContinueStep(Funnel.getStep(id))),
          ]),

        ]) : null,
        // Div({id:'flow-log-end'}) // for to scroll to the end with the stuff inside.
      ]),
    ])

  }

  function isAtBottom (el) {
    return el.scrollTop + el.clientHeight >= el.scrollHeight - 1
  }

  const morph = () => {
    morphdom(document.querySelector('#flow-simulator'), FlowSimulator(), {
      onNodeAdded: (el) => {
        if (el.matches && el.matches('.flow-log-item') && State.simulating && State.scrollLog) {
          scrollLogToBottom()
        }
      },
    })
  }

  const scrollLogToBottom = () => {
    let log = document.getElementById('flow-simulator-log')
    log.scrollTo({
      top     : log.scrollHeight,
      behavior: 'smooth',
    })
  }

  document.addEventListener('DOMContentLoaded', morph)

  Groundhogg.simulator = {
    state: State,
    FlowSimulator,
    morph,
  }

} )()
