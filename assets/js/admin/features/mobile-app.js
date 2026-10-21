/**
 * The mobile app in the admin.
 *
 *  - The notice that tells people about the app: closing it with the X is remembered, so it doesn't come back.
 *  - The Mobile app page: draw the code that opens the app on a phone, and copy the link.
 */
( function () {

  const NOTICE = 'mobile-app-available'

  // The X is added to every dismissible notice by the admin's own script, and only hides it. For this one it is
  // remembered as well (the notice's id is the code it was added with).
  document.addEventListener( 'click', e => {
    if ( e.target.closest && e.target.closest( '#' + NOTICE + ' .notice-dismiss' ) && window.Groundhogg && Groundhogg.api ) {
      Groundhogg.api.ajax( {
        action: 'gh_dismiss_notice',
        notice: NOTICE,
      } )
    }
  } )

  const qr = document.getElementById( 'gh-mobile-qr' )

  if ( qr && window.qrcode ) {
    // type 0 picks the smallest size that fits, M is the error correction that survives a bit of glare on a screen
    const code = window.qrcode( 0, 'M' )
    code.addData( qr.dataset.url )
    code.make()

    qr.innerHTML = code.createSvgTag( {
      cellSize: 6,
      margin: 0,
      scalable: true,
      alt: qr.dataset.label,
      title: qr.dataset.label,
    } )
  }

  const copy = document.getElementById( 'gh-mobile-copy' )

  if ( copy ) {
    copy.addEventListener( 'click', () => {

      const done = () => {
        const label = copy.textContent
        copy.textContent = copy.dataset.copied
        setTimeout( () => {
          copy.textContent = label
        }, 1800 )
      }

      if ( navigator.clipboard && navigator.clipboard.writeText ) {
        navigator.clipboard.writeText( copy.dataset.url ).then( done )
        return
      }

      // older browsers, and a page that isn't on https
      const field = document.createElement( 'input' )
      field.value = copy.dataset.url
      document.body.appendChild( field )
      field.select()
      document.execCommand( 'copy' )
      field.remove()
      done()
    } )
  }

} )()
