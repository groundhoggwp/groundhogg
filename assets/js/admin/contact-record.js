(($) => {

  const { StepTypes } = Groundhogg
  const {
    searchOptionsWidget,
    regexp,
    specialChars,
    breadcrumbs,
    modal,
    input,
    tabs,
    loadingDots,
    copyObject,
    objectEquals,
    moreMenu,
    select,
    dangerConfirmationModal,
    confirmationModal,
    clickInsideElement,
    progressBar,
    dialog,
    bold,
    tooltip,
    button,
    infoCard,
    el,
    savingButtonOnClick,
    icons,
    adminPageURL
  } = Groundhogg.element
  const { post, get, patch, routes, ajax } = Groundhogg.api
  const {
    searches: SearchesStore,
    contacts: ContactsStore,
    tags: TagsStore,
    funnels: FunnelsStore,
    activity: ActivityStore,
    broadcasts: BroadcastsStore,
    events: EventsStore,
    emails: EmailsStore
  } = Groundhogg.stores
  const { tagPicker, funnelPicker } = Groundhogg.pickers
  const { userHasCap } = Groundhogg.user
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n
  const {
    InfoCardProvider
  } = Groundhogg.utils

  const { contact } = GroundhoggContact

  ContactsStore.itemsFetched([
    contact
  ])

  const simpleForm = (rows) => {

    const row = ({ label, input }) => {
      //language=HTML
      return `
		  <div class="gh-row">
			  <div class="gh-col">
				  <label class="row-label">
					  <b>${label}</b>
				  </label>
				  ${Array.isArray(input) ? `<div class="gh-input-group">${input.join('')}</div>` : input}
			  </div>
		  </div>`
    }

    //language=HTML
    return `
		<div class="gh-rows-and-columns">
			${rows.map(row).join('')}
		</div>`
  }

  const PrimaryInfoCards = InfoCardProvider({})
  const SecondaryInfoCards = InfoCardProvider({})

  PrimaryInfoCards.registerCard('contact-info', {
    title: () => {
      return __('Contact info', 'groundhogg')
    },
    content: ({ getContact }, { editing = false }) => {

      const { first_name, last_name, email = '' } = getContact().data
      const { primary_phone = '', primary_phone_extension = '', mobile_phone = '' } = getContact().meta

      const view = () => {

        // language=HTML
        return `
			<div class="space-between">
				<b>${__('Email')}</b><span>${email ? `<a href="mailto:${email}" class="send-email">${email}</a>` : '-'}</span>
			</div>
			<div class="space-between">
				<b>${__('Phone')}</b><span>${primary_phone ? `<a href="tel:${primary_phone}">${primary_phone} ${primary_phone_extension ? `x${primary_phone_extension}` : ''}</a>` : '-'}</span>
			</div>
			<div class="space-between">
				<b>${__('Mobile')}</b><span>${mobile_phone ? `<a href="tel:${mobile_phone}">${mobile_phone}</a>` : '-'}</span>
			</div>
			<p>
				<button class="gh-button secondary small" id="edit-contact-info">${__('Edit')}</button>
			</p>`
      }

      const edit = () => {

        // language=HTML
        return `${simpleForm([
			{
				label: __('Email Address'),
				input: input({
					name: 'email',
					id: 'edit-email',
					value: email,
					placeholder: __('john.doe@example.com', 'groundhogg')
				})
			},
			{
				label: __('Primary Phone'),
				// language=HTML
				input: `<div class="gh-input-group">${input({
					name: 'primary_phone',
					id: 'edit-primary-phone',
					value: primary_phone,
					placeholder: __('(555) 555-5555', 'groundhogg')
				})}${input({
					type: 'number',
					name: 'primary_phone_extension',
					id: 'edit-primary-phone-extension',
					value: primary_phone_extension,
					placeholder: __('Extension', 'groundhogg')
				})}</div>`
			},
			{
				label: __('Mobile Phone'),
				input: input({
					name: 'mobile_phone',
					id: 'edit-mobile-phone',
					value: mobile_phone,
					placeholder: __('(555) 555-5555', 'groundhogg')
				})
			}
		])}
		<p class="align-right-space-between">
			<button class="gh-button danger text small" id="cancel-contact-info">${__('Cancel')}</button>
			<button class="gh-button primary small" id="save-contact-info">${__('Save')}</button>
		</p>`
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: ({ getContact, updateContact }, { editing = false }, setState) => {

      if (!editing) {
        $('#edit-contact-info').on('click', () => setState({ editing: true }))
      } else {

        let data = {}
        let meta = {}

        $('#edit-email').on('change', ({ target }) => {
          data.email = target.value
        })

        $('#edit-primary-phone,#edit-primary-phone-extension,#edit-mobile-phone').on('change', ({ target }) => {
          meta[target.name] = target.value
        })

        savingButtonOnClick('#save-contact-info', () => {
          updateContact({
            data,
            meta
          }).then(() => setState({ editing: false }))
        })
      }

    },
    preload: ({ contact }) => {}
  })

  PrimaryInfoCards.registerCard('tags', {
    title: () => {
      return __('Tags', 'groundhogg')
    },
    content: ({ contact }, { editing = false }) => {

      // language=HTML
      return `${select({
		  id: 'edit-tags',
		  name: 'tags'
	  })}
	  <div class="tag-button-save-wrap hidden">
		  <p class=" align-right-space-between">
			  <button class="gh-button danger text small" id="cancel-tag-changes">${__('Cancel')}</button>
			  <button class="gh-button primary small" id="save-tag-changes">${__('Save')}</button>
		  </p>
	  </div>
      `
    },
    onMount: ({ getContact, updateContact }) => {

      let request

      const resetRequest = () => {
        request = {
          add_tags: [],
          remove_tags: [],
        }
      }

      resetRequest()

      const $p = $('.tag-button-save-wrap')

      const maybeShowBtn = () => {
        if (request.add_tags.length || request.remove_tags.length) {
          $p.show(0)
        } else {
          $p.hide(0)
        }
      }

      tagPicker('#edit-tags', true, (items) => TagsStore.itemsFetched(items), {
        width: '100%',
        data: [
          ...TagsStore.getItems().map(t => ({
            id: t.ID,
            text: t.data.tag_name,
            selected: getContact().tags.find(_t => _t.ID == t.ID)
          }))
        ]
      }).on('select2:select', ({ params }) => {

        let tag = params.data

        // If the tag is not already associated with the contact
        if (!getContact().tags.find(t => tag.id == t.ID)) {
          request.add_tags.push(parseInt(tag.id))
        }

        // If the tag was being removed, we no longer want to remove it
        request.remove_tags = request.remove_tags.filter(tId => tId != tag.id)

        maybeShowBtn()

      }).on('select2:unselect', ({ params }) => {

        let tag = params.data

        // If the tag is associated with the contact
        if (getContact().tags.find(t => tag.id == t.ID)) {
          request.remove_tags.push(parseInt(tag.id))
        }

        // If the tag was being added, we no longer want to add it
        request.add_tags = request.add_tags.filter(tId => tId != tag.id)

        maybeShowBtn()
      })

      savingButtonOnClick('#save-tag-changes', (release) => {

        updateContact(request).then(() => {
          release()
          resetRequest()
          maybeShowBtn()
        })
      })

    },
    preload: ({ contact }) => {
      TagsStore.itemsFetched(contact.tags)
    }
  })

  PrimaryInfoCards.registerCard('company-details', {
    title: () => {
      return __('Company', 'groundhogg')
    },
    content: ({ contact }, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: ({ contact }) => {
    },
    preload: ({ contact }) => {}
  })

  PrimaryInfoCards.registerCard('location', {
    title: () => {
      return __('Location', 'groundhogg')
    },
    content: ({ getContact }, { editing = false }) => {

      const {
        street_address_1 = '',
        street_address_2 = '',
        city = '',
        region = '',
        country = '',
        postal_zip = ''
      } = getContact().meta

      const edit = () => {
        // language=HTML
        return `${simpleForm([
          {
            label: __('Line 1', 'groundhogg'),
            input: input({
              name: 'street_address_1',
              id: 'edit-street-address-1',
              value: street_address_1
            })
          },
          {
            label: __('Line 2', 'groundhogg'),
            input: input({
              name: 'street_address_2',
              id: 'edit-street-address-2',
              value: street_address_2
            })
          },
        ])}`
      }

      const view = () => {
        // language=HTML
        return `
			<p><b>${__('Address', 'groundhogg')}</b></p>
			<div title="${__('Line 1', 'groundhogg')}">${street_address_1}</div>
			<div title="${__('Line 2', 'groundhogg')}">${street_address_2}</div>
			<div title="${__('City', 'groundhogg')}">${city}</div>
			<div title="${__('State', 'groundhogg')}">${region}</div>
			<div title="${__('Zip Code', 'groundhogg')}">${postal_zip}</div>
			<button class="gh-button small secondary" id="edit-location">${__('Edit')}</button>
        `
      }

      // language=HTML
      return `
		  <div id="contact-location">
			  ${editing ? edit() : view()}
		  </div>`
    },
    onMount: ({ contact, updateContact }, { editing = false }, setState) => {

      if (editing) {
        $('#save-location').on('click', () => setState({ editing: false }))
      } else {
        $('#edit-location').on('click', () => setState({ editing: true }))
      }

    },
    preload: (contact) => {}
  })

  PrimaryInfoCards.registerCard('compliance-details', {
    title: () => {
      return __('Compliance', 'groundhogg')
    },
    content: ({ contact, getContact }) => {

      const { marketing_consent_date, data_processing_consent_date, terms_agreement_date } = getContact().data

      const isRealDate = (d) => d !== '0000-00-00 00:00:00'

      // language=HTML
      return `
		  <div class="space-between">
			  <b>${__('Data processing consent')}</b><span>${isRealDate(data_processing_consent_date) ? formatDate(data_processing_consent_date) : '-'}</span>
		  </div>
		  <div class="space-between">
			  <b>${__('Marketing consent')}</b><span>${isRealDate(marketing_consent_date) ? formatDate(marketing_consent_date) : '-'}</span>
		  </div>
		  <div class="space-between">
			  <b>${__('Agreed to terms')}</b><span>${isRealDate(terms_agreement_date) ? formatDate(terms_agreement_date) : '-'}</span>
		  </div>`
    },
    onMount: ({ contact }) => {
    },
    preload: ({ contact }) => {}
  })

  SecondaryInfoCards.registerCard('user', {
    title: ({ data }) => {
      return __('WordPress User', 'groundhogg')
    },
    content: (contact, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: (contact) => {
    },
    preload: (contact) => {}
  })

  SecondaryInfoCards.registerCard('page_visits', {
    title: ({ data }) => {
      return __('Page Visits', 'groundhogg')
    },
    content: (contact, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: (contact) => {
    },
    preload: (contact) => {}
  })

  const ContactActions = {}

  const ActivityTimeline = {

    types: {
      wp_fusion: {
        icon: icons.wp_fusion,
        render: ({ data, meta }) => {
          const { event_name, event_value } = meta
          return `${event_name}: <code>${event_value}</code>`
        },
        preload: () => {}
      },
      wp_login: {
        icon: icons.login,
        render: ({ email }) => {
          return __('Logged in', 'groundhogg')
        },
        preload: () => {}
      },
      wp_logout: {
        icon: icons.logout,
        render: ({ email }) => {
          return __('Logged out', 'groundhogg')
        },
        preload: () => {}
      },
      email_opened: {
        icon: icons.open_email,
        render: ({ email }) => {
          return sprintf(__('Opened %s', 'groundhogg'), bold(email.data.title))
        },
        preload: ({ email }) => {
          EmailsStore.itemsFetched([email])
        }
      },
      email_link_click: {
        icon: icons.link_click,
        render: ({ email, data }) => {
          return sprintf(__('Clicked %s in %s', 'groundhogg'), el('a', {
            target: '_blank',
            href: data.referer,
          }, bold(data.referer)), bold(email.data.title))
        },
        preload: ({ email }) => {
          EmailsStore.itemsFetched([email])
        }
      }
    },

    renderActivity (activity) {

      if (activity.type === 'event' && activity.data.event_type == 1) {

        let { step } = activity

        // language=HTML
        return `
			<li class="activity-item">
				<div class="activity-icon ${step.data.step_group}">${StepTypes.getType(step.data.step_type).svg}</div>
				<div class="activity-rendered">
					<div class="activity-info">
						<div class="space-between" style="gap: 20px">
							<span>${sprintf(step.data.step_group === 'action' ? __('Completed action: %s', 'groundhogg') : __('Completed benchmark: %s', 'groundhogg'), StepTypes.getType(step.data.step_type).title(step))}</span>
							<button class="gh-button secondary icon text small event-more" data-event="${activity.ID}">
								${icons.verticalDots}
							</button>
						</div>
					</div>
					<div class="event-extra">
						${sprintf( __( 'in funnel %s', 'groundhogg' ), FunnelsStore.get(step.data.funnel_id).data.title )}
					</div>
					<div class="diff-time">
						${sprintf(__('%s ago', 'groundhogg'), activity.locale.diff_time)}
					</div>
				</div>
			</li>`
      }

      if (activity.type === 'event' && activity.data.event_type == 2) {

        // language=HTML
        return `
			<li class="activity-item">
				<div class="activity-icon broadcast">${icons.megaphone}</div>
				<div class="activity-rendered">
					<div class="activity-info">
						<div class="space-between" style="gap: 20px">
							<span>${sprintf(__('Received broadcast: %s', 'groundhogg'), bold(activity.broadcast.object.data.title))}</span>
							<button class="gh-button secondary icon text small event-more" data-event="${activity.ID}">
								${icons.verticalDots}
							</button>
						</div>
					</div>
					<div class="diff-time">
						${sprintf(__('%s ago', 'groundhogg'), activity.locale.diff_time)}
					</div>
				</div>
			</li>`
      }

      const type = this.types[activity.data.activity_type]

      // language=HTML
      return `
		  <li class="activity-item ${activity.data.activity_type} activity">
			  <div class="activity-icon ${activity.data.activity_type}">${type.icon}</div>
			  <div class="activity-rendered">
				  <div class="activity-info">
					  ${type.render(activity)}
				  </div>
				  <div class="diff-time">
					  ${sprintf(__('%s ago', 'groundhogg'), activity.locale.diff_time)}
				  </div>
			  </div>
		  </li>`
    },

    render (activities) {

      // language=HTML
      return `
		  <ul id="activity-timeline">
			  ${activities.map(a => this.renderActivity(a)).join('')}
		  </ul>`

    },

    onMount () {

      $('.event-more').on('click', (e) => {

        let eventId = e.currentTarget.dataset.event
        const event = EventsStore.get(eventId)

        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'run_again',
              text: __('Run Again')
            }
          ],
          onSelect: (key) => {
            switch (key) {
              case 'run_again':

                break
            }
          }
        })
      })
    },

    mount (selector, activities) {

      const $el = $(selector)

      // Only show supported activities
      activities = activities.filter(a => a.type === 'event' || a.data.activity_type in this.types)

      if (!activities.length) {
        $el.html(`<p>${__('No tracked activity yet', 'groundhogg')}</p>`)
        return
      }

      let promises = [
        ...activities
          .filter(a => a.type === 'activity')
          .map(a => this.types[a.data.activity_type].preload(a)),
        // Funnel Events
        ...activities
          .filter(a => a.type === 'event' && a.data.event_type == 1)
          .map(a => StepTypes.getType(a.step.step_type).preload(a.step)),
        // Funnels
        FunnelsStore.fetchItems({
          ID: activities
            .filter(a => a.type === 'event' && a.data.event_type == 1)
            .reduce((arr, e) => {

              if (!arr.includes(e.data.funnel_id)) {
                arr.push(e.data.funnel_id)
              }

              return arr
            }, [])
        }),
        // Broadcast Events
        ...activities
          .filter(a => a.type === 'event' && a.data.event_type == 2)
          .map(a => BroadcastsStore.itemsFetched([a.broadcast])),
      ]

      Promise.all(promises).then(() => {
        $el.html(this.render(activities))
        this.onMount()
      })

    }

  }

  const ContactRecord = {

    editingBasicDetails: false,

    contact,

    /**
     * Render the contact record HTML
     *
     * @returns {string}
     */
    render () {

      const header = () => {
        return `
         <div class="gh-header space-between">
				  <div class="title-wrap">
					  <h1 class="breadcrumbs">
						  ${breadcrumbs([
          __('Contacts', 'groundhogg'),
          `<div class="space-between"><img width="30" height="30" alt="profile picture" src="${contact.data.gravatar}"/> ${contact.data.full_name}</div>`
        ])}
					  </h1>
				  </div>
				  <div class="actions">

				  </div>
			  </div>`
      }

      const editBasicDetails = () => {

        let { first_name, last_name, email, optin_status } = this.contact.data

        // language=HTML
        return `
			<div class="inside" style="padding: 12px">
				${simpleForm([
					{
						label: __('Name'),
						// language=HTML
						input: [
							input({
								value: first_name,
								name: 'first_name',
								id: 'edit-first-name',
								placeholder: __('First', 'groundhogg')
							}),
							input({
								value: last_name,
								name: 'last_name',
								id: 'edit-last-name',
								placeholder: __('Last', 'groundhogg')
							})
						]
					},
					{
						label: __('Email address', 'groundhogg'),
						input: input({
							name: 'email',
							id: 'edit-email-primary',
							value: email
						})
					},
					{
						label: __('Optin Status', 'groundhogg'),
						input: select({
							name: 'optin_status',
							id: 'edit-optin-status',
						}, Groundhogg.filters.optin_status, optin_status)
					}
				])}
				<p class="align-right-space-between">
					<button class="gh-button danger text small" id="cancel-basic-details">${__('Cancel')}</button>
					<button class="gh-button primary small" id="save-basic-details">${__('Save')}</button>
				</p>
			</div>`
      }

      const viewBasicDetails = () => {
        // language=HTML
        return `<img class="avatar" width="100" height="100" alt="profile picture"
		             src="${contact.data.gravatar}"/>
		<div class="full-name">${contact.data.full_name}</div>
		<div class="email-address">${contact.data.email}</div>
		<div class="contact-actions">
			<button class="gh-button secondary text icon">${icons.email}</button>
			<button class="gh-button secondary text icon">${icons.phone}</button>
			<button class="gh-button secondary text icon">${icons.note}</button>
			<button class="gh-button secondary text icon">${icons.verticalDots}</button>
		</div>`
      }

      // language=HTML
      return `
		  <div id="contact-record">
			  <div class="space-between" id="contact-stuff">
				  <div class="gh-panel" id="general-details">
					  ${this.editingBasicDetails ? editBasicDetails() : viewBasicDetails()}
					  <div id="contact-primary-info-cards"></div>
				  </div>
				  <div id="main-stuff">

				  </div>
				  <div class="gh-panel" id="info-cards">
					  <h2 class="additional">${__('Additional info', 'groundhogg')}</h2>
					  <div id="more-info-cards">

					  </div>
				  </div>
			  </div>
		  </div>`
    },

    /**
     * Add contact editor to the dom
     */
    mount () {
      $('#app').html(this.render())
      this.onMount()
    },

    /**
     * Add event listeners
     */
    onMount () {

      let contactChanges = {}

      const updateContact = (data, r = false) => {
        return ContactsStore.patch(contact.ID, data).then((c) => {

          dialog({
            message: __('Contact updated', 'groundhogg')
          })

          if (r) {
            this.mount()
          }

          this.contact = c

          return c
        })
      }

      const getContact = () => {
        return ContactsStore.get(contact.ID)
      }

      $('.full-name, .email-address').on('click', () => {
        this.editingBasicDetails = true
        this.mount()
      })

      PrimaryInfoCards.mount('#contact-primary-info-cards', { contact, getContact, updateContact })
      SecondaryInfoCards.mount('#more-info-cards', { contact, getContact, updateContact })

      tabs('#main-stuff', {
        tabClassName: 'tab',
        contentClassName: 'gh-panel has-tabs',
        tabWrapClassName: 'gh-panel-tabs',
        curTab: this.curTab ?? 'notes',
        onTabbed: (tab) => this.curTab = tab,
        tabs: [
          {
            id: 'notes',
            name: __('Notes', 'groundhogg'),
            content: () => {
              return `<div id="notes-here" class="inside"></div>`
            },
            onMount: () => {
              Groundhogg.noteEditor('#notes-here', {
                object_id: contact.ID,
                object_type: 'contact',
                title: '',
              })
            }
          },
          {
            id: 'activity',
            name: __('Activity', 'groundhogg'),
            content: () => {
              return `<div id="activity-here" class="inside"></div>`
            },
            onMount: () => {

              Promise.all([
                ActivityStore.fetchItems({
                  contact_id: this.contact.ID,
                  limit: 50
                }),
                EventsStore.fetchItems({
                  contact_id: this.contact.ID,
                  limit: 50,
                  orderby: 'time',
                  order: 'DESC'
                })
              ]).then(() => {

                let allActivities = [
                  ...ActivityStore.getItems().map(a => ({
                    ...a,
                    type: 'activity',
                    time: parseInt(a.data.timestamp)
                  })),
                  ...EventsStore.getItems().map(e => ({
                    ...e,
                    type: 'event',
                    time: parseInt(e.data.time) + parseFloat(e.data.micro_time)
                  }))
                ].sort((a, b) => b.time - a.time)

                ActivityTimeline.mount('#activity-here', allActivities)

              })

            }
          },
          {
            id: 'emails',
            name: __('Emails', 'groundhogg'),
            content: () => {
              return `<div id="emails-here" class="inside"></div>`
            },
            onMount: () => {

            }
          },
        ]
      })
    },
  }

  $(() => {ContactRecord.mount()})

})(jQuery)