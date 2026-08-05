( ($) => {

  const { replies: RepliesStore } = Groundhogg.stores
  const {
    tinymceElement,
    addMediaToBasicTinyMCE,
    escHTML,
    dangerConfirmationModal
  } = Groundhogg.element
  const {
    userHasCap,
  } = Groundhogg.user
  const {
    sprintf,
    __,
    _x,
  } = wp.i18n

  const {
    Div,
    Form,
    Span,
    H2,
    Button,
    Dashicon,
    ToolTip,
    Fragment,
    Skeleton,
    Label,
    Input,
    Textarea,
    Pg,
    Modal,
  } = MakeEl

  const SavedReplies = ({
    single = '',
    plural = '',
    note_type = 'saved_reply', // saved replies are stored in the notes table
  } = {}) => {

    let strings = {
      /* translators: %s: the plural label of saved replies, like "Saved Replies" */
      manage: sprintf(__('Manage %s', 'groundhogg'), plural),
      /* translators: singular label of saved reply, such as "Reply" */
      name: sprintf( __('%s Name', 'groundhogg'), single),
      /* translators: singular label of saved reply, such as "Reply" */
      create: sprintf(_x( 'Create %s', 'as in create a saved reply', 'groundhogg' ), single ),
      /* translators: %s: the singular label of a saved reply, like "Reply" */
      new: sprintf(__('New %s', 'groundhogg'), single),
      /* translators: singular label of saved reply, such as "Reply" */
      update: sprintf( _x( 'Update %s', 'as in update a saved reply', 'groundhogg' ), single),
      /* translators: singular label of an asset type */
      delete: sprintf( _x('Delete %s', 'deleting a single item', 'groundhogg'), single),
      /* translators: %s: the singular label of a saved reply, like "Reply" */
      deleteConfirmation: sprintf(__('Are you sure you want to delete this %s?', 'groundhogg'), single),
      /* translators: %s: the plural label of saved replies, like "Saved Replies" */
      noReplies: sprintf(__('No %s yet.', 'groundhogg'), plural)

    }

    const State = Groundhogg.createState({
      adding      : false,
      editing     : false,
      notes       : [],
      loaded      : false,
      edit_summary: '',
      edit_content: '',
    })

    const clearEditState = () => State.set({
      edit_summary: '',
      edit_content: '',
      editing     : false,
      adding      : false,
    })

    const fetchNotes = () => RepliesStore.fetchItems({
      type: note_type,
    }).then(notes => {

      State.set({
        loaded: true,
        notes : notes.map(({ ID }) => ID),
      })

      return notes
    })

    return Div({
      id       : 'saved-replies',
      className: 'saved-replies-widget',
    }, morph => {

      if (!State.loaded) {

        fetchNotes().then(morph)

        return Skeleton({}, [
          'full',
          'full',
          'full',
        ])

      }

      /**
       * The form for adding/editing the note details
       *
       * @returns {*}
       * @constructor
       */
      const EditReply = () => {

        return Form({
          className: 'reply display-grid gap-10',
          onSubmit : e => {
            e.preventDefault()

            if (State.adding) {

              RepliesStore.post({
                data: {
                  summary: State.edit_summary,
                  content: State.edit_content,
                  type   : note_type,
                },
              }).then(note => {

                State.set({
                  adding: false,
                  notes : [
                    ...State.notes,
                    note.ID,
                  ], // add the new note ID
                })

                clearEditState()

                morph()
              })

            }
            else {

              RepliesStore.patch(State.editing, {
                data: {
                  summary: State.edit_summary,
                  content: State.edit_content,
                },
              }).then(() => {

                clearEditState()

                morph()

              })
            }

          },
        }, [
          Div({
            className: 'full display-flex gap-10',
          }, [
            Div({
              className: 'full-width',
            }, [
              Label({
                for: 'reply-summary',
              }, strings.name ),
              Input({
                className: 'full-width',
                id       : 'reply-summary',
                name     : 'summary',
                required : true,
                value    : State.edit_summary,
                onChange : e => State.set({
                  edit_summary: e.target.value,
                }),
              }),
            ]),
          ]),
          Div({
            className: 'full',
          }, [
            Label({
              for: 'edit-reply-content',
            }, __('Content', 'groundhogg')),
            Textarea({
              id       : 'edit-reply-content',
              className: 'full-width',
              value    : State.edit_content,
              onCreate : el => {
                try {
                  wp.editor.remove('edit-reply-content')
                }
                catch (err) {

                }

                setTimeout(() => {
                  addMediaToBasicTinyMCE()
                  tinymceElement('edit-reply-content', {
                    quicktags   : false,
                    replacements: true,
                  }, content => {
                    State.set({
                      edit_content: content,
                    })
                  })
                }, 10)
              },
            }),
          ]),
          Div({
            className: 'full display-flex flex-end gap-5',
          }, [
            Button({
              className: 'gh-button danger text',
              id       : 'cancel-reply-changes',
              type     : 'button',
              onClick  : e => {
                clearEditState()
                State.set({
                  adding : false,
                  editing: false,
                })

                morph()
              },
            }, 'Cancel'),
            Button({
              className: 'gh-button primary',
              id       : 'update-reply',
              type     : 'submit',
            }, State.adding ? strings.create : strings.update ),
          ]),
        ])
      }

      /**
       * The note itself
       *
       * @param note
       * @returns {*}
       * @constructor
       */
      const Reply = note => {

        const {
          content,
          summary,
        } = note.data

        return Div({
          className: 'reply',
          id       : `reply-item-${ note.ID }`,
          dataId   : note.ID,
        }, noteMorph => Fragment([

          Div({
            className: 'reply-header display-flex gap-5 align-center',
          }, [
            summary ? Span({
              className: 'summary',
              style    : {
                marginRight: 'auto',
                fontSize   : '14px',
                fontWeight : '500',
              },
            }, escHTML(summary)) : null,
            Button({
              className: 'gh-button primary icon text',
              onClick  : e => {
                State.set({
                  editing     : note.ID,
                  edit_summary: summary,
                  edit_content: content,
                })
                morph()
              },
            }, [
              Dashicon('edit'),
              ToolTip(__( 'Edit', 'groundhogg' ) ),
            ]),
            userHasCap( 'delete_others_notes' ) ? Button({
              className: 'gh-button danger icon text',
              onClick: e => {
                dangerConfirmationModal({
                  alert: `<p>${strings.deleteConfirmation}</p>`,
                  confirmText: __( 'Delete', 'groundhogg' ),
                  onConfirm: () => {
                    RepliesStore.delete(note.ID).then(() => {
                      State.set({
                        notes: State.notes.filter(id => id !== note.ID),
                      });
                      morph();
                    });
                  }
                })
              }
            }, [
              Dashicon('trash'),
              ToolTip(__( 'Delete', 'groundhogg' ) ),
            ]) : null,
          ]),
        ]))
      }

      let notes = State.notes.map(id => RepliesStore.get(id))

      return Fragment([

        Div({
          className: 'space-between',
        }, [
          H2({}, strings.manage ),
          Button({
            className: 'gh-button primary',
            onClick  : e => {
              State.set({
                adding: true,
              })
              morph()
            },
            /* translators: %s: the singular label of a saved reply, like "Reply" */
          }, strings.new ),
        ]),

        // Add Note Form
        State.adding ? EditReply() : null,
        ...notes.map(note => State.editing == note.ID ? EditReply(note) : Reply(note)),
        notes.length || State.adding ? null : Pg({
          style: {
            textAlign: 'center',
          },
          /* translators: %s: the plural label of saved replies, like "Saved Replies" */
        }, strings.noReplies ),
      ])
    })

  }

  Groundhogg.SavedReplies = SavedReplies

  Groundhogg.SavedRepliesModal = (props) => {
    Modal({
      width        : '600px',
      // dialogClasses: 'overflow-visible',
    }, Fragment([
      SavedReplies({
        single: __('Saved Reply', 'groundhogg'),
        plural: __('Saved Replies', 'groundhogg'),
        ...props,
      }),
    ]))
  }

  // $(()=>{
  //   Groundhogg.SavedRepliesModal()
  // })

} )(jQuery)
