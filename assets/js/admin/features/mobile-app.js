/**
 * The mobile app in the admin.
 *
 * The Mobile app page: draw the code that opens the app on a phone, and copy the link.
 */
( function () {

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
