( ($) => {

  const { tasks: TasksStore } = Groundhogg.stores
  const {
    icons,
    input,
    select,
    textarea,
    bold,
    tinymceElement,
    addMediaToBasicTinyMCE,
    moreMenu,
    tooltip,
    dangerConfirmationModal,
    spinner,
    dialog,
  } = Groundhogg.element
  const { post, get, patch, routes, ajax } = Groundhogg.api
  const { userHasCap } = Groundhogg.user
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n

  const typeToIcon = {
    call: icons.phone,
    task: icons.tasks,
    email: icons.email,
    meeting: icons.contact,
  }

  const taskTypes = {
    task: __('Task', 'groundhogg'),
    call: __('Call', 'groundhogg'),
    email: __('Email', 'groundhogg'),
    meeting: __('Meeting', 'groundhogg'),
  }

  const dueBy = (task) => {

    if (task.is_overdue) {
      return `<span class="pill orange" title="${ task.i18n.due_date }">${ sprintf(__('%s overdue', 'groundhogg'),
        task.i18n.due_in) }</span>`
    }

    if (task.is_complete) {
      return `<span class="pill green" title="${ task.i18n.completed_date }">${ sprintf(__('%s ago', 'groundhogg'),
        task.i18n.completed) }</span>`
    }

    return `<span class="pill" title="${ task.i18n.due_date }">${ sprintf(__('In %s', 'groundhogg'),
      task.i18n.due_in) }</span>`
  }

  const templates = {

    tasks: (tasks, title, { adding = false, editing = false, filter = t => true }) => {

      tasks = tasks.sort((a, b) => a.due_timestamp - b.due_timestamp)

      let overdue = tasks.filter(t => t.is_overdue)
      let complete = tasks.filter(t => t.is_complete)
      let pending = tasks.filter(t => !t.is_complete)

      // language=HTML
      return `
          <div class="tasks-widget">
              <div class="tasks-header">
                  ${ title ? `<h3>${ title }</h3>` : '' }
                  <div class="display-flex gap-10">
                      ${ pending.length ? `<span class="pill filter-tasks" data-filter="pending">${ sprintf(
                              __('%d pending', 'groundhogg'),
                              pending.length) }</span>` : '' }
                      ${ overdue.length ? `<span class="pill orange filter-tasks" data-filter="overdue">${ sprintf(
                              __('%d overdue', 'groundhogg'),
                              overdue.length) }</span>` : '' }
                      ${ complete.length ? `<span class="pill green filter-tasks" data-filter="complete">${ sprintf(
                              __('%d complete', 'groundhogg'),
                              complete.length) }</span>` : '' }
                  </div>
                  <button class="gh-button text icon secondary task-add">
                      <span class="dashicons dashicons-plus-alt2"></span>
                  </button>
              </div>
              <div class="tasks">
                  ${ adding ? templates.addTask(tasks) : `` }
                  ${ tasks.filter(filter).map(n => editing == n.ID ? templates.editTask(n) : templates.task(n)).
                          join('') }
              </div>
          </div>`
    },
    editTask: (task) => {

      let [due_date = '', due_time = ''] = task.data.due_date.split(' ')

      // language=HTML
      return `
          <div class="add-task">
              <div class="display-flex column gap-10">
                  <div class="label-with-input">
                      <label for="task-summary">${ __('Task summary') }</label>
                      ${ input({
                          name: 'summary',
                          id: 'task-summary',
                          className: 'full-width',
                          value: task.data.summary,
                      }) }
                  </div>
                  <div class="display-flex gap-20 stack-on-mobile">
                      <div class="label-with-input">
                          <label for="due-date">
                              ${ __('Due date') }
                          </label>
                          <div class="gh-input-group">
                              ${ input({
                                  type: 'date',
                                  className: '',
                                  required: true,
                                  name: 'due-date',
                                  id: 'due-date',
                                  value: due_date,
                              }) }
                              ${ input({
                                  type: 'time',
                                  className: '',
                                  required: false,
                                  name: 'due-time',
                                  id: 'due-time',
                                  value: due_time,
                              }) }
                          </div>
                      </div>
                      <div class="label-with-input">
                          <label for="task-type">${ __('Type', 'groundhogg') }</label>
                          ${ select({
                              id: 'task-type',
                              options: taskTypes,
                              selected: task.data.type,
                          }) }
                      </div>
                  </div>
                  <div class="label-with-input">
                      <label>${ __('Task details') }</label>
                      ${ textarea({
                          id: 'edit-task-editor',
                          value: task.data.content,
                      }) }
                  </div>
              </div>
              <div class="display-flex flex-end space-above-10">
                  <button class="gh-button danger text cancel">${ __('Cancel') }</button>
                  <button class="gh-button primary save">${ __('Save') }</button>
              </div>
          </div>`
    },
    addTask: () => {

      // language=HTML
      return `
          <div class="add-task">
              <div class="display-flex column gap-10">
                  <div class="label-with-input">
                      <label for="task-summary">${ __('Task summary') }</label>
                      ${ input({
                          name: 'summary',
                          id: 'task-summary',
                          className: 'full-width',
                      }) }
                  </div>
                  <div class="display-flex gap-20 stack-on-mobile">
                      <div class="label-with-input">
                          <label for="due-date">
                              ${ __('Due date') }
                          </label>
                          <div class="gh-input-group">
                              ${ input({
                                  type: 'date',
                                  className: '',
                                  required: true,
                                  name: 'due-date',
                                  id: 'due-date',
                              }) }
                              ${ input({
                                  type: 'time',
                                  className: '',
                                  required: false,
                                  name: 'due-time',
                                  id: 'due-time',
                                  value: '17:00:00',
                              }) }
                          </div>
                      </div>
                      <div class="label-with-input">
                          <label for="task-type">${ __('Type', 'groundhogg') }</label>
                          ${ select({ id: 'task-type', options: taskTypes }) }
                      </div>
                  </div>
                  <div class="label-with-input">
                      <label>${ __('Task details') }</label>
                      <textarea id="add-task-editor"></textarea>
                  </div>
              </div>
              <div class="display-flex flex-end space-above-10">
                  <button class="gh-button danger text cancel">${ __('Cancel') }</button>
                  <button class="gh-button primary create">${ __('Create') }</button>
              </div>
          </div>`
    },
    task: (task) => {

      const { content, type, context, user_id } = task.data
      const { summary } = task.esc_html


      const addedBy = () => {

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

      // language=HTML
      return `
          <div class="task ${ task.is_complete ? 'complete' : '' }" data-id="${ task.ID }">
              <div class="icon">
                  ${ typeToIcon[type] }
              </div>
              <div style="width: 100%">
                  <div class="task-header">
                      <div class="display-flex gap-10 align-center wrap">
                          ${ summary ? `<span class="summary"><b>${ summary }</b></span>` : '' }
                          ${ dueBy(task) }
                          <span class="added-by">${ addedBy() }</span>
                      </div>
                      <div class="actions display-flex">
                          ${ task.is_complete
                                  ? ''
                                  : `<button class="gh-button text icon primary mark-complete" data-id="${ task.ID }">
                              <span class="dashicons dashicons-thumbs-up"></span>
                          </button>` }
                          <button class="gh-button text icon secondary task-more" data-id="${ task.ID }">
                              ${ icons.verticalDots }
                          </button>
                      </div>
                  </div>
                  <div class="task-content space-above-10">
                      ${ content }
                  </div>
              </div>

          </div>`
    },
    myTasks: (tasks) => {

      tasks = tasks.sort((a, b) => a.due_timestamp - b.due_timestamp).slice(0, 10)

      // language=HTML
      return `
          <div class="my-tasks">
              ${ tasks.map(n => templates.myTask(n)).join('') }
          </div>`
    },
    myTask: (task) => {

      const { content, type } = task.data
      const { associated, ID } = task
      const { summary } = task.esc_html

      // language=HTML
      return `
          <div class="task" data-id="${ ID }">
              <div class="icon">
                  ${ typeToIcon[type] }
              </div>
              <div style="width: 100%">
                  <div class="task-header">
                      <div class="display-flex gap-10 align-center">
                          ${ summary ? `<span class="summary"><b>${ summary }</b></span>` : '' }
                          ${ dueBy(task) }—<a class="name" href="${ associated.link }">${ associated.name }</a>
                      </div>
                      <div class="actions display-flex">
                          <button class="gh-button text icon primary mark-complete" data-id="${ task.ID }">
                              <span class="dashicons dashicons-thumbs-up"></span>
                          </button>
                      </div>
                  </div>
                  <div class="task-content">
                      ${ content }
                  </div>
              </div>
          </div>`
    },
  }

  const ObjectTasks = (selector, {
    object_type = '',
    object_id = 0,
    title = __('Tasks', 'groundhogg'),
  }) => {

    let state = {
      adding: false,
      editing: false,
      filter: t => !t.is_complete,
    }

    const $el = $(selector)

    const render = () => {

      wp.editor.remove('edit-task-editor')
      wp.editor.remove('add-task-editor')

      const tasks = TasksStore.filter(({ data }) => data.object_type == object_type && data.object_id == object_id).
        sort((a, b) => a.due_timestamp - b.due_timestamp)

      $el.html(templates.tasks(tasks, title, state))
      onMount()
    }

    const onMount = () => {

      $(`${ selector } .filter-tasks`).on('click', (e) => {
        state.adding = false
        state.editing = false

        switch (e.currentTarget.dataset.filter) {
          case 'complete':
            state.filter = t => t.is_complete
            break
          case 'pending':
            state.filter = t => !t.is_complete
            break
          case 'overdue':
            state.filter = t => t.is_overdue
            break
        }

        render()
      })

      const addTask = () => {
        if (state.editing) {
          wp.editor.remove('edit-task-editor')
          state.editing = false
        }

        state.adding = true

        render()
      }

      const editTask = (id) => {

        if (state.adding) {
          wp.editor.remove('add-task-editor')
          state.adding = false
        }

        if (state.editing) {
          wp.editor.remove('edit-task-editor')
        }

        state.editing = id

        render()
      }

      $(`${ selector } .task-add`).on('click', () => {

        if (this.adding) {
          return
        }

        addTask()
      })

      if (!userHasCap('add_tasks')) {
        $('.task-add').remove()
      }

      tooltip(`${ selector } .task-add`, {
        content: __('Add Task', 'groundhogg'),
        position: 'left',
      })

      let due_date, due_time

      if (state.adding) {

        const newTask = {
          object_id,
          object_type,
          summary: '',
          content: '',
          type: 'task',
          due_date: '',
        }

        addMediaToBasicTinyMCE()

        let editor = tinymceElement('add-task-editor', {
          quicktags: false,
        }, (content) => {
          newTask.content = content
        })

        $(`${ selector } #due-date`).on('change', (e) => {
          due_date = e.target.value
          newTask.due_date = `${ due_date } ${ due_time }`
        })

        $(`${ selector } #due-time`).on('change', (e) => {
          due_time = e.target.value
          newTask.due_date = `${ due_date } ${ due_time }`
        })

        $(`${ selector } #task-summary`).on('input', (e) => {
          newTask.summary = e.target.value
        })

        $(`${ selector } #task-type`).on('change', (e) => {
          newTask.type = e.target.value
        })

        $(`${ selector } .cancel`).on('click', () => {
          state.adding = false
          render()
        })

        $(`${ selector } .create`).on('click', () => {
          state.adding = false
          state.editing = false
          state.filter = t => !t.is_complete

          TasksStore.post({
            data: {
              ...newTask,
              content: editor.getContent({ format: 'raw' }),
            },
          }).then(() => {
            render()
          })
        })
      }
      else if (state.editing) {

        const editedTask = TasksStore.get(state.editing)

        const updateTask = {
          content: editedTask.data.content,
          type: editedTask.data.type,
          due_date: editedTask.data.due_date,
        }

        let [due_date = '', due_time = ''] = updateTask.due_date.split(' ')

        let editor = tinymceElement('edit-task-editor', {
          quicktags: false,
        }, (content) => {
          updateTask.content = content
        })

        $(`${ selector } #task-summary`).on('input', (e) => {
          updateTask.summary = e.target.value
        })

        $(`${ selector } #task-type`).on('change', (e) => {
          updateTask.type = e.target.value
        })

        $(`${ selector } #due-date`).on('change', (e) => {
          due_date = e.target.value
          updateTask.due_date = `${ due_date } ${ due_time }`
        })

        $(`${ selector } #due-time`).on('change', (e) => {
          due_time = e.target.value
          updateTask.due_date = `${ due_date } ${ due_time }`
        })

        $(`${ selector } .cancel`).on('click', () => {
          state.editing = false
          render()
        })

        $(`${ selector } .save`).on('click', () => {
          state.adding = false

          TasksStore.patch(state.editing, {
            data: {
              ...updateTask,
              content: editor.getContent({ format: 'raw' }),
            },
          }).then(() => {
            state.editing = false
            render()
          })
        })
      }

      let curTask

      const task = () => TasksStore.get(curTask)
      const belongsToMe = () => task().data.user_id == Groundhogg.currentUser.ID

      tooltip(`${ selector } .mark-complete`, {
        content: __('Mark complete'),
        position: 'left',
      })

      $(`${ selector } .mark-complete`).on('click', (e) => {
        curTask = parseInt(e.currentTarget.dataset.id)

        $el.find(`.task[data-id="${ curTask }"]`).addClass('completing')

        TasksStore.complete(curTask).then(task => {
          dialog({
            message: __('Task completed!'),
          })

          render()
        })

      })

      $(`${ selector } .task-more`).on('click', (e) => {

        curTask = parseInt(e.currentTarget.dataset.id)

        let items = [
          {
            key: 'edit',
            cap: belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
            text: __('Edit'),
            onSelect: () => {
              editTask(curTask)
            },
          },
          {
            key: 'delete',
            cap: belongsToMe() ? 'delete_tasks' : 'delete_others_tasks',
            text: `<span class="gh-text danger">${ __('Delete') }</span>`,
            onSelect: () => {
              dangerConfirmationModal({
                alert: `<p>${ __('Are you sure you want to delete this task?', 'groundhogg') }</p>`,
                onConfirm: () => {
                  TasksStore.delete(curTask).then(() => render())
                },
              })
            },
          },
        ]

        if (task().is_complete) {
          items.unshift({
            key: 'incomplete',
            cap: belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
            text: __('Mark incomplete'),
            onSelect: () => {
              TasksStore.incomplete(curTask).then(() => {
                render()
              })
            },
          })
        }

        moreMenu(e.currentTarget, items.filter(i => userHasCap(i.cap)))
      })
    }

    if (!TasksStore.filter(n => n.data.object_type == object_type && n.data.object_id == object_id).length) {
      $el.html(spinner('gray'))
      TasksStore.fetchItems({
        object_id,
        object_type,
        limit: 9999,
      }).then(() => {
        render()
      })
    }
    else {
      render()
    }
  }

  let fetchedMyTasks = false

  const fetchMyTasks = () => {
    return TasksStore.fetchItems({
      orderby: 'due_date',
      order: 'ASC',
      limit: 30,
      // Only fetch incomplete tasks
      incomplete: true,
      user_id: Groundhogg.currentUser.ID,
    }).then(tasks => {
      fetchedMyTasks = true
      return tasks
    })
  }

  const MyTasks = (selector, {}) => {

    const $el = $(selector)

    const mount = () => {

      let tasks = TasksStore.getItems().filter(t => !t.is_complete)
      $el.html(templates.myTasks(tasks))
      onMount()
    }

    const onMount = () => {

      tooltip(`${ selector } .mark-complete`, {
        content: __('Mark complete'),
        position: 'left',
      })

      let curTask

      $(`${ selector } .mark-complete`).on('click', (e) => {
        curTask = parseInt(e.currentTarget.dataset.id)

        $el.find(`.task[data-id="${ curTask }"]`).addClass('completing')

        TasksStore.complete(curTask).then(task => {
          dialog({
            message: __('Task completed!'),
          })

          mount()
        }).catch(err => {
          dialog({
            message: err.message,
            type: 'error',
          })

          mount()
        })

      })

    }

    if (fetchedMyTasks) {
      mount()
    }
    else {
      $el.html(spinner('gray'))
      fetchMyTasks().then(mount)
    }
  }

  Groundhogg.taskEditor = ObjectTasks
  Groundhogg.myTasks = MyTasks

} )(jQuery)
