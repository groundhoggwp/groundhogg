(function ($) {
  const {
    copyObject,
    select,
    input,
    tinymceElement,
    specialChars,
    inputWithReplacementsAndEmojis,
  } = Groundhogg.element;
  const { post, get, patch, routes } = Groundhogg.api;

  const EmailEditor = ({
    selector,
    email,
    onChange = (email) => {},
    onCommit = (email) => {},
  }) => ({
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
      editor() {
        return `
          <div id="email-editor-header">
              ${this.components.header.call(this)}
          </div>
          <div id="email-editor-body">
            <div id="email-editor-main">
              <div id="email-editor-content">
                  ${this.components.content.call(this)}
              </div>
              <details open id="email-editor-controls">
                <Summary>Advanced</Summary>
                  ${this.components.controls.call(this)}
              </details>
            </div>
            <div id="email-editor-sidebar">
                ${this.components.sidebar.call(this)}
            </div>
          </div>
        `;
      },

      header() {
        return `
          <p>
				  <label class="row-label">Title</label>
				  ${input({
            id: "title",
            name: "title",
            value: this.email.data.title,
          })}
          </p>
        `;
      },

      content() {
        const fromOptions = {};
        Groundhogg.filters.owners.forEach((owner) => {
          fromOptions[
            owner.ID
          ] = `${owner.data.display_name} (${owner.data.user_email})`;
        });

        return `
          <p>
            <label class="row-label">Send this email from...</label>
            ${select(
              {
                id: "from-user",
                name: "form_user",
              },
              fromOptions,
              this.email.data.from_user
            )}
				  </p>
				  <p>
				  <label class="row-label">Replies are sent to...</label>
				  ${input({
            id: "reply-to",
            name: "reply_to",
            value: this.email.meta.reply_to_override,
          })}
          </p>
          <p>
          <label>Subject:</label>
					  ${inputWithReplacementsAndEmojis({
              name: "subject",
              placeholder: "Subject line...",
              value: this.email.data.subject,
            })}
          </p>
          <p>
          <label>Content:</label>
          <textarea id="content" name="content">${
            this.email.data.content || ""
          }</textarea>
          </p>
        `;
      },

      sidebar() {
        return `
          sidebar
        `;
      },

      controls() {
        return `
          <label>Custom headers:</label>
        `;
      },

      inspector() {},
    },

    autoSaveChanges() {
      this.saveUndoState();

      post(`${routes.v4.emails}/${this.email.ID}/meta`, {
        edited: this.edited,
      });
    },

    commitChanges() {
      patch(`${routes.v4.emails}/${this.email.ID}`, {
        data: this.edited.data,
        meta: this.edited.meta,
      }).then((d) => {
        this.loadEmail(d.item);
        onCommit(this.email);
      });
    },

    updateEmailData(newData) {
      this.edited.data = {
        ...this.email.edited.data,
        ...newData,
      };

      this.autoSaveChanges();
    },

    updateEmailMeta(newMeta) {
      this.edited.meta = {
        ...this.email.edited.meta,
        ...newMeta,
      };

      this.autoSaveChanges();

      onChange(this.edited, this.email);
    },

    render() {
      return this.components.editor.call(this);
    },

    mount() {
      console.log("Hello World!");

      this.loadEmail(this.email);
      this.$el.html(this.render());
      this.onMount();
    },

    loadEmail(email) {
      console.log(email);

      this.email = copyObject(email);

      if (email.meta.edited) {
        this.edited = copyObject(email.meta.edited);
      } else {
        this.edited = copyObject(email);
      }
    },

    onMount() {
      let saveTimer;

      tinymceElement(
        "content",
        {
          tinymce: true,
          quicktags: true,
        },
        (content) => {
          window.console.log("onchange");
          // Reset timer.
          clearTimeout(saveTimer);

          // Only save after a second.
          saveTimer = setTimeout(function () {
            window.console.log("save");
            // updateStepMeta({
            //   note_text: content
            // })
          }, 300);
        }
      );
    },

    currentState() {
      const { email } = this;

      return {
        email: copyObject(email),
      };
    },

    /**
     * Saves the current state of the funnel for an undo slot
     */
    saveUndoState() {
      this.undoStates.push(this.currentState());
    },

    /**
     * Undo the previous change
     */
    undo() {
      var lastState = this.undoStates.pop();

      if (!lastState) {
        return;
      }

      this.redoStates.push(this.currentState());

      Object.assign(this, lastState);

      this.render();
    },

    /**
     * Redo the previous change
     */
    redo() {
      var lastState = this.redoStates.pop();

      if (!lastState) {
        return;
      }

      this.undoStates.push(this.currentState());

      Object.assign(this, lastState);

      this.render();
    },
  });

  Groundhogg.EmailEditor = EmailEditor;

  //usage
  // const editor = EmailEditor('#editor', { content: 'HI!' })
  // editor.mount()
})(jQuery);
