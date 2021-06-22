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
              <details open id="email-editor-advanced">
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
        return `
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
            <label>Preview text:</label>
					  ${input({
              id: "preview-text",
              name: "pre_header",
              value: this.email.data.pre_header,
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
        const fromOptions = {};
        Groundhogg.filters.owners.forEach((owner) => {
          fromOptions[
            owner.ID
          ] = `${owner.data.display_name} (${owner.data.user_email})`;
        });

        const message_typeOptions = {};
        // @todo
        // Groundhogg.filters.owners.forEach((owner) => {
        //   message_typeOptions[
        //     owner.ID
        //     ] = `${owner.data.display_name} (${owner.data.user_email})`;
        // });

        return `
          <div id="email-editor-sidebar-controls">
          <button class="gh-button secondary">Send test email</button>
          <button class="gh-button secondary">
            <svg width="12" height="19" viewBox="0 0 12 19" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8.74288 15.776C9.15709 15.776 9.49288 15.4402 9.49288 15.026C9.49288 14.6117 9.15709 14.276 8.74288 14.276V15.776ZM3.54739 14.276C3.13318 14.276 2.79739 14.6117 2.79739 15.026C2.79739 15.4402 3.13318 15.776 3.54739 15.776V14.276ZM8.74288 1.48749V2.23749V1.48749ZM3.54739 1.48749L3.54739 0.737488L3.54739 1.48749ZM1.23828 15.0259H1.98828H1.23828ZM1.23828 3.94903H0.488281H1.23828ZM8.74286 17.4875V16.7375V17.4875ZM3.54739 17.4875V18.2375V17.4875ZM11.052 15.026L11.802 15.026L11.052 15.026ZM11.052 3.94903L10.302 3.94903L11.052 3.94903ZM8.74288 0.737488L3.54739 0.737488L3.54739 2.23749L8.74288 2.23749V0.737488ZM1.98828 15.0259L1.98828 3.94903H0.488281L0.488281 15.0259H1.98828ZM8.74286 16.7375H3.54739V18.2375H8.74286V16.7375ZM11.802 15.026L11.802 3.94903L10.302 3.94903L10.302 15.026L11.802 15.026ZM8.74286 18.2375C10.4768 18.2375 11.802 16.7538 11.802 15.026L10.302 15.026C10.302 16.0171 9.55949 16.7375 8.74286 16.7375V18.2375ZM8.74288 2.23749C9.55951 2.23749 10.302 2.95789 10.302 3.94903L11.802 3.94903C11.802 2.22123 10.4768 0.737488 8.74288 0.737488V2.23749ZM3.54739 0.737488C1.81345 0.737488 0.488281 2.22123 0.488281 3.94903L1.98828 3.94903C1.98828 2.95788 2.73076 2.23749 3.54739 2.23749L3.54739 0.737488ZM0.488281 15.0259C0.488281 16.7537 1.81345 18.2375 3.54739 18.2375V16.7375C2.73076 16.7375 1.98828 16.0171 1.98828 15.0259H0.488281ZM3.54739 15.776H8.74288V14.276H3.54739V15.776Z" fill="#0075FF"/>
            </svg>
          </button>
          <button class="gh-button secondary">
              <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.2702 13.7952V13.0452V13.7952ZM2.57008 13.7952V14.5452H2.57008L2.57008 13.7952ZM16.4247 2.71826L17.1747 2.71826L16.4247 2.71826ZM16.4247 12.5644H15.6747H16.4247ZM1.41553 2.71827H0.665527H1.41553ZM1.41553 12.5644H2.16553H1.41553ZM15.2702 1.48749V2.23749H15.2702L15.2702 1.48749ZM2.57008 1.4875L2.57008 0.737501L2.57008 1.4875ZM16.4247 11.4683C16.8389 11.4683 17.1747 11.1325 17.1747 10.7183C17.1747 10.304 16.8389 9.96826 16.4247 9.96826V11.4683ZM1.41553 9.96826C1.00131 9.96826 0.665527 10.304 0.665527 10.7183C0.665527 11.1325 1.00131 11.4683 1.41553 11.4683L1.41553 9.96826ZM12.3838 18.2375C12.798 18.2375 13.1338 17.9017 13.1338 17.4875C13.1338 17.0733 12.798 16.7375 12.3838 16.7375V18.2375ZM5.45646 16.7375C5.04225 16.7375 4.70646 17.0733 4.70646 17.4875C4.70646 17.9017 5.04225 18.2375 5.45646 18.2375V16.7375ZM5.86102 17.4875C5.86102 17.9017 6.19681 18.2375 6.61102 18.2375C7.02523 18.2375 7.36102 17.9017 7.36102 17.4875H5.86102ZM7.36102 13.7952C7.36102 13.381 7.02523 13.0452 6.61102 13.0452C6.19681 13.0452 5.86102 13.381 5.86102 13.7952H7.36102ZM10.4792 17.4875C10.4792 17.9017 10.815 18.2375 11.2292 18.2375C11.6434 18.2375 11.9792 17.9017 11.9792 17.4875H10.4792ZM11.9792 13.7952C11.9792 13.381 11.6434 13.0452 11.2292 13.0452C10.815 13.0452 10.4792 13.381 10.4792 13.7952H11.9792ZM15.2702 13.0452L2.57008 13.0452L2.57008 14.5452L15.2702 14.5452V13.0452ZM15.6747 2.71826L15.6747 12.5644H17.1747L17.1747 2.71826L15.6747 2.71826ZM0.665527 2.71827L0.665528 12.5644H2.16553L2.16553 2.71827H0.665527ZM15.2702 0.737488L2.57008 0.737501L2.57008 2.2375L15.2702 2.23749L15.2702 0.737488ZM17.1747 2.71826C17.1747 1.67019 16.3665 0.737486 15.2702 0.737488L15.2702 2.23749C15.4492 2.23749 15.6747 2.40685 15.6747 2.71826L17.1747 2.71826ZM2.16553 2.71827C2.16553 2.40686 2.39109 2.2375 2.57008 2.2375L2.57008 0.737501C1.47378 0.737502 0.665527 1.67021 0.665527 2.71827H2.16553ZM2.57008 13.0452C2.39109 13.0452 2.16553 12.8758 2.16553 12.5644H0.665528C0.665528 13.6125 1.47378 14.5452 2.57008 14.5452L2.57008 13.0452ZM15.2702 14.5452C16.3665 14.5452 17.1747 13.6125 17.1747 12.5644H15.6747C15.6747 12.8758 15.4492 13.0452 15.2702 13.0452V14.5452ZM16.4247 9.96826H1.41553L1.41553 11.4683H16.4247V9.96826ZM12.3838 16.7375H5.45646V18.2375H12.3838V16.7375ZM7.36102 17.4875V13.7952H5.86102V17.4875H7.36102ZM11.9792 17.4875V13.7952H10.4792V17.4875H11.9792Z" fill="#0075FF"/>
                </svg>
          </button>
          </div>
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
				  <div id="email-editor-sidebar-options">
            <div>
            <label class="row-label">Alignment:</label>
              <button class="gh-button secondary">
                <svg width="13" height="14" viewBox="0 0 13 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.777832 13.1662H6.4477M0.777832 9.0427H12.1176M0.777832 0.795624H12.1176M0.777832 4.91916H6.4477" stroke="#102640" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button class="gh-button secondary">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path opacity="0.6" d="M12.5319 9.00262H1.19189M12.5319 0.755951H1.19189M9.95462 13.126H4.28462M9.95462 4.87928H4.28462" stroke="#102640" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          <div id="email-editor-sidebar-message_type">
            <label class="row-label">Messaging type:</label>
            ${select(
              {
                id: "message-type",
                name: "message_type",
              },
              message_typeOptions,
              this.email.meta.message_type
            )}
				  </div>
          </div>
          <details>
            <summary>Additional options:</summary>
            <p>
            
</p>
          </details>
        `;
      },

      controls() {
        return `
          <div id="email-editor-advanced-headers">
            <label>Custom headers:</label>
            <table>
              <thead>
                <tr>
                  <th>
                    Key
                  </th>
                  <th>Value</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                  <tr>
                      <td>
                          <input type="text"/>
                      </td>
                      <td>
                          <input type="text"/>
                      </td>
                      <td>
                        <button class="gh-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 10h-10v-10h-4v10h-10v4h10v10h4v-10h10z"/></svg>
                        </button>
                        <button class="gh-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M3 6l3 18h12l3-18h-18zm19-4v2h-20v-2h5.711c.9 0 1.631-1.099 1.631-2h5.316c0 .901.73 2 1.631 2h5.711z"/></svg>
                        </button>
                      </td>
                  </tr>
                  <tr>
                      <td>
                          <input type="text"/>
                      </td>
                      <td>
                          <input type="text"/>
                      </td>
                      <td>
                        <button class="gh-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 10h-10v-10h-4v10h-10v4h10v10h4v-10h10z"/></svg>
                        </button>
                        <button class="gh-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M3 6l3 18h12l3-18h-18zm19-4v2h-20v-2h5.711c.9 0 1.631-1.099 1.631-2h5.316c0 .901.73 2 1.631 2h5.711z"/></svg>
                        </button>
                      </td>
                  </tr>
                  <tr>
                      <td>
                          <input type="text"/>
                      </td>
                      <td>
                          <input type="text"/>
                      </td>
                      <td>
                        <button class="gh-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 10h-10v-10h-4v10h-10v4h10v10h4v-10h10z"/></svg>
                        </button>
                        <button class="gh-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M3 6l3 18h12l3-18h-18zm19-4v2h-20v-2h5.711c.9 0 1.631-1.099 1.631-2h5.316c0 .901.73 2 1.631 2h5.711z"/></svg>
                        </button>
                      </td>
                  </tr>
              </tbody>
            </table>
          </div>
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
