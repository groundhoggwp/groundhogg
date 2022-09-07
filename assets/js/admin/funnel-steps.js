(($) => {

  const {
    select,
    toggle,
    tinymceElement,
    improveTinyMCE,
  } = Groundhogg.element

  const { sprintf, __, _x, _n } = wp.i18n

  improveTinyMCE()

  const FunnelSteps = {

    init () {

      $(document).on('step-active', e => {

        let active = Funnel.getActiveStep()

        switch ( active.data.step_type ){
          case 'apply_note':
            this.applyNote( active )
            break;
          case 'admin_notification':
            this.adminNotification( active )
            break;
        }
      })
    },

    adminNotification (step) {
      
      let $customEmail = $('.active .custom-settings input.custom-email')
      let $replyType = $('.active .custom-settings select.reply-to-type')

      $replyType.on('change', e => {

        switch ( $replyType.val() ){
          case 'contact':
          case 'owner':
            $customEmail.addClass('hidden')
            break;
          case 'custom':
            $customEmail.removeClass('hidden')
            break;
        }

      })

      this.applyNote(step)
    },

    applyNote ( step ) {
      let id = `step_${step.ID}_note_text`

      wp.editor.remove( id )
      tinymceElement( id, {
        quicktags: false
      }, ( content ) => {
        Funnel.updateStepMeta( {
          note_text: content
        })
      } )
    }
  }

  $(() => FunnelSteps.init())

})(jQuery)
