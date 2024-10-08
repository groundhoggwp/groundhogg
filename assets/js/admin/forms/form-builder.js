var FormBuilder = {};

( function ($, builder) {

  $.extend(builder, {

    activeEditor: null,
    active: false,
    currentType: null,
    types: {},

    init: function () {

      var self = this

      $(document).on('step-active', e => {
        this.activeEditor = null
      })

      $(document).on('click', 'textarea.code.form-html', e => {
        this.activeEditor = e.target
      })

      $(document).on('click', 'div.form-buttons', function (e) {
        self.getForm(e.target)
      })

      $('.gh-legacy-modal .gh-modal-footer .legacy-modal-close').click(function (e) {
        if (self.active) {
          self.makeField()
        }
      })

      $('#field-name').change(function () {
        $(this).val(self.sanitizeKey($(this).val()))
      })
      $('#field-id').change(function () {
        $(this).val(self.sanitizeKey($(this).val()))
      })

      /* Handled  Radio button options */
      $('#gh-option-table').click(function (e) {
        if ($(e.target).closest('.deleteOption').length) {
          $(e.target).closest('.option-wrapper').remove()
        }
      })

      $('.addoption').click(function () {
        self.addOptionsHTML()
      })

      this.initTypes()

    },

    saveOptions: function () {

      var ghoptions = []
      $('input[name="option[]"]').each(function () {
        ghoptions.push($(this).val())
      })

      var ghtags = []
      $('select[name="tags[]"]').each(function () {
        ghtags.push($(this).val())
      })
      var str = ''
      for (i = 0; i < ghoptions.length; i++) {
        if (ghoptions[i] && ghtags[i]) {
          str += ghoptions[i].trim() + '|' + ghtags[i] + '\n'
        }
        else if (ghoptions[i]) {
          str += ghoptions[i].trim() + '\n'
        }
        else {
          //do nothing
        }
      }
      $('#field-options').val(str.trim())
    },

    addOptionsHTML: function () {

      // language=HTML
      let newOption = `
          <div class="option-wrapper display-flex gap-10">
              <input type="text" class="input" style="float: left" name="option[]" placeholder="Option Text">
              <select class="gh-single-tag-picker" name='tags[]' style="max-width: 140px;"></select>
              <a style="text-decoration: none" href="javascript:void(0)"
                 class="deleteOption gh-button danger text small">
                  <span class="dashicons dashicons-trash"></span>
              </a>
          </div>`

      $('#gh-option-table').append(newOption)
      $(document).trigger('gh-init-pickers')
    },

    resetOptions: function () {
      $('#gh-option-table').html('')
      this.addOptionsHTML()
    },

    initTypes: function () {
      this.types.first = ['required', 'label', 'placeholder', 'id', 'class']
      this.types.last = ['required', 'label', 'placeholder', 'id', 'class']
      this.types.email = ['label', 'placeholder', 'id', 'class']
      this.types.phone = ['required', 'label', 'placeholder', 'phone_type', 'show_ext', 'id', 'class']
      this.types.gdpr = ['label', 'tag', 'id', 'class']
      this.types.terms = ['label', 'tag', 'id', 'class']
      this.types.recaptcha = ['captcha-theme', 'captcha-size', 'id', 'class']
      this.types.submit = ['text', 'id', 'class']
      this.types.custom = ['required', 'label', 'custom_field', 'id', 'class']
      this.types.text = ['required', 'label', 'placeholder', 'name', 'id', 'class']
      this.types.textarea = ['required', 'label', 'placeholder', 'name', 'id', 'class']
      this.types.number = ['required', 'label', 'name', 'min', 'max', 'id', 'class']
      this.types.dropdown = ['required', 'label', 'name', 'default', 'options', 'multiple', 'id', 'class']
      this.types.radio = ['required', 'label', 'name', 'options', 'id', 'class']
      this.types.checkbox = ['required', 'label', 'name', 'value', 'tag', 'id', 'class']
      this.types.address = ['required', 'label', 'id', 'class']
      this.types.birthday = ['required', 'label', 'id', 'class']
      this.types.row = ['id', 'class']
      this.types.col = ['width', 'id', 'class']
      this.types.date = ['required', 'label', 'name', 'min_date', 'max_date', 'id', 'class']
      this.types.time = ['required', 'label', 'name', 'min_time', 'max_time', 'id', 'class']
      this.types.file = ['required', 'label', 'name', 'max_file_size', 'file_types', 'id', 'class']
    },

    sanitizeKey: function (key) {
      return key.toLowerCase().replace(/[^a-z0-9\-_]/g, '')
    },

    getForm: function (button) {

      this.active = true
      GroundhoggModal.args.preventSave = true

      this.currentType = button.dataset.type

      var fields = this.types[this.currentType]
      this.hideFields()
      this.showFields(fields)

    },

    hideFields: function () {
      $('.form-field-form').find('tr').addClass('hidden')
    },

    showFields: function (fields) {
      for (var i = 0; i < fields.length; i++) {
        $('#gh-field-' + fields[i]).removeClass('hidden')
      }
    },

    buildCode: function () {

      var $form = $('#form-field-form')

      this.saveOptions()
      var attrs = $form.serializeArray()

      var code = '[' + this.currentType
      var ignore = [
        'col',
        'row',
        'gdpr',
        'recaptcha',
        'submit',
      ]

      for (var i = 0; i < attrs.length; i++) {

        //check if this field actually has the included name of the field allowed.
        if (this.types[this.currentType].includes(attrs[i].name)) {

          if (attrs[i].value !== '') {
            code += ' ' + attrs[i].name + '="' +
              attrs[i].value.replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\n/g, ',') + '"'
          }
          else if (attrs[i].name === 'label' && ignore.indexOf(this.currentType) === -1) {
            code += ' label=""'
          }

        }

      }

      code += ']'

      switch (this.currentType) {
        case 'col':
          code += '\n        \n    [/col]'
          break
        case 'row':
          code += '\n    \n[/row]'
          break
      }

      $form.trigger('reset')

      this.resetOptions()

      return code

    },

    insert: function (myValue) {

      let sel

      var myField = this.activeEditor
      // console.log( myField );

      if ( ! myField ) {
        return;
      }

      if (document.selection) {
        myField.focus()
        sel = document.selection.createRange()
        sel.text = myValue
      }

      //MOZILLA and others
      else if (myField.selectionStart) {
        var startPos = myField.selectionStart
        var endPos = myField.selectionEnd
        myField.value = myField.value.substring(0, startPos)
          + myValue
          + myField.value.substring(endPos, myField.value.length)
        myField.selectionStart = startPos + myValue.length
        myField.selectionEnd = startPos + myValue.length
      }
      else {
        myField.value += myValue
      }
    },

    makeField: function () {
      var code = this.buildCode()
      this.insert(code)
      this.active = false
    },

  })

  $(function () {
    builder.init()
  })

} )(jQuery, FormBuilder)
