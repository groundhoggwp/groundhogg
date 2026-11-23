(function ($) {

  let reader = {}
  let file = {}
  let file_name = ''
  let slice_size = 1000 * 1024
  let retries = 0
  let percentComplete = 0
  const MAX_RETRIES = 3

  const MEGABYTE = 1000000

  const { ProgressBar, Div } = MakeEl
  const { __ } = wp.i18n
  const { _adminajax: AdminAjaxNonce } = groundhogg_nonces
  const { location, selector, maxUploadSize = 0, canChunk = true } = BigFileUploader

  // Leave room for the rest of the request, a file right at the limit still fails
  const chunkAbove = maxUploadSize ? Math.min(maxUploadSize * 0.9, 10 * MEGABYTE) : 10 * MEGABYTE

  const el = document.querySelector(selector)

  if ( !el ){
    return
  }

  const $el = $(el);
  const $form = $el.closest( 'form' )

  /**
   * A file size people can read, e.g. 2.4 MB
   *
   * @param bytes
   * @return {string}
   */
  const readableSize = bytes => {
    if ( bytes < MEGABYTE ) {
      return `${Math.max( 1, Math.round( bytes / 1000 ) )} KB`
    }

    return `${( bytes / MEGABYTE ).toFixed( 1 )} MB`
  }

  // The drop zone shows which file was picked, and highlights while a file is dragged over it
  const dropzone = el.closest( '.gh-import-dropzone' )

  if ( dropzone ) {
    const fileName = dropzone.querySelector( '.gh-import-file-name' )

    const showFile = () => {
      const chosen = el.files[0]
      dropzone.classList.toggle( 'has-file', Boolean( chosen ) )

      if ( fileName ) {
        fileName.textContent = chosen ? `${chosen.name} (${readableSize( chosen.size )})` : ''
      }
    }

    const dragging = on => () => dropzone.classList.toggle( 'is-dragover', on )

    el.addEventListener( 'change', showFile )
    el.addEventListener( 'dragenter', dragging( true ) )
    el.addEventListener( 'dragover', dragging( true ) )
    el.addEventListener( 'dragleave', dragging( false ) )
    el.addEventListener( 'drop', dragging( false ) )

    // the browser may keep the file when coming back to the page
    showFile()
  }

  $form.on( 'submit', e => {
    reader = new FileReader();
    file = el.files[0];

    if ( ! file ){
      e.preventDefault()
      return false
    }

    if ( canChunk && file.size > chunkAbove ){
      e.preventDefault()
      pre_file_upload()

      $form.html( `<div id="big-file-uploading"><h2>${__('Preparing upload...', 'groundhogg')}</h2></div>` );

      return false
    }
  })

  /**
   * Show that the upload failed
   */
  function upload_failed() {
    morphdom( document.querySelector( '#big-file-uploading' ), Div({
      id: 'big-file-uploading'
    }, [
      `<h3>${__('Upload failed.', 'groundhogg')}</h3>`,
      `<p>${__('Something went wrong while uploading your file. Please try again.', 'groundhogg')}</p>`,
      ProgressBar({
        error: true,
        percent: percentComplete
      })
    ] ) )
  }

  /**
   * Do stuff before uploading the file
   */
  function pre_file_upload() {
    $.ajax( {
      url: ajaxurl,
      type: 'POST',
      dataType: 'json',
      cache: false,
      data: {
        action: 'gh_pre_big_file_upload',
        nonce: AdminAjaxNonce,
        location,
        file_name: file.name
      },
      error: upload_failed,
      success: function( response ) {
        if ( ! response.success ){
          upload_failed()
          return
        }

        file_name = response.data.file_name

        upload_file(0 );
      }
    } );
  }

  /**
   * Run when the uploading is completed
   */
  function file_upload_success() {
    // Update upload progress
    morphdom( document.querySelector( '#big-file-uploading' ), Div({
      id: 'big-file-uploading'
    }, [
      `<h3>${__('Upload complete!', 'groundhogg')}</h3>`,
    ] ) )


    $.ajax( {
      url: ajaxurl,
      type: 'POST',
      dataType: 'json',
      cache: false,
      data: {
        action: 'gh_big_file_upload_success',
        nonce: AdminAjaxNonce,
        location,
        file_name
      },
      error: upload_failed,
      success: function( response ) {
        if ( ! response.success ) {
          upload_failed()
          return
        }

        window.location.href = response.data.return_url;
      }
    } );
  }

  /**
   * Upload the file recursively
   *
   * @param start
   */
  function upload_file( start ) {

    let next_slice = start + slice_size + 1
    let blob = file.slice(start, next_slice)

    reader.onloadend = function( event ) {
      if ( event.target.readyState !== FileReader.DONE ) {
        return;
      }

      $.ajax( {
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: {
          action: 'gh_big_file_upload',
          file_data: event.target.result,
          file_type: file.type,
          location,
          file_name,
          nonce: AdminAjaxNonce
        },
        error: function( jqXHR, textStatus, errorThrown ) {
          if ( retries < MAX_RETRIES ){
            retries++
            upload_file( start )
            return
          }

          upload_failed()
        },
        success: function( response ) {

          if ( ! response.success ){
            upload_failed()
            return
          }

          retries = 0

          const size_done = Math.min( start + slice_size, file.size );
          const percent = Math.floor( ( size_done / file.size ) * 100 );
          percentComplete = percent

          if ( next_slice < file.size ) {
            // Update upload progress

            morphdom( document.querySelector( '#big-file-uploading' ), Div({
              id: 'big-file-uploading'
            }, [
              `<h3>${__('Uploading file...', 'groundhogg')}</h3>`,
              ProgressBar({
                percent
              })
            ] ) )
            // More to upload, call function recursively
            upload_file( next_slice );
          } else {
            file_upload_success()
          }
        }
      } );
    };

    reader.readAsDataURL( blob );
  }

})(jQuery);
