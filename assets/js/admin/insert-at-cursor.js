(function ($) {

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

  const Insert = {

    active: null,
    text: '',
    to_mce: false,

    init: function () {

      var self = this

      $doc.on( 'ghClearInsertTarget', function ( ){
        self.to_mce = false;
        self.active = false;
      } )

      // GO TO MCE
      $doc.on('to_mce', function () {
        self.to_mce = true
        $doc.trigger('ghInsertTargetChanged')
      })

      // NOPE, GO TO TEXT
      $doc.on('focus', '#wpbody input, #wpbody textarea', function () {
        self.active = this
        self.to_mce = false
        $doc.trigger('ghInsertTargetChanged')
      })

    },

    setActive(el){
      this.active = el
    },

    inserting(){
      return this.active || this.to_mce
    },

    insert: function (text) {

      console.log('insert',{ text: text })

      // CHECK TINY MCE
      if (typeof tinymce != 'undefined' && tinymce.activeEditor != null && this.to_mce) {
        tinymce.activeEditor.execCommand('mceInsertContent', false, text)
        // INSERT REGULAR TEXT INPUT.
      }

      if (this.active != null && !this.to_mce) {

        insertAtCursor(this.active, text)

        return this.active
      }
    }

  }

  $(function () {
    Insert.init()
  })

  window.InsertAtCursor = Insert

})(jQuery)