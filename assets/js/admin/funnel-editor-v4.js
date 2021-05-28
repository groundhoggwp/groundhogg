(function (Funnel, $) {

  const Tags = Groundhogg.stores.tags
  const apiGet = Groundhogg.api.get
  const apiPost = Groundhogg.api.post
  const apiRoutes = Groundhogg.api.routes.v4

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

  const slot = (name, ...args) => {
    return SlotFillProvider.slot(name, ...args)
  }

  const fill = (name, component) => {
    return SlotFillProvider.fill(name, component)
  }

  const slotsMounted = () => {
    return SlotFillProvider.slotsMounted()
  }

  const slotsDemounted = () => {
    return SlotFillProvider.slotsDemounted()
  }

  const SlotFillProvider = {

    fills: [],
    _slotsMounted: [],
    _slotsDemounted: [],

    /**
     * Render a slot name
     *
     * @param slotName
     * @param args
     * @returns {string}
     */
    slot (slotName, ...args) {
      this._slotsMounted.push({
        name: slotName,
        args: args
      })
      return this.fills.filter(fill => fill.slot === slotName).map(fill => fill.render(...args)).join('')
    },

    /**
     * Call this after any slots have been added to the DOM
     */
    slotsMounted () {
      let slot

      while (this._slotsMounted.length > 0) {
        // Get the next mounted slot
        slot = this._slotsMounted.pop()
        this.fills.filter(fill => fill.slot === slot.name).forEach(fill => {
          fill.onMount(...slot.args)
        })

        // After a slot has been mounted, remember it has been so it can be demounted later
        this._slotsDemounted.push(slot)
      }
    },

    /**
     * Any callbacks to demount a slot
     * Call before any slots are removed from the DOM
     */
    slotsDemounted () {
      let slot

      while (this._slotsDemounted.length > 0) {
        // get the next demounted slot
        slot = this._slotsDemounted.pop()
        this.fills.filter(fill => fill.slot === slot.name).forEach(fill => {
          fill.onDemount(...slot.args)
        })
      }
    },

    /**
     * Register a fill for a slot
     *
     * @param slot
     * @param component
     */
    fill (slot, component) {
      this.fills.push({
        slot,
        ...{
          render () {},
          onMount () {},
          onDemount () {},
          ...component
        }
      })
    }
  }

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
          return `<input type="text" class="funnel-title-edit regular-text" id="funnel-title-edit" name="funnel_title"
		                 value="${title}">`
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
					${slot('beforeStepSettings', step)}
					${Editor.stepTypes[step_type].edit(step)}
					${slot('afterStepSettings', step)}
				</div>
				<div class="actions-and-notes">
					${slot('beforeStepNotes', step)}
					<div class="panel">
						<label class="row-label"><span class="dashicons dashicons-admin-comments"></span> Notes</label>
						<textarea rows="4" id="step-notes" class="notes full-width"
						          name="step_notes">${specialChars(meta.step_notes || '')}</textarea>
					</div>
					${slot('afterStepNotes', step)}
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
				<div class="step-status ${status}"></div>
				<div class="step-menu">
					<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
					     fill-rule="evenodd" clip-rule="evenodd">
						<path
							d="M12 16a3.001 3.001 0 010 6 3.001 3.001 0 010-6zm0 1a2 2 0 11-.001 4.001A2 2 0 0112 17zm0-8a3.001 3.001 0 010 6 3.001 3.001 0 010-6zm0 1a2 2 0 11-.001 4.001A2 2 0 0112 10zm0-8a3.001 3.001 0 010 6 3.001 3.001 0 010-6zm0 1a2 2 0 11-.001 4.001A2 2 0 0112 3z"/>
					</svg>
					<ul>
						<li class="step-menu-edit">Edit</li>
						<li class="step-menu-duplicate">Duplicate</li>
						<li class="step-menu-delete">Delete</li>
					</ul>
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

      $doc.on('mouseleave', '.step-flow .steps .step', function (e) {
        const $step = $(this)
        $('.step-menu ul', $step).hide()
      })

      $doc.on('click', '.step-flow .steps .step', function (e) {

        const $step = $(this)

        switch (true) {
          case ($(e.target).is('.step-menu-duplicate')) :
            window.console.log('duplicate')
            const stepToCopy = self.funnel.steps
              .find(step => step.ID === parseInt($step.data('id')))

            const newStep = copyObject(stepToCopy)
            newStep.ID = uniqid()
            self.addStep(newStep)
            break
          case ($(e.target).is('.step-menu-delete')) :
            window.console.log('delete')
            self.deleteStep(parseInt($step.data('id')))
            break
          case ($(e.target).is('.step-menu') || $(e.target).parent('.step-menu').length > 0) :
            window.console.log('toggle menu')
            $('.step-menu ul', $step).toggle()
            break
          case ($(e.target).is('.step-menu-edit')) :
          default:
            window.console.log('edit')
            const clickedStep = parseInt($step.data('id'))

            if (clickedStep === self.activeStep) {
              return
            }

            self.saveUndoState()
            self.previousActiveStep = self.activeStep
            self.activeStep = clickedStep
            self.view = 'editingStep'
            self.renderStepFlow()
            self.renderStepEdit()
        }
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
          self.resizeTitleEdit()
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

          // console.log('received', ui)

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
              funnel_id: Editor.funnel.ID,
              step_type: type,
              step_group: group,
              step_order: $(ui.helper).prevAll('.step').length
            },
            meta: StepTypes.getType(type).defaults
          })
        },
        update: function (e, ui) {

          // console.log('updated', ui)

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

      // console.log('setup-step-types')

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

      // console.log(self.stepErrors)
    },

    /**
     * Renders the edit step panel for the current step in the controls panel
     */
    renderStepEdit () {

      if (this.view !== 'editingStep') {
        return
      }

      var self = this

      // const activeElementId = document.activeElement.id

      const step = this.funnel.steps.find(step => step.ID === this.activeStep)
      const previousStep = this.funnel.steps.find(step => step.ID === this.previousActiveStep)

      if (previousStep) {
        this.stepTypes[previousStep.data.step_type].onDemount(previousStep)
      }

      slotsDemounted()

      $('#control-panel').html(this.htmlTemplates.stepEditPanel(step))

      this.stepTypes[step.data.step_type].onMount(step)

      // Step notes listener
      $('#step-notes').on('change', function (e) {
        self.updateCurrentStepMeta({
          step_notes: $(this).val()
        })
      })

      slotsMounted()
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
        $('.funnel-title-edit').focus()
        this.resizeTitleEdit()
      }
    },

    resizeTitleEdit () {
      $('.funnel-title-edit').width((this.funnel.data.title.length + 1) + 'ch')
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

      // console.log('synced', self.funnel.steps.map(step => step.data))
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

      // console.log('add-step')

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

      // console.log('delete-step')

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
     * Get all the funnel steps
     *
     * @returns {[]}
     */
    getSteps () {
      return this.funnel.steps
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

      // console.log(newStep)

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

      console.log(this)

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
        apiPost(`${apiRoutes.funnels}/${self.funnel.ID}/meta`, {
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

  const kebabize = str => {
    return str.split('').map((letter, idx) => {
      return letter.toUpperCase() === letter
        ? `${idx !== 0 ? '-' : ''}${letter.toLowerCase()}`
        : letter
    }).join('')
  }

  const objectToStyle = (object) => {
    const props = []

    for (const prop in object) {
      if (object.hasOwnProperty(prop)) {
        props.push(`${kebabize(prop)}:${specialChars(object[prop])}`)
      }
    }

    return props.join(';')
  }

  /**
   * Convert an object of HTML props into a string
   *
   * @param object
   * @returns {string}
   */
  const objectToProps = (object) => {
    const props = []

    for (const prop in object) {
      if (object.hasOwnProperty(prop)) {

        switch (prop) {
          case 'className':
            props.push(`class="${specialChars(object[prop])}"`)
            break
          case 'style':
            props.push(`style="${specialChars(objectToStyle(object[prop]))}"`)
            break
          default:
            props.push(`${kebabize(prop)}="${specialChars(object[prop])}"`)
            break
        }
      }
    }

    return props.join(' ')
  }

  const Elements = {
    input (props) {
      props = {
        type: 'text',
        className: 'input',
        ...props
      }

      return `<input ${objectToProps(props)}/>`
    },
    select (props, options, selected) {
      return `<select ${objectToProps(props)}>${createOptions(options, selected)}</select>`
    },
    option: function (value, text, selected) {
      //language=HTML
      return `
		  <option value="${specialChars(value)}" ${selected ? 'selected' : ''}>${text}</option>`
    },
    mappableFields (props, selected) {
      return Elements.select(props, Groundhogg.fields.mappable, selected)
    },
    inputWithReplacementsAndEmojis ({
      type = 'text',
      name,
      id,
      value,
      className,
      placeholder = ''
    }, replacements = true, emojis = true) {
      const classList = [
        replacements && 'input-with-replacements',
        emojis && 'input-with-emojis'
      ]
      //language=HTML
      return `
		  <div class="input-wrap ${classList.filter(c => c).join()}">
			  <input type="${type}" id="${id}" name="${name}" value="${specialChars(value) || ''}" class="${className}"
			         placeholder="${specialChars(placeholder)}">
			  ${emojis ? `<button class="emoji-picker-start" title="insert emoji"><span class="dashicons dashicons-smiley"></span>
			  </button>` : ''}
			  ${replacements ? `<button class="replacements-picker-start" title="insert replacement"><span
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

    // Options is an array format
    if (Array.isArray(options)) {
      options.forEach(option => {
        optionsString.push(Elements.option(
          option, option,
          Array.isArray(selected)
            ? selected.indexOf(option) !== -1
            : option === selected))
      })
    }
    // Assume object
    else {
      for (const option in options) {
        if (options.hasOwnProperty(option)) {
          optionsString.push(Elements.option(
            option, options[option],
            Array.isArray(selected)
              ? selected.indexOf(option) !== -1
              : option === selected))
        }
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

    register (type, opts) {
      this[type] = {
        type: type,
        ...opts
      }
      // console.log('step-registered', type, opts, this)
    },

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
      onMount (step) {
        var self = this

        self.promiseController = new AbortController()
        const { signal } = self.promiseController

        apiPost(`${apiRoutes.steps}/html`, step, {
          signal
        }).then(r => {
          $('#dynamic-step-settings').html(r.html)
          $(document).trigger('gh-init-pickers')
          const $form = $('#settings-form')
          $form.on('submit', function (e) {
            e.preventDefault()
            return false
          }).on('change', function (e) {
            e.preventDefault()
            const meta = $(this).serializeFormJSON()
            // console.log(meta)
            Editor.updateCurrentStepMeta(meta)
          })
          self.promiseController = null
        }).catch(() => {})
      },
      onDemount () {
        if (this.promiseController) {
          this.promiseController.abort()
        }
      },
      validate: function (step, errors) {},
      defaults: {}
    },

    apply_note: {
      //language=HTML
      svg: `
		  <svg viewBox="0 0 42 37" fill="none" xmlns="http://www.w3.org/2000/svg">
			  <path d="M41.508 31.654h-10m5-5v10" stroke="currentColor" stroke-width="2"/>
			  <path
				  d="M27.508 11.988h1a1 1 0 00-.293-.708l-.707.708zm-7.084-7.084l.708-.707a1 1 0 00-.708-.293v1zm0 7.084h-1v1h1v-1zm7.084 17.416h-1 1zm-18.834 2h17.834v-2H8.674v2zm-2-25.5v23.5h2v-23.5h-2zm21.834 23.5V11.988h-2v17.416h2zm-8.084-25.5H8.674v2h11.75v-2zm7.79 7.376l-7.082-7.083-1.415 1.414 7.084 7.084 1.414-1.415zm-8.79-6.376v7.084h2V4.904h-2zm1 8.084h7.084v-2h-7.084v2zm-8.5 5.666h11.334v-2H11.925v2zm0 5.667h11.334v-2H11.925v2zm14.584 7.083a2 2 0 002-2h-2v2zm-17.834-2h-2a2 2 0 002 2v-2zm0-23.5v-2a2 2 0 00-2 2h2z"
				  fill="currentColor"/>
		  </svg>`,
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

        for (id in tinymce.editors) {
          if (id.trim()) {
            elementReady(id)
          }
        }

        // Wait for non-initialised editors to initialise
        tinymce.on('AddEditor', function (e) {
          elementReady(e.editor.id)
        })

        // function to call when tinymce-editor has initialised
        function elementReady (editor_id) {

          // get tinymce editor based on instance-id
          var _editor = tinymce.editors[editor_id]

          // Timer for saving on pause.
          let saveTimer = null

          _editor.on('keyup', function (e) {
            // Reset timer.
            clearTimeout(saveTimer)

            // Only save after a second.
            saveTimer = setTimeout(function () {
              Editor.updateCurrentStepMeta({
                note_text: tinyMCE.activeEditor.getContent({ format: 'raw' })
              })
            }, 1000)
          })
        }
      },
      onDemount () {
        wp.editor.remove(
          'note_text'
        )
      }
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
            options.push(Elements.option(role, roles[role], meta.role.indexOf(role) >= 0))
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
          // console.log(e)
          Editor.updateCurrentStepMeta({
            [$(this).attr('name')]: $(this).val()
          })
          updatePreview()
        })

        $('.delay-input').on('change', function (e) {
          // console.log(e)

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

  for (const func in Editor) {
    if (Editor.hasOwnProperty(func) && typeof func === 'function') {
      Editor[func] = Editor[func].bind(Editor)
    }
  }

  Groundhogg.helpers = {
    objectToProps,
    specialChars,
    isString,
  }
  Groundhogg.funnelEditor = Editor
  Groundhogg.funnelEditor.functions = {
    slot,
    fill,
    slotsDemounted,
    slotsMounted,
    getSteps () {
      return Editor.getSteps()
    },
    stepTitle (step) {
      return StepTypes.getType(step.data.step_type).title(step)
    },
    registerStepType (type, opts) {
      return StepTypes.register(type, opts)
    },
    updateCurrentStepMeta (newMeta) {
      return Editor.updateCurrentStepMeta(newMeta)
    },
    renderStepEdit () {
      return Editor.renderStepEdit()
    },
    getCurrentStep () {
      return Editor.getCurrentStep()
    }
  }

  Groundhogg.funnelEditor.elements = Elements

})(GroundhoggFunnel, jQuery)