(function ($, Templates) {

  const { select, regexp, modal, input, primaryButton, loadingDots } = Groundhogg.element
  const { templates } = Templates
  const { post, get, routes } = Groundhogg.api

  const setFrameContent = (frame, content) => {
    var blob = new Blob([content], { type: 'text/html; charset=utf-8' })
    frame.src = URL.createObjectURL(blob)
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

  const createEmail = (template) => {
    return post(`${routes.v4.emails}`, {
      ...template,
      ID: false
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

  const Add = {

    search: '',
    selectedTemplate: '',

    renderTemplate (template) {

      const { ID, data, meta, steps } = template

      //language=html
      return `
		  <div class="gh-panel template" tabindex="0">
			  <div class="subject-and-preview">
				  <div class="subject-wrap">Subject: <span class="subject">${data.subject}</span></div>
				  <div class="preview-wrap">Preview: <span class="preview-text">${data.pre_header}</span></div>
			  </div>
			  <div class="template-preview">
				  <div class="template-content">
					  ${data.content}
				  </div>
				  <div class="template-actions">
					  <button class="gh-button primary select-template" data-template="${ID}">Use Template</button>
				  </div>
			  </div>
		  </div>`
    },

    render () {
      //language=HTML
      return `
		  <div>
			  <div id="header">
				  <h1>Choose a template</h1>
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
					  <button id="import-button" class="gh-button secondary">Import</button>
					  <button id="scratch-button" class="gh-button secondary">Start From Scratch</button>
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

    getTemplates () {
      return templates.filter(t => {

        if (!this.search) {
          return true
        }

        return t.data.title.match(regexp(this.search))
      })
    },

    titleModal () {
      const modalContent = (isCreating = false) => {

        //language=HTML
        return `
			<div id="template-name"><h2>Name your email</h2>
				${input({
					id: 'title-input',
					placeholder: 'Title',
					value: this.newTitle,
					disabled: isCreating,
				})}
				${primaryButton({
					id: 'create',
					className: 'medium bold',
					text: isCreating ? 'Creating' : 'Creat Email',
					disabled: isCreating || !this.newTitle
				})}
			</div>`
      }

      const { close, setContent } = modal({
        content: modalContent()
      })

      const handleCreate = () => {
        setContent(modalContent(true))
        loadingDots('#create')

        createEmail({
          ...this.selectedTemplate,
          data: {
            ...this.selectedTemplate.data,
            title: this.newTitle
          },
        }).then(email => {
          window.location.href = email.admin
        })
      }

      $('#create').on('click', handleCreate)

      $('#title-input').focus().on('change input keydown', (e) => {

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

      $('#view').html(this.renderTemplates())

      $('button.select-template').on('click', (e) => {
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
				<h1>Import your Email</h1>
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
						<a href="#" class="cancel action-link">&larr; Cancel</a>
					</div>
				</div>
			</div>`
      }

      //language=HTML
      return `
		  <div id="import">
			  <h1>Import your Email</h1>
			  <input type="file" id="import-file" name="import" accept=".email,.html"/>
			  ${this.importError ? `<p class="error">${this.importError}</p>` : ''}
			  <p><a class="action-link cancel">&larr; Cancel</a></p>
		  </div>`
    },

    mountImport () {
      $('#view').html(this.renderImport())

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
          this.importError = 'The provided file is not a valid funnel.'
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
          this.importError = 'The provided file is not a valid funnel.'
          this.mountImport()
          this.importError = ''
        } else {
          this.importError = ''
          this.importTemplate = template
          this.mountImport()
        }
      }

      $('a.cancel').on('click', () => {
        this.reset()
        this.mountTemplates()
      })

      $('#import-file').on('change', (e) => {
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
      $('#add-email').html(this.render())
      this.mountTemplates()

      $('#search').on('input change', (e) => {
        this.search = e.target.value
        this.reset()
        this.mountTemplates()
      })

      $('#import-button').on('click', (e) => {
        this.reset()
        this.mountImport()
      })

      $('#scratch-button').on('click', (e) => {
        this.reset()
        this.selectedTemplate = {
          data: {},
          meta: {},
        }
        this.titleModal()
      })
    }

  }

  $(() => {
    Add.mount()
  })

})(jQuery, AddEmail)