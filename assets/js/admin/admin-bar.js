( function ($) {

  const classPrefix = 'groundhogg-toolbar-quick-search'

  const {
    moreMenu,
    input,
    select,
    tooltip,
    loadingDots,
    isValidEmail,
    dialog,
    errorDialog,
    clickedIn,
    icons,
    adminPageURL,
    spinner,
  } = Groundhogg.element

  const { quickAddForm } = Groundhogg.components

  const { userHasCap } = Groundhogg.user
  const { sprintf, __, _x, _n } = wp.i18n
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting

  const { tagPicker } = Groundhogg.pickers
  const { ajax } = Groundhogg.api
  const { isWhiteLabeled } = Groundhogg

  const {
    contacts: ContactsStore,
    options: OptionsStore,
  } = Groundhogg.stores

  const doReplacements = (content) => {

    const replacements = {
      admin: Groundhogg.url.admin,
      home: Groundhogg.url.home,
      name: Groundhogg.name,
      display_name: Groundhogg.currentUser.data.display_name,
    }

    Object.keys(replacements).forEach(s => {

      let regex = new RegExp(`#${s}#`, 'g')
      content = content.replaceAll(regex, replacements[s])
    })

    return content
  }

  const Tabs = {
    search_contacts: {
      tooltip: `Search for contacts`,
      cap: 'view_contacts',
      // language=HTML
      svg: icons.contactSearch,
      view: () => {

        // language=HTML
        return `
            <div id="quick-search-wrap">
                ${ input({
                    type: 'search',
                    id: 'quick-search-input',
                    placeholder: __('Search by name or email...', 'groundhogg'),
                }) }
                <div class="${ classPrefix }-results"></div>
            </div>`
      },
      onMount: () => {

        const mountSearchResults = (items, search) => {
          $(`.${ classPrefix }-results`).replaceWith(renderSearchResults(items, search))

          tooltip(`.${ classPrefix }-result .edit-profile`, {
            content: __('Edit profile', 'groundhogg'),
            position: 'top',
          })

          tooltip(`.${ classPrefix }-result .send-email`, {
            content: __('Send email', 'groundhogg'),
            position: 'top',
          })

          tooltip(`.${ classPrefix }-result .call-primary`, {
            content: __('Call', 'groundhogg'),
            position: 'top',
          })

          tooltip(`.${ classPrefix }-result .call-mobile`, {
            content: __('Call mobile', 'groundhogg'),
            position: 'top',
          })

          $(`.${ classPrefix }-result`).on('click', (e) => {

            const ID = parseInt(e.currentTarget.dataset.contact)
            const contact = ContactsStore.get(ID)

            if (clickedIn(e, '.email-contact')) {

              e.preventDefault()

              window.location.href = contact.admin + '&send_email=true'

              return
            }

            if (clickedIn(e, '.call-primary')) {
              return
            }

            if (clickedIn(e, '.call-mobile')) {
              return
            }

            window.location.href = contact.admin
          })
        }

        const renderSearchResult = (item) => {

          let phones = []
          let actions = [
            // language=html
            `
                <button class="gh-button secondary text icon edit-profile">
                    ${ icons.contact }
                </button>`,
            // language=html
            `<a class="email-contact gh-button secondary text icon send-email"
                href="mailto:${ item.data.email }" data-id=${ item.ID } target="_blank">
                ${ icons.email }
            </a>`,
          ]

          if (item.meta.primary_phone) {
            phones.push(`<span title="primary phone">${item.meta.primary_phone}</span>`)

            // language=html
            actions.push(`<a class="gh-button secondary text icon call-primary" href="tel:${ item.meta.primary_phone }">
                ${ icons.phone }
            </a>`)
          }

          if (item.meta.mobile_phone) {
            phones.push(`<span title="mobile phone">${item.meta.mobile_phone}</span>`)
            // language=html
            actions.push(`<a class="gh-button secondary text icon call-mobile" href="tel:${ item.meta.mobile_phone }">
                ${ icons.mobile }
            </a>`)
          }

          let allTags = item.tags
          let showTags = allTags.splice(0, 10 )

          //language=HTML
          return `
              <div id="search-result-${ item.ID }" data-contact="${ item.ID }" class="${ classPrefix }-result">
                  <div class="above">
                      <img class="avatar" src="${ item.data.gravatar }" alt="avatar"/>
                      <div class="details">
                          <div class="name">${ item.data.first_name } ${ item.data.last_name } <span class="subscribed">— ${ sprintf(
                                  __('Subscribed %s'),
                                  `<abbr title="${ formatDateTime(item.data.date_created) }">${ sprintf(__('%s ago '),
                                          item.locale.created) }</abbr>`) }</span></div>
                          <div class="email">${ item.data.email } — <span class="gh-text ${ item.is_marketable
                                  ? 'green'
                                  : 'red' }"><b>${ Groundhogg.filters.optin_status[item.data.optin_status] }</b></span>
                          </div>
                          ${ ! phones.length ? '' : `<div class="phones">${phones.join('')}</div>` }
                      </div>
                      <div class="actions">
                          ${ actions.join('') }
                      </div>
                  </div>
                  <div class="gh-tags">
                      ${ showTags.map(t => `<span class="gh-tag">${ t.data.tag_name }</span>`).join('') }
                      ${ allTags.length ? `<span class="gh-tag">${ sprintf(__('%d more...', 'groundhogg'),
                              allTags.length) }</span>` : '' }
                  </div>
              </div>`
        }

        const renderSearchResults = (items = [], search) => {
          if (!items || items.length === 0) {
            //language=HTML
            return `
                <div class="${ classPrefix }-results">
                    <p>
                        ${ __('No contacts found for the current search', 'groundhogg') }
                    </p>
                </div>`
          }

          const viewAllContacts = () => {

            let moreItems = ContactsStore.getTotalItems() - items.length
            //language=HTML
            return `<p style="text-align: center"><a
                    href="${ adminPageURL('gh_contacts', { s: search }) }">${ sprintf(
                    _n('See %s more contact', 'See %s more contacts', moreItems), formatNumber(moreItems)) }</a>
            </p>`
          }

          //language=HTML
          return `
              <div class="${ classPrefix }-results">
                  ${ items.map(item => renderSearchResult(item)).join('') }
                  ${ ContactsStore.getTotalItems() > items.length ? viewAllContacts() : '' }
              </div>`
        }

        let timeout

        $('#quick-search-input').on('input', ({ target }) => {

          if (timeout) {
            clearTimeout(timeout)
          }
          else {
            $(`.${ classPrefix }-results`).html(spinner('gray'))
          }

          timeout = setTimeout(() => {

            ContactsStore.fetchItems({
              search: target.value,
              orderby: 'date_created',
              order: 'DESC',
              limit: 5,
            }).then(items => {
              mountSearchResults(items, target.value)
              timeout = null
            })
          }, 1000)
        }).focus()

      },
    },
    create_contact: {
      cap: 'add_contacts',
      tooltip: `Create a contact`,
      // language=HTML
      svg: icons.createContact,
      view: () => {

        // language=HTML
        return `
            <div id="admin-quick-add">

            </div>`
      },
      onMount: () => {

        quickAddForm('#admin-quick-add', {
          prefix: 'admin-quick-add',
          onCreate: (c) => {
            window.location.href = c.admin
          },
        })

      },
    },
    broadcast: {
      cap: 'schedule_broadcasts',
      //language=HTML
      tooltip: `Send a broadcast`,
      svg: icons.megaphone,
      view: () => {
        // language=HTML
        return `
            <div id="send-broadcast"></div>`
      },
      onMount: ({ setTab }) => {
        Groundhogg.SendBroadcast('#send-broadcast', {}, {
          onScheduled: () => {
            setTab('broadcast')
          },
        })
      },
    },
    tasks: {
      cap: 'view_tasks',
      //language=HTML
      tooltip: 'My tasks',
      svg: icons.tasks,
      view: () => {
        // language=HTML
        return `
            <div id="admin-tasks"></div>`
      },
      onMount: ({ setTab }) => {
        Groundhogg.myTasks('#admin-tasks', {})
      },
    },
  }

  if (!isWhiteLabeled) {

    const Notifications = {
      state: 'showActive',
      $el: false,

      showAll () {

        let html = this.getAll().map(n => this.renderNotification(n, {
          showDismiss: false,
        }))

        html.push(`<p class="all-dismissed"><a href="#" id="show-active">${ __('See active...') }</a></p>`)

        this.$el.html(html.join(''))

        $('#show-active').on('click', e => {
          e.preventDefault()
          this.setState('showActive')
        })

      },
      showActive () {

        // set to all dismissedd
        if (!this.getActive().length) {
          return this.setState('allDismissed')
        }

        let html = this.getActive().map(n => this.renderNotification(n))

        html.push(`<p class="all-dismissed"><a href="#" id="show-all">${ __('See all...') }</a></p>`)

        this.$el.html(html.join(''))

        this.mountNotifications(() => {
          if (!this.getActive().length) {
            this.setState('allDismissed')
          }
        })

        $('#show-all').on('click', e => {
          e.preventDefault()
          this.setState('showAll')
        })

        setTimeout(() => {
          this.readAll().then(() => {
            // Remove unread notice
            $('.unread-notices.gh-has-notifications').removeClass('unread-notices gh-has-notifications')
          })
        }, 3000)
      },
      allDismissed () {

        let html = []

        html.push(`<p class="all-dismissed">${ __('🙌 There are no new notifications.') }</p>`)

        // but there are some to be seen
        if (Notifications.getAll().length) {
          html.push(
            `<p class="all-dismissed"><a href="#" id="show-all" ">${ __('See dismissed...') }</a></p>`)
        }

        this.$el.html(html.join(''))

        $('#show-all').on('click', e => {
          e.preventDefault()
          this.setState('showAll')
        })

      },

      setState (state) {
        this.state = state
        this.mount()
      },

      async mount ($el = false) {

        if ($el !== false) {
          this.$el = $el
        }

        await this.fetch()

        this[this.state]()
      },

      notifications: [],
      dismissed: GroundhoggToolbar.dismissed_notices,
      read: GroundhoggToolbar.read_notices,

      readAll () {
        return ajax({
          action: 'gh_read_notice',
          notice: this.notifications.map(n => n.id),
          _wpnonce: groundhogg_nonces._wpnonce,
        })
      },

      dismiss (id) {
        this.dismissed.push(id)

        return ajax({
          action: 'gh_dismiss_notice',
          notice: id,
          _wpnonce: groundhogg_nonces._wpnonce,
        })
      },

      fetch () {

        if (this.notifications.length) {
          return Promise.resolve(this.notifications)
        }

        return ajax({
          action: 'gh_remote_notifications',
        }).
        then(notifications => {
          this.notifications = notifications
          return notifications
        })
      },

      getAll () {
        return this.notifications
      },

      getUnread () {
        return this.notifications.filter(n => !this.read.includes(n.id))
      },

      getActive () {
        return this.notifications.filter(n => !this.dismissed.includes(n.id))
      },

      renderNotification: (n, {
        showDismiss = true,
      } = {}) => {

        const {
          id,
          title,
          content,
          acf,
        } = n

        // language=HTML
        return `
          <div id="n-${ id }" class="gh-panel outlined overflow-visible">
              <div class="gh-panel-header">
                  <h2>${ doReplacements(title.rendered) }</h2>
                  ${ !showDismiss ? '' : `<button class="gh-button dismiss small" data-id="${ id }">
                      <span class="dashicons dashicons-no-alt"></span>
                  </button>` }
              </div>
              <div class="inside">
                  <div class="content">${ doReplacements(content.rendered) }</div>
                  ${ !acf.cta_text ? '' : `<div class="actions">
                      <a href="${ doReplacements(acf.cta_url) }" target="_blank"
                         class="gh-button primary small">${ doReplacements(acf.cta_text) }</a>
                  </div>` }
              </div>
          </div>`
      },

      mountNotifications: (dismissed = () => {}) => {
        tooltip('.gh-button.dismiss', {
          content: 'Dismiss',
          position: 'right',
        })

        $('.gh-button.dismiss').click(e => {

          let nId = parseInt(e.currentTarget.dataset.id)

          const n = $(`#n-${ nId }`)
          Notifications.dismiss(nId)
          n.fadeOut({
            complete: () => {
              n.remove()
              dismissed()
            },
          })
        })
      },
    }

    Tabs.notifications = {
      cap: 'manage_options',
      //language=HTML
      tooltip: `Notifications`,
      svg: icons.bell,
      view: () => {
        // language=HTML
        return `
            <div id="gh-notifications">
                ${ spinner('gray') }
            </div>`
      },
      onMount: ({ setTab, remMount }) => {
        Notifications.mount($('#gh-notifications'))
      },
    }


  }

  $(() => {

    const $menuItem = $('#wp-admin-bar-groundhogg')

    let openFlag = false
    let tab = 'search_contacts'

    if (!isWhiteLabeled && Tabs.hasOwnProperty('notifications') && GroundhoggToolbar.unread > 0) {
      tab = 'notifications'
    }

    const close = () => {
      openFlag = false
      $('#groundhogg-toolbar-quick-search').remove()
      $('body').removeClass('groundhogg-toolbar-quick-search-open')
    }

    $menuItem.on('click', (e) => {

      if (openFlag) {
        close()
        return
      }

      openFlag = true

      const { right, bottom } = e.currentTarget.getBoundingClientRect()

      const renderTabs = () => {

        const renderTab = (t, { svg, cap }) => {

          if (!userHasCap(cap)) {
            return ''
          }

          //language=HTML
          return `
              <button id="gh-tab-${ t }" data-tab=${ t }
                      class="${ classPrefix }-tab-button gh-button text ${ tab === t ? 'primary' : 'secondary' } icon">
                  ${ svg }
              </button>`
        }
        //language=HTML
        return `
            <div class="${ classPrefix }-tabs">
                ${ Object.keys(Tabs).map(t => renderTab(t, Tabs[t])).join('') }
            </div>`
      }

      const renderQuickSearch = () => {
        // language=HTML
        return `
            <div id="groundhogg-toolbar-quick-search" class="${ classPrefix }" tabindex="0"></div>`
      }

      const mountQuickSearch = () => {

        // language=html
        const html = `
            <button type="button" class="dashicon-button ${ classPrefix }-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
            ${ renderTabs() }
            ${ userHasCap(Tabs[tab].cap) ? Tabs[tab].view() : '' }`

        $quickSearch.html(html)

        const setTab = (t) => {
          tab = t
          mountQuickSearch()
        }

        $(`.${ classPrefix }-tab-button`).on('click', ({ currentTarget }) => {
          setTab(currentTarget.dataset.tab)
        })

        Tabs[tab].onMount({ setTab, reMount: mountQuickSearch })

        Object.keys(Tabs).forEach(t => tooltip(`#gh-tab-${ t }`, {
          content: Tabs[t].tooltip,
          position: 'bottom',
        }))

        $quickSearch.css({
          top: Math.min(bottom, window.innerHeight - $quickSearch.height() - 20),
          left: ( right - $quickSearch.outerWidth() ),
        })

        $(`.${ classPrefix }-close`).on('click', () => {
          close()
        })

        if (!isWhiteLabeled && GroundhoggToolbar.unread > 0) {
          $('#gh-tab-notifications').addClass('unread-notices gh-has-notifications')
        }
      }

      const $quickSearch = $(renderQuickSearch())

      $('body').append($quickSearch).addClass('groundhogg-toolbar-quick-search-open')

      $quickSearch.on('keydown', ({ key }) => {
        switch (key) {
          case 'Esc':
          case 'Escape':
            close()
            break
        }
      })

      mountQuickSearch()

    })

    // Notices
    if (!isWhiteLabeled && GroundhoggToolbar.unread > 0) {
      $menuItem.addClass('unread-notices gh-has-notifications')
    }
  })
} )(jQuery)
