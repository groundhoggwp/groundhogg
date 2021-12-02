(function ($) {
  const {
    copyObject,
    select,
    input,
    tinymceElement,
    specialChars,
    breadcrumbs,
    improveTinyMCE,
    inputWithReplacementsAndEmojis,
    inputWithReplacements,
    inputRepeaterWidget,
    textarea,
    isValidEmail,
    adminPageURL,
    modal,
    dialog,
    loadingDots,
    moreMenu,
    objectEquals,
    toggle,
    icons,
    codeEditor,
    confirmationModal,
    savingModal,
    dangerConfirmationModal,
  } = Groundhogg.element
  const { post, get, patch, delete: apiDelete, routes } = Groundhogg.api
  const { user_test_email } = Groundhogg
  const { emails: EmailsStore, campaigns: CampaignsStore } = Groundhogg.stores
  const { campaignPicker } = Groundhogg.pickers
  const { __, _x, _n, _nx, sprintf } = wp.i18n

  const setFrameContent = (frame, content) => {
    var blob = new Blob([content], { type: 'text/html; charset=utf-8' })
    frame.src = URL.createObjectURL(blob)
  }

  const EmailEditor = ({
    selector,
    email,
    onChange = (email) => {},
    onCommit = (email) => {},
    breadcrumbs: crumbs = [
      'Emails',
    ],
    onHeaderMount = () => {},
    afterPublishActions = ''
  }) => ({
    selector,
    email: copyObject(email),
    origEmail: copyObject(email),
    $el: $(selector),
    undoStates: [],
    redoStates: [],
    edited: {
      data: {},
      meta: {},
    },

    components: {
      editor () {
        //language=HTML
        return `
			<div id="email-editor">
				<div id="email-editor-header">
					${this.components.header.call(this)}
				</div>
				<div id="email-editor-body">
					<div id="email-editor-main">
						<div id="email-editor-content">
							${this.components.content.call(this)}
						</div>
						<div id="email-editor-content-editor">
							${this.components.contentEditor.call(this)}
						</div>
						<div id="email-editor-advanced">
							${this.components.controls.call(this)}
						</div>
					</div>
					<div id="email-editor-sidebar">
						${this.components.sidebar.call(this)}
					</div>
				</div>
			</div>
        `
      },

      header () {

        const titleEdit = () => {
          return input({
            id: 'email-title-edit',
            name: 'email-title',
            value: this.email.data.title
          })
        }

        const titleDisplay = () => {
          return `<span id="email-title">${specialChars(this.email.data.title)}</span><span class="dashicons dashicons-edit"></span>`
        }

        // language=HTML
        return `
			<div class="title-wrap">
				<h1 class="breadcrumbs">${breadcrumbs([
					...crumbs,
					this.isEditingTitle ? titleEdit() : titleDisplay()
				])}</h1>
			</div>
			<div class="actions">
				<div class="undo-and-redo">
					<button class="redo dashicon-button" ${this.redoStates.length ? '' : 'disabled'}><span
						class="dashicons dashicons-redo"></span></button>
					<button class="undo dashicon-button" ${this.undoStates.length ? '' : 'disabled'}><span
						class="dashicons dashicons-undo"></span></button>
				</div>
				<div class="publish-actions">
					${this.email.data.status === 'ready' ? `<button id="to-draft" class="gh-button danger text">Back to draft</button>
					<button id="commit" class="gh-button primary" ${this.hasChanges() ? '' : 'disabled'}>Update</button>` : `<button id="commit" class="gh-button action">Publish</button>`}
				</div>
				<button id="email-actions" class="gh-button secondary text icon">${icons.verticalDots}</button>
				${afterPublishActions}
			</div>
        `
      },

      content () {
        const {
          subject = '',
          pre_header = '',
        } = this.edited.data
        //language=HTML
        return `
			<div class="inline-label">
				<label for="subject">Subject:</label>
				${inputWithReplacementsAndEmojis({
					id: 'subject',
					name: 'subject',
					placeholder: 'Subject line...',
					value: subject,
				})}
			</div>
			<div class="inline-label">
				<label for="preview-text">Preview:</label>
				${inputWithReplacementsAndEmojis({
					id: 'preview-text',
					name: 'pre_header',
					placeholder: 'Preview text...',
					value: pre_header,
				})}
			</div>
        `
      },

      contentEditor () {

        const {
          content = '',
        } = this.edited.data

        // language=HTML
        return `
			<div class="email-content-wrap">
				${textarea({
					id: 'content',
					className: 'wp-editor-area',
					value: this.edited.data.content
				})}
			</div>`
      },

      sidebar () {
        const message_typeOptions = {
          marketing: 'Marketing',
          transactional: 'Transactional',
        }

        const {
          reply_to_override = '',
          alignment = 'left',
          from_user = 0,
          message_type = 'marketing',
        } = this.edited.meta

        // language=HTML
        return `
			<div class="gh-panel">
				<div class="inside">
					<div id="email-editor-sidebar-controls" class="gh-button-group">
						<button id="send-test" class="gh-button secondary">Send test email</button>
						<button data-device="mobile" class="show-preview gh-button secondary">
							${icons.smartphone}
						</button>
						<button data-device="desktop" class="show-preview gh-button secondary">
							${icons.desktop}
						</button>
					</div>
					<p>
						<label class="">Send this email from:</label>
						${select(
							{
								id: 'from-user',
								name: 'from_user',
							},
							Groundhogg.filters.owners.map((owner) => ({
								text: owner.data.user_email,
								value: owner.ID,
							})),
							from_user
						)}
					</p>
					<p>
						<label class="">Replies are sent to:</label>
						${input({
							id: 'reply-to',
							name: 'reply_to_override',
							value: reply_to_override,
						})}
					</p>
					<div id="email-editor-sidebar-options">
						<div>
							<label class="">Alignment:</label>
							<button id="align-left" data-alignment="left"
							        class="change-alignment gh-button ${
								        alignment === 'left' ? 'primary' : 'secondary'
							        }">
								${icons.alignLeft}
							</button>
							<button id="align-center" data-alignment="center"
							        class="change-alignment gh-button ${
								        alignment === 'center' ? 'primary' : 'secondary'
							        }">
								${icons.alignCenter}
							</button>
						</div>
						<div id="email-editor-sidebar-message_type">
							<label class="">Messaging type:</label>
							${select(
								{
									id: 'message-type',
									name: 'message_type',
								},
								message_typeOptions,
								message_type
							)}
						</div>
					</div>
				</div>
			</div>
        `
      },

      controls () {
        // language=HTML
        return `
			<h3>Custom email headers</h3>
			<div id="email-editor-advanced-headers">
			</div>
        `
      },

      inspector () {},
    },

    autoSaveTimeout: null,
    abortController: null,

    autoSaveChanges () {
      this.saveUndoState()

      if (this.autoSaveTimeout) {
        clearTimeout(this.autoSaveTimeout)
      }

      this.autoSaveTimeout = setTimeout(() => {
        this.autoSaveTimeout = null
        this.abortController = new AbortController()
        const { signal } = this.abortController

        EmailsStore.patch(this.email.ID, {
            meta: {
              edited: this.edited
            },
          },
          {
            signal,
          }
        ).then((e) => {
          this.loadEmail(e)
          this.abortController = null
        })
      }, 3000)
    },

    hasChanges () {
      return !objectEquals(this.email.data, this.edited.data)
    },

    update (data) {
      return EmailsStore.patch(this.email.ID, data).then((e) => {
        this.loadEmail(e)
        return e
      }).catch((e) => {
        dialog({
          type: 'error',
          message: __('Something went wrong', 'groundhogg')
        })
      })
    },

    commitChanges () {
      if (this.autoSaveTimeout) {
        clearTimeout(this.autoSaveTimeout)
      } else if (this.abortController) {
        this.abortController.abort()
      }

      const { close } = savingModal()

      return EmailsStore.patch(this.email.ID, {
        data: {
          ...this.edited.data,
          status: 'ready',
          title: this.email.data.title
        },
        meta: {
          ...this.edited.meta,
          edited: {
            ...this.edited
          }
        },
      }).then((e) => {
        this.loadEmail(e)
        this.mount()
        onCommit(e)
        close()
      }).catch((e) => {
        dialog({
          type: 'error',
          message: __('Something went wrong', 'groundhogg')
        })
      })
    },

    updateEmailData (newData) {
      this.edited.data = {
        ...this.edited.data,
        ...newData,
      }

      this.autoSaveChanges()
    },

    updateEmailMeta (newMeta) {
      this.edited.meta = {
        ...this.edited.meta,
        ...newMeta,
      }

      this.autoSaveChanges()

      onChange(this.edited, this.email)
    },

    render () {
      return this.components.editor.call(this)
    },

    mount () {
      improveTinyMCE()

      if (this.mounted) {
        this.demount()
      }

      this.mounted = true

      this.loadEmail(this.email)
      this.$el.html(this.render())
      this.onMount()
    },

    loadEmail (email) {
      // console.log(email)

      this.email = copyObject(email)

      if (email.meta.edited) {
        this.edited = copyObject(email.meta.edited)
      } else {
        this.edited = copyObject(email)
      }
    },

    onMount () {
      let saveTimer

      const handleContentOnChange = (content) => {
        clearTimeout(saveTimer)

        // Only save after a second.
        saveTimer = setTimeout(() => {
          this.updateEmailData({
            content: content,
          })
          mountHeader()
        }, 150)
      }

      const mainContentMount = () => {

        if (this.edited.meta.type === 'html') {

          const { editor } = codeEditor({
            selector: '#content',
            onChange: handleContentOnChange,
            initialContent: this.edited.data.content
          })

          this.codemirror = editor

        } else {
          tinymceElement(
            'content',
            {
              tinymce: true,
              quicktags: true,
            },
            handleContentOnChange
          )
        }

        $('#subject, #preview-text').on('change', (e) => {
          this.updateEmailData({
            [e.target.name]: e.target.value,
          })
          mountHeader()
        })

        const getHeadersArray = () => {
          const { custom_headers = {} } = this.edited.meta

          const rows = []

          Object.keys(custom_headers).forEach((key) => {
            rows.push([key, custom_headers[key]])
          })

          if (!rows.length) {
            rows.push(['', ''])
          }

          return rows
        }

        const headersEditor = inputRepeaterWidget({
          selector: '#email-editor-advanced-headers',
          rows: getHeadersArray(),
          cellProps: [
            { placeholder: 'Header...' },
            { placeholder: 'Value...' },
          ],
          cellCallbacks: [input, inputWithReplacements],
          onChange: (rows) => {
            const headers = {}

            rows.forEach(([key, value]) => {
              headers[key] = value
            })

            // console.log( headers )

            this.updateEmailMeta({
              custom_headers: headers,
            })
            mountHeader()
          },
        })

        headersEditor.mount()
      }

      const mountHeader = () => {
        $('#email-editor-header').html(this.components.header.call(this))
        headerMounted()
      }

      const headerMounted = () => {

        const handleOnSelect = (key) => {
          switch (key) {
            case 'campaigns':

              const campaignContent = () => {
                // language=HTML
                return `
					<div class="manage-campaigns" style="width: 400px">
						<h2>${__('Add this email to one or more campaigns...', 'groundhogg')}</h2>
						<p>${select({
							id: 'manage-campaigns',
							multiple: true
						})}</p>
						<p>
							${__('Campaigns are a tool to group marketing assets for reporting. Visit the dashboard to see an analytics breakdown by campaign.', 'groundhogg')}</p>
					</div>`
              }

              modal({
                content: campaignContent()
              })

              campaignPicker('#manage-campaigns', true, (items) => {
                CampaignsStore.itemsFetched(items)
              }, {
                placeholder: __('Select one or more campaigns', 'groundhogg'),
                width: '100%',
                data: [
                  { id: '', text: '' },
                  ...this.email.campaigns.map(c => ({
                    ...c,
                    id: c.ID,
                    text: c.data.name,
                    selected: true
                  }))
                ]
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
                post(`${routes.v4.emails}/${this.email.ID}/relationships`, {
                  other_id: campaign.id,
                  other_type: 'campaign'
                }).then(r => this.loadEmail(r.item))
              }).on('select2:unselect', async (e) => {
                let campaign = e.params.data

                // existing campaign
                apiDelete(`${routes.v4.emails}/${this.email.ID}/relationships`, {
                  other_id: campaign.id,
                  other_type: 'campaign'
                }).then(r => this.loadEmail(r.item))
              })

              break
            case 'export':
              window.location.href = this.email.links.export
              break
            case 'share':

              const sharingModalOnMount = () => {
                $('#sharing-enabled').on('change', ({ target }) => {
                  this.update({
                    meta: {
                      sharing: target.checked ? 'enabled' : 'disabled'
                    }
                  }).then(() => {
                    setShareContent(sharingModalContent())
                    sharingModalOnMount()
                  })
                })
              }

              const sharingModalContent = () => {
                if (this.email.meta.sharing !== 'enabled') {
                  // language=HTML
                  return `
					  <div class="share">
						  <h2>${__('Sharing is not enabled', 'groundhogg')}</h2>
						  <p>${__('Enable sharing?', 'groundhogg')} ${toggle({
							  name: 'sharing',
							  id: 'sharing-enabled',
							  checked: this.email.meta.sharing === 'enabled',
							  onLabel: _x('YES', 'toggle switch', 'groundhogg'),
							  offLabel: _x('NO', 'toggle switch', 'groundhogg'),
						  })}</p>
						  <p>
							  ${__('When sharing is enabled this email can be downloaded via a private link.', 'groundhogg')}</p>
					  </div>`
                } else {
                  // language=HTML
                  return `
					  <div class="share">
						  <h2>${__('Share this email', 'groundhogg')}</h2>
						  ${input({
							  type: 'url',
							  className: 'code full-width',
							  readonly: true,
							  value: this.email.links.export,
							  onfocus: 'this.select()'
						  })}
						  <p>
							  ${__('Anyone with the above link will be able to download a copy of this email.', 'groundhogg')}</p>
						  <p>${__('Enable sharing?', 'groundhogg')} ${toggle({
							  name: 'sharing',
							  id: 'sharing-enabled',
							  checked: this.email.meta.sharing === 'enabled',
							  onLabel: _x('YES', 'toggle switch', 'groundhogg'),
							  offLabel: _x('NO', 'toggle switch', 'groundhogg'),
						  })}</p>
					  </div>`
                }
              }

              const { setContent: setShareContent } = modal({
                // language=HTML
                content: sharingModalContent()
              })

              sharingModalOnMount()

              break
            case 'reports':
              window.location.href = this.email.links.report
              break
            case 'delete':

              dangerConfirmationModal({
                //language=HTML
                alert: `<p><b>${__('Delete this email?', 'groundhogg')}</b></p>
				<p>${__('Any associated events and reports will also be deleted.', 'groundhogg')}</p>
				<p>${__('This action cannot be undone. Are you sure?', 'groundhogg')}</p>`,
                confirmText: __('Delete'),
                onConfirm: () => {
                  EmailsStore.delete( this.email.ID ).then(() => {
                    dialog({
                      message: __( 'Email deleted!', 'groundhogg' )
                    })
                    window.location.href = adminPageURL( 'gh_emails' )
                  })
                }
              })

              break
            case 'archive':

              dangerConfirmationModal({
                //language=HTML
                alert: `
					<p>
						<b>${_x('Archive this funnel?', 'archive is representing a verb in this phrase', 'groundhogg')}</b>
					</p>
					<p>
						${__('Any active contacts will be removed from the funnel permanently. The funnel will become un-editable until restored.', 'groundhogg')}</p>`,
                confirmText: _x('Archive', 'a verb meaning to add an item to an archive', 'groundhogg'),
                onConfirm: () => {
                  this.update({
                    data: {
                      status: 'archived'
                    }
                  }).then(() => {
                    dialog({
                      message: __('Funnel Archived', 'groundhogg')
                    })

                    window.location.href = adminPageURL('gh_funnels')
                  })
                }
              })

              break
            case 'send':

              if ( this.email.data.status !== 'ready' ){
                confirmationModal({
                  alert: `<p>${__('Before this email can be sent it must be published. Would you like to publish it now?', 'groundhogg')}<p>`,
                  confirmText: __( 'Publish' ),
                  onConfirm: () => {
                    this.commitChanges().then(() => {
                      handleOnSelect( 'send' )
                    })
                  }
                })

                break;
              }

              const { close } = modal({
                content: `<div id="gh-broadcast-form" style="width: 400px"></div>`
              })

              Groundhogg.SendBroadcast('#gh-broadcast-form', {
                email: this.email
              }, {
                onScheduled: () => {
                  dialog({
                    message: __( 'Broadcast scheduled' )
                  })
                  close()
                }
              })
              break
          }
        }

        $('#email-actions').on('click', (e) => {
          moreMenu(e.currentTarget, {
            onSelect: handleOnSelect,
            items: [
              {
                key: 'campaigns',
                //language=HTML
                text: `${icons.campaign} ${_x('Campaigns', 'noun meaning collection of marketing materials', 'groundhogg')}`
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
                key: 'send',
                //language=HTML
                text: `${icons.megaphone} ${__('Send Broadcast', 'groundhogg')}`
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

        $('#commit').on('click', () => {
          this.commitChanges()
        })

        $('#to-draft').on('click', () => {

          dangerConfirmationModal({
            alert: `<p>${__('Once in draft mode this email cannot be sent. Are you sure?', 'groundhogg')}</p>`,
            confirmText: __('Unpublish'),
            onConfirm: () => {
              const { close } = savingModal()

              EmailsStore.patch(this.email.ID, {
                data: {
                  status: 'draft'
                }
              }).then((e) => {
                this.loadEmail(e)
                close()
                mountHeader()
              })
            }
          })

        })

        $('.undo-and-redo .undo').on('click', (e) => {
          this.undo()
          $('.undo-and-redo .undo').focus()
        })

        $('.undo-and-redo .redo').on('click', (e) => {
          this.redo()
          $('.undo-and-redo .redo').focus()
        })

        if (!this.isEditingTitle) {
          $('#email-title').on('click', (e) => {
            this.isEditingTitle = true
            mountHeader()
          })
        } else {
          $('#email-title-edit').focus().on('change blur keydown', (e) => {
            if (e.type === 'keydown' && e.key !== 'Enter') {
              return
            }

            const title = e.target.value

            this.email.data.title = title
            this.isEditingTitle = false
            mountHeader()

            EmailsStore.patch(this.email.ID, {
              data: {
                title
              }
            }).then((e) => {
              this.loadEmail(e)
            })
          })
        }

        onHeaderMount()
      }

      const mountSidebar = () => {
        $('#email-editor-sidebar').html(this.components.sidebar.call(this))
        sidebarMount()
      }

      const sidebarMount = () => {

        $('.show-preview').on('click', (e) => {

          const device = e.currentTarget.dataset.device

          modal({
            content: `<iframe id="preview" class="${device}"></div>`
          })

          const {
            built, edited_preview
          } = this.email.context

          setFrameContent($('#preview')[0], edited_preview || built)

        })

        $('#from-user')
          .select2()
          .on('change', (e) => {
            this.updateEmailData({
              from_user: e.target.value,
            })
            mountHeader()
          })

        $('#message-type').on('change', (e) => {
          this.updateEmailMeta({
            message_type: e.target.value,
          })
          mountHeader()
        })

        $('#reply-to').autocomplete({
          change: (e) => {
            this.updateEmailMeta({
              reply_to_override: e.target.value,
            })
            mountHeader()
          },
          source: [
            '{owner_email}',
            ...Groundhogg.filters.owners.map((u) => u.data.user_email),
          ],
        })

        $('.change-alignment').on('click', (e) => {
          this.updateEmailMeta({
            alignment: e.currentTarget.dataset.alignment,
          })
          mountHeader()
          mountSidebar()
          $('#' + e.currentTarget.id).focus()
        })

        $('#send-test').on('click', (e) => {
          if (!this.testEmailAddress) {
            this.testEmailAddress = user_test_email
          }

          const modalContent = (isSending = false) => {
            //language=HTML
            return `<h2>Send a test email to the following address...</h2>
			<div class="test-email-address-wrap">
				${input({
					type: 'email',
					id: 'email-address',
					name: 'email-address',
					placeholder: 'Your email...',
					disabled: isSending,
					value: this.testEmailAddress,
				})}
				<button id="initiate-test" class="gh-button primary" ${
					isSending ? 'disabled' : ''
				}>
					<span>${isSending ? 'Sending' : 'Send'}</span>
				</button>
			</div>`
          }

          const { $modal, close: closeModal, setContent } = modal({
            content: modalContent(),
          })

          $('#email-address').autocomplete({
            source: Groundhogg.filters.owners.map((u) => u.data.user_email),
            change: (e) => {
              this.testEmailAddress = e.target.value
              mountHeader()
            },
          })

          $('#initiate-test').on('click', () => {
            setContent(modalContent(true))
            const { stop: stopDots } = loadingDots('#initiate-test')

            post(`${routes.v4.emails}/${this.email.ID}/test`, {
              to: this.testEmailAddress,
              edited: this.edited,
            }).then((r) => {
              stopDots()
              setContent(`<p>Test sent to <b>${this.testEmailAddress}</b></p>`)
              setTimeout(closeModal, 2000)
            })
          })
        })
      }

      mainContentMount()
      sidebarMount()
      headerMounted()
    },

    demount () {
      this.onDemount()
    },

    onDemount () {

      if (this.edited.meta.type === 'html') {
        this.codemirror.toTextArea()
      } else {
        wp.editor.remove('content')
      }
    },

    currentState () {
      const { email, edited } = this

      return {
        email: copyObject(email),
        edited: copyObject(edited),
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
      var lastState = this.undoStates.pop()

      if (!lastState) {
        return
      }

      this.redoStates.push(this.currentState())

      Object.assign(this, lastState)

      this.mount()
    },

    /**
     * Redo the previous change
     */
    redo () {
      var lastState = this.redoStates.pop()

      if (!lastState) {
        return
      }

      this.undoStates.push(this.currentState())

      Object.assign(this, lastState)

      this.mount()
    },
  })

  Groundhogg.EmailEditor = EmailEditor

  
})(jQuery)
