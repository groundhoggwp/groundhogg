(function ($) {

  const { copyObject } = Groundhogg.element
  const { post, get, patch, routes } = Groundhogg.api

  const EmailEditor = ({ selector, email, onChange = (email) => {}, onCommit = (email) => {} }) => ({

    selector,
    email: copyObject(email),
    origEmail: copyObject(email),
    $el: $(selector),
    undoStates: [],
    redoStates: [],
    edited: {
      data: {},
      meta: {},
    },

    components: {

      editor ({ data, meta, ID }) {

      },

      header ({}) {

      },

      content ({ subject }) {

      },

      sidebar ({}) {

      },

      controls () {

      },

      inspector () {

      }

    },

    autoSaveChanges () {

      this.saveUndoState()

      post(`${routes.v4.emails}/${this.email.ID}/meta`, {
        edited: this.edited
      })

    },

    commitChanges () {
      patch(`${routes.v4.emails}/${this.email.ID}`, {
        data: this.edited.data,
        meta: this.edited.meta
      }).then(d => {
        this.loadEmail( d.item )
        onCommit( this.email )
      })
    },

    updateEmailData (newData) {

      this.edited.data = {
        ...this.email.edited.data,
        ...newData
      }

      this.autoSaveChanges()
    },

    updateEmailMeta (newMeta) {

      this.edited.meta = {
        ...this.email.edited.meta,
        ...newMeta
      }

      this.autoSaveChanges()

      onChange(this.edited, this.email)
    },

    render () {
      return this.components.editor(this.edited)
    },

    mount () {

      console.log( 'Hello World!' )

      this.loadEmail(this.email)
      this.$el.html(this.render())
      this.onMount()
    },

    loadEmail (email) {

      console.log( email )

      this.email = copyObject(email)

      if (email.meta.edited) {
        this.edited = copyObject(email.meta.edited)
      } else {
        this.edited = copyObject(email)
      }
    },

    onMount () {
      // Event listeners
    },

    currentState () {
      const {
        email,
      } = this

      return {
        email: copyObject(email)
      }
    },

    /**
     * Saves the current state of the funnel for an undo slot
     */
    saveUndoState () {
      this.undoStates.push(this.currentState())
    },

    /**
     * Undo the previous change
     */
    undo () {
      var lastState = this.undoStates.pop()

      if (!lastState) {
        return
      }

      this.redoStates.push(this.currentState())

      Object.assign(this, lastState)

      this.render()
    },

    /**
     * Redo the previous change
     */
    redo () {
      var lastState = this.redoStates.pop()

      if (!lastState) {
        return
      }

      this.undoStates.push(this.currentState())

      Object.assign(this, lastState)

      this.render()
    },

  })

  Groundhogg.EmailEditor = EmailEditor

  //usage
  // const editor = EmailEditor('#editor', { content: 'HI!' })
  // editor.mount()

})(jQuery)