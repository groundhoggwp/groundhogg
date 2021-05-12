(function (Funnel, $) {

  const Editor = {

    activeAddType: 'actions',
    view: 'add',
    activeStep: {},
    htmlModules: {},
    isEditingTitle: false,

    /**
     * These gets overridden by the Funnel object passed in
     */
    funnel: {
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
      stepFlowCard ({ ID, data, meta }, activeStep) {

        const { step_type, step_title, step_group } = data

        //language=HTML
        return `
			<div class="step ${step_type} ${step_group} ${activeStep === ID && 'active'}" data-id="${ID}">
				<img alt="${Editor.stepTypes[step_type].name}" class="icon" src="${Editor.stepTypes[step_type].icon}"/>
				<div class="details">
					<div class="step-title">${step_title}</div>
					<div class="step-type">${Editor.stepTypes[step_type].name}</div>
				</div>
				<div class="step-status edited"><span class="status"></span></div>
			</div>`
      }
    },

    init () {

      var self = this
      var $doc = $(document)

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
        if ( ! self.isEditingTitle ){
          self.isEditingTitle = true
          self.renderTitle()
        }
      })

      $doc.on('change blur', '.funnel-title-edit', function (e) {
        self.saveUndoState()
        self.funnel.data.title = e.target.value
      })

      $('.step-flow .steps').sortable({
        update: function (e, ui) {
          self.saveUndoState()
          self.syncOrderWithFlow()
        }
      }).disableSelection()

      self.render()
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
    },

    renderTitle () {

      $('.header-stuff .title').html(
        this.htmlTemplates.funnelTitleEdit(
          this.funnel.data.title,
          this.isEditingTitle
        )
      )

      if ( this.isEditingTitle ){
        $('.funnel-title-edit').focus()
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
        funnel,
        activeStep,
        activeAddType
      })
    },

    undo () {
      var lastState = this.undoStates.pop()

      if (!lastState) {
        return
      }

      const { funnel, activeStep, activeAddType, view } = lastState

      this.funnel = funnel
      this.activeStep = activeStep
      this.activeAddType = activeAddType
      this.view = view

      this.redoStates.push(lastState)

      this.render()
    },

    redo () {
      var lastState = this.redoStates.pop()

      if (!lastState) {
        return
      }

      const { funnel, activeStep, activeAddType, view } = lastState

      this.funnel = funnel
      this.activeStep = activeStep
      this.activeAddType = activeAddType
      this.view = view

      this.undoStates.push(lastState)

      this.render()
    },

    ...Funnel,
  }

  $(function () {
    Editor.init()
  })

})(GroundhoggFunnel, jQuery)