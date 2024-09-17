( function ($) {

  const classPrefix = 'groundhogg-toolbar-quick-search'

  const {
    input,
    tooltip,
    clickedIn,
    icons,
    adminPageURL,
    spinner,
  } = Groundhogg.element

  const { quickAddForm } = Groundhogg.components

  const { userHasCap } = Groundhogg.user
  const { sprintf, __, _x, _n } = wp.i18n
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting

  const { isWhiteLabeled } = Groundhogg

  const {
    contacts: ContactsStore,
  } = Groundhogg.stores

  const Tabs = {
    search_contacts: {
      tooltip: `Search for contacts`,
      cap: 'view_contacts',
      // language=HTML
      svg: icons.contactSearch,
      view: () => {

        // language=HTML
        return `
            <div id="quick-search">
            </div>`
      },
      onMount: () => {
        morphdom( document.getElementById( 'quick-search' ), Groundhogg.components.QuickSearch() )
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
            <div id="gh-admin-bar-send-broadcast"></div>`
      },
      onMount: ({ setTab }) => {
        document.getElementById('gh-admin-bar-send-broadcast').append(Groundhogg.BroadcastScheduler())
      },
    },
  }

  if (!isWhiteLabeled) {

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
        morphdom( document.getElementById( 'gh-notifications' ), Groundhogg.Notifications() )
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
