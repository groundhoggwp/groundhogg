(function ($, Templates) {

  const { breadcrumbs, select, regexp, modal, input, primaryButton, loadingDots, setFrameContent } = Groundhogg.element
  const { templates, stepTypes } = Templates
  const { post, get, routes } = Groundhogg.api
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting

  /**
   * If all the step types in the given template are active on the current site
   *
   * @param steps
   * @returns bool
   */
  const hasRequiredSteps = (steps) => {
    return steps.reduce((hasStep, { data, type }) => {

      if (data) {
        return stepTypes.hasOwnProperty(data.step_type) && hasStep
      } else if (type) {
        return stepTypes.hasOwnProperty(type) && hasStep
      }

      return false
    }, true)
  }

  /**
   * Reduce a list of items into groups of arrays of a given length
   *
   * @param size
   * @param items
   * @returns {*}
   */
  const rowsOf = (size, items) => {
    return items.reduce((rows, item) => {
      if (rows[rows.length - 1].length === size) {
        rows.push([])
      }

      rows[rows.length - 1].push(item)

      return rows
    }, [[]])
  }

  const createFunnel = (template) => {
    return post(`${routes.v4.funnels}/import`, {
      ...template,
    }).then(r => r.item)
  }

  /**
   * Ensure that a list of items has the given number of items
   * fill the remaining space with a given item if size requirment is not met
   *
   * @param size
   * @param fill
   * @param row
   * @returns {*}
   */
  const fillRow = (size, fill, row) => {
    while (row.length < size) {
      row.push(fill)
    }
    return row
  }

  const FunnelTemplatePicker = ({
    selector,
    breadcrumbs: crumbs = [
      'Funnels',
      'Add New'
    ],
    onSelect = (funnel) => {
      console.log(funnel)
    },
    afterHeaderActions = '',
    onMount = () => {}
  }) => ({

    search: '',
    selectedTemplate: '',
    $el: $(selector),

    renderTemplate: (template) => {

      const { ID, data, meta, steps, campaigns } = template

      const stepCount = steps.length
      const canUse = hasRequiredSteps(steps)

      const numActions = steps.filter(({ data }) => data.step_group === 'action').length
      const numBenchmarks = steps.filter(({ data }) => data.step_group === 'benchmark').length
      const pills = [
        `<span class="pill orange">${sprintf(_n('%d benchmark', '%d benchmarks', numBenchmarks, 'groundhogg'), numBenchmarks)}</span>`,
        `<span class="pill green">${sprintf(_n('%d action', '%d actions', numActions, 'groundhogg'), numActions)}</span>`,
        `<span class="pill">${sprintf(__('Added %s', 'groundhogg'), formatDate(data.date_created))}</span>`
      ]

      //language=html
      return `
		  <div class="gh-panel template ${canUse ? __('enabled', 'groundhogg') : __('disabled', 'groundhogg')}"
		       tabindex="0">
			  <div class="template-header">
				  <h2>${data.title}</h2>
			  </div>
			  <div class="inside">
				  <p>${meta.description || ''}</p>
				  <p>
					  <b>${__('Details', 'groundhogg')}</b>
				  </p>
				  <p class="display-flex gap-10">
					  ${pills.join('')}
				  </p>
				  <p>
					  <b>${__('Campaigns', 'groundhogg')}</b>
				  </p>
				  <p class="display-flex gap-10 flex-wrap">
					  ${campaigns.map(c => `<span class="pill">${c.data.name}</span>`).join('')}
				  </p>
				  <p class="actions">
					  ${hasRequiredSteps(steps) ?
						  `<button data-template="${ID}" class="gh-button primary small select-template">Import</button>` : `<span class="gh-text danger">${__('You do not have the required extensions installed for this template.', 'groundhogg')}</span>`}
				  </p>
			  </div>
		  </div>`
    },

    render () {
      //language=HTML
      return `
		  <div class="templates-picker">
			  <div id="header" class="gh-header is-sticky">
				  <div class="title-wrap">
					  <h1 class="breadcrumbs">
						  ${breadcrumbs(crumbs)}
					  </h1>
				  </div>
				  <div class="search-templates">
					  ${select({
						  id: 'campaign',
						  name: 'campaign',
						  multiple: true,
						  style: {
							  width: '300px',
						  }
					  }, templates.reduce((carry, curr) => {
						  if (curr.campaigns) {
							  carry.push(...curr.campaigns.map(c => ({
								  text: c.data.name,
								  value: c.data.slug
							  })).filter(c => !carry.find(_c => c.value === _c.value)))
						  }
						  return carry
					  }, []))}
					  <input type="search" id="search" name="search" placeholder="Search templates" value=""/>
				  </div>
				  <div class="template-actions">
					  <button id="import-button" class="gh-button secondary">${__('Import', 'groundhogg')}</button>
					  <button id="scratch-button" class="gh-button secondary">
						  ${__('Start From Scratch', 'groundhogg')}
					  </button>
					  ${afterHeaderActions}
				  </div>
			  </div>
			  <div id="view"></div>
		  </div>`
    },

    renderTemplates () {
      return `<div id="templates">${ this.getTemplates()
      .map(t => this.renderTemplate(t)).join('')}</div>`
    },

    getTemplates () {
      return templates.filter(t => {

        if (!this.search) {
          return true
        }

        return t.data.title.match(regexp(this.search))
      }).filter(t => {

        if (!this.campaign || !this.campaign.length) {
          return true
        }

        return t.campaigns.find(c => this.campaign.includes(c.data.slug))

      }).sort((a, b) => (hasRequiredSteps(a.steps) === hasRequiredSteps(b.steps)) ? 0 : hasRequiredSteps(a.steps) ? -1 : 1)
    },

    titleModal () {
      const modalContent = (isCreating = false) => {

        //language=HTML
        return `
			<div id="template-name"><h2>${__('Name your funnel', 'groundhogg')}</h2>
				${input({
					id: 'title-input',
					placeholder: 'Title',
					value: this.newTitle,
					disabled: isCreating,
				})}
				${primaryButton({
					id: 'create',
					className: 'medium bold',
					text: isCreating ? __('Importing', 'groundhogg') : __('Import Funnel', 'groundhogg'),
					disabled: isCreating || !this.newTitle
				})}
			</div>`
      }

      const { close, setContent } = modal({
        content: modalContent()
      })

      const handleCreate = () => {
        setContent(modalContent(true))
        loadingDots(`#create`)

        createFunnel({
          ...this.selectedTemplate,
          data: {
            ...this.selectedTemplate.data,
            title: this.newTitle
          },
        }).then(funnel => {
          close()
          onSelect(funnel)
        })
      }

      $(`#create`).on('click', handleCreate)

      $(`#title-input`).focus().on('change input keydown', (e) => {

        this.newTitle = e.target.value

        if (this.newTitle) {
          $('#create').prop('disabled', false)
        } else {
          $('#create').prop('disabled', true)
        }

        if (e.type === 'keydown' && e.key === 'Enter' && this.newTitle) {
          handleCreate()
        }
      })
    },

    mountTemplates () {

      $(`${selector} #view`).html(this.renderTemplates())

      $(`${selector} button.select-template`).on('click', (e) => {
        const templateId = e.target.dataset.template
        const template = templates.find(t => t.ID == templateId)
        this.selectedTemplate = template
        this.newTitle = this.selectedTemplate.data.title

        this.titleModal()
      })
    },

    renderImport () {

      if (this.importTemplate) {
        //language=HTML
        return `
			<div id="import">
				<h1>${__('Import your Email', 'groundhogg')}</h1>
				<div class="template-preview-wrap">
					<div class="import-template-preview">
						<iframe id="template-preview"></iframe>
					</div>
					<div class="template-actions">
						${primaryButton({
							text: 'Use this template',
							id: 'create-from-import',
							className: 'big bold loud'
						})}
						<a href="#" class="cancel action-link">&larr; ${__('Cancel', 'groundhogg')}</a>
					</div>
				</div>
			</div>`
      }

      //language=HTML
      return `
		  <div id="import">
			  <h1>${__('Import your Funnel', 'groundhogg')}</h1>
			  <input type="file" id="import-file" name="import" accept=".funnel"/>
			  ${this.importError ? `<p class="error">${this.importError}</p>` : ''}
			  <p><a class="action-link cancel">&larr; ${__('Cancel', 'groundhogg')}</a></p>
		  </div>`
    },

    mountImport: function () {
      $(`${selector} #view`).html(this.renderImport())

      if (this.importTemplate) {
        setFrameContent($('#template-preview')[0], this.importTemplate.data.content)

        $('#create-from-import').on('click', (e) => {
          this.selectedTemplate = this.importTemplate
          this.newTitle = this.selectedTemplate.data.title || ''
          this.titleModal()
        })
      }

      const onReaderLoadHTML = (e) => {
        const content = e.target.result

        if (!content) {
          this.importError = __('The provided file is not a valid funnel.', 'groundhogg')
          this.mountImport()
          this.importError = ''
          return
        }

        this.importTemplate = {
          data: {
            content
          },
          meta: {
            type: 'html'
          }
        }

        this.mountImport()
      }

      const onReaderLoadJSON = (e) => {
        const template = JSON.parse(e.target.result)

        if (!template) {
          this.importError = __('The provided file is not a valid funnel.', 'groundhogg')
          this.mountImport()
          this.importError = ''
        } else {
          this.importError = ''
          this.importTemplate = template
          this.mountImport()
        }
      }

      $(`${selector} a.cancel`).on('click', () => {
        this.reset()
        this.mountTemplates()
      })

      $(`${selector} #import-file`).on('change', (e) => {
        const files = e.target.files

        const reader = new FileReader()

        const file = files[0]
        const ext = file.name.split('.').pop().toLowerCase()

        switch (ext) {
          case 'html':
            reader.onload = onReaderLoadHTML
            reader.readAsText(file)
            return
          case 'json':
            reader.onload = onReaderLoadJSON
            reader.readAsText(file)
            return
        }

      })
    },

    reset () {
      this.selectedTemplate = false
      this.importTemplate = false
      this.newTitle = ''
    },

    mount () {

      this.$el.html(this.render())
      this.mountTemplates()

      $(`${selector} #campaign`).select2({
        placeholder: 'Filter by campaign...'
      }).on('change', (e) => {
        this.campaign = $(e.target).val()
        this.reset()
        this.mountTemplates()
      })

      $(`${selector} #search`).on('input change', (e) => {
        this.search = e.target.value
        this.reset()
        this.mountTemplates()
      })

      $(`${selector} #import-button`).on('click', (e) => {
        this.reset()
        this.mountImport()
      })

      $(`${selector} #scratch-button`).on('click', (e) => {
        this.reset()
        this.selectedTemplate = {
          data: {},
          meta: {},
        }
        this.titleModal()
      })

      onMount()
    }
  })

  Groundhogg.FunnelTemplatePicker = FunnelTemplatePicker

})(jQuery, AddFunnel)