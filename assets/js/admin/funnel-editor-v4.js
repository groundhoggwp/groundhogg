(function (Funnel, $) {

  const Tags = Groundhogg.stores.tags

  const Editor = {

    activeAddType: 'actions',
    view: 'addingStep',
    activeStep: {},
    htmlModules: {},
    isEditingTitle: false,
    stepFlowContextMenu: null,
    stepOpenInContextMenu: null,

    /**
     * These gets overridden by the Funnel object passed in
     */
    funnel: {
      ID: 0,
      data: {},
      steps: []
    },

    /**
     * Copy of the original funnel to compare changes to
     */
    origFunnel: {
      ID: 0,
      data: {},
      steps: []
    },

    stepTypes: {},

    /**
     * Keep track of all the previous states of the funnel
     */
    undoStates: [],
    redoStates: [],

    stepErrors: {},
    funnelErrors: [],

    htmlTemplates: {
      funnelTitleEdit (title, isEditing) {
        //language=HTML
        if (isEditing) {
          return `<input type="text" class="funnel-title-edit" name="funnel_title" value="${title}">`
        } else {
          return `<span class="title-inner">${title}</span><span class="pencil"><span class="dashicons dashicons-edit"></span></span>`
        }
      },
      stepEditPanel (step) {

        const { ID, data, meta } = step
        const { step_type, step_title, step_group } = data

        let hasErrors = false
        let errors = []

        if (Editor.stepErrors.hasOwnProperty(ID) && Editor.stepErrors[ID].length > 0) {
          hasErrors = true
          errors = Editor.stepErrors[ID]
        }

        //language=HTML
        return `
			${hasErrors ?
				`<div class="step-errors">
                <ul>
                    ${errors.map(error => `<li class="step-error"><span class="dashicons dashicons-warning"></span> ${error}</li>`).join('')}
                </ul>
            </div>` : ''}
			<div class="step-edit">
				<div class="settings">
					${Editor.stepTypes[step_type].edit(step)}
				</div>
				<div class="actions-and-notes">
					<div class="panel">
						<label class="row-label"><span class="dashicons dashicons-admin-comments"></span> Notes</label>
						<textarea rows="4" id="step-notes" class="notes full-width"
						          name="step_notes">${step.meta.step_notes || ''}</textarea>
					</div>
				</div>
			</div>`
      },
      stepAddPanel (activeType) {
        //language=HTML
        return `
			<div class="step-add">
				<div class="type-select">
					<button class="select-type actions ${activeType === 'actions' && 'active'}" data-type="actions">
						${'Actions'}
					</button>
					<button class="select-type benchmarks ${activeType === 'benchmarks' && 'active'}"
					        data-type="benchmarks">
						${'Benchmarks'}
					</button>
				</div>
				<div class="types">
					${Object.values(Editor.stepTypes).filter(step => step.group + 's' === activeType).map(Editor.htmlTemplates.addStepCard).join('')}
				</div>
			</div>`
      },
      stepTypeSelect (type) {
        //language=HTML
        return `
			<div class="type-select">
				<button class="select-type actions ${type === 'actions' && 'active'}" data-type="actions">
					${'Actions'}
				</button>
				<button class="select-type benchmarks ${type === 'benchmarks' && 'active'}" data-type="benchmarks">
					${'Benchmarks'}
				</button>
			</div>`
      },
      addStepCard (step) {
        //language=HTML
        return `
			<div class="add-step ${step.type} ${step.group}" data-type="${step.type}" data-group="${step.group}"
			     title="${step.name}">
				${Editor.stepTypes[step.type].hasOwnProperty('svg') ? `<div class="step-icon-svg">${Editor.stepTypes[step.type].svg}</div>` : `<img alt="${Editor.stepTypes[step.type].name}" class="step-icon"
				     src="${Editor.stepTypes[step.type].icon}"/>`}
				<p>${step.name}</p></div>`
      },
      stepFlowCard (step, activeStep) {

        const { ID, data, meta } = step
        const { step_type, step_title, step_group, step_order } = data

        const origStep = Editor.origFunnel.steps.find(s => s.ID === ID)
        let status
        let hasErrors = false

        if (Editor.stepErrors.hasOwnProperty(ID) && Editor.stepErrors[ID].length > 0) {
          status = 'config-error'
          hasErrors = true
        } else if (origStep && !objectEquals(step, origStep)) {
          status = 'edited'
        } else if (!origStep) {
          status = 'new'
        }

        const nextStep = Editor.funnel.steps.find(step => step.data.step_order === step_order + 1)
        const prevStep = Editor.funnel.steps.find(step => step.data.step_order === step_order - 1)

        //language=HTML
        return `
			${step_group === 'benchmark' ?
				(step_order === 1 ?
					`<div class="text-helper until-helper"><span class="dashicons dashicons-filter"></span> Start the funnel when...</div>`
					: (prevStep && prevStep.data.step_group !== 'benchmark' ? '<div class="until-helper text-helper">Until...</div>' : ''))
				: ''}
			<div
				class="step ${step_type} ${step_group} ${activeStep === ID ? 'active' : ''} ${hasErrors ? 'has-errors' : ''}"
				data-id="${ID}">
				${Editor.stepTypes[step_type].hasOwnProperty('svg') ? `<div class="icon-svg">${Editor.stepTypes[step_type].svg}</div>` : `<img alt="${Editor.stepTypes[step_type].name}" class="icon"
				     src="${Editor.stepTypes[step_type].icon}"/>`}
				<div class="details">
					<div class="step-title">${Editor.stepTypes[step_type].title(step)}</div>
					<div class="step-type">${Editor.stepTypes[step_type].name}</div>
				</div>
				<div class="step-status ${status}"><span class="status"></span>
				</div>
			</div>
			${step_group === 'benchmark' && nextStep ? (nextStep.data.step_group === 'benchmark' ? `<div class="or-helper text-helper">Or...</div>` : '<div class="then-helper text-helper">Then...</div>') : ''}
        `
      }
    },

    init () {

      var self = this
      var $doc = $(document)

      // Copy from the orig included data
      // self.funnel = copyObject(self.funnel)
      self.origFunnel = copyObject(self.funnel)

      $doc.on('click', '.step-add .select-type', function () {
        self.saveUndoState()
        self.activeAddType = $(this).data('type')
        self.renderStepAdd()
      })

      $doc.on('click', '.step-flow .steps .step', function () {
        self.saveUndoState()
        self.previousActiveStep = self.activeStep
        self.activeStep = $(this).data('id')
        self.view = 'editingStep'
        self.renderStepFlow()
        self.renderStepEdit()
      })

      $doc.on('click', '.step-flow .add-new-step', function () {
        self.activeStep = null
        self.view = 'addingStep'
        self.renderStepFlow()
        self.renderStepAdd()
      })

      $doc.on('click', '.undo-and-redo .undo', function () {
        self.undo()
      })

      $doc.on('click', '.undo-and-redo .redo', function () {
        self.redo()
      })

      $doc.on('click', '.header-stuff .title', function () {
        if (!self.isEditingTitle) {
          self.isEditingTitle = true
          self.renderTitle()
        }
      })

      $(document).on('tinymce-editor-setup', function (event, editor) {
        editor.settings.toolbar1 = 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,spellchecker,wp_adv,dfw,groundhoggreplacementbtn,groundhoggemojibtn'
        editor.settings.toolbar2 = 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
        editor.settings.height = 200
        editor.on('click', function (ed, e) {
          $(document).trigger('to_mce')
        })
      })

      $doc.on('blur change keydown', '.funnel-title-edit', function (e) {

        // If the event is key down do nothing if the key wasn't enter
        if (e.type === 'keydown' && e.keyCode !== 13) {
          return
        }

        self.saveUndoState()
        self.funnel.data.title = e.target.value
        self.isEditingTitle = false
        self.autoSaveEditedFunnel()
        self.renderTitle()
      })

      Tags.preloadTags()

      if (this.funnel.meta.edited) {
        this.funnel.steps = this.funnel.meta.edited.steps
        this.funnel.data.title = this.funnel.meta.edited.title
      }

      self.setupSortable()
      self.setupStepTypes()
      self.initStepFlowContextMenu()

      self.render()
    },

    /**
     * Init the sortable list of steps in the step flow
     */
    setupSortable () {
      var self = this
      $('.step-flow .steps').sortable({
        placeholder: 'step-placeholder',
        cancel: '.text-helper',
        start: function (e, ui) {
          ui.placeholder.height(ui.item.height())
          ui.placeholder.width(ui.item.width())
        },
        receive: function (e, ui) {

          console.log('received', ui)

          self.saveUndoState()

          var type = $(ui.helper).data('type')
          var group = $(ui.helper).data('group')

          var id = Date.now()

          $(ui.helper).addClass('step')
          $(ui.helper).data('id', id)

          self.addStep({
            ID: id,
            data: {
              ID: id,
              step_type: type,
              step_group: group,
              step_order: $(ui.helper).prevAll('.step').length
            },
            meta: StepTypes.getType(type).defaults
          })
        },
        update: function (e, ui) {

          console.log('updated', ui)

          self.saveUndoState()
          self.syncOrderWithFlow()
          self.autoSaveEditedFunnel()
          self.renderStepFlow()
        }
      }).disableSelection()
    },

    /**
     * Merge the step types passed from PHP with methods defined in JS
     */
    setupStepTypes () {
      for (var prop in this.stepTypes) {
        if (Object.prototype.hasOwnProperty.call(this.stepTypes, prop)
          && Object.prototype.hasOwnProperty.call(StepTypes, prop)) {
          Object.assign(this.stepTypes[prop], {
            ...StepTypes.default,
            ...StepTypes[prop]
          })
        } else {
          Object.assign(this.stepTypes[prop], StepTypes.default)
        }
      }
    },

    /**
     * Setup the context menu for editing duplicating and deleting steps
     */
    initStepFlowContextMenu () {

      var self = this

      this.stepFlowContextMenu = createContextMenu({
        menuClassName: 'step-context-menu',
        targetSelector: '.step-flow .steps .step',
        items: [
          { key: 'duplicate', text: 'Duplicate' },
          { key: 'delete', text: 'Delete' },
        ],
        onOpen (e, el) {
          self.stepOpenInContextMenu = parseInt(el.dataset.id)
        },
        onSelect (key) {
          switch (key) {
            case 'delete':

              self.deleteStep(self.stepOpenInContextMenu)
              break
            case 'duplicate':

              const stepToCopy = self.funnel.steps
                .find(step => step.ID === self.stepOpenInContextMenu)

              const newStep = copyObject(stepToCopy)
              newStep.ID = uniqid()
              self.addStep(newStep)

              break
          }
        }
      })

      this.stepFlowContextMenu.init()
    },

    /**
     * Renders the step flow
     */
    renderStepFlow () {

      var self = this

      this.checkForStepErrors()

      var steps = this.funnel.steps
        .sort((a, b) => a.data.step_order - b.data.step_order)
        .map(step => self.htmlTemplates.stepFlowCard(step, self.activeStep))
        .join('')

      $('.step-flow .steps').html(steps)
    },

    checkForStepErrors () {

      const self = this

      this.funnel.steps.forEach(step => {

        const errors = []

        self.stepErrors[step.ID] = errors

        const { step_group, step_order, step_type } = step.data

        if (step_group === 'action' && step_order === 1) {
          errors.push(
            'Actions cannot be at the start of a funnel.'
          )
        }

        const typeHandler = StepTypes.getType(step_type)
        if (typeHandler) {
          typeHandler.validate(step, errors)
        }
      })

      console.log(self.stepErrors)
    },

    /**
     * Renders the edit step panel for the current step in the controls panel
     */
    renderStepEdit () {

      if (this.view !== 'editingStep') {
        return
      }

      // const activeElementId = document.activeElement.id

      const step = this.funnel.steps.find(step => step.ID === this.activeStep)
      const previousStep = this.funnel.steps.find(step => step.ID === this.previousActiveStep)

      if (previousStep) {
        this.stepTypes[previousStep.data.step_type].onDemount(previousStep)
      }

      $('#control-panel').html(this.htmlTemplates.stepEditPanel(step))

      this.stepTypes[step.data.step_type].onMount(step)
    },

    /**
     * Renders the add step panel for the current step in the controls panel
     */
    renderStepAdd () {

      if (this.view !== 'addingStep') {
        return
      }

      $('#control-panel').html(this.htmlTemplates.stepAddPanel(this.activeAddType))

      var self = this

      $('.add-step').draggable({
        connectToSortable: '.step-flow .steps',
        helper: 'clone',
        revert: 'invalid',
        revertDuration: 0,
      })
    },

    /**
     * Renders the funnel title edit component
     */
    renderTitle () {

      $('.header-stuff .title').html(
        this.htmlTemplates.funnelTitleEdit(
          this.funnel.data.title,
          this.isEditingTitle
        )
      )

      if (this.isEditingTitle) {
        $('.funnel-title-edit').focus().width((this.funnel.data.title.length + 1) + 'ch')
      }
    },

    /**
     * Re-render the whole editor
     */
    render () {
      this.renderTitle()
      this.renderStepFlow()
      this.renderStepAdd()
      this.renderStepEdit()
    },

    /**
     * Syncs the order of the steps in the state with that of the order which the steps appear in the flow
     */
    syncOrderWithFlow () {
      var self = this

      $('.step-flow .steps .step').each(function (i) {
        self.funnel.steps.find(step => step.ID === $(this).data('id')).data.step_order = i + 1
      })

      this.fixStepOrders()

      console.log('synced', self.funnel.steps.map(step => step.data))
    },

    /**
     * Saves the current state of the funnel for an undo slot
     */
    saveUndoState () {
      const {
        view,
        funnel,
        activeStep,
        activeAddType,
        isEditingTitle
      } = this

      this.undoStates.push({
        view,
        activeStep,
        isEditingTitle,
        activeAddType,
        funnel: copyObject(funnel)
      })
    },

    /**
     * Undo the previous change
     */
    undo () {
      var lastState = this.undoStates.pop()

      if (!lastState) {
        return
      }

      Object.assign(this, lastState)

      this.redoStates.push(lastState)

      this.render()
    },

    /**
     * Redo the previous change
     */
    redo () {
      var lastState = this.redoStates.pop()

      if (!lastState) {
        return
      }

      Object.assign(this, lastState)

      this.undoStates.push(lastState)

      this.render()
    },

    /**
     * Add a step to the funnel
     *
     * @param step
     */
    addStep (step) {

      console.log('add-step')

      if (!step) {
        return
      }

      this.saveUndoState()

      this.funnel.steps.push(step)

      this.fixStepOrders()
      this.renderStepFlow()

      this.autoSaveEditedFunnel()
    },

    fixStepOrders () {
      let newOrder = 1
      this.funnel.steps.sort((a, b) => a.data.step_order - b.data.step_order).forEach(step => {
        step.data.step_order = newOrder
        newOrder++
      })
    },

    /**
     * Delete a step from the funnel
     *
     * @param stepId
     */
    deleteStep (stepId) {

      if (!stepId) {
        return
      }

      console.log('delete-step')

      this.saveUndoState()

      this.funnel.steps = this.funnel.steps.filter(step => step.ID !== stepId)

      this.fixStepOrders()
      this.renderStepFlow()

      if (this.activeStep === stepId) {
        this.view = 'addingStep'
        this.renderStepAdd()
        this.activeStep = null
      }

      this.autoSaveEditedFunnel()
    },

    /**
     * Get the step object from the funnel
     *
     * @param stepId
     * @returns {*}
     */
    getStep (stepId) {
      return this.funnel.steps.find(step => step.ID === stepId)
    },

    /**
     * Get the currently active step
     *
     * @returns {*}
     */
    getCurrentStep () {
      return this.getStep(this.activeStep)
    },

    /**
     * Update a step
     *
     * @param stepId
     * @param newData
     */
    updateStep (stepId, newData) {

      const step = this.getStep(stepId)

      const newStep = {
        ...step,
        ...newData
      }

      console.log(newStep)

      var toReplace = this.funnel.steps.findIndex(step => step.ID === stepId)

      this.saveUndoState()

      if (toReplace !== -1) {
        this.funnel.steps[toReplace] = newStep
      }

      this.renderStepFlow()
    },

    /**
     * Updates the current active step
     *
     * @param newData
     */
    updateCurrentStep (newData) {
      this.updateStep(this.activeStep, newData)

      this.autoSaveEditedFunnel()
    },

    /**
     * Updates the current active step
     *
     * @param newMeta
     */
    updateCurrentStepMeta (newMeta) {

      const { meta } = this.getCurrentStep()

      this.updateStep(this.activeStep, {
        meta: {
          ...meta,
          ...newMeta
        }
      })

      this.autoSaveEditedFunnel()
    },

    autoSaveTimeout: null,

    autoSaveEditedFunnel () {
      var self = this

      if (this.autoSaveTimeout) {
        clearTimeout(this.autoSaveTimeout)
      }

      this.autoSaveTimeout = setTimeout(function () {
        apiPost(`${Groundhogg.endpoints.v4.funnels}/${self.funnel.ID}/meta`, {
          edited: {
            steps: self.funnel.steps,
            title: self.funnel.data.title
          }
        })
      }, 3000)

    },

    ...Funnel,
  }

  $(function () {
    Editor.init()
  })

  /**
   * Make a copy of the object
   *
   * @param object
   * @param initial
   * @returns {*}
   */
  function copyObject (object, initial) {
    initial = initial || {}
    return $.extend(true, initial, object)
  }

  /**
   * Whether 2 objects are equal
   *
   * @param a
   * @param b
   * @returns {boolean}
   */
  function objectEquals (a, b) {
    return JSON.stringify(a) === JSON.stringify(b)
  }

  /**
   * Fetch stuff from the API
   * @param route
   */
  async function apiFetch (route) {
    const response = fetch(route, {
      headers: {
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      }
    })

    return response.json()
  }

  /**
   * Post data
   *
   * @param url
   * @param data
   * @returns {Promise<any>}
   */
  async function apiPost (url = '', data = {}) {
    // Default options are marked with *
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': Groundhogg.nonces._wprest,
      },
      body: JSON.stringify(data) // body data type must match "Content-Type" header
    })
    return response.json() // parses JSON response into native JavaScript objects
  }

  /**
   *
   */
  function uniqid () {
    return Date.now()
  }

  function andList (array, text = 'and') {
    if (array.length === 1) {
      return array[0]
    }
    return `${array.slice(0, -1).join(', ')} ${text} ${array[array.length - 1]}`
  }

  function orList (array) {
    return andList(array, 'or')
  }

  function isString (string) {
    return typeof string === 'string'
  }

  /**
   * If it's not a string just return the value
   *
   * @param string
   * @returns {*}
   */
  const specialChars = (string) => {
    if (!isString(string)) {
      return string
    }

    return string.replace(/&/g, '&amp;').replace(/>/g, '&gt;').replace(/</g, '&lt;').replace(/"/g, '&quot;')
  }

  const Elements = {
    option: function (value, text, selected) {
      //language=HTML
      return `
		  <option value="${specialChars(value)}" ${selected ? 'selected' : ''}>${text}</option>`
    },
    inputWithReplacementsAndEmojis ({
      type = 'text',
      name,
      id,
      value,
      className,
      placeholder = ''
    }, replacements = true, emojis = true) {
      //language=HTML
      return `
		  <div class="input-wrap input-with-replacements input-with-emojis">
			  <input type="${type}" id="${id}" name="${name}" value="${specialChars(value) || ''}" class="${className}"
			         placeholder="${specialChars(placeholder)}">
			  ${emojis ? `<button class="emoji-picker-start" title="insert emoji"><span class="dashicons dashicons-smiley"></span>
			  </button>` : ''}
			  ${replacements ? `		  <button class="replacements-picker-start" title="insert replacement"><span
				  class="dashicons dashicons-admin-users"></span></button>` : ''}
		  </div>`
    },
    inputWithReplacements: function (atts) {
      return Elements.inputWithReplacementsAndEmojis(atts, true, false)
    },
    inputWithEmojis: function (atts) {
      return Elements.inputWithReplacementsAndEmojis(atts, false, true)
    },
    textAreaWithReplacementsAndEmojis: function ({ name, id, value }) {

    },
    textAreaWithReplacements: function ({ name, id, value }) {

    },
    textAreaWithEmojis: function ({ name, id, value }) {

    }
  }

  /**
   * Create a list of options
   *
   * @param options
   * @param selected
   * @returns {string}
   */
  const createOptions = (options, selected) => {

    const optionsString = []

    for (const option in options) {
      if (options.hasOwnProperty(option)) {
        optionsString.push(Elements.option(
          option, options[option],
          Array.isArray(selected)
            ? selected.indexOf(option) !== -1
            : option === selected))
      }
    }

    return optionsString.join('')
  }

  const tagWithConditionOnMount = (step) => {
    tagOnMount(step)

    $('#condition').change(function (e) {

      const { meta } = Editor.getCurrentStep()

      Editor.updateCurrentStep({
        meta: {
          ...meta,
          condition: $(this).val()
        }
      })
    })
  }

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

  const tagOnMount = (step) => {

    const $tags = $('#tags')
    $tags.select2({
      tags: true,
      multiple: true,
    })

    $tags.on('change', function (e) {

      const { meta } = Editor.getCurrentStep()

      const tags = []
      const newTags = []

      $(this).val().forEach(tag => {

        if (Tags.get(parseInt(tag))) {
          tags.push(parseInt(tag))
        } else {
          newTags.push(tag)
        }

      })

      if (newTags.length > 0) {
        Tags.validate($(this).val()).then(tags => {
          Editor.updateCurrentStep({
            meta: {
              ...meta,
              tags: tags.map(tag => tag.ID)
            }
          })
        })
      } else {
        Editor.updateCurrentStep({
          meta: {
            ...meta,
            tags
          }
        })
      }
    })
  }

  const delayTimerDefaults = {
    delay_amount: 3,
    delay_type: 'days',
    run_on_type: 'any',
    run_when: 'now',
    run_time: '',
    send_in_timezone: true,
    run_time_to: '',
    run_on_dow_type: 'any', // Run on days of week type
    run_on_dow: [], // Run on days of week
    run_on_month_type: 'any', // Run on month type
    run_on_months: [], // Run on months
    run_on_dom: [], // Run on days of month
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
    run_on_dow, // Run on days of week
    run_on_month_type, // Run on month type
    run_on_months, // Run on months
    run_on_dom, // Run on days of month
  }) => {

    const preview = []

    // Deal with the easiest cases first
    if (delay_type === 'none' && run_on_type === 'any') {
      switch (run_when) {
        default:
        case 'now':
          return `Run at any time`
        case 'later':
          return `Run at <b>${run_time}</b>`
        case 'between':
          return `Run between <b>${run_time}</b> and <b>${run_time_to}</b>`
      }
    }

    if (delay_type !== 'none') {
      preview.push(`Wait at least <b>${delay_amount} ${delay_type}</b> and then`)
    }

    if (run_on_type !== 'any') {
      preview.push(preview.length > 0 ? 'run on' : 'Run on')
    }

    switch (run_on_type) {
      default:
      case 'any':
        // preview.push()
        break
      case 'weekday':
        preview.push('<b>a weekday</b>')
        break
      case 'weekend':
        preview.push('<b>a weekend</b>')
        break
      case 'day_of_week':

        let dowList = orList(run_on_dow.map(i => `<b>${i}</b>`))
        dowList = `${run_on_dow_type === 'any' ? `any ${dowList}` : `the ${run_on_dow_type} ${dowList}`}`

        if (run_on_month_type === 'specific') {
          preview.push(`${dowList} in ${orList(run_on_months.map(i => `<b>${i}</b>`))}`)
        } else {
          preview.push(`${dowList} of <b>any month</b>`)
        }

        break
      case 'day_of_month':

        const dayList = run_on_dom.length > 0
          ? `the ${orList(run_on_dom.map(i => `<b>${ordinal_suffix_of(i)}</b>`))}`
          : `<b>any day</b>`

        if (run_on_month_type === 'specific') {
          preview.push(`${dayList} in ${orList(run_on_months.map(i => `<b>${i}</b>`))}`)
        } else {
          preview.push(`${dayList} of <b>any month</b>`)
        }

        break
    }

    switch (run_when) {
      default:
      case 'now':
        preview.push(`at any time`)
        break
      case 'later':
        preview.push(`at <b>${run_time}</b>`)
        break
      case 'between':
        preview.push(`between <b>${run_time}</b> and <b>${run_time_to}</b>`)
        break
    }

    return preview.join(' ')
  }

  const StepTypes = {

    getType (type) {

      if (!this.hasOwnProperty(type)) {
        return this.default
      }

      return Object.assign({}, this.default, this[type])
    },

    /**
     * Step type default fallbacks
     */
    default: {
      title ({ ID, data, meta }) {
        return data.step_title
      },
      edit ({ ID, data, meta }) {
        //language=HTML
        return `
			<div class="panel"></div>`
      },
      onMount: function () {},
      onDemount: function () {},
      validate: function (step, errors) {},
      defaults: {}
    },

    apply_note: {
      title ({ meta }) {
        return 'Apply Note'
      },
      edit ({ meta }) {

        const { note_text } = meta

        //language=html
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="note_text">Add the following note the the contact...</label>
					<textarea id="note_text" name="note_text">${note_text || ''}</textarea>
				</div>
			</div>`
      },
      onMount () {
        wp.editor.initialize(
          'note_text',
          {
            tinymce: true,
            quicktags: true
          }
        )

        $('#note_next').on('change', function (e) {
          Editor.updateCurrentStepMeta({
            note_text: $(this).val()
          })
        })
      },
      onDemount () {
        wp.editor.remove(
          'note_text'
        )
      }
    },

    admin_notification: {
      defaults: {
        to: '{owner_email}',
        from: '{owner_email}',
        reply_to: '{email}',
        note_text: '',
      },
      title ({ meta }) {

        const { to } = meta

        return `Send notification to <b>${to}</b>`

      },
      edit ({ meta }) {
//language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="to">Send this notification to...</label>
					${Elements.inputWithReplacements({
						type: 'text',
						id: 'to',
						name: 'to',
						className: 'regular-text',
						value: meta.to || '{owner_email}'
					})}
					<p class="description">Comma separated list of emails addresses.</p>
				</div>
				<div class="row">
					<label class="row-label" for="from">This notification should be sent from...</label>
					${Elements.inputWithReplacements({
						type: 'text',
						id: 'from',
						name: 'from',
						className: 'regular-text',
						value: meta.from || '{owner_email}'
					})}
					<p class="description">A single email address which you'd like the notification to come from.</p>
				</div>
				<div class="row">
					<label class="row-label" for="reply-to">Replies should go to...</label>
					${Elements.inputWithReplacements({
						type: 'text',
						id: 'reply-to',
						name: 'reply_to',
						className: 'regular-text',
						value: meta.reply_to || '{email}'
					})}
					<p class="description">A single email address which replies to this notification should be sent
						to.</p>
				</div>
				<div class="row">
					<label class="row-label" for="subject">Subject line</label>
					${Elements.inputWithReplacementsAndEmojis({
						type: 'text',
						id: 'subject',
						name: 'subject',
						className: 'regular-text',
						value: meta.subject || 'Notification from "{full_name}"'
					})}
					<p class="description">The subject line of the notification.</p>
				</div>
				<div class="row">
					<textarea id="note_text" name="note_text">${specialChars(meta.note_text || '')}</textarea>
				</div>
			</div>`
      },
      onMount () {
        wp.editor.initialize(
          'note_text',
          {
            tinymce: true,
            quicktags: true
          }
        )
      },
      onDemount () {
        wp.editor.remove(
          'note_text'
        )
      }
    },

    /**
     * Account created
     */
    account_created: {

      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 35" xmlns="http://www.w3.org/2000/svg">
			  <g clip-path="url(#clip0)">
				  <path
					  d="M4.473 31.684l-.96-.284a1 1 0 00.96 1.284v-1zm25.5 0v1a1 1 0 00.959-1.284l-.96.284zm-12.75-15.166a6.083 6.083 0 01-6.084-6.084h-2a8.083 8.083 0 008.084 8.084v-2zm-6.084-6.084a6.083 6.083 0 016.084-6.083v-2a8.083 8.083 0 00-8.084 8.083h2zM5.431 31.968c1.59-5.368 6.297-9.2 11.792-9.2v-2c-6.471 0-11.894 4.505-13.71 10.632l1.918.568zm11.792-9.2c5.495 0 10.2 3.832 11.79 9.2l1.918-.568c-1.815-6.127-7.237-10.632-13.708-10.632v2zm-12.75 9.916h25.5v-2h-25.5v2zm12.75-28.333a6.05 6.05 0 013.04.813l1.002-1.731a8.05 8.05 0 00-4.042-1.082v2z"
					  fill="currentColor"/>
				  <path d="M34.223 11.85H20.057m7.083-7.082v14.166" stroke="currentColor" stroke-width="2"/>
			  </g>
			  <defs>
				  <clipPath id="clip0">
					  <path fill="#fff" transform="translate(.223 .518)" d="M0 0h34v34H0z"/>
				  </clipPath>
			  </defs>
		  </svg>
      `,

      // Title
      title ({ ID, data, meta }) {

        const roles = Editor.stepTypes.account_created.context.roles

        if (meta.role && meta.role.length === 1) {
          return `<b>${roles[meta.role[0]]}</b> is created`
        } else if (meta.role && meta.role.length > 1) {
          return `${orList(meta.role.map(role => `<b>${roles[role]}</b>`))} is created`
        } else {
          return 'User Created'
        }
      },

      // Edit
      edit ({ ID, data, meta }) {

        let options = []
        let roles = Editor.stepTypes.account_created.context.roles

        for (var role in roles) {
          if (Object.prototype.hasOwnProperty.call(roles, role)) {
            options.push(Elements.option(role, roles[role], meta.role?.indexOf(role) >= 0))
          }
        }

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="roles">Select user roles.</label>
					<select name="role" id="roles" multiple>
						${options.join('')}
					</select>
					<p class="description">Runs when a new user is created with any of the defined roles.</p>
				</div>
			</div>`
      },

      // On mount
      onMount () {

        const $roles = $('#roles')
        $roles.select2()
        $roles.on('change', function (e) {
          let roles = $(this).val()
          Editor.updateCurrentStep({
            meta: {
              role: roles
            }
          })
        })
      },
    },

    /**
     * Account created
     */
    create_user: {

      // Title
      title ({ ID, data, meta }) {

        const roles = Editor.stepTypes.account_created.context.roles

        if (meta.role) {
          return `Create <b>${roles[meta.role]}</b>`
        } else {
          return 'Create user'
        }
      },

      // Edit
      edit ({ ID, data, meta }) {

        let options = []
        let roles = Editor.stepTypes.account_created.context.roles

        for (var role in roles) {
          if (Object.prototype.hasOwnProperty.call(roles, role)) {
            options.push(Elements.option(role, roles[role], meta.role === role))
          }
        }

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="roles">Select the role of the new user.</label>
					<select name="role" id="role">
						${options.join('')}
					</select>
					<p class="description">Runs when a new user is created with any of the defined roles.</p>
				</div>
			</div>
			<div class="panel">
				<div class="row">
					<label for="disable-notification">
						<input type="checkbox" id="disable-notification" value="1"
						       ${meta.disable_notification ? 'checked' : ''}>
						Disable the account created notification sent to the user.</label>
				</div>
			</div>`
      },

      // On mount
      onMount () {

        const $role = $('#role')
        $role.select2()

        $role.on('change', function (e) {
          let role = $(this).val()
          Editor.updateCurrentStep({
            meta: {
              ...Editor.getCurrentStep().meta,
              role: role
            }
          })
        })

        $('#disable-notification').on('change', function (e) {

          var checked = this.checked

          Editor.updateCurrentStep({
            meta: {
              ...Editor.getCurrentStep().meta,
              disable_notification: checked
            }
          })
        })
      },
    },

    /**
     * Apply a tag
     */
    apply_tag: {

      ...this.default,

      title ({ ID, data, meta }) {

        if (!meta.tags) {
          return 'Apply tags'
        }

        return `Apply <b>${meta.tags.length}</b> ${meta.tags.length > 1 ? 'tags' : 'tag'}`
      },

      edit ({ ID, data, meta }) {

        let options = Tags.items
          .map(tag => Elements.option(tag.ID, tag.data.tag_name, meta.tags && meta.tags.indexOf(tag.ID) !== -1))

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">Select tags to add.</label>
					<select name="tags" id="tags" multiple>${options.join('')}</select>
					<p class="description">All of the defined tags will be added to the contact.</p>
				</div>
			</div>`
      },

      onMount (step) {
        tagOnMount(step)
      }
    },

    /**
     * Remove a tag
     */
    remove_tag: {

      title ({ ID, data, meta }) {

        if (!meta.tags) {
          return 'Remove tags'
        }

        return `Remove <b>${meta.tags.length}</b> ${meta.tags.length > 1 ? 'tags' : 'tag'}`
      },

      edit ({ ID, data, meta }) {

        let options = Tags.items
          .map(tag => Elements.option(tag.ID, tag.data.tag_name, meta.tags && meta.tags.indexOf(tag.ID) !== -1))

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">Select tags to remove.</label>
					<select name="tags" id="tags" multiple>${options.join('')}</select>
					<p class="description">All of the defined tags will be removed from the contact.</p>
				</div>
			</div>`
      },

      onMount (step) {
        tagOnMount(step)
      }
    },

    /**
     * When a tag is applied
     */
    tag_applied: {

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

        if (!meta.tags) {
          return 'Tag is applied'
        }

        const { tags, condition } = meta

        if (tags.length > 1) {
          return condition === 'all' ? `<b>${tags.length}</b> tags are applied` : `Any of <b>${tags.length}</b> tags are applied`
        } else if (tags.length === 1) {
          return `<b>${tags.length}</b> tag is applied`
        } else {
          return 'Tag is applied'
        }
      },

      edit ({ ID, data, meta }) {

        let options = Tags.items
          .map(tag => Elements.option(tag.ID, tag.data.tag_name, meta.tags && meta.tags.indexOf(tag.ID) !== -1))

        const { condition } = meta

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">When <select id="condition">
						<option value="any" ${condition === 'any' ? 'selected' : ''}>Any</option>
						<option value="all" ${condition === 'all' ? 'selected' : ''}>All</option>
					</select> of the defined tags are applied</label>
					<select name="tags" id="tags" multiple>${options.join('')}</select>
					<p class="description">Runs when ${condition || 'any'} of the provided tags are applied.</p>
				</div>
			</div>`
      },

      onMount (step) {
        tagWithConditionOnMount(step)
      }
    },

    /**
     * When a tag is remvoed
     */
    tag_removed: {

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

        if (!meta.tags) {
          return 'Tag is removed'
        }

        const { tags, condition } = meta

        if (tags.length > 1) {
          return condition === 'all' ? `<b>${tags.length}</b> tags are removed` : `Any of <b>${tags.length}</b> tags are removed`
        } else if (tags.length === 1) {
          return `<b>${tags.length}</b> tag is removed`
        } else {
          return 'Tag is removed'
        }
      },

      edit ({ ID, data, meta }) {

        let options = Tags.items
          .map(tag => Elements.option(tag.ID, tag.data.tag_name, meta.tags && meta.tags.indexOf(tag.ID) !== -1))

        const { condition } = meta

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<label class="row-label" for="tags">When <select id="condition">
						<option value="any" ${condition === 'any' ? 'selected' : ''}>Any</option>
						<option value="all" ${condition === 'all' ? 'selected' : ''}>All</option>
					</select> of the defined tags are removed</label>
					<select name="tags" id="tags" multiple>${options.join('')}</select>
					<p class="description">Runs when ${condition || 'any'} of the provided tags are removed.</p>
				</div>
			</div>`
      },

      onMount (step) {
        tagWithConditionOnMount(step)
      }
    },

    delay_timer: {
      title ({ meta }) {
        return delayTimerName({
          ...delayTimerDefaults,
          ...meta
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
          ...meta
        }

        const delayTypes = {
          minutes: 'Minutes',
          hours: 'Hours',
          days: 'Days',
          weeks: 'Weeks',
          months: 'Months',
          years: 'Years',
          none: 'No delay',
        }

        const runOnTypes = {
          any: 'Any day',
          weekday: 'Weekday',
          weekend: 'Weekend',
          day_of_week: 'Day of week',
          day_of_month: 'Day of month',
        }

        const runWhenTypes = {
          now: 'Any time',
          later: 'Specific time',
        }

        if (delay_type === 'minutes' || delay_type === 'hours') {
          runWhenTypes.between = 'Between'
        }

        const runOnDOWTypes = {
          any: 'Any',
          first: 'First',
          second: 'Second',
          third: 'Third',
          fourth: 'Fourth',
          last: 'Last',
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

        const runOnDaysOfWeek = {
          monday: 'Monday',
          tuesday: 'Tuesday',
          wednesday: 'Wednesday',
          thursday: 'Thursday',
          friday: 'Friday',
          saturday: 'Saturday',
          sunday: 'Sunday',
        }

        const runOnMonths = {
          january: 'January',
          february: 'February',
          march: 'March',
          april: 'April',
          may: 'May',
          june: 'June',
          july: 'July',
          august: 'August',
          september: 'September',
          october: 'October',
          november: 'November',
          december: 'December',
        }

        //language=HTML
        const runOnMonthOptions = `
			<div style="margin-top: 10px"><select
				class="delay-input re-render"
				name="run_on_month_type">
				${createOptions(runOnMonthTypes, run_on_month_type)}</select>
				${run_on_month_type === 'specific' ? `<select class="select2" name="run_on_months" multiple>${createOptions(runOnMonths, run_on_months)}</select>` : ''}
			</div>`

        //language=HTML
        const daysOfWeekOptions = `
			<div style="margin-top: 10px"><select
				class="delay-input" name="run_on_dow_type">
				${createOptions(runOnDOWTypes, run_on_dow_type)}</select>
				<select class="select2" name="run_on_dow"
				        multiple>${createOptions(runOnDaysOfWeek, run_on_dow)}</select></div>
			${runOnMonthOptions}`

        //language=HTML
        const daysOfMonthOptions = `
			<div style="margin-top: 10px"><select class="select2"
			                                      name="run_on_dom"
			                                      multiple>${createOptions(runOnDaysOfMonth, run_on_dom)}</select></div>
			${runOnMonthOptions}`

        //language=HTML
        return `
			<div class="panel">
				<div class="row">
					<h3 class="delay-preview" style="font-weight: normal">${delayTimerName({
						...meta,
						...delayTimerDefaults
					})}</h3>
				</div>
				<div class="row">
					<label class="row-label">Wait at least...</label>
					<input class="delay-input" type="number" name="delay_amount" value="${delay_amount || 3}"
					       placeholder="3"
					       ${delay_type === 'none' ? 'disabled' : ''}>
					<select class="delay-input re-render" name="delay_type">
						${createOptions(delayTypes, delay_type)}
					</select>
				</div>
				<div class="row">
					<label class="row-label">Then run on...</label>
					<select class="delay-input re-render" name="run_on_type">
						${createOptions(runOnTypes, run_on_type)}
					</select>
					${run_on_type === 'day_of_week' ? daysOfWeekOptions : ''}
					${run_on_type === 'day_of_month' ? daysOfMonthOptions : ''}
				</div>
				<div class="row">
					<label class="row-label">Then run at...</label>
					<select class="delay-input re-render" name="run_when">
						${createOptions(runWhenTypes, run_when)}
					</select>
					${run_when === 'later' ? `<input class="delay-input" type="time" name="run_time" value="${run_time}">` : ''}
					${run_when === 'between' ? `<input class="delay-input" type="time" name="run_time" value="${run_time}"> and <input class="delay-input" type="time" name="run_time_to" value="${run_time_to}">` : ''}
				</div>
			</div>`
      },

      onMount (step) {

        const updatePreview = () => {
          const { meta } = Editor.getCurrentStep()
          $('.delay-preview').html(delayTimerName({
            ...delayTimerDefaults,
            ...meta
          }))
        }

        $('.select2').select2().on('change', function (e) {
          console.log(e)
          Editor.updateCurrentStepMeta({
            [$(this).attr('name')]: $(this).val()
          })
          updatePreview()
        })

        $('.delay-input').on('change', function (e) {
          console.log(e)

          Editor.updateCurrentStepMeta({
            [e.target.name]: e.target.value
          })

          if (e.target.classList.contains('re-render')) {
            Editor.renderStepEdit()
            $(`[name=${e.target.name}]`).focus()
          } else {
            updatePreview()
          }
        })

        $('.delay-input').on('blur', function (e) {
          updatePreview()
        })

      },
    },

    /**
     * Send email
     */
    send_email: {

      title ({ ID, data, meta }) {
        return `Send Email`
      },
      edit ({ ID, data, meta }) {

        //language=HTML
        return `
			<div class="panel">

			</div>`
      }
    },

    link_click: {
      //language=HTML
      svg: `
		  <svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path d="M8.594 4.671v22.305l5.329-5.219 3.525 8.607 3.278-1.23-3.688-8.718h7.14L8.593 4.67z"
			        stroke="currentColor" stroke-width="2"/>
		  </svg>`
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
		  </svg>`
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
      title ({}) {
        return 'form'
      },
      edit ({ meta }) {
        //language=HTML
        return `
			<div id="edit-form"></div>
			<div class="panel">
				<div class=""></div>
			</div>`
      },
      onMount ({}) {
        const editor = FormBuilder(document.querySelector('#edit-form'),)
        editor.init()
      }
    }
  }
})(GroundhoggFunnel, jQuery)