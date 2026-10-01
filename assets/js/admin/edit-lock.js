( $ => {

  const {
    makeEl,
    Div,
    Button,
    Dashicon,
    Modal
  } = MakeEl

  const { __, _x, _n, _nx, sprintf } = wp.i18n

  const ExitButton = () => makeEl('a', {
    className: 'gh-button primary',
    href: GhLockData.exit
  }, __( 'Exit editor' ) )

  const Avatar = src =>  makeEl( 'img', {
    src,
    width: 64,
    height: 64,
    style: {
      borderRadius: '3px'
    }
  } )

  const TakeOverDialog = ( {
    name,
    avatar_src,
    take_over = null
  } ) => Modal({
    closeButton: false,
    closeOnOverlayClick: false,
    width: '500px'
  }, () => Div({
    className: 'post-locked'
  }, [
    `<h2>${__('Someone else is editing this asset.', 'groundhogg')}</h2>`,
    Div({
      className: 'display-flex gap-20'
    }, [
      Avatar( avatar_src ),
      Div({}, [
        /* translators: %s: the name of a user */
        `<p style="margin-top: 0">${ sprintf( __( '%s is currently working on this asset, which means you cannot make any changes, unless you take over.', 'groundhogg' ), `<b>${name}</b>` ) }</p>`,
        `<p>${ __( 'If you take over, the other user will lose editing control of this asset.', 'groundhogg' ) }</p>`,
      ])
    ]),
    Div({
      className: 'display-flex flex-end gap-10'
    }, [
      take_over ? makeEl('a', {
        className: 'gh-button primary text',
        href: take_over
      }, __( 'Take over', 'groundhogg' ) ) : null,
      ExitButton(),
    ])
  ]))

  const TakenOverDialog = ( {
    name,
    avatar_src,
  } ) => Modal({
    closeButton: false,
    closeOnOverlayClick: false,
    width: '500px'
  }, () => Div({
    className: 'post-locked'
  }, [
    `<h2>${__('Someone else has taken over this asset.', 'groundhogg')}</h2>`,
    Div({
      className: 'display-flex gap-20'
    }, [
      Avatar( avatar_src ),
      Div({}, [
        /* translators: %s: the name of a user */
        `<p style="margin-top: 0">${ sprintf( __( '%s now has editing control of this asset.', 'groundhogg' ), `<b>${name}</b>` ) }</p>`,
      ])
    ]),
    Div({
      className: 'display-flex flex-end'
    }, [
      ExitButton(),
    ])
  ]))

  /**
   * The version of the object this page has, which an editor can keep up to date with its own saves by setting
   * GhLockData.getVersion, or GhLockData.resync() after a save it can't tell the version of
   *
   * @return {string}
   */
  const currentVersion = () => typeof GhLockData.getVersion === 'function' ? GhLockData.getVersion() : GhLockData.version

  let changedShown = false
  let hasBaseline = false

  /**
   * Says that something else changed the object while it's open, like an ability, see maybe_refresh_lock()
   */
  const ChangedNotice = () => {

    changedShown = true

    const notice = Div({
      id       : 'gh-edit-lock-changed',
      className: 'gh-panel display-flex align-center gap-10',
      style    : {
        position : 'fixed',
        bottom   : '20px',
        left     : '50%',
        transform: 'translateX(-50%)',
        zIndex   : 100000,
        padding  : '10px 10px 10px 20px',
      },
    }, [
      makeEl('span', {}, GhLockData.changed_text),
      Button({
        className: 'gh-button primary small',
        onClick  : () => window.location.reload(),
      }, __('Reload', 'groundhogg')),
      Button({
        className: 'gh-button secondary text icon small',
        onClick  : () => notice.remove(),
      }, Dashicon('no-alt')),
    ])

    document.body.append(notice)
  }

  window.wp.heartbeat.interval( 30 )

  $(()=>{

    // the next heartbeat's version is the editor's own, after it saved, GhLockData is printed after this script
    GhLockData.resync = () => {
      GhLockData.version = ''
      window.wp.heartbeat.connectNow()
    }

    const { lock_error = null } = GhLockData

    if ( ! lock_error ){
      // the version to compare with, see maybeShowChanged()
      if ( GhLockData.version !== undefined ) {
        window.wp.heartbeat.connectNow()
      }
      return
    }

    TakeOverDialog( lock_error )
  })

  // release the lock when the editor closes, so others don't have to wait for it to expire
  window.addEventListener('pagehide', () => {

    const { id, type, lock = '', lock_error = null, remove_nonce = '' } = GhLockData

    if (lock_error || !lock || !remove_nonce) {
      return
    }

    const data = new FormData()
    data.append('action', 'groundhogg_remove_lock')
    data.append('id', id)
    data.append('type', type)
    data.append('_wpnonce', remove_nonce)

    navigator.sendBeacon(ajaxurl, data)
  })

  // refresh the lock
  $(document).on('heartbeat-send.groundhogg-refresh-lock', function (event, data) {

    const { id, type, lock = '', lock_error = null } = GhLockData

    const send = {}

    // Don't refresh lock if there is a lock error
    if (lock_error || !id || !type) {
      return
    }

    send.id = id
    send.type = type

    if ( lock ){
      send.lock = lock
    }

    if ( GhLockData.version !== undefined && !changedShown ) {
      send.version = currentVersion() || ''
    }

    data['groundhogg-refresh-lock'] = send

  }).on('heartbeat-tick.groundhogg-refresh-lock', function (e, data) {

    // Post locks: update the lock string or show the dialog if somebody has taken over editing.
    let received;

    if ( data['groundhogg-refresh-lock'] ) {
      received = data['groundhogg-refresh-lock'];

      if ( received.lock_error ) {

        // Add lock error to main data
        GhLockData.lock_error = received.lock_error

        TakenOverDialog( received.lock_error )

      } else if ( received.new_lock ) {

        // Set the new lock
        GhLockData.lock = received.new_lock
      }

      maybeShowChanged( received.version )
    }
  })

  /**
   * Whether the version the server has is one this page doesn't know about
   *
   * @param version string the object's version when the heartbeat was received
   */
  const maybeShowChanged = version => {

    if ( !version || changedShown || GhLockData.lock_error ) {
      return
    }

    const known = currentVersion()

    // after a save of its own, the server's version is the page's. So is the first heartbeat's, unless an editor keeps
    // the version up to date itself: the one printed with the page can differ, like for the email editor's email
    if ( !known || ( !hasBaseline && typeof GhLockData.getVersion !== 'function' ) ) {
      hasBaseline = true
      GhLockData.version = version
      return
    }

    // the page's own save may not have replied yet, the next heartbeat checks again
    if ( known === version || ( typeof GhLockData.isBusy === 'function' && GhLockData.isBusy() ) ) {
      return
    }

    ChangedNotice()
  }
} )(jQuery)
