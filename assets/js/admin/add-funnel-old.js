(function ($, StepsAndTemplates) {

  const { select, regexp, specialChars } = Groundhogg.element
  const { stepTypes, templates } = StepsAndTemplates
  const { post, get } = Groundhogg.api

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

  const Add = {

    search: '',
    selectedTemplate: '',

    renderTemplate (template) {

      const { ID, data, meta, steps } = template

      const stepCount = steps.length
      const canUse = hasRequiredSteps(steps)

      const numActions = steps.filter(({ data }) => data.step_group === 'action').length
      const numBenchmarks = steps.filter(({ data }) => data.step_group === 'benchmark').length
      const pills = [
        `<span class="pill">${stepCount} steps</span>`,
        `<span class="pill green">${numActions} ${numActions === 1 ? 'action' : 'actions'}</span>`,
        `<span class="pill orange">${numBenchmarks} ${numBenchmarks === 1 ? 'benchmark' : 'benchmarks'}</span>`,
        // canUse ? 'enabled' : 'disabled',
      ]

      //language=html
      return `
		  <div class="gh-panel template ${canUse ? 'enabled' : 'disabled'}" tabindex="0">
			  <div class="template-header">
				  <h2>${data.title}</h2>
				  <div class="actions" tabindex="0">
					  <button data-template="${ID}" class="select-template gh-button primary"
					          ${canUse ? '' : 'disabled'}>Use Template
					  </button>
				  </div>
			  </div>
			  <div class="inside">
				  <p>${meta.description || ''}</p>
				  <p>
					  ${pills.join('')}
				  </p>
			  </div>
		  </div>`
    },

    render () {
      //language=HTML
      return `
		  <div>
			  <div id="header">
				  <h3>Select a funnel template</h3>
				  <div class="search-templates">
					  ${select({
						  id: 'campaign',
						  name: 'campaign',
					  }, {
						  a: 'campaign a',
						  b: 'campaign b',
					  })}
					  <input type="search" id="search" name="search" placeholder="Search templates" value=""/>
				  </div>
				  <div class="alternate">
					  <button id="import" class="gh-button secondary">Import</button>
					  <button id="scratch" class="gh-button secondary">Start From Scratch</button>
				  </div>
			  </div>
			  <div id="view"></div>
		  </div>`
    },

    renderTemplates () {
      return `<div id="templates">${rowsOf(3, this.getTemplates()
        .map(t => this.renderTemplate(t)))
        .map(r => `<div class="row">${fillRow(3, '<div class="fill"></div>', r)
          .join('')}</div>`).join('')}</div>`
    },

    renderFunnelTitleInput (title = '') {
      //language=HTML
      return `
		  <div id="funnel-title-input">
			  <h1>Name your Funnel</h1>

			  <div class="gh-panel">
				  <div class="inside">
					  <input type="text" id="title" name="title" value="${specialChars(title)}">
					  <div class="submit">
						  <button id="create-funnel" class="gh-button primary">Create Funnel</button>
					  </div>
				  </div>
			  </div>
			  <p><a class="cancel">&larr; Cancel</a></p>
		  </div>`
    },

    renderImport () {
      //language=HTML
      return `
		  <div id="funnel-import">
			  <h1>Import your Funnel</h1>
			  <input type="file" id="import-file" name="import" accept=".funnel"/>
			  ${this.importError ? `<p class="error">${this.importError}</p>` : ''}
			  <p><a class="cancel">&larr; Cancel</a></p>
		  </div>`
    },

    getTemplates () {
      return templates.filter(t => {

        if (!this.search) {
          return true
        }

        return t.data.title.match(regexp(this.search))
      })
    },

    mountTemplates () {
      $('#view').html(this.renderTemplates())
      $('button.select-template').on('click', (e) => {
        const templateId = e.target.dataset.template
        const template = templates.find(t => t.ID == templateId)
        this.selectedTemplate = template
        this.mountFunnelTitle(template.data.title)
      })
    },

    mountFunnelTitle (title = '') {
      $('#view').html(this.renderFunnelTitleInput(title))

      this.newFunnelTitle = title

      $('#title').select().focus().on('change', (e) => {
        this.newFunnelTitle = e.target.value
      })

      $('a.cancel').on('click', () => {
        this.mountTemplates()
      })

      $('#create-funnel').on('click', (e) => {

        let { selectedTemplate } = this

        // Starting from scratch
        if (!selectedTemplate) {
          selectedTemplate = {
            data: {
              title: this.newFunnelTitle
            },
            steps: []
          }
        } // new template format
        else if (selectedTemplate.data) {
          selectedTemplate.data.title = this.newFunnelTitle
        } // legacy template format
        else {
          selectedTemplate.title = this.newFunnelTitle
        }

        post(`${Groundhogg.api.routes.v4.funnels}/import`, selectedTemplate).then(d => {
          if (d.item) {
            window.location.href = `${Groundhogg.url.admin}admin.php?page=gh_funnels&action=edit&funnel=${d.item.ID}`
          }
        })
      })
    },

    mountImport () {
      $('#view').html(this.renderImport())

      const onReaderLoad = (e) => {
        const template = JSON.parse(e.target.result)

        if (!template || !hasRequiredSteps(template.steps)) {
          if (!template) {
            this.importError = 'The provided file is not a valid funnel.'
          } else {
            this.importError = 'This funnel contains step types which are not registered on your site. Choose another funnel to import.'
          }
          this.mountImport()
          this.importError = ''
        } else {
          this.selectedTemplate = template
          this.mountFunnelTitle(template.title || template.data.title)
        }
      }

      $('a.cancel').on('click', () => {
        this.mountTemplates()
      })

      $('#import-file').on('change', (e) => {
        const files = e.target.files

        const reader = new FileReader()
        reader.onload = onReaderLoad
        reader.readAsText(files[0])
      })
    },

    createFunnel () {

    },

    mount () {
      $('#add-funnel').html(this.render())
      this.mountTemplates()

      $('#search').on('input change', (e) => {
        this.search = e.target.value
        this.mountTemplates()
      })

      $('#scratch').on('click', () => {
        this.selectedTemplate = false
        this.mountFunnelTitle('')
      })

      $('#import').on('click', () => {
        this.mountImport()
      })
    }

  }

  $(() => {
    Add.mount()
  })

})(jQuery, AddFunnel)