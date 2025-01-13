( ($) => {

  const { tasks: TasksStore } = Groundhogg.stores
  const {
    icons,
    tinymceElement,
    addMediaToBasicTinyMCE,
    moreMenu,
    dangerConfirmationModal,
    dialog,
    escHTML,
  } = Groundhogg.element
  const {
    getOwner,
  } = Groundhogg.user
  const {
    userHasCap,
    getCurrentUser,
  } = Groundhogg.user
  const {
    formatDateTime,
  } = Groundhogg.formatting
  const {
    sprintf,
    __,
  } = wp.i18n

  const typeToIcon = {
    call   : icons.phone,
    task   : icons.tasks,
    email  : icons.email,
    meeting: icons.contact,
  }

  const taskTypes = {
    task   : __('Task', 'groundhogg'),
    call   : __('Call', 'groundhogg'),
    email  : __('Email', 'groundhogg'),
    meeting: __('Meeting', 'groundhogg'),
  }

  const isOverdue = t => t.is_overdue && !t.is_complete
  const isComplete = t => t.is_complete
  const isPending = t => !t.is_complete
  const isDueToday = t => t.is_due_today && !t.is_complete
  const isDueSoon = t => t.days_till_due < 14 && !t.is_overdue && !t.is_due_today && !t.is_complete

  const dueBy = (task) => {

    if (isOverdue(task)) {
      return `<span class="pill red" title="${ task.i18n.due_date }">${ sprintf(__('%s overdue', 'groundhogg'),
        task.i18n.due_in) }</span>`
    }

    if (isComplete(task)) {
      return `<span class="pill green" title="${ task.i18n.completed_date }">${ sprintf(__('%s ago', 'groundhogg'),
        task.i18n.completed) }</span>`
    }

    let color = ''

    if (isDueToday(task)) {
      color = 'orange'
    }
    else if (isDueSoon(task)) {
      color = 'yellow'
    }

    return `<span class="pill ${ color }" title="${ task.i18n.due_date }">${ sprintf(__('In %s', 'groundhogg'),
      task.i18n.due_in) }</span>`
  }

  const addedBy = (task) => {

    const {
      context,
      user_id,
    } = task.data

    let date_created = `<abbr title="${ formatDateTime(task.data.date_created) }">${ task.i18n.time_diff }</abbr>`

    switch (context) {
      case 'user':
        let user = Groundhogg.filters.owners.find(o => o.ID == user_id)
        let username

        if (!user) {
          username = __('Unknown')
        }
        else {
          username = user.ID == Groundhogg.currentUser.ID ? __('me') : user.data.display_name
        }

        return sprintf(__('Added by %s %s ago', 'groundhogg'), username, date_created)

      default:
      case 'system':
        return sprintf(__('Added by %s %s ago', 'groundhogg'), __('System'), date_created)
      case 'funnel':
        return sprintf(__('Added by %s %s ago', 'groundhogg'), __('Funnel'), date_created)
    }
  }

  const {
    Div,
    Form,
    Span,
    H2,
    H3,
    An,
    Img,
    Button,
    Dashicon,
    ToolTip,
    Fragment,
    Skeleton,
    InputGroup,
    Label,
    Select,
    Input,
    Textarea,
    Pg,
    ItemPicker,
  } = MakeEl

  const BetterObjectTasks = ({
    object_type = '',
    object_id = 0,
    title = __('Tasks', 'groundhogg'),
    ...props
  } = {}) => {

    const State = Groundhogg.createState({
      adding      : false,
      editing     : false,
      bulk_edit   : false,
      filter      : isPending,
      tasks       : [],
      selected    : [],
      loaded      : false,
      edit_summary: '',
      edit_date   : '',
      edit_time   : '',
      edit_content: '',
      assigned_to : 0,
      edit_type   : 'task',
      myTasks     : !( object_id && object_type ),
    })

    const clearEditState = () => State.set({
      edit_summary: '',
      edit_date   : '',
      edit_time   : '',
      edit_content: '',
      assigned_to : 0,
      edit_type   : 'task',
      editing     : false,
    })

    const fetchTasks = () => {

      let query = {
        limit  : 99,
        orderby: 'due_date',
        order  : 'ASC',
      }

      // tasks for anything, but only assigned to the current user
      if (!object_type && !object_id) {
        query = {
          user_id   : getCurrentUser().ID,
          incomplete: true,
          ...query,
        }
      }
      else {
        query = {
          object_id,
          object_type,
          ...query,
        }
      }

      return TasksStore.fetchItems(query).then(tasks => {

        State.set({
          loaded: true,
          tasks : tasks.map(({ ID }) => ID),
        })

        return tasks

      })
    }

    return Div({
      ...props,

      id       : 'my-tasks',
      className: 'tasks-widget',
    }, morph => {

      if (!State.loaded) {

        fetchTasks().then(morph)

        return Skeleton({}, [
          'full',
          'full',
          'full',
        ])

      }

      /**
       * The form for adding/editing the task details
       *
       * @returns {*}
       * @constructor
       */
      const TaskDetails = () => {

        return Form({
          className: 'task display-grid gap-10',
          onSubmit : e => {
            e.preventDefault()

            if (State.adding) {

              TasksStore.post({
                data: {
                  object_id,
                  object_type,
                  summary : State.edit_summary,
                  content : State.edit_content,
                  user_id : State.assigned_to,
                  type    : State.edit_type,
                  due_date: `${ State.edit_date } ${ State.edit_time }`,
                },
              }).then(task => {

                State.set({
                  adding: false,
                  tasks : [
                    ...State.tasks,
                    task.ID,
                  ], // add the new task ID
                })

                clearEditState()

                morph()
              })

            }
            else {

              TasksStore.patch(State.editing, {
                data: {
                  summary : State.edit_summary,
                  content : State.edit_content,
                  type    : State.edit_type,
                  user_id : State.assigned_to,
                  due_date: `${ State.edit_date } ${ State.edit_time }`,
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
                for: 'task-summary',
              }, __('Task summary')),
              Input({
                className: 'full-width',
                id       : 'task-summary',
                name     : 'summary',
                required : true,
                value    : State.edit_summary,
                onChange : e => State.set({
                  edit_summary: e.target.value,
                }),
              }),
            ]),

            Div({
              className: '',
            }, [
              Label({
                for: 'task-type',
              }, __('Type')),
              `<br>`,
              Select({
                id      : 'task-type',
                options : taskTypes,
                selected: State.edit_type,
                onChange: e => State.set({
                  edit_type: e.target.value,
                }),
              }),
            ]),
          ]),
          Div({
            className: 'half',
          }, [
            Label({
              for: 'task-date',
            }, __('Due Date')),
            InputGroup([
              Input({
                type     : 'date',
                id       : 'task-date',
                className: 'full-width',
                value    : State.edit_date,
                onChange : e => State.set({
                  edit_date: e.target.value,
                }),
              }),
              Input({
                type     : 'time',
                id       : 'task-time',
                name     : 'time',
                className: 'full-width',
                value    : State.edit_time,
                onChange : e => State.set({
                  edit_time: e.target.value,
                }),
              }),
            ]),
          ]),
          Div({
            className: 'half',
          }, [
            Label({
              for: 'task-assigned-to',
            }, __('Assigned To')),
            `<br>`,
            ItemPicker({
              id          : `task-assigned-to`,
              noneSelected: __('Select a user...', 'groundhogg'),
              selected    : State.assigned_to ? {
                id  : State.assigned_to,
                text: getOwner(State.assigned_to).data.display_name,
              } : [],
              multiple    : false,
              style       : {
                flexGrow: 1,
              },
              fetchOptions: (search) => {
                search = new RegExp(search, 'i')
                let options = Groundhogg.filters.owners.map(u => ( {
                  id  : u.ID,
                  text: u.data.display_name,
                } )).filter(({ text }) => text.match(search))
                return Promise.resolve(options)
              },
              onChange    : item => {
                State.set({
                  assigned_to: item.id,
                })
              },
            }),
          ]),
          Div({
            className: 'full',
          }, [
            Label({
              for: 'edit-task-content',
            }, __('Details')),
            Textarea({
              id       : 'edit-task-content',
              className: 'full-width',
              value    : State.edit_content,
              onCreate : el => {
                try {
                  wp.editor.remove('edit-task-content')
                }
                catch (err) {

                }

                setTimeout(() => {
                  addMediaToBasicTinyMCE()
                  tinymceElement('edit-task-content', {
                    quicktags: false,
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
              id       : 'cancel-task-changes',
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
              id       : 'update-task',
              type     : 'submit',
            }, State.adding ? 'Create Task' : 'Update Task'),
          ]),
        ])
      }

      /**
       * The task itself
       *
       * @param task
       * @returns {*}
       * @constructor
       */
      const Task = task => {

        const {
          content,
          type,
          due_date,
          context,
          user_id,
          summary,
        } = task.data

        /**
         * If the task belongs to the current user
         *
         * @returns {boolean}
         */
        const belongsToMe = () => user_id == Groundhogg.currentUser.ID

        let assocIcon = null

        if (task.associated.img) {
          assocIcon = Img({
            src: task.associated.img,
          })
        }
        else if (task.associated.icon) {
          assocIcon = Dashicon(task.associated.icon)
        }

        return Div({
          className: `task ${ task.is_complete ? 'complete' : 'pending' } ${ task.is_overdue ? 'overdue' : '' }`,
          id       : `task-item-${ task.ID }`,
          dataId   : task.ID,
        }, [

          Div({
            className: 'task-header',
          }, [
            typeToIcon[type],
            Input({
              type     : 'checkbox',
              name     : 'tasks[]',
              className: 'select-task',
              checked  : State.selected.includes(task.ID),
              onChange : e => {
                if (e.target.checked) {
                  State.selected.push(task.ID)
                }
                else {
                  State.set({
                    selected: State.selected.filter(id => id !== task.ID),
                  })
                }
                morph()
              },
            }),
            summary ? Span({ className: 'summary' }, escHTML(summary)) : null,
            Div({
              className: 'display-flex',
              style    : {
                marginLeft: 'auto',
              },
            }, [
              task.is_complete ? null : Button({
                id       : `task-mark-complete-${ task.ID }`,
                className: 'gh-button text icon primary mark-complete',
                onClick  : e => {
                  document.getElementById(`task-item-${ task.ID }`).classList.add('completing')
                  TasksStore.complete(task.ID).then(task => {
                    dialog({
                      message: __('Task completed!'),
                    })

                    morph()
                  })
                },
              }, [
                Dashicon('thumbs-up'),
                ToolTip(__('Mark complete', 'groundhogg'), 'left'),
              ]),
              Button({
                id       : `task-actions-${ task.ID }`,
                className: 'gh-button text icon secondary task-more',
                onClick  : e => {

                  let items = [
                    {
                      key     : 'edit',
                      cap     : belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
                      text    : __('Edit'),
                      onSelect: () => {
                        State.set({
                          editing     : task.ID,
                          edit_summary: summary,
                          edit_date   : due_date.split(' ')[0],
                          edit_time   : due_date.split(' ')[1],
                          edit_content: content,
                          edit_type   : type,
                          assigned_to : user_id,
                        })
                        morph()
                      },
                    },
                    {
                      key     : 'delete',
                      cap     : belongsToMe() ? 'delete_tasks' : 'delete_others_tasks',
                      text    : `<span class="gh-text danger">${ __('Delete') }</span>`,
                      onSelect: () => {
                        dangerConfirmationModal({
                          alert    : `<p>${ __('Are you sure you want to delete this task?', 'groundhogg') }</p>`,
                          onConfirm: () => {
                            TasksStore.delete(task.ID).then(() => {
                              // also remove from state
                              State.tasks.splice(State.tasks.indexOf(task.ID), 1)
                              morph()
                            })
                          },
                        })
                      },
                    },
                  ]

                  if (isDueSoon(task) || isDueToday(task) || isOverdue(task)) {
                    items.unshift({
                      key     : 'incomplete',
                      cap     : belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
                      text    : __('Snooze'),
                      onSelect: () => {
                        TasksStore.patch(task.ID, {
                          data: { snooze: 1 },
                        }).then(() => {
                          morph()
                        })
                      },
                    })
                  }

                  if (task.is_complete) {
                    items.unshift({
                      key     : 'incomplete',
                      cap     : belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
                      text    : __('Mark incomplete'),
                      onSelect: () => {
                        TasksStore.incomplete(task.ID).then(() => {
                          morph()
                        })
                      },
                    })
                  }

                  moreMenu(e.currentTarget, items.filter(i => userHasCap(i.cap)))

                },
              }, icons.verticalDots),
            ]),
          ]),
          !object_id ? An({
            className: 'associated-object',
            href     : task.associated.link,
            style    : {
              width: 'fit-content',
            },
          }, [
            assocIcon,
            task.associated.name,
          ]) : null,
          Div({
              className: 'display-flex gap-5 align-center details flex-wrap',
            }, [
              dueBy(task),
              Span({ className: 'added-by' }, addedBy(task)),
            ],
          ),
          Div({
            className: 'task-content space-above-10',
          }, content),

        ])

      }

      let tasks = State.tasks.map(id => TasksStore.get(id))

      tasks = tasks.sort((a, b) => a.due_timestamp - b.due_timestamp)

      let filteredTasks = tasks.filter(State.filter)

      /**
       * Update the current filter on the tasks
       *
       * @param filter
       */
      const setFilter = filter => {
        State.set({
          filter,
          adding : false,
          editing: false,
        })
        morph()
      }

      const FilterPill = ({
        id = '',
        text = '',
        color = '',
        filter = isPending,
      }) => {

        let num = tasks.filter(filter).length

        if (!num) {
          return null
        }

        return Span({
          id       : `filter-${ id || color }`,
          className: `pill ${ color } clickable ${ State.filter === filter ? 'bold' : '' }`,
          onClick  : e => setFilter(filter),
        }, sprintf(text, num))
      }

      return Fragment([
        title ? H3({}, title) : null,

        object_id || tasks.length ? Div({
          className: 'tasks-header',
        }, [
          FilterPill({
            id    : 'overdue',
            text  : __('%d overdue', 'groundhogg'),
            color : 'red',
            filter: isOverdue,
          }),

          FilterPill({
            id    : 'due-today',
            text  : __('%d due today', 'groundhogg'),
            color : 'orange',
            filter: isDueToday,
          }),

          FilterPill({
            id    : 'due-soon',
            text  : __('%d due soon', 'groundhogg'),
            color : 'yellow',
            filter: isDueSoon,
          }),

          FilterPill({
            id    : 'pending',
            text  : __('%d pending', 'groundhogg'),
            filter: isPending,
          }),

          FilterPill({
            id    : 'complete',
            text  : __('%d complete', 'groundhogg'),
            color : 'green',
            filter: isComplete,
          }),

          userHasCap('add_tasks') && object_id ? Button({
            id       : 'add-tasks',
            className: 'gh-button secondary text icon',
            onClick  : e => {

              if (State.editing) {
                clearEditState()
              }

              State.set({
                adding: true,
              })

              morph()
            },
          }, [
            Dashicon('plus-alt2'),
            ToolTip('Add Task', 'left'),
          ]) : null,
        ]) : null,
        State.selected.length ? Div({
          className: 'display-flex gap-5',
          style    : {
            padding: '0 0 10px 10px',
          },
        }, [
          // Edit
          Button({
            className: `gh-button ${ State.bulk_edit ? 'primary' : 'secondary' } small`,
            onClick  : e => {
              State.set({
                bulk_edit: !State.bulk_edit,
              })

              if ( State.bulk_edit ){
                clearEditState()
                State.set({
                  edit_type: '', // no default type
                })
              }
              morph()
            },
          }, __('Edit')),
          // Snooze
          Button({
            className: 'gh-button secondary small',
            disabled : State.bulk_edit,
            onClick  : e => {

              TasksStore.patchMany({
                query: {
                  include: State.selected,
                },
                data : { snooze: 1 },
              }).then(() => {
                dialog({
                  message: sprintf('%d tasks snoozed!', State.selected.length),
                })
                morph()
              })

            },
          }, __('Snooze')),
          // Delete
          Button({
            className: 'gh-button danger small',
            disabled : State.bulk_edit,
            onClick  : e => {
              dangerConfirmationModal({
                alert    : `<p>${ sprintf(__('Are you sure you want to delete these %d tasks?', 'groundhogg'), State.selected.length) }</p>`,
                onConfirm: () => {
                  TasksStore.deleteMany({
                    include: State.selected,
                  }).then(() => {
                    dialog({
                      message: sprintf('%d tasks deleted!', State.selected.length),
                    })
                    // also remove from state
                    State.set({
                      tasks   : State.tasks.filter(task => !State.selected.includes(task)),
                      selected: [],
                    })
                    morph()
                  })
                },
              })
            },
          }, __('Delete')),
          // Mark complete
          Button({
            className: 'gh-button primary small',
            disabled : State.bulk_edit,
            onClick  : e => {
              TasksStore.patchMany({
                query: {
                  include: State.selected,
                },
                data : { complete: 1 },
              }).then(() => {
                dialog({
                  message: sprintf('%d tasks snoozed!', State.selected.length),
                })
                morph()
              })
            },
          }, __('Mark Complete')),
          // Mark complete
          Button({
            className: 'gh-button danger text small',
            onClick  : e => {
              State.set({
                bulk_edit: false,
                selected : [],
              })
              morph()
            },
          }, __('Cancel')),
        ]) : null,
        // Bulk Edit
        State.bulk_edit ? Div({
          className: 'display-flex gap-10 align-bottom flex-wrap',
          style    : {
            padding: '0 10px 10px 10px',
          },
        }, [

          Div({}, [
            Label({
              for: 'task-type',
            }, __('Type')),
            `<br>`,
            Select({
              id      : 'task-type',
              options : {
                '' : 'No change',
                ...taskTypes
              },
              selected: State.edit_type,
              onChange: e => State.set({
                edit_type: e.target.value,
              }),
            }),
          ]),

          Div({}, [
            Label({
              for: 'task-date',
            }, __('Due Date')),
            InputGroup([
              Input({
                type     : 'date',
                id       : 'task-date',
                className: 'full-width',
                value    : State.edit_date,
                onChange : e => State.set({
                  edit_date: e.target.value,
                }),
              }),
              Input({
                type     : 'time',
                id       : 'task-time',
                name     : 'time',
                className: 'full-width',
                value    : State.edit_time,
                onChange : e => State.set({
                  edit_time: e.target.value,
                }),
              }),
            ]),
          ]),

          Div({}, [
            Label({
              for: 'task-assigned-to',
            }, __('Assigned To')),
            `<br>`,
            ItemPicker({
              id          : `task-assigned-to`,
              noneSelected: __('Assign to a new user...', 'groundhogg'),
              selected    : State.assigned_to ? {
                id  : State.assigned_to,
                text: getOwner(State.assigned_to).data.display_name,
              } : [],
              multiple    : false,
              style       : {
                flexGrow: 1,
              },
              fetchOptions: (search) => {
                search = new RegExp(search, 'i')
                let options = Groundhogg.filters.owners.map(u => ( {
                  id  : u.ID,
                  text: u.data.display_name,
                } )).filter(({ text }) => text.match(search))
                return Promise.resolve(options)
              },
              onChange    : item => {
                State.set({
                  assigned_to: item.id,
                })
              },
            }),
          ]),
          Button({
            className: 'gh-button primary',
            onClick  : e => {

              let data = {}

              if (State.edit_date && State.edit_time) {
                data.due_date = `${ State.edit_date } ${ State.edit_time }`
              }

              if (State.assigned_to) {
                data.user_id = State.assigned_to
              }

              if (State.edit_type) {
                data.type = State.edit_type
              }

              TasksStore.patchMany({
                query: {
                  include: State.selected,
                },
                data,
              }).then(() => {
                clearEditState()
                State.set({
                  bulk_edit: false,
                })
                dialog({
                  message: sprintf('%d tasks updated!', State.selected.length),
                })
                morph()
              })
            },
          }, __('Update')),
        ]) : null,
        // Add Task Form
        State.adding ? TaskDetails() : null,
        ...filteredTasks.map(task => State.editing == task.ID ? TaskDetails(task) : Task(task)),
        tasks.length || State.adding ? null : Pg({
          style: {
            textAlign: 'center',
          },
        }, __('🎉 No pending tasks!', 'groundhogg')),
      ])
    })

  }

  const ObjectTasks = (selector, {
    object_type = '',
    object_id = 0,
    title = __('Tasks', 'groundhogg'),
  }) => {

    document.querySelector(selector).append(BetterObjectTasks({
      object_type,
      object_id,
      title,
    }))
  }

  const MyTasks = (selector, props) => {
    document.querySelector(selector).append(BetterObjectTasks({
      title: false,
    }))
  }

  Groundhogg.taskEditor = ObjectTasks
  Groundhogg.ObjectTasks = BetterObjectTasks
  Groundhogg.myTasks = MyTasks

} )(jQuery)
