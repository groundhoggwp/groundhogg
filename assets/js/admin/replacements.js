var ReplacementsInsertListener = {};
(function ($, replacements, modal) {

  var $doc = $(document)

  function insertAtCursor (myField, myValue) {
    //IE support
    if (document.selection) {
      myField.focus()
      var sel = document.selection.createRange()
      sel.text = myValue
    }
    //MOZILLA and others
    else if (myField.selectionStart || myField.selectionStart == '0') {
      var startPos = myField.selectionStart
      var endPos = myField.selectionEnd
      myField.value = myField.value.substring(0, startPos)
        + myValue
        + myField.value.substring(endPos, myField.value.length)
    } else {
      myField.value += myValue
    }

    $(myField).trigger('change')
  }

  $.extend(replacements, {

    inserting: false,
    active: null,
    text: '',
    to_mce: false,

    init: function () {

      var self = this

      $doc.on( 'ghClearReplacementTarget', function ( ){
        self.to_mce = false;
        self.active = false;
      } )

      $doc.on('click', '.replacements-button', function () {
        self.inserting = true
      })

      // GO TO MCE
      $doc.on('to_mce', function () {
        self.to_mce = true
        $doc.trigger('ghReplacementTargetChanged')
      })

      // NOPE, GO TO TEXT
      $doc.on('click', '#wpbody input, #wpbody textarea', function () {
        self.active = this
        self.to_mce = false
        $doc.trigger('ghReplacementTargetChanged')
      })

      $doc.on('click', '.replacement-selector', function () {
        self.text = $(this).val()
      })

      $doc.on('dblclick', '.replacement-selector', function () {
        self.text = $(this).val()
        self.insert()
        modal.close()
      })

      $doc.on('change', '.replacement-code-dropdown', function () {
        self.text = $(this).val()
        self.insert()
        $('.replacement-code-dropdown option').prop('selected', false)
        $doc.trigger( 'ghInsertReplacement', [ self.text, self ] )
      })

      $('#popup-close-footer').on('click', function () {

        if (!self.inserting) {
          return
        }
        self.insert()
        self.inserting = false
      })

    },

    insert: function ( ) {

      // CHECK TINY MCE
      if (typeof tinymce != 'undefined' && tinymce.activeEditor != null && this.to_mce) {
        tinymce.activeEditor.execCommand('mceInsertContent', false, this.text)
        // INSERT REGULAR TEXT INPUT.
      }

      if (this.active != null && !this.to_mce) {
        insertAtCursor(this.active, this.text)
      }

      console.log({ text: this.text })
    }

  })

  $(function () {
    replacements.init()
  })

})(jQuery, ReplacementsInsertListener, GroundhoggModal)
