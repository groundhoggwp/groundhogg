(function (Funnel, $) {

  const {
    tags: TagsStore,
    emails: EmailsStore,
    funnels: FunnelsStore,
    campaigns: CampaignsStore
  } = Groundhogg.stores

  const {
    get: apiGet,
    post: apiPost,
    delete: apiDelete,
    patch: apiPatch,
    routes
  } = Groundhogg.api

  const { v4: apiRoutes } = routes

  const {
    improveTinyMCE,
    dialog,
    confirmationModal,
    dangerConfirmationModal,
    loadingModal,
    modal,
    select,
    input,
    textarea,
    objectEquals,
    copyObject,
    uniqid,
    specialChars,
    regexp,
    tooltip,
    toggle,
    savingModal,
    moreMenu,
    clickInsideElement,
  } = Groundhogg.element

  const { campaignPicker } = Groundhogg.pickers

  const { StepTypes, StepPacks } = Groundhogg

  const { __, _x, _n, _nx } = wp.i18n

  const toEditorButton = () => {
    // language=HTML
    return `
		<button id="close-email-editor" class="gh-button secondary text icon">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 365.7 365.7">
				<path fill="currentColor"
				      d="M243.2 182.9L356.3 69.7a32 32 0 000-45.2l-15-15.1a32 32 0 00-45.3 0L182.9 122.5 69.7 9.4a32 32 0 00-45.2 0l-15.1 15a32 32 0 000 45.3L122.5 183 9.4 295.9a32 32 0 000 45.3l15 15.1a32 32 0 0045.3 0L183 243.2l113 113.1a32 32 0 0045.3 0l15.1-15a32 32 0 000-45.3zm0 0"/>
			</svg>
		</button>`
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

  const stepIsReal = (stepId) => {
    return Editor.origFunnel.steps.find((step) => step.ID === stepId)
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
        args: args,
      })
      return this.fills
        .filter((fill) => fill.slot === slotName)
        .map((fill) => fill.render(...args))
        .join('')
    },

    /**
     * Call this after any slots have been added to the DOM
     */
    slotsMounted () {
      let slot

      while (this._slotsMounted.length > 0) {
        // Get the next mounted slot
        slot = this._slotsMounted.pop()
        this.fills
          .filter((fill) => fill.slot === slot.name)
          .forEach((fill) => {
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
        this.fills
          .filter((fill) => fill.slot === slot.name)
          .forEach((fill) => {
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
          ...component,
        },
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

    /**
     * These gets overridden by the Funnel object passed in
     */
    funnel: {
      ID: 0,
      data: {},
      steps: [],
    },

    /**
     * Copy of the original funnel to compare changes to
     */
    origFunnel: {
      ID: 0,
      data: {},
      steps: [],
    },

    /**
     * Keep track of all the previous states of the funnel
     */
    undoStates: [],
    redoStates: [],

    stepErrors: {},
    stepWarnings: {},
    funnelErrors: [],

    htmlTemplates: {
      container () {
        //language=HTML
        return `
			<div id="funnel-editor" class="editor">
				<div class="editor-header">
					<div class="back-to-admin"></div>
					<div class="header-stuff">
						<div class="title-wrap">
						</div>
						<div class="header-actions">
							<div class="undo-and-redo"></div>
							<div class="publish-actions">
							</div>
						</div>
					</div>
				</div>
				<div class="flow-and-edit">
					<div class="step-flow">
						<div class="steps"></div>
						<div class="add-new-step-wrapper">
							<button class="gh-button secondary add-new-step">
								${_x('Add New Step', 'funnel editor', 'groundhogg')}
							</button>
						</div>
					</div>
					<div id="control-panel">
						<div class="step-add">
						</div>
					</div>
				</div>
			</div>`
      },
      undoRedoActions () {
        //language=HTML
        return `
			<div class="undo-and-redo">
				<button class="redo dashicon-button" ${Editor.redoStates.length ? '' : 'disabled'}><span
					class="dashicons dashicons-redo"></span></button>
				<button class="undo dashicon-button" ${Editor.undoStates.length ? '' : 'disabled'}><span
					class="dashicons dashicons-undo"></span></button>
			</div>`
      },
      publishActions (status) {

        // language=HTML
        const moreMenu = `
			<button id="more-menu" class="gh-button secondary text icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 384">
					<circle fill="currentColor" cx="192" cy="42.7" r="42.7"/>
					<circle fill="currentColor" cx="192" cy="192" r="42.7"/>
					<circle fill="currentColor" cx="192" cy="341.3" r="42.7"/>
				</svg>
			</button>`

        // Todo switch back
        if (status === 'inactive') {
          //language=HTML
          return `
			  <button class="gh-button action update-and-launch">${_x('Launch', 'funnel editor', 'groundhogg')}
				  <svg viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					  <path
						  d="M8.888 7.173a21.621 21.621 0 017.22-4.783m-7.22 4.783a21.766 21.766 0 00-2.97 3.697m2.97-3.697c-1.445-.778-4.935-1.2-7.335 3.334l2.364 2.364 2-2m10.19-8.481A21.709 21.709 0 0123.22.843a21.708 21.708 0 01-1.546 7.112M16.108 2.39l5.565 5.565M5.917 10.87l1.885 4.057m9.088.248a21.62 21.62 0 004.783-7.22m-4.783 7.22a21.771 21.771 0 01-3.698 2.97m3.698-2.97c.778 1.445 1.2 4.934-3.334 7.335l-2.364-2.364 2-2m0 0L9.136 16.26m0 0l-1.334-1.334m1.334 1.334l-2.71 2.71-.667-.666-.667-.667 2.71-2.71m6.42-5.087a1.886 1.886 0 112.668-2.667 1.886 1.886 0 01-2.668 2.667z"
						  stroke="currentColor" stroke-width="1.5"/>
				  </svg>
			  </button>${moreMenu}`
        } else {
          //language=HTML
          return `
			  <button class="deactivate gh-button text danger">${_x('Deactivate', 'funnel editor', 'groundhogg')}
			  </button>
			  <button class="update gh-button primary"
			          ${
				          objectEquals(
					          Editor.funnel.steps,
					          Editor.origFunnel.steps
				          ) || Object.keys(Editor.stepErrors).length > 0
					          ? 'disabled'
					          : ''
			          }>
				  <svg viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
					  <path
						  d="M1 21.956V2.995c0-.748.606-1.355 1.354-1.355H17.93l4.74 4.74v15.576c0 .748-.606 1.354-1.354 1.354H2.354A1.354 1.354 0 011 21.956z"
						  stroke="currentColor" stroke-width="1.5"/>
					  <path d="M14.544 16.539a2.709 2.709 0 11-5.418 0 2.709 2.709 0 015.418 0z" stroke="#fff"
					        stroke-width="1.5"/>
					  <path fill="currentColor" d="M5.619 6.298h9.634v2.846H5.619z"/>
				  </svg>
				  ${_x('Update', 'funnel editor', 'groundhogg')}
			  </button>
			  ${moreMenu}`
        }
      },
      funnelTitleEdit (title, isEditing) {

        const titleEdit = () => {
          return input({
            id: 'funnel-title-edit',
            name: 'funnel_title',
            value: title
          })
        }
        const titleDisplay = () => {
          return `<span id="title">${specialChars(title)}</span><span class="dashicons dashicons-edit"></span>`
        }

        return `<h1 class="breadcrumbs"><span class="root">${_x('Funnels', 'funnel editor', 'groundhogg')}</span><span class="sep">/</span>${isEditing ? titleEdit() : titleDisplay()}</h1>`
      },
      stepEditPanel (step) {
        const { ID, data, meta } = step
        const { step_type, step_group } = data

        const StepType = StepTypes.getType(step_type)

        let hasErrors = false
        let errors = []
        let hasWarnings = false
        let warnings = []

        if (
          Editor.stepErrors.hasOwnProperty(ID) &&
          Editor.stepErrors[ID].length > 0
        ) {
          hasErrors = true
          errors = Editor.stepErrors[ID]
        }

        if (
          Editor.stepWarnings.hasOwnProperty(ID) &&
          Editor.stepWarnings[ID].length > 0
        ) {
          hasWarnings = true
          warnings = Editor.stepWarnings[ID]
        }

        const updateStepMeta = (meta, reRenderStepEdit = false) => {
          return Editor.updateCurrentStepMeta(meta, reRenderStepEdit)
        }

        const updateStep = (data, reRenderStepEdit = false) => {
          return Editor.updateCurrentStep(data, reRenderStepEdit)
        }

        const benchmarkPanel = () => {
          // language=HTML
          return `
			  <div class="panel benchmark-settings">
				  <div class="row">
					  ${isStartingStep(step.ID) ? '' :
						  `<label class="row-label">${_x('Allow contacts to enter the funnel at this step?', 'funnel editor', 'groundhogg')}</label>
					  ${toggle({
							  name: 'is_entry_point',
							  id: 'is-entry-point',
							  checked: data.is_entry,
							  onLabel: _x( 'YES', 'funnel editor', 'groundhogg' ),
							  offLabel: _x( 'NO', 'funnel editor', 'groundhogg' ),
						  })}
				  </div>`}
					  <div class="row">
						  <label class="row-label">${_x('Track a conversion whenever this step is completed.', 'funnel editor', 'groundhogg')}</label>
						  ${toggle({
							  name: 'is_conversion',
							  id: 'is-conversion',
							  checked: data.is_conversion,
							  onLabel: _x( 'YES', 'funnel editor', 'groundhogg' ),
							  offLabel: _x( 'NO', 'funnel editor', 'groundhogg' ),
						  })}
					  </div>
				  </div>`
        }

        const slotArgs = [step, updateStepMeta, updateStep]

        return `
			${
          hasErrors
            ? `<div class="step-errors">
                <ul>
                    ${errors
              .map(
                (error) =>
                  `<li class="step-error"><span class="dashicons dashicons-warning"></span> ${_x(error, 'funnel editor', 'groundhogg')}</li>`
              )
              .join('')}
                </ul>
            </div>`
            : ''
        }
			${
          hasWarnings
            ? `<div class="step-warnings">
                <ul>
                    ${warnings
              .map(
                (warning) =>
                  `<li class="step-warning"><span class="dashicons dashicons-warning"></span> ${_x(warning, 'funnel editor', 'groundhogg')}</li>`
              )
              .join('')}
                </ul>
            </div>`
            : ''
        }
			<div class="step-edit ${step_type} ${step_group}">
				<div class="settings">
					${slot('beforeStepSettings', ...slotArgs)}
					${slot(`beforeStepSettings.${step_type}`, ...slotArgs)}
					${StepType.edit(...slotArgs)}
					${slot(`afterStepSettings.${step_type}`, ...slotArgs)}
					${slot('afterStepSettings', ...slotArgs)}
				</div>
				<div class="actions-and-notes">
					${slot('beforeStepNotes', ...slotArgs)}
					${slot(`beforeStepNotes.${step_type}`, ...slotArgs)}
					<div class="panel">
						<label class="row-label"><span class="dashicons dashicons-admin-comments"></span> ${_x('Notes', 'funnel editor', 'groundhogg')}</label>
						<textarea rows="4" id="step-notes" class="notes full-width"
						          name="step_notes">${specialChars(meta.step_notes || '')}</textarea>
					</div>
					${step_group === 'benchmark' ? benchmarkPanel() : ''}
					${slot(`afterStepNotes.${step_type}`, ...slotArgs)}
					${slot('afterStepNotes', ...slotArgs)}
				</div>
			</div>`
        //language=HTML
      },
      stepAddPanel (activeType, search = '', pack = '') {
        //language=HTML
        return `
			<div class="step-add">
				<div class="step-add-filters">
					<div class="type-select">
						<button class="select-type actions ${
							activeType === 'actions' && 'active'
						}" data-type="actions">
							${_x('Actions', 'funnel editor', 'groundhogg')}
						</button>
						<button class="select-type benchmarks ${
							activeType === 'benchmarks' && 'active'
						}"
						        data-type="benchmarks">
							${_x('Benchmarks', 'funnel editor', 'groundhogg')}
						</button>
					</div>
					${input({
						id: 'search-steps',
						name: 'search_steps',
						type: 'search',
						className: 'search-steps',
						placeholder: _x( 'Search...', 'funnel editor', 'groundhogg' ),
						value: search,
					})}
					${select(
						{
							id: 'pack-filter',
							name: 'pack_filter',
						},
						[
							{ text: _x('Filter by pack...', 'funnel editor', 'groundhogg'), value: '' },
							...Object.values(StepPacks.packs).map((pack) => ({
								value: pack.id,
								text: pack.name,
							})),
						],
						pack
					)}
				</div>
				<div id="types" class="types">
				</div>
			</div>`
      },
      stepTypeSelect (type) {
        //language=HTML
        return `
			<div class="type-select">
				<button class="select-type actions ${
					type === 'actions' && 'active'
				}" data-type="actions">
					${_x('Actions', 'funnel editor', 'groundhogg')}
				</button>
				<button class="select-type benchmarks ${
					type === 'benchmarks' && 'active'
				}" data-type="benchmarks">
          ${_x('Benchmarks', 'funnel editor', 'groundhogg')}
				</button>
			</div>`
      },
      addStepCard (step) {
        const pack = StepPacks.get(step.pack)

        //language=HTML
        return `
			<div class="add-step ${step.type} ${step.group}" data-type="${
				step.type
			}" data-group="${step.group}"
			     title="${step.name}">
				${slot('beforeAddStepCard', step)}
				${slot('beforeAddStepCard.' + step.type, step)}
				${
					typeof pack !== 'undefined' && pack.id !== 'core'
						? `<div class="pack">${
							pack.svg
								? pack.svg
								: `<span class="pack-name">${pack.name}</span>`
						}</div>`
						: ''
				}
				${
					step.hasOwnProperty('svg')
						? `<div class="step-icon-svg">${
							step.svg
						}</div>`
						: `<img alt="${
							step.name
						}" class="step-icon"
				     src="${step.icon}"/>`
				}
				<p>${step.name}</p>
				${slot('afterAddStepCard.' + step.type, step)}
				${slot('afterAddStepCard', step)}
			</div>`
      },
      stepFlowCard (step, activeStep) {
        const { ID, data, meta } = step
        const { step_type, step_title, step_group, step_order, is_entry } = data

        const StepType = StepTypes.getType(step_type)
        const origStep = Editor.origFunnel.steps.find((s) => s.ID === ID)

        let status
        let hasErrors = false
        let hasWarnings = false

        if (
          Editor.stepErrors.hasOwnProperty(ID) &&
          Editor.stepErrors[ID].length > 0
        ) {
          status = 'config-error'
          hasErrors = true
        } else if (Editor.stepWarnings.hasOwnProperty(ID) &&
          Editor.stepWarnings[ID].length > 0) {
          status = 'config-warning'
          hasWarnings = true
        } else if (origStep && !objectEquals(step, origStep)) {
          status = 'edited'
        } else if (!origStep) {
          status = 'new'
        } else if (StepType.type === 'error') {
          hasErrors = true
        }

        const nextStep = Editor.funnel.steps.find(
          (step) => step.data.step_order === step_order + 1
        )
        const prevStep = Editor.funnel.steps.find(
          (step) => step.data.step_order === step_order - 1
        )

        //language=HTML
        return `
			${
				step_group === 'benchmark'
					? step_order === 1
					? `<div class="text-helper until-helper"><span class="dashicons dashicons-filter"></span>${_x('Start the funnel when...', 'funnel editor', 'groundhogg')}
          </div>`
					: prevStep && prevStep.data.step_group !== 'benchmark'
						? '<div class="until-helper text-helper">'+_x('Until...', 'funnel editor', 'groundhogg')+'</div>'
						: ''
					: ''
			}
			<div
				class="step ${step_type} ${step_group} ${
					activeStep === ID ? 'active' : ''
				} ${hasErrors ? 'has-errors' : ''} ${hasWarnings ? 'has-warnings' : ''}"
				data-id="${ID}">
				${step_group === 'benchmark' && is_entry && !isStartingStep(ID) ? `<div class="is-entry"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
  <path fill="currentColor" d="M260.5 329.5a24 24 0 0034 34L385 273a24 24 0 000-34l-90.5-90.5a24 24 0 00-34 0 24 24 0 000 34l49.6 49.5H48a24 24 0 00-24 24 24 24 0 0024 24h262z"/>
  <path fill="currentColor" d="M448 24H224a40 40 0 00-40 40v32a24 24 0 0048 0V72h208v368H232v-24a24 24 0 00-48 0v32a40 40 0 0040 40h224a40 40 0 0040-40V64a40 40 0 00-40-40z"/>
</svg></div>` : ''}
				${slot('insideStepFlowCard.' + step.type, step)}
				${slot('insideStepFlowCard', step)}
				${
					StepType.hasOwnProperty('svg')
						? `<div class="icon-svg">${StepType.svg}</div>`
						: `<img alt="${StepType.name}" class="icon"
				     src="${StepType.icon}"/>`
				}
				<div class="details">
					<div class="step-title">${StepType.title(step)}</div>
					<div class="step-type">${StepType.name}</div>
					<div tabindex="0" class="step-menu-button">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 384">
							<circle fill="currentColor" cx="192" cy="42.7" r="42.7"/>
							<circle fill="currentColor" cx="192" cy="192" r="42.7"/>
							<circle fill="currentColor" cx="192" cy="341.3" r="42.7"/>
						</svg>
					</div>
				</div>
				<div class="step-status ${status}"></div>
			</div>
			${
				step_group === 'benchmark' && nextStep
					? nextStep.data.step_group === 'benchmark'
					? `<div class="or-helper text-helper">`+_x('Or...', 'funnel editor', 'groundhogg')+`</div>`
					: '<div class="then-helper text-helper">'+_x('Then...', 'funnel editor', 'groundhogg')+'</div>'
					: ''
			}
        `
      },
    },

    init () {
      this.loadingClose = loadingModal().close

      const self = this
      const $doc = $(document)

      $doc.on('click', '.step-add .select-type', function () {
        self.saveUndoState()
        self.activeAddType = $(this).data('type')
        self.renderStepAdd()
      })

      $doc.on('click', '.step-flow .steps .step', function (e) {

        const $step = $(this)
        const step = self.funnel.steps.find(
          (step) => step.ID === parseInt($step.data('id'))
        )

        const setStepEdit = () => {
          if (step.ID === self.activeStep) {
            return
          }

          self.saveUndoState()
          self.previousActiveStep = self.activeStep
          self.activeStep = step.ID
          self.view = 'editingStep'
          self.renderStepFlow()
          self.renderStepEdit()
        }

        if (clickInsideElement(e, '.step-menu-button')) {
          moreMenu(this, {
            items: [
              { key: 'edit', text: _x('Edit', 'funnel editor', 'groundhogg') },
              { key: 'move-up', text: _x('Move up', 'funnel editor', 'groundhogg') },
              { key: 'move-down', text: _x('Move down', 'funnel editor', 'groundhogg') },
              { key: 'duplicate', text: _x('Edit', 'funnel editor', 'groundhogg') },
              { key: 'delete', text: '<span class="gh-text danger">'+_x('Delete', 'funnel editor', 'groundhogg')+'</span>' },
            ],
            onSelect: (key) => {
              switch (key) {
                case 'move-up':
                  self.moveStepUp(step)
                  break
                case 'move-down':
                  self.moveStepDown(step)
                  break
                case 'duplicate':
                  const newStep = copyObject(step)
                  newStep.ID = uniqid()
                  self.addStep(newStep)
                  break
                case 'delete':
                  self.deleteStep(step.ID)
                  break
                case 'edit':
                  setStepEdit()
                  break
              }
            }
          })

        } else {
          setStepEdit()
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

      $doc.on('click', '.header-stuff #title', function () {
        if (!self.isEditingTitle) {
          self.isEditingTitle = true
          self.renderTitle()
        }
      })

      $doc.on('blur change keydown', '#funnel-title-edit', function (e) {
        // If the event is key down do nothing if the key wasn't enter
        if (e.type === 'keydown' && e.key !== 'Enter') {
          self.resizeTitleEdit()
          return
        }

        self.saveUndoState()
        self.funnel.data.title = e.target.value
        self.isEditingTitle = false
        self.update(
          {
            data: {
              title: e.target.value,
            },
          },
          false
        )
        self.renderTitle()
      })

      $doc.on('click', '#more-menu', () => {
        moreMenu('#more-menu', {
          onSelect: (key) => {
            switch (key) {
              case 'campaigns':

                const campaignContent = () => {
                  // language=HTML
                  return `
					  <div class="manage-campaigns">
						  <p><b>${_x('Add this funnel to one or more campaigns...', 'funnel editor', 'groundhogg')}</b></p>
						  <p>${select({
							  id: 'manage-campaigns',
							  multiple: true
						  }, this.funnel.campaigns.map(c => ({
							  text: c.data.name,
							  value: c.ID
						  })), this.funnel.campaigns.map(c => c.ID))}</p>
					  </div>`
                }

                modal({
                  content: campaignContent()
                })

                campaignPicker('#manage-campaigns', true, (items) => {
                  CampaignsStore.itemsFetched(items)
                }).on('select2:select', async (e) => {
                  let campaign = e.params.data
                  // its a new campaign
                  if (!CampaignsStore.hasItem(campaign.id)) {
                    campaign = await CampaignsStore.post({
                      data: {
                        name: campaign.id
                      }
                    }).then((c) => ({ id: c.ID, name: c.data.name }))
                  }
                  // existing campaign
                  apiPost(`${apiRoutes.funnels}/${this.funnel.ID}/relationships`, {
                    other_id: campaign.id,
                    other_type: 'campaign'
                  }).then(r => this.loadFunnel(r.item))
                }).on('select2:unselect', async (e) => {
                  let campaign = e.params.data

                  // existing campaign
                  apiDelete(`${apiRoutes.funnels}/${this.funnel.ID}/relationships`, {
                    other_id: campaign.id,
                    other_type: 'campaign'
                  }).then(r => this.loadFunnel(r.item))
                })

                break
              case 'export':
                window.location.href = this.funnel.links.export
                break
              case 'share':

                break
              case 'reports':
                window.location.href = this.funnel.links.report
                break
              case 'delete':

                dangerConfirmationModal({
                  //language=HTML
                  alert: `<p><b>${_x('Delete this funnel?', 'funnel editor', 'groundhogg')}</b></p>
				  <p>${_x('Any associated events, steps, and reports will also be deleted.', 'funnel editor', 'groundhogg')}</p>
				  <p>${_x('This action cannot be undone. Are you sure?', 'funnel editor', 'groundhogg')}</p>`,
                  confirmText: 'Delete',
                  onConfirm: () => {
                    console.log('yikes')
                  }
                })

                break
              case 'archive':

                dangerConfirmationModal({
                  //language=HTML
                  alert: `<p><b>${_x('Archive this funnel?', 'funnel editor', 'groundhogg')}</b></p>
				  <p>${_x('Any active contacts will be removed from the funnel permanently.', 'funnel editor', 'groundhogg')}</p>
				  <p>${_x('The funnel will become un-editable until restored.', 'funnel editor', 'groundhogg')}</p>`,
                  confirmText: _x('Archive', 'funnel editor', 'groundhogg'),
                  onConfirm: () => {
                  console.log('yikes')
                  }
                })

                break
            }
          },
          items: [
            {
              key: 'campaigns',
              //language=HTML
              text: `
				  <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
					  <defs/>
					  <path
						  d="M343.2 49.7A45.1 45.1 0 00300 82L71.8 130a15 15 0 00-12 14.7v17.1H15a15 15 0 00-15 15V256a15 15 0 0015 15h44.9v21a15 15 0 0011.9 14.8l23.6 5v107.8a42.6 42.6 0 0069.2 33.5 42.5 42.5 0 0016.2-33.5V400h3c34 0 62.8-23.4 70.9-55l45.3 9.5a45.1 45.1 0 0088.3-12.5V94.7a45 45 0 00-45-45zM60 241H30v-49.2h29.9zm91 178.6a12.7 12.7 0 01-15.7 12.4 12.7 12.7 0 01-9.8-12.4V318l25.4 5.4v96.2zm33-49.5h-3v-40.5l44.4 9.4c-5.3 18-21.9 31-41.5 31zm114.3-46.5L89.9 280V157L298.2 113zm60 18.5a15 15 0 01-30 0V94.7a15 15 0 0130 0zM446.3 117a15 15 0 009.5-3.4l30.2-25a15 15 0 00-19.1-23l-30.2 24.8a15 15 0 009.6 26.6zM486 344.2l-30.2-25a15 15 0 00-19 23.2l30 25a15 15 0 0021.2-2 15 15 0 00-2-21.2zM497 201.4h-63.6a15 15 0 000 30H497a15 15 0 000-30z"/>
				  </svg> Campaigns`
            },
            {
              key: 'export',
              //language=HTML
              text: `
				  <svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 367 367">
					  <defs/>
					  <path
						  fill="currentColor"
						  stroke-width="1"
						  d="M363.6 247l.4-.5.5-.7.4-.6.3-.6.4-.7.3-.7.2-.6c0-.3.2-.5.3-.7l.1-.7.2-.8.1-.8.1-.6.1-1.5V236l-.2-.6v-.8l-.3-.8-.1-.7-.3-.7-.2-.6-.3-.7-.4-.7-.3-.6-.4-.6-.5-.7-.4-.5a15 15 0 00-1-1v-.1l-37.5-37.5a15 15 0 00-21.2 21.2l11.9 11.9H270v-78.6-.4a15 15 0 00-3.4-9.5 15.2 15.2 0 00-1-1.2c-.2 0-.3-.2-.4-.4L155.6 23a15 15 0 00-1-.9l-.3-.2a14.9 14.9 0 00-1.9-1.3l-.3-.2-1.1-.6-.5-.1a14.5 14.5 0 00-2.2-.7l-.4-.1-1.2-.2h-1.4l-.3-.1H15a15 15 0 00-15 15v300a15 15 0 0015 15h240a15 15 0 0015-15v-81h45.8l-12 11.9a15 15 0 0021.3 21.2l37.5-37.5 1-1zM160 69.7l58.8 58.8H160V69.7zm80 248.8H30v-270h100v95a15 15 0 0015 15h95v64h-65a15 15 0 000 30h65v66z"/>
				  </svg> Export`
            },
            {
              key: 'share',
              //language=HTML
              text: `
				  <svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="-33 0 512 512">
					  <path fill="currentColor"
					        d="M361.8 344.4a83.6 83.6 0 00-62 27.4l-138-85.4a83.3 83.3 0 000-60.8l138-85.4a83.6 83.6 0 00145.8-56.4 83.9 83.9 0 10-161.9 30.4l-138 85.4A83.6 83.6 0 000 256a83.9 83.9 0 00145.8 56.4l138 85.4a83.9 83.9 0 10161.9 30.4 83.9 83.9 0 00-83.9-83.8zM308.6 83.8a53.3 53.3 0 11106.6.1 53.3 53.3 0 01-106.6-.1zM83.8 309.2a53.3 53.3 0 11.1-106.6 53.3 53.3 0 01-.1 106.6zm224.8 119a53.3 53.3 0 11106.6.1 53.3 53.3 0 01-106.6-.1zm0 0"/>
				  </svg> Share`
            },
            {
              key: 'reports',
              //language=HTML
              text: `
				  <svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 510 510">
					  <path fill="currentColor"
					        d="M495 420h-14V161.8a15 15 0 00-15-15h-82.2a15 15 0 00-15 15V420h-42.3V75a15 15 0 00-15-15h-82.3a15 15 0 00-15 15v345H172V232.2a15 15 0 00-15-15H74.7a15 15 0 00-15 15V420H30V75a15 15 0 00-30 0v360a15 15 0 0015 15h480a15 15 0 000-30zm-405.3 0V247.2h52.2V420zm154.5 0V90h52.2v330zm154.6 0V176.8H451V420z"/>
				  </svg> Reports`
            },
            {
              key: 'archive',
              //language=HTML
              text: `
				  <svg class="danger" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 520 520">
					  <defs/>
					  <path fill="currentColor"
					        d="M475 125V90a45 45 0 00-45-45H219.4l-7.9-12.8a15 15 0 00-12.7-7.2H45A45 45 0 000 70v380a45 45 0 0045 45h430a45 45 0 0045-45V170a45 45 0 00-45-45zm-45-50a15 15 0 0115 15v35H268.4l-20-32.8L237.7 75zm60 375a15 15 0 01-15 15H45a15 15 0 01-15-15V70a15 15 0 0115-15h145.3l7.9 12.8 29 47.3 20 32.8A15 15 0 00260 155h215a15 15 0 0115 15v280z"/>
				  </svg><span class="gh-text danger">Archive</span>`
            },
            {
              key: 'delete',
              //language=HTML
              text: `
				  <svg class="danger" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
					  <defs/>
					  <path fill="currentColor"
					        d="M436 60h-75V45a45 45 0 00-45-45H196a45 45 0 00-45 45v15H76a45 45 0 00-14 87.8l26.8 323a45.3 45.3 0 0044.8 41.2h244.8c23.2 0 43-18.1 44.8-41.3l26.8-323A45 45 0 00436 60zM181 45a15 15 0 0115-15h120a15 15 0 0115 15v15H181V45zm212.3 423.2a15 15 0 01-14.9 13.8H133.6a15 15 0 01-15-13.7L92.4 150h327.4l-26.4 318.2zM436 120H76a15 15 0 010-30h360a15 15 0 010 30z"/>
					  <path fill="currentColor"
					        d="M196 436l-15-242a15 15 0 00-30 2l15 242a15 15 0 1030-2zM256 180a15 15 0 00-15 15v242a15 15 0 0030 0V195a15 15 0 00-15-15zM347 180a15 15 0 00-16 14l-15 242a15 15 0 0030 2l15-242a15 15 0 00-14-16z"/>
				  </svg><span class="gh-text danger">Delete</span>`
            },
          ]
        })
      })

      $doc.on('click', '.publish-actions .deactivate', function () {
        dangerConfirmationModal({
          // language=HTML
          alert: `<p><b>${_x('Are you sure you want to deactivate the funnel?', 'funnel editor', 'groundhogg')}</b></p>
		  <p>${_x('Active contacts will be paused until the funnel is reactivated.', 'funnel editor', 'groundhogg')}</p>`,
          confirmText: _x('Deactivate', 'funnel editor', 'groundhogg'),
          onConfirm: () => {
            self.deactivate()
          },
        })
      })

      $doc.on(
        'click',
        '.publish-actions .update-and-launch, .publish-actions .update',
        function () {
          confirmationModal({
            // language=HTML
            alert: `<p><b>${_x('Are you sure you want to commit these changes?', 'funnel editor', 'groundhogg')}</b></p><p>${_x('The changes made will take immediate effect to anyone currently in the funnel.', 'funnel editor', 'groundhogg')}</p>`,
            onConfirm: () => {
              self.commitChanges()
            },
          })
        }
      )

      improveTinyMCE()

      StepTypes.setup()
      // self.initStepFlowContextMenu()
      this.preloadFunnel()
    },

    renderContainer () {
      $('#app').html(this.htmlTemplates.container())
      this.setupSortable()
    },

    /**
     * Re-render the whole editor
     */
    render () {
      this.renderContainer()
      this.renderTitle()
      this.renderPublishActions()
      this.renderStepFlow()
      this.renderStepAdd()
      this.renderStepEdit()

      this.loadingClose()
      $(window).trigger('resize')
    },

    preloadFunnel () {
      FunnelsStore.fetchItem(this.funnel.ID).then((item) => {
        this.loadFunnel(item)
        StepTypes.preloadSteps(this.funnel.steps)
          .then(() => this.render())
      })
    },

    loadFunnel (funnel) {
      this.funnel = funnel

      // Copy from the orig included data
      // self.funnel = copyObject(self.funnel)
      this.origFunnel = copyObject(funnel)

      if (this.funnel.meta.edited) {
        this.funnel.steps = this.funnel.meta.edited.steps
      }
    },

    /**
     * Init the sortable list of steps in the step flow
     */
    setupSortable () {
      const self = this
      $('.step-flow .steps')
        .sortable({
          placeholder: 'step-placeholder',
          cancel: '.text-helper',
          start: function (e, ui) {
            ui.placeholder.height(ui.item.height())
            ui.placeholder.width(ui.item.width())
          },
          receive: function (e, ui) {
            // console.log('received', ui)

            self.saveUndoState()

            const type = $(ui.helper).data('type')
            const group = $(ui.helper).data('group')

            const id = uniqid()

            $(ui.helper).addClass('step')
            $(ui.helper).data('id', id)

            self.addStep({
              ID: id,
              data: {
                ID: id,
                funnel_id: Editor.funnel.ID,
                step_title: StepTypes.getType(type).name,
                step_type: type,
                step_group: group,
                step_order: $(ui.helper).prevAll('.step').length,
              },
              meta: StepTypes.getType(type).defaults,
            })
          },
          update: function (e, ui) {
            // console.log('updated', ui)

            self.saveUndoState()
            self.syncOrderWithFlow()
            self.autoSaveEditedFunnel()
            self.renderStepFlow()
            self.renderStepEdit()
          },
        })
        .disableSelection()
    },

    /**
     * Setup the context menu for editing duplicating and deleting steps
     */
    initStepFlowContextMenu () {
      const self = this

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
              const stepToCopy = self.funnel.steps.find(
                (step) => step.ID === self.stepOpenInContextMenu
              )

              const newStep = copyObject(stepToCopy)
              newStep.ID = uniqid()
              self.addStep(newStep)

              break
          }
        },
      })

      this.stepFlowContextMenu.init()
    },

    /**
     * Renders the step flow
     */
    renderStepFlow () {
      const self = this

      this.checkForStepErrors()

      const steps = this.funnel.steps
        .sort((a, b) => a.data.step_order - b.data.step_order)
        .map((step) => self.htmlTemplates.stepFlowCard(step, self.activeStep))
        .join('')

      $('.step-flow .steps').html(steps)

      this.renderPublishActions()
    },

    /**
     * Add the step error
     *
     * @param id
     * @param error
     */
    addStepError (id, error) {
      if (!this.stepErrors.hasOwnProperty(id)) {
        this.stepErrors[id] = []
      }

      this.stepErrors[id].push(error)
    },

    /**
     * Add the step error
     *
     * @param id
     * @param error
     */
    addStepWarning (id, error) {
      if (!this.stepWarnings.hasOwnProperty(id)) {
        this.stepWarnings[id] = []
      }

      this.stepWarnings[id].push(error)
    },

    /**
     * Check for step errors
     */
    checkForStepErrors () {
      const self = this

      this.stepErrors = {}
      this.stepWarnings = {}

      this.funnel.steps.forEach((step) => {

        const addError = (error) => {
          this.addStepError(step.ID, error)
        }

        const addWarning = (warning) => {
          this.addStepWarning(step.ID, warning)
        }

        const { step_group, step_order, step_type } = step.data

        const typeHandler = StepTypes.getType(step_type)

        if (step_group === 'action' && step_order === 1) {
          addError(_x('Actions cannot be at the start of a funnel.', 'funnel editor', 'groundhogg')
          )
        } else if (typeHandler.type === 'error') {
          addError(_x('Settings not found.', 'funnel editor', 'groundhogg'))
        }

        if (typeHandler) {
          typeHandler.validate(step, addError, addWarning)
        }
      })
    },

    mountStep (step) {
      step = step || this.funnel.steps.find((s) => s.ID === this.activeStep)

      if (!step) {
        return
      }

      const updateStepMeta = (meta, reRenderStepEdit = false) => {
        return this.updateCurrentStepMeta(meta, reRenderStepEdit)
      }

      const updateStepData = (data, reRenderStepEdit = false) => {
        return this.updateCurrentStepData(data, reRenderStepEdit)
      }

      const updateStep = (data, reRenderStepEdit = false) => {
        return this.updateCurrentStep(data, reRenderStepEdit)
      }

      const currentState = () => {
        return this.getCurrentStep()
      }

      // Step notes listener
      $('#step-notes').on('change', function (e) {
        updateStepMeta({
          step_notes: $(this).val(),
        })
      })

      if (step.data.step_group === 'benchmark') {
        $('#is-entry-point').on('change', (e) => {
          updateStepData({
            is_entry: e.target.checked,
          })
        })
        $('#is-conversion').on('change', (e) => {
          updateStepData({
            is_conversion: e.target.checked,
          })
        })
      }

      const StepType = StepTypes.getType(step.data.step_type)

      StepType.onMount(step, updateStepMeta, updateStep, currentState)

      this.lastStepEditMounted = this.activeStep
    },

    demountStep (step) {
      step =
        step ||
        this.funnel.steps.find((s) => s.ID === this.lastStepEditMounted)

      if (!step) {
        return
      }

      const updateStepMeta = (meta) => {
        return this.updateCurrentStepMeta(meta)
      }

      const updateStep = (data) => {
        return this.updateCurrentStep(data)
      }

      const currentState = () => {
        return this.getCurrentStep()
      }

      const StepType = StepTypes.getType(step.data.step_type)

      StepType.onDemount(step, updateStepMeta, updateStep, currentState, this)

      this.lastStepEditMounted = null
    },

    /**
     * Renders the edit step panel for the current step in the controls panel
     */
    renderStepEdit () {
      if (this.view !== 'editingStep') {
        return
      }

      // const activeElementId = document.activeElement.id

      const step = this.funnel.steps.find(
        (step) => step.ID === this.activeStep
      )
      const previousStep = this.funnel.steps.find(
        (step) => step.ID === this.previousActiveStep
      )

      if (!step) {
        this.activeStep = false
        this.previousActiveStep = false
        this.view = 'addingStep'
        this.renderStepAdd()
        return
      }

      // Handle remounting the step
      if (this.activeStep === this.lastStepEditMounted) {
        this.demountStep(step)
      } else if (previousStep) {
        this.demountStep(previousStep)
      }

      slotsDemounted()

      $('#control-panel').html(this.htmlTemplates.stepEditPanel(step))

      this.mountStep(step)

      slotsMounted()
    },

    /**
     * Renders the add step panel for the current step in the controls panel
     */
    renderStepAdd () {
      if (this.view !== 'addingStep') {
        return
      }

      const self = this

      self.demountStep()

      $('#control-panel').html(
        self.htmlTemplates.stepAddPanel(
          self.activeAddType,
          self.stepSearch,
          self.packFilter
        )
      )

      self.renderStepFlow()

      const mountSteps = () => {
        const sr = regexp(self.stepSearch)

        $('#types').html(
          Object.values(StepTypes)
            .filter((step) => typeof step === 'object' && step.hasOwnProperty('group'))
            .filter((step) => step.group + 's' === self.activeAddType)
            .filter((step) => step.name.match(sr) || step.pack.match(sr))
            .filter((step) => !self.packFilter || step.pack === self.packFilter)
            .map(Editor.htmlTemplates.addStepCard)
            .join('')
        )

        const addStepHere = ({
          type,
          group,
          order,
        }) => {
          const id = Date.now()

          self.addStep({
            ID: id,
            data: {
              ID: id,
              funnel_id: Editor.funnel.ID,
              step_title: StepTypes.getType(type).name,
              step_type: type,
              step_group: group,
              step_order: order,
            },
            meta: StepTypes.getType(type).defaults,
          })
        }

        const $addSteps = $('.add-step')

        $addSteps.draggable({
          connectToSortable: '.step-flow .steps',
          helper: 'clone',
          revert: 'invalid',
          revertDuration: 0,
        })

        $addSteps.on('click', function () {
          const $button = $(this)
          const type = $button.data('type')
          const group = $button.data('group')
          addStepHere({
            type,
            group,
            order: self.addingStepOrder || self.funnel.steps.length
          })
          self.addingStepOrder = false
        })

      }

      mountSteps()

      $('#search-steps').on('input', (e) => {
        this.stepSearch = e.target.value
        mountSteps()
      })

      $('#pack-filter').on('change', (e) => {
        this.packFilter = e.target.value
        mountSteps()
      })
    },

    renderEmailTemplatePicker (updateStepMeta) {

      this.demountStep()

      const picker = Groundhogg.EmailTemplatePicker({
        selector: '#app',
        breadcrumbs: [
          'Funnels',
          `<span id="back-to-funnel" style="cursor: pointer">${specialChars(this.funnel.data.title)}</span>`,
          'Add Email'
        ],
        onSelect: (email) => {
          this.render()
          EmailsStore.itemsFetched([
            email
          ])
          updateStepMeta({
            email_id: email.ID
          }, true)
          this.renderEmailEditor(email)
        },
        afterHeaderActions: toEditorButton(),
        onMount: () => {
          $('#back-to-funnel,#close-email-editor').on('click', () => {
            this.render()
          })
        }
      })

      picker.mount()

    },

    renderEmailEditor (email) {

      this.demountStep()

      const editor = Groundhogg.EmailEditor({
        selector: '#app',
        email: email,
        onChange: (email) => {
          console.log(email)
        },
        onCommit: (email) => {
          console.log(email)
        },
        onHeaderMount: () => {
          $('#back-to-funnel,#close-email-editor').on('click', () => {
            editor.demount()
            this.render()
          })
        },
        breadcrumbs: [
          'Funnels',
          `<span id="back-to-funnel" style="cursor: pointer">${specialChars(this.funnel.data.title)}</span>`,
        ],
        //language=html
        afterPublishActions: toEditorButton()
      })

      editor.mount()
    },

    /**
     * Renders the funnel title edit component
     */
    renderTitle () {
      $('.header-stuff .title-wrap').html(
        this.htmlTemplates.funnelTitleEdit(
          this.funnel.data.title,
          this.isEditingTitle
        )
      )

      if (this.isEditingTitle) {
        $('#funnel-title-edit').focus()
        this.resizeTitleEdit()
      }
    },

    resizeTitleEdit () {
      $('#funnel-title-edit').width(this.funnel.data.title.length + 1 + 'ch')
    },

    /**
     * Publish actions
     */
    renderPublishActions () {
      $('.publish-actions').html(
        this.htmlTemplates.publishActions(this.funnel.data.status)
      )
      $('.undo-and-redo').replaceWith(
        this.htmlTemplates.undoRedoActions()
      )

      const { close: cUndo } = tooltip('.undo', {
        content: 'Undo'
      })

      const { close: cRedo } = tooltip('.redo', {
        content: 'Redo'
      })

    },

    /**
     * Syncs the order of the steps in the state with that of the order which the steps appear in the flow
     */
    syncOrderWithFlow () {
      const self = this

      $('.step-flow .steps .step').each(function (i) {
        self.funnel.steps.find(
          (step) => step.ID === $(this).data('id')
        ).data.step_order = i + 1
      })

      this.fixStepOrders()
    },

    currentState () {
      const { view, funnel, activeStep, activeAddType, isEditingTitle } = this

      return {
        view,
        activeStep,
        isEditingTitle,
        activeAddType,
        funnel: copyObject(funnel),
      }
    },

    /**
     * Saves the current state of the funnel for an undo slot
     */
    saveUndoState () {
      this.undoStates.push(this.currentState())
    },

    /**
     * Undo the previous change
     */
    undo () {
      const lastState = this.undoStates.pop()

      if (!lastState) {
        return
      }

      this.redoStates.push(this.currentState())

      Object.assign(this, lastState)

      this.render()
    },

    /**
     * Redo the previous change
     */
    redo () {
      const lastState = this.redoStates.pop()

      if (!lastState) {
        return
      }

      this.undoStates.push(this.currentState())

      Object.assign(this, lastState)

      this.render()
    },

    update (data, reload = true) {
      const self = this

      return FunnelsStore.patch(this.funnel.ID, data).then(
        (item) => {
          self.setLastSaved()
          if (item && reload) {
            self.loadFunnel(item)
            self.render()
          }
        }
      )
    },

    activate () {
      const { close } = loadingModal('Launching')

      this.update({
        data: {
          status: 'active',
        },
      }).then(() => close()).then(() => dialog({
        message: _x('Funnel activated!', 'funnel editor', 'groundhogg')
      }))
    },

    deactivate () {
      const { close } = loadingModal('Deactivating')

      this.update({
        data: {
          status: 'inactive',
        },
      }).then(() => close()).then(() => dialog({
        message: _x('Funnel deactivated!', 'funnel editor', 'groundhogg')
      }))
    },

    commitChanges () {
      const self = this

      if (objectEquals(this.funnel.steps, this.origFunnel.steps)) {
        return this.activate()
      }

      if (this.autoSaveTimeout) {
        clearTimeout(this.autoSaveTimeout)
      } else if (this.abortController) {
        this.abortController.abort()
      }

      const { close } = savingModal()

      FunnelsStore.commit(this.funnel.ID, {
        edited: {
          steps: self.funnel.steps,
        },
      }).then((item) => {

        self.setLastSaved()

        if (item) {
          self.loadFunnel(item)
          self.render()
          dialog({
            message: _x('Funnel updated!', 'funnel editor', 'groundhogg')
          })
        }
      })
        .then(() => close())
    },

    setLastSaved () {
      clearInterval(self.lastSavedTimer)

      self.lastSavedTimer = setInterval(
        this.updateLastSaved,
        30 * 1000,
        new Date()
      ) // 30 seconds
      this.updateLastSaved(new Date())
    },

    /**
     * Update the header-actions attr
     *
     * @param Date lastSaved The `new Date`
     *
     * @link https://stackoverflow.com/a/7641812
     */
    updateLastSaved (lastSaved) {
      const delta = Math.round((+new Date() - lastSaved) / 1000)

      const minute = 60,
        hour = minute * 60,
        day = hour * 24,
        week = day * 7

      let fuzzy = 'Saved '

      if (delta < 30) {
        fuzzy += 'just now'
      } else if (delta < minute) {
        fuzzy += delta + ' seconds ago'
      } else if (delta < 2 * minute) {
        fuzzy += 'a minute ago'
      } else if (delta < hour) {
        fuzzy += Math.floor(delta / minute) + ' minutes ago.'
      } else if (Math.floor(delta / hour) == 1) {
        fuzzy += '1 hour ago'
      } else if (delta < day) {
        fuzzy = Math.floor(delta / hour) + ' hours ago.'
      } else if (delta < day * 2) {
        fuzzy += 'yesterday'
      }

      $('.header-actions').attr('data-lastSaved', fuzzy)
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

      delete this.addingStepOrder
      // this.activeStep = step.ID
      // this.view = 'editingStep'
      this.renderStepFlow()
      // this.renderStepEdit()

      this.autoSaveEditedFunnel()
    },

    moveStep (step, direction) {
      if (!step) {
        return
      }

      const move = 'up' === direction ? -1.1 : 1.1

      this.saveUndoState()

      step.data.step_order = step.data.step_order + move

      window.console.log('steps', this.funnel.steps)

      this.fixStepOrders()
      this.renderStepFlow()

      this.autoSaveEditedFunnel()
    },

    moveStepUp (step) {
      this.moveStep(step, 'up')
    },

    moveStepDown (step) {
      this.moveStep(step, 'down')
    },

    insertPlaceholderStep (step, beforeAfter) {
      const self = this

      self.previousActiveStep = step.ID

      self.view = 'addingStep'
      self.addingStepOrder =
        'before' === beforeAfter
          ? parseInt(step.data.step_order) - 0.1
          : parseInt(step.data.step_order) + 0.1
      self.renderStepFlow()
      self.renderStepAdd()
      const $html = $(
        `<div class="step-placeholder">${_x('Choose a step to add here', 'funnel editor', 'groundhogg')} &rarr;<button type="button" class="button button-secondary">${_x('Cancel', 'funnel editor', 'groundhogg')}</button></div>`
      )

      $('button', $html).on('click', function () {
        delete self.addingStepOrder
        $html.remove()

        self.activeStep = self.previousActiveStep
        self.view = 'editingStep'
        self.renderStepFlow()
        self.renderStepEdit()
      })

      if ('before' === beforeAfter) {
        $html.insertBefore(`.steps [data-id="${step.ID}"]`)
      } else {
        $html.insertAfter(`.steps [data-id="${step.ID}"]`)
      }
    },

    fixStepOrders () {
      let newOrder = 1
      this.funnel.steps
        .sort((a, b) => a.data.step_order - b.data.step_order)
        .forEach((step) => {
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

      const removeStep = () => {
        this.saveUndoState()

        this.funnel.steps = this.funnel.steps.filter(
          (step) => step.ID !== stepId
        )

        this.fixStepOrders()
        this.renderStepFlow()

        if (this.activeStep === stepId) {
          this.view = 'addingStep'
          this.renderStepAdd()
          this.activeStep = null
        }

        this.autoSaveEditedFunnel()
      }

      const origStep = Editor.origFunnel.steps.find((s) => s.ID === stepId)

      if (origStep) {
        dangerConfirmationModal({
          alert: `
          <p><b>${_x('Delete this step?', 'funnel editor', 'groundhogg')}</b></p>
          <p>${_x('Active contacts at this step will be removed from the funnel when it is updated.', 'funnel editor', 'groundhogg')}</p> 
        `,
          confirmText:_x('Delete', 'funnel editor', 'groundhogg'),
          onConfirm: () => {
            removeStep()
          },
        })
      } else {
        removeStep()
      }
    },

    /**
     * Get the step object from the funnel
     *
     * @param stepId
     * @returns {*}
     */
    getStep (stepId) {
      return this.funnel.steps.find((step) => step.ID === stepId)
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
        ...newData,
      }

      newStep.data.step_title = StepTypes.getType(newStep.data.step_type).title(
        newStep
      )
      const toReplace = this.funnel.steps.findIndex((step) => step.ID === stepId)

      this.autoSaveEditedFunnel()
      this.saveUndoState()

      if (toReplace !== -1) {
        this.funnel.steps[toReplace] = newStep
      }

      this.renderStepFlow()

      return newStep
    },

    /**
     * Updates the current active step
     *
     * @param newData
     * @param reRenderStepEdit
     */
    updateCurrentStep (newData, reRenderStepEdit = false) {
      const step = this.updateStep(this.activeStep, newData)

      if (reRenderStepEdit) {
        this.renderStepEdit()
      }

      return step
    },

    /**
     * Updates the current active step
     *
     * @param newData
     * @param reRenderStepEdit
     */
    updateCurrentStepData (newData, reRenderStepEdit = false) {
      // console.log(this)

      const { data, meta } = this.getCurrentStep()

      const step = this.updateStep(this.activeStep, {
        data: {
          ...data,
          ...newData,
        },
      })

      if (reRenderStepEdit) {
        this.renderStepEdit()
      }

      return step
    },

    /**
     * Updates the current active step
     *
     * @param newMeta
     * @param reRenderStepEdit
     */
    updateCurrentStepMeta (newMeta, reRenderStepEdit = false) {
      // console.log(this)

      const { data, meta } = this.getCurrentStep()

      const step = this.updateStep(this.activeStep, {
        meta: {
          ...meta,
          ...newMeta,
        },
      })

      if (reRenderStepEdit) {
        this.renderStepEdit()
      }

      return step
    },

    autoSaveTimeout: null,
    abortController: null,

    autoSaveEditedFunnel () {
      const self = this

      if (this.autoSaveTimeout) {
        clearTimeout(this.autoSaveTimeout)
      }

      this.autoSaveTimeout = setTimeout(() => {
        self.autoSaveTimeout = null
        self.abortController = new AbortController()
        const { signal } = self.abortController

        FunnelsStore.patchMeta(this.funnel.ID, {
            edited: {
              steps: self.funnel.steps,
              title: self.funnel.data.title,
            },
          },
          {
            signal,
          }
        ).then((data) => {
          self.setLastSaved()
          self.abortController = null
        })
      }, 3000)
    },

    ...Funnel,
  }

  $(function () {
    Editor.init()
  })

  fill('beforeStepNotes.send_email', {
    render ({ ID, meta }) {

      const { email_id } = meta
      const email = EmailsStore.get(parseInt(email_id))

      if (!email_id || !email) {
        return ''
      }

      //language=HTML
      return `
		  <button style="width: 100%" id="edit-email-right" class="gh-button secondary">${_x('Edit Email', 'funnel editor', 'groundhogg')}</button>
      `
    },
    onMount ({ ID, meta }) {

      const { email_id } = meta
      const email = EmailsStore.get(parseInt(email_id))

      if (email_id && email) {
        $('#edit-email-right').on('click', () => {
          Editor.renderEmailEditor(email)
        })
      }
    }
  })

  fill('beforeStepNotes.form_fill', {
    render ({ ID, meta }) {
      if (!stepIsReal(ID)) {
        return ''
      }

      const copyValue = (toCopy) => {
        return input({
          className: 'code',
          value: toCopy,
          onfocus: 'this.select()',
          readonly: true,
        })
      }

      //language=HTML
      return `
		  <div id="form-embed-options" class="panel">
			  <div class="row">
				  <label class="row-label">${_x('Embed via Shortcode', 'funnel editor', 'groundhogg')}</label>
				  <div class="embed-option">${copyValue(`[gh_form id="${ID}"]`)}
				  </div>
			  </div>
			  <div class="row">
				  <label class="row-label">${_x('Embed via iFrame', 'funnel editor', 'groundhogg')}</label>
				  <div class="embed-option">${copyValue(`[gh_form id="${ID}"]`)}
				  </div>
			  </div>
		  </div>`
    },
  })

  const isStartingStep = (stepId) => {
    const step = stepId ? Editor.getStep(stepId) : Editor.getCurrentStep()
    return !getPrecedingSteps(step.ID)
      .find(_step => _step.data.step_group === 'action')
  }

  const getProceedingSteps = (stepId) => {
    const step = stepId ? Editor.getStep(stepId) : Editor.getCurrentStep()
    return Editor.getSteps()
      .filter((_step) => _step.data.step_order > step.data.step_order)
      .sort((a, b) => a.data.step_order - b.data.step_order)
  }

  const getPrecedingSteps = (stepId) => {
    const step = stepId ? Editor.getStep(stepId) : Editor.getCurrentStep()
    return Editor.getSteps()
      .filter((_step) => _step.data.step_order < step.data.step_order)
      .sort((a, b) => a.data.step_order - b.data.step_order)
  }

  Groundhogg.funnelEditor = Editor
  Groundhogg.funnelEditor.functions = {
    slot,
    fill,
    slotsDemounted,
    slotsMounted,
    // FormIntegration,
    registerFormIntegration (type, opts) {
      StepTypes.registerFormIntegration(type, opts)
    },
    getSteps () {
      return Editor.getSteps()
    },
    stepTitle (step) {
      return StepTypes.getType(step.data.step_type).title(step)
    },
    registerStepType (type, opts) {
      return StepTypes.register(type, opts)
    },
    registerStepPack (id, name, svg) {
      return StepPacks.add(id, name, svg)
    },
    updateCurrentStepMeta (newMeta) {
      return Editor.updateCurrentStepMeta(newMeta)
    },
    renderStepEdit () {
      return Editor.renderStepEdit()
    },
    getCurrentStep () {
      return Editor.getCurrentStep()
    },
    getCurrentStepMeta () {
      return Editor.getCurrentStep().meta
    },
    getProceedingSteps,
    getPrecedingSteps,
  }

})(Funnel, jQuery)
