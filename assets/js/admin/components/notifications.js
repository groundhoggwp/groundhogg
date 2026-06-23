( ($) => {

  const { ajax } = Groundhogg.api
  const {
    __,
  } = wp.i18n

  const {
    Div,
    H2,
    An,
    Button,
    Dashicon,
    ToolTip,
    Fragment,
    Skeleton,
    Pg,
  } = MakeEl

  const State = Groundhogg.createState({
    show         : 'active',
    dismissed    : [],
    notifications: [],
    read         : false,
    loaded       : false,
    is_expanded  : new Groundhogg.TokenList(),
    unread       : 3,
  })

  if (typeof GroundhoggNotifications !== 'undefined') {

    let { unread = 3, dismissed_notices = [] } = GroundhoggNotifications

    if ( ! unread ){
      unread = 3
    }

    State.set({
      dismissed: dismissed_notices,
      unread
    })
  }

  /**
   * Mark all notifications as read
   * @returns {*}
   */
  const readAllNotifications = () => {

    // clear the class from the UI as well
    document.querySelectorAll( '.gh-has-notifications.unread-notices' ).forEach(el => el.classList.remove('unread-notices', 'gh-has-notifications') )

    return ajax({
      action: 'gh_read_notice',
      notice: State.notifications.map(n => n.id),
    })
  }

  /**
   * Fetch remote notifications
   * @returns {*}
   */
  const fetchNotifications = () => ajax({
    action: 'gh_remote_notifications',
  }).then(notifications => {
    State.set({ notifications, loaded: true })
    return notifications
  })

  /**
   * Dismiss a notification
   * @param id
   * @returns {*}
   */
  const dismissNotification = id => {

    State.set({
      dismissed: [
        ...State.dismissed,
        id,
      ],
    })

    return ajax({
      action: 'gh_dismiss_notice',
      notice: id,
    })
  }

  const Notification = ({
    id,
    title,
    content,
    sent,
    morph,
  }) => Div({
    id       : `n-${ id }`,
    className: `gh-panel outlined ${ State.is_expanded.contains( id ) ? '' : 'collapsed' }`,
  }, [
    Div({
      className: 'gh-panel-header',
    }, [
      H2({}, [
        title,
        '<br>',
        MakeEl.makeEl( 'abbr', {title: 'sent on', style: {
          fontSize: '12px',
          fontWeight: '400'
        }}, sent )
      ]),
      State.show === 'active' ? Button({
        id       : `dismiss-${ id }`,
        className: 'gh-button dismiss small',
        onClick  : e => {
          dismissNotification(id)
          morph()
        },
      }, [
        Dashicon('no-alt'),
        ToolTip('Dismiss', 'left'),
      ]) : null,
    ]),
    Div({
      className: 'inside',
    }, [
      content,
    ]),
    An({
      type: 'button',
      className: 'show-more',
      href : `#n-${ id }`,
      id: `#expand-${id}`,
      onClick: e => {
        e.preventDefault()
        State.is_expanded.toggle(id)
        morph()
      }
    },  State.is_expanded.contains( id ) ? 'See less' : 'Read more...' )
  ])

  const Notifications = ({ id = 'remote-notifications'}) => Div({
    id,
    className: 'remote-notifications',
  }, morph => {

    if (!State.loaded) {
      fetchNotifications().then(() => morph())
      return Skeleton({
        cellAttributes: {
          style: {
            height: '200px',
            borderRadius: '8px'
          }
        },
        className: 'display-grid gap-10',
      }, Array( State.unread ).fill('full' ) )
    }

    let notifications, button

    switch (State.show) {
      case 'all':
        notifications = State.notifications
        break
      default:
      case 'active':
        notifications = State.notifications.filter(({ id }) => !State.dismissed.includes(id))

        if (!State.read) {
          readAllNotifications().then(() => State.set({ read: true }))
        }
        break
    }

    return Fragment([
      ...notifications.map(n => Notification({
        ...n,
        morph,
      })),
      notifications.length === 0 ? Pg({
        style: {
          textAlign: 'center',
          fontSize : '18px',
        },
      }, __('You\'re all caught up! 🥳')) : null,
      State.show === 'all' || State.dismissed.length ? Div({
        className: 'display-flex center',
      }, Button({
        id       : 'change-state',
        className: 'gh-button secondary text',
        onClick  : e => {
          State.set({
            show: State.show === 'all' ? 'active' : 'all',
          })
          morph()
        },
      }, State.show === 'active' ? __('See all') : __('See active'))) : null,
    ])
  })

  const NotificationsSidebar = () => MakeEl.Sidebar({
    header: '🔔 Notifications',
    onClose: () => {
      // clear the fragment
      history.pushState(
        {},
        document.title,
        window.location.pathname + window.location.search
      );
    }
  }, [
    Groundhogg.Notifications({
      id: 'sidebar-notifications',
    })
  ])

  Groundhogg.Notifications = Notifications
  Groundhogg.NotificationsSidebar = NotificationsSidebar

  const handleHashChange = () => {
    let hash = window.location.hash.replace('#', '')
    if ( ! hash.startsWith('gh-show-notifications') ) {
      return
    }

    NotificationsSidebar()
  }

  window.addEventListener('hashchange', handleHashChange )
  window.addEventListener('load', handleHashChange )

} )(jQuery)
