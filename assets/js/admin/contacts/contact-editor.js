( ($, editor) => {

  const {
    contact,
    meta_exclusions,
    unsubReasons,
  } = editor

  const { gh_contact_custom_properties } = Groundhogg.filters

  const {
    tooltip,
    regexp,
    inputRepeaterWidget,
    inputRepeater,
    el,
    input,
    select,
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
    escHTML,
    sanitizeHTML,
    safeURL,
    dialog,
    skeleton,
  } = Groundhogg.element

  // titles from the flow editor / emails may carry basic inline formatting - allow only that.
  // Cached because the same handful of titles render across many timeline rows.
  const INLINE_TITLE_TAGS = { b: [], strong: [], u: [], i: [], em: [], code: [] }
  const titleHTMLCache = new Map()
  const titleHTML = str => {
    str = String(str ?? '')
    if (!titleHTMLCache.has(str)) {
      titleHTMLCache.set(str, sanitizeHTML(str, INLINE_TITLE_TAGS))
    }
    return titleHTMLCache.get(str)
  }

  const {
    currentUser,
    filters,
    propertiesEditor,
    isWPFusionActive,
  } = Groundhogg
  const { userHasCap } = Groundhogg.user
  const {
    events     : EventsStore,
    event_queue: EventQueue,
    tags       : TagsStore,
    contacts   : ContactsStore,
    emails     : EmailsStore,
    activity   : ActivityStore,
    funnels    : FunnelsStore,
    broadcasts : BroadcastsStore,
    page_visits: PageVisitsStore,
    submissions: SubmissionsStore,
  } = Groundhogg.stores

  const {
    post,
    delete: _delete,
    get,
    patch,
    routes,
    ajax,
  } = Groundhogg.api

  const {
    selectContactModal,
    betterTagPicker,
    internalForm,
    EmailLogModal,
    Relationships,
    EmailPreviewModal,
    ContactListItem,
  } = Groundhogg.components

  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n

  const { dateI18n, isInTheFuture } = wp.date
  const wpDateFormats = ( wp.date.getSettings ?? wp.date.__experimentalGetSettings )().formats
  // site time format, but tighten "10:58 am" -> "10:58am" to save gutter space (24h formats unaffected)
  const compactTimeFormat = wpDateFormats.time.replace(/\s+(\\?[aA])/g, '$1')

  ContactsStore.itemsFetched([contact])

  let files = []

  // whether the single /timeline request has run this session (it also hydrates the funnel/email stores)
  let timelineHydrated = false
  // steps referenced by timeline events, hydrated separately from the events (kept across tab switches)
  const timelineStepsById = {}

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
    $('#refresh-timeline').click()
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
      to        : [contact.data.email],
      from_email: currentUser.from_email,
      from_name : currentUser.from_name,
    }

    if (contact.data.owner_id && currentUser.ID != contact.data.owner_id) {
      email.cc = [
        filters.owners.find(u => u.ID == contact.data.owner_id).data.user_email,
      ]
    }

    Groundhogg.components.emailModal( email, activityUpdated )
  }

  const strings = {

    /* translators: %s: a step name */
    pending: __( 'Pending — %s', 'groundhogg'),
    /* translators: %s: a step name */
    failed: __( 'Failed — %s', 'groundhogg'),
    /* translators: %s: the broadcast title */
    will_receive_broadcast: __( 'Will receive broadcast: %s', 'groundhogg'),
    /* translators: %s: the broadcast title */
    received_broadcast: __( 'Received broadcast: %s', 'groundhogg'),
    /* translators: %s: the broadcast title */
    failed_broadcast: __( 'Failed to send broadcast: %s', 'groundhogg'),
    /* translators: %s: the email title */
    will_receive_email: __( 'Will receive email: %s', 'groundhogg'),
    /* translators: %s: the email title */
    received_email: __( 'Received email: %s', 'groundhogg'),
    /* translators: %s: the email title */
    failed_email: __( 'Failed to send email: %s', 'groundhogg'),

  }

  const ContactActions = [
    {
      id     : 'send-email',
      icon   : icons.email,
      tooltip: __('Send Email', 'groundhogg'),
      show   : contact => true,
      onClick: e => {
        moreMenu(e.currentTarget, {
          items   : [
            {
              key : 'compose',
              text: __('Compose', 'groundhogg'),
            },
            {
              key : 'template',
              text: __('Use template', 'groundhogg'),
            },
          ],
          onSelect: k => {
            switch (k) {

              case 'compose':
                sendEmail()
                break
              case 'template':
                Groundhogg.components.EmailTemplateModal(getContact().ID, activityUpdated)
                break

            }
          },
        })
      },
    },
    {
      id     : 'call-primary',
      icon   : icons.phone,
      tooltip: __('Call primary phone', 'groundhogg'),
      show   : contact => contact.meta.primary_phone,
      onClick: e => {
        window.open(`tel:${ contact.meta.primary_phone }`)
      },
    },
    {
      id     : 'call-mobile',
      icon   : icons.smartphone,
      tooltip: __('Call mobile', 'groundhogg'),
      show   : contact => contact.meta.mobile_phone,
      onClick: e => {
        window.open(`tel:${ contact.meta.mobile_phone }`)
      },
    },
    {
      id     : 'add-to-funnel',
      icon   : icons.funnel,
      tooltip: __('Add to a flow', 'groundhogg'),
      show   : contact => true,
      onClick: e => {

        const State = Groundhogg.createState({})

        MakeEl.Modal({}, ({
          morph,
          close,
        }) => MakeEl.Div({
          id       : 'add-to-funnel-dialog',
          className: 'display-flex gap-10 column',
        }, [
          /* translators: %s: the name of a contact */
          `<h3 style="margin: 0">${ sprintf(__('Add %s to a flow', 'groundhogg'), getContact().data.full_name) }</h3>`,
          MakeEl.ItemPicker({
            id          : `select-a-funnel`,
            noneSelected: __('Select a flow...', 'groundhogg'),
            selected    : State.funnel_id ? {
              id  : State.funnel_id,
              text: FunnelsStore.get(State.funnel_id).data.title,
            } : [],
            multiple    : false,
            clearable   : false,
            style       : {
              flexGrow: 1,
            },
            fetchOptions: (search) => {
              return FunnelsStore.fetchItems({
                  search,
                  status: 'active',
                }).
                then(funnels => funnels.map(({
                  ID,
                  data,
                }) => ( {
                  id  : ID,
                  text: data.title,
                } )))
            },
            onChange    : item => {
              State.set({
                funnel_id: item.id,
                step_id  : FunnelsStore.get(item.id).steps[0].ID,
              })
              morph()
            },
          }),
          State.funnel_id ? MakeEl.ItemPicker({
            id          : `select-step-from-${ State.funnel_id }`,
            noneSelected: __('Select a step...', 'groundhogg'),
            clearable   : false,
            selected    : State.step_id ? {
              id  : State.step_id,
              text: FunnelsStore.get(State.funnel_id).steps.find(s => s.ID === State.step_id).data.step_title,
            } : [],
            multiple    : false,
            style       : {
              flexGrow: 1,
            },
            fetchOptions: async (search) => FunnelsStore.get(State.funnel_id).
              steps.
              map(({
                ID,
                data,
              }) => ( {
                id  : ID,
                text: data.step_title,
              } )).
              filter(opt => opt.text.match(new RegExp(search, 'i'))),
            onChange    : item => {
              State.set({
                step_id: item.id,
              })
              morph()

            },
          }) : null,
          State.funnel_id && State.step_id ? MakeEl.Button({
            id       : 'add-to-funnel',
            className: 'gh-button primary medium',
            onClick  : e => {

              e.currentTarget.disabled = true
              e.currentTarget.innerHTML = `<span class="gh-spinner"></span>`

              FunnelsStore.addContacts({
                funnel_id : State.funnel_id,
                step_id   : State.step_id,
                contact_id: getContact().ID,
              }).then(() => {

                dialog({
                  message: sprintf(
                    /* translators: %s: the name of a contact */
                    __('%s added to flow!', 'groundhogg'),
                    getContact().data.full_name),
                })

                activityUpdated()

                close()

              }).catch(err => {

                dialog({
                  type   : 'error',
                  message: err.message,
                })

                morph()
              })

            },
          }, sprintf(
            /* translators: %s: the name of a flow */
            __('Add to %s now!', 'groundhogg'),
            bold(FunnelsStore.get(State.funnel_id).data.title))) : null,
        ]))
      },
    },
    {
      id     : 'internal-form',
      icon   : icons.form,
      tooltip: __('Submit an internal form', 'groundhogg'),
      show   : contact => true,
      onClick: e => {
        internalForm({
          contact : getContact(),
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

    ContactActions.forEach(({
      id,
      tooltip: __tp,
      onClick,
    }) => {

      $(`#action-${ id }`).on('click', e => onClick(e, getContact()))

      tooltip(`#action-${ id }`, {
        content: __tp,
      })

    })

    $('#contact-more').on('click', e => moreMenu(e.target, {
      items   : [
        {
          key : 'merge',
          cap : 'delete_contacts',
          text: __('Merge'),
        },
        {
          key : 'delete',
          cap : 'delete_contacts',
          text: `<span class="gh-text danger">${ __('Delete') }</span>`,
        },
      ].filter(i => userHasCap(i.cap)),
      onSelect: k => {
        switch (k) {
          case 'merge':

            selectContactModal({
              exclude : [contact.ID],
              onSelect: (_contact) => {

                confirmationModal({
                  confirmText: __('Merge'),
                  width      : 500,
                  // language=HTML
                  alert    : `<p>
                      ${ sprintf(
                              /* translators: 1: the name of a contact, 2: the name of another contact */
                              __('Are you sure you want to merge %1$s with %2$s? This action cannot be undone.', 'groundhogg'),
                              bold(_contact.data.full_name),
                              bold(getContact().data.full_name)) }</p>
                  <p>
                      <a href="https://groundhogg.io/doc/merging-contacts/"
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
              confirmText: __('Delete', 'groundhogg' ),
              alert      : `<p>${ sprintf(
                /* translators: %s: the name of an asset (contacts, flows, emails, etc...) */
                __('Are you sure you want to delete %s?', 'groundhogg'),
                bold(getContact().data.full_name)) }</p>`,
              onConfirm  : () => {
                ContactsStore.delete(contact.ID).then(() => {
                  dialog({
                    /* translators: %s: the name of an asset (contacts, flows, emails, etc...) */
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

  const {
    Div,
    Span,
    An,
    Ul,
    Li,
    Button,
    Fragment,
    H2,
    Table,
    TBody,
    Tr,
    Td,
    Dashicon,
    makeEl,
  } = MakeEl

  /**
   * A collapsible panel of key => value details, rendered with MakeEl.
   *
   * @param details object or array of { label, value }
   * @param opts
   * @return {HTMLElement|null}
   */
  // coerce an arbitrary detail value to a safe, displayable string
  const stringifyDetail = v => escHTML(
    v === null || v === undefined
    ? ''
    : typeof v === 'object' ? JSON.stringify(v) : String(v),
  )

  const ActivityDetails = (details, {
    // labels may legitimately arrive wrapped in <code> (server-side code_it()) or other basic
    // inline formatting - keep that, strip anything hostile
    key = k => titleHTML(String(k ?? '')),
    value = stringifyDetail,
    heading = __('Details'),
    open = false,
    className = '',
  } = {}) => {

    // object provided, parse to label => value
    if (!Array.isArray(details)) {

      let parsed = []
      Object.keys(details).forEach((k) => {
        parsed.push(( {
          label: k,
          value: details[k],
        } ))
      })
      details = parsed

    }

    if (details.length === 0) {
      return null
    }

    return Div({
      className: `gh-panel outlined ${ open ? '' : 'closed' } activity-details overflow-hidden ${ className }`.trim(),
      style    : { marginTop: '5px' },
    }, [
      Div({
        className: 'gh-panel-header',
      }, [
        H2({}, heading),
        Button({
          type        : 'button',
          className   : 'toggle-indicator',
          ariaExpanded: 'false',
          onClick     : e => {
            e.currentTarget.closest('.gh-panel').classList.toggle('closed')
          },
        }),
      ]),
      Div({
        className: 'inside',
        style    : { padding: 0 },
      }, [
        Table({
          className: 'wp-list-table widefat striped',
          style    : { border: 'none' },
        }, [
          TBody({}, details.map(({
            label,
            value: val,
          }) => Tr({}, [
            Td({}, `${ key(label) }`),
            Td({}, `${ value(val) }`),
          ]))),
        ]),
      ]),
    ])
  }

  const stepTypeIcon = (type) => {

    const {
      svg,
      icon,
      name,
    } = Groundhogg.rawStepTypes[type]

    if (svg) {
      return svg
    }

    return `<img class="step-icon" src="${ icon }" alt="${ name }"/>`

  }

  /**
   * Open the email log (or a preview fallback) for a completed event.
   *
   * @param eventId
   */
  const openEventEmailLog = async (eventId) => {

    const event = EventsStore.get(eventId)
    const { email_log: LogsStore } = Groundhogg.stores

    let { close } = loadingModal()

    try {

      if (!parseInt(event.data.queued_id)) {
        throw new Error('Invalid queued event ID')
      }

      let logItems = await LogsStore.fetchItems({
        queued_event_id: event.data.queued_id,
        limit          : 1,
      })

      EmailLogModal(logItems[0])
      close()
    }
    catch (err) {

      close()

      if (event.data.email_id) {
        try {
          await EmailPreviewModal(event.data.email_id, {})
          return
        }
        catch (err2) {
          // Silence
        }
      }

      dialog({
        message: err.message,
        type   : 'error',
        ttl    : 5000,
      })
    }
  }

  const ActivityTimeline = {

    hiddenActivity: [
      'thread_reply',
    ],

    addType (type, opts) {
      this.types[type] = {
        icon   : '',
        render : () => '',
        preload: () => {},
        ...opts,
      }
    },

    types: {
      unsubscribed       : {
        icon  : icons.brokenHeart,
        render: ({ meta }) => {
          let {
            reason = '',
            feedback = '',
          } = meta

          let parts = [
            __('Unsubscribed', 'groundhogg'),
          ]

          if (reason.length) {
            parts.push(`; ${ bold(escHTML(unsubReasons[reason] ?? reason)) }`)
          }

          if (feedback.length) {
            parts.push(`<div class="contact-input">"${ escHTML(feedback) }"</div>`)
          }

          return parts.join('')
        },
      },
      bounce             : {
        icon  : icons.bounce,
        render: ({
          meta,
        }) => {

          return Fragment([
            __('Email <b>bounced</b>', 'groundhogg'),
            ActivityDetails(meta),
          ])
        },
      },
      soft_bounce        : {
        icon  : icons.bounce,
        render: ({
          meta,
        }) => {

          return Fragment([
            __('Email <b>soft bounced</b>', 'groundhogg'),
            ActivityDetails(meta),
          ])
        },
      },
      complaint          : {
        icon  : icons.spam,
        render: ({
          meta,
        }) => {

          return Fragment([
            __('Marked email as <b>spam</b>', 'groundhogg'),
            ActivityDetails(meta),
          ])
        },
      },
      wp_fusion          : {
        icon      : icons.wp_fusion,
        iconFramed: false,
        render    : ({ meta }) => {
          const {
            event_name,
            event_value,
          } = meta
          return `${ escHTML(event_name) }: <code>${ escHTML(event_value) }</code>`
        },
        preload   : () => {},
      },
      wp_login           : {
        icon   : icons.login,
        render : () => {
          return __('Logged in', 'groundhogg')
        },
        preload: () => {},
      },
      wp_logout          : {
        icon   : icons.logout,
        render : () => {
          return __('Logged out', 'groundhogg')
        },
        preload: () => {},
      },
      composed_email_sent: {
        icon   : icons.open_email,
        render : ({
          meta,
          i18n,
          ID,
        }) => {
          /* translators: sentby: the name of a user, subject: the subject line of an email */
          return sprintf(__('%(sentby)s sent an email with subject %(subject)s', 'groundhogg'), {
            sentby: bold(escHTML(i18n.sent_by)),
            subject: `<a href="#" class="view-composed-email-log-item" data-activity-id="${ escHTML(String(ID)) }">${ bold(escHTML(meta.subject)) }</a>`
          })
        },
        preload: () => {},
      },
      email_opened       : {
        icon  : icons.open_email,
        render: ({ data }) => {
          /* translators: %s: the title of an email */
          return sprintf(__('Opened %s', 'groundhogg'), el('a', {
            href: parseInt(data.funnel_id) === 1
                  ? adminPageURL('gh_reporting', {
                tab      : 'broadcasts',
                broadcast: data.step_id,
              })
                  : adminPageURL('gh_reporting', {
                tab : 'funnels',
                step: data.step_id,
              }),
          }, bold(titleHTML(EmailsStore.get(data.email_id).data.title))))
        },
      },
      email_link_click   : {
        icon  : icons.link_click,
        render: ({ data }) => {

          const link = data.referer || ''

          /* translators: 1: the link clicked, 2: the title of an email */
          return sprintf(__('Clicked %1$s in %2$s', 'groundhogg'), el('a', {
              target   : '_blank',
              href     : safeURL(link),
              className: 'clicked-link',
            }, bold(escHTML(link))),
            el('a', {
              href: parseInt(data.funnel_id) === 1
                    ? adminPageURL('gh_reporting', {
                  tab      : 'broadcasts',
                  broadcast: data.step_id,
                })
                    : adminPageURL('gh_reporting', {
                  tab : 'funnels',
                  step: data.step_id,
                }),
            }, bold(titleHTML(EmailsStore.get(data.email_id).data.title)))
          )
        },
      },
      imported           : {
        icon  : '<span class="dashicons dashicons-upload"></span>',
        render: ({ i18n }) => {
          /* translators: user: the name of a user, file: the name of a file */
          return sprintf(__('Imported by %(user)s from %(file)s', 'groundhogg'), { user: bold(escHTML(i18n.by)), file: bold(escHTML(i18n.file)) } )
        },
      },
      funnel_conversion  : {
        icon  : '<span class="dashicons dashicons-flag"></span>',
        render: ({ data }) => {

          let funnelTitle = bold(titleHTML(FunnelsStore.get(data.funnel_id).data.title))
          let link = el('a', {
            href: adminPageURL('gh_funnels', {
              action: 'edit',
              funnel: data.funnel_id,
            }, data.step_id),
          }, funnelTitle)

          /* translators: %s: the name of a flow */
          return sprintf(__('Converted in %s', 'groundhogg'), link)
        },
      },
      fallback           : {
        icon  : icons.heartbeat,
        render: ({
          data,
          meta,
        }) => {

          let html = [
            `Tracked <code>${ escHTML(data.activity_type) }</code>`,
          ]

          if (data.value) {
            html.push(` for <b>${ escHTML(data.value) }</b>`)
          }

          html.push(ActivityDetails(meta, {
            key  : k => `<code>${ escHTML(String(k ?? '')) }</code>`,
            value: v => escHTML(JSON.stringify(v)),
          }))

          return Fragment(html)
        },
      },
    },

    renderActivity (activity) {

      if (activity.type === 'submission') {

        let heading = {
          form            : __('Submission', 'groundhogg'),
          webhook         : __('Request', 'groundhogg'),
          webhook_response: __('Response', 'groundhogg'),
          api             : __('Request', 'groundhogg'),
          import          : __('Data', 'groundhogg'),
        }[activity.data.type] ?? __('Data', 'groundhogg')

        let funnel = activity.form
                     ? FunnelsStore.get(activity.form.data.funnel_id)
                     : null

        const flowLink = () => el('a', {
          href  : funnel.admin + `#${ activity.data.step_id }`,
          target: '_blank',
        }, bold(titleHTML(funnel.data.title)))

        let icon = icons.contact
        let before

        switch (activity.data.type) {
          case 'form':
            icon = icons.form
            /* translators: 1: the form name, 2: the flow name */
            before = sprintf(__('Submitted form %1$s in flow %2$s', 'groundhogg'),
              bold(titleHTML(activity.form.data.step_title)), flowLink())
            break
          case 'webhook':
            icon = icons.webhook
            /* translators: 1: the step name, 2: the flow name */
            before = sprintf(__('Received request to %1$s in flow %2$s', 'groundhogg'),
              bold(titleHTML(activity.form.data.step_title)), flowLink())
            break
          case 'webhook_response':
            icon = icons.webhook
            /* translators: 1: the step name, 2: the flow name */
            before = sprintf(__('Received response from %1$s in flow %2$s', 'groundhogg'),
              bold(titleHTML(activity.form.data.step_title)), flowLink())
            break
          case 'api':
            icon = icons.api
            before = __('Contact updated via REST API.', 'groundhogg')
            break
          case 'import':
            icon = '<span class="dashicons dashicons-upload"></span>'
            /* translators: %s: the name of a file */
            before = sprintf(__('Contact imported from %s.', 'groundhogg'), bold(escHTML(activity.data.name)))
            break
          default:
            if (activity.form) {
              icon = stepTypeIcon(activity.form.data.step_type)
              heading = __('Submission', 'groundhogg')
              /* translators: 1: the form name, 2: the flow name */
              before = sprintf(__('Submitted %1$s in flow %2$s', 'groundhogg'),
                bold(escHTML(activity.data.name)), flowLink())
            }
            else {
              /* translators: %s: a form name */
              before = sprintf(__('Contact updated by %s', 'groundhogg'), bold(escHTML(activity.data.name)))
            }
        }

        return this.ActivityItem({
          id       : `ti-sub-${ activity.ID }`,
          iconClass: 'submission',
          icon,
          body     : before,
          details  : ActivityDetails(activity.i18n.answers, { heading }),
          diffTime : activity.i18n.diff_time,
        })
      }

      if (activity.type === 'page_visit') {
        return this.ActivityItem({
          id       : `ti-pv-${ activity.ID }`,
          iconClass: 'page-visit',
          icon     : icons.link_click,
          body     : sprintf(
            /* translators: %s: a url/path */
            __('Visited %s', 'groundhogg'),
            `<a href="${ escHTML(safeURL(activity.data.path)) }" class="clicked-link" target="_blank">${ escHTML(activity.data.path) }</a>`),
          diffTime : activity.i18n.diff_time,
          ymdhis   : activity.i18n.ymdhis,
        })
      }

      if (activity.type === 'event') {
        return this.renderEvent(activity)
      }

      if (this.hiddenActivity.includes(activity.data.activity_type)) {
        return null
      }

      const type = this.types.hasOwnProperty(activity.data.activity_type)
                   ? this.types[activity.data.activity_type]
                   : this.types.fallback

      return this.ActivityItem({
        id        : `ti-act-${ activity.ID }`,
        className : `${ activity.data.activity_type } activity`,
        tabindex  : 0,
        iconClass : activity.data.activity_type,
        iconFramed: type.iconFramed,
        icon      : maybeCall(type.icon, activity),
        body      : type.render(activity),
        diffTime  : activity.i18n.diff_time,
        ymdhis    : activity.i18n.ymdhis,
        children  : activity.children,
      })
    },

    renderEvent (activity) {

      let {
        step,
        pending = false,
      } = activity

      let failed = !pending && activity.data.status === 'failed'

      const emailLogLink = (display) => el('a', {
        href       : '#',
        className  : 'view-event-email-log-item',
        dataEventId: activity.ID,
      }, display)

      // the failure reason, shown in a details card
      const errorCard = () => {

        if (!failed) {
          return null
        }

        let rows = {}

        if (activity.data.error_code) {
          rows[__('Code', 'groundhogg')] = `<code>${ escHTML(activity.data.error_code) }</code>`
        }
        if (activity.data.error_message) {
          rows[__('Message', 'groundhogg')] = escHTML(activity.data.error_message)
        }

        if (!Object.keys(rows).length) {
          return null
        }

        // rows are already escaped / intentionally wrapped in <code>
        return ActivityDetails(rows, {
          heading  : __('Error', 'groundhogg'),
          open     : true,
          value    : v => v,
          className: 'activity-details--error',
        })
      }

      const rowId = `ti-${ pending ? 'qe' : 'ev' }-${ activity.ID }`

      switch (parseInt(activity.data.event_type)) {
        case 1: {

          if (!step) {
            return null
          }

          let grouped = !!activity.grouped
          let funnel = FunnelsStore.get(step.data.funnel_id)

          if (!funnel && !grouped) {
            return null
          }

          // the flow editor's generated title, kses'd server-side to basic inline formatting
          let title = titleHTML(step.data.step_title)

          // completed -> just the step title; pending / failed -> "Pending — title" (not bold)
          let label = pending
                      ? sprintf(strings.pending, title)
                      : failed
                        ? sprintf(strings.failed, title)
                        : title

          // link into the flow editor at this step, revealed on hover
          let editorLink = funnel
                           ? el('a', {
                             href     : funnel.admin + '#' + activity.data.step_id,
                             className: 'open-in-editor',
                             target   : '_blank',
                             title    : __('Open in flow editor', 'groundhogg'),
                           }, '<span class="dashicons dashicons-external"></span>')
                           : ''

          let stepTypeName = el('span', {
            className: [ 'step-type', step.data.step_group ].join(' '),
          }, Groundhogg.rawStepTypes[step.data.step_type].name)

          return this.ActivityItem({
            id       : rowId,
            iconClass: `${ step.data.step_group } ${ pending ? 'pending' : '' } ${ failed ? 'failed' : '' }`,
            icon     : pending ? icons.hourglass : stepTypeIcon(step.data.step_type),
            body     : `<span>${ label }</span>`,
            details  : errorCard(),
            // inside a flow group the flow is already named by the group header
            extra    : grouped
                       ? null
                       : sprintf(
                         /* translators: 1: the step type, 2: the flow name */
                         __('%1$s in flow %2$s', 'groundhogg'),
                         stepTypeName,
                         el('a', {
                           href: funnel.admin + '#' + activity.data.step_id,
                         }, titleHTML(funnel.data.title))),
            diffTime : activity.i18n.diff_time,
            ymdhis   : activity.i18n.ymdhis,
            actions  : [ ...this.eventActions(activity), editorLink ].filter(Boolean),
            children : activity.children,
            childDay : this.dayKey(activity.time),
          })
        }
        case 2: {

          if (!activity.broadcast) {
            return null
          }

          let objectTitleDisplay = bold(titleHTML(activity.broadcast.object?.data?.title || __('Broadcast', 'groundhogg')))

          if (!pending && activity.broadcast.data.object_type === 'email') {
            objectTitleDisplay = emailLogLink(objectTitleDisplay)
          }

          let verb = pending
                     ? strings.will_receive_broadcast
                     : failed ? strings.failed_broadcast : strings.received_broadcast

          return this.ActivityItem({
            id       : rowId,
            iconClass: `broadcast ${ failed ? 'failed' : '' }`,
            icon     : icons.megaphone,
            body     : `<span>${ sprintf(verb, objectTitleDisplay) }</span>`,
            details  : errorCard(),
            diffTime : activity.i18n.diff_time,
            ymdhis   : activity.i18n.ymdhis,
            actions  : this.eventActions(activity),
            children : activity.children,
            childDay : this.dayKey(activity.time),
          })
        }
        case 3: {

          let email = activity.email?.email || EmailsStore.get(activity.data.email_id)
          let emailTitleDisplay = bold(titleHTML(email ? email.data.title : __('Email', 'groundhogg')))

          if (!pending) {
            emailTitleDisplay = emailLogLink(emailTitleDisplay)
          }

          let verb = pending
                     ? strings.will_receive_email
                     : failed ? strings.failed_email : strings.received_email

          return this.ActivityItem({
            id       : rowId,
            iconClass: `broadcast ${ failed ? 'failed' : '' }`,
            icon     : icons.email,
            body     : `<span>${ sprintf(verb, emailTitleDisplay) }</span>`,
            details  : errorCard(),
            diffTime : activity.i18n.diff_time,
            ymdhis   : activity.i18n.ymdhis,
            actions  : this.eventActions(activity),
            children : activity.children,
            childDay : this.dayKey(activity.time),
          })
        }
      }

      return null
    },

    /**
     * Inline event actions, shown on hover after the timestamp.
     *
     * @return {Array}
     */
    eventActions (activity) {

      const action = (props, text) => An({
        className: 'activity-action',
        ...props,
      }, text)

      if (activity.pending) {

        const queueItem = () => EventQueue.get(activity.ID)

        return [
          action({
            onClick: e => {
              patch(`${ EventQueue.route }/${ queueItem().ID }/execute`).then(() => {
                dialog({ message: __('Event rescheduled', 'groundhogg') })
                this.needsRefresh()
              })
            },
          }, __('Run now', 'groundhogg')),
          action({
            className: 'activity-action danger',
            onClick  : e => {
              let event = queueItem()
              patch(`${ EventQueue.route }/${ event.ID }/cancel`).then(() => {
                EventQueue.items.splice(EventQueue.items.findIndex(i => i.ID === event.ID), 1)
                dialog({ message: __('Event cancelled', 'groundhogg') })
                this.needsRefresh()
              })
            },
          }, __('Cancel', 'groundhogg')),
        ]
      }

      let actions = []

      if (activity.step && [
        'admin_notification',
        'send_email',
      ].includes(activity.step.data.step_type)) {
        actions.push(action({
          onClick: e => openEventEmailLog(activity.ID),
        }, __('Email log', 'groundhogg')))
      }

      actions.push(action({
        onClick: e => {
          patch(`${ EventsStore.route }/${ activity.ID }/execute`).then(() => {
            dialog({ message: __('Event rescheduled', 'groundhogg') })
            this.needsRefresh()
          })
        },
      }, __('Run again', 'groundhogg')))

      return actions
    },

    ActivityItem ({
      id = false, // stable id so morphdom can match the row across re-renders
      icon = '',
      iconClass = '',
      iconFramed = true,
      body = '',
      extra = null,
      details = null,
      diffTime = '',
      ymdhis = '',
      actions = null,
      className = '',
      tabindex = false,
      children = [],
      childDay = null, // the day the parent event happened - engagement rows omit the date when it matches
    }) {

      return Li({
        id,
        className: `activity-item ${ className }`.trim(),
        tabindex : tabindex === false ? false : tabindex,
      }, [
        Div({
          className: `activity-icon ${ iconClass } ${ iconFramed === false ? 'no-frame' : '' }`.replace(/\s+/g, ' ').trim(),
        }, icon),
        Div({ className: 'activity-rendered' }, [
          Div({ className: 'activity-info' }, [
            body,
            // timestamp inline with the main content, like the group meta
            diffTime ? makeEl('abbr', { className: 'diff-time', title: ymdhis || false }, diffTime) : null,
            // inline actions, revealed on hover, after the timestamp
            actions && actions.length ? Span({ className: 'activity-actions' }, actions) : null,
          ]),
          extra ? Div({ className: 'event-extra' }, extra) : null,
          details,
        ]),
        children && children.length ? this.Engagement(children, childDay) : null,
      ])
    },

    Engagement (children, sendDay) {

      let rendered = children.map(c => {
        try {
          return this.engagementItem(c, sendDay)
        }
        catch (e) {
          return null
        }
      }).filter(Boolean)

      if (!rendered.length) {
        return null
      }

      // summarise engagement by type
      let counts = children.reduce((acc, c) => {
        let t = c.data.activity_type
        acc[t] = ( acc[t] || 0 ) + 1
        return acc
      }, {})

      let summaryParts = []

      if (counts.email_opened) {
        /* translators: %d: a number of email opens */
        summaryParts.push(sprintf(_n('%d open', '%d opens', counts.email_opened, 'groundhogg'), counts.email_opened))
      }
      if (counts.email_link_click) {
        /* translators: %d: a number of link clicks */
        summaryParts.push(sprintf(_n('%d click', '%d clicks', counts.email_link_click, 'groundhogg'), counts.email_link_click))
      }

      return Ul({ className: 'activity-children' }, [
        summaryParts.length ? Li({ className: 'engagement-summary' }, summaryParts.join(' · ')) : null,
        ...rendered,
      ])
    },

    /**
     * A single engagement row nested under a send event. The parent already names
     * the email/broadcast, so opens/clicks only show the action + when it happened
     * (time only, plus the date when it's a different day than the send).
     */
    engagementItem (activity, sendDay) {

      const d = new Date(activity.time * 1000)
      const sameDay = sendDay && this.dayKey(activity.time) === sendDay
      const when = sameDay
                   ? dateI18n(compactTimeFormat, d)
                   : `${ dateI18n('M j', d) }, ${ dateI18n(compactTimeFormat, d) }`
      const whenTitle = dateI18n(wpDateFormats.datetimeAbbreviated || wpDateFormats.datetime, d)

      switch (activity.data.activity_type) {

        case 'email_opened':
          return this.ActivityItem({
            id       : `ti-act-${ activity.ID }`,
            iconClass: 'email_opened',
            icon     : icons.open_email,
            body     : __('Opened', 'groundhogg'),
            diffTime : when,
            ymdhis   : whenTitle,
          })

        case 'email_link_click': {
          let link = activity.data.referer || ''
          return this.ActivityItem({
            id       : `ti-act-${ activity.ID }`,
            iconClass: 'email_link_click',
            icon     : icons.link_click,
            /* translators: %s: the link that was clicked */
            body     : sprintf(__('Clicked %s', 'groundhogg'), el('a', {
              target   : '_blank',
              href     : safeURL(link),
              className: 'clicked-link',
            }, escHTML(link))),
            diffTime : when,
            ymdhis   : whenTitle,
          })
        }
      }

      // anything else keeps its normal rendering
      return this.renderActivity(activity)
    },

    /**
     * A collapsible timeline group (flow run, browsing session, ...).
     */
    CollapsibleGroup ({
      id = false,
      className,
      iconClass,
      icon,
      title,
      meta = '',
      items = [],
      dataAttrs = {},
    }) {

      return Li({
        id,
        className: `activity-item group ${ className } closed`,
        ...dataAttrs,
      }, [
        Div({ className: `activity-icon ${ iconClass }` }, icon),
        Div({ className: 'group-body' }, [
          Div({
            className: 'group-header',
            onClick  : e => {
              e.currentTarget.closest('li.activity-item').classList.toggle('closed')
            },
          }, [
            Div({ className: 'group-title' }, [
              bold(title),
              meta ? Span({ className: 'group-meta' }, meta) : null,
            ]),
            Dashicon('arrow-down-alt2'),
          ]),
          Ul({ className: 'group-items' }, ( () => {
            // each row gets its own time gutter, with a date on multi-day boundaries
            let entries = items.map(i => ( { el: this.renderNode(i), time: i.time } ))
            this.gutterizeRun(entries)
            return entries.map(e => e.el)
          } )()),
        ]),
      ])
    },

    renderFlowGroup (group) {

      let funnel = FunnelsStore.get(group.funnel_id)
      let title = funnel ? titleHTML(funnel.data.title) : __('Flow', 'groundhogg')
      // keyed by the run's entry event so morphdom keeps the open/closed state across re-renders
      let entryId = group.items.reduce((a, b) => a.time <= b.time ? a : b, group.items[0]).ID

      return this.CollapsibleGroup({
        id       : `ti-fg-${ group.funnel_id }-${ entryId }`,
        className: 'flow-group',
        iconClass: 'funnel',
        icon     : icons.funnel,
        title,
        /* translators: %d: a number of events */
        meta     : sprintf(_n('%d event', '%d events', group.items.length, 'groundhogg'), group.items.length),
        items    : group.items,
        dataAttrs: { dataFunnel: group.funnel_id },
      })
    },

    renderVisitSession (session) {

      // a lone visit doesn't need grouping
      if (session.items.length === 1) {
        return this.renderActivity(session.items[0])
      }

      let entryId = session.items.reduce((a, b) => a.time <= b.time ? a : b, session.items[0]).ID

      return this.CollapsibleGroup({
        id       : `ti-vs-${ entryId }`,
        className: 'visit-session',
        iconClass: 'page-visit',
        icon     : icons.link_click,
        /* translators: %d: a number of pages */
        title    : sprintf(_n('%d page visited', '%d pages visited', session.items.length, 'groundhogg'), session.items.length),
        items    : session.items,
      })
    },

    renderNode (node) {

      if (node && node.type === 'flow_group') {
        try {
          return this.renderFlowGroup(node)
        }
        catch (e) {
          return null
        }
      }

      if (node && node.type === 'visit_session') {
        try {
          return this.renderVisitSession(node)
        }
        catch (e) {
          return null
        }
      }

      try {
        return this.renderActivity(node)
      }
      catch (e) {
        return null
      }
    },

    /**
     * Turn the flat, time-sorted activity list into a tree:
     *  - opens / clicks / other engagement activities that carry an `event_id`
     *    nest beneath the send event they belong to
     *  - funnel events are grouped into "flow runs", a new run starting each time
     *    the contact enters the flow through an entry-point step
     *  - consecutive page visits are grouped into browsing sessions
     *  - broadcasts, email notifications, submissions and standalone activities
     *    stay at the root
     *
     * @param activities the flat list (already filtered)
     * @param order      'asc' | 'desc'
     * @return {Array} root nodes
     */
    buildTree (activities, order = 'desc') {

      order = order === 'asc' ? 'asc' : 'desc'

      // index completed events so engagement activities can nest beneath them
      let eventById = new Map()
      activities.forEach(a => {
        if (a.type === 'event' && !a.pending) {
          a.children = []
          eventById.set(String(a.ID), a)
        }
      })

      // pass 1: nest opens/clicks/etc. under their originating event
      let flat = []
      activities.forEach(a => {
        if (a.type === 'activity') {
          let eventId = a.data.event_id
          if (eventId && eventById.has(String(eventId))) {
            eventById.get(String(eventId)).children.push(a)
            return
          }
        }
        flat.push(a)
      })

      eventById.forEach(ev => ev.children.sort((x, y) => x.time - y.time))

      // group consecutive page visits into browsing sessions (30 min inactivity gap)
      const SESSION_GAP = 30 * 60
      let sessions = []
      flat.filter(a => a.type === 'page_visit').
        sort((a, b) => a.time - b.time).
        forEach(visit => {
          let current = sessions[sessions.length - 1]
          if (current && ( visit.time - current.maxTime ) <= SESSION_GAP) {
            current.items.push(visit)
            current.maxTime = visit.time
          }
          else {
            sessions.push({
              type   : 'visit_session',
              items  : [ visit ],
              minTime: visit.time,
              maxTime: visit.time,
            })
          }
        })

      // pass 2: walk the rest ascending, grouping funnel events into flow runs
      let ascending = flat.filter(a => a.type !== 'page_visit').sort((a, b) => a.time - b.time)
      let openGroups = new Map()
      let roots = [ ...sessions ]

      ascending.forEach(item => {

        let isFunnelEvent = item.type === 'event' && parseInt(item.data.event_type) === 1
        let funnelId = parseInt(item.data.funnel_id)

        if (!isFunnelEvent || !( funnelId > 1 )) {
          roots.push(item)
          return
        }

        let step = item.step
        let isEntry = !!( step && step.data && ( step.is_starting || step.is_entry ) )

        if (isEntry || !openGroups.has(funnelId)) {
          let group = {
            type     : 'flow_group',
            funnel_id: funnelId,
            items    : [],
            minTime  : item.time,
            maxTime  : item.time,
          }
          openGroups.set(funnelId, group)
          roots.push(group)
        }

        let group = openGroups.get(funnelId)
        item.grouped = true // rendered inside a flow group - no need to restate the flow
        group.items.push(item)
        group.minTime = Math.min(group.minTime, item.time)
        group.maxTime = Math.max(group.maxTime, item.time)
      })

      // order group items + compute a sort key for each root
      let nowSeconds = Math.floor(Date.now() / 1000)

      roots.forEach(node => {
        if (node.type === 'flow_group' || node.type === 'visit_session') {
          node.items.sort((a, b) => order === 'desc' ? b.time - a.time : a.time - b.time)
          // a group's "when" is its last *actual* activity, not a pending future step
          let happened = node.items.filter(i => i.time <= nowSeconds).map(i => i.time)
          let latest = happened.length ? Math.max(...happened) : node.minTime
          node.sortTime = order === 'desc' ? latest : node.minTime
        }
        else {
          node.sortTime = node.time
        }
      })

      roots.sort((a, b) => order === 'desc' ? b.sortTime - a.sortTime : a.sortTime - b.sortTime)

      return roots
    },

    // the calendar day a unix timestamp falls on, in the site timezone (e.g. "2024-03-14")
    dayKey (time) {
      return dateI18n('Y-m-d', new Date(time * 1000))
    },

    /**
     * The relative-time bucket a timestamp falls into, used for the section headers.
     */
    dateBucket (time) {

      if (isInTheFuture(new Date(time * 1000))) {
        return { key: 'upcoming', label: __('Upcoming', 'groundhogg') }
      }

      const dayKey = this.dayKey(time)
      const todayKey = this.dayKey(Date.now() / 1000)

      if (dayKey === todayKey) {
        return { key: 'today', label: __('Today', 'groundhogg') }
      }

      // whole calendar days between the two dates (parsed as UTC midnight so DST/TZ don't skew it)
      const days = Math.round(( Date.parse(todayKey) - Date.parse(dayKey) ) / 86400000)

      if (days === 1) {
        return { key: 'yesterday', label: __('Yesterday', 'groundhogg') }
      }
      if (days <= 7) {
        return { key: 'last-7', label: __('Previous 7 days', 'groundhogg') }
      }
      if (days <= 30) {
        return { key: 'last-30', label: __('Previous 30 days', 'groundhogg') }
      }

      const d = new Date(time * 1000)
      const sameYear = dateI18n('Y', d) === dateI18n('Y', new Date())

      return {
        key  : dateI18n('Y-m', d),
        label: dateI18n(sameYear ? 'F' : 'F Y', d),
      }
    },

    // the left-gutter cell: time of day, optionally prefixed with the date
    gutterEl (ms, withDate = false) {
      const d = new Date(ms)
      return makeEl('span', {
        className: 'activity-gutter',
        title    : dateI18n(wpDateFormats.datetimeAbbreviated || wpDateFormats.datetime, d),
      }, [
        withDate ? makeEl('span', { className: 'activity-date' }, dateI18n('M j', d)) : null,
        makeEl('span', { className: 'activity-time' }, dateI18n(compactTimeFormat, d)),
      ])
    },

    /**
     * Prepend a time gutter to a contiguous run of rendered rows. The date is shown on the
     * oldest row of each day - the last row when reading desc (bottom-up), the first when asc.
     *
     * @param entries [{ el, time }] - rendered <li> + its unix timestamp (el may be null)
     */
    gutterizeRun (entries) {

      const labelLast = this.order !== 'asc'

      const addGutter = (el, time, withDate) => {
        el.insertBefore(this.gutterEl(time * 1000, withDate), el.firstChild)
      }

      let prev = null // { el, time, day }

      entries.forEach(({ el, time }) => {

        if (!el || !time) {
          return
        }

        let day = this.dayKey(time)

        if (labelLast) {
          if (prev) {
            addGutter(prev.el, prev.time, prev.day !== day)
          }
          prev = { el, time, day }
        }
        else {
          addGutter(el, time, !prev || prev.day !== day)
          prev = { el, time, day }
        }
      })

      if (prev && labelLast) {
        addGutter(prev.el, prev.time, true)
      }
    },

    render (nodes) {

      let items = []
      let currentBucket = null
      let run = [] // { el, time } for the current section

      const flushRun = () => {
        this.gutterizeRun(run)
        run = []
      }

      nodes.forEach(node => {

        let bucket = this.dateBucket(node.sortTime)

        if (bucket.key !== currentBucket) {
          flushRun()
          currentBucket = bucket.key
          items.push(Li({ id: `ti-th-${ bucket.key }`, className: 'timeline-date-header' }, bucket.label))
        }

        let el = this.renderNode(node)
        if (!el) {
          return
        }

        run.push({ el, time: node.sortTime })
        items.push(el)
      })

      flushRun()

      return Ul({ id: 'activity-timeline' }, items)
    },

    onMount () {},

    mount (selector, activities, {
      needsRefresh = () => {},
      order = 'desc',
    } = {}) {

      this.needsRefresh = needsRefresh
      this.order = order

      const el = document.querySelector(selector)

      if (!el) {
        return
      }

      if (!activities.length) {
        el.innerHTML = `<div class="align-center-space-between" style="margin: 20px"><span class="pill orange">${ __(
          'No activity found.', 'groundhogg') }</span></div>`
        return
      }

      // preload hook for externally-registered activity types
      let promises = activities.
        filter(a => a.type === 'activity' && this.types[a.data.activity_type]?.hasOwnProperty('preload')).
        map(a => this.types[a.data.activity_type].preload(a))

      Promise.all(promises).catch(() => {}).finally(() => {

        let next = this.render(this.buildTree(activities, order))
        let current = el.querySelector('#activity-timeline')

        if (current) {
          // morph in place so a refresh only touches what changed, and scroll position,
          // hover, and expanded groups / detail panels survive
          morphdom(current, next, {
            onBeforeElUpdated: (fromEl, toEl) => {
              // keep the user's expand/collapse choice through a re-render
              if (fromEl.classList.contains('group') || fromEl.classList.contains('activity-details')) {
                toEl.classList.toggle('closed', fromEl.classList.contains('closed'))
              }
              return !fromEl.isEqualNode(toEl)
            },
          })
        }
        else {
          el.innerHTML = ''
          el.append(next)
        }

        this.onMount()
      })

    },

  }

  const otherContactStuff = () => {

    const tabs = [
      {
        id     : 'activity',
        name   : __('Activity', 'groundhogg'),
        render : () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div class="inside">
                      <div class="display-flex gap-10 align-bottom">
                          <div class="order-by">
                              <label for="activity-order"><b>${ __(
                                      'Order by', 'groundhogg') }</b></label><br/>
                              ${ select({
                                  id  : 'activity-order',
                                  name: 'order',
                              }, {
                                  desc: __('Newest first', 'groundhogg'),
                                  asc : __('Oldest first', 'groundhogg'),
                              }, 'desc') }
                          </div>
                          <div class="filter-by">
                              <label for="timeline-filter-picker-search-input"><b>${ __(
                                      'Filter by', 'groundhogg') }</b></label><br/>
                              <div id="timeline-filter"></div>
                          </div>
                          <button id="refresh-timeline"
                                  class="gh-button secondary text icon"><span
                                  class="dashicons dashicons-update-alt"></span>
                          </button>
                      </div>
                  </div>
              </div>
              <div id="activity-here">
                  ${ skeleton() }
              </div>
              <div id="timeline-load-earlier"></div>`
        },
        onMount: () => {

          const nowUnix = () => Math.floor(Date.now() / 1000)

          let order = 'desc'
          let filter = null
          let filterOptions = []
          let stepsById = timelineStepsById // module-scoped so it survives tab switches

          // The timeline holds everything from `windowAfter` up to now. "Load earlier" slides
          // `windowAfter` back in fixed slices; "refresh" fetches only what's new since `newestLoaded`.
          const DEFAULT_LOOKBACK_DAYS = 90
          const LOAD_EARLIER_DAYS = 90
          let windowAfter = nowUnix() - ( DEFAULT_LOOKBACK_DAYS * 86400 )
          let newestLoaded = null
          let loadEarlierStep = LOAD_EARLIER_DAYS
          let emptyStreak = 0
          let atStartOfHistory = false

          // nothing predates the contact - a hard floor for "load earlier"
          const contactCreatedUnix = ( () => {
            let parsed = Date.parse(String(contact?.data?.date_created || '').replace(/-/g, '/'))
            return isNaN(parsed) ? 0 : Math.floor(parsed / 1000)
          } )()

          $('#refresh-timeline').on('click', e => {

            $(e.currentTarget).find('.dashicons').addClass('spinning')

            // incremental: only what's newer than what we already hold (+ the full pending queue)
            fetchActivity({ after: newestLoaded ?? windowAfter }).then(() => {
              $(e.currentTarget).find('.dashicons').removeClass('spinning')
            })
          })

          tooltip('#refresh-timeline', {
            content : __('Refresh', 'groundhogg'),
            position: 'right',
          })

          $('#activity-order').on('change', (e) => {
            // order only affects the client-side sort / grouping - no refetch needed
            order = e.target.value
            loadTimeline()
          })

          // Build the "filter by" options from what's actually in the timeline:
          // activity types, plus specific flows / emails / broadcasts that appear.
          const buildFilterOptions = (activities) => {

            let typeOpts = [
              { id: 'flows', text: __('All flow activity', 'groundhogg') },
              { id: 'broadcasts', text: __('All broadcasts', 'groundhogg') },
              { id: 'submissions', text: __('Form submissions', 'groundhogg') },
              { id: 'web', text: __('Web activity', 'groundhogg') },
            ]

            if (isWPFusionActive) {
              typeOpts.push({ id: 'wp_fusion', text: __('WPFusion activity', 'groundhogg') })
            }

            let specificOpts = []
            let seenFunnels = new Set()
            let seenEmails = new Set()
            let seenBroadcasts = new Set()

            activities.forEach(a => {

              let funnelId = parseInt(a.data?.funnel_id || a.form?.data?.funnel_id)
              if (funnelId > 1 && !seenFunnels.has(funnelId)) {
                seenFunnels.add(funnelId)
                let funnel = FunnelsStore.get(funnelId)
                specificOpts.push({
                  id  : `flow:${ funnelId }`,
                  /* translators: %s: a flow name */
                  text: sprintf(__('Flow: %s', 'groundhogg'), funnel ? titleHTML(funnel.data.title) : `#${ funnelId }`),
                })
              }

              let emailId = parseInt(a.data?.email_id)
              if (emailId > 0 && !seenEmails.has(emailId)) {
                seenEmails.add(emailId)
                let email = EmailsStore.get(emailId)
                if (email) {
                  specificOpts.push({
                    id  : `email:${ emailId }`,
                    /* translators: %s: an email subject */
                    text: sprintf(__('Email: %s', 'groundhogg'), titleHTML(email.data.title)),
                  })
                }
              }

              if (a.type === 'event' && parseInt(a.data.event_type) === 2 && a.broadcast && !seenBroadcasts.has(a.broadcast.ID)) {
                seenBroadcasts.add(a.broadcast.ID)
                specificOpts.push({
                  id  : `broadcast:${ a.broadcast.ID }`,
                  /* translators: %s: a broadcast name */
                  text: sprintf(__('Broadcast: %s', 'groundhogg'), titleHTML(a.broadcast.object?.data?.title) || `#${ a.broadcast.ID }`),
                })
              }
            })

            specificOpts.sort((a, b) => a.text.localeCompare(b.text))

            return [ ...typeOpts, ...specificOpts ]
          }

          const applyFilter = (activities) => {

            if (!filter || filter === 'all') {
              return activities
            }

            switch (filter) {
              case 'flows': {
                // funnel events plus engagement (opens / clicks / conversions) tied to a flow
                let eventIds = new Set(activities.
                  filter(a => a.type === 'event' && parseInt(a.data.event_type) === 1).
                  map(a => String(a.ID)))
                return activities.filter(a =>
                  ( a.type === 'event' && parseInt(a.data.event_type) === 1 ) ||
                  ( a.type === 'activity' && ( parseInt(a.data.funnel_id) > 1 || eventIds.has(String(a.data.event_id)) ) ),
                )
              }
              case 'broadcasts': {
                // keep broadcast events plus any engagement activity tied to them
                let eventIds = new Set(activities.
                  filter(a => a.type === 'event' && parseInt(a.data.event_type) === 2).
                  map(a => String(a.ID)))
                return activities.filter(a =>
                  ( a.type === 'event' && parseInt(a.data.event_type) === 2 ) ||
                  ( a.type === 'activity' && eventIds.has(String(a.data.event_id)) ),
                )
              }
              case 'submissions':
                return activities.filter(a => a.type === 'submission')
              case 'web':
                return activities.filter(a => a.type === 'page_visit')
              case 'wp_fusion':
                return activities.filter(a => a.type === 'activity' && a.data.activity_type === 'wp_fusion')
            }

            let [ kind, rawId ] = filter.split(':')
            let id = parseInt(rawId)

            switch (kind) {
              case 'flow': {
                let eventIds = new Set(activities.
                  filter(a => a.type === 'event' && parseInt(a.data.funnel_id) === id).
                  map(a => String(a.ID)))
                return activities.filter(a =>
                  parseInt(a.data?.funnel_id) === id ||
                  parseInt(a.form?.data?.funnel_id) === id ||
                  ( a.type === 'activity' && eventIds.has(String(a.data.event_id)) ),
                )
              }
              case 'email':
                return activities.filter(a => parseInt(a.data?.email_id) === id)
              case 'broadcast': {
                // keep the broadcast event(s) plus any engagement activity tied to them
                let eventIds = new Set(activities.
                  filter(a => a.type === 'event' && parseInt(a.data.event_type) === 2 && a.broadcast && a.broadcast.ID === id).
                  map(a => String(a.ID)))
                return activities.filter(a =>
                  ( a.type === 'event' && a.broadcast && a.broadcast.ID === id ) ||
                  ( a.type === 'activity' && eventIds.has(String(a.data.event_id)) ),
                )
              }
            }

            return activities
          }

          document.getElementById('timeline-filter').append(MakeEl.ItemPicker({
            id          : 'timeline-filter-picker',
            multiple    : false,
            clearable   : true,
            noneSelected: __('All activity', 'groundhogg'),
            selected    : [],
            fetchOptions: (search) => {
              search = ( search || '' ).toLowerCase()
              return Promise.resolve(filterOptions.filter(o => o.text.toLowerCase().includes(search)))
            },
            onChange    : (item) => {
              filter = item ? item.id : null
              loadTimeline()
            },
          }))

          // The four time-bounded stores. The queue is handled separately (always fetched whole).
          const rangedStores = [ SubmissionsStore, ActivityStore, EventsStore, PageVisitsStore ]

          /**
           * @param before  upper bound (unix) - omit for "everything since `after`"
           * @param after   lower bound (unix)
           * @param replace  clear the ranged stores first (initial load / order reset)
           */
          const fetchActivity = ({ before = null, after = windowAfter, replace = false } = {}) => {

            let params = { order, after }
            if (before !== null) {
              params.before = before
            }

            return get(`${ ContactsStore.route }/${contact.ID}/timeline`, params).then(response => {

              // Hydration data - events reference these by id rather than embedding them
              FunnelsStore.itemsFetched(response.funnels || [])
              EmailsStore.itemsFetched(response.emails || [])
              BroadcastsStore.itemsFetched(response.broadcasts || [])

              ;( response.steps || [] ).forEach(step => { stepsById[step.ID] = step })

              if (replace) {
                rangedStores.forEach(s => {
                  s.clearItems()
                  s.clearResultsCache()
                })
              }

              SubmissionsStore.itemsFetched(response.submissions)
              ActivityStore.itemsFetched(response.activity)
              EventsStore.itemsFetched(response.events)
              PageVisitsStore.itemsFetched(response.page_visits)

              // the pending queue is always returned whole - rebuild it so fired events drop off
              EventQueue.clearItems()
              EventQueue.itemsFetched(response.event_queue)

              // fallback for an unhydrated response (step still embedded on the event)
              ;( response.events || [] ).
                filter(e => parseInt(e.data.event_type) === 2 && e.broadcast).
                forEach(e => BroadcastsStore.itemsFetched([e.broadcast]))

              timelineHydrated = true

              loadTimeline()

              return response
            })
          }

          const loadEarlier = () => {

            let sliceBefore = windowAfter
            let sliceAfter = windowAfter - ( loadEarlierStep * 86400 )
            windowAfter = sliceAfter

            fetchActivity({ before: sliceBefore, after: sliceAfter }).then(response => {

              let got = [ 'submissions', 'activity', 'events', 'page_visits' ].
                reduce((n, k) => n + ( Array.isArray(response[k]) ? response[k].length : 0 ), 0)

              if (got) {
                emptyStreak = 0
                loadEarlierStep = LOAD_EARLIER_DAYS
              }
              else {
                // skip dormant stretches faster; give up after a few empty slices in a row
                emptyStreak++
                loadEarlierStep = Math.min(loadEarlierStep * 2, 730)
              }

              if (windowAfter <= contactCreatedUnix || emptyStreak >= 3) {
                atStartOfHistory = true
              }

              renderLoadEarlier()
            })
          }

          const renderLoadEarlier = () => {

            const $container = $('#timeline-load-earlier')

            if (atStartOfHistory) {
              $container.empty()
              return
            }

            $container.html(
              `<button id="load-earlier-activity" class="gh-button secondary text">${ __('Load earlier activity', 'groundhogg') }</button>`)

            $('#load-earlier-activity').on('click', e => {
              $(e.currentTarget).prop('disabled', true).addClass('loading-dots').text(__('Loading', 'groundhogg'))
              loadEarlier()
            })
          }

          // re-attach the step / broadcast an event references (unless it's still embedded)
          const hydrateEvent = (e) => {
            switch (parseInt(e.data.event_type)) {
              case 1:
                return e.step ? {} : { step: stepsById[e.data.step_id] }
              case 2:
                return e.broadcast ? {} : { broadcast: BroadcastsStore.get(e.data.step_id) }
              default:
                return {}
            }
          }

          const loadTimeline = () => {
            let allActivities = [
              ...SubmissionsStore.getItems().map(a => ( {
                ...a,
                type: 'submission',
                time: parseInt(a.data.time),
              } )),
              ...ActivityStore.getItems().map(a => ( {
                ...a,
                type: 'activity',
                time: parseInt(a.data.timestamp),
              } )),
              ...EventsStore.getItems().map(e => ( {
                ...e,
                ...hydrateEvent(e),
                type: 'event',
                time: parseInt(e.data.time) + parseFloat(e.data.micro_time),
              } )),
              ...EventQueue.getItems().map(e => ( {
                ...e,
                ...hydrateEvent(e),
                type   : 'event',
                pending: true,
                time   : parseInt(e.data.time) + parseFloat(e.data.micro_time),
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

            // track the bounds of what we hold (ignore pending/future queue items)
            allActivities.forEach(a => {
              if (a.pending) {
                return
              }
              if (newestLoaded === null || a.time > newestLoaded) {
                newestLoaded = a.time
              }
              if (a.time < windowAfter) {
                windowAfter = a.time
              }
            })

            if (windowAfter <= contactCreatedUnix) {
              atStartOfHistory = true
            }

            filterOptions = buildFilterOptions(allActivities)
            allActivities = applyFilter(allActivities)

            ActivityTimeline.mount('#activity-here', allActivities, {
              order,
              needsRefresh: () => fetchActivity({ after: newestLoaded ?? windowAfter }),
            })

            $('#activity-here').
              css({ maxHeight: $('#primary-contact-stuff').height() })

            renderLoadEarlier()
          }

          if (timelineHydrated && (
            ActivityStore.hasItems()
            || EventsStore.hasItems()
            || EventQueue.hasItems()
            || PageVisitsStore.hasItems()
            || SubmissionsStore.hasItems()
          )) {
            loadTimeline()
            return
          }

          fetchActivity({ after: windowAfter, replace: true })
        },
      },
      {
        id     : 'notes',
        name   : __('Notes', 'groundhogg'),
        render : () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div id="notes-here"></div>
              </div>`
        },
        onMount: () => {
          Groundhogg.noteEditor('#notes-here', {
            object_id  : contact.ID,
            object_type: 'contact',
            title      : '',
          })
        },
      },
      {
        id     : 'tasks',
        name   : __('Tasks', 'groundhogg'),
        render : () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div id="tasks-here"></div>
              </div>`
        },
        onMount: () => {

          morphdom(document.getElementById('tasks-here'), Groundhogg.ObjectTasks({
            object_id  : contact.ID,
            object_type: 'contact',
            title      : false,
          }))
        },
      },
      {
        id     : 'files',
        name   : __('Files', 'groundhogg'),
        render : () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div id="file-actions" class="inside display-flex gap-10">
                      ${ input({
                          placeholder: __('Search files...', 'groundhogg'),
                          type       : 'search',
                          id         : 'search-files',
                          className  : 'full-width',
                      }) }
                      <button id="upload-file" class="gh-button secondary">
                          ${ __('Upload Files', 'groundhogg') }
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
              confirmText: __('Delete', 'groundhogg'),
              alert      : `<p>${ sprintf(
                /* translators: %d: the number of files */
                _n('Are you sure you want to delete %d file?', 'Are you sure you want to delete %d files?', selectedFiles.length, 'groundhogg'),
                selectedFiles.length) }</p>`,
              onConfirm  : () => {
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
            content : __('Bulk delete files', 'groundhogg'),
            position: 'right',
          })

          const renderFile = (file) => {
            //language=HTML
            return `
                <tr class="file">
                    <th scope="row" class="check-column">${ input({
                        type     : 'checkbox',
                        name     : 'select[]',
                        className: 'file-toggle',
                        value    : file.name,
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

                items   : [
                  {
                    key : 'download',
                    text: __('Download', 'groundhogg'),
                  },
                  userHasCap('delete_files') ? {
                    key : 'delete',
                    text: `<span class="gh-text danger">${ __( 'Delete', 'groundhogg') }</span>`,
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
                        alert      : `<p>${ sprintf(
                          /* translators: %s: the name of an asset (contacts, flows, emails, etc...) */
                          __('Are you sure you want to delete %s?', 'groundhogg'), _file) }</p>`,
                        onConfirm  : () => {
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
              action      : 'groundhogg_contact_upload_file',
              nonce       : '',
              beforeUpload: (fd) => fd.append('contact', contact.ID),
              onUpload    : (json, file) => {
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
        id     : 'inbox',
        name   : __('Inbox'),
        render : () => {
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
                          ${ __( 'You can help us get there faster by giving us a <a target="_blank" href="https://wordpress.org/support/plugin/groundhogg/reviews/">⭐⭐⭐⭐⭐ review!</a>' ) }</p>
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
              }) => `<a href="#${ id }" data-tab="${ id }" class="nav-tab ${ activeTab ===
                                                                      id ? 'nav-tab-active' : '' }">${ name }</a>`).join('') }
          </h2>
              ${ tabs.find(t => t.id === activeTab).render() }
          </div>`
    }

    const mount = () => {

      $('#other-contact-stuff').html(template())
      onMount()
    }

    let hash = window.location.hash.replace('#', '')
    let activeTab

    if ( tabs.find(t => t.id === hash) ) {
      activeTab = hash
    } else {
      activeTab = editor.default_tab ?? 'activity'
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

  const handleGeoLocate = () => {
    $('#geolocate').on( 'click', e => {
      let $saveButton = $('#save-primary')
      $saveButton[0].insertAdjacentElement('afterend', MakeEl.Input({ type: 'hidden', name: 'geolocate', value: '1' }) )
      $saveButton.click()
    } )
  }

  const handleFormSubmit = () => {

    $('#primary-form').on('submit', e => {
      e.preventDefault()

      const $btn = $('#save-primary')

      $btn.prop('disabled', true).addClass( 'loading-dots' )

      let data = new FormData(e.currentTarget)

      data.append('action', 'groundhogg_edit_contact')
      data.append('contact', getContact().ID)

      ajax(data).then(r => {

        $('#primary-contact-stuff .contact-details').replaceWith(r.data.details)
        contactMoreActions()

        ContactsStore.itemsFetched([r.data.contact])

        $btn.prop('disabled', false).removeClass('loading-dots')

        dialog({
          message: __('Changes saved!', 'groundhogg'),
        })

      })

    })

  }

  const managePrimaryTabs = () => {

    let activeTab = 'general'

    let customTabState = gh_contact_custom_properties || {
      tabs  : [],
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

      $('#save-meta').prop('disabled', true).addClass( 'loading-dots' )

      Promise.all([
        ContactsStore.patchMeta(getContact().ID, metaChanges),
        deleteKeys.length ? ContactsStore.deleteMeta(getContact().ID,
          deleteKeys) : null,
      ]).then(() => {

        metaChanges = {}
        deleteKeys = []

        mount()
        dialog({
          message: __('Changes saved!', 'groundhogg'),
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
          items   : customTabState.tabs.map(t => ( {
            key : t.id,
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
                                                                activeTab ? ' nav-tab-active' : '' }">${ _x('More', 'as in additional contact fields', 'groundhogg') }</a>`).
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
          content : __('Custom tabs', 'groundhogg'),
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
                    <div class="sticky-submit has-box-shadow">
                        <button id="cancel-meta-changes"
                                class="gh-button danger text">${ __('Cancel') }
                        </button>
                        <button id="save-meta" class="gh-button primary">${ __('Save Changes', 'groundhogg') }</button>
                    </div>
                </div>
            </div>`

        $(customTabUi).insertAfter('#primary-contact-stuff form')
        $(`<button class="gh-button tab-more secondary text icon">${ icons.verticalDots }</button>`).
          insertAfter('#add-tab')

        $('#save-meta').on('click', commitMetaChanges)
        $('#cancel-meta-changes').on('click', cancelMetaChanges)

        tooltip('.tab-more', {
          content : __('Tab Options', 'groundhogg'),
          position: 'right',
        })

        $('.tab-more').on('click', e => {
          e.preventDefault()

          moreMenu(e.currentTarget, {
            items   : [
              {
                key : 'rename',
                cap : 'manage_options',
                text: __('Rename', 'groundhogg'),
              },
              {
                key : 'delete',
                cap : 'manage_options',
                text: `<span class="gh-text danger">${ __('Delete', 'groundhogg') }</span>`,
              },
            ],
            onSelect: k => {

              switch (k) {

                case 'delete':

                  dangerConfirmationModal({
                    confirmText: __('Delete'),
                    alert      : `<p>${ sprintf(
                      /* translators: %s: the name of an asset (contacts, flows, emails, etc...) */
                      __('Are you sure you want to delete %s?', 'groundhogg'),
                      bold(customTabState.tabs.find(
                        t => t.id === activeTab).name)) }</p>`,
                    onConfirm  : () => {

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
                                    id         : 'tab-name',
                                    value      : customTabState.tabs.find(
                                            t => t.id === activeTab).name,
                                    placeholder: __('Tab name', 'groundhogg'),
                                }) }
                                <button id="update-tab"
                                        class="gh-button primary">
                                    ${ __('Save', 'groundhogg') }
                                </button>
                            </div>
                        </div>`,
                    onOpen : ({ close }) => {

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
          values             : {
            ...getContact().meta,
            ...metaChanges,
          },
          properties         : {
            groups: __groups(),
            fields: __fields(),
          },
          onPropertiesUpdated: ({
            groups = [],
            fields = [],
          }) => {

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
              ...groups.map(g => ( {
                ...g,
                tab: activeTab,
              } )),
            ]

            updateTabState()

          },
          onChange           : (meta) => {
            metaChanges = {
              ...metaChanges,
              ...meta,
            }
          },
          canEdit            : () => {
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
                    <h2>${ _x('Meta', 'as in additional contact metadata', 'groundhogg') }</h2>
                    <div id="meta-here">
                    </div>
                    <div class="sticky-submit has-box-shadow">
                        <button id="cancel-meta-changes"
                                class="gh-button danger text">${ __('Cancel', 'groundhogg') }
                        </button>
                        <button id="save-meta" class="gh-button primary">${ __('Save Changes', 'groundhogg') }</button>
                    </div>
                </div>
            </div>`

        $(metaUi).insertAfter('#primary-contact-stuff form')
        $('#cancel-meta-changes').on('click', cancelMetaChanges)

        let {
          alternate_emails = [],
          alternate_phones = [],
        } = getContact().meta

        inputRepeater('#contact-phones-here', {
          rows    : alternate_phones,
          cells   : [
            ({
              value,
              ...props
            }) => select({
              options : {
                mobile: __('Mobile', 'groundhogg'),
                home  : __('Home', 'groundhogg'),
                work  : __('Work', 'groundhogg'),
              },
              selected: value,
              ...props,
            }),
            props => input({
              type       : 'tel',
              placeholder: __('(123) 456-7890', 'groundhogg'),
              ...props,
            }),
          ],
          onMount : () => {
          },
          onChange: (rows) => {
            metaChanges.alternate_phones = rows
          },
        }).mount()

        inputRepeaterWidget({
          selector     : '#contact-emails-here',
          rows         : alternate_emails.map(e => [e]),
          cellProps    : [
            {
              type       : 'email',
              className  : 'alternate-email-address',
              placeholder: __('john.doe@example.com', 'groundhogg'),
            },
          ],
          cellCallbacks: [input],
          onMount      : () => {
          },
          onChange     : (rows) => {
            metaChanges.alternate_emails = rows.map(r => r[0])
          },
        }).mount()

        inputRepeater('#meta-here', {
          rows    : Object.keys(combinedMeta).
            filter(k => !meta_exclusions.includes(k)).
            map(k => ( [
              k,
              combinedMeta[k],
            ] )),
          cells   : [
            (props) => input({
              ...props,
              readonly : !!props.value,
              className: 'meta-key',
            }),
            ({
              value,
              ...props
            }) => input({
              value   : [
                          'array',
                          'object',
                        ].includes(typeof value)
                        ? JSON.stringify(value)
                        : value,
              readonly: [
                'array',
                'object',
              ].includes(typeof value),
              ...props,
            }),
          ],
          onMount : () => {
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
                          id         : 'tab-name',
                          placeholder: __('Tab name', 'groundhogg'),
                      }) }
                      <button id="create-tab" class="gh-button primary">
                          ${ __('Create', 'groundhogg') }
                      </button>
                  </div>
              </div>`,
          onOpen : ({ close }) => {

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
        content : __('Add tab', 'groundhogg'),
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
                  ${ __('Cancel', 'groundhogg') }
              </button>
              <button id="save-tag-changes" class="gh-button primary">
                  ${ __('Save', 'groundhogg') }
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
        dates: getContact().i18n.tagDates,
        onChange: ({
          addTags   : _addTags,
          removeTags: _removeTags,
        }) => {
          removeTags = _removeTags
          addTags = _addTags
          maybeShowTagChangeActions()
        },
      })

      $('#save-tag-changes').on('click', () => {
        ContactsStore.patch(getContact().ID, {
          remove_tags: removeTags,
          add_tags   : addTags,
        }).then(() => {
          dialog({
            message: __('Changes saved!', 'groundhogg'),
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

      handleGeoLocate()
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

      $(document).on('click', '.gh-panel.outlined button.toggle-indicator', e => {

        // do not do for elements made with makeEl on this page
        if (e.currentTarget.makeEl === true) {
          return
        }

        $(e.target).closest('.gh-panel.outlined').toggleClass('closed')
      })

      if (window.location.href.match(/send_email=true/)) {
        sendEmail()
      }
    },
  })

  const { email_log: LogsStore } = Groundhogg.stores

  // Handle log items
  $(document).on('click', 'a.view-event-email-log-item', e => {
    e.preventDefault()
    openEventEmailLog(parseInt($(e.currentTarget).data('event-id')))
  })

  // Handle log items
  $(document).on('click', 'a.view-composed-email-log-item', async e => {

    e.preventDefault()

    let activityId = parseInt($(e.currentTarget).data('activity-id'))

    let activity = ActivityStore.get(activityId)

    let { close } = loadingModal()

    try {

      let logItem

      const {
        log_id = 0,
        sent_by,
        from: from_address,
        subject,
      } = activity.meta

      if (log_id) {
        logItem = await LogsStore.maybeFetchItem(log_id)
      }
      else {

        let logItems = await LogsStore.fetchItems({
          subject,
          sent_by,
          from_address,
          filters: [
            [
              {
                type      : 'recipients',
                recipients: [getContact().data.email],
              },
              {
                type      : 'date_sent',
                date_range: 'day_of',
                after     : moment.unix(activity.data.timestamp).utc().format('YYYY-MM-DD'),
              },
            ],
          ],
          limit  : 1,
        })

        logItem = logItems[0]
      }

      EmailLogModal(logItem)

    }
    catch (err) {

      dialog({
        message: err.message,
        type   : 'error',
        ttl    : 5000,
      })

    }

    close()

  })

  $(function () {
    editor.init()

    const ContactRelationships = ({
      title,
      rel = '',
    }) => Relationships({
      title,
      id               : ContactEditor.contact_id,
      [`${ rel }_type`]: 'contact',
      store            : ContactsStore,
      renderItem       : ({
        onDelete,
        ...item
      }) => ContactListItem(item, {
        extra: Div( { className: 'display-flex gap-5' },[
          An({
            onClick  : e => {
              window.open( item.admin )
            },
          }, __('View')),
          Span({},'|'),
          An({
            className: 'danger',
            onClick  : e => {
              e.preventDefault()
              onDelete(item.ID)
            },
          }, __('Remove')),
        ]),
      }),
      onAddItem        : (res, rej, state) => {
        selectContactModal({
          onSelect: item => res(item),
          exclude : state.items.map(i => i.ID),
          onClose : rej,
        })
      },
    })

    let el = document.getElementById('contact-relationships')
    if (el) {
      morphdom(el.parentNode, Div({
        className: 'inside',
        style    : {
          padding: 0,
        },
      }, [
        ContactRelationships({
          title: __('Parents', 'groundhogg'),
          rel  : 'parent',
        }),
        ContactRelationships({
          title: __('Children', 'groundhogg'),
          rel  : 'child',
        }),
      ]))
    }
  })

  Groundhogg.ActivityTimeline = ActivityTimeline
  Groundhogg.ContactActions = ContactActions

  $(document).on('heartbeat-send.groundhogg-refresh-local-time', function (event, data) {

    data['groundhogg-refresh-local-time'] = getContact().ID

  }).on('heartbeat-tick.groundhogg-refresh-local-time', function (e, data) {

    // Post locks: update the lock string or show the dialog if somebody has taken over editing.
    let received

    if (data['groundhogg-refresh-local-time']) {
      received = data['groundhogg-refresh-local-time']

      if (received.local_time) {
        $('#contact-localtime abbr').replaceWith(received.local_time)
      }
    }
  })

  $(document).on('click', '.contact-picture', e => {

    let file_frame = wp.media.frames.file_frame = wp.media({
      title: __('Select a profile picture', 'groundhogg'),
      button: {
        text: __('Select', 'groundhogg'),
      },
      library: {
        type: 'image',
      },
      multiple: false,
      frame: 'select',
    })
    // When an image is selected, run a callback.
    file_frame.on('select', function () {
      // We set multiple to false so only get one image from the uploader
      let attachment = file_frame.state().get('selection').first().toJSON()

      ContactsStore.patch(getContact().ID, {
        meta: {
          profile_picture: attachment.url,
        }
      })

      $('.contact-picture > .gh-square-image')[0].style.backgroundImage = `url(${ attachment.url })`

    })
    // Finally, open the modal
    file_frame.open()

  })

} )(jQuery, ContactEditor)

