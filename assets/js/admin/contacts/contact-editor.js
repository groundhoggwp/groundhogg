( ($, editor) => {

  const { contact, meta_exclusions } = editor

  const { gh_contact_custom_properties } = Groundhogg.filters

  const {
    tooltip,
    regexp,
    inputRepeaterWidget,
    inputRepeater,
    el,
    searchOptionsWidget,
    input,
    select,
    isNumeric,
    textarea,
    icons,
    bold,
    loadingModal,
    modal,
    uuid,
    dangerConfirmationModal,
    confirmationModal,
    adminPageURL,
    moreMenu,
    setFrameContent,
    loadingDots,
    spinner,
    dialog,
  } = Groundhogg.element
  const {
    currentUser,
    filters,
    propertiesEditor,
    isWPFusionActive,
  } = Groundhogg
  const { userHasCap } = Groundhogg.user
  const {
    events: EventsStore,
    event_queue: EventQueue,
    tags: TagsStore,
    contacts: ContactsStore,
    emails: EmailsStore,
    activity: ActivityStore,
    funnels: FunnelsStore,
    broadcasts: BroadcastsStore,
    page_visits: PageVisitsStore,
    submissions: SubmissionsStore,
  } = Groundhogg.stores

  const { emailPicker } = Groundhogg.pickers

  const { post, delete: _delete, get, patch, routes, ajax } = Groundhogg.api
  const {
    selectContactModal, betterTagPicker, internalForm,
  } = Groundhogg.components

  const { sprintf, __, _x, _n } = wp.i18n

  ContactsStore.itemsFetched([contact])

  let files = []

  const maybeCall = (maybeFunc, ...args) => {
    if (typeof maybeFunc === 'string') {
      return maybeFunc
    }
    if (typeof maybeFunc === 'function') {
      return maybeFunc(...args)
    }

    return maybeFunc
  }

  const activityUpdated = () => {
    window.dispatchEvent(new Event('activityupdated'))
  }

  const getContact = () => {
    return ContactsStore.get(contact.ID)
  }

  const sanitizeKey = (label) => {
    return label.toLowerCase().replace(/[^a-z0-9]/g, '_')
  }

  const sendEmail = () => {

    let contact = getContact()

    let email = {
      to: [contact.data.email],
      from_email: currentUser.data.user_email,
      from_name: currentUser.data.display_name,
    }

    if (contact.data.owner_id && currentUser.ID != contact.data.owner_id) {
      email.cc = [
        filters.owners.find(u => u.ID == contact.data.owner_id).data.user_email,
      ]
    }

    Groundhogg.components.emailModal(email)
  }

  const ContactActions = [
    {
      id: 'send-email',
      icon: icons.email,
      tooltip: __('Send Email', 'groundhogg'),
      show: contact => true,
      onClick: e => {
        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'compose',
              text: __('Compose', 'groundhogg'),
            },
            {
              key: 'template',
              text: __('Use template', 'groundhogg'),
            },
          ],
          onSelect: k => {
            switch (k) {

              case 'compose':
                sendEmail()
                break
              case 'template':

                let emailId

                const preview = () => {

                  // language=HTML
                  return `
                      <div class="gh-row">
                          <div class="gh-col">
                              <iframe id="select-email-preview"
                                      class="hidden"></iframe>
                          </div>
                      </div>`
                }

                const showFrame = () => {
                  if (emailId) {
                    let $frm = $('#select-email-preview')
                    setFrameContent($frm[0],
                      EmailsStore.get(emailId).context.built)
                    $frm.removeClass('hidden')
                  }
                }

                modal({
                  width: 500,
                  // language=HTML
                  content:
                    `
                        <h2>${ __('Select an email template to send',
                                'groundhogg') }</h2>
                        <div class="gh-rows-and-columns">
                            <div class="gh-row">
                                <div class="gh-col">
                                    ${ select({
                                        name: 'email',
                                        id: `select-email`,
                                        options: [
                                            { text: '', value: '' },
                                            ...EmailsStore.getItems().
                                                    map(e => ( {
                                                        text: e.data.title,
                                                        value: e.ID,
                                                    } )),
                                        ],
                                        selected: emailId,
                                    }) }
                                </div>
                            </div>
                            ${ preview() }
                            <div class="gh-row">
                                <div class="gh-col">
                                    <button id="send-email-template"
                                            class="gh-button primary" disabled>
                                        ${ __('Send Email', 'groundhogg') }
                                    </button>
                                </div>
                            </div>
                        </div>
                    `,
                  onOpen: ({ close }) => {

                    let $btn = $('#send-email-template')

                    emailPicker(`#select-email`, false,
                      (items) => {EmailsStore.itemsFetched(items)}, {
                        status: 'ready',
                      }, {
                        placeholder: __('Select an email to send...',
                          'groundhogg'),
                      }).on('change', ({ target }) => {

                      emailId = parseInt(target.value)
                      showFrame()

                      $btn.prop('disabled', false)

                    })

                    $btn.on('click', e => {

                      $btn.prop('disabled', true)
                      let { stop } = loadingDots(e.currentTarget)

                      post(`${ EmailsStore.route }/${ emailId }/send`, {
                        to: getContact().ID,
                      }).then(r => {
                        dialog({
                          message: __('Email sent!'),
                        })
                        stop()
                        close()
                      }).catch(e => {
                        stop()
                        $btn.prop('disabled', false)
                        dialog({
                          type: 'error',
                          message: e.message,
                        })
                      })

                    })
                  },
                })

                break

            }
          },
        })
      },
    },
    {
      id: 'call-primary',
      icon: icons.phone,
      tooltip: __('Call primary phone', 'groundhogg'),
      show: contact => contact.meta.primary_phone,
      onClick: e => {
        window.open(`tel:${ contact.meta.primary_phone }`)
      },
    },
    {
      id: 'call-mobile',
      icon: icons.smartphone,
      tooltip: __('Call mobile', 'groundhogg'),
      show: contact => contact.meta.mobile_phone,
      onClick: e => {
        window.open(`tel:${ contact.meta.mobile_phone }`)
      },
    },
    {
      id: 'add-to-funnel',
      icon: icons.funnel,
      tooltip: __('Add to a funnel', 'groundhogg'),
      show: contact => true,
      onClick: e => {
        modal({
          content: ``,
          onOpen: ({ close, setContent }) => {

            let funnel = FunnelsStore.hasItems()
              ? FunnelsStore.getItems()[0]
              : null
            let step = funnel ? funnel.steps[0] : null

            const addToFunnelUi = () => {
              // language=HTML
              return `
                  <div>
                      <h2>${ sprintf(__('Add %s to a funnel', 'groundhogg'),
                              getContact().data.full_name) }</h2>
                      <div class="gh-rows-and-columns">
                          <div class="gh-row">
                              <div class="gh-col">
                                  ${ select({ id: 'select-funnel' }) }
                              </div>
                          </div>
                          <div class="gh-row">
                              <div class="gh-col">
                                  ${ funnel ? select({ id: 'select-step' }) : '' }
                              </div>
                          </div>
                          <div class="gh-row">
                              <div class="gh-col">
                                  ${ funnel && step
                                          ? `<button id="confirm-add-to-funnel" class="gh-button primary">${ __(
                                                  'Add to funnel') }</button>`
                                          : '' }
                              </div>
                          </div>
                      </div>
                  </div>`
            }

            setContent(addToFunnelUi())

            const onMount = () => {

              $('#select-funnel').ghPicker({
                endpoint: FunnelsStore.route,
                getParams: (q) => {
                  return {
                    ...q,
                    status: 'active',
                  }
                },
                data: FunnelsStore.getItems().map(f => ( {
                  id: f.ID,
                  text: f.data.title,
                  selected: funnel && f.ID === funnel.ID,
                } )),
                getResults: ({ items }) => {
                  FunnelsStore.itemsFetched(items)
                  return items.map(f => ( { id: f.ID, text: f.data.title } ))
                },
                placeholder: __('Select a funnel...', 'groundhogg'),
              }).on('change', ({ target }) => {
                funnel = FunnelsStore.get(parseInt($(target).val()))

                step = funnel.steps.find(s => s.data.step_order == 1)
                setContent(addToFunnelUi())
                onMount()

              })

              if (funnel) {
                $('#select-step').select2({
                  placeholder: __('Select a step...', 'groundhogg'),
                  data: funnel.steps.sort(
                    (a, b) => a.data.step_order - b.data.step_order).map(s => ( {
                    id: s.ID,
                    text: `${ s.data.step_title } (${ Groundhogg.rawStepTypes[s.data.step_type].name })`,
                    selected: s.ID == step.ID,
                  } )),
                  // templateSelection: template,
                  // templateResult: template
                }).on('change', ({ target }) => {
                  step = funnel.steps.find(
                    s => s.ID === parseInt($(target).val()))
                  setContent(addToFunnelUi())
                  onMount()
                })
              }

              if (funnel && step) {
                $('#confirm-add-to-funnel').on('click', e => {

                  FunnelsStore.addContacts({
                    funnel_id: funnel.ID,
                    step_id: step.ID,
                    contact_id: getContact().ID
                  }).then(() => {

                    dialog({
                      message: sprintf(__('%s added to funnel!', 'groundhogg'),
                        getContact().data.full_name),
                    })

                    close()

                  })

                })
              }

            }

            onMount()

          },
        })
      },
    },
    {
      id: 'internal-form',
      icon: icons.form,
      tooltip: __('Submit an internal form', 'groundhogg'),
      show: contact => true,
      onClick: e => {
        internalForm({
          contact: getContact(),
          onSubmit: () => {
            activityUpdated()
          },
        })
      },
    },

  ]

  const contactMoreActions = () => {

    // language = HTML
    let actions = `
        ${ ContactActions.filter(action => action.show(getContact())).
      map(({
        icon,
        id,
      }) => `<button id="action-${ id }" class="gh-button secondary text icon">${ icon }</button>`).
      join('') }
				<button id="contact-more" class="gh-button secondary text icon">${ icons.verticalDots }</button>`

    $('#contact-more-actions').html(actions)

    ContactActions.forEach(({ id, tooltip: __tp, onClick }) => {

      $(`#action-${ id }`).on('click', e => onClick(e, getContact()))

      tooltip(`#action-${ id }`, {
        content: __tp,
      })

    })

    $('#contact-more').on('click', e => moreMenu(e.target, {
      items: [
        {
          key: 'merge',
          cap: 'delete_contacts',
          text: __('Merge'),
        },
        {
          key: 'delete',
          cap: 'delete_contacts',
          text: `<span class="gh-text danger">${ __('Delete') }</span>`,
        },
      ].filter(i => userHasCap(i.cap)),
      onSelect: k => {
        switch (k) {
          case 'merge':

            selectContactModal({
              exclude: [contact.ID],
              onSelect: (_contact) => {

                confirmationModal({
                  confirmText: __('Merge'),
                  width: 500,
                  // language=HTML
                  alert: `<p>
                      ${ sprintf(
                              __('Are you sure you want to merge %1$s with %2$s? This action cannot be undone.',
                                      'groundhogg'),
                              bold(_contact.data.full_name),
                              bold(getContact().data.full_name)) }</p>
                  <p>
                      <a href="https://help.groundhogg.io/article/540-merging-contacts"
                         target="_blank">${ __(
                              'What happens when contacts are merged?',
                              'groundhogg') }</a></p>`,
                  onConfirm: () => {

                    loadingModal()

                    post(`${ ContactsStore.route }/${ contact.ID }/merge`, [
                      _contact.ID,
                    ]).then(() => {
                      location.reload()
                    })
                  },
                })

              },
            })

            break
          case 'delete':
            dangerConfirmationModal({
              confirmText: __('Delete'),
              alert: `<p>${ sprintf(
                __('Are you sure you want to delete %s?', 'groundhogg'),
                bold(getContact().data.full_name)) }</p>`,
              onConfirm: () => {
                ContactsStore.delete(contact.ID).then(() => {
                  dialog({
                    message: sprintf(__('%s was deleted!', 'groundhogg'),
                      contact.data.full_name),
                  })
                  window.open(adminPageURL('gh_contacts'), '_self')
                })
              },
            })
        }
      },
    }))
  }

  const ActivityTimeline = {

    hiddenActivity: [
      'thread_reply',
    ],

    addType (type, opts) {
      this.types[type] = {
        icon: '',
        render: () => '',
        preload: () => {},
        ...opts,
      }
    },

    types: {
      wp_fusion: {
        icon: icons.wp_fusion,
        iconFramed: false,
        render: ({ data, meta }) => {
          const { event_name, event_value } = meta
          return `${ event_name }: <code>${ event_value }</code>`
        },
        preload: () => {},
      },
      wp_login: {
        icon: icons.login,
        render: ({ email }) => {
          return __('Logged in', 'groundhogg')
        },
        preload: () => {},
      },
      wp_logout: {
        icon: icons.logout,
        render: ({ email }) => {
          return __('Logged out', 'groundhogg')
        },
        preload: () => {},
      },
      email_opened: {
        icon: icons.open_email,
        render: ({ email }) => {
          return sprintf(__('Opened %s', 'groundhogg'), bold(email.data.title))
        },
        preload: ({ email }) => {
          EmailsStore.itemsFetched([email])
        },
      },
      composed_email_sent: {
        icon: icons.open_email,
        render: ({ meta, sent_by, ID }) => {
          return sprintf(__('%s sent an email with subject %s', 'groundhogg'),
            bold(sent_by),
            `<a href="#" class="view-composed-email-log-item" data-activity-id="${ ID }">${ bold(meta.subject) }</a>`)
        },
        preload: () => {},
      },
      email_link_click: {
        icon: icons.link_click,
        render: ({ email, data }) => {

          const maybeTruncateLink = (link) => {
            return link.length > 50 ? `${ link.substring(0, 47) }...` : link
          }

          return sprintf(__('Clicked %s in %s', 'groundhogg'), el('a', {
            target: '_blank',
            href: data.referer,
          }, bold(maybeTruncateLink(data.referer))), bold(email.data.title))
        },
        preload: ({ email }) => {
          EmailsStore.itemsFetched([email])
        },
      },
      fallback: {
        icon: icons.heartbeat,
        render: ({ data, meta }) => {

          let html = [
            data.activity_type,
          ]

          if (data.value) {
            html.push(`: <code>${ data.value }</code>`)
          }

          if (Object.keys(meta).length) {
            html.push(textarea({
              className: 'full-width code custom-activity-meta space-above-20',
              value: JSON.stringify(meta, null, 2),
              readonly: true,
            }))
          }

          return html.join('')
        },
      },
    },

    renderActivity (activity) {

      if (activity.type === 'submission') {

        let funnel = FunnelsStore.get(activity.form.data.funnel_id)
        // language=HTML
        return `
            <li class="activity-item">
                <div class="activity-icon submission">${ icons.form }</div>
                <div class="activity-rendered gh-panel">
                    <div class="activity-info">
                        ${ sprintf(__('Submitted form %s in funnel %s', 'groundhogg'),
                                bold(activity.form.data.step_title), el('a', {
                                    href: funnel.admin,
                                    target: '_blank',
                                }, bold(funnel.data.title))) }
                        <p>
                            ${ textarea({
                                className: 'full-width code',
                                value: JSON.stringify(activity.meta, null, 2),
                                readonly: true,
                            }) }
                        </p>
                    </div>
                    <div class="diff-time">
                        ${ activity.locale.diff_time }
                    </div>
                </div>
            </li>`
      }

      if (activity.type === 'page_visit') {
        // language=HTML
        return `
            <li class="activity-item">
                <div class="activity-icon page-visit">${ icons.link_click }
                </div>
                <div class="activity-rendered gh-panel">
                    <div class="activity-info">
                        ${ sprintf(__('Visited %s', 'groundhogg'),
                                `<a href="${ activity.data.path }" target="_blank">${ bold(
                                        activity.data.path) }</a>`) }
                    </div>
                    <div class="diff-time">
                        ${ activity.locale.diff_time }
                    </div>
                </div>
            </li>`
      }

      if (activity.type === 'event') {

        let { step, pending = false } = activity

        switch (parseInt(activity.data.event_type)) {
          case 1:

            let funnel = FunnelsStore.get(step.data.funnel_id)

            let stepTitleDisplay = bold(step.data.step_title)

            // Support for email log items
            if (!pending && ['admin_notification', 'send_email'].includes(step.data.step_type)) {
              stepTitleDisplay = el('a', {
                href: '#',
                className: 'view-event-email-log-item',
                dataEventId: activity.ID,
              }, stepTitleDisplay)
            }

            // language=HTML
            return `
                <li class="activity-item">
                    <div class="activity-icon ${ step.data.step_group } ${pending ? 'pending' : '' }">
                        ${ pending ? icons.hourglass : `<img class="step-icon" src="${Groundhogg.rawStepTypes[step.data.step_type].icon}" alt="${Groundhogg.rawStepTypes[step.data.step_type].name}"/>` }
                    </div>
                    <div class="activity-rendered gh-panel space-between">
                        <div>
                            <div class="activity-info">
                                <span>${ sprintf(pending ? __(
                                                'Pending %s',
                                                'groundhogg') : __(
                                                'Completed %s',
                                                'groundhogg'),
                                        stepTitleDisplay) }</span>
                            </div>
                            <div class="event-extra">
                                ${ sprintf(__('%s in funnel %s', 'groundhogg'),
                                        el('span', {
                                                    className: [
                                                        'step-type',
                                                        step.data.step_group,
                                                    ].join(' '),
                                                },
                                                Groundhogg.rawStepTypes[step.data.step_type].name),
                                        el('a', {
                                            href: funnel.admin + '#' + activity.data.step_id,
                                        }, funnel.data.title)) }
                            </div>
                            <div class="diff-time">
                                ${ activity.locale.diff_time }
                            </div>
                        </div>
                        <button
                                class="gh-button secondary icon text event-${ pending
                                        ? 'queue-'
                                        : '' }more"
                                data-event="${ activity.ID }">
                            ${ icons.verticalDots }
                        </button>
                    </div>
                </li>`
          case 2:

            let objectTitleDisplay = bold(activity.broadcast.object.data.title)

            // Support for email log items
            if (!pending && activity.broadcast.data.object_type === 'email') {
              objectTitleDisplay = el('a', {
                href: '#',
                className: 'view-event-email-log-item',
                dataEventId: activity.ID,
              }, objectTitleDisplay)
            }

            // language=HTML
            return `
                <li class="activity-item">
                    <div class="activity-icon broadcast">${ icons.megaphone }
                    </div>
                    <div class="activity-rendered gh-panel space-between">
                        <div>
                            <div class="activity-info">
                                <span>${ sprintf(pending ? __(
                                                'Will receive broadcast: %s',
                                                'groundhogg') : __(
                                                'Received broadcast: %s', 'groundhogg'),
                                        objectTitleDisplay) }</span>
                            </div>
                            <div class="diff-time">
                                ${ activity.locale.diff_time }
                            </div>
                        </div>
                        <button
                                class="gh-button secondary icon text event-${ pending
                                        ? 'queue-'
                                        : '' }more"
                                data-event="${ activity.ID }">
                            ${ icons.verticalDots }
                        </button>
                    </div>
                </li>`
          case 3:

            let emailTitleDisplay = bold(activity.email.email.data.title)

            if (!pending) {
              emailTitleDisplay = el('a', {
                href: '#',
                className: 'view-event-email-log-item',
                dataEventId: activity.ID,
              }, emailTitleDisplay)
            }

            // language=HTML
            return `
                <li class="activity-item">
                    <div class="activity-icon broadcast">${ icons.email }
                    </div>
                    <div class="activity-rendered gh-panel space-between">
                        <div>
                            <div class="activity-info">
                                <span>${ sprintf(pending ? __(
                                                'Will receive email: %s',
                                                'groundhogg') : __(
                                                'Received email: %s', 'groundhogg'),
                                        emailTitleDisplay) }</span>
                            </div>
                            <div class="diff-time">
                                ${ activity.locale.diff_time }
                            </div>
                        </div>
                        <button
                                class="gh-button secondary icon text event-${ pending
                                        ? 'queue-'
                                        : '' }more"
                                data-event="${ activity.ID }">
                            ${ icons.verticalDots }
                        </button>
                    </div>
                </li>`
        }

        return ''
      }

      if (this.hiddenActivity.includes(activity.data.activity_type)) {
        return ''
      }

      const type = this.types.hasOwnProperty(activity.data.activity_type)
        ? this.types[activity.data.activity_type]
        : this.types.fallback

      // language=HTML
      return `
          <li class="activity-item ${ activity.data.activity_type } activity"
              tabindex="0">
              <div
                      class="activity-icon ${ activity.data.activity_type } ${ type.iconFramed ===
                      false
                              ? 'no-frame'
                              : '' }">
                  ${ maybeCall(type.icon, activity) }
              </div>
              <div class="activity-rendered gh-panel">
                  <div class="activity-info">
                      ${ type.render(activity) }
                  </div>
                  <div class="diff-time">
                      ${ activity.locale.diff_time }
                  </div>
              </div>
          </li>`
    },

    render (activities) {

      // language=HTML
      return `
          <ul id="activity-timeline">
              ${ activities.map(a => {
                  try {
                      return this.renderActivity(a)
                  }
                  catch (e) {
                      return ''
                  }
              }).join('') }
          </ul>`

    },

    onMount () {

      $('.event-queue-more').on('click', (e) => {

        let eventId = e.currentTarget.dataset.event
        const event = EventQueue.get(eventId)

        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'execute',
              text: __('Run Now'),
            },
            {
              key: 'cancel',
              text: `<span class="gh-text danger">${ __('Cancel') }</span>`,
            },
          ],
          onSelect: (key) => {
            switch (key) {
              case 'cancel':

                patch(`${ EventQueue.route }/${ event.ID }/cancel`).then(() => {
                  EventQueue.items.splice(
                    EventQueue.items.findIndex(e => e.ID === event.ID), 1)
                  dialog({
                    message: __('Event cancelled', 'groundhogg'),
                  })
                  this.needsRefresh()
                })

                break

              case 'execute':

                patch(`${ EventQueue.route }/${ event.ID }/execute`).then(() => {
                  dialog({
                    message: __('Event rescheduled', 'groundhogg'),
                  })
                  this.needsRefresh()
                })

                break
            }
          },
        })
      })

      $('.event-more').on('click', (e) => {

        let eventId = e.currentTarget.dataset.event
        const event = EventsStore.get(eventId)

        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'execute',
              text: __('Run Again'),
            },
          ],
          onSelect: (key) => {
            switch (key) {
              case 'execute':

                patch(`${ EventsStore.route }/${ event.ID }/execute`).then(() => {
                  dialog({
                    message: __('Event rescheduled', 'groundhogg'),
                  })
                  this.needsRefresh()
                })

                break
            }
          },
        })
      })
    },

    mount (selector, activities, {
      needsRefresh = () => {},
    }) {

      this.needsRefresh = needsRefresh

      const $el = $(selector)

      // Only show supported activities
      // activities = activities.filter(
      //   a => ['event', 'page_visit', 'submission'].includes(a.type) ||
      //     a.data.activity_type in this.types)

      if (!activities.length) {
        $el.html(
          `<div class="align-center-space-between" style="margin: 20px"><span class="pill orange">${ __(
            'No activity found.', 'groundhogg') }</span></div>`)
        return
      }

      let funnelIds = activities.reduce((arr, e) => {

        let funnelId = parseInt(e.data?.funnel_id || e.form?.data?.funnel_id)

        if (funnelId > 1) {
          if (!arr.includes(funnelId)) {
            arr.push(funnelId)
          }
        }

        return arr
      }, [])

      let promises = [
        // Preload activities
        ...activities.filter(a => a.type === 'activity').
          map(a => this.types[a.data.activity_type]?.preload(a)),

        // events with funnel IDs
        funnelIds.length && !FunnelsStore.hasItems(funnelIds)
          ? FunnelsStore.fetchItems({
            ID: funnelIds,
          })
          : null,

        // Broadcast Events
        ...activities.filter(a => a.type === 'event' && a.data.event_type == 2).
          map(a => BroadcastsStore.itemsFetched([a.broadcast])),
      ]

      Promise.all(promises).then(() => {
        $el.html(this.render(activities))
        this.onMount()
      }).catch(e => {
        // Something went wrong
        console.log(e)
      })

    },

  }

  const otherContactStuff = () => {

    let activeTab = editor.default_tab ?? 'activity'

    const tabs = [
      {
        id: 'activity',
        name: __('Activity'),
        render: () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div class="inside">
                      <div class="display-flex gap-10 align-bottom">
                          <div class="order-by">
                              <label for="activity-order"><b>${ __(
                                      'Order by') }</b></label><br/>
                              ${ select({
                                  id: 'activity-order',
                                  name: 'order',
                              }, {
                                  desc: __('Newest first'),
                                  asc: __('Oldest first'),
                              }, 'desc') }
                          </div>
                          <div class="filter-by">
                              <label for="filter-by"><b>${ __(
                                      'Filter by') }</b></label><br/>
                              ${ select({
                                  id: 'filter-by',
                                  name: 'filter',
                              }, {
                                  all: __('All Activity', 'groundhogg'),
                                  funnel: __('Funnel Activity', 'groundhogg'),
                                  email: __('Email Activity', 'groundhogg'),
                                  web: __('Web Activity', 'groundhogg'),
                                  ...isWPFusionActive ? {
                                      wp_fusion: __('WPFusion Activity',
                                              'groundhogg'),
                                  } : {},
                              }, '') }
                          </div>
                          <button id="refresh-timeline"
                                  class="gh-button secondary text icon"><span
                                  class="dashicons dashicons-update-alt"></span>
                          </button>
                      </div>
                  </div>
              </div>
              <div id="activity-here">
                  ${ spinner() }
              </div>`
        },
        onMount: () => {

          let order = 'desc'
          let filter = 'all'

          $('#refresh-timeline').on('click', e => {

            $(e.currentTarget).find('.dashicons').addClass('spinning')

            fetchActivity().then(() => {
              $(e.currentTarget).find('.dashicons').removeClass('spinning')
            })
          })

          tooltip('#refresh-timeline', {
            content: __('Refresh'),
            position: 'right',
          })

          $('#activity-order').on('change', (e) => {
            order = e.target.value
            fetchActivity()
          })

          $('#filter-by').on('change', (e) => {
            filter = e.target.value
            loadTimeline()
          })

          const fetchActivity = () => {
            return Promise.all([
              SubmissionsStore.fetchItems({
                contact_id: contact.ID,
                limit: 50,
                order,
                orderby: 'date_created',
              }),
              ActivityStore.fetchItems({
                contact_id: contact.ID,
                limit: 50,
                order,
                orderby: 'timestamp',
              }),
              EventsStore.fetchItems({
                contact_id: contact.ID,
                status: 'complete',
                limit: 50,
                orderby: 'time',
                order,
              }),
              EventQueue.fetchItems({
                contact_id: contact.ID,
                status: 'waiting',
                limit: 50,
                orderby: 'time',
                order,
              }),
              PageVisitsStore.fetchItems({
                contact_id: contact.ID,
                limit: 50,
                orderby: 'timestamp',
                order,
              }),
            ]).then(() => {
              loadTimeline()
            }).catch(e => {
              loadTimeline()
            })
          }

          const loadTimeline = () => {
            let allActivities = [
              ...SubmissionsStore.getItems().map(a => ( {
                ...a,
                type: 'submission',
                time: parseInt(a.data.time),
              } )), ...ActivityStore.getItems().map(a => ( {
                ...a,
                type: 'activity',
                time: parseInt(a.data.timestamp),
              } )),
              ...EventsStore.getItems().map(e => ( {
                ...e,
                type: 'event',
                time: parseInt(e.data.time) + parseFloat(e.data.micro_time),
              } )),
              ...EventQueue.getItems().map(e => ( {
                ...e,
                type: 'event',
                pending: true,
                time: parseInt(e.data.time) + parseFloat(e.data.micro_time),
              } )),
              ...PageVisitsStore.getItems().map(v => ( {
                ...v,
                type: 'page_visit',
                time: parseInt(v.data.timestamp),
              } )),
            ].sort(
              (a, b) => {

                // Order by mirco time second
                if (b.time === a.time && a.micro_time && b.micro_time) {
                  return order === 'desc'
                    ? b.micro_time - a.micro_time
                    : a.micro_time - b.micro_time
                }

                return order === 'desc' ? b.time - a.time : a.time - b.time

              })

            switch (filter) {
              case 'submissions':
                allActivities = allActivities.filter(
                  a => a.type === 'submission')
                break
              case 'funnel':
                allActivities = allActivities.filter(
                  a => a.type === 'event' && a.data.event_type == 1)
                break
              case 'email':
                allActivities = allActivities.filter(a => a.data.email_id > 0)
                break
              case 'web':
                allActivities = allActivities.filter(
                  a => a.type === 'page_visit')
                break
              case 'wp_fusion':
                allActivities = allActivities.filter(
                  a => a.type === 'activity' && a.data.activity_type ===
                    'wp_fusion')
                break
            }

            ActivityTimeline.mount('#activity-here', allActivities, {
              needsRefresh: () => {
                fetchActivity()
              },
            })

            $('#activity-here').
              css({ maxHeight: $('#primary-contact-stuff').height() })
          }

          if (ActivityStore.hasItems()
            || EventsStore.hasItems()
            || EventQueue.hasItems()
            || PageVisitsStore.hasItems()
            || SubmissionsStore.hasItems()
          ) {
            loadTimeline()
            return
          }

          fetchActivity()
        },
      },
      {
        id: 'notes',
        name: __('Notes'),
        render: () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div class="inside" id="notes-here"></div>
              </div>`
        },
        onMount: () => {
          Groundhogg.noteEditor('#notes-here', {
            object_id: contact.ID,
            object_type: 'contact',
            title: '',
          })
        },
      },
      {
        id: 'tasks',
        name: __('Tasks'),
        render: () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div class="inside" id="tasks-here"></div>
              </div>`
        },
        onMount: () => {
          Groundhogg.taskEditor('#tasks-here', {
            object_id: contact.ID,
            object_type: 'contact',
            title: '',
          })
        },
      },
      {
        id: 'files',
        name: __('Files'),
        render: () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div id="file-actions" class="inside display-flex gap-10">
                      ${ input({
                          placeholder: __('Search files...'),
                          type: 'search',
                          id: 'search-files',
                          className: 'full-width',
                      }) }
                      <button id="upload-file" class="gh-button secondary">
                          ${ __('Upload Files') }
                      </button>
                  </div>
                  <div id="bulk-actions" class="hidden inside"
                       style="padding-top: 0">
                      <button id="bulk-delete-files"
                              class="gh-button danger icon"><span
                              class="dashicons dashicons-trash"></span></button>
                  </div>
                  <table class="wp-list-table widefat striped"
                         style="border: none">
                      <thead></thead>
                      <tbody id="files-here">
                      </tbody>
                  </table>
              </div>`
        },
        onMount: () => {

          let selectedFiles = []

          let fileSearch = ''

          $('#bulk-delete-files').on('click', () => {
            dangerConfirmationModal({
              confirmText: __('Delete'),
              alert: `<p>${ sprintf(
                _n('Are you sure you want to delete %d file?',
                  'Are you sure you want to delete %d files?',
                  selectedFiles.length, 'groundhogg'),
                selectedFiles.length) }</p>`,
              onConfirm: () => {
                _delete(`${ routes.v4.contacts }/${ contact.ID }/files`,
                  selectedFiles).then(({ items }) => {
                  selectedFiles = []
                  files = items
                  mount()
                })
              },
            })
          })

          $('#search-files').on('input change', e => {
            fileSearch = e.target.value
            mount()
          })

          tooltip('#bulk-delete-files', {
            content: __('Bulk delete files'),
            position: 'right',
          })

          const renderFile = (file) => {
            //language=HTML
            return `
                <tr class="file">
                    <th scope="row" class="check-column">${ input({
                        type: 'checkbox',
                        name: 'select[]',
                        className: 'file-toggle',
                        value: file.name,
                    }) }
                    </th>
                    <td class="column-primary"><a class="row-title"
                                                  href="${ file.url }"
                                                  target="_blank">${ file.name }</a>
                    </td>
                    <td>${ file.date_modified }</td>
                    <td>
                        <div class="space-between align-right">
                            <button data-file="${ file.name }"
                                    class="file-more gh-button secondary text icon">
                                ${ icons.verticalDots }
                            </button>
                        </div>
                    </td>
                </tr>`
          }

          const mount = () => {

            $('#files-here').html(files.filter(
              f => !fileSearch || f.name.match(regexp(fileSearch))).
              map(f => renderFile(f)).
              join(''))
            onMount()
          }

          const onMount = () => {

            const maybeShowBulkActions = () => {
              if (selectedFiles.length) {
                $('#bulk-actions').removeClass('hidden')
              }
              else {
                $('#bulk-actions').addClass('hidden')
              }
            }

            $('.file-more').on('click', e => {

              let _file = e.currentTarget.dataset.file

              moreMenu(e.currentTarget, {

                items: [
                  {
                    key: 'download',
                    text: __('Download'),
                  },
                  userHasCap('delete_files') ? {
                    key: 'delete',
                    text: `<span class="gh-text danger">${ __(
                      'Delete') }</span>`,
                  } : false,
                ],
                onSelect: k => {
                  switch (k) {
                    case 'download':
                      window.open(files.find(f => f.name === _file).url,
                        '_blank').focus()
                      break
                    case 'delete':

                      dangerConfirmationModal({
                        confirmText: __('Delete'),
                        alert: `<p>${ sprintf(
                          __('Are you sure you want to delete %s?',
                            'groundhogg'), _file) }</p>`,
                        onConfirm: () => {
                          _delete(
                            `${ routes.v4.contacts }/${ contact.ID }/files`, [
                              _file,
                            ]).then(({ items }) => {
                            selectedFiles = []
                            files = items
                            mount()
                          })
                        },
                      })

                      break
                  }
                },
              })
            })

            $('.file-toggle').on('change', e => {
              if (e.target.checked) {
                selectedFiles.push(e.target.value)
              }
              else {
                selectedFiles.splice(selectedFiles.indexOf(e.target.value), 1)
              }
              maybeShowBulkActions()
            })

          }

          $('#upload-file').on('click', e => {
            e.preventDefault()

            Groundhogg.components.fileUploader({
              action: 'groundhogg_contact_upload_file',
              nonce: '',
              beforeUpload: (fd) => fd.append('contact', contact.ID),
              onUpload: (json, file) => {
                // console.log( json )
                files = json.data.files
                mount()
              },
            })
          })

          if (!files.length) {
            ContactsStore.fetchFiles(contact.ID).then(_files => {
              files = _files
              mount()
            })
          }

          mount()

        },
      },
      {
        id: 'inbox',
        name: __('Inbox'),
        render: () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div class="inside" id="inbox-here">
                      <p>
                          ${ sprintf(
                                  __('Hi %s, we\'re still working on the inbox feature! We know how important this is for you, so our team is working around the clock to make it a reality!',
                                          'groundhogg'),
                                  Groundhogg.currentUser.data.display_name) }</p>
                      <p>
                          ${ __(
                                  'You can help us get there faster by giving us a <a target="_blank" href="https://wordpress.org/support/plugin/groundhogg/reviews/?filter=5">⭐⭐⭐⭐⭐ review!</a>') }</p>
                  </div>
              </div>`
        },
        onMount: () => {

          // get( `${ContactsStore.route}/${contact.ID}/inbox`).then( r => {
          //   console.log(r)
          // } )

        },
      },
    ]

    // if (Groundhogg.isWhiteLabeled) {
    tabs.splice(tabs.findIndex(t => t.id === 'inbox'), 1)
    // }

    const template = () => {
      // language=HTML
      return `
          <div id="secondary-tabs"><h2
                  class="no-margin nav-tab-wrapper secondary gh">
              ${ tabs.map(({
                  id,
                  name,
              }) => `<a href="#" data-tab="${ id }" class="nav-tab ${ activeTab ===
              id ? 'nav-tab-active' : '' }">${ name }</a>`).join('') }
          </h2>
              ${ tabs.find(t => t.id === activeTab).render() }
          </div>`
    }

    const mount = () => {

      $('#other-contact-stuff').html(template())
      onMount()
    }

    const onMount = () => {
      tabs.find(t => t.id === activeTab).onMount()

      $('.nav-tab-wrapper.secondary .nav-tab').on('click', (e) => {
        activeTab = e.target.dataset.tab
        mount()
      })
    }

    mount()

  }

  const handleFormSubmit = () => {

    $('#primary-form').on('submit', e => {
      e.preventDefault()

      const $btn = $('#save-primary')

      let { stop } = loadingDots('#save-primary')
      $btn.prop('disabled', true)

      let data = new FormData(e.currentTarget)

      data.append('action', 'groundhogg_edit_contact')
      data.append('contact', getContact().ID)

      ajax(data).then(r => {

        $('.gh-panel.contact-details .inside').replaceWith(r.data.details)

        ContactsStore.itemsFetched([r.data.contact])

        $btn.prop('disabled', false)
        stop()

        dialog({
          message: __('Changes saved!'),
        })

      })

    })

  }

  const managePrimaryTabs = () => {

    let activeTab = 'general'

    let customTabState = gh_contact_custom_properties || {
      tabs: [],
      groups: [],
      fields: [],
    }

    const __groups = () =>
      customTabState.groups.filter(g => g.tab === activeTab)

    const __fields = () => customTabState.fields.filter(
      f => __groups().find(g => g.id === f.group))

    let timeout
    let metaChanges = {}
    let deleteKeys = []

    const commitMetaChanges = () => {

      let { stop } = loadingDots('#save-meta')
      $('#save-meta').prop('disabled', true)

      Promise.all([
        ContactsStore.patchMeta(getContact().ID, metaChanges),
        deleteKeys.length ? ContactsStore.deleteMeta(getContact().ID,
          deleteKeys) : null,
      ]).then(() => {

        metaChanges = {}
        deleteKeys = []

        stop()
        mount()
        dialog({
          message: __('Changes saved!'),
        })
      })
    }

    const cancelMetaChanges = () => {
      metaChanges = {}
      deleteKeys = []

      mount()
    }

    const updateTabState = () => {

      if (timeout) {
        clearTimeout(timeout)
      }

      timeout = setTimeout(() => {
        patch(routes.v4.options, {
          gh_contact_custom_properties: customTabState,
        }).then(() => {
          dialog({
            message: __('Changes saved!', 'groundhogg'),
          })
        })
      }, 3000)

    }

    $(document).on('click', '.nav-tab-wrapper.primary a.nav-tab', (e) => {

      e.preventDefault()

      if (e.currentTarget.id === 'custom-tabs-menu') {

        moreMenu(e.currentTarget, {
          items: customTabState.tabs.map(t => ( {
            key: t.id,
            text: t.name,
          } )),
          onSelect: k => {
            activeTab = k
            $('.nav-tab-wrapper.primary .nav-tab').removeClass('nav-tab-active')
            mount()
          },
        })

        return
      }

      let $tab = $(e.currentTarget)

      $('.nav-tab-wrapper.primary .nav-tab').removeClass('nav-tab-active')
      $tab.addClass('nav-tab-active')

      activeTab = e.target.id

      mount()

    })

    const mount = () => {
      $('#primary-contact-stuff .edit-meta').remove()
      $('#primary-contact-stuff .custom-tab').remove()
      $('#primary-contact-stuff .tab-more').remove()

      $(`<a href="#" id="edit-meta" class="nav-tab edit-meta ${ 'edit-meta' ===
      activeTab ? ' nav-tab-active' : '' }">${ __('More', 'groundhogg') }</a>`).
        insertAfter('#general')

      if (customTabState.tabs.length <= 3) {
        $(customTabState.tabs.map(({
          id,
          name,
        }) => `<a href="#" id="${ id }" class="nav-tab custom-tab${ id ===
        activeTab ? ' nav-tab-active' : '' }">${ name }</a>`).join('')).
          insertAfter('#edit-meta')
      }
      else {
        $('#primary-contact-stuff #custom-tabs-menu').remove()
        $(`<a href="#" id="custom-tabs-menu" class="nav-tab"></a>`).
          insertAfter('#edit-meta')
        $(customTabState.tabs.filter(t => t.id === activeTab).
          map(({
            id,
            name,
          }) => `<a href="#" id="${ id }" class="nav-tab custom-tab${ id ===
          activeTab ? ' nav-tab-active' : '' } custom-tabs-menu">${ name }</a>`).
          join('')).insertAfter('#edit-meta')
        tooltip('#custom-tabs-menu', {
          content: __('Custom tabs'),
          position: 'top',
        })
      }

      onMount()
    }

    const onMount = () => {

      $('#primary-contact-stuff .tab-content-wrapper').removeClass('active')
      $(`#primary-contact-stuff [data-tab-content="${ activeTab }"]`).
        addClass('active')

      // If the current tab is a custom tab
      if (customTabState.tabs.find(t => t.id === activeTab)) {

        // language=HTML
        let customTabUi = `
            <div
                    class="tab-content-wrapper custom-tab gh-panel top-left-square active"
                    data-tab-content="${ activeTab }">
                <div class="inside">
                    <div id="custom-fields-here">
                    </div>
                    <p>
                        <button id="save-meta" class="gh-button primary">
                            ${ __('Save Changes') }
                        </button>
                        <button id="cancel-meta-changes"
                                class="gh-button danger text">${ __('Cancel') }
                        </button>
                    </p>
                </div>
            </div>`

        $(customTabUi).insertAfter('#primary-contact-stuff form')
        $(`<button class="gh-button tab-more secondary text icon">${ icons.verticalDots }</button>`).
          insertAfter('#add-tab')

        $('#save-meta').on('click', commitMetaChanges)
        $('#cancel-meta-changes').on('click', cancelMetaChanges)

        tooltip('.tab-more', {
          content: __('Tab Options', 'groundhogg'),
          position: 'right',
        })

        $('.tab-more').on('click', e => {
          e.preventDefault()

          moreMenu(e.currentTarget, {
            items: [
              {
                key: 'rename',
                cap: 'manage_options',
                text: __('Rename'),
              },
              {
                key: 'delete',
                cap: 'manage_options',
                text: `<span class="gh-text danger">${ __('Delete') }</span>`,
              },
            ],
            onSelect: k => {

              switch (k) {

                case 'delete':

                  dangerConfirmationModal({
                    confirmText: __('Delete'),
                    alert: `<p>${ sprintf(
                      __('Are you sure you want to delete %s?', 'groundhogg'),
                      bold(customTabState.tabs.find(
                        t => t.id === activeTab).name)) }</p>`,
                    onConfirm: () => {

                      let fields = __fields().map(f => f.id)

                      customTabState.fields = customTabState.fields.filter(
                        f => !fields.includes(f.id))
                      customTabState.groups = customTabState.groups.filter(
                        g => g.tab !== activeTab)
                      customTabState.tabs = customTabState.tabs.filter(
                        t => t.id !== activeTab)

                      updateTabState()
                      activeTab = 'general'
                      mount()
                    },
                  })

                  break

                case 'rename':
                  modal({
                    // language=HTML
                    content: `
                        <div>
                            <h2>${ __('Rename tab', 'groundhogg') }</h2>
                            <div class="align-left-space-between">
                                ${ input({
                                    id: 'tab-name',
                                    value: customTabState.tabs.find(
                                            t => t.id === activeTab).name,
                                    placeholder: __('Tab name', 'groundhogg'),
                                }) }
                                <button id="update-tab"
                                        class="gh-button primary">
                                    ${ __('Save') }
                                </button>
                            </div>
                        </div>`,
                    onOpen: ({ close }) => {

                      let tabName

                      $('#tab-name').on('change input', (e) => {
                        tabName = e.target.value
                      }).focus()

                      $('#update-tab').on('click', () => {

                        customTabState.tabs.find(
                          t => t.id === activeTab).name = tabName

                        updateTabState()

                        mount()
                        close()

                      })

                    },
                  })
                  break

              }

            },
          })
        })

        propertiesEditor('#custom-fields-here', {
          values: {
            ...getContact().meta,
            ...metaChanges,
          },
          properties: {
            groups: __groups(),
            fields: __fields(),
          },
          onPropertiesUpdated: ({ groups = [], fields = [] }) => {

            customTabState.fields = [
              // Filter out any fields that are part of any group belonging to
              // the current tab
              ...customTabState.fields.filter(
                field => !__fields().find(f => f.id === field.id)),
              // Any new fields
              ...fields,
            ]

            customTabState.groups = [
              // Filter out groups that are part of the current tab
              ...customTabState.groups.filter(
                group => !__groups().find(g => g.id === group.id)),
              // The groups that were edited and any new groups
              ...groups.map(g => ( { ...g, tab: activeTab } )),
            ]

            updateTabState()

          },
          onChange: (meta) => {
            metaChanges = {
              ...metaChanges,
              ...meta,
            }
          },
          canEdit: () => {
            return userHasCap('manage_options')
          },

        })

      }
      else if (activeTab === 'edit-meta') {

        let combinedMeta = {
          ...getContact().meta,
          ...metaChanges,
        }

        // language=HTML
        let metaUi = `
            <div
                    class="tab-content-wrapper edit-meta gh-panel top-left-square active"
                    data-tab-content="${ activeTab }">
                <div class="inside">
                    <h2>${ __('Additional Contact Methods', 'groundhogg') }</h2>
                    <p><b>${ __('Email Addresses', 'groundhogg') }</b></p>
                    <div id="contact-emails-here"></div>
                    <p><b>${ __('Phone Numbers', 'groundhogg') }</b></p>
                    <div id="contact-phones-here"></div>
                    <h2>${ __('Meta') }</h2>
                    <div id="meta-here">
                    </div>
                    <p>
                        <button id="save-meta" class="gh-button primary">
                            ${ __('Save Changes') }
                        </button>
                        <button id="cancel-meta-changes"
                                class="gh-button danger text">${ __('Cancel') }
                        </button>
                    </p>
                </div>
            </div>`

        $(metaUi).insertAfter('#primary-contact-stuff form')
        $('#cancel-meta-changes').on('click', cancelMetaChanges)

        let { alternate_emails = [], alternate_phones = [] } = getContact().meta

        inputRepeaterWidget({
          selector: '#contact-phones-here',
          rows: alternate_phones,
          cellProps: [
            {
              type: 'email',
              options: {
                mobile: __('Mobile'),
                home: __('Home'),
                work: __('Work'),
              },
            }, {
              type: 'tel',
              placeholder: __('(123) 456-7890'),
            },
          ],
          cellCallbacks: [select, input],
          onMount: () => {
          },
          onChange: (rows) => {
            metaChanges.alternate_phones = rows
          },
        }).mount()

        inputRepeaterWidget({
          selector: '#contact-emails-here',
          rows: alternate_emails.map(e => [e]),
          cellProps: [
            {
              type: 'email',
              className: 'alternate-email-address',
              placeholder: __('john.doe@example.com', 'groundhogg'),
            },
          ],
          cellCallbacks: [input],
          onMount: () => {
          },
          onChange: (rows) => {
            metaChanges.alternate_emails = rows.map(r => r[0])
          },
        }).mount()

        inputRepeater('#meta-here', {
          rows: Object.keys(combinedMeta).
            filter(k => !meta_exclusions.includes(k)).
            map(k => ( [k, combinedMeta[k]] )),
          cells: [
            (props) => input({
              ...props,
              readonly: !!props.value,
              className: 'meta-key',
            }),
            ({ value, ...props }) => input({
              value: ['array', 'object'].includes(typeof value)
                ? 'SERIALIZED DATA'
                : value,
              readonly: ['array', 'object'].includes(typeof value),
              ...props,
            }),
          ],
          onMount: () => {
            $('.meta-key').on('change', (e) => {
              let key = sanitizeKey(e.target.value)
              $(e.target).val(key)
            })
          },
          onChange: (rows) => {
            rows.forEach(([key, value]) => {

              if (!key) {
                return
              }

              metaChanges[key] = value
            })
          },
          onRemove: ([key, value]) => {

            if (!key) {
              return
            }

            deleteKeys.push(key)
            delete metaChanges[key]
          },
        }).mount()

        $('#save-meta').on('click', commitMetaChanges)
      }

    }

    if (userHasCap('manage_options')) {
      $('.nav-tab-wrapper.primary').append(
        `<div id="tab-actions" class="space-between"><button type="button" id="add-tab"><span class="dashicons dashicons-plus-alt2"></span></button></div>`)
      $('#add-tab').on('click', (e) => {
        e.preventDefault()

        modal({
          // language=HTML
          content: `
              <div>
                  <h2>${ __('Add a new tab', 'groundhogg') }</h2>
                  <div class="align-left-space-between">
                      ${ input({
                          id: 'tab-name',
                          placeholder: __('Tab name', 'groundhogg'),
                      }) }
                      <button id="create-tab" class="gh-button primary">
                          ${ __('Create') }
                      </button>
                  </div>
              </div>`,
          onOpen: ({ close }) => {

            let tabName

            $('#tab-name').on('change input', (e) => {
              tabName = e.target.value
            }).focus()

            $('#create-tab').on('click', () => {

              let id = uuid()

              customTabState.tabs.push({
                id,
                name: tabName,
              })

              activeTab = id
              updateTabState()

              mount()
              close()

            })

          },
        })

      })

      tooltip('#add-tab', {
        content: __('Add tab', 'groundhogg'),
        position: 'right',
      })

    }

    mount()
  }

  const manageTags = () => {

    let removeTags = []
    let addTags = []

    $('.tags-panel').on('click', '.handlediv', (e) => {
      $('.tags-panel').toggleClass('closed')
    })

    const template = () => {
      // language=HTML
      return `
          <div id="gh-better-tag-picker">
          </div>
          <div class="tag-change-actions" style="margin-top: 10px">
              <button id="cancel-tag-changes" class="gh-button danger text">
                  ${ __('Cancel') }
              </button>
              <button id="save-tag-changes" class="gh-button primary">
                  ${ __('Save') }
              </button>
          </div>`
    }

    const maybeShowTagChangeActions = () => {
      if (removeTags.length || addTags.length) {
        $('.tag-change-actions').addClass('align-right-space-between')
      }
      else {
        $('.tag-change-actions').removeClass('align-right-space-between')
      }
    }

    const mount = () => {
      $('#tags-here').html(template())
      onMount()
    }

    const onMount = () => {

      betterTagPicker('#gh-better-tag-picker', {
        selected: getContact().tags,
        onChange: ({ addTags: _addTags, removeTags: _removeTags }) => {
          removeTags = _removeTags
          addTags = _addTags
          maybeShowTagChangeActions()
        },
      })

      $('#save-tag-changes').on('click', () => {
        ContactsStore.patch(getContact().ID, {
          remove_tags: removeTags,
          add_tags: addTags,
        }).then(() => {
          dialog({
            message: __('Changes saved!'),
          })
          addTags = []
          removeTags = []
          mount()
        })
      })

      $('#cancel-tag-changes').on('click', () => {
        addTags = []
        removeTags = []
        mount()
      })

    }

    TagsStore.itemsFetched(getContact().tags)

    mount()

  }

  $.extend(editor, {

    init () {

      handleFormSubmit()
      contactMoreActions()
      manageTags()
      managePrimaryTabs()
      otherContactStuff()

      $('#send-email').on('click', e => {
        e.preventDefault()
        sendEmail()
      })

      $('#primary-contact-stuff .toggle-indicator').on('click', e => {
        $(e.target).closest('.gh-panel').toggleClass('closed')
      })

      if (window.location.href.match(/send_email=true/)) {
        sendEmail()
      }
    },
  })

  const { email_log: LogsStore } = Groundhogg.stores
  const { EmailLogModal, EmailPreviewModal } = Groundhogg.components

  // Handle log items
  $(document).on('click', 'a.view-event-email-log-item', async e => {

    let eventId = parseInt($(e.currentTarget).data('event-id'))

    let event = EventsStore.get(eventId)

    let { close } = loadingModal()

    try {

      let logItems = await LogsStore.fetchItems({
        queued_event_id: event.data.queued_id,
        limit: 1
      })

      EmailLogModal(logItems[0])

      close()

    }
    catch (err) {

      close()

      if ( event.data.email_id ){
        try {
          await EmailPreviewModal( event.data.email_id, {} )
          return
        } catch ( err2 ) {
          // Silence
        }
      }

      dialog({
        message: err.message,
        type: 'error',
        ttl: 5000
      })

    }

  })

  // Handle log items
  $(document).on('click', 'a.view-composed-email-log-item', async e => {

    e.preventDefault()

    let activityId = parseInt($(e.currentTarget).data('activity-id'))

    let activity = ActivityStore.get(activityId)

    let { close } = loadingModal()

    try {

      let logItems = await LogsStore.fetchItems({
        subject: activity.meta.subject,
        recipients: ['RLIKE', getContact().data.email],
        date_sent: moment.unix(activity.data.timestamp).utc().format("YYYY-MM-DD hh:mm:ss"),
        limit: 1
      })

      EmailLogModal(logItems[0])

    }
    catch (err) {

      dialog({
        message: err.message,
        type: 'error',
        ttl: 5000
      })

    }

    close()

  })

  $(function () {
    editor.init()
  })

  Groundhogg.ActivityTimeline = ActivityTimeline
  Groundhogg.ContactActions = ContactActions

} )(jQuery, ContactEditor)
