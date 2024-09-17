( ($) => {

  const {
    spinner,
  } = Groundhogg.element

  const { ajax } = Groundhogg.api
  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n

  const doReplacements = (content) => {

    const replacements = {
      admin       : Groundhogg.url.admin,
      home        : Groundhogg.url.home,
      name        : Groundhogg.name,
      display_name: Groundhogg.currentUser.data.display_name,
    }

    Object.keys(replacements).forEach(s => {

      let regex = new RegExp(`#${s}#`, 'g')
      content = content.replaceAll(regex, replacements[s])
    })

    return content
  }

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
  })

  if (typeof GroundhoggNotifications !== 'undefined') {
    State.set({
      dismissed: GroundhoggNotifications.dismissed_notices,
    })
  }

  /**
   * Mark all notifications as read
   * @returns {*}
   */
  const readAllNotifications = () => ajax({
    action: 'gh_read_notice',
    notice: State.notifications.map(n => n.id),
  })

  /**
   * Fetch remote notifications
   * @returns {*}
   */
  const fetchNotifications = () => ajax({
    action: 'gh_remote_notifications',
  }).then(notifications => {
    State.set({ notifications })
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
    acf,
    morph,
  }) => Div({
    id       : `n-${ id }`,
    className: 'gh-panel outlined overflow-visible',
  }, [
    Div({
      className: 'gh-panel-header',
    }, [
      H2({}, title.rendered),
      State.show === 'active' ? Button({
        id       : `dismiss-${ id }`,
        className: 'gh-button dismiss small',
        onClick  : e => {
          dismissNotification(id)
          morph()
        },
      }, [
        Dashicon('no-alt'),
        ToolTip('Dismiss', 'right'),
      ]) : null,
    ]),
    Div({
      className: 'inside',
    }, [
      doReplacements(content.rendered),
      acf.cta_text ? An({
        className: 'gh-button primary small',
        href     : doReplacements(acf.cta_url),
        target   : '_blank',
      }, doReplacements(acf.cta_text)) : null,
    ]),
  ])

  const Notifications = () => Div({
    id       : 'remote-notifications',
    className: 'notifications',
  }, morph => {

    if (!State.notifications.length) {
      fetchNotifications().then(() => morph())
      return Skeleton({}, [
        'full',
        'full',
        'full',
      ])
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

  Groundhogg.Notifications = Notifications

} )(jQuery)
