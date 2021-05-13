(function (Funnel, $) {

  const Editor = {

    activeAddType: 'actions',
    view: 'add',
    activeStep: {},
    htmlModules: {},
    isEditingTitle: false,
    isAddingStep: false,
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

    htmlTemplates: {
      funnelTitleEdit (title, isEditing) {
        //language=HTML
        if (isEditing) {
          return `<input type="text" class="funnel-title-edit" name="funnel_title" value="${title}">`
        } else {
          return `<span class="title-inner">${title}</span><span class="pencil"><span class="dashicons dashicons-edit"></span></span>`
        }
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
			     title="${step.name}"><img alt="${step.name}" class="step-icon" src="${step.icon}">
				<p>${step.name}</p></div>`
      },
      stepFlowCard (step, activeStep) {

        const { ID, data, meta } = step
        const { step_type, step_title, step_group } = data

        const origStep = Editor.origFunnel.steps.find(s => s.ID === ID)
        let status

        if (origStep && !objectEquals(step, origStep)) {
          status = 'edited'
        } else if (!origStep) {
          status = 'new'
        }

        //language=HTML
        return `
			<div class="step ${step_type} ${step_group} ${activeStep === ID && 'active'}" data-id="${ID}">
				<img alt="${Editor.stepTypes[step_type].name}" class="icon" src="${Editor.stepTypes[step_type].icon}"/>
				<div class="details">
					<div class="step-title">${step_title}</div>
					<div class="step-type">${Editor.stepTypes[step_type].name}</div>
				</div>
				<div class="step-status ${status}"><span class="status"></span>
				</div>
			</div>`
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
        self.activeStep = $(this).data('id')
        self.renderStepFlow()
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

      $doc.on('blur change keydown', '.funnel-title-edit', function (e) {

        // If the event is key down do nothing if the key wasn't enter
        if (e.type === 'keydown' && e.keyCode !== 13) {
          return
        }

        self.saveUndoState()
        self.funnel.data.title = e.target.value
        self.isEditingTitle = false
        self.renderTitle()
      })

      $('.step-flow .steps').sortable({
        placeholder: 'step-placeholder',
        update: function (e, ui) {
          self.saveUndoState()
          self.syncOrderWithFlow()
        }
      }).disableSelection()

      self.initStepFlowContextMenu()

      self.render()
    },

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

    renderStepFlow () {

      var self = this

      var steps = this.funnel.steps
        .sort((a, b) => a.data.step_order - b.data.step_order)
        .map(step => self.htmlTemplates.stepFlowCard(step, self.activeStep))
        .join('')

      $('.step-flow .steps').html(steps)
    },

    renderStepAdd () {
      $('.panel').html(this.htmlTemplates.stepAddPanel(this.activeAddType))

      var self = this

      $('.add-step').draggable({
        connectToSortable: '.step-flow .steps',
        helper: 'clone',
        revert: 'invalid',
        revertDuration: 0,
        stop: function (e, ui) {

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
            },
            meta: {}
          })
        }
      })
    },

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

    render () {
      this.renderTitle()
      this.renderStepFlow()
      this.renderStepAdd()
    },

    /**
     * Syncs the order of the steps in the state with that of the order which the steps appear in the flow
     */
    syncOrderWithFlow () {
      var self = this

      $('.step-flow .steps .step').each(function (i) {
        self.funnel.steps.find(step => step.ID === $(this).data('id')).data.step_order = i + 1
      })
    },

    saveUndoState () {
      const { funnel, activeStep, activeAddType, view } = this

      this.undoStates.push({
        view,
        activeStep,
        activeAddType,
        funnel: copyObject(funnel)
      })
    },

    undo () {
      var lastState = this.undoStates.pop()

      if (!lastState) {
        return
      }

      const { funnel, activeStep, activeAddType, view } = lastState

      Object.assign(this, {
        funnel, activeStep, activeAddType, view
      })

      this.redoStates.push(lastState)

      this.render()
    },

    redo () {
      var lastState = this.redoStates.pop()

      if (!lastState) {
        return
      }

      const { funnel, activeStep, activeAddType, view } = lastState

      Object.assign(this, {
        funnel, activeStep, activeAddType, view
      })

      this.undoStates.push(lastState)

      this.render()
    },

    addStep (step) {

      if (!step) {
        return
      }

      this.saveUndoState()

      this.funnel.steps.push(step)

      this.syncOrderWithFlow()
      this.renderStepFlow()
    },

    deleteStep (stepId) {

      if (!stepId) {
        return
      }

      this.saveUndoState()

      this.funnel.steps = this.funnel.steps.filter(step => step.ID !== stepId)

      this.renderStepFlow()
      this.syncOrderWithFlow()
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
   * @returns {*}
   */
  function copyObject (object) {
    return $.extend(true, {}, object)
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

})(GroundhoggFunnel, jQuery)