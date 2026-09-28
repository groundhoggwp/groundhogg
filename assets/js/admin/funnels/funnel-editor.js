( function ($) {

  const {
    patch,
    routes,
    ajax,
  } = Groundhogg.api

  const {
    funnels  : FunnelsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores

  const {
    Div,
    Pg,
    Span,
    H3,
    Img,
    An,
    Button,
    Dashicon,
    ToolTip,
    Modal,
    ModalFrame,
    Textarea,
    ItemPicker,
    Input,
    Select,
  } = MakeEl

  const {
    icons,
    uuid,
    moreMenu,
    tooltip,
    dialog,
    dangerConfirmationModal,
    confirmationModal,
    adminPageURL,
    loadingModal,
    modal,
    escHTML,
  } = Groundhogg.element

  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n

  const getFunnel = () => FunnelsStore.get(Funnel.id)

  const {
    FlowStore,
    FlowCanvas,
  } = Groundhogg

  const flowCanvas = FlowCanvas.createCanvas({
    h          : MakeEl.makeEl,
    stepTypes  : Groundhogg.rawStepTypes,
    defaultIcon: `${ Groundhogg.assets.images }funnel-icons/no-icon.png`,
  })

  /**
   * Draw a flow's steps into #step-sortable from a store
   *
   * @param store Object see FlowStore.createStore()
   * @param editing bool
   * @param reporting bool
   * @param debug bool
   * @param previewOf function see flow-canvas.js
   */
  const drawCanvas = ({
    store,
    editing = true,
    reporting = false,
    debug = false,
    previewOf = () => null,
  }) => {
    morphdom(document.getElementById('step-sortable'), Div({}, flowCanvas.render({
      store,
      editing,
      reporting,
      debug,
      previewOf,
    })), {
      childrenOnly     : true,
      onBeforeElUpdated: function (fromEl, toEl) {

        // preserve the editing class
        if (fromEl.classList.contains('editing')) {
          toEl.classList.add('editing')
        }

        return true
      },
    })
  }

  /**
   * Draw a flow that isn't being edited, like on the reporting page
   *
   * @param steps Object[]
   * @param canvas Object see Funnel::get_canvas_data()
   * @param reporting bool whether to add the places the flow report puts each step's stats
   */
  Groundhogg.drawFlow = ({
    steps = [],
    canvas = {},
  }, { reporting = false } = {}) => {
    drawCanvas({
      store  : FlowStore.createStore({
        steps,
        canvas,
      }),
      editing: false,
      reporting,
    })
    drawLogicLines()
  }

  /**
   * The plain text title of a step in the flow
   *
   * @param stepId int
   * @return string
   */
  const getStepTitle = stepId => document.querySelector(`#step-${ stepId } .step-title, #step-${ stepId } .title`)?.textContent.trim() ?? `#${ stepId }`

  /**
   * Steps other steps point at in their settings can't be deleted, explain which steps use them
   *
   * @param blocked {{stepId: int, by: {ID: int, title: string}[]}[]} the steps that can't be deleted, and the steps using them
   */
  const cantDeleteUsedSteps = blocked => {
    Modal({
      width: '500px',
    }, ({ close }) => Div({
      className: 'display-flex column gap-20',
    }, [
      Div({ className: 'gh-header modal-header' }, [
        H3({}, __('This step is being used', 'groundhogg')),
        Button({
          className: 'gh-button icon secondary text',
          onClick  : close,
        }, Dashicon('no-alt')),
      ]),
      Pg({}, __('Other steps point at it in their settings, so it can\'t be deleted. Change those steps first.', 'groundhogg')),
      ...blocked.map(({
        stepId,
        by,
      }) => Pg({}, sprintf(__('%1$s is used by %2$s', 'groundhogg'), `<b>${ escHTML(getStepTitle(stepId)) }</b>`,
        by.map(ref => `<b>${ escHTML(ref.title) }</b>`).join(', '))),
      ),
      Div({ className: 'display-flex flex-end' }, [
        Button({
          className: 'gh-button primary',
          onClick  : close,
        }, __('OK', 'groundhogg')),
      ]),
    ]))
  }

  /**
   * Ask what to do with contacts waiting at deleted steps before the changes go live
   *
   * @param pendingDeletes {{steps: Object[], targets: Object[]}} deleted steps with waiting contacts, and actions they can be moved to
   * @param confirmText string the label of the button that was clicked, like Publish Changes or Activate
   * @param onConfirm function receives the choices, step ID => { action, to }
   */
  const confirmDeletedSteps = (pendingDeletes, confirmText, onConfirm) => {

    const {
      steps = [],
      targets = [],
    } = pendingDeletes ?? {}

    if (!steps.length) {
      onConfirm({})
      return
    }

    // cancel by default
    const choices = Object.fromEntries(steps.map(step => [
      step.ID, {
        action: 'cancel',
        to    : step.next || targets[0]?.ID || 0,
      },
    ]))

    Modal({
      width: '500px',
    }, ({
      close,
      morph,
    }) => Div({
      className: 'display-flex column gap-20',
    }, [
      Div({ className: 'gh-header modal-header' }, [
        H3({}, __('Contacts are waiting at deleted steps', 'groundhogg')),
        Button({
          className: 'gh-button icon secondary text',
          onClick  : close,
        }, Dashicon('no-alt')),
      ]),
      Pg({}, __('Choose what happens to them when your changes go live.', 'groundhogg')),
      ...steps.map(step => Div({
        className: 'display-flex column gap-10',
      }, [
        Pg({}, sprintf(_n('%1$s contact is waiting at %2$s', '%1$s contacts are waiting at %2$s', step.contacts, 'groundhogg'),
          `<b>${ step.contacts.toLocaleString() }</b>`, `<b>${ escHTML(step.title) }</b>`)),
        Div({
          className: 'display-flex gap-10',
        }, [
          Select({
            id      : `deleted-step-action-${ step.ID }`,
            options : [
              {
                value: 'cancel',
                text : __('Cancel their events', 'groundhogg'),
              },
              ...( targets.length ? [
                {
                  value: 'move',
                  text : __('Move them to...', 'groundhogg'),
                },
              ] : [] ),
            ],
            selected: choices[step.ID].action,
            onChange: e => {
              choices[step.ID].action = e.target.value
              morph()
            },
          }),
          choices[step.ID].action === 'move' ? Select({
            id      : `deleted-step-target-${ step.ID }`,
            options : targets.map(target => ( {
              value: target.ID,
              text : escHTML(target.title),
            } )),
            selected: choices[step.ID].to,
            onChange: e => {
              choices[step.ID].to = parseInt(e.target.value)
            },
          }) : null,
        ]),
      ])),
      Div({
        className: 'display-flex flex-end gap-10',
      }, [
        Button({
          className: 'gh-button secondary text',
          onClick  : close,
        }, __('Cancel', 'groundhogg')),
        Button({
          id       : 'confirm-deleted-steps',
          className: 'gh-button primary',
          onClick  : e => {
            close()
            onConfirm(choices)
          },
        }, confirmText),
      ]),
    ]))
  }

  if (typeof Funnel !== 'undefined' && Funnel.is_editor) {

    FunnelsStore.itemsFetched([Funnel])

    const syncReplacementCodes = () => {

      let flowReplacements = getFunnel().meta.replacements || {}

      // remove all replacements under this_flow from the replacements object
      // re-add replacements direct from meta

      // Filter out keys in Groundhogg.replacements that start with "this_flow"
      Groundhogg.replacements.codes = Object.entries(Groundhogg.replacements.codes).reduce((acc, [key, value]) => {
        if (value.group !== 'this_flow') {
          acc[key] = value
        }
        return acc
      }, {})

      Groundhogg.replacements.groups.this_flow = 'This Flow'

      for (const [key, value] of Object.entries(flowReplacements)) {
        Groundhogg.replacements.codes[`__this_flow_${ key }`] = {
          code  : `this_flow.${ key }`,
          desc  : '',
          name  : key,
          group : 'this_flow',
          insert: `{this_flow.${ key }}`,
        }
      }
    }

    const createPlaceholderEl = (data) => {

      let {
        step_group,
        step_type,
      } = data

      let placeholder = Div({
        className: `step step-placeholder ${ step_group } ${ step_type }`,
      }, [
        Input({
          type : 'hidden',
          name : 'step_ids[]',
          value: JSON.stringify(data),
        }),
        Div({ className: 'hndle' }, [
          // icon
          Div({ className: 'hndle-icon' }, Groundhogg.rawStepTypes[step_type].svg),
          Div({}, [
            // title,
            Span({ className: 'step-title loading-dots' }, _x('Loading', 'as in waiting to for something to load', 'groundhogg')),
            // name
            Span({ className: 'step-name' }, Groundhogg.rawStepTypes[step_type].name),
          ]),
        ]),
      ])

      if (step_group !== 'benchmark') {
        placeholder = Div({ className: `sortable-item ${ step_group } ${ step_type }` }, [
          Div({}), // for space
          Div({ className: 'flow-line' }),
          placeholder,
          Div({ className: 'flow-line' }),
        ])
      }

      return placeholder

    }

    /**
     * A step ID from the DOM, steps that aren't saved yet have a temporary one
     */
    const toId = id => FlowStore.isTempId(id) ? id : parseInt(id)

    /**
     * The step a .sortable-item on the canvas is for, the first or last trigger of an OR group
     *
     * @param item Element
     * @param last bool
     * @return string|undefined
     */
    const stepIdOf = (item, last = false) => {

      if (item.matches('.sortable-item.benchmarks')) {
        const triggers = item.querySelectorAll(':scope > .step-branch.benchmarks > .sortable-item.benchmark > .step')
        return triggers[last ? triggers.length - 1 : 0]?.dataset.id
      }

      return item.querySelector('.step[data-id]')?.dataset.id
    }

    /**
     * The steps a dragged .sortable-item moves, every trigger of an OR group
     *
     * @param item Element
     * @return Array
     */
    const stepIdsOf = item => {

      if (item.matches('.sortable-item.benchmarks')) {
        return [...item.querySelectorAll(':scope > .step-branch.benchmarks > .sortable-item.benchmark > .step')].map(step => toId(step.dataset.id))
      }

      const id = stepIdOf(item)

      return id ? [toId(id)] : []
    }

    /**
     * Where an element dropped on the canvas is, as an edit-flow position
     *
     * @param el Element in a .step-branch
     * @return Object
     */
    const domPosition = el => {

      const sibling = direction => {
        let item = el[direction]
        while (item && !( item.matches('.sortable-item') && stepIdOf(item) )) {
          item = item[direction]
        }
        return item
      }

      const prev = sibling('previousElementSibling')

      if (prev) {
        return { after: toId(stepIdOf(prev, true)) }
      }

      const next = sibling('nextElementSibling')

      if (next) {
        return { before: toId(stepIdOf(next)) }
      }

      return FlowStore.branchPosition(el.parentElement.closest('.step-branch').dataset.branch, 'start')
    }

    /**
     * Where an add button on the canvas adds steps, as an edit-flow position, see flow-canvas.js for the buttons
     *
     * @param button Element
     * @return Object|null
     */
    const addButtonPosition = button => {

      const id = button?.id ?? ''
      let match

      if (id === 'end-funnel') {
        return {
          branch  : 'main',
          position: 'end',
        }
      }

      if (( match = id.match(/^before-group-(.+)$/) ) || ( match = id.match(/^before-(.+)$/) )) {
        return { before: toId(match[1]) }
      }

      if (( match = id.match(/^add-to-group-after-(.+)$/) )) {
        return { after: toId(match[1]) }
      }

      if (( match = id.match(/^end-inside-(.+)$/) ) || ( match = id.match(/^in-branch-(.+)$/) )) {
        return FlowStore.branchPosition(match[1], 'end')
      }

      return null
    }

    /**
     * A trigger's flag, a checkbox named like the rest of its settings, so it's posted with them
     */
    const TriggerToggle = ({
      step,
      flag,
      label,
      yesNo = false,
    }) => Div({ className: 'display-flex align-center gap-5' }, [
      MakeEl.Label({ for: `step_${ step.ID }_${ flag }` }, label),
      MakeEl.Label({ className: 'gh-switch' }, [
        Input({
          type   : 'checkbox',
          id     : `step_${ step.ID }_${ flag }`,
          name   : `steps[${ step.ID }][${ flag }]`,
          value  : 1,
          checked: Boolean(parseInt(step.data[flag] ?? 0)),
        }),
        Span({ className: 'slider' }),
        Span({ className: 'on' }, yesNo ? __('Yes', 'groundhogg') : __('On', 'groundhogg')),
        Span({ className: 'off' }, yesNo ? __('No', 'groundhogg') : __('Off', 'groundhogg')),
      ]),
    ])

    const TriggerSettings = step => Div({ className: 'gh-panel benchmark-settings' }, [
      Div({ className: 'gh-panel-header' }, MakeEl.H2({}, __('Trigger Settings', 'groundhogg'))),
      Div({ className: 'inside display-flex gap-20 column' }, [
        step.is_starting ? null : TriggerToggle({
          step,
          flag : 'is_entry',
          label: __('Allow contacts to enter the flow at this step?', 'groundhogg'),
          yesNo: true,
        }),
        step.is_starting ? null : TriggerToggle({
          step,
          flag : 'can_passthru',
          label: __('Allow contacts to pass through this trigger', 'groundhogg'),
          yesNo: true,
        }),
        TriggerToggle({
          step,
          flag : 'is_conversion',
          label: __('Track conversion when triggered', 'groundhogg'),
        }),
        // see TriggerFrequencySettings()
        Div({
          id       : `trigger-frequency-settings-${ step.ID }`,
          className: 'ignore-morph',
        }),
      ]),
    ])

    /**
     * A step's settings panel. The step type's part, its island, is HTML from the server, see
     * Funnel_Step::get_settings_island(), and is only replaced when the server sends a new one.
     *
     * @param step Object
     */
    const SettingsPanel = step => {

      const canvas = Funnel.store.getCanvas(step.ID) ?? {}
      const island = Funnel.islands[step.ID]
      const drawn = Funnel.drawnIslands[step.ID] === island && document.getElementById(`settings-${ step.ID }`)

      const {
        step_type,
        step_group,
      } = step.data

      return Div({
        id       : `settings-${ step.ID }`,
        dataId   : step.ID,
        dataType : step_type,
        className: `step ${ step_group } ${ step_type } settings ${ canvas.locked ? 'locked' : '' }`,
      }, [
        Div({ className: 'step-locked' }, Dashicon('lock')),
        Div({ className: 'step-warnings' }, ( canvas.errors ?? [] ).map(error => Div({
          className    : 'notice notice-warning is-dismissible',
          dataErrorCode: error.code,
        }, `<p>${ error.message }</p>`))),
        Div({ className: 'step-flex' }, [
          Div({
            className : `step-edit panels ${ island?.ignore_morph ? 'ignore-morph' : '' }`,
            dataIsland: drawn ? 'same' : 'new',
          }, drawn ? '' : island?.html ?? `<p class="loading-dots">${ _x('Loading', 'as in waiting to for something to load', 'groundhogg') }</p>`),
          Div({ className: 'step-notes' }, [
            island?.before_notes || null,
            step_group === 'benchmark' ? TriggerSettings(step) : null,
            Textarea({
              id         : `step_${ step.ID }_step-notes`,
              name       : 'step_notes',
              className  : 'step-notes-textarea full-width',
              rows       : 7,
              value      : step.meta.step_notes ?? '',
              placeholder: __('You can use this area to store custom notes about the step. Accepts HTML and basic markdown.', 'groundhogg'),
            }),
          ]),
        ]),
      ])
    }

    /**
     * The undo and redo buttons, over Funnel.history
     */
    const UndoRedoManager = {

      morph () {
        let el = document.getElementById('undo-and-redo')
        if (el) {
          morphdom(el, UndoRedo())
        }
      },

      canUndo () {
        return Funnel.history.canUndo()
      },

      canRedo () {
        return Funnel.history.canRedo()
      },

      undo () {
        Funnel.undo()
      },

      redo () {
        Funnel.redo()
      },

      clear () {
        Funnel.history.clear()
        this.morph()
      },

    }

    const UndoRedo = () => Div({
        className: 'gh-input-group',
        id       : 'undo-and-redo',
      },
      [
        Button({
            id       : 'editor-undo',
            className: 'gh-button secondary text icon',
            disabled : !UndoRedoManager.canUndo(),
            type: 'button',
            onClick  : e => {
              UndoRedoManager.undo()
            },
          },
          [
            Dashicon('undo'),
            ToolTip('Undo', 'bottom'),
          ]),
        Button({
            id       : 'editor-redo',
            className: 'gh-button secondary text icon',
            type: 'button',
            disabled : !UndoRedoManager.canRedo(),
            onClick  : e => {
              UndoRedoManager.redo()
            },
          },
          [
            Dashicon('redo'),
            ToolTip('Redo', 'bottom'),
          ]),
      ])

    const FlowSettings = () => {

      const State = Groundhogg.createState({
        saving: false,
      })

      let funnel = getFunnel()

      let {
        description = '',
        replacements = {},
      } = funnel.meta
      let { campaigns = [] } = funnel
      CampaignsStore.itemsFetched( campaigns )
      let campaignIds = campaigns.map(c => c.ID)

      return MakeEl.Div({
        id: 'flow-settings',
      }, morph => {

        return Div({
          className: 'display-flex gap-20 column',
        }, [
          Div({
            className: 'gh-panel',
          }, [
            Div({
              className: 'gh-panel-header',
            }, [
              MakeEl.H2({}, 'General Settings'),
            ]),
            Div({
              className: 'inside',
            }, [
              `<p>Add a simple description.</p>`,
              Textarea({
                id       : 'funnel-description',
                className: 'full-width',
                onInput  : e => {
                  description = e.target.value
                },
                value    : description,
              }),
            ]),
          ]),
          Div({
            className: 'gh-panel',
          }, [
            Div({
              className: 'gh-panel-header',
            }, [
              MakeEl.H2({}, 'Campaigns'),
            ]),
            Div({
              className: 'inside',
            }, [
              `<p>Use <b>campaigns</b> to organize your flows. Use terms like <code>Black Friday</code> or <code>Sales</code>.</p>`,
              ItemPicker({
                id          : 'pick-campaigns',
                noneSelected: 'Add a campaign...',
                selected    : campaigns.map(({
                  ID,
                  data,
                }) => ( {
                  id  : ID,
                  text: data.name,
                } )),
                tags        : true,
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
                createOption: async value => {
                  let campaign = await CampaignsStore.create({
                    data: {
                      name: value,
                    },
                  })

                  return {
                    id  : campaign.ID,
                    text: campaign.data.name,
                  }
                },
                onChange    : items => {
                  campaignIds = items.map(item => item.id)
                  campaigns = campaignIds.map(id => CampaignsStore.get(id))
                },
              }),
            ]),
          ]),
          Div({
            className: 'gh-panel',
          }, [
            Div({
              className: 'gh-panel-header',
            }, [
              MakeEl.H2({}, 'Flow Replacements'),
            ]),
            Div({
              className: 'inside',
            }, [
              `<p>${ __(
                'Define custom replacements that are only used in the context of this flow. Usage is <code>{this_flow.replacement_key}</code>.') }</p>`,
              MakeEl.InputRepeater({
                id      : 'flow-replacements-editor',
                rows    : Object.entries(replacements),
                cells   : [
                  props => Input({
                    ...props,
                    placeholder: _x( 'Key', 'as in a metadata field key', 'groundhogg' ),
                  }),
                  props => Input({
                    ...props,
                    placeholder: _x( 'Value', 'as in a field value', 'groundhogg' ),
                  }),

                ],
                onChange: rows => {
                  replacements = {}
                  rows.forEach(([key, val]) => replacements[key] = val)
                },
              }),
            ]),
          ]),
          Div({
            className: 'display-flex flex-end',
          }, Button({
            id       : 'save-settings',
            className: 'gh-button primary',
            disabled : State.saving,
            onClick  : async e => {

              State.set({
                saving: true,
              })

              morph()

              await FunnelsStore.patch(getFunnel().ID, {
                campaigns: campaignIds,
                meta     : {
                  description,
                  replacements,
                },
              })

              State.set({
                saving: false,
              })

              morph()

              syncReplacementCodes()

              dialog({
                message: 'Changes saved!',
              })
            },
          }, State.saving ? '<span class="gh-spinner"></span>' : 'Save Settings')),
        ])
      })
    }

    const morphSettings = () => morphdom(document.getElementById('flow-settings'), FlowSettings())

    $.extend(Funnel, {

      // the canvas is drawn from this, edits change it first and are then sent to the server, see flow-store.js
      store: FlowStore.createStore({
        steps    : Funnel.steps,
        canvas   : Funnel.canvas,
        stepTypes: Groundhogg.rawStepTypes,
        defaults : type => Funnel.stepTypes[type]?.defaults,
      }),

      // step types' JS, see registerStepType()
      stepTypes: {},

      /**
       * Give a step type JS that draws its settings and shows its title, warnings, and branches right away.
       * Everything is optional, what's left out comes from the server. The server's title and warnings replace the
       * ones shown here once a change is saved.
       *
       * @param type string
       * @param handler Object
       *   settings( step, update ) the settings, drawn where the step type's PHP settings() would be, call
       *                            update( { setting: value } ) to change them
       *   title( step )            the title, or undefined to keep the last one
       *   validate( step )         warnings, [ { code, message } ]
       *   branches( step )         a logic step's branches, [ { key, name, classes } ]
       *   defaults                 the settings new steps start with
       *   onDuplicate( step )      extra fields to post when the step is duplicated, or a promise of them
       */
      registerStepType (type, handler) {
        this.stepTypes[type] = handler
      },

      /**
       * What a step type's JS shows instead of the server's while a step's change is being saved
       *
       * @param step Object
       * @return Object|null
       */
      previewOf (step) {

        const handler = this.stepTypes[step.data.step_type]

        if (!handler || !( FlowStore.isTempId(step.ID) || this.hasPendingSettings(step.ID) )) {
          return null
        }

        const preview = {}

        try {
          if (handler.title) {
            // an empty title isn't used, like on the server
            preview.title = handler.title(step) || undefined
          }
          if (handler.validate) {
            preview.errors = handler.validate(step)
          }
          if (handler.branches) {
            preview.branches = handler.branches(step)
          }
        }
        catch (err) {
          console.warn(err)
        }

        return preview
      },

      /**
       * Change a step's settings from its type's JS, see registerStepType()
       *
       * @param stepId
       * @param patch Object setting => value
       */
      updateSettings (stepId, patch) {
        this.updateStepMeta(patch, stepId)
      },

      /**
       * Draw the settings of step types that have JS for them, where their PHP settings() would be
       */
      mountSettings () {
        document.querySelectorAll('.step-settings .step-type-settings:not([data-mounted])').forEach(el => {

          const step = this.getStep(el.closest('.step.settings')?.dataset.id)
          const handler = this.stepTypes[step?.data.step_type]

          if (!handler?.settings) {
            return
          }

          el.dataset.mounted = '1'

          try {
            el.replaceChildren(handler.settings(step, patch => this.updateSettings(step.ID, patch)))
          }
          catch (err) {
            console.error(err)
          }
        })
      },

      history: FlowStore.createHistory(),

      /**
       * Redraw the canvas from the store
       */
      drawCanvas () {
        drawCanvas({
          store    : this.store,
          debug    : Boolean(this.debug),
          previewOf: step => this.previewOf(step),
        })

        // the add button that's highlighted
        if (this.addEl) {
          this.addEl = document.getElementById(this.addEl.id)
          this.addEl?.classList.add('here')
        }
      },

      /**
       * Redraw the canvas and what depends on it
       */
      redraw () {

        if (!this.dragging) {
          this.drawCanvas()
          this.makeSortable()
        }

        drawLogicLines()
        UndoRedoManager.morph()
      },

      /**
       * Make a change, in groundhogg/edit-flow's operations: apply it to the store, redraw, and save it in the background
       *
       * @param operations Object[]
       */
      perform (operations) {

        const applied = []
        const undo = []

        try {
          operations.forEach(operation => {
            undo.unshift(...this.store.apply(operation))
            applied.push(operation)
          })
        }
        catch (err) {
          dialog({
            message: err.message,
            type   : 'error',
          })
        }

        if (applied.length) {
          this.history.record({
            undo,
            redo: applied,
          })
          this.queue.push(applied)
        }

        this.redraw()
      },

      /**
       * Apply operations from the undo history
       *
       * @param operations Object[]
       */
      replay (operations) {

        try {
          operations.forEach(operation => this.store.apply(operation))
        }
        catch (err) {
          // like a step it refers to being changed since
          this.history.clear()
          dialog({
            message: err.message,
            type   : 'error',
          })
          this.redraw()
          return
        }

        // the panels show what was put back, even the one being edited
        this.forcePanels = true

        this.queue.push(operations)
        this.redraw()
      },

      undo () {
        this.replay(this.history.undo())
      },

      redo () {
        this.replay(this.history.redo())
      },

      /**
       * Send operations to the server, see Funnels_Page::ajax_flow_operations()
       *
       * @param operations Object[]
       * @return Promise
       */
      sendOperations (operations) {

        $('body').addClass('auto-saving')

        return ajax({
          action    : 'gh_flow_operations',
          funnel    : this.id,
          revision  : this.revision,
          operations: JSON.stringify(operations),
        })
      },

      /**
       * The server applied the operations
       *
       * @param data Object
       */
      operationsSaved (data, operations = []) {

        const ids = data.ids ?? {}

        Object.assign(this.realIds, ids)

        this.history.rewriteIds(ids)
        this.store.rewriteIds(ids)

        this.conflicts = 0

        const saved = operations.filter(operation => operation.op === 'update' && operation.form).map(operation => operation.step)

        this.applyState(data, {
          loaded: () => this.recordSettingsChanges(saved),
        })

        $('body').removeClass('auto-saving')
      },

      /**
       * The server refused the operations, none of them were applied
       *
       * @param data Object the error's code and message, and the state to go back to
       * @param operations Object[]
       */
      operationsRefused (data, operations) {

        $('body').removeClass('auto-saving')

        // changed somewhere else, like another tab, so make the changes again on top of that
        if (data.code === 'flow_changed' && data.state && ( this.conflicts = ( this.conflicts ?? 0 ) + 1 ) < 3) {
          this.queue.outbox.unshift(...operations)
          this.applyState(data.state)
          this.queue.flush()
          return
        }

        // the history can have changes that were refused
        UndoRedoManager.clear()

        operations.filter(operation => operation.form).forEach(operation => delete this.settingsBefore[operation.step])

        if (data.state) {
          this.forcePanels = true
          this.applyState(data.state)
        }

        dialog({
          message: data.message ?? __('Something went wrong updating the flow. Your changes could not be saved.', 'groundhogg'),
          type   : 'error',
        })
      },

      /**
       * A request didn't get through, it's retried
       */
      operationsRetrying (error, attempt) {
        console.warn(error)
        document.getElementById('last-saved-text').innerHTML = __('Changes not saved yet, retrying...', 'groundhogg')
      },

      /**
       * Load what the server sent after a save, see Funnels_Page::get_editor_state()
       *
       * @param data Object
       * @param quiet bool
       * @param shouldMorphSettings bool
       * @param loaded function called once the server's steps are loaded, before edits made since are applied again
       */
      applyState (data, {
        quiet = true,
        shouldMorphSettings = true,
        loaded = () => {},
      } = {}) {

        // make sure the status is available to the parent funnel form element
        document.getElementById('funnel-form').dataset.status = data.funnel.data.status

        this.pending_deletes = data.pending_deletes
        this.step_references = data.step_references
        this.revision = data.revision

        Object.assign(this.islands, data.islands ?? {})

        this.store.load({
          steps : data.funnel.steps,
          canvas: data.canvas,
        })

        // the order and levels are derived in JS too, catch it drifting from Funnel::set_step_levels()
        if (this.debug) {
          const differences = this.store.levelDifferences()
          if (differences.length) {
            console.warn('The flow store derives different step levels than the server', differences)
          }
        }

        loaded()

        // edits made since these were sent
        this.queue.outbox.forEach(operation => {
          try {
            this.store.apply(operation)
          }
          catch (err) {
            console.warn(err)
          }
        })

        this.redraw()

        const force = this.forcePanels
        this.forcePanels = false

        if (shouldMorphSettings || force) {
          this.drawPanels({
            quiet,
            force,
          })
        }

        // what was put back is in the panel being edited, set up its settings again
        if (force) {
          this.stepSettingsCallbacks()
        }

        // publish button is enabled when there's something to publish
        document.getElementById('funnel-update').disabled = !data.has_changes

        this.lastSaved = new Date()
        this.updateLastSaved()

        if (quiet) {
          $(document).trigger('auto-save')
          $(document).trigger('gh-init-pickers') // re-init pickers that would have been removed
        }
      },

      // the step types' parts of the settings panels, step ID => { html, before_notes, ignore_morph }
      islands: Funnel.islands ?? {},

      // the islands in the panels now, step ID => island
      drawnIslands: {},

      // settings being changed that aren't sent yet, step ID => { meta, morph, timer }
      pendingSettings: {},

      // the steps before their settings changed, for undo, step ID => step
      settingsBefore: {},

      // steps whose settings were changed with an input that doesn't redraw them, see .no-morph
      skipIslandMorph: {},

      /**
       * Draw the settings panels, see SettingsPanel()
       *
       * @param quiet bool the step being edited keeps what doesn't redraw while it's being edited, see .ignore-morph
       * @param force bool redraw everything, like after undo
       */
      drawPanels ({
        quiet = true,
        force = false,
      } = {}) {

        const steps = this.store.steps.filter(step => !FlowStore.isTempId(step.ID)).sort((a, b) => a.ID - b.ID)

        const islandOf = el => el.closest('.step.settings')?.dataset.id

        morphdom(document.querySelector('.step-settings'), Div({}, steps.map(step => SettingsPanel(step))), {
          childrenOnly     : true,
          onBeforeElUpdated: (fromEl, toEl) => {

            // what's being typed in
            if (fromEl === document.activeElement && !force) {
              return false
            }

            if (fromEl.tagName === 'TEXTAREA' && toEl.tagName === 'TEXTAREA') {
              toEl.style.height = fromEl.style.height
            }

            // preserve the editing class
            if (fromEl.classList.contains('editing')) {
              toEl.classList.add('editing')
            }

            if (fromEl.matches('.step-edit.panels')) {

              const id = islandOf(fromEl)

              // the server didn't send new settings
              if (toEl.dataset.island === 'same') {
                return false
              }

              // changed since, or changed with an input that doesn't redraw them
              if (!force && ( this.hasPendingSettings(id) || this.skipIslandMorph[id] )) {
                delete this.skipIslandMorph[id]
                this.drawnIslands[id] = this.islands[id]
                return false
              }
            }

            // don't morph the currently edited step to avoid glitchiness
            if (!force && quiet && fromEl.matches('.editing .ignore-morph')) {
              return false
            }

            if (fromEl.matches('.step-edit.panels')) {
              this.drawnIslands[islandOf(fromEl)] = this.islands[islandOf(fromEl)]
            }

            return true
          },
          onNodeAdded      : node => {
            if (node.matches?.('.step.settings')) {
              this.drawnIslands[node.dataset.id] = this.islands[node.dataset.id]
            }
            return node
          },
        })

        this.mountSettings()
      },

      /**
       * Save a step's settings, what its panel posts and any settings set by its JS, like the editor always has.
       * Waits a moment for more changes, then sends an update operation, see Flow_Operations::set_stored().
       *
       * @param stepId
       * @param meta Object settings set by JS, see updateStepMeta()
       * @param morph bool whether the step type's settings are redrawn after
       */
      saveSettings (stepId, {
        meta = {},
        morph = true,
      } = {}) {

        const step = this.getStep(stepId)

        if (!step || FlowStore.isTempId(step.ID)) {
          return
        }

        this.settingsBefore[step.ID] ??= JSON.parse(JSON.stringify(step))

        const pending = this.pendingSettings[step.ID] ??= {
          meta : {},
          morph: true,
        }

        pending.meta = {
          ...pending.meta,
          ...meta,
        }

        pending.morph = pending.morph && morph

        clearTimeout(pending.timer)
        pending.timer = setTimeout(() => this.flushSettings(step.ID), 400)
      },

      /**
       * Send a step's settings changes now
       *
       * @param stepId
       */
      flushSettings (stepId) {

        const pending = this.pendingSettings[stepId]

        if (!pending) {
          return
        }

        clearTimeout(pending.timer)
        delete this.pendingSettings[stepId]

        const step = this.getStep(stepId)

        if (!step) {
          return
        }

        const operation = {
          op  : 'update',
          step: step.ID,
        }

        if (Object.keys(pending.meta).length) {
          operation.meta = pending.meta
        }

        const panel = document.getElementById(`settings-${ step.ID }`)

        if (panel) {
          operation.form = FlowStore.formToSettings($(panel).find(':input').serializeArray(), step.ID)

          if (step.data.step_group === 'benchmark') {
            operation.flags = Object.fromEntries(['is_entry', 'is_conversion', 'can_passthru'].map(flag => [flag, Boolean(operation.form[flag])]))
          }
        }

        if (!pending.morph) {
          this.skipIslandMorph[step.ID] = true
        }

        // the settings set by JS and the flags show right away
        try {
          this.store.apply(operation)
        }
        catch (err) {
          console.warn(err)
        }

        this.queue.push([operation])
      },

      flushAllSettings () {
        Object.keys(this.pendingSettings).forEach(id => this.flushSettings(id))
      },

      hasPendingSettings (stepId) {
        const isFor = operation => operation.op === 'update' && operation.form && operation.step == stepId
        return Boolean(this.pendingSettings[stepId]) || this.queue.outbox.some(isFor) || this.queue.sending.some(isFor)
      },

      /**
       * Once settings changes are saved, what the server made of them can be undone
       *
       * @param ids the steps saved
       */
      recordSettingsChanges (ids) {

        ids.forEach(id => {

          const before = this.settingsBefore[id]
          const step = this.getStep(id)

          // more changes are on their way, they're recorded together
          if (!before || !step || this.hasPendingSettings(id)) {
            return
          }

          delete this.settingsBefore[id]

          const changes = FlowStore.stepChanges(before, step)

          if (changes) {
            this.history.record({
              undo: [changes.undo],
              redo: [changes.redo],
            })
          }
        })
      },

      sortables      : null,
      editing        : false,
      addCurrentGroup: 'all',
      addSearch      : '',
      addEl          : null,
      targetStep     : null,
      targetAdd      : null,
      lastSaved      : null,

      stepCallbacks: {},

      views: [
        {
          match  : /^\d+$/, // step settings,
          handler: function (matches) {
            this.startEditing(parseInt(matches[0]))
            this.showSettings()
          },
        },
        {
          match  : /^simulator$/,
          handler: function () {
            this.showSettings()
            this.showSimulator()
          },
        },
        {
          match  : /^add$/,
          handler: function (matches) {
            if (this.addCurrentGroup) {
              window.location.hash = `add/${ this.addCurrentGroup }`
            }
            else {
              window.location.hash = `add/all`
            }
          },
        },
        {
          match  : /^add\/(action|logic|benchmark|all)$/,
          handler: function (matches) {
            this.showSettings()
            this.showAddStep()

            this.addCurrentGroup = matches[1]

            this.clearSearch()
            this.setCurrentGroupButtonToCurrent()
            this.filterStepTypes()

            setTimeout(() => {
              scrollIntoViewIfNeeded(this.addEl, document.querySelector(`.fixed-inside`))
            }, 300)
          },
        },
        {
          match  : /^settings$/,
          handler: function () {
            this.showSettings()
            this.startEditing(null)
            morphSettings()
          },
        },
        {
          match  : /^share$/,
          handler: function (id) {

          },
        },
        {
          match  : /^$/, // no hash
          handler: function () {
            this.hideSettings()
            this.startEditing(null)
          },
        },
      ],

      clearSearch () {
        this.addSearch = ''
        $('#step-search').val('')
      },

      handleHashChange () {
        const hash = window.location.hash.substring(1)
        for (const view of this.views) {
          const result = view.match.exec(hash)
          if (result) {
            document.getElementById('step-settings-inner').dataset.view = hash || 'settings'
            view.handler.apply(this, [result])
            return
          }
        }
      },

      filterStepTypes () {
        $(`.select-step`).addClass('visible')

        // filter by addGroup
        if (this.addCurrentGroup !== 'all') {
          $(`.select-step:not(:has([data-group="${ this.addCurrentGroup }" i]))`).removeClass('visible')
        }

        if (this.addSearch) {
          $(`.select-step:not([data-keywords*="${ this.addSearch }" i])`).removeClass('visible')
        }
      },

      setCurrentGroupButtonToCurrent () {
        $('button.step-filter').removeClass('current')
        $(`button.step-filter[data-group="${ this.addCurrentGroup }"]`).addClass('current')
      },

      /**
       * Register letious step callbacks
       *
       * @param type
       */
      registerStepCallbacks (type, callbacks) {
        this.stepCallbacks[type] = callbacks
      },

      init: async function () {

        let $document = $(document)
        let $form = $('#funnel-form')

        let preloaders = [
          FunnelsStore.maybeFetchItem(this.id),
        ]

        // Preload emails
        let emails = this.steps.filter(step => step.data.step_type === 'send_email').
          map(step => parseInt(step.meta.email_id))

        if (emails.length) {
          preloaders.push(Groundhogg.stores.emails.maybeFetchItems(emails))
        }

        // Preload tags
        let tags = this.steps.filter(
            ({ data: { step_type } }) => [
              'apply_tag',
              'remove_tag',
              'tag_applied',
              'tag_removed',
            ].includes(step_type)).
          reduce((allTags, { meta: { tags } }) => {

            if (!Array.isArray(tags)) {
              return allTags
            }

            tags.forEach(id => {
              if (!allTags.includes(id)) {
                allTags.push(id)
              }
            })

            return allTags
          }, [])

        if (tags.length) {
          preloaders.push(Groundhogg.stores.tags.maybeFetchItems(tags))
        }

        if (tags.length || emails.length) {
          const { close } = loadingModal()
          await Promise.all(preloaders)
          close()
        }

        // handle focused step for copying
        $document.on('click', e => {
          if (Groundhogg.element.clickedIn(e, '#step-flow .step')) {
            this.targetStep = e.target.closest('.step')
          }
          else {
            this.targetStep = null
          }

          if (Groundhogg.element.clickedIn(e, '#step-flow button.add-step')) {
            this.targetAdd = e.target.closest('button.add-step')
          }
          else {
            this.targetAdd = null
          }
        })

        // handle ctrl v, ctrl c
        $document.on('keydown', async e => {
          if (e.key === 'c' && ( e.ctrlKey || e.metaKey ) && this.editing && this.targetStep) {
            navigator.clipboard.writeText(JSON.stringify({
              copy : this.editing,
              group: this.targetStep.dataset.group,
              type : this.targetStep.dataset.type,
            }))
            dialog({
              message: 'Step copied!',
            })
          }
          if (e.key === 'v' && ( e.ctrlKey || e.metaKey ) && this.targetAdd && this.addEl) {

            let text = await navigator.clipboard.readText()

            let json

            try {
              json = JSON.parse(text)
              if (!json) {
                throw new Error('invalid step')
              }

            }
            catch (err) {
              dialog({
                message: err.message,
                type   : 'error',
              })
              return
            }

            const targetId = this.targetAdd.id

            this.save({
              quiet : true,
              // once what's queued is saved, so the canvas won't be redrawn over the placeholder
              before: () => {

                const target = document.getElementById(targetId)

                target?.insertAdjacentElement('beforebegin', createPlaceholderEl({
                  copy      : json.copy,
                  step_group: json.group,
                  step_type : json.type,
                  branch    : target.closest('.step-branch').dataset.branch,
                }))
              },
            }).then(() => {
              this.targetAdd = null
            })

          }
          if (e.key === 'm' && ( e.ctrlKey || e.metaKey ) && this.editing) {
            // cancel
            if (this.moving) {
              document.body.classList.remove('gh-moving-step')
              this.moving = null
              drawLogicLines()
              return
            }

            this.hideSettings()
            this.moving = document.getElementById(`step-${ this.editing }`).closest('.sortable-item')
            document.body.classList.add('gh-moving-step')
            drawLogicLines()
          }

        })

        const settingsHidden = () => $('#step-settings-container').hasClass('slide-out')

        $document.on('click', '#collapse-settings', e => {

          if (settingsHidden()) {
            window.location.hash = 'settings'
          }
          else {
            window.location.hash = ''
          }
        })

        $document.on('click', '#step-flow .step:not(.step-placeholder)', e => {

          if ($(e.target).is('.dashicons, button')) {
            return
          }

          if (this.moving) {
            dialog({
              message: 'Click on any add icon to move the steps.',
              type   : 'info',
            })
            return
          }

          window.location.hash = e.currentTarget.dataset.id
        })

        $document.on('click', '#step-flow', e => {
          if (!Groundhogg.element.clickedIn(e, '.step,.add-step')) {
            window.location.hash = ''
          }
        })

        $document.on('click', 'button.step-filter:not(.current)', e => {
          window.location.hash = `add/${ e.currentTarget.dataset.group }`
        })

        $('#step-search').on('input', e => {
          this.addSearch = e.target.value
          this.filterStepTypes()
        })

        $document.on('mousedown', '.step-element.premium', e => {

          ModalFrame({},
            ({ close }) => Div({
              style: {
                position: 'relative',
              },
            }, [
              Button({
                className: 'gh-button secondary text icon',
                onClick  : close,
                style    : {
                  position: 'absolute',
                  top     : '5px',
                  right   : '5px',
                },
              }, Dashicon('no-alt')),
              An({
                href  : 'https://groundhogg.io/pricing/',
                target: '_blank',
              }, Img({
                style    : {
                  borderRadius: '10px',
                },
                className: 'has-box-shadow',
                src      : `${ Groundhogg.assets.images }upgrade-needed.png`,
              })),
            ]),
          )
        })

        // clicking on an add-step icon in the flow
        // handles both moving and non-moving state
        $document.on('click', 'button.add-step', e => {

          this.clearAddEl()
          this.addEl = e.currentTarget
          this.addEl.classList.add('here')

          if (this.moving) {

            const at = addButtonPosition(this.addEl)
            const ids = stepIdsOf(this.moving)

            document.body.classList.remove('gh-moving-step')
            this.moving = null

            if (at && ids.length) {
              this.perform(ids.map((id, i) => ( {
                op  : 'move',
                step: id,
                at  : i === 0 ? at : { after: ids[i - 1] },
              } )))
            }

            return
          }

          if (this.addEl.matches('.add-benchmark')) {
            window.location.hash = `add/benchmark`
          }
          else if (this.addEl.matches('.add-action')) {
            window.location.hash = `add/action`
          }
          else {
            window.location.hash = `add/${ this.addCurrentGroup }`
          }
        })

        // clicking on a step element icon to add it into the flow at the highlighted position
        $document.on('click', '.step-element.step-draggable:not(.premium)', e => {

          if (!this.addEl) {
            dialog({
              message: 'Click on a + icon in the flow first.',
              type   : 'info',
            })
            return
          }

          const at = addButtonPosition(this.addEl)

          if (!at) {
            return
          }

          this.perform([
            {
              op   : 'add',
              at,
              steps: [
                {
                  type: e.currentTarget.dataset.type,
                  id  : FlowStore.newTempId(),
                },
              ],
            },
          ])
        })

        /* Bind Delete */
        $document.on('click', 'button.delete-step', e => {
          let stepId = e.currentTarget.parentNode.parentNode.dataset.id
          this.deleteStep(stepId)
        })

        $document.on('click', 'button.lock-step', e => {
          let stepId = e.currentTarget.parentNode.parentNode.dataset.id
          this.lockStep(stepId)
        })

        $document.on('click', 'button.unlock-step', e => {
          let stepId = e.currentTarget.parentNode.parentNode.dataset.id
          this.unlockStep(stepId)
        })

        /* Bind Duplicate */
        $document.on('click', 'button.duplicate-step', e => {
          let stepId = e.currentTarget.parentNode.parentNode.dataset.id
          this.duplicateStep(stepId)
        })

        /* Activate Spinner */
        $form.on('submit', function (e) {
          e.preventDefault()
          return false
        })

        $form.on('change', e => {

          // the flow's title
          if (e.target.matches('#title')) {
            FunnelsStore.patch(this.id, {
              data: {
                title: e.target.value,
              },
            })
            return
          }

          const panel = e.target.closest('.step-settings .step.settings')

          if (!panel) {
            return
          }

          if (e.target.matches('textarea[name=step_notes]')) {
            this.updateStepMeta({
              step_notes: e.target.value,
            }, panel.dataset.id)
            return
          }

          this.saveSettings(panel.dataset.id, {
            morph: !e.target.matches('.no-morph'),
          })
        })

        $('#gh-legacy-modal-save-changes').on('click', () => {
          this.saveQuietly()
        })

        // Funnel Title
        $document.on('click', '.title-view .title', function (e) {
          $('.title-view').hide()
          $('.title-edit').show().removeClass('hidden')
          $('#title').focus()
        })

        $document.on('blur change', '#title', function (e) {

          let title = $(this).val()

          $('.title-view').find('.title').text(title)
          $('.title-view').show()
          $('.title-edit').hide()
        })

        $('#funnel-deactivate').on('click', e => {
          dangerConfirmationModal({
            alert      : `<p><b>Are you sure you want to deactivate the flow?</b></p>
<p>Any pending events will be paused. They will be resumed immediately when the flow is reactivated.</p>
<p>Unsaved changes will be discarded. To preserve any changes, update the flow first, then deactivate.</p>`,
            confirmText: __('Deactivate'),
            onConfirm  : () => {
              this.save({
                quiet   : false,
                moreData: formData => formData.append('_deactivate', true),
              })
            },
          })
        })

        $('#funnel-update').on('click', e => {

          const label = e.currentTarget.textContent.trim()
          const update = () => confirmDeletedSteps(this.pending_deletes, label, choices => this.save({
            quiet   : false,
            moreData: formData => {
              formData.append('_commit', true)
              formData.append('_deleted_steps', JSON.stringify(choices))
            },
          }))

          // errors
          if (document.getElementById('step-flow').querySelector('.has-errors')) {

            dangerConfirmationModal({
              // language=HTML
              alert      : `<p><b>Some of your steps have issues!</b></p>
              <p>Review steps with the ⚠️ icon before updating.</p>
              <p>Are you sure you want to commit your changes?</p>`,
              onConfirm  : update,
              confirmText: 'Update anyway',
            })

            return
          }

          update()
        })

        $('#funnel-activate').on('click', e => {

          const label = e.currentTarget.textContent.trim()
          const activate = () => confirmDeletedSteps(this.pending_deletes, label, choices => this.save({
            quiet   : false,
            moreData: formData => {
              formData.append('_activate', true)
              formData.append('_deleted_steps', JSON.stringify(choices))
            },
          }))

          // errors
          if (document.getElementById('step-flow').querySelector('.has-errors')) {

            dangerConfirmationModal({
              // language=HTML
              alert      : `<p><b>Some of your steps have issues!</b></p>
              <p>Review steps with the ⚠️ icon before activating.</p>
              <p>Are you sure you want to activate with issues present?</p>`,
              onConfirm  : activate,
              confirmText: 'Activate anyway',
            })

            return
          }

          activate()
        })

        $('#funnel-simulate').on('click', e => {
          window.location.hash = 'simulator'
        })

        $('#funnel-settings').on('click', e => {
          window.location.hash = 'settings'
        })

        if (window.innerWidth > 600) {
          this.makeSortable()
        }

        morphSettings()

        this.handleHashChange = this.handleHashChange.bind(this)
        window.addEventListener('hashchange', this.handleHashChange)
        this.handleHashChange()

        let header = document.querySelector('.funnel-editor-header > .actions')

        header.append(Button({
          id       : 'funnel-more',
          className: 'gh-button secondary text icon',
          type     : 'button',
          onClick  : e => {
            moreMenu('#funnel-more', [
              {
                key     : 'settings',
                text    : 'Settings',
                onSelect: e => {
                  window.location.hash = 'settings'
                },
              },
              {
                key     : 'export',
                text    : 'Export',
                onSelect: e => {
                  window.open(Funnel.export_url, '_blank')
                },
              },
              {
                key     : 'share',
                text    : 'Share',
                onSelect: e => {
                  prompt('Copy this link to share', Funnel.export_url)
                },
              },
              {
                key     : 'reports',
                text    : 'Reports',
                onSelect: e => {
                  window.open(adminPageURL('gh_reporting', {
                    tab   : 'funnels',
                    funnel: Funnel.id,
                  }), '_blank')
                },
              },
              {
                key     : 'contacts',
                text    : 'Add Contacts',
                onSelect: e => {
                  modal({
                    //language=HTML
                    content: `<h2>${ __('Add contacts to this flow', 'groundhogg') }</h2>
                    <div id="gh-add-to-funnel" style="width: 500px"></div>`,
                    onOpen : () => {
                      document.getElementById('gh-add-to-funnel').append(Groundhogg.FunnelScheduler({
                        funnel    : getFunnel(),
                        funnelStep: getFunnel().steps[0],
                      }))
                    },
                  })
                },
              },
              {
                key     : 'screenshot-mode',
                text    : document.body.classList.contains('gh-screenshot-mode') ? 'Editing Mode' : 'Screenshot Mode',
                onSelect: e => {
                  document.body.classList.toggle('gh-screenshot-mode')
                  drawLogicLines()
                },
              },
              {
                key     : 'fullscreen',
                text    : document.body.classList.contains('gh-full-screen') ? 'Exit Fullscreen' : 'Fullscreen',
                onSelect: e => {
                  document.body.classList.toggle('gh-full-screen')
                  ajax({
                    action     : 'gh_funnel_editor_full_screen_preference',
                    full_screen: document.body.classList.contains('gh-full-screen') ? 1 : 0,
                  })
                },
              },
              {
                key     : 'shortcuts',
                text    : 'Keyboard Shortcuts',
                onSelect: e => {

                  const shortcuts = [
                    [
                      'Copy a step',
                      [
                        'CTRL',
                        'C',
                      ],
                    ],
                    [
                      'Paste a copied step',
                      [
                        'CTRL',
                        'V',
                      ],
                    ],
                    [
                      'Move a step',
                      [
                        'CTRL',
                        'M',
                      ],
                    ],
                    [
                      'Insert replacement code',
                      [
                        'CTRL',
                        'SHIFT',
                        '{',
                      ],
                    ],
                    // [ 'Undo', [ 'CTRL', 'Z' ] ],
                    // [ 'Redo', [ 'CTRL', 'Shift', 'Z' ] ],
                  ]

                  MakeEl.ModalWithHeader({
                      width : '500px',
                      header: 'Keyboard Shortcuts',
                    },
                    Div({ className: 'display-flex column' }, shortcuts.map(([desc, keys]) => Div({ className: 'space-between' }, [
                      MakeEl.Pg({}, desc),
                      MakeEl.Pg({}, keys.map(key => `<code>${ key }</code>`).join(' + ')),
                    ]))),
                  )
                },
              },
              {
                key     : 'feedback',
                text    : 'Feedback',
                onSelect: e => {
                  Groundhogg.components.FeedbackModal({
                    subject: 'Flow editor',
                  })
                },
              },
              {
                key     : 'uncommit',
                text    : '<span class="gh-text danger">Revert Changes</span>',
                onSelect: e => {
                  dangerConfirmationModal({
                    alert    : '<p>Are you sure you want to revert your changes?</p><p>Your flow will be restored to the most recent save point.</p>',
                    onConfirm: () => {
                      this.save({
                        moreData: formData => {
                          formData.append('_uncommit', 1)
                        },
                      }).then(() => UndoRedoManager.clear())
                    },
                  })
                },
              },
            ])
          },
        }, icons.verticalDots))

        $('#step-settings-container').resizable({
          handles        : 'w',
          animateDuration: 'fast',
          resize         : function (event, ui) {
            ui.element.css('left', '') // Remove the 'left' style to keep the div in place
            localStorage.setItem('gh-funnel-settings-panel-width', ui.size.width)
          },
        })

        setInterval(() => this.updateLastSaved(), 10 * 1000)

        syncReplacementCodes()
      },

      updateLastSaved () {

        if (this.lastSaved === null) {
          return
        }

        document.getElementById('last-saved-text').innerHTML = `Changes saved ${ wp.date.humanTimeDiff(this.lastSaved, new Date()) }.`
      },

      /**
       * Save by posting the editor's form, which is how step settings are saved, and duplicating, pasting, and locking
       * steps, and publishing. Waits for the edits queued before it to be saved, see FlowStore.createQueue().
       *
       * @param args Object|true true for a quiet save
       * @return Promise
       */
      save (args = {}) {

        if (args === true) {
          args = {
            quiet: true,
          }
        }

        // settings changes go first
        this.flushAllSettings()

        return this.queue.exclusive(() => this.postForm(args))
      },

      /**
       * @param quiet bool whether it's an autosave, otherwise it's like publishing and says when it's done
       * @param moreData function( formData ) to add to what's posted
       * @param before function called right before posting, like to add a placeholder step to the form
       * @param shouldMorphSettings bool
       */
      async postForm ({
        quiet = true,
        moreData = () => {},
        before = () => {},
        shouldMorphSettings = true,
      }) {

        this.saving = true

        before()

        // let's make sure all the branch info is correct!
        this.updateBranches()

        let formData = new FormData(document.getElementById('funnel-form'))

        // these are in the form but are not actually used when posted
        formData.delete('step_notes')
        formData.delete('note_text')

        formData.append('action', 'gh_save_funnel_via_ajax')

        if (!quiet) {
          $('body').addClass('saving')
          // deleted steps can only be removed for real after this, see Step::delete()
          UndoRedoManager.clear()
        }
        else {
          $('body').addClass('auto-saving')
        }

        // Update the JS meta changes first
        const sentMetaUpdates = this.metaUpdates

        if (Object.keys(sentMetaUpdates).length) {
          formData.append('metaUpdates', JSON.stringify(sentMetaUpdates))
        }

        // edits made while this saves are sent next time
        this.metaUpdates = {}

        // the save failed or was refused, so send the meta updates again next time, newer edits win
        const keepMetaUpdates = () => {
          Object.entries(sentMetaUpdates).forEach(([stepId, meta]) => {
            this.metaUpdates[stepId] = {
              ...meta,
              ...( this.metaUpdates[stepId] ?? {} ),
            }
          })
        }

        // add additional data to the formData if required
        if (moreData) {
          moreData(formData)
        }

        // what the steps were, so the changes the server makes can be undone, like duplicating
        const before_ = JSON.parse(JSON.stringify(this.store.steps))

        return await ajax(formData, {
          url: `${ ajaxurl }?${ quiet ? 'auto-save' : 'explicit-save' }=1`,
        }).then(response => {

          this.saving = false

          // refused, like when someone else is editing the flow
          if (!response.success) {
            $('body').removeClass('saving auto-saving')

            keepMetaUpdates()

            dialog({
              message: response.data?.[0]?.message ?? __('Something went wrong updating the flow. Your changes could not be saved.', 'groundhogg'),
              type   : 'error',
            })

            return response
          }

          if (response.data.err) {
            keepMetaUpdates()
          }

          this.applyState(response.data, {
            quiet,
            shouldMorphSettings,
            loaded: () => {
              if (quiet) {
                this.history.record(this.store.changesSince(before_))
              }
              else {
                this.store.trash = {}
              }
            },
          })

          // quietly!
          if (quiet) {
            $('body').removeClass('auto-saving')
            return response
          }

          $(document).trigger('saved')

          this.stepSettingsCallbacks()

          $('body').removeClass('saving')

          if (response.data.err) {
            dialog({
              message: response.data.err,
              type   : 'error',
            })
            return response
          }

          dialog({
            message: __('Flow saved!', 'groundhogg'),
          })

          return response

        }).catch(err => {

          this.saving = false
          $('body').removeClass('saving auto-saving')

          keepMetaUpdates()

          dialog({
            message: __('Something went wrong updating the flow. Your changes could not be saved.', 'groundhogg'),
            type   : 'error',
          })
          throw err
        })
      },

      saveQuietly: Groundhogg.functions.debounce((args = {}) => Funnel.save({ quiet: true, ...args }), 750),

      updateBranches () {
        document.querySelectorAll(`input[name*="[branch]"][type="hidden"]`).forEach(input => {
          input.value = input.closest('.step-branch').dataset.branch
        })
      },

      makeSortable () {
        this.sortables = $('.step-branch').sortable({
          placeholder: 'sortable-placeholder',
          connectWith: '.step-branch',
          // handle: '.step',
          // tolerance: 'pointer',
          cancel  : '.locked',
          distance: 100,
          cursorAt: {
            left: 5,
            top : 5,
          },
          helper  : (e, $el) => {

            let $step = $el.is('.step') ? $el : $el.find('.step')
            let icon = $el.find('.hndle-icon')[0]

            // language=HTML
            return `
                <div class="sortable-helper-icon ${ $step.data('group') }">
                    <div class="step-icon">
                        ${ icon.outerHTML }
                    </div>
                </div>`
          },
          change  : () => drawLogicLines(),
          // sort    : () => drawLogicLines(),
          stop   : (e, ui) => {

            this.dragging = false

            // added from the step picker, see receive
            if (this.received) {
              this.received = false
              return
            }

            const item = ui.item[0]
            const ids = stepIdsOf(item)

            if (!ids.length || !item.parentElement) {
              this.redraw()
              return
            }

            const at = domPosition(item)

            // dropped where it was, the redraw puts back anything the drag changed
            try {
              const step = this.store.getStep(ids[0])
              const to = this.store.resolvePosition(at, step.ID)
              const index = this.store.branchSteps(step.data.branch).findIndex(sibling => sibling.ID == step.ID)

              if (ids.length === 1 && to.branch === step.data.branch && to.index === index) {
                this.redraw()
                return
              }
            }
            catch (err) {
              this.redraw()
              return
            }

            this.perform(ids.map((id, i) => ( {
              op  : 'move',
              step: id,
              at  : i === 0 ? at : { after: ids[i - 1] },
            } )))
          },
          start  : (e, ui) => {
            ui.helper.width(60)
            ui.helper.height(60)
            drawLogicLines()
            this.dragging = true
          },
          receive: (e, ui) => {

            // moved from another branch, see stop
            if (ui.helper === null) {
              return
            }

            // dropped from the step picker
            this.received = true
            this.dragging = false

            const type = ui.helper.data('type')

            if (!type) {
              ui.helper.remove() // discard right away
              return
            }

            const at = domPosition(ui.helper[0])

            ui.helper.remove()

            this.perform([
              {
                op   : 'add',
                at,
                steps: [
                  {
                    type,
                    id: FlowStore.newTempId(),
                  },
                ],
              },
            ])
          },
        })

        this.sortables.disableSelection()

        $('.step-element.step-draggable').draggable({
          connectToSortable: '.step-branch',
          cancel           : '.premium',
          distance         : 100,
          stop             : () => {
            drawLogicLines()
          },
          helper           : (e) => {

            let $el = $(e.currentTarget)
            let icon = $el.find('.step-icon')[0]

            // language=HTML
            return `
                <div class="sortable-helper-icon ${ $el.data('group') }" data-group="${ $el.data('group') }" data-type="${ $el.attr('id') }">
                    ${ icon.outerHTML }
                </div>`
          },
        })
      },

      hideSettings () {
        $('#step-settings-container').removeAttr('style').addClass('slide-out')
        setTimeout(() => {
          document.dispatchEvent(new Event('resize'))
        }, 400)
      },

      showSettings () {
        $('#step-settings-container').css('width', localStorage.getItem('gh-funnel-settings-panel-width')).removeClass('slide-out')
        setTimeout(() => {
          document.dispatchEvent(new Event('resize'))
        }, 400)
      },

      showAddStep () {
        this.showSettings()
        this.startEditing(null)
      },

      showSimulator () {

        if (!this.steps.length) {
          Groundhogg.element.errorDialog({
            message: 'You must add steps to the flow first.',
          })
          return
        }

        this.showSettings()
        Groundhogg.simulator.state.set({
          current: this.editing ? parseInt(this.editing) : this.steps[0].ID,
        })
        this.startEditing(null)
        Groundhogg.simulator.morph()
      },

      /**
       * Given an element delete it
       *
       * @param id int
       */
      deleteStep: function (id) {

        let step = document.getElementById(`step-${ id }`)
        let $step = $(step)
        let sortable = getSortableEl(step)
        let $sortable = $(sortable)

        // the steps in its branches are deleted with it, steps pointing at those are fine if they're deleted too
        const deleting = [...new Set([id, ...$sortable.find('.step[data-id]').map((i, el) => el.dataset.id).get()].map(String))]

        const blocked = deleting.map(stepId => ( {
          stepId,
          by: ( this.step_references?.[stepId] ?? [] ).filter(ref => !deleting.includes(String(ref.ID))),
        } )).filter(({ by }) => by.length)

        if (blocked.length) {
          cantDeleteUsedSteps(blocked)
          return
        }

        const deleteStep = () => {
          if (this.isEditing(id)) {
            this.startEditing(null)
          }

          // if the server refuses, like when the step is used now, it comes back
          $sortable.fadeOut(200, () => this.perform([
            {
              op  : 'delete',
              step: toId(id),
            },
          ]))
        }

        // deleting the branch will delete inner steps
        if ($sortable.is('.branch-logic') && $sortable.find('.step-branch .step').length > 0) {
          dangerConfirmationModal({
            alert    : '<p>Are you sure you want to delete this step? Any steps in branches will also be deleted.</p>',
            onConfirm: () => deleteStep(),
          })
          return
        }

        // deleting the benchmark will also delete inner steps
        if ($sortable.is('.benchmark') && $sortable.find('.step-branch .step').length > 0) {
          dangerConfirmationModal({
            alert    : '<p>Are you sure you want to delete this trigger? Any sub steps will also be deleted.</p>',
            onConfirm: () => deleteStep(),
          })
          return
        }

        deleteStep()
      },

      /**
       * Given an element delete it
       *
       * @param id int
       */
      lockStep: function (id) {
        this.save({
          quiet   : true,
          moreData: formData => {
            formData.append('_lock_step', id)
          },
        })
      },

      /**
       * Given an element delete it
       *
       * @param id int
       */
      unlockStep: function (id) {
        this.save({
          quiet   : true,
          moreData: formData => {
            formData.append('_unlock_step', id)
          },
        })
      },

      /**
       * Given an element, duplicate the step and
       * Add it to the funnel
       *
       * @param id int
       */
      async duplicateStep (id) {

        const step = this.steps.find(s => s.ID == id)

        if (!step) {
          return
        }

        const type = step.data.step_type
        let extra = {}

        let stepEl = document.getElementById(`step-${ step.ID }`)
        let sortable = stepEl.closest('.sortable-item')

        // it's a benchmark that might have inner steps
        if (sortable.querySelector(`.step-branch:has(.step)`)) {

          extra = await new Promise((res, rej) => {

            confirmationModal({
              alert      : `<p>${ __('Do you also want to duplicate the sub steps as well?', 'groundhogg') }</p>`,
              confirmText: __('Yes, duplicate all sub steps!', 'groundhogg'),
              closeText  : __('No, just this step.', 'groundhogg'),
              onConfirm  : () => res({}),
              onCancel   : () => res({
                __ignore_inner: true,
              }),
            })

          })

        }

        if (this.stepTypes[type]?.onDuplicate) {
          extra = {
            ...extra,
            ...await this.stepTypes[type].onDuplicate(step),
          }
        }
        else if (this.stepCallbacks.hasOwnProperty(type) && this.stepCallbacks[type].hasOwnProperty('onDuplicate')) {
          let _extra = await new Promise((res, rej) => this.stepCallbacks[type].onDuplicate(step, res, rej))
          extra = {
            ...extra,
            ..._extra,
          }
        }

        return await this.save({
          quiet   : true,
          // once what's queued is saved, so the step has its real ID and the canvas won't be redrawn over the placeholder
          before  : () => {

            const realId = this.realIds[step.ID] ?? step.ID

            document.getElementById(`step-${ realId }`)?.closest('.sortable-item').insertAdjacentElement('afterend', createPlaceholderEl({
              duplicate : realId,
              step_type : step.data.step_type,
              step_group: step.data.step_group,
            }))
          },
          moreData: formData => {

            Object.keys(extra).forEach(key => {
              formData.append(key, extra[key])
            })

          },
        })

      },

      getStep (id) {
        return this.steps.find(s => s.ID == id)
      },

      /**
       * The step that is currently being edited.
       *
       * @returns {unknown}
       */
      getActiveStep () {
        return this.getStep(this.editing)
      },

      metaUpdates: {},

      // the real IDs of steps added in the editor, temporary ID => real ID
      realIds: {},

      updateStepMeta (_meta, stepId = false) {

        let step

        if (stepId) {
          step = this.steps.find(s => s.ID == stepId)
        }
        else {
          step = this.getActiveStep()
        }

        // what it was, for undo
        this.settingsBefore[step.ID] ??= JSON.parse(JSON.stringify(step))

        step.meta = {
          ...step.meta,
          ..._meta,
        }

        this.saveSettings(step.ID, { meta: _meta })

        // the title the step type's JS gives it, see previewOf()
        if (this.stepTypes[step.data.step_type]?.title) {
          this.drawCanvas()
          drawLogicLines()
        }

        return step
      },

      isEditing (id) {
        return this.editing == id
      },

      clearAddEl () {
        if (this.addEl) {
          this.addEl.classList.remove('here')
        }
        this.addEl = null
      },

      /**
       * Make the given step active.
       *
       * @param id string
       * @param hps bool what to do with the browser history
       */
      startEditing (id, hps = false) {

        // trying to make the current step active
        if (this.editing === id) {
          return
        }

        // this step is not in the funnel
        if (id && !this.steps.find(s => s.ID == id)) {
          return
        }

        // deactivate the current step
        if (this.editing) {
          try {
            document.getElementById(`step-${ this.editing }`).classList.remove('editing')
            document.getElementById(`settings-${ this.editing }`).classList.remove('editing')
          }
          catch (err) {

          }
        }

        this.editing = id

        // we are indeed making a different step active
        if (this.editing) {

          this.clearAddEl()

          document.getElementById(`step-${ this.editing }`).classList.add('editing')
          document.getElementById(`settings-${ this.editing }`).classList.add('editing')

          this.stepSettingsCallbacks()

          setTimeout(() => {
            scrollIntoViewIfNeeded(document.getElementById(`step-${ this.editing }`), document.querySelector(`.fixed-inside`))
          }, 300)
        }
      },

      stepSettingsCallbacks () {
        const step = this.getActiveStep()

        if (!step) {
          return
        }

        const type = step.data.step_type

        // drawn by the step type's JS instead, see registerStepType()
        if (this.stepTypes[type]?.settings) {
          $(document).trigger('gh-init-pickers')
          $(document).trigger('step-active')
          return
        }

        if (this.stepCallbacks.hasOwnProperty(type) && this.stepCallbacks[type].hasOwnProperty('onActive')) {
          this.stepCallbacks[type].onActive({
            ...step,
            updateStep: meta => this.updateStepMeta(meta, step.ID),
          })
        }

        $(document).trigger('gh-init-pickers')
        $(document).trigger('step-active')
      },

      startTour () {

        Groundhogg.components.Tour([
          {
            prompt  : `This is where you'll build your flow. Every flow is made up of a series of steps. Steps can be <span class="gh-text orange">triggers</span>, <span class="gh-text green">actions</span>, or <span class="gh-text purple">logic</span>.`,
            position: 'right',
            target  : '#step-sortable',
            onInit  : ({
              target,
            }) => {
              target.click()
            },
          },
          {
            prompt  : '👈 Click on any ➕ icon to start adding new steps to a flow. Once clicked the icon will become highlighted and new steps can be added at that position.',
            position: 'right',
            target  : 'button.add-step',
            onInit  : ({
              target,
            }) => {
              target.click()
            },
          },
          {
            prompt  : 'Filter the various types by using the group filters.',
            position: 'below',
            target  : '.steps-select .gh-input-group.full-width',
          },
          {
            prompt  : `<span class="gh-text orange">Triggers</span> start flows, and move contacts forward when they do something you're watching for, like filling out a form or making a purchase.`,
            position: 'below',
            target  : 'button.step-filter[data-group="benchmark"]',
            onBefore: ({ target }) => target.click(),
          },
          {
            prompt  : `<span class="gh-text green">Actions</span> are used to communicate with the contact and provide your customer experience.`,
            position: 'below',
            target  : 'button.step-filter[data-group="action"]',
            onBefore: ({ target }) => target.click(),
          },
          {
            prompt  : `<span class="gh-text purple">Logic</span> steps are used to provide a different experience to contacts based on their attributes and history.`,
            position: 'below',
            target  : 'button.step-filter[data-group="logic"]',
            onBefore: ({ target }) => target.click(),
          },
          {
            prompt  : 'You can also search for steps using keywords.',
            position: 'left',
            target  : '.step-search-wrap',
          },
          {
            prompt  : 'Click on a type to add it to the flow at the highlighted position 👇',
            position: 'above',
            target  : '.steps-grid',
            onBefore: () => {
              document.querySelector('button.step-filter[data-group="benchmark"]').click()
            },
          },
          {
            prompt  : `Let's add a <span class="gh-text orange">Tag Applied</span> trigger to the flow now by clicking on the icon.`,
            position: 'right',
            target  : '#tag_applied',
            onBefore: ({ target }) => {
              document.querySelector('button.step-filter[data-group="benchmark"]').click()
              target.click()
            },
          },
          {
            prompt  : '👈 Click on a step in the flow to show its settings.',
            position: 'right',
            target  : '#step-flow .step.apply_tag',
            onInit  : ({ target }) => {
              target.click()
            },
          },
          {
            prompt  : '👆 You will configure the step settings from here.',
            position: 'below',
            target  : '.settings.editing .main-step-settings-panel',
          },
          {
            prompt  : 'Some context specific settings will appear here, as well as the notes area for your personal usage.',
            position: 'left',
            target  : '.settings.editing .step-notes',
          },
          {
            prompt  : `You can start a flow with more than one <span class="gh-text orange">trigger</span> by adding a new trigger adjacent to an existing one.`,
            position: 'left',
            target  : '.add-step.add-benchmark',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Let's add a new <span class="gh-text orange">User Created</span> trigger.`,
            position: 'right',
            target  : '#account_created',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Now the flow will start when a tag is applied <span class="gh-text purple">or</span> when a user is created!`,
            position: 'right',
            target  : '.step-branch.benchmarks',
            onBefore: () => this.hideSettings(),
          },
          {
            prompt  : `<span class="gh-text orange">Triggers</span> have their own sub-flows that are not part of the main flow. Only contacts that completed the <span class="gh-text orange">trigger</span> can run through these steps.`,
            position: 'left',
            target  : '.step.benchmark + .step-branch',
            onBefore: ({}) => {
              this.hideSettings()
            },
          },
          {
            prompt  : `Click on the ➕ icon to add an action to the sub-flow.`,
            position: 'right',
            target  : '.step.benchmark + .step-branch .add-step',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Let's add a new <span class="gh-text green">Delay Timer</span> action to the sub flow.`,
            position: 'right',
            target  : '#delay_timer',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Now, only contacts that completed the <span class="gh-text orange">Tag Applied</span> trigger will wait at the <span class="gh-text green">Delay Timer</span>.`,
            position: 'right',
            target  : '.step-branch.benchmarks .step.delay_timer',
            onInit  : ({ target }) => this.hideSettings(),
          },
          {
            prompt  : 'Click here to add a new step to the main flow. Steps here will run for anyone in the flow, regardless of where they entered.',
            position: 'above',
            target  : '.add-step#end-funnel',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Let's add a new <span class="gh-text purple">Logic</span> step to send different emails based on a contact's tags.`,
            position: 'right',
            target  : '#if_else',
            onBefore: ({ target }) => {
              document.querySelector('button.step-filter[data-group="logic"]').click()
              target.click()
            },
          },
          {
            prompt  : `Click on the ➕ icon within a logic branch to add steps to it.`,
            position: 'right',
            target  : '#step-flow .branch-logic .split-branch.green .step-branch .add-step',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Let's add a new <span class="gh-text green">Send Email</span> action to the <span class="gh-text green">Yes</span> branch.`,
            position: 'left',
            target  : '#send_email',
            onBefore: () => {
              document.querySelector('button.step-filter[data-group="action"]').click()
            },
            onInit  : ({ target }) => {
              target.click()
            },
          },
          {
            prompt  : `We want to send a different email if the contact does not meet our conditions, so click on the ➕ in the No branch.`,
            position: 'right',
            target  : '#step-flow .branch-logic .split-branch.red .step-branch .add-step',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Let's add a new <span class="gh-text green">Send Email</span> action to the <span class="gh-text red">No</span> branch as well.`,
            position: 'left',
            target  : '#send_email',
            onBefore: () => {
              document.querySelector('button.step-filter[data-group="action"]').click()
            },
            onInit  : ({ target }) => {
              target.click()
            },
          },
          {
            prompt  : `Let's configure the logic condition so that the right contacts get the right email`,
            position: 'right',
            target  : '#step-flow .step.if_else',
            onInit  : ({ target }) => {
              target.click()
            },
          },
          {
            prompt  : `Use powerful <span class="gh-text purple">filters</span> to send contacts down the different branches. Contacts that match the filters will go down the <span class="gh-text green">Yes</span> branch, otherwise the <span class="gh-text red">No</span> branch.`,
            position: 'below',
            target  : '.settings.editing .custom-settings',
          },
          {
            prompt  : `Let's add another <span class="gh-text orange">trigger</span> that will end the flow.`,
            position: 'above',
            target  : '.add-step#end-funnel',
            onInit  : ({ target }) => {
              target.click()
              document.querySelector('button.step-filter[data-group="benchmark"]').click()
            },
          },
          {
            prompt  : `Let's end the flow with a <span class="gh-text orange">Tag Removed</span> trigger.`,
            position: 'right',
            target  : '#tag_removed',
            onInit  : ({ target }) => target.click(),
          },
          {
            prompt  : `Now if a tag is removed from the contact at any time, they will jump here, ending the flow.`,
            position: 'above',
            target  : '#step-flow .step.tag_removed',
            onBefore: ({ target }) => {
              setTimeout(() => {
                target.focus().scrollIntoView({
                  behavior: 'smooth',
                  block   : 'center',
                  inline  : 'center',
                })
              }, 250)
            },
            onInit  : () => {
              this.hideSettings()
            },
          },
          {
            prompt  : `When you've configured all the steps in your flow, turn it on by clicking <span class="gh-text green">Activate</span>!`,
            position: 'below-left',
            target  : '#funnel-activate',
          },
        ], {
          fixed        : true,
          onFinish     : () => {
            dialog({
              message: '🎉 Tour complete!',
            })
            return ajax({
              action: 'gh_dismiss_notice',
              notice: 'funnel-tour',
            })
          },
          beforeDismiss: ({ dismiss }) => {
            confirmationModal({
              alert    : `<p>Are you sure you want to exit the tour?</p>`,
              onConfirm: () => {
                dismiss()
                return ajax({
                  action: 'gh_dismiss_notice',
                  notice: 'funnel-tour',
                }).then(() => true)
              },
            })
          },
        })
      },
    })

    // Funnel.steps is the store's, which step type JS reads
    Object.defineProperty(Funnel, 'steps', {
      get () {
        return Funnel.store.steps
      },
      set (steps) {
        Funnel.store.steps = steps
      },
      configurable: true,
      enumerable  : true,
    })

    // edits are saved in the background, one request at a time
    Funnel.queue = FlowStore.createQueue({
      send     : operations => Funnel.sendOperations(operations),
      onSaved  : (data, operations) => Funnel.operationsSaved(data, operations),
      onRefused: (data, operations) => Funnel.operationsRefused(data, operations),
      onRetry  : (error, attempt) => Funnel.operationsRetrying(error, attempt),
    })

    $(function () {
      Funnel.drawCanvas()
      Funnel.drawPanels()
      drawLogicLines()
      Funnel.init().then(() => {

        // if tour is dismissed, do regular stuff
        if (Funnel.funnelTourDismissed) {

          const url = new URL(window.location)

          // do title prompt if prompted...
          const hasFromAdd = url.searchParams.get('from') === 'add'

          if (hasFromAdd) {

            // remove from url so reloads don't re-prompt
            url.searchParams.delete('from')
            window.history.replaceState({}, '', url)

            // Prompt to rename the funnel
            MakeEl.ModalWithHeader({
              header: 'Name your flow',
              onOpen: () => {
                let input = document.getElementById('prompt-funnel-title')
                input.focus()
                input.select()
              },
            }, ({ close }) => MakeEl.Form({
              onSubmit: e => {
                e.preventDefault()
                let fd = new FormData(e.currentTarget)
                let title = fd.get('funnel_title')

                $('.title-view').find('.title').text(title)
                $('#title').val(title)
                Funnel.saveQuietly()
                close()
              },
            }, [
              Div({
                className: 'display-flex gap-5',
              }, [
                Input({
                  id   : 'prompt-funnel-title',
                  name : 'funnel_title',
                  value: Funnel.data.title,
                }),
                Button({
                  className: 'gh-button primary',
                  type     : 'submit',
                }, 'Save'),
              ]),
            ]))

          }

          return
        }

        if (Funnel.steps.length > 0) {
          // existing funnel, offer the tour in a scratch funnel
          confirmationModal({
            alert      : `<p>👋 New to building flows?</p><p>Would you like a quick tour of the flow editor? We'll open a blank practice flow so this one isn't changed.</p>`,
            confirmText: 'Start tour!',
            closeText  : 'No thanks',
            onConfirm  : () => {
              localStorage.setItem('gh-force-tour', 'yes')
              // open a new scratch funnel to start the tour
              window.open(Funnel.scratchFunnelURL, '_self')
            },
            onCancel   : () => {
              return ajax({
                action: 'gh_dismiss_notice',
                notice: 'funnel-tour',
              })
            },
          })
          return
        }

        // tour is forced
        if (localStorage.getItem('gh-force-tour') === 'yes') {
          localStorage.removeItem('gh-force-tour') // clear from local storage
          Funnel.startTour()
          return
        }

        // this is a scratch funnel, lets ask if they want to tour
        confirmationModal({
          alert      : `<p>👋 Flows let you automate your customer journey, from the first touchpoint to the sale and beyond.</p><p>Would you like a quick tour of how to build one?</p>`,
          confirmText: 'Start tour!',
          closeText  : 'No thanks',
          onConfirm  : () => {
            Funnel.startTour()
          },
          onCancel   : () => {
            return ajax({
              action: 'gh_dismiss_notice',
              notice: 'funnel-tour',
            })
          },
        })
      })
    })

    window.addEventListener('beforeunload', e => {

      if (Object.keys(Funnel.metaUpdates).length || Object.keys(Funnel.pendingSettings).length || Funnel.queue.isBusy()) {
        e.preventDefault()
        let msg = __('You have unsaved changes, are you sure you want to leave?', 'groundhogg')
        e.returnValue = msg
        return msg
      }

      return null
    })
  }

  $(document).on('step-active', e => {

    const step = Funnel.getActiveStep()

    if ( step.data.step_group === 'benchmark' ) {
      morphdom( document.getElementById( `trigger-frequency-settings-${ step.ID }` ), TriggerFrequencySettings() )
    }

  })

  const TriggerFrequencySettings = () => {

    const step = () => Funnel.getActiveStep()

    return Div({
      id: `trigger-frequency-settings-${ step().ID }`,
      className: 'trigger-frequency-settings display-flex align-center gap-5 ignore-morph flex-wrap',
    }, morph => MakeEl.Fragment([
      MakeEl.Label({ for: `trigger-frequency-${step().ID}` }, 'Can be triggered' ),
      MakeEl.InputGroup([
        MakeEl.Select({
          name: '_trigger_frequency',
          id: `trigger-frequency-${step().ID}`,
          onChange: e => {
            Funnel.updateStepMeta({
              _trigger_frequency: e.target.value,
            })
            morph()
          },
          options: {
            unlimited: 'Unlimited times',
            once: 'At most once per contact',
            x: 'Up to X times per contact',
          },
          selected: step().meta._trigger_frequency ?? 'unlimited',
        }),
        step().meta._trigger_frequency === 'x' ? Input({
          type: 'number',
          className: 'number',
          style: { width: '50px'},
          value: step().meta._trigger_frequency_x_times ?? 1,
          onChange: e => {
            Funnel.updateStepMeta({
              _trigger_frequency_x_times: e.target.value,
            })
          },
        }) : null
      ]),
      step().meta._trigger_frequency === 'x' ? MakeEl.InputGroup([
        MakeEl.Select({
          onChange: e => {
            Funnel.updateStepMeta({
              _trigger_frequency_range: e.target.value,
            })
            morph()
          },
          options: {
            all: 'in all time',
            x_days: 'in X days',
          },
          selected: step().meta._trigger_frequency_range ?? 'all',
        }),
        step().meta._trigger_frequency_range === 'x_days' ? Input({
          type: 'number',
          className: 'number',
          style: { width: '50px'},
          value: step().meta._trigger_frequency_x_days ?? 1,
          onChange: e => {
            Funnel.updateStepMeta({
              _trigger_frequency_x_days: e.target.value,
            })
          },
        }) : null,
      ]) : null
    ]))

  }

  function areNumbersClose (num1, num2, tolerancePercent) {
    const average = ( Math.abs(num1) + Math.abs(num2) ) / 2
    const tolerance = ( tolerancePercent / 100 ) * average
    return Math.abs(num1 - num2) <= tolerance
  }

  const getSortableEl = el => {
    if (el.matches('.sortable-item')) {
      return el
    }

    return el.closest('.sortable-item')
  }

  const JumpArrow = dir => Div({ className: `jump-arrow ${dir}` }, [
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 15 15"><path color='currentColor' d="M7.539 2c-.295 0-.489.177-.616.385l-5.846 9.538C1 12 1 12.153 1 12.308c0 .538.385.692.692.692h11.616c.384 0 .692-.154.692-.692 0-.154 0-.231-.077-.385l-5.77-9.538C8.029 2.177 7.789 2 7.539 2"/></svg>`
  ])

  /**
   * Get the distance between two anchor points on DOM elements.
   *
   * Examples:
   *   elementDistance(a, b, 'bottom', 'middle') // vertical: bottom of A → middle of B
   *   elementDistance(a, b, 'left', 'left')     // horizontal: left of A → left of B
   *   elementDistance(a, b, 'right', 'left')    // horizontal: right of A → left of B
   *
   * @param {HTMLElement} a
   * @param {HTMLElement} b
   * @param {'top'|'bottom'|'left'|'right'|'middle'|'center'} from
   * @param {'top'|'bottom'|'left'|'right'|'middle'|'center'} to
   * @return {number}
   */
  const elementDistance = (a, b, from, to) => {

    const aRect = a.getBoundingClientRect()
    const bRect = b.getBoundingClientRect()

    const point = (rect, position) => {
      switch (position) {
        case 'top':
          return rect.top

        case 'bottom':
          return rect.bottom

        case 'left':
          return rect.left

        case 'right':
          return rect.right

        case 'middle':
          return rect.top + rect.height / 2

        case 'center':
          return rect.left + rect.width / 2
      }
    }

    return point(bRect, to) - point(aRect, from)
  }

  function findWidestElementBetween (startElement, endElement) {

    startElement = getSortableEl(startElement)
    endElement = getSortableEl(endElement)

    let el = startElement
    let currEl = startElement.nextElementSibling

    while (currEl) {

      if (el.getBoundingClientRect().width < currEl.getBoundingClientRect().width) {
        el = currEl
      }

      if (currEl.isSameNode(endElement)) {
        break
      }

      currEl = currEl.nextElementSibling
    }

    return el
  }

  function getClosestScrollingAncestor (element) {
    let parent = element.parentElement

    while (parent) {
      const style = window.getComputedStyle(parent)
      const overflowY = style.overflowY
      const isScrollable = ( overflowY === 'auto' || overflowY === 'scroll' ) && parent.scrollHeight > parent.clientHeight

      if (isScrollable) {
        return parent // Found the closest scrollable ancestor
      }

      parent = parent.parentElement
    }

    return document.documentElement // Defaults to <html> if no scrollable ancestor is found
  }

  function scrollIntoViewIfNeeded (element, container) {

    if (!element) {
      return
    }

    if (!container) {
      container = getClosestScrollingAncestor(element)
    } // Default to parent if no container is provided

    const elementRect = element.getBoundingClientRect()
    const containerRect = container.getBoundingClientRect()

    const isVisibleVertically =
      elementRect.top >= containerRect.top &&
      elementRect.bottom <= containerRect.bottom

    const isVisibleHorizontally =
      elementRect.left >= containerRect.left &&
      elementRect.right <= containerRect.right

    if (!isVisibleVertically || !isVisibleHorizontally) {
      element.scrollIntoView({
        behavior: 'smooth',
        block   : isVisibleVertically ? 'nearest' : 'center',
        inline  : isVisibleHorizontally ? 'nearest' : 'center',
      })
    }
  }

  let lineWidth

  function drawLogicLines () {

    // const borderRadius = '50px'
    const borderRadius = 'var(--logic-line-radius)'
    const borderWidth = `var(--logic-line-width)`

    if (!lineWidth) {
      lineWidth = parseInt(window.getComputedStyle(document.getElementById('step-flow')).getPropertyValue('--logic-line-width'))
    }
    let offset = lineWidth / 2

    const clearLineStyle = line => line.removeAttribute('style')

    let main = document.querySelector(`.step-branch[data-branch='main']`)
    let end = main.querySelector('div.funnel-end')

    if (!end) {
      end = MakeEl.Fragment([
        document.body.classList.contains('gh_funnels') ? Button({
          className: `add-step ${ Funnel.steps.length ? 'add-action' : 'add-benchmark' }`,
          id       : 'end-funnel',
        }, MakeEl.Dashicon('plus-alt2')) : null,
        Div({ className: 'flow-line' }),
        Div({ className: 'funnel-end' }, Span({ className: 'the-end' }, 'End')),
      ])
    }

    main.append(end)

    // Benchmark Groups
    try {
      document.querySelectorAll('.step-branch.benchmarks').forEach(el => {

        // already there
        if (el.previousElementSibling && el.previousElementSibling.matches('.benchmark-pill')) {
          return
        }

        let insert

        if (el.parentElement.matches('.starting')) {
          insert = Div({ className: 'benchmark-pill ' }, [
            'Start the flow when...',
          ])
        }
        else {
          insert = Div({ className: 'benchmark-pill' }, [
            'Until...',
            ToolTip('Contacts will be <i>pulled</i> here, skipping all actions, when any <span class="gh-text orange">trigger</span> is completed.',
              'right'),
          ])

          el.insertAdjacentElement('beforebegin', Div({ className: 'flow-stop' }))
        }
        el.insertAdjacentElement('beforebegin', insert)
      })

    }
    catch (err) {

    }

    // Benchmarks
    try {

      document.querySelectorAll('.logic-line.benchmark-line').forEach(el => el.remove())
      document.querySelectorAll('.step-branch.benchmarks > .sortable-item').forEach(el => {

        // the step-branch.benchmarks container
        let rowPos = el.parentElement.getBoundingClientRect()

        // the benchmark itself
        let step = el

        let stepPos = step.getBoundingClientRect()

        let stepCenter = stepPos.left + stepPos.width / 2
        let rowCenter = rowPos.left + rowPos.width / 2

        let line1 = Div({ className: `logic-line benchmark-line line-${ step.id }-1` })
        step.parentElement.append(line1)

        let line2 = Div({ className: `logic-line benchmark-line line-${ step.id }-2` })
        step.parentElement.append(line2)

        if (step.style.display === 'none') {
          line1.remove()
          line2.remove()
          return
        }

        clearLineStyle(line1)
        clearLineStyle(line2)

        let lineWidth = Math.abs(rowCenter - stepCenter) / 2
        let lineHeight = Math.abs(rowPos.bottom - stepPos.bottom) / 2

        line1.style.bottom = `${ Math.abs(stepPos.bottom - rowPos.bottom) - lineHeight }px`
        line2.style.bottom = `${ Math.abs(stepPos.bottom - rowPos.bottom) - ( lineHeight * 2 ) }px`
        line1.style.width = `${ lineWidth }px`
        line1.style.height = `${ lineHeight }px`
        line2.style.width = `${ lineWidth }px`
        line2.style.height = `${ lineHeight }px`

        // center
        if (areNumbersClose(stepCenter, rowCenter, 1)) {
          line1.style.left = `calc(50% - ${ offset }px)`
          line1.style.width = 0
          line1.style.bottom = 0
          line1.style.height = `${ lineHeight * 2 }px`
          line1.style.borderWidth = `0 0 0 ${ borderWidth }`
          line2.style.display = 'none'
        }
        // left side
        else if (stepCenter < rowCenter) {
          line1.style.left = `${ stepCenter - rowPos.left - offset }px`
          line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line1.style.borderRadius = `0 0 0 ${ borderRadius }`

          line2.style.left = `${ stepCenter - rowPos.left + lineWidth - offset }px`
          line2.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line2.style.borderRadius = `0 ${ borderRadius } 0 0`
        }
        // right side
        else {
          line1.style.left = `${ stepCenter - rowPos.left - lineWidth - offset }px`
          line1.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line1.style.borderRadius = `0 0 ${ borderRadius } 0`

          line2.style.left = `${ stepCenter - rowPos.left - ( lineWidth * 2 ) - offset }px`
          line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line2.style.borderRadius = `${ borderRadius } 0 0 0`
        }

        // no above lines for starting group
        if (step.closest('.sortable-item.benchmarks').matches('.starting')) {
          return
        }

        // above
        lineHeight = Math.abs(rowPos.top - stepPos.top) / 2

        let line4 = Div({ className: `logic-line benchmark-line passthru line-${ step.id }-4` })
        let line3 = Div({ className: `logic-line benchmark-line passthru line-${ step.id }-3` }, [
          step.classList.contains('passthru') ? Span({ className: 'path-indicator' }, 'Pass-through') : null,
          line4,
        ])

        step.parentElement.append(line3)

        if (step.style.display === 'none') {
          line3.remove()
          return
        }

        clearLineStyle(line3)
        clearLineStyle(line4)
        line3.classList.remove('left', 'right', 'middle')

        line3.style.top = `0`
        line3.style.width = `${ lineWidth }px`
        line3.style.height = `${ lineHeight }px`

        line4.style.top = `100%`
        line4.style.width = `${ lineWidth }px`
        line4.style.height = `${ lineHeight }px`

        // center
        if (areNumbersClose(stepCenter, rowCenter, 1)) {
          line3.style.left = `calc(50% - ${ offset }px)`
          line3.style.width = 0
          line3.style.top = 0
          line3.style.height = `${ lineHeight * 2 }px`
          line3.style.borderWidth = `0 0 0 ${ borderWidth }`
          line4.style.display = 'none'
          line3.classList.add('middle')
        }
        // left side
        else if (stepCenter < rowCenter) {
          line3.style.left = `${ stepCenter - rowPos.left + lineWidth - offset }px`
          line3.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line3.style.borderRadius = `0 0 ${ borderRadius } 0`
          line3.classList.add('left')
          line4.style.right = `${ lineWidth }px`
          line4.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line4.style.borderRadius = `${ borderRadius } 0 0 0`
        }
        // right side
        else {
          line3.style.left = `${ rowCenter - rowPos.left }px`
          line3.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line3.style.borderRadius = `0 0 0 ${ borderRadius } `
          line3.classList.add('right')
          line4.style.left = `${ lineWidth }px`
          line4.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line4.style.borderRadius = `0 ${ borderRadius } 0 0`
        }
      })
    }
    catch (e) {}

    // Above
    try {
      document.querySelectorAll('.logic-line.line-above').forEach(line => {

        let branchPos = line.parentElement.getBoundingClientRect()
        let stepPos = line.closest('.step-branches').previousElementSibling.getBoundingClientRect()

        let stepCenter = stepPos.left + stepPos.width / 2
        let branchCenter = branchPos.left + branchPos.width / 2

        let stepHeightCenter = stepPos.top + stepPos.height / 2
        let lineHeight = branchPos.top - stepHeightCenter

        clearLineStyle(line)
        line.classList.remove('left', 'right', 'middle')

        if (!( stepPos.left < branchCenter && branchCenter < stepPos.right )) {
          line.querySelectorAll('.line-inside').forEach(el => el.remove())
        }

        // center
        if (areNumbersClose(branchCenter, stepCenter, 1)) {
          line.classList.add('middle')
          lineHeight = branchPos.top - stepPos.bottom
          line.style.left = 'calc(50% - 1px)'
          line.style.top = `-${ lineHeight }px`
          line.style.height = `${ lineHeight }px`
          line.style.borderWidth = `0 0 0 ${ borderWidth }`
        }
        // middle but curvy
        else if (stepPos.left < branchCenter && branchCenter < stepPos.right) {

          lineHeight = Math.abs(branchPos.top - stepPos.bottom)

          let line1 = line
          let line2 = line1.querySelector('.line-inside')
          if (!line2) {
            line2 = Div({ className: `logic-line line-inside` })
            line1.append(line2)
          }

          clearLineStyle(line2)

          let lineWidth = Math.abs(branchCenter - stepCenter) / 2

          line1.style.top = `-${ lineHeight }px`
          line1.style.height = `${ lineHeight / 2 }px`
          line1.style.width = `${ lineWidth }px`

          line2.style.width = `${ lineWidth }px`
          line2.style.height = `${ lineHeight / 2 }px`
          line2.style.top = '100%'

          // right
          if (branchCenter > stepCenter) {
            line.classList.add('right')

            line1.style.right = `calc(50% - 1px + ${ lineWidth }px)`
            line1.style.borderBottomLeftRadius = borderRadius
            line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
            line2.style.left = '100%'
            line2.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
            line2.style.borderTopRightRadius = borderRadius
          }
          else {
            line.classList.add('left')

            line1.style.left = `calc(50% - 1px + ${ lineWidth }px)`
            line1.style.borderBottomRightRadius = borderRadius
            line1.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
            line2.style.right = '100%'
            line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
            line2.style.borderTopLeftRadius = borderRadius
          }
        }
        // left side
        else if (branchCenter < stepCenter) {

          line.classList.add('left')

          line.style.left = 'calc(50% - 1px)'
          line.style.width = `${ stepPos.left - branchCenter }px`
          line.style.top = `-${ lineHeight }px`
          line.style.height = `${ lineHeight }px`
          line.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line.style.borderTopLeftRadius = borderRadius
        }
        // right side
        else {

          line.classList.add('right')

          line.style.right = 'calc(50% - 1px)'
          line.style.width = `${ branchCenter - stepPos.right }px`
          line.style.top = `-${ lineHeight }px`
          line.style.height = `${ lineHeight }px`
          line.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line.style.borderTopRightRadius = borderRadius
        }

      })
    }
    catch (e) {}

    // Below
    try {
      document.querySelectorAll('.logic-line.line-below').forEach(el => {

        let line1 = el
        let line2 = el.nextElementSibling

        let branchPos = line1.parentElement.getBoundingClientRect()
        let containerPos = line1.closest('.sortable-item').getBoundingClientRect()

        let stepCenter = containerPos.left + containerPos.width / 2
        let branchCenter = branchPos.left + branchPos.width / 2

        let lineHeight = Math.abs(containerPos.bottom - branchPos.bottom) / 2
        let lineWidth = Math.abs(stepCenter - branchCenter) / 2

        clearLineStyle(line1)
        clearLineStyle(line2)

        // center
        if (areNumbersClose(stepCenter, branchCenter, 1)) {
          line1.style.left = 'calc(50% - 1px)'
          line1.style.bottom = `-${ lineHeight * 2 }px`
          line1.style.height = `${ lineHeight * 2 }px`
          line1.style.borderWidth = `0 0 0 ${ borderWidth }`
          line2.style.display = 'none'
        }
        // left side
        else if (branchCenter < stepCenter) {
          line1.style.left = 'calc(50% - 1px)'
          line1.style.width = `${ lineWidth }px`
          line1.style.bottom = `-${ lineHeight }px`
          line1.style.height = `${ lineHeight }px`
          line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line1.style.borderBottomLeftRadius = borderRadius

          line2.style.left = `calc(50% + ${ lineWidth - 1 }px)`
          line2.style.width = `${ lineWidth }px`
          line2.style.bottom = `-${ lineHeight * 2 }px`
          line2.style.height = `${ lineHeight }px`
          line2.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line2.style.borderTopRightRadius = borderRadius

        }
        // right side
        else {
          line1.style.right = `calc(50% - 1px)`
          line1.style.width = `${ lineWidth }px`
          line1.style.bottom = `-${ lineHeight }px`
          line1.style.height = `${ lineHeight }px`
          line1.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line1.style.borderBottomRightRadius = borderRadius

          line2.style.right = `calc(50% + ${ lineWidth - 1 }px)`
          line2.style.width = `${ lineWidth }px`
          line2.style.bottom = `-${ lineHeight * 2 }px`
          line2.style.height = `${ lineHeight }px`
          line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line2.style.borderTopLeftRadius = borderRadius
        }

      })
    }
    catch (e) {}

    /**
     * -->--②
     * |
     * ^
     * |
     * --<--①
     *
     * @param from
     * @param to
     */
    const loopLine = ( from, to ) => {

      let line2 = Div({ className: `logic-line loop-line loop-line-2 loop-line-${ from.dataset.id }-to-${ to.dataset.id }--2` }, [
       JumpArrow('right')
      ])
      let line1 = Div({ className: `logic-line loop-line loop-line-1 loop-line-${ from.dataset.id }-to-${ to.dataset.id }--1` }, line2 )

      from.parentNode.querySelector( `:scope > .loop-line-${ from.dataset.id }-to-${ to.dataset.id }--1` )?.remove()
      from.parentNode.append(line1)

      let widestEl = findWidestElementBetween(to, from)
      let toPos = to.getBoundingClientRect()
      let fromPos = from.getBoundingClientRect()

      let lineHeight = Math.abs(( fromPos.bottom - ( fromPos.height / 2 ) ) - ( toPos.bottom - ( toPos.height / 2 ) )) / 2

      let branchPos = from.closest('.step-branch').getBoundingClientRect()
      let sortable = from.closest('.sortable-item')

      let width = ( ( widestEl ? widestEl.getBoundingClientRect().width : branchPos.width ) ) / 2

      line1.style.right = '100%'
      line1.style.bottom = `${elementDistance(from, sortable, 'middle', 'bottom')}px`
      line1.style.height = `${ lineHeight }px`
      line1.style.width = `${ width }px`
      line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
      line1.style.borderBottomLeftRadius = borderRadius

      line2.style.left = `-2px`
      line2.style.bottom = `100%`
      line2.style.height = `${ lineHeight }px`
      line2.style.width = `${ elementDistance(line1, to, 'left', 'left' ) - 8 }px`
      line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
      line2.style.borderTopLeftRadius = borderRadius

      // line2.firstElementChild.style.left = '100%'


    }

    /**
     * ①-->--
     *       |
     *       ↓
     *       |
     * ②--<--
     *
     * @param from
     * @param to
     * @param offset
     */
    const skipLine = (from, to, offset = 0) => {

      let line2 = Div({ className: `logic-line skip-line skip-line-2 skip-line-${ from.dataset.id }-to-${ to.dataset.id }--2` }, [
        JumpArrow('left'),
      ])
      let line1 = Div({ className: `logic-line skip-line skip-line-1 skip-line-${ from.dataset.id }-to-${ to.dataset.id }--1` }, line2 )

      from.parentNode.querySelector( `:scope > .skip-line-${ from.dataset.id }-to-${ to.dataset.id }--1` )?.remove()
      from.parentNode.append(line1)

      let widestEl = findWidestElementBetween(to, from)

      let lineHeight = elementDistance(from, to, 'middle', 'middle') / 2

      let branchPos = from.closest('.step-branch').getBoundingClientRect()
      let sortable = from.closest('.sortable-item')

      let width = ( ( widestEl ? widestEl.getBoundingClientRect().width : branchPos.width ) ) / 2

      line1.style.left = '100%'
      line1.style.top = `${elementDistance(sortable, from, 'top', 'middle')}px`
      line1.style.height = `${ lineHeight }px`
      line1.style.width = `${ width }px`
      line1.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
      line1.style.borderTopRightRadius = borderRadius

      line2.style.right = `-2px`
      line2.style.top = `100%`
      line2.style.height = `${ lineHeight }px`
      line2.style.width = `${ elementDistance(to, line1, 'right', 'right' ) - 8 }px`
      line2.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
      line2.style.borderBottomRightRadius = borderRadius

      // line2.firstElementChild.style.right = '100%'

    }


    // loops
    try {
      document.querySelectorAll('.step-branch .step.loop, .step-branch .step.logic_loop:not(.loop_broken)').forEach(el => {

        // the step-branch.benchmarks container
        let stepId = el.dataset.id
        let targetStepId = Funnel.steps.find(s => s.ID == stepId).meta.next

        if (!targetStepId || typeof targetStepId == 'undefined' || targetStepId == 0) {
          return
        }

        let targetStep = document.getElementById(`step-${ targetStepId }`)

        loopLine( el, targetStep )

      })
    }
    catch (e) {}

    /**
     * Finds the centerpoint coordinate of a node
     *
     * @param node
     */
    const centerPointPos = ( node ) => {
      const { left, width } = node.getBoundingClientRect()
      return left + ( width / 2 )
    }

    /**
     * Determines if two nodes overlap vertically
     *
     * @param a
     * @param b
     * @returns {boolean}
     */
    function horizontallyOverlaps(a, b) {
      const aRect = a.getBoundingClientRect()
      const bRect = b.getBoundingClientRect()

      return (
        aRect.left < bRect.right &&
        aRect.right > bRect.left
      )
    }

    /**
     * ①
     * |
     * -->--
     *     |
     *     ②
     *
     * @param from
     * @param to
     */
    const bottomToTopLine = (from, to) => {

      // todo

      let line2 = Div({ className: `logic-line jump-line bottom-to-top-line bottom-to-top-line-${ from.dataset.id }-to-${ to.dataset.id }--2` })
      let line1 = Div({ className: `logic-line jump-line bottom-to-top-line bottom-to-top-line-${ from.dataset.id }-to-${ to.dataset.id }--1` }, line2 )

      from.parentNode.querySelector( `:scope > .bottom-to-top-line-${ from.dataset.id }-to-${ to.dataset.id }--1` )?.remove()
      from.parentNode.append(line1)

      let fromPos = from.getBoundingClientRect()
      let fromContainerPos = from.parentNode.getBoundingClientRect()
      let toPos = to.getBoundingClientRect()

      let fromCenter = fromPos.left + fromPos.width / 2
      let toCenter = toPos.left + toPos.width / 2

      let abovePos, belowPos, going

      if ( fromPos.top > toPos.top ) {
        abovePos = toPos
        belowPos = fromPos
        going = 'up'
      } else {
        abovePos = fromPos
        belowPos = toPos
        going = 'down'
      }

      let lineHeight = Math.abs(abovePos.bottom - belowPos.top) / 2
      let lineWidth = Math.abs(fromCenter - toCenter) / 2

      clearLineStyle(line1)
      clearLineStyle(line2)

      let positionOffset = `calc(50% - 1px)`

      line1.style.width = `${ lineWidth }px`
      line1.style.height = `${ lineHeight }px`

      line2.style.width = `${ lineWidth }px`
      line2.style.height = `${ going === 'down' ? lineHeight - elementDistance( to.querySelector('.hndle-icon'), to, 'top', 'top' ) - 10: lineHeight - 10 }px`

      // ↑
      if ( going === 'up' ) {

        line1.style.bottom = `${ fromContainerPos.bottom - fromPos.top }px`
        line2.style.bottom = '100%'

        let arrow = JumpArrow('up')
        line2.append( arrow )

        arrow.style.bottom = `100%`

        //    ②
        //    ↑ :: line 2
        // ----
        // ↑ :: line1
        // ①
        if (fromCenter < toCenter) {

          arrow.style.right = `-1px`
          arrow.style.transform = `translateX(50%)`

          line1.style.left = positionOffset

          line1.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line1.style.borderTopLeftRadius = borderRadius

          line2.style.left = '100%'
          line2.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line2.style.borderBottomRightRadius = borderRadius

        }
        // ②
        // ↑ :: line 2
        // ----
        //    ↑ :: line 1
        //    ①
        else {

          arrow.style.left = `-1px`
          arrow.style.transform = `translateX(-50%)`

          line1.style.right = positionOffset

          line1.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line1.style.borderTopRightRadius = borderRadius

          line2.style.right = '100%'
          line2.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line2.style.borderBottomLeftRadius = borderRadius

        }

      }
      // ↓
      else {

        line1.style.top = `${ fromPos.bottom - fromContainerPos.top }px`
        line2.style.top = '100%'

        let arrow = JumpArrow('down')
        line2.append( arrow )

        arrow.style.top = `calc(100% - 3px)`

        //    ①
        //    ↓ :: line1
        // ----
        // ↓ :: line2
        // ②
        if (fromCenter > toCenter) {

          arrow.style.left = `-1px`
          arrow.style.transform = `translateX(-50%)`

          line1.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line1.style.borderBottomRightRadius = borderRadius
          line1.style.right = positionOffset

          line2.style.right = '100%'
          line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line2.style.borderTopLeftRadius = borderRadius

        }
        // ①
        // ↓ :: line1
        // ----
        //    ↓ :: line2
        //    ②
        else {

          arrow.style.right = `-1px`
          arrow.style.transform = `translateX(50%)`

          line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line1.style.borderBottomLeftRadius = borderRadius
          line1.style.left = positionOffset

          line2.style.left = '100%'
          line2.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line2.style.borderTopRightRadius = borderRadius

        }
      }
    }

    /**
     * ① ->-
     *     |
     *     ->-②
     *
     * @param from
     * @param to
     */
    const leftToRightLine = (from, to) => {

      let line2 = Div({ className: `logic-line jump-line left-to-right-line left-to-right-line-${ from.dataset.id }-to-${ to.dataset.id }--2` })
      let line1 = Div({ className: `logic-line jump-line left-to-right-line left-to-right-line-${ from.dataset.id }-to-${ to.dataset.id }--1` }, line2 )

      from.parentNode.querySelector( `:scope > .left-to-right-line-${ from.dataset.id }-to-${ to.dataset.id }--1` )?.remove()
      from.parentNode.append(line1)

      let fromPos = from.getBoundingClientRect()
      let fromContainerPos = from.parentNode.getBoundingClientRect()
      let toPos = to.getBoundingClientRect()

      let fromCenter = fromPos.top + fromPos.height / 2
      let toCenter = toPos.top + toPos.height / 2

      let leftPos, rightPos, going

      if ( fromPos.left > toPos.left ) {
        leftPos = toPos
        rightPos = fromPos
        going = 'left'
      } else {
        leftPos = fromPos
        rightPos = toPos
        going = 'right'
      }

      let lineHeight = Math.abs(fromCenter - toCenter ) / 2
      let lineWidth = Math.abs(rightPos.left - leftPos.right ) / 2

      clearLineStyle(line1)
      clearLineStyle(line2)

      line1.style.width = `${ lineWidth }px`
      line1.style.height = `${ lineHeight }px`

      line2.style.width = `${ lineWidth - 7 }px`
      line2.style.height = `${ lineHeight }px`

      // ←---
      if ( going === 'left' ) {

        let arrow = JumpArrow('left')
        line2.append( arrow )

        line1.style.left = `-${ lineWidth + 1 }px`
        line2.style.right = '100%'

        // ②-←-
        //     |
        //     -←-①
        if (fromCenter > toCenter) {

          arrow.style.transform = 'translateY(-50%)'
          arrow.style.top = '-2px'
          arrow.style.right = 'calc(100% - 2px)'

          line1.style.bottom = `${fromContainerPos.bottom - fromCenter}px`

          line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line1.style.borderBottomLeftRadius = borderRadius

          line2.style.bottom = '100%'
          line2.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line2.style.borderTopRightRadius = borderRadius

        }
        //     -←-①
        //     |
        // ②-←-
        else {

          arrow.style.transform = 'translateY(50%)'
          arrow.style.bottom = '0'
          arrow.style.right = `calc(100% - 2px)`

          // arrow.style.transform = 'translateY(50%)'
          // arrow.style.bottom = '0'

          line1.style.top = `${fromCenter - fromContainerPos.top}px`

          line1.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line1.style.borderTopLeftRadius = borderRadius

          line2.style.top = '100%'
          line2.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line2.style.borderBottomRightRadius = borderRadius

        }

      }
      // ---→
      else {

        let arrow = JumpArrow('right')
        line2.append( arrow )

        line1.style.right = `-${ lineWidth + 1 }px`
        line2.style.left = '100%'

        //     -→-②
        //     |
        // ①-→-
        if (fromCenter > toCenter) {

          arrow.style.transform = 'translateY(-50%)'
          arrow.style.top = '-2px'
          arrow.style.left = `calc(100% - 2px)`

          line1.style.bottom = `${fromContainerPos.bottom - fromCenter}px`

          line1.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line1.style.borderBottomRightRadius = borderRadius

          line2.style.bottom = '100%'
          line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line2.style.borderTopLeftRadius = borderRadius

        }
        // ①-→-
        //     |
        //     -→-②
        else {

          arrow.style.transform = 'translateY(50%)'
          arrow.style.bottom = '0'
          arrow.style.left = `calc(100% - 2px)`

          line1.style.top = `${fromCenter - fromContainerPos.top}px`

          line1.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line1.style.borderTopRightRadius = borderRadius

          line2.style.top = '100%'
          line2.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line2.style.borderBottomLeftRadius = borderRadius

        }
      }

    }

    /**
     * Draws a line between two nodes anywhere in the flow
     *
     * IF both nodes are inline, use a `loopLine()` or `skipLine()` depending if $from is before or after $to
     *
     * ①-->--
     *       |
     *       ↓
     *       |
     * ②--<--
     *
     * ELSE IF the nodes rects overlap, use top/bottom ports
     *
     * ①
     * |
     * -->--
     *     |
     *     ②
     *
     * ELSE IF no overlap, use left/right port
     *
     * ① ->-
     *     |
     *     ->-②
     *
     * @param from
     * @param to
     */
    const goToLine = ( from, to ) => {

      let fromPos = from.getBoundingClientRect()
      let toPos = to.getBoundingClientRect()

      // check if inline
      if ( areNumbersClose( centerPointPos( from ), centerPointPos( to ), 1 ) ) {

        if ( fromPos.top < toPos.top ) {
          skipLine( from, to )
          return
        }

        loopLine( from, to)
        return
      }

      // check if overlapping, use top/bottom ports
      if ( horizontallyOverlaps( from, to ) ) {
        bottomToTopLine( from, to)
        return
      }

      // otherwise use left/right ports
      leftToRightLine(from, to)
    }


    // skips
    try {
      document.querySelectorAll('.step-branch .step.skip, .step-branch .step.logic_skip:not(.loop_broken)').forEach(step => {

        // the step-branch.benchmarks container
        let stepId = step.dataset.id
        let targetStepId = Funnel.steps.find(s => s.ID == stepId).meta.next

        if (!targetStepId || typeof targetStepId == 'undefined' || targetStepId == 0) {
          return
        }

        let targetStep = document.getElementById(`step-${ targetStepId }`)

        skipLine(step, targetStep)
      })
    }
    catch (e) {}

    // Jump
    try {
      document.querySelectorAll('.step-branch .step.logic_jump:not(.loop_broken)').forEach(step => {

        // the step-branch.benchmarks container
        let stepId = step.dataset.id
        let targetStepId = Funnel.steps.find(s => s.ID == stepId).meta.next

        if (!targetStepId || typeof targetStepId == 'undefined' || targetStepId == 0) {
          return
        }

        let targetStep = document.getElementById(`step-${ targetStepId }`)

        goToLine(step, targetStep)
      })
    } catch (e) {
      console.warn(e)
    }

    // stops
    try {
      document.querySelectorAll('.step-branch .step.logic_stop').forEach(step => {

        // the step-branch.benchmarks container
        let stepId = step.dataset.id
        let stepPos = step.getBoundingClientRect()
        let sortablePos = step.parentElement.getBoundingClientRect()

        let line = step.parentElement.querySelector('.logic-line.line-end')
        clearLineStyle(line)

        line.style.top = `${ stepPos.bottom - sortablePos.top }px`
        line.style.height = '30px'
        line.style.width = `${ stepPos.width / 2 }px`
        line.style.left = 'calc(50% - 1px)'
        line.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
        line.style.borderRadius = `0 0 0 ${ borderRadius }`
      })
    }
    catch (e) {}

    // timer skips
    try {
      document.querySelectorAll('.step-branch .step.timer_skip').forEach(step => {

        let stepId = step.dataset.id

        let timers = Funnel.steps.find(s => s.ID == stepId).meta.timers

        if (!timers || !timers.length) {
          return
        }

        timers.forEach((targetStepId, i) => {

          if (!targetStepId || typeof targetStepId == 'undefined') {
            return
          }

          let targetStep = document.getElementById(`step-${ targetStepId }`)

          skipLine(step, targetStep, 20 * i)
        })

      })
    }
    catch (e) { console.log(e) }

    $(document).trigger('draw-logic-lines')

  }

  function selectText (node) {

    if (document.body.createTextRange) {
      const range = document.body.createTextRange()
      range.moveToElementText(node)
      range.select()
    }
    else if (window.getSelection) {
      const selection = window.getSelection()
      const range = document.createRange()
      range.selectNodeContents(node)
      selection.removeAllRanges()
      selection.addRange(range)
    }
    else {
      console.warn('Could not select text in node: Unsupported browser.')
    }
  }

  $(document).on('dblclick', '.step.settings table code,.step.settings table pre', e => {
    selectText(e.currentTarget)
    navigator.clipboard.writeText(e.currentTarget.innerText)
    dialog({
      message: 'Copied to clipboard!',
    })
  })

  $(document).on('click', '.step.settings input.copy-text,.step.settings textarea.copy-text', e => {
    e.currentTarget.select()
    navigator.clipboard.writeText(e.currentTarget.value)
    dialog({
      message: 'Copied to clipboard!',
    })
  })

  window.addEventListener('resize', drawLogicLines)

  Groundhogg.drawLogicLines = drawLogicLines

} )(jQuery)
