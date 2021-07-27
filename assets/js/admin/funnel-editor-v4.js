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
    icons,
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

  const { campaignPicker, emailPicker } = Groundhogg.pickers

  const { StepTypes, StepPacks } = Groundhogg

  const { __, _x, _n, _nx, sprintf } = wp.i18n

  const toEditorButton = () => {
    // language=HTML
    return `
		<button id="close-email-editor" class="gh-button secondary text icon">
			${icons.close}
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
								${__('Add New Step', 'groundhogg')}
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
				${icons.verticalDots}
			</button>`
        if (status === 'inactive') {
          //language=HTML
          return `
			  <button class="gh-button action update-and-launch">
				  ${_x('Launch', 'when publishing a funnel', 'groundhogg')}
				  ${icons.rocket}
			  </button>${moreMenu}`
        } else {
          //language=HTML
          return `
			  <button class="deactivate gh-button text danger">
				  ${_x('Deactivate', 'when deactivating a funnel', 'groundhogg')}
			  </button>
			  <button class="update gh-button primary"
			          ${objectEquals(
				          Editor.funnel.steps,
				          Editor.origFunnel.steps
			          ) || Object.keys(Editor.stepErrors).length > 0
				          ? 'disabled'
				          : ''
			          }>
				  ${icons.save}
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

        return `<h1 class="breadcrumbs"><span class="root">${__('Funnels', 'groundhogg')}</span><span class="sep">/</span>${isEditing ? titleEdit() : titleDisplay()}</h1>`
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
						  `<label class="row-label">${__('Allow contacts to enter the funnel at this step?', 'groundhogg')}</label>
					  ${toggle({
							  name: 'is_entry_point',
							  id: 'is-entry-point',
							  checked: data.is_entry,
							  onLabel: _x('YES', 'toggle switch', 'groundhogg'),
							  offLabel: _x('NO', 'toggle switch', 'groundhogg'),
						  })}
				  </div>`}
					  <div class="row">
						  <label
							  class="row-label">${__('Track a conversion whenever this step is completed.', 'groundhogg')}</label>
						  ${toggle({
							  name: 'is_conversion',
							  id: 'is-conversion',
							  checked: data.is_conversion,
							  onLabel: _x('YES', 'toggle switch', 'groundhogg'),
							  offLabel: _x('NO', 'toggle switch', 'groundhogg'),
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
                  `<li class="step-error"><span class="dashicons dashicons-warning"></span> ${error}</li>`
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
                  `<li class="step-warning"><span class="dashicons dashicons-warning"></span> ${warning}</li>`
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
						<label class="row-label"><span class="dashicons dashicons-admin-comments"></span> ${__('Notes', 'groundhogg')}</label>
						${textarea({
          rows: 4,
          id: 'step-notes',
          class: 'notes full-width',
          name: 'step_notes',
          value: meta.step_notes,
          placeholder: __('Notes about the step...', 'groundhogg')
        })}	</div>
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
							${_x('Actions', 'group of step types', 'groundhogg')}
						</button>
						<button class="select-type benchmarks ${
							activeType === 'benchmarks' && 'active'
						}"
						        data-type="benchmarks">
							${_x('Benchmarks', 'group of step types', 'groundhogg')}
						</button>
					</div>
					${input({
						id: 'search-steps',
						name: 'search_steps',
						type: 'search',
						className: 'search-steps',
						placeholder: _x('Search...', 'groundhogg'),
						value: search,
					})}
					${select(
						{
							id: 'pack-filter',
							name: 'pack_filter',
						},
						[
							{ text: __('Filter by pack...', 'groundhogg'), value: '' },
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
					? `<div class="text-helper until-helper"><span class="dashicons dashicons-filter"></span>${_x('Start the funnel when...', 'showing at the top of the step flow', 'groundhogg')}
          </div>`
					: prevStep && prevStep.data.step_group !== 'benchmark'
						? `<div class="until-helper text-helper">${_x('Until...', 'before a group of benchmarks in the step flow', 'groundhogg')}</div>`
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
					? `<div class="or-helper text-helper">` + _x('Or...', 'between to benchmarks in the step flow', 'groundhogg') + `</div>`
					: '<div class="then-helper text-helper">' + _x('Then...', 'before a group of actions in the step flow', 'groundhogg') + '</div>'
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
              { key: 'edit', text: __('Edit') },
              { key: 'move-up', text: __('Move up', 'to move a step before another step', 'groundhogg') },
              { key: 'move-down', text: _x('Move down', 'to move a step after another step', 'groundhogg') },
              { key: 'duplicate', text: _x('Duplicate', 'to duplicate a step', 'groundhogg') },
              {
                key: 'delete',
                text: '<span class="gh-text danger">' + __('Delete') + '</span>'
              },
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
						  <p><b>${__('Add this funnel to one or more campaigns...', 'groundhogg')}</b>
						  </p>
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
                  alert: `<p><b>${__('Delete this funnel?', 'groundhogg')}</b></p>
				  <p>
					  ${__('Any associated events, steps, and reports will also be deleted.', 'groundhogg')}</p>
				  <p>${__('This action cannot be undone. Are you sure?', 'groundhogg')}</p>`,
                  confirmText: __('Delete'),
                  onConfirm: () => {
                    console.log('yikes')
                  }
                })

                break
              case 'archive':

                dangerConfirmationModal({
                  //language=HTML
                  alert: `<p>
					  <b>${_x('Archive this funnel?', 'archive is representing a verb in this phrase', 'groundhogg')}</b>
				  </p>
				  <p>
					  ${__('Any active contacts will be removed from the funnel permanently.', 'groundhogg')}</p>
				  <p>${__('The funnel will become un-editable until restored.', 'groundhogg')}</p>`,
                  confirmText: _x('Archive', 'a verb meaning to add an item to an archive', 'groundhogg'),
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
              text: `${icons.megaphone} ${_x('Campaigns', 'noun meaning collection of marketing materials', 'groundhogg')}`
            },
            {
              key: 'export',
              //language=HTML
              text: `${icons.export} ${_x('Export', 'a verb meaning to download', 'groundhogg')}`
            },
            {
              key: 'share',
              //language=HTML
              text: `${icons.share} ${_x('Share', 'a verb meaning to share something', 'groundhogg')}`
            },
            {
              key: 'reports',
              //language=HTML
              text: `${icons.chart} ${__('Reports', 'groundhogg')}`
            },
            {
              key: 'archive',
              //language=HTML
              text: `${icons.folder} <span
				  class="gh-text danger">${_x('Archive', 'a verb meaning to add an item to an archive', 'groundhogg')}</span>`
            },
            {
              key: 'delete',
              //language=HTML
              text: `${icons.trash} <span class="gh-text danger">${__('Delete')}</span>`
            },
          ]
        })
      })

      $doc.on('click', '.publish-actions .deactivate', function () {
        dangerConfirmationModal({
          // language=HTML
          alert: `<p><b>${__('Are you sure you want to deactivate the funnel?', 'groundhogg')}</b></p>
		  <p>
			  ${__('Active contacts will be paused until the funnel is reactivated.', 'groundhogg')}</p>`,
          confirmText: _x('Deactivate', 'when deactivating a funnel', 'groundhogg'),
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
            alert: `<p><b>${__('Are you sure you want to commit these changes?', 'groundhogg')}</b></p>
			<p>
				${__('The changes made will take immediate effect to anyone currently in the funnel.', 'groundhogg')}</p>`,
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
          __('Funnels', 'groundhogg'),
          `<span id="back-to-funnel" style="cursor: pointer">${specialChars(this.funnel.data.title)}</span>`,
          __('Add Email', 'groundhogg')
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
        content: __('Undo')
      })

      const { close: cRedo } = tooltip('.redo', {
        content: __('Redo')
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
      const { close } = loadingModal(__('Launching', 'groundhogg'))

      this.update({
        data: {
          status: 'active',
        },
      }).then(() => close()).then(() => dialog({
        message: __('Funnel activated!', 'groundhogg')
      }))
    },

    deactivate () {
      const { close } = loadingModal(__('Deactivating', 'groundhogg'))

      this.update({
        data: {
          status: 'inactive',
        },
      }).then(() => close()).then(() => dialog({
        message: __('Funnel deactivated!', 'groundhogg')
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
            message: __('Funnel updated!', 'groundhogg')
          })
        }
      })
        .then(() => close())
    },

    setLastSaved () {
      clearInterval(this.lastSavedTimer)

      this.lastSavedTimer = setInterval(
        this.updateLastSaved,
        30 * 1000,
        new Date()
      ) // 30 seconds
      this.updateLastSaved(new Date())
    },

    /**
     * Update the header-actions attr
     *
     *
     * @link https://stackoverflow.com/a/7641812
     * @param lastSaved
     */
    updateLastSaved (lastSaved) {
      $('.header-actions').attr('data-lastSaved', sprintf(_x('Saved %s ago', 'time passed since last update', 'groundhogg'), moment(lastSaved).fromNow(true)))
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
        `<div class="step-placeholder">${__('Choose a step to add here', 'groundhogg')} &rarr;<button type="button" class="button button-secondary">${__('Cancel')}</button></div>`
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
          <p><b>${__('Delete this step?', 'groundhogg')}</b></p>
          <p>${__('Active contacts at this step will be removed from the funnel when it is updated.', 'groundhogg')}</p> 
        `,
          confirmText: __('Delete'),
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
		  <button style="width: 100%" id="edit-email-right" class="gh-button secondary">
			  ${__('Edit Email', 'groundhogg')}
		  </button>
		  <div class="panel">
			  <div class="row">
				  <div class="column">
					  <label
						  class="row-label">${__('Select a different email to send...', 'groundhogg')}</label>
					  ${select({
							  id: 'email-picker-right',
							  name: 'email_id',
						  },
						  EmailsStore.getItems().map((item) => {
							  return {
								  text: item.data.title,
								  value: item.ID,
							  }
						  }), email && email.ID
					  )}
				  </div>
			  </div>
			  <div class="row">
				  <div class="column">
					  <label class="row-label">Or...</label>
					  <button id="add-new-email-right" class="gh-button secondary">
						  ${__('Create a new email', 'groundhogg')}
					  </button>
				  </div>
			  </div>
		  </div>
      `
    },
    onMount ({ ID, meta }, updateStepMeta) {

      const { email_id } = meta
      const email = EmailsStore.get(parseInt(email_id))

      if (!email_id || !email) {
        return
      }

      $('#edit-email-right').on('click', () => {
        Editor.renderEmailEditor(email)
      })

      $('#add-new-email-right').on('click', () => {
        Editor.renderEmailTemplatePicker(updateStepMeta)
      })


      emailPicker('#email-picker-right', false, (items) => EmailsStore.itemsFetched(items), {}, { width: '100%' })
        .on('change', (e) => {

          updateStepMeta({
            email_id: e.target.value
          }, true)

        })
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
				  <label class="row-label">${__('Embed via Shortcode', 'groundhogg')}</label>
				  <div class="embed-option">${copyValue(`[gh_form id="${ID}"]`)}
				  </div>
			  </div>
			  <div class="row">
				  <label class="row-label">${__('Embed via iFrame', 'groundhogg')}</label>
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
