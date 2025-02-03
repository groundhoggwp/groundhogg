( function ($, funnel) {

  const { patch, routes, ajax } = Groundhogg.api

  const { funnels: FunnelsStore, campaigns: CampaignsStore } = Groundhogg.stores

  const {
    Div,
    Button,
    Modal,
    Textarea,
    ItemPicker,
  } = MakeEl

  const {
    icons, uuid,
    moreMenu,
    tooltip,
    dialog,
    dangerConfirmationModal,
    adminPageURL,
    loadingModal,
    modal,
  } = Groundhogg.element

  const { sprintf, __, _x, _n } = wp.i18n

  const funnelId = parseInt(Funnel.id)

  const removePathFromSvgsInStepFlow = () => {
    document.querySelectorAll( '#step-flow svg path' ).forEach( el => {
      el.removeAttribute( 'class' )
      console.log(el)
    })

  }

  const getFunnel = () => FunnelsStore.get(funnelId)

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

    stepCallbacks: {},

    /**
     * Register various step callbacks
     *
     * @param type
     */
    registerStepCallbacks (type, callbacks) {
      this.stepCallbacks[type] = callbacks
    },

    init: async function () {

      // removePathFromSvgsInStepFlow()

      var self = this

      var $document = $(document)
      var $form = $('#funnel-form')
      var $steps = self.getSteps()
      var $settings = self.getSettings()

      let preloaders = [
        FunnelsStore.maybeFetchItem(funnelId),
      ]

      // Preload emails
      let emails = this.steps.filter(step => step.data.step_type === 'send_email').
        map(step => parseInt(step.meta.email_id))

      if (emails.length) {
        preloaders.push(Groundhogg.stores.emails.maybeFetchItems(emails))
      }

      // Preload tags
      let tags = this.steps.filter(
          ({ data: { step_type } }) => ['apply_tag', 'remove_tag', 'tag_applied', 'tag_removed'].includes(step_type)).
        reduce((allTags, { meta: { tags } }) => {

          if (!Array.isArray(tags)) {
            return allTags
          }

          tags.forEach(id => {
            if (!allTags.includes(id)) {
              allTags.push(id)
            }
          })

          return allTags
        }, [])

      if (tags.length) {
        preloaders.push(Groundhogg.stores.tags.maybeFetchItems(tags))
      }

      if (tags.length || emails.length) {
        const { close } = loadingModal()
        await Promise.all(preloaders)
        close()
      }

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
          full_screen: $(document.body).hasClass('funnel-full-screen') ? 1 : 0,
        })

      })

      $document.on('click', '#step-flow .step:not(.step-placeholder)', function (e) {

        if ($(e.target).is('.dashicons, button')) {
          return
        }

        self.makeActive(this.id, true)
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

        let stepId = this.parentNode.parentNode.id

        self.duplicateStep(stepId)
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

      $('#status-toggle').on('change', e => {

        if (!e.target.checked && Funnel.is_active) {
          dangerConfirmationModal({
            alert: `<p><b>Are you sure you want to deactivate the funnel?</b></p><p>Any pending events will be paused. They will be resumed immediately when the funnel is reactivated.</p>`,
            confirmText: __('Deactivate'),
            onConfirm: () => {
              setTimeout(() => this.save(), 100)
            },
            onCancel: () => {
              $('#status-toggle').prop('checked', true)
            },
          })
        }

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

      const dealWithHash = () => {
        let hash = window.location.hash.substring(1)

        if ( hash === 'add' ){
          this.showAddStep()
        } else {
          this.makeActive(parseInt(window.location.hash.substring(1)))
        }
      }

      if (window.location.hash) {
        dealWithHash()
      }

      window.addEventListener('hashchange', dealWithHash )

      let header = document.querySelector('.funnel-editor-header > .actions')

      header.append(Button({
        id: 'funnel-more',
        className: 'gh-button secondary text icon',
        onClick: e => {
          moreMenu('#funnel-more', [
            {
              key: 'export', text: 'Export', onSelect: e => {
                window.open(Funnel.export_url, '_blank')
              },
            },
            {
              key: 'share', text: 'Share', onSelect: e => {
                prompt('Copy this link to share', Funnel.export_url)
              },
            },
            {
              key: 'reports', text: 'Reports', onSelect: e => {
                window.open(adminPageURL('gh_reporting', {
                  tab: 'funnels',
                  funnel: Funnel.id,
                }), '_blank')
              },
            },
            {
              key: 'contacts', text: 'Add Contacts', onSelect: e => {
                modal({
                  //language=HTML
                  content: `<h2>${ __('Add contacts to this funnel', 'groundhogg') }</h2>
                  <div id="gh-add-to-funnel" style="width: 500px"></div>`,
                  onOpen: () => {
                    document.getElementById('gh-add-to-funnel').append(Groundhogg.FunnelScheduler({
                      funnel: getFunnel(),
                      funnelStep: getFunnel().steps[0],
                    }))
                  },
                })
              },
            },
          ])
        },
      }, icons.verticalDots))

      tooltip('#full-screen', {
        content: 'Toggle full Screen',
      })

      tooltip('#replacements', {
        content: 'Replacement codes',
      })

      tooltip('#funnel-settings', {
        content: 'Funnel settings',
      })

      document.getElementById('funnel-settings').addEventListener('click', e => {

        Modal({}, ({ close }) => {

          let funnel = FunnelsStore.get(funnelId)

          let { description = '' } = funnel.meta
          let { campaigns = [] } = funnel

          let campaignIds = campaigns.map(c => c.ID)

          return Div({}, [
            `<h2>Funnel Settings</h2>`,
            `<p>Use <b>campaigns</b> to organize your funnels. Use terms like <code>Black Friday</code> or <code>Sales</code>.</p>`,
            ItemPicker({
              id: 'pick-campaigns',
              noneSelected: 'Add a campaign...',
              selected: campaigns.map(({ ID, data }) => ( { id: ID, text: data.name } )),
              tags: true,
              fetchOptions: async (search) => {
                let campaigns = await CampaignsStore.fetchItems({
                  search,
                  limit: 20,
                })

                return campaigns.map(({ ID, data }) => ( { id: ID, text: data.name } ))
              },
              createOption: async value => {
                let campaign = await CampaignsStore.create({
                  data: {
                    name: value,
                  },
                })

                return { id: campaign.ID, text: campaign.data.name }
              },
              onChange: items => campaignIds = items.map(item => item.id),
            }),
            `<p>Add a simple funnel description.</p>`,
            Textarea({
              id: 'funnel-description',
              className: 'full-width',
              onInput: e => {
                description = e.target.value
              },
              value: description,
            }),
            Div({
              className: 'display-flex flex-end',
            }, Button({
              id: 'save-settings',
              className: 'gh-button primary',
              onClick: async e => {

                close()

                await FunnelsStore.patch(funnelId, {
                  campaigns: campaignIds,
                  meta: {
                    description,
                  },
                })

                dialog({
                  message: 'Changes saved!',
                })
              },
            }, 'Save')),
          ])
        })

      })

    },

    async save ($form) {

      if (typeof $form === 'undefined') {
        $form = $('#funnel-form')
      }

      var self = this

      var $saveButton = $('.save-button')

      $('body').addClass('saving')

      $saveButton.html(`<span class="gh-spinner"></span>`)

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

        try {
          let response = await patch(routes.v4.steps, changes)
        }
        catch (e) {
          dialog({
            message: __('Something went wrong updating the funnel. Your changes could not be saved.', 'groundhogg'),
            type: 'error',
          })
          throw e
        }
        // reset
        this.metaUpdates = {}
      }

      // Do regular form update
      adminAjaxRequest(fd, (response) => {

        this.steps = response.data.funnel.steps

        // match the status of the funnel with the one from the response
        $('#status-toggle').prop('checked', response.data.funnel.status === 'active' )

        $saveButton.removeClass('spin')
        $saveButton.html(self.save_text)

        self.getSettings().html(response.data.settings)
        self.getSteps().html(response.data.sortable)

        $(document).trigger('new-step')

        $('body').removeClass('saving')
        self.makeActive(self.currentlyActive)

        if ( response.data.err ){
          dialog({
            message: response.data.err,
            type: 'error',
          })
          return
        }

        dialog({
          message: __('Funnel saved!', 'groundhogg'),
        })
      }, err => {
        dialog({
          message: __('Something went wrong updating the funnel. Your changes could not be saved.', 'groundhogg'),
          type: 'error',
        })
        throw err
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

          showSpinner()

          this.getStepHtml(data).then(r => {
            hideSpinner()
            $(`#${ id }`).remove()
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

      history.pushState(null, null, '#add')
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

        $step.fadeOut(400, () => {
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
    async duplicateStep (id) {

      const step = this.steps.find(s => s.ID == id)

      if (!step) {
        return
      }

      this.insertAfterStep = id

      const type = step.data.step_type

      let extra = {}

      if (this.stepCallbacks.hasOwnProperty(type) && this.stepCallbacks[type].hasOwnProperty('onDuplicate')) {
        try {
          extra = await new Promise((res, rej) => this.stepCallbacks[type].onDuplicate(step, res, rej))
        }
        catch (e) {
          throw e
        }
      }

      var data = {
        action: 'wpgh_duplicate_funnel_step',
        step_id: id,
        version: 2,
        ...extra,
      }

      const { close } = loadingModal()

      await this.getStepHtml(data)

      close()
    },

    /**
     * Performs an ajax call and replaces
     *
     * @param obj
     */
    getStepHtml: async function (obj) {
      let self = this
      let $steps = self.getSteps()
      let $settings = self.getSettings()

      // Make sure the nonce is there
      obj._wpnonce = Groundhogg.nonces._wpnonce

      let response = await ajax(obj)

      this.steps.push(response.data.json)

      if (self.insertAfterStep) {
        $(`#${ self.insertAfterStep }`).after(response.data.sortable)
      }
      else {
        $steps.append(response.data.sortable)
      }

      $settings.append(response.data.settings)

      $(document).trigger('new-step')

      return response
    },

    /**
     * The step that is currently being edited.
     *
     * @returns {unknown}
     */
    getActiveStep () {
      return this.steps.find(s => s.ID == this.currentlyActive)
    },

    metaUpdates: {},

    updateStepMeta (_meta, stepId = false) {

      let step

      if (stepId) {
        step = this.steps.find(s => s.ID == stepId)
      }
      else {
        step = this.getActiveStep()
      }

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
    makeActive (id, hps = false) {
      var self = this

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

      const step = this.getActiveStep()

      if (!step) {
        return
      }

      const type = step.data.step_type

      if (this.stepCallbacks.hasOwnProperty(type) && this.stepCallbacks[type].hasOwnProperty('onActive')) {
        this.stepCallbacks[type].onActive({ ...step, updateStep: meta => this.updateStepMeta(meta, step.ID) })
      }

      $(document).trigger('step-active')

      if (hps) {
        history.pushState(null, null, `#${ step.ID }`)
      }
      else {
        history.replaceState(null, null, `#${ step.ID }`)
      }

    },
  })

  $(function () {
    funnel.init()
  })

  window.addEventListener('beforeunload', e => {

    if (Object.keys(Funnel.metaUpdates).length) {
      e.preventDefault()
      let msg = __('You have unsaved changes, are you sure you want to leave?', 'groundhogg')
      e.returnValue = msg
      return msg
    }

    return null
  })

} )(jQuery, Funnel)
