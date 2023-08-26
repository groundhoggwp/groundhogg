( function ($, funnel) {

  const { uuid } = Groundhogg.element
  const { patch, routes, ajax } = Groundhogg.api

  $.extend(funnel, {

    sortables: null,
    insertAfterStep: false,
    currentlyActive: false,

    getSteps: function () {
      return $('#step-sortable')
    },

    getSettings: function () {
      return $('.step-settings')
    },

    init: function () {

      var self = this

      var $document = $(document)
      var $form = $('#funnel-form')
      var $steps = self.getSteps()
      var $settings = self.getSettings()

      $document.on('change input', '.step-title-large', function () {
        var $title = $(this)
        var id = $title.attr('data-id')
        var $step = $('#' + id)
        $step.find('.step-title').text($title.val())
      })

      $document.on('click', '#full-screen', () => {
        $(document.body).toggleClass('funnel-full-screen')

        ajax({
          action: 'gh_funnel_editor_full_screen_preference',
          full_screen: $(document.body).hasClass('funnel-full-screen') ? 1 : 0
        })

      })

      $document.on('click', '#step-flow .step:not(.step-placeholder)', function (e) {

        if ($(e.target).is('.dashicons, button')) {
          return
        }

        self.makeActive(this.id, e)
      })

      $document.on('click', '.add-step-bottom-wrap button', e => {
        this.showAddStep()
      })

      $document.on('click', '#step-toggle button', e => {

        let group = e.currentTarget.dataset.group

        $(`.steps-grid`).addClass('hidden')
        $(`#${ group }`).removeClass('hidden')

        $('#step-toggle button').removeClass('active')
        $(e.currentTarget).addClass('active')

      })

      /* Bind Delete */
      $document.on('click', 'button.delete-step', function (e) {
        self.deleteStep(this.parentNode.parentNode.id)
      })

      /* Bind Duplicate */
      $document.on('click', 'button.duplicate-step', function (e) {
        self.duplicateStep(this.parentNode.parentNode.id)
      })

      /* Activate Spinner */
      $form.on('submit', function (e) {
        e.preventDefault()
        return false
      })

      $document.on('click', '#update', function (e) {
        self.save($form)
      })

      /* Auto save */
      $document.on('change', '.auto-save', function (e) {
        e.preventDefault()
        self.save($form)
      })

      /* Auto save */
      $document.on('auto-save', function (e) {
        e.preventDefault()
        self.save($form)
      })

      // Funnel Title
      $document.on('click', '.title-view .title', function (e) {
        $('.title-view').hide()
        $('.title-edit').show().removeClass('hidden')
        $('#title').focus()
      })

      $document.on('blur change', '#title', function (e) {

        var title = $(this).val()

        $('.title-view').find('.title').text(title)
        $('.title-view').show()
        $('.title-edit').hide()
      })

      // Step Title
      $document.on('click', '.step-title-view .title', function (e) {

        var $step = $(this).closest('.step')

        $step.find('.step-title-view').hide()
        $step.find('.step-title-edit').show().removeClass('hidden')
        $step.find('.step-title-edit .edit-title').focus()
      })

      $document.on('blur change', '.edit-title', function (e) {

        var $step = $(this).closest('.step')

        var title = $(this).val()

        $step.find('.step-title-view').find('.title').text(title)
        $step.find('.step-title-view').show()
        $step.find('.step-title-edit').hide()
      })

      $document.on('click', '#enter-full-screen', function (e) {
        $('html').toggleClass('full-screen')
      })

      if (window.innerWidth > 600) {
        this.makeSortable()
      }

      $('#add-contacts-button').click(function () {
        self.addContacts()
      })

      $('#copy-share-link').click(function (e) {
        e.preventDefault()
        prompt('Copy this link.', $('#share-link').val())
      })

      if (window.location.hash) {
        this.makeActive(parseInt(window.location.hash.substring(1)))
      }

      let email_ids = this.steps.filter( step => step.data.step_type === 'send_email' ).map( step => parseInt( step.meta.email_id ) ).filter( id => Boolean(id) )
      if ( email_ids.length ){
        Groundhogg.stores.emails.maybeFetchItems( email_ids )
      }

    },

    async save ($form) {

      if (typeof $form === 'undefined') {
        $form = $('#funnel-form')
      }

      var self = this

      var $saveButton = $('.save-button')

      $('body').addClass('saving')

      $saveButton.html(self.saving_text)
      $saveButton.addClass('spin')

      var fd = $form.serialize()
      fd += '&action=gh_save_funnel_via_ajax&version=2'

      if (this._delete_step) {
        fd += '&_delete_step=' + this._delete_step
      }

      // Update the JS meta changes first
      if (Object.keys(this.metaUpdates).length) {

        let changes = Object.keys(this.metaUpdates).map(ID => ( {
          ID,
          meta: {
            ...this.metaUpdates[ID],
          },
        } ))

        let response = await patch(routes.v4.steps, changes)
        // reset
        this.metaUpdates = {}
      }

      // Do regular form update
      adminAjaxRequest(fd, (response) => {
        handleNotices(response.data.notices)
        this.steps = response.data.data.steps

        setTimeout(function () {
          $('.notice-success').fadeOut()
        }, 3000)

        $saveButton.removeClass('spin')
        $saveButton.html(self.save_text)

        self.getSettings().html(response.data.data.settings)
        self.getSteps().html(response.data.data.sortable)

        $(document).trigger('new-step')

        $('body').removeClass('saving')
        self.makeActive(self.currentlyActive)
      })
    },

    makeSortable () {
      this.sortables = $('.ui-sortable').sortable({
        placeholder: 'sortable-placeholder',
        connectWith: '.ui-sortable',
        receive: (e, ui) => {

          var data = {
            action: 'wpgh_get_step_html',
            step_type: ui.item.prop('id'),
            step_group: ui.item.data('group'),
            after_step: ui.helper.prev().prop('id'),
            funnel_id: this.id,
            version: 2,
          }

          this.insertAfterStep = data.after_step

          let id = uuid()
          // language=HTML
          ui.helper.replaceWith(`
              <div class="step step-placeholder ${ data.step_group }" id="${ id }">
                  Loading...
              </div>`)

          var self = this
          var $steps = self.getSteps()
          var $settings = self.getSettings()

          showSpinner()
          adminAjaxRequest(data, (response) => {

            this.steps.push(response.data.data.json)

            if (self.insertAfterStep) {
              $(`#${ self.insertAfterStep }`).after(response.data.data.sortable)
            }
            else {
              $steps.prepend(response.data.data.sortable)
            }

            $settings.append(response.data.data.settings)
            $(`#${ id }`).remove()

            hideSpinner()
            $(document).trigger('new-step')
          })
        },
      })

      this.sortables.disableSelection()

      $('.wpgh-element.ui-draggable').draggable({
        connectToSortable: '#step-sortable.ui-sortable',
        helper: 'clone',
      })
    },

    showAddStep () {
      $('#add-steps').removeClass('hidden')
      $('.step-settings').addClass('hidden')
      $('#step-sortable .step').removeClass('active')

      this.currentlyActive = false
    },

    /**
     * Given an element delete it
     *
     * @param id int
     */
    deleteStep: function (id) {

      var self = this

      var $step = $('#' + id)
      var result = confirm(
        'Are you sure you want to delete this step? Any pending events for this step will be removed.')

      if (result) {

        self._delete_step = id

        $step.fadeOut( 400, () => {
          $step.remove()

          let sid = '#settings-' + id
          let $step_settings = $(sid)

          $step_settings.remove()

          self.save()

          if (this.currentlyActive === id) {
            self.showAddStep()
          }

          self._delete_step = false
        })
      }
    },

    /**
     * Given an element, duplicate the step and
     * Add it to the funnel
     *
     * @param id int
     */
    duplicateStep: function (id) {
      this.insertAfterStep = id
      var data = {
        action: 'wpgh_duplicate_funnel_step',
        step_id: id,
        version: 2,
      }
      this.getStepHtml(data)
    },

    /**
     * Performs an ajax call and replaces
     *
     * @param obj
     */
    getStepHtml: function (obj) {
      var self = this
      var $steps = self.getSteps()
      var $settings = self.getSettings()

      adminAjaxRequest(obj, (response) => {

        this.steps.push(response.data.data.json)

        if (self.insertAfterStep) {
          $(`#${ self.insertAfterStep }`).after(response.data.data.sortable)
        }
        else {
          $steps.append(response.data.data.sortable)
        }

        $settings.append(response.data.data.settings)

        $(document).trigger('new-step')
      })
    },

    getActiveStep () {
      return this.steps.find(s => s.ID == this.currentlyActive)
    },

    metaUpdates: {},

    updateStepMeta (_meta) {

      let step = this.getActiveStep()

      step.meta = {
        ...step.meta,
        ..._meta,
      }

      this.metaUpdates[step.ID] = {
        ...this.metaUpdates[step.ID],
        ..._meta,
      }

      return step
    },

    /**
     * Make the given step active.
     *
     * @param id string
     * @param e object
     */
    makeActive: function (id) {
      var self = this

      if (typeof e == 'undefined') {
        e = false
      }

      if (!id) {
        this.showAddStep()
        return
      }

      this.currentlyActive = id

      var $steps = self.getSteps()
      var $settings = self.getSettings()
      var $html = $('html')

      var $step = $('#' + id)

      // If the click step was already active...
      var was_active = $step.hasClass('active')

      // In some cases we do not want to allow deselecting a step...
      if (was_active) {
        return
      }

      $('#add-steps').addClass('hidden')
      $('.step-settings').removeClass('hidden')

      $settings.find('.step').addClass('hidden')
      $settings.find('.step').removeClass('active')
      $steps.find('.step').removeClass('active')
      $html.removeClass('active-step')

      // Make the clicked step active
      $step.addClass('active')

      var sid = '#settings-' + $step.attr('id')
      var $step_settings = $(sid)

      $step_settings.removeClass('hidden')
      $step_settings.addClass('active')
      $html.addClass('active-step')

      $(document).trigger('step-active')

    },
  })

  $(function () {
    funnel.init()
  })

} )(jQuery, Funnel)
