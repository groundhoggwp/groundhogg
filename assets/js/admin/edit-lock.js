( $ => {

  const {
    makeEl,
    Div,
    Button,
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
      }, __( 'Take over' ) ) : null,
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
        `<p style="margin-top: 0">${ sprintf( __( '%s now has editing control of this asset.', 'groundhogg' ), `<b>${name}</b>` ) }</p>`,
      ])
    ]),
    Div({
      className: 'display-flex flex-end'
    }, [
      ExitButton(),
    ])
  ]))

  window.wp.heartbeat.interval( 30 )

  $(()=>{
    const { lock_error = null } = GhLockData

    if ( ! lock_error ){
      return
    }

    TakeOverDialog( lock_error )
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
    }
  })
} )(jQuery)
