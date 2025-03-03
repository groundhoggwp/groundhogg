( function ($) {

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
    Pg,
    H3,
    Img,
    An,
    Button,
    Dashicon,
    Modal,
    ModalFrame,
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

  const getFunnel = () => FunnelsStore.get(Funnel.id)

  if (typeof Funnel !== 'undefined') {

    $.extend(Funnel, {

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

        let self = this

        let $document = $(document)
        let $form = $('#funnel-form')

        let preloaders = [
          FunnelsStore.maybeFetchItem(this.id),
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

        $document.on('click', '#full-screen', () => {
          $(document.body).toggleClass('gh-full-screen')

          ajax({
            action     : 'gh_funnel_editor_full_screen_preference',
            full_screen: $(document.body).hasClass('gh-full-screen') ? 1 : 0,
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

        $document.on('click', '.step-branch', e => {
          if (!Groundhogg.element.clickedIn(e, '.step')) {
            this.startEditing(null)
            this.hideSettings()
          }
        })

        $('#step-search').on('input', e => {
          let search = e.target.value
          $(`.select-step`).addClass('visible')
          if (search) {
            $(`.select-step:not([data-name*="${ search }" i])`).removeClass('visible')
          }
        })

        $document.on('mousedown', '.step-element.premium', e => {

          const {
            name = '',
            type = '',
            group = '',
          } = e.currentTarget.dataset

          console.log('prem step')
          ModalFrame({},
            ({close}) => Div({
              style: {
                position: 'relative',
              }
            }, [
              Button({
                className: 'gh-button secondary text icon',
                onClick: close,
                style: {
                  position: 'absolute',
                  top: '5px',
                  right: '5px',
                }
              }, Dashicon('no-alt')),
              An({
                href: 'https://groundhogg.io/pricing/',
                target: '_blank',
              }, Img({
                style    : {
                  borderRadius: '10px',
                },
                className: 'has-box-shadow',
                src      : `${ Groundhogg.assets.images }upgrade-needed.png`,
              })),
            ]),
          )
        })

        $document.on('click', 'button#add-new-step', e => {
          this.showAddStep()
        })

        /* Bind Delete */
        $document.on('click', 'button.delete-step', e => {
          let stepId = e.currentTarget.parentNode.parentNode.dataset.id
          this.deleteStep(stepId)
        })

        $document.on('click', 'button.lock-step', e => {
          let stepId = e.currentTarget.parentNode.parentNode.dataset.id
          this.lockStep(stepId)
        })

        $document.on('click', 'button.unlock-step', e => {
          let stepId = e.currentTarget.parentNode.parentNode.dataset.id
          this.unlockStep(stepId)
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
            alert      : `<p><b>Are you sure you want to deactivate the funnel?</b></p>
<p>Any pending events will be paused. They will be resumed immediately when the funnel is reactivated.</p>
<p>Unsaved changes will be discarded. To preserve any changes, update the funnel first, then deactivate.</p>`,
            confirmText: __('Deactivate'),
            onConfirm  : () => {
              this.save({
                moreData: formData => formData.append('_deactivate', true),
              })
            },
          })
        })

        $('#funnel-update').on('click', e => {

          const update = () => this.save({
            moreData: formData => formData.append('_commit', true),
          })

          // errors
          if (document.getElementById('step-flow').querySelector('.has-errors')) {

            dangerConfirmationModal({
              // language=HTML
              alert      : `<p><b>Some of your steps have issues!</b></p>
              <p>Review steps with the ⚠️ icon before updating.</p>
              <p>Are you sure you want to commit your changes?</p>`,
              onConfirm  : update,
              confirmText: 'Update anyway',
            })

            return
          }

          update()
        })

        $('#funnel-activate').on('click', e => {

          const activate = () => this.save({
            moreData: formData => formData.append('_activate', true),
          })

          // errors
          if (document.getElementById('step-flow').querySelector('.has-errors')) {

            dangerConfirmationModal({
              // language=HTML
              alert      : `<p><b>Some of your steps have issues!</b></p>
              <p>Review steps with the ⚠️ icon before activating.</p>
              <p>Are you sure you want to activate with issues present?</p>`,
              onConfirm  : activate,
              confirmText: 'Activate anyway',
            })

            return
          }

          activate()
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

            let funnel = FunnelsStore.get(this.id)

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

                  await FunnelsStore.patch(this.id, {
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
          this.metaUpdates = {} // clear the meta updates
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

              if (quiet && fromEl.matches('.editing .step-edit.panels')) {
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
            $(document).trigger('auto-save')
            $(document).trigger('gh-init-pickers')
            return response
          }

          this.stepSettingsCallbacks()
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

      saveQuietly: Groundhogg.functions.debounce(() => Funnel.save(true), 500),

      makeSortable () {
        this.sortables = $('.step-branch').sortable({
          placeholder: 'sortable-placeholder',
          connectWith: '.step-branch',
          // handle: '.step',
          // tolerance: 'pointer',
          cancel  : '.locked',
          distance: 100,
          cursorAt: {
            left: 5,
            top : 5,
          },
          helper  : (e, $el) => {

            let icon = $el.find('.hndle-icon')[0]

            // language=HTML
            return `
                <div class="sortable-helper-icon ${ $el.data('group') }">
                    <div class="step-icon">
                        ${ icon.outerHTML }
                    </div>
                </div>`
          },
          change  : () => drawLogicLines(),
          // sort    : () => drawLogicLines(),
          stop    : () => {

            // update the branch hidden fields to be correct with their parent
            $('input[name*="[branch]"][type="hidden"]').each(function (el) {
              $(this).val($(this).closest('.step-branch').data('branch'))
            })

            this.dragging = false
            this.saveQuietly()
            drawLogicLines()

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
            if (ui.helper === null) {
              return
            }

            let branch = ui.helper.closest('.step-branch').data('branch')
            let type = ui.helper.data('type')
            let group = ui.helper.data('group')

            if (!type) {
              ui.helper.remove() // discard right away
              return
            }

            let data = {
              step_type: type,
              branch   : branch,
            }

            // language=HTML
            ui.helper.replaceWith(`
                <div class="step step-placeholder ${ group }">
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

        $('.step-element.step-draggable').draggable({
          connectToSortable: '.step-branch',
          cancel           : '.premium',
          helper           : (e) => {

            let $el = $(e.currentTarget)
            let icon = $el.find('.step-icon')[0]

            // language=HTML
            return `
                <div class="sortable-helper-icon ${ $el.data('group') }" data-group="${ $el.data('group') }" data-type="${ $el.attr('id') }">
                    ${ icon.outerHTML }
                </div>`
          },
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

        let $step = $(`#step-${ id }`)

        if (!$step.is('.sortable-item')) {
          $step = $step.closest('.sortable-item')
        }

        const deleteStep = () => {
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

        if ($step.is('.logic')) {
          dangerConfirmationModal({
            alert    : '<p>Are you sure you want to delete this step? Any steps in branches will also be deleted.</p>',
            onConfirm: () => deleteStep(),
          })
          return
        }

        deleteStep()
      },

      /**
       * Given an element delete it
       *
       * @param id int
       */
      lockStep: function (id) {
        this.save({
          quiet   : true,
          moreData: formData => {
            formData.append('_lock_step', id)
          },
        })
      },

      /**
       * Given an element delete it
       *
       * @param id int
       */
      unlockStep: function (id) {
        this.save({
          quiet   : true,
          moreData: formData => {
            formData.append('_unlock_step', id)
          },
        })
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

          this.stepSettingsCallbacks()

          const step = this.getActiveStep()

          if (!step) {
            return
          }

          if (hps) {
            history.pushState(null, null, `#${ step.ID }`)
          }
          else {
            history.replaceState(null, null, `#${ step.ID }`)
          }
        }
      },

      stepSettingsCallbacks () {
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
      },
    })

    $(function () {
      drawLogicLines()
      Funnel.init()
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
  }

  function areNumbersClose (num1, num2, tolerancePercent) {
    const average = ( Math.abs(num1) + Math.abs(num2) ) / 2
    const tolerance = ( tolerancePercent / 100 ) * average
    return Math.abs(num1 - num2) <= tolerance
  }

  function findWidestElementBetween (startElement, endElement) {
    let el = startElement
    let currEl = startElement.nextElementSibling

    while (currEl) {

      if (el.getBoundingClientRect().width < currEl.getBoundingClientRect().width) {
        el = currEl
      }

      if (currEl.isSameNode(endElement)) {
        break
      }

      currEl = currEl.nextElementSibling
    }

    return el
  }

  function drawLogicLines () {

    // const borderRadius = '50px'
    const borderRadius = '100%'
    const borderPixels = 2
    const borderWidth = `${ borderPixels }px`

    const clearLineStyle = line => line.removeAttribute("style");

    // loops
    try {
      document.querySelectorAll('.step-branch .step.loop, .step-branch .step.logic_loop').forEach(el => {

        // the step-branch.benchmarks container
        let stepPos = el.getBoundingClientRect()
        let stepId = el.dataset.id
        let targetStepId = Funnel.steps.find(s => s.ID == stepId).meta.next

        if (!targetStepId || typeof targetStepId == 'undefined' || targetStepId == 0) {
          return
        }

        let targetStep = document.getElementById(`step-${ targetStepId }`)
        let widestEl = findWidestElementBetween(targetStep, el)
        let targetPos = targetStep.getBoundingClientRect()

        let lineHeight = stepPos.bottom - targetPos.bottom
        let minWidth = Math.min( stepPos.width, targetPos.width )

        let branch = el.closest('.step-branch')
        let branchPos = branch.getBoundingClientRect()

        let line = branch.querySelector(`div.logic-line.loop-${ stepId }-to-${ targetStepId }`)

        if (!line) {
          line = Div({ className: `logic-line loop-line loop-${ stepId }-to-${ targetStepId }` }, [
            Div({ className: 'line-arrow top' }),
            Div({ className: 'line-arrow left' }),
            Div({ className: 'line-arrow bottom' }),
          ])
          branch.append(line)
        }

        clearLineStyle(line)

        let width = (( widestEl ? widestEl.getBoundingClientRect().width : branchPos.width ))/2

        line.style.bottom = `${ branchPos.bottom - stepPos.bottom + ( stepPos.height / 2 ) }px`
        line.style.width = `${ width }px`
        line.style.right = `calc(50% + ${minWidth/2}px)`
        line.style.height = `${ lineHeight }px`
        line.style.borderWidth = `${ borderWidth } 0 ${ borderWidth } ${ borderWidth }`
        line.style.borderBottomLeftRadius = borderRadius
        line.style.borderTopLeftRadius = borderRadius

      })
    } catch (e) {}

    // skips
    try {
      document.querySelectorAll('.step-branch .step.skip, .step-branch .step.logic_skip').forEach(step => {

        // the step-branch.benchmarks container
        let stepPos = step.getBoundingClientRect()
        let stepId = step.dataset.id
        let targetStepId = Funnel.steps.find(s => s.ID == stepId).meta.next

        if (!targetStepId || typeof targetStepId == 'undefined' || targetStepId == 0) {
          return
        }

        let targetStep = document.getElementById(`step-${ targetStepId }`)
        let widestEl = findWidestElementBetween(step, targetStep)
        let targetPos = targetStep.getBoundingClientRect()

        let lineHeight = Math.abs( stepPos.bottom - targetPos.bottom )
        let minWidth = Math.min( stepPos.width, targetPos.width )

        let branch = step.closest('.step-branch')
        let branchPos = branch.getBoundingClientRect()

        let line = branch.querySelector(`div.logic-line.skip-${ stepId }-to-${ targetStepId }`)

        if (!line) {
          line = Div({ className: `logic-line skip-line skip-${ stepId }-to-${ targetStepId }` }, [
            Div({ className: 'line-arrow top' }),
            Div({ className: 'line-arrow right' }),
            Div({ className: 'line-arrow bottom' }),
          ])
          branch.append(line)
        }

        clearLineStyle(line)

        let width = (( widestEl ? widestEl.getBoundingClientRect().width : branchPos.width ))/2

        line.style.top = `${ stepPos.top - branchPos.top + ( stepPos.height / 2 ) }px`
        line.style.width = `${ width }px`
        line.style.left = `calc(50% + ${minWidth/2}px)`
        line.style.height = `${ lineHeight }px`
        line.style.borderWidth = `${ borderWidth } ${ borderWidth } ${ borderWidth } 0`
        line.style.borderTopRightRadius = borderRadius
        line.style.borderBottomRightRadius = borderRadius

      })
    } catch (e) { console.log( e ) }

    // Benchmarks
    try {

      document.querySelectorAll('.logic-line.benchmark-line').forEach( el => el.remove() )
      document.querySelectorAll('.step-branch .step.benchmark').forEach(el => {

        // the step-branch.benchmarks container
        let rowPos = el.parentElement.getBoundingClientRect()

        // the benchmark itself
        let step = el

        let stepPos = step.getBoundingClientRect()

        let stepCenter = stepPos.left + stepPos.width / 2
        let rowCenter = rowPos.left + rowPos.width / 2

        let line1 = Div({ className: `logic-line benchmark-line line-${ step.id }-1` })
        step.parentElement.append(line1)

        let line2 = Div({ className: `logic-line benchmark-line line-${ step.id }-2` })
        step.parentElement.append(line2)

        if ( step.style.display === 'none' ){
          line1.remove()
          line2.remove()
          return
        }

        clearLineStyle(line1)
        clearLineStyle(line2)

        let lineWidth = Math.abs( rowCenter - stepCenter ) / 2
        let lineHeight = Math.abs( rowPos.bottom - stepPos.bottom ) / 2

        line1.style.bottom = `${Math.abs(stepPos.bottom-rowPos.bottom) - lineHeight}px`
        line2.style.bottom = `${Math.abs(stepPos.bottom-rowPos.bottom) - (lineHeight*2)}px`
        line1.style.width = `${ lineWidth }px`
        line1.style.height = `${ lineHeight }px`
        line2.style.width = `${ lineWidth }px`
        line2.style.height = `${ lineHeight }px`

        // center
        if (areNumbersClose(stepCenter, rowCenter, 1)) {
          line1.style.left = '50%'
          line1.style.width = 0
          line1.style.bottom = 0
          line1.style.height = `${ lineHeight * 2 }px`
          line1.style.borderWidth = `0 0 0 ${ borderWidth }`
          line2.style.display = 'none'
        }
        // left side
        else if (stepCenter < rowCenter) {
          line1.style.left = `${ stepCenter - rowPos.left }px`
          line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line1.style.borderRadius = `0 0 0 ${borderRadius}`

          line2.style.left = `${ stepCenter - rowPos.left + lineWidth }px`
          line2.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line2.style.borderRadius = `0 ${borderRadius} 0 0`
        }
        // right side
        else {
          line1.style.left = `${ stepCenter - rowPos.left - lineWidth }px`
          line1.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line1.style.borderRadius = `0 0 ${borderRadius} 0`

          line2.style.left = `${ stepCenter - rowPos.left - (lineWidth*2) }px`
          line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line2.style.borderRadius = `${borderRadius} 0 0 0`
        }

      })
    } catch (e) {}

    // Above
    try {
      document.querySelectorAll('.logic-line.line-above').forEach(line => {

        let branchPos = line.parentElement.getBoundingClientRect()
        let stepPos = line.closest('.step-branches').previousElementSibling.getBoundingClientRect()

        let stepCenter = stepPos.left + stepPos.width / 2
        let branchCenter = branchPos.left + branchPos.width / 2

        let stepHeightCenter = stepPos.top + stepPos.height / 2
        let lineHeight = branchPos.top - stepHeightCenter

        clearLineStyle(line)
        line.classList.remove('left', 'right', 'middle')

        // center
        if ( areNumbersClose( branchCenter, stepCenter, 1 ) ) {
          line.classList.add('middle')
          lineHeight = branchPos.top - stepPos.bottom
          line.style.left = '50%'
          line.style.top = `-${ lineHeight }px`
          line.style.height = `${ lineHeight }px`
          line.style.borderWidth = `0 0 0 ${ borderWidth }`
        }
        // middle but curvy
        else if ( stepPos.left < branchCenter && branchCenter < stepPos.right ) {

          lineHeight = Math.abs( branchPos.top - stepPos.bottom )

          let line1 = line
          let line2 = line1.querySelector( '.logic-line' )
          if ( ! line2 ){
            line2 = Div({ className: `logic-line line-inside` })
            line1.append(line2)
          }

          clearLineStyle(line2)

          let lineWidth = Math.abs( branchCenter - stepCenter ) / 2

          line1.style.left = `${stepCenter - branchPos.left}px`
          line1.style.top = `-${lineHeight}px`
          line1.style.height = `${lineHeight/2}px`
          line1.style.width = `${lineWidth}px`

          line2.style.width = `${lineWidth}px`
          line2.style.height = `${lineHeight/2}px`
          line2.style.top = '100%'

          // right
          if ( branchCenter > stepCenter ) {
            line1.style.borderBottomLeftRadius = borderRadius
            line1.style.borderWidth = `0 0 ${borderWidth} ${borderWidth}`
            line2.style.left = '100%'
            line2.style.borderWidth = `${borderWidth} ${borderWidth} 0 0`
            line2.style.borderTopRightRadius = borderRadius
          } else {
            line1.style.borderBottomRightRadius = borderRadius
            line1.style.borderWidth = `0 ${borderWidth} ${borderWidth} 0`
            line2.style.right = '100%'
            line2.style.borderWidth = `${borderWidth} 0 0 ${borderWidth}`
            line2.style.borderTopLeftRadius = borderRadius
          }
        }
        // left side
        else if (branchCenter < stepCenter) {

          line.classList.add('left')

          line.style.left = '50%'
          line.style.width = `${ stepPos.left - branchCenter }px`
          line.style.top = `-${ lineHeight }px`
          line.style.height = `${ lineHeight }px`
          line.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line.style.borderTopLeftRadius = borderRadius
        }
        // right side
        else {

          line.classList.add('right')

          line.style.right = '50%'
          line.style.width = `${ branchCenter - stepPos.right }px`
          line.style.top = `-${ lineHeight }px`
          line.style.height = `${ lineHeight }px`
          line.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line.style.borderTopRightRadius = borderRadius
        }

      })
    } catch (e) {}

    // Below
    try {
      document.querySelectorAll('.logic-line.line-below').forEach(el => {

        let line1 = el
        let line2 = el.nextElementSibling

        let branchPos = line1.parentElement.getBoundingClientRect()
        let containerPos = line1.closest('.sortable-item').getBoundingClientRect()

        let stepCenter = containerPos.left + containerPos.width / 2
        let branchCenter = branchPos.left + branchPos.width / 2

        let lineHeight = Math.abs( containerPos.bottom - branchPos.bottom ) / 2
        let lineWidth = Math.abs( stepCenter - branchCenter ) / 2

        clearLineStyle(line1)
        clearLineStyle(line2)

        // center
        if (areNumbersClose(stepCenter, branchCenter, 1)) {
          line1.style.left = '50%'
          line1.style.bottom = `-${ lineHeight * 2 }px`
          line1.style.height = `${ lineHeight * 2 }px`
          line1.style.borderWidth = `0 0 0 ${ borderWidth }`
          line2.style.display = 'none'
        }
        // left side
        else if (branchCenter < stepCenter) {
          line1.style.left = '50%'
          line1.style.width = `${ lineWidth }px`
          line1.style.bottom = `-${ lineHeight }px`
          line1.style.height = `${ lineHeight }px`
          line1.style.borderWidth = `0 0 ${ borderWidth } ${ borderWidth }`
          line1.style.borderBottomLeftRadius = borderRadius

          line2.style.left = `calc(50% + ${lineWidth}px)`
          line2.style.width = `${ lineWidth }px`
          line2.style.bottom = `-${ lineHeight * 2 }px`
          line2.style.height = `${ lineHeight }px`
          line2.style.borderWidth = `${ borderWidth } ${ borderWidth } 0 0`
          line2.style.borderTopRightRadius = borderRadius

        }
        // right side
        else {
          line1.style.right = `calc(50% - ${borderWidth})`
          line1.style.width = `${ lineWidth }px`
          line1.style.bottom = `-${ lineHeight }px`
          line1.style.height = `${ lineHeight }px`
          line1.style.borderWidth = `0 ${ borderWidth } ${ borderWidth } 0`
          line1.style.borderBottomRightRadius = borderRadius

          line2.style.right = `calc(50% + ${lineWidth}px - ${borderWidth})`
          line2.style.width = `${ lineWidth }px`
          line2.style.bottom = `-${ lineHeight * 2 }px`
          line2.style.height = `${ lineHeight }px`
          line2.style.borderWidth = `${ borderWidth } 0 0 ${ borderWidth }`
          line2.style.borderTopLeftRadius = borderRadius
        }

      })
    } catch (e) {}

    $(document).trigger('draw-logic-lines')

  }

  window.addEventListener('resize', drawLogicLines)

  Groundhogg.drawLogicLines = drawLogicLines

} )(jQuery)
