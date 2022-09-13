/*
Grab a container via the ID of the container and load that content into the box.
Display the box in the correct position of the screen.
close the thickbox and put the content back where it came from.
*/

var GroundhoggModal = {};

( function ($, modal, defaults) {

  const { loadingModal } = Groundhogg.element

  $.extend(modal, {

    is_open: false,
    overlay: null,
    window: null,
    content: null,
    title: null,
    source: null,
    frameUrl: '',
    args: {},
    defaults: {},
    showFooter: true,
    closeLoader: () => {},

    init: function (title, href) {

      if (this.is_open) {
        this.close()
      }

      var self = this

      Object.assign(this.args, defaults)

      if (typeof href == 'string') {
        this.parseArgs(href)
      }
      else {
        this.args = $.extend(defaults, href)
      }

      this.wrapper = $('.gh-legacy-modal')
      this.overlay = $('.gh-legacy-modal .gh-modal-overlay')
      this.window = $('.gh-legacy-modal .gh-modal-dialog')
      this.content = $('.gh-legacy-modal .gh-modal-dialog-content')
      this.title = $('.gh-legacy-modal .gh-modal-dialog-title')
      this.header = $('.gh-legacy-modal .gh-header')
      this.footer = $('.gh-legacy-modal .gh-modal-footer')
      this.loader = $('.iframe-loader-wrapper')
      this.footerClose = $('.gh-legacy-modal .gh-modal-footer .legacy-modal-close')

      if (typeof this.args.footer !== 'undefined' && this.args.footer === 'false') {
        this.showFooter = false
        this.footer.addClass('hidden')
      }
      else {
        this.footer.removeClass('hidden')
      }

      if (this.matchUrl(this.args.source)) {

        //language=HTML
        this.source = $(`
            <div>
                <iframe
                        style="display: block"
                        src="${ this.args.source }"
                        onload="GroundhoggModal.prepareFrame( this )"
                        width="${ this.args.width ?? 1200 }"
                ></iframe>
            </div>`)

        this.closeLoader = loadingModal().close

      }
      else {
        this.source = $('#' + this.args.source)
      }

      this.title.text(title)

      if (typeof this.args.footertext !== 'undefined') {
        this.footerClose.text(this.args.footertext)
      }

      this.content.css('height', 'auto')

      self.open()
    },

    matchUrl: function (maybeUrl) {
      var exp = /(https|http)?:\/\/((www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6})|(localhost)\b([-a-zA-Z0-9@:%_\+.~#?&\/=]*)/
      var urlRegex = new RegExp(exp)
      return maybeUrl.match(urlRegex)
    },

    parseArgs: function (queryArgs) {
      var querystart = queryArgs.indexOf('#')
      var listArgs = queryArgs.substring(querystart + 1)
      listArgs = listArgs.split('&')
      for (var i = 0; i < listArgs.length; i++) {
        var args = listArgs[i].split('=')
        this.args[args[0]] = decodeURIComponent(args[1].replace('+', ' '))
      }
      return this.args
    },

    open: function () {
      this.showPopUp()
      this.pullContent()

      this.is_open = true

      $(document).trigger('GroundhoggModalOpened')
    },

    close: function () {

      if (!this.is_open) {
        return
      }

      this.pushContent()
      this.hidePopUp()
      if (this.args.preventSave === undefined || this.args.preventSave === false || this.args.preventSave === 'false') {
        $(document).trigger('GroundhoggModalClosed')
      }
      this.reset()
    },

    prepareFrame: function (iframe) {
      let frameHeight = Math.max(iframe.contentWindow.document.body.scrollHeight,
        iframe.contentWindow.document.body.offsetHeight) + 100

      iframe.style.height = '100%'
      this.content.css('height', '100vh')

      let dialogHeight = this.window.height() - this.header.outerHeight()

      this.content.css('height', Math.min( frameHeight, dialogHeight ) )

      this.closeLoader()
    },

    frameReload: function () {
      this.content.addClass('hidden')
      this.loader.removeClass('hidden')
    },

    /* Switch the content In the source and target between */
    pullContent: function () {
      this.content.append(this.source.children())
      $(document).trigger('GroundhoggModalContentPulled')
    },

    pushContent: function () {
      this.source.append(this.content.children())
      $(document).trigger('GroundhoggModalContentPushed')
    },

    /* Load the PopUp onto the screen */
    showPopUp: function () {
      this.wrapper.removeClass('hidden')
    },

    /* Close the PopUp */
    hidePopUp: function () {
      this.wrapper.addClass('hidden')
    },

    getDefaults: function () {
      return JSON.parse(JSON.stringify(defaults))
    },

    reset: function () {
      this.is_open = false
      this.args = this.getDefaults()
    },

    reload: function () {
      var self = this

      $(document).on('click', 'a[href^="#source="]',
        function (e) {
          e.preventDefault()
          //console.log(this.href);
          self.init(this.title, this.href)
        },
      )

      $(document).on('click', '.gh-legacy-modal .legacy-modal-close',
        function () {
          $(document).trigger('modal-closed')
          self.close()
        },
      )
    },
  })

  $(function () {
    modal.reload()
  })

} )(jQuery, GroundhoggModal, GroundhoggModalDefaults)
