( function ($, funnel) {

  const {
    patch,
    routes,
    ajax,
  } = Groundhogg.api

  const {
    funnels  : FunnelsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores

  const {
    Div,
    Button,
    Modal,
    Textarea,
    ItemPicker,
    Input,
  } = MakeEl

  const {
    icons,
    uuid,
    moreMenu,
    tooltip,
    dialog,
    dangerConfirmationModal,
    confirmationModal,
    adminPageURL,
    loadingModal,
    modal,
  } = Groundhogg.element

  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n

  const funnelId = parseInt(Funnel.id)

  const removePathFromSvgsInStepFlow = () => {
    document.querySelectorAll('#step-flow svg path').forEach(el => {
      el.removeAttribute('class')
      console.log(el)
    })

  }

  const getFunnel = () => FunnelsStore.get(funnelId)

  $.extend(funnel, {

    sortables      : null,
    insertInBranch : 'main',
    insertAfterStep: false,
    editing        : false,

    getSteps: function () {
      return $('#step-sortable')
    },

    getSettings: function () {
      return $('.step-settings')
    },

    stepCallbacks: {},

    /**
     * Register letious step callbacks
     *
     * @param type
     */
    registerStepCallbacks (type, callbacks) {
      this.stepCallbacks[type] = callbacks
    },

    init: async function () {

      // removePathFromSvgsInStepFlow()

      let self = this

      let $document = $(document)
      let $form = $('#funnel-form')

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
          ({ data: { step_type } }) => [
            'apply_tag',
            'remove_tag',
            'tag_applied',
            'tag_removed',
          ].includes(step_type)).
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
        let $title = $(this)
        let id = $title.attr('data-id')
        let $step = $('#' + id)
        $step.find('.step-title').text($title.val())
      })

      $document.on('click', '#full-screen', () => {
        $(document.body).toggleClass('funnel-full-screen')

        ajax({
          action     : 'gh_funnel_editor_full_screen_preference',
          full_screen: $(document.body).hasClass('funnel-full-screen') ? 1 : 0,
        })

      })

      $document.on('click', '#collapse-settings', e => {
        this.startEditing(null)
        this.hideSettings()
      })

      $document.on('click', '#step-flow .step:not(.step-placeholder)', function (e) {

        if ($(e.target).is('.dashicons, button')) {
          return
        }

        self.startEditing(this.dataset.id, true)
        self.showSettings()
      })

      $('#step-search').on('input', e => {
        let search = e.target.value
        $(`.select-step`).addClass('visible')
        if (search) {
          $(`.select-step:not([data-name*="${ search }" i])`).removeClass('visible')
        }
      })

      $document.on('click', 'button#add-new-step', e => {
        this.showAddStep()
      })

      /* Bind Delete */
      $document.on('click', 'button.delete-step', e => {
        let stepId = e.currentTarget.parentNode.parentNode.dataset.id
        this.deleteStep(stepId)
      })

      /* Bind Duplicate */
      $document.on('click', 'button.duplicate-step', e => {
        let stepId = e.currentTarget.parentNode.parentNode.dataset.id
        this.duplicateStep(stepId)
      })

      /* Activate Spinner */
      $form.on('submit', function (e) {
        e.preventDefault()
        return false
      })

      /* Auto save */
      $document.on('change', '.auto-save', e => {
        e.preventDefault()
        this.saveQuietly()
      })

      /* Auto save */
      $document.on('auto-save', e => {
        e.preventDefault()
        this.saveQuietly()
      })

      $form.on('change', e => {
        if (e.target.matches('textarea[name=step_notes]')) {
          this.updateStepMeta({
            step_notes: e.target.value,
          })
          return
        }

        this.saveQuietly()
      })

      // Funnel Title
      $document.on('click', '.title-view .title', function (e) {
        $('.title-view').hide()
        $('.title-edit').show().removeClass('hidden')
        $('#title').focus()
      })

      $document.on('blur change', '#title', function (e) {

        let title = $(this).val()

        $('.title-view').find('.title').text(title)
        $('.title-view').show()
        $('.title-edit').hide()
      })

      $('#funnel-deactivate').on('click', e => {
        dangerConfirmationModal({
          alert      : `<p><b>Are you sure you want to deactivate the funnel?</b></p><p>Any pending events will be paused. They will be resumed immediately when the funnel is reactivated.</p>`,
          confirmText: __('Deactivate'),
          onConfirm  : () => {
            this.save({
              moreData: formData => formData.append('_deactivate', true),
            })
          },
        })
      })

      $('#funnel-update').on('click', e => {
        confirmationModal({
          alert    : `<p><b>Are you sure you want to publish your changes?</b></p>`,
          onConfirm: () => {
            this.save({
              moreData: formData => formData.append('_commit', true),
            })
          },
        })
      })

      $('#funnel-activate').on('click', e => {
        confirmationModal({
          alert    : `<p><b>Are you sure you want to activate you funnel?</b></p>`,
          onConfirm: () => {
            this.save({
              moreData: formData => formData.append('_activate', true),
            })
          },
        })
      })

      // Step Title
      $document.on('click', '.step-title-view .title', function (e) {

        let $step = $(this).closest('.step')

        $step.find('.step-title-view').hide()
        $step.find('.step-title-edit').show().removeClass('hidden')
        $step.find('.step-title-edit .edit-title').focus()
      })

      $document.on('blur change', '.edit-title', function (e) {

        let $step = $(this).closest('.step')

        let title = $(this).val()

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

        if (hash === 'add') {
          this.showAddStep()
        }
        else {
          this.startEditing(parseInt(window.location.hash.substring(1)))
        }

        this.showSettings()
      }

      if (window.location.hash) {
        dealWithHash()
      }

      window.addEventListener('hashchange', dealWithHash)

      let header = document.querySelector('.funnel-editor-header > .actions')

      header.append(Button({
        id       : 'funnel-more',
        className: 'gh-button secondary text icon',
        onClick  : e => {
          moreMenu('#funnel-more', [
            {
              key     : 'export',
              text    : 'Export',
              onSelect: e => {
                window.open(Funnel.export_url, '_blank')
              },
            },
            {
              key     : 'share',
              text    : 'Share',
              onSelect: e => {
                prompt('Copy this link to share', Funnel.export_url)
              },
            },
            {
              key     : 'reports',
              text    : 'Reports',
              onSelect: e => {
                window.open(adminPageURL('gh_reporting', {
                  tab   : 'funnels',
                  funnel: Funnel.id,
                }), '_blank')
              },
            },
            {
              key     : 'contacts',
              text    : 'Add Contacts',
              onSelect: e => {
                modal({
                  //language=HTML
                  content: `<h2>${ __('Add contacts to this funnel', 'groundhogg') }</h2>
                  <div id="gh-add-to-funnel" style="width: 500px"></div>`,
                  onOpen : () => {
                    document.getElementById('gh-add-to-funnel').append(Groundhogg.FunnelScheduler({
                      funnel    : getFunnel(),
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
              id          : 'pick-campaigns',
              noneSelected: 'Add a campaign...',
              selected    : campaigns.map(({
                ID,
                data,
              }) => ( {
                id  : ID,
                text: data.name,
              } )),
              tags        : true,
              fetchOptions: async (search) => {
                let campaigns = await CampaignsStore.fetchItems({
                  search,
                  limit: 20,
                })

                return campaigns.map(({
                  ID,
                  data,
                }) => ( {
                  id  : ID,
                  text: data.name,
                } ))
              },
              createOption: async value => {
                let campaign = await CampaignsStore.create({
                  data: {
                    name: value,
                  },
                })

                return {
                  id  : campaign.ID,
                  text: campaign.data.name,
                }
              },
              onChange    : items => campaignIds = items.map(item => item.id),
            }),
            `<p>Add a simple funnel description.</p>`,
            Textarea({
              id       : 'funnel-description',
              className: 'full-width',
              onInput  : e => {
                description = e.target.value
              },
              value    : description,
            }),
            Div({
              className: 'display-flex flex-end',
            }, Button({
              id       : 'save-settings',
              className: 'gh-button primary',
              onClick  : async e => {

                close()

                await FunnelsStore.patch(funnelId, {
                  campaigns: campaignIds,
                  meta     : {
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

      if (!this.steps.length) {
        this.showAddStep()
      }
    },

    async save (args = {}) {

      let quiet

      if (args === true) {
        quiet = true
      }
      else {
        quiet = args.quiet ?? false
      }

      if (quiet && this.saving) {
        return
      }

      this.saving = true

      let formData = new FormData(document.getElementById('funnel-form'))

      // these are in the form but are not actually used when posted
      formData.delete('step_notes')
      formData.delete('note_text')

      formData.append('action', 'gh_save_funnel_via_ajax')

      if (!quiet) {
        $('body').addClass('saving')
      }

      // Update the JS meta changes first
      if (Object.keys(this.metaUpdates).length) {
        formData.append('metaUpdates', JSON.stringify(this.metaUpdates))
        this.metaUpdates = [] // clear the meta updates
      }

      // add additional data to the formData if required
      if (args.moreData) {
        args.moreData(formData)
      }

      return await ajax(formData, {
        url: `${ ajaxurl }?${ quiet ? 'auto-save' : 'explicit-save' }=1`,
      }).then(response => {

        // make sure the status is available to the parent funnel form element
        document.getElementById('funnel-form').dataset.status = response.data.funnel.data.status

        this.steps = response.data.funnel.steps

        if (!this.dragging) {
          morphdom(document.getElementById('step-sortable'), Div({}, response.data.sortable), {
            childrenOnly     : true,
            onBeforeElUpdated: function (fromEl, toEl) {

              // preserve the editing class
              if (fromEl.classList.contains('editing')) {
                toEl.classList.add('editing')
              }

              if (fromEl.matches('.editing .step-edit.panels')) {
                return false // don't morph the currently edited step to avoid glitchiness
              }
              return true
            },
          })

          this.makeSortable()
        }

        morphdom(document.querySelector('.step-settings'), Div({}, response.data.settings), {
          childrenOnly     : true,
          onBeforeElUpdated: function (fromEl, toEl) {

            // preserve the editing class
            if (fromEl.classList.contains('editing')) {
              toEl.classList.add('editing')
            }

            if (fromEl.matches('.editing .step-edit.panels')) {
              return false // don't morph the currently edited step to avoid glitchiness
            }

            return true
          },
        })

        // self.makeSortable()
        drawLogicLines()

        this.saving = false

        // quietly!
        if (quiet) {
          return response
        }

        $(document).trigger('new-step')

        $('body').removeClass('saving')

        if (response.data.err) {
          dialog({
            message: response.data.err,
            type   : 'error',
          })
          return response
        }

        dialog({
          message: __('Funnel saved!', 'groundhogg'),
        })
      }).catch(err => {
        dialog({
          message: __('Something went wrong updating the funnel. Your changes could not be saved.', 'groundhogg'),
          type   : 'error',
        })
        throw err
      })
    },

    saveQuietly: Groundhogg.functions.debounce(() => funnel.save(true), 500),

    makeSortable () {
      this.sortables = $('.step-branch').sortable({
        placeholder: 'sortable-placeholder',
        connectWith: '.step-branch',
        // handle: '.step',
        // tolerance: 'pointer',
        distance: 100,
        cursorAt: {
          left: 5,
          top : 5,
        },
        helper  : (e, $el) => {

          let icon = $el.find('.hndle-icon')[0]

          // language=HTML
          return `
              <div class="wpgh-element" data-group="action">
                  <div class="step-icon">
                      ${ icon.outerHTML }
                  </div>
              </div>`
        },
        change  : drawLogicLines,
        stop    : () => {
          drawLogicLines()

          // update the branch hidden fields to be correct with their parent
          $('input[name*="[branch]"][type="hidden"]').each(function (el) {
            $(this).val($(this).closest('.step-branch').data('branch'))
          })

          this.dragging = false
          this.saveQuietly()
        },
        start   : (e, ui) => {
          ui.helper.width(60)
          ui.helper.height(60)
          drawLogicLines()
          this.dragging = true
        },
        receive : (e, ui) => {

          drawLogicLines()

          // receiving from another sortable?
          if (typeof ui.helper === 'undefined' || !ui.helper) {
            return
          }

          let branch = ui.helper.closest('.step-branch').data('branch')

          let data = {
            step_type: ui.item.prop('id'),
            branch   : branch,
          }

          // language=HTML
          ui.helper.replaceWith(`
              <div class="step step-placeholder ${ data.step_group }">
                  Loading...
                  ${ Input({
                      type : 'hidden',
                      name : 'step_ids[]',
                      value: JSON.stringify(data),
                  }).outerHTML }
              </div>`)

          this.save({
            quiet: true,
          })
        },
      })

      this.sortables.disableSelection()

      $('.wpgh-element.ui-draggable').draggable({
        connectToSortable: '.ui-sortable',
        helper           : 'clone',
      })
    },

    hideSettings () {
      $('#step-settings-container').addClass('slide-out')
    },

    showSettings () {
      $('#step-settings-container').removeClass('slide-out')
    },

    showAddStep () {
      this.showSettings()
      this.startEditing(null)

      history.pushState(null, null, '#add')
    },

    /**
     * Given an element delete it
     *
     * @param id int
     */
    deleteStep: function (id) {

      let $step = $('#step-' + id)
      let result = confirm(
        'Are you sure you want to delete this step? Any pending events for this step will be removed.')

      if (result) {

        if (this.isEditing(id)) {
          this.startEditing(null)
        }

        $step.fadeOut(400, () => {
          $step.remove()
          drawLogicLines()
          this.save({
            quiet   : true,
            moreData: formData => {
              formData.append('_delete_step', id)
            },
          })
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

      document.getElementById(`step-${ id }`).querySelector(`input[name='step_ids[]'][type='hidden']`).insertAdjacentElement('afterend', Input({
        type : 'hidden',
        name : 'step_ids[]',
        value: 'duplicate',
      }))

      return await this.save({
        quiet   : true,
        moreData: formData => {
          formData.append('_duplicate_step', JSON.stringify({
            id,
            ...extra,
          }))
        },
      })

    },

    /**
     * The step that is currently being edited.
     *
     * @returns {unknown}
     */
    getActiveStep () {
      return this.steps.find(s => s.ID == this.editing)
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

      this.saveQuietly()

      return step
    },

    isEditing (id) {
      return this.editing == id
    },

    /**
     * Make the given step active.
     *
     * @param id string
     * @param hps bool what to do with the browser history
     */
    startEditing (id, hps = false) {

      // trying to make the current step active
      if (this.editing === id) {
        return
      }

      // this step is not in the funnel
      if (id && !this.steps.find(s => s.ID == id)) {
        return
      }

      // deactivate the current step
      if (this.editing) {
        document.getElementById(`step-${ this.editing }`).classList.remove('editing')
        document.getElementById(`settings-${ this.editing }`).classList.remove('editing')
      }

      this.editing = id

      // we are indeed making a different step active
      if (this.editing) {

        document.getElementById(`step-${ this.editing }`).classList.add('editing')
        document.getElementById(`settings-${ this.editing }`).classList.add('editing')

        const step = this.getActiveStep()

        if (!step) {
          return
        }

        const type = step.data.step_type

        if (this.stepCallbacks.hasOwnProperty(type) && this.stepCallbacks[type].hasOwnProperty('onActive')) {
          this.stepCallbacks[type].onActive({
            ...step,
            updateStep: meta => this.updateStepMeta(meta, step.ID),
          })
        }

        $(document).trigger('step-active')

        if (hps) {
          history.pushState(null, null, `#${ step.ID }`)
        }
        else {
          history.replaceState(null, null, `#${ step.ID }`)
        }
      }
    },
  })

  $(function () {
    drawLogicLines()
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

  function areNumbersClose (num1, num2, tolerancePercent) {
    const average = ( Math.abs(num1) + Math.abs(num2) ) / 2
    const tolerance = ( tolerancePercent / 100 ) * average
    return Math.abs(num1 - num2) <= tolerance
  }

  function drawLogicLines () {

    const borderRadius = '20px'
    const borderPixels = 2
    const borderWidth = `${ borderPixels }px`

    // loops
    document.querySelectorAll('.step-branch .step.loop').forEach(el => {

      // the step-branch.benchmarks container
      let stepPos = el.getBoundingClientRect()
      let targetStepId = Funnel.steps.find(s => s.ID == el.dataset.id).meta.next

      if (!targetStepId) {
        return
      }

      let targetStep = document.getElementById(`step-${ targetStepId }`).getBoundingClientRect()

      // let stepCenter = stepPos.top + stepPos.height / 2
      // let targetCenter = targetStep.top + targetStep.height / 2

      let lineHeight = stepPos.bottom - targetStep.bottom

      let line = el.querySelector('div.logic-line')

      if (!line) {
        line = Div({ className: 'logic-line' })
        el.append(line)
      }

      let width = Math.max(stepPos.right, targetStep.right) - Math.min(stepPos.right, targetStep.right) + 15

      line.style.bottom = '50%'
      line.style.width = `${ width }px`
      line.style.right = `-${ width }px`
      line.style.height = `${ lineHeight }px`
      line.style.borderWidth = `${ borderWidth } ${ borderWidth } ${ borderWidth } 0`
      line.style.borderBottomRightRadius = borderRadius
      line.style.borderTopRightRadius = borderRadius

    })

    // Benchmarks
    document.querySelectorAll('.logic-line.benchmark').forEach(el => {

      // the step-branch.benchmarks container
      let containerPos = el.closest('.step-branch').getBoundingClientRect()

      // the benchmark itself
      let branchPos = el.closest('.step.benchmark').getBoundingClientRect()

      let stepCenter = containerPos.left + containerPos.width / 2
      let branchCenter = branchPos.left + branchPos.width / 2

      let lineHeight = containerPos.bottom - branchPos.bottom

      // center
      if (areNumbersClose(stepCenter, branchCenter, 1)) {
        el.style.left = '50%'
        el.style.bottom = `-${ lineHeight }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `0 0 0 ${ borderWidth }`
      }
      // left side
      else if (branchCenter < stepCenter) {
        el.style.left = '50%'
        el.style.width = `${ stepCenter - branchCenter }px`
        el.style.bottom = `-${ lineHeight + borderPixels }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
        el.style.borderBottomLeftRadius = borderRadius
      }
      // right side
      else {
        el.style.right = '50%'
        el.style.width = `${ branchCenter - stepCenter }px`
        el.style.bottom = `-${ lineHeight + borderPixels }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
        el.style.borderBottomRightRadius = borderRadius
      }

    })

    // Above
    document.querySelectorAll('.logic-line.line-above').forEach(el => {
      let branchPos = el.parentElement.getBoundingClientRect()
      let stepPos = el.closest('.step-branches').previousElementSibling.getBoundingClientRect()

      let stepCenter = stepPos.left + stepPos.width / 2
      let branchCenter = branchPos.left + branchPos.width / 2

      let stepHeightCenter = stepPos.top + stepPos.height / 2
      let lineHeight = branchPos.top - stepHeightCenter

      // center
      if (areNumbersClose(stepCenter, branchCenter, 1)) {

        el.classList.remove('left', 'right')
        el.classList.add('middle')

        lineHeight = branchPos.top - stepPos.bottom

        el.style.left = '50%'
        el.style.top = `-${ lineHeight }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `0 0 0 ${ borderWidth }`
      }
      // left side
      else if (branchCenter < stepCenter) {

        el.classList.remove('middle', 'right')
        el.classList.add('left')

        el.style.left = '50%'
        el.style.width = `${ stepPos.left - branchCenter }px`
        el.style.top = `-${ lineHeight }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
        el.style.borderTopLeftRadius = borderRadius
      }
      // right side
      else {

        el.classList.remove('middle', 'left')
        el.classList.add('right')

        el.style.right = '50%'
        el.style.width = `${ branchCenter - stepPos.right }px`
        el.style.top = `-${ lineHeight }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
        el.style.borderTopRightRadius = borderRadius
      }

    })

    // Below
    document.querySelectorAll('.logic-line.line-below').forEach(el => {

      let branchPos = el.parentElement.getBoundingClientRect()
      let containerPos = el.closest('.sortable-item').getBoundingClientRect()

      let stepCenter = containerPos.left + containerPos.width / 2
      let branchCenter = branchPos.left + branchPos.width / 2

      let lineHeight = containerPos.bottom - branchPos.bottom

      // center
      if (areNumbersClose(stepCenter, branchCenter, 1)) {
        el.style.left = '50%'
        el.style.bottom = `-${ lineHeight }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `0 0 0 ${ borderWidth }`
      }
      // left side
      else if (branchCenter < stepCenter) {
        el.style.left = '50%'
        el.style.width = `${ stepCenter - branchCenter }px`
        el.style.bottom = `-${ lineHeight }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
        el.style.borderBottomLeftRadius = borderRadius
      }
      // right side
      else {
        el.style.right = '50%'
        el.style.width = `${ branchCenter - stepCenter }px`
        el.style.bottom = `-${ lineHeight }px`
        el.style.height = `${ lineHeight }px`
        el.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
        el.style.borderBottomRightRadius = borderRadius
      }

    })

    $(document).trigger('draw-logic-lines')

  }

} )(jQuery, Funnel)
