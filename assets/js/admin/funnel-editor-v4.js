(function (Funnel, $) {

  const Tags = {

    limit: 100,
    offset: 0,
    items: [],

    get (id) {

    },

    validate (maybeTags, callback) {
      var self = this

      fetch(`${Groundhogg.endpoints.v4.tags}/validate`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': Groundhogg.nonces._wprest,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(maybeTags)
      }).then(r => r.json()).then(data => {

        if (!data.items) {
          return
        }

        data.items.forEach(tag => {
          if (self.items.findIndex(t => t.ID === tag.ID) === -1) {
            self.items.push(tag)
          }
        })

        callback(data.items)
      })
    },

    preloadTags () {

      var self = this

      fetch(`${Groundhogg.endpoints.v4.tags}?limit=${this.limit}&offset=${this.offset}`, {
        headers: {
          'X-WP-Nonce': Groundhogg.nonces._wprest,
        }
      }).then(r => r.json()).then(data => {

        if (!data.items) {
          return
        }

        Object.assign(self.items, data.items)

        if (data.items.length === self.limit) {
          self.offset += self.limit
          self.preloadTags()
        }

      })
    },

  }

  const Editor = {

    activeAddType: 'actions',
    view: 'addingStep',
    activeStep: {},
    htmlModules: {},
    isEditingTitle: false,
    stepFlowContextMenu: null,
    stepOpenInContextMenu: null,
    tagsCache: {
      limit: 100,
      offset: 0,
      items: []
    },

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
      stepEditPanel (step) {

        const { ID, data, meta } = step
        const { step_type, step_title, step_group } = data

        //language=HTML
        return `
			<div class="step-edit">
				${Editor.stepTypes[step_type].edit(step)}
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
				<img alt="${Editor.stepTypes[step_type].name}" class="icon"
				     src="${Editor.stepTypes[step_type].icon}"/>
				<div class="details">
					<div class="step-title">${Editor.stepTypes[step_type].title(step)}</div>
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
        update: function (e, ui) {
          self.saveUndoState()
          self.syncOrderWithFlow()
        }
      }).disableSelection()
    },

    /**
     * Merge the step types passed from PHP with mthods defined in JS
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

      var steps = this.funnel.steps
        .sort((a, b) => a.data.step_order - b.data.step_order)
        .map(step => self.htmlTemplates.stepFlowCard(step, self.activeStep))
        .join('')

      $('.step-flow .steps').html(steps)
    },

    /**
     * Renders the edit step panel for the current step in the controls panel
     */
    renderStepEdit () {

      if (this.view !== 'editingStep') {
        return
      }

      const step = this.funnel.steps.find(step => step.ID === this.activeStep)

      $('#control-panel').html(this.htmlTemplates.stepEditPanel(step))

      this.stepTypes[step.data.step_type].onMount()
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

      if (!step) {
        return
      }

      this.saveUndoState()

      this.funnel.steps.push(step)

      this.syncOrderWithFlow()
      this.renderStepFlow()

      this.autoSaveEditedFunnel()
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

      this.saveUndoState()

      this.funnel.steps = this.funnel.steps.filter(step => step.ID !== stepId)

      this.renderStepFlow()
      this.syncOrderWithFlow()

      this.view = 'addingStep'
      this.renderStepAdd()

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

    autoSaveEditedFunnel () {
      var self = this
      apiPost(`${Groundhogg.endpoints.v4.funnels}/${self.funnel.ID}/meta`, {
        edited: {
          steps: self.funnel.steps,
          title: self.funnel.data.title
        }
      })

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

  function andList (array) {
    var lastItem = array.pop()
    lastItem = 'and ' + lastItem
    array.push(lastItem)
    return array.join(', ')
  }

  function orList (array) {
    var lastItem = array.pop()
    lastItem = 'or ' + lastItem
    array.push(lastItem)
    return array.join(array.length > 2 ? ', ' : ' ')
  }

  const Elements = {
    option: function (value, text, selected) {
      //language=HTML
      return `
		  <option value="${value}" ${selected ? 'selected' : ''}>${text}</option>`
    }
  }

  const StepTypes = {
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
    },

    /**
     * Account created
     */
    account_created: {

      // Title
      title ({ ID, data, meta }) {

        const roles = Editor.stepTypes.account_created.context.roles

        if (meta.role && meta.role.length === 1) {
          return `${roles[meta.role[0]]} is created`
        } else if (meta.role && meta.role.length > 1) {
          return `${orList(meta.role.map(role => roles[role]))} is created`
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
          return `Create ${roles[meta.role]}`
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
			      <input type="checkbox" id="disable-notification" value="1" ${ meta.disable_notification ? 'checked' : '' }>
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

        $( '#disable-notification' ).on('change', function (e) {

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
      title ({ ID, data, meta }) {

        if (!meta.tags) {
          return 'Apply tags'
        }

        return `Apply ${meta.tags.length} ${meta.tags.length > 1 ? 'tags' : 'tag'}`
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

      onMount () {

        const $tags = $('#tags')
        $tags.select2({
          tags: true,
          multiple: true,
        })

        $tags.on('change', function (e) {

          Tags.validate($(this).val(), tags => {
            Editor.updateCurrentStep({
              meta: {
                tags: tags.map(tag => tag.ID)
              }
            })
          })
        })
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

        return `Remove ${meta.tags.length} ${meta.tags.length > 1 ? 'tags' : 'tag'}`
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

      onMount () {

        const $tags = $('#tags')
        $tags.select2({
          tags: true,
          multiple: true,
        })

        $tags.on('change', function (e) {

          Tags.validate($(this).val(), tags => {
            Editor.updateCurrentStep({
              meta: {
                tags: tags.map(tag => tag.ID)
              }
            })
          })
        })
      }
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
    }
  }
})(GroundhoggFunnel, jQuery)