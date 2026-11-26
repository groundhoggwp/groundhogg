/**
 * An editor that slides in from the right of a list table, like the flow editor's step settings. There's no overlay,
 * so the menu and the table stay usable while it's open.
 *
 * The page supplies what to load, what to show and how to save. This handles opening and closing, the sticky header
 * with the name and save button, unsaved changes, the row being edited and keeping the open item across reloads.
 */
( ($) => {

  const {
    dialog,
    loadingDots,
    icons,
    moreMenu,
    dangerConfirmationModal,
  } = Groundhogg.element

  const {
    Div,
    Button,
    Input,
    Dashicon,
    Pg,
    Bold,
    Label,
    H2,
    Skeleton,
    Toggle,
  } = MakeEl

  const { __ } = wp.i18n

  /**
   * A panel with a header
   */
  const Panel = ({
    id,
    title,
    actions = null,
    className = '',
    style = {},
  }, children) => Div({
    id,
    className: `gh-panel ${ className }`,
    style,
  }, [
    Div({
      className: 'gh-panel-header',
    }, [
      H2({}, title),
      actions,
    ]),
    Div({
      className: 'inside display-flex column gap-10',
    }, children),
  ])

  /**
   * A field with its label above it
   */
  const Field = (label, field, description = '') => Div({
    className: 'display-flex column gap-5',
  }, [
    Label({}, Bold({}, label)),
    field,
    description ? Pg({
      className: 'description',
      style    : { margin: 0 },
    }, description) : null,
  ])

  /**
   * Read only text that is selected when focused, with a button that copies it
   */
  const CopyField = (id, value) => Div({
    className: 'gh-input-group',
  }, [
    Input({
      id,
      className: 'code full-width',
      readonly : true,
      value,
      onFocus  : e => e.target.select(),
    }),
    Button({
      id       : `${ id }-copy`,
      className: 'gh-button secondary icon',
      title    : __('Copy', 'groundhogg'),
      onClick  : () => {
        navigator.clipboard.writeText(value).then(() => dialog({
          message: __('Copied!', 'groundhogg'),
        }))
      },
    }, Dashicon('admin-page')),
  ])

  /**
   * Fields rendered by PHP, such as the ones add-ons put in the old edit screens. Changing one is an unsaved change.
   *
   * @param id string
   * @param html string
   * @param changed function
   */
  const ServerFields = (id, html, changed) => Div({
    id,
    className: 'gh-sidebar-editor-server-fields',
    onCreate : el => {
      el.innerHTML = html
      el.addEventListener('input', changed)
      el.addEventListener('change', changed)
    },
  })

  /**
   * The values of the fields in an element, for a form post
   *
   * @param id string
   * @returns {{name: string, value: string}[]}
   */
  const serializeFields = id => $(`#${ id } :input`).serializeArray()

  /**
   * @param config object
   * @param config.id string prefixes the editor's element IDs, like "tag"
   * @param config.label string the editor's accessible name, like "Edit tag"
   * @param config.load function given the ID returns what the editor shows, or a promise of it, with a row's HTML in
   *                    `row` if it changed
   * @param config.draft function given what was loaded, returns the values the editor changes
   * @param config.name object|null { key, placeholder } the draft value the header's name field edits
   * @param config.title function|null given { item, draft }, what the header shows in place of a name field
   * @param config.headerActions function given the item, returns more elements for the header, before the save button
   * @param config.onRender function given { item, draft, changed }, called once the editor is on the page, for
   *                        fields that are set up by their ID
   * @param config.toggle object|null { key, on, off, onLabel, offLabel, title } a switch in the header
   * @param config.status object|null { key, active, inactive, confirmDeactivate, messages: { activated, deactivated } }
   *                      Activate and Deactivate buttons around the save button, like the flow editor's header. They
   *                      set the draft value and save, along with any other changes. confirmDeactivate is optional,
   *                      the alert and confirmText of a dangerConfirmationModal shown before deactivating.
   * @param config.panels function given { item, draft, changed }, returns the panels below the header
   * @param config.save function async, given { item, draft } returns the saved item, with the row's HTML in `row`
   * @param config.moreActions function given the item, returns more menu items, each { key, text, onSelect }
   * @param config.editLinks string selector for links that open an item, with the ID in data-id
   * @param config.messages object { saved, saveFailed, discard }, an empty saved message shows nothing
   * @param config.urlParam string|null keeps the open item across reloads, null for editors that aren't a page's own
   * @param config.rows bool whether the items are rows of the page's list table, to highlight the open one
   */
  const SidebarEditor = ({
    id,
    label = '',
    load,
    draft: makeDraft,
    name: nameField = null,
    title = null,
    headerActions = () => [],
    onRender = () => {},
    rows = true,
    toggle = null,
    status = null,
    panels,
    save: saveItem,
    moreActions = () => [],
    editLinks = '',
    messages = {},
    urlParam = 'editing',
  }) => {

    messages = {
      saved     : __('Saved!', 'groundhogg'),
      saveFailed: __('Your changes could not be saved.', 'groundhogg'),
      discard   : __('You have unsaved changes. Discard them?', 'groundhogg'),
      ...messages,
    }

    if (status) {
      status = {
        ...status,
        messages: {
          activated  : __('Activated!', 'groundhogg'),
          deactivated: __('Deactivated!', 'groundhogg'),
          ...status.messages,
        },
      }
    }

    const paneId = `${ id }-editor`

    const pane = () => {

      let el = document.getElementById(paneId)

      if (!el) {
        el = document.createElement('aside')
        el.id = paneId
        el.className = 'gh-sidebar-editor'
        el.setAttribute('aria-label', label)
        document.body.append(el)
      }

      return el
    }

    const rowOf = itemId => document.querySelector(`#the-list > tr[id="${ itemId }"]`)

    const replaceRow = (itemId, html) => {

      if (!html || !rows) {
        return
      }

      let row = rowOf(itemId)

      if (row) {
        row.outerHTML = html
      }
      else {
        let list = document.getElementById('the-list')
        // the table's "no items" row
        list.querySelectorAll('tr.no-items').forEach(row => row.remove())
        list.insertAdjacentHTML('afterbegin', html)
      }
    }

    // the item open in the editor
    let current = null

    const markActiveRow = () => {
      if (!rows) {
        return
      }
      document.querySelectorAll('#the-list > tr.gh-sidebar-editing').forEach(row => row.classList.remove('gh-sidebar-editing'))
      if (current) {
        let row = rowOf(current.id)
        if (row) {
          row.classList.add('gh-sidebar-editing')
        }
      }
    }

    const setUrlParam = itemId => {
      if (!urlParam) {
        return
      }
      const url = new URL(window.location.href)
      if (itemId) {
        url.searchParams.set(urlParam, itemId)
      }
      else {
        url.searchParams.delete(urlParam)
      }
      window.history.replaceState({}, '', url)
    }

    // the panel starts below the page's sticky header, which would otherwise cover its top
    const fitBelowHeader = () => {
      let header = document.querySelector('.admin-page-header')
      if (header) {
        pane().style.top = `${ Math.max(0, Math.round(header.getBoundingClientRect().bottom)) }px`
      }
    }

    const show = content => {

      // last on the page, so it opens above a modal it was opened from, and modals opened from it go above it
      if (!pane().classList.contains('open')) {
        document.body.append(pane())
      }

      pane().innerHTML = ''
      pane().append(content)
      pane().scrollTop = 0
      fitBelowHeader()
      pane().classList.add('open')
    }

    /**
     * Ask before throwing away unsaved changes
     */
    const maybeDiscard = () => new Promise(resolve => {

      if (!current || !current.dirty) {
        resolve()
        return
      }

      dangerConfirmationModal({
        alert      : `<p>${ messages.discard }</p>`,
        confirmText: __('Discard', 'groundhogg'),
        onConfirm  : resolve,
      })
    })

    const close = () => {
      current = null
      pane().classList.remove('open')
      markActiveRow()
      setUrlParam(null)

      // once it has slid out, unless another item was opened since
      setTimeout(() => {
        if (!current) {
          pane().innerHTML = ''
        }
      }, 300)
    }

    /**
     * Stands in for the editor while an item loads: the header, then a couple of panels
     */
    const EditorSkeleton = () => Div({
      className: 'gh-sidebar-editor-inner display-flex column gap-20',
    }, [
      Div({
        className: 'gh-panel gh-sidebar-editor-header',
      }, Div({ className: 'inside' }, Skeleton({}, [
        'span-9',
        'span-3',
      ]))),
      ...[
        [
          'span-12',
          'span-12',
        ],
        [
          'span-12',
        ],
      ].map(pieces => Div({
        className: 'gh-panel',
      }, Div({ className: 'inside' }, Skeleton({}, pieces)))),
    ])

    /**
     * Shows a loaded item in the editor
     *
     * @param item object
     */
    const render = item => {

      const draft = makeDraft(item)

      current = {
        id   : item.ID,
        item,
        draft,
        dirty : false,
        saving: false,
      }

      // the editor that's open now, for the buttons to change after a save
      const editing = current

      // the save button's text and whether it can be clicked follow the unsaved changes
      const SaveButton = (className = 'gh-button primary') => Button({
        id       : `save-${ id }`,
        className,
        disabled : !editing.dirty || editing.saving,
        onClick  : () => save(),
      }, editing.dirty ? __('Save changes', 'groundhogg') : __('Saved', 'groundhogg'))

      /**
       * Like the flow editor's header: Deactivate and Save when active, Save and Activate when not
       */
      const StatusButtons = () => draft[status.key] === status.active ? [
        Button({
          id       : `${ id }-deactivate`,
          className: 'gh-button danger text',
          disabled : editing.saving,
          onClick  : () => {
            if (!status.confirmDeactivate) {
              setStatus(status.inactive)
              return
            }
            dangerConfirmationModal({
              ...status.confirmDeactivate,
              onConfirm: () => setStatus(status.inactive),
            })
          },
        }, __('Deactivate', 'groundhogg')),
        SaveButton(),
      ] : [
        SaveButton('gh-button secondary'),
        Button({
          id       : `${ id }-activate`,
          className: 'gh-button action',
          disabled : editing.saving,
          onClick  : () => setStatus(status.active),
        }, __('Activate', 'groundhogg')),
      ]

      // the buttons that save, which change with the status
      const Buttons = () => Div({
        id       : `${ id }-editor-buttons`,
        className: 'gh-sidebar-editor-buttons display-flex gap-10 align-center',
      }, status ? StatusButtons() : SaveButton())

      const refreshButtons = () => {
        let buttons = document.getElementById(`${ id }-editor-buttons`)
        if (buttons && current === editing) {
          buttons.replaceWith(Buttons())
        }
      }

      editing.refreshButtons = refreshButtons

      const changed = () => {
        if (!editing.dirty) {
          editing.dirty = true
          refreshButtons()
        }
      }

      /**
       * @param message string shown when it saved
       * @param button Element the button that was clicked, shows it's saving
       * @param failed function called when it didn't save
       */
      const save = async ({
        message = messages.saved,
        button = document.getElementById(`save-${ id }`),
        failed = () => {},
      } = {}) => {

        if (editing.saving) {
          return
        }

        const wasDirty = editing.dirty

        // nothing else saves until this is done
        editing.saving = true
        document.querySelectorAll(`#${ id }-editor-buttons button`).forEach(el => el.disabled = true)
        const { stop } = loadingDots(button)

        // changes made while it saves are still unsaved
        editing.dirty = false

        let saved

        try {
          saved = await saveItem({
            item,
            draft,
          })
        }
        catch (err) {
          editing.dirty = wasDirty || editing.dirty
          failed()
          dialog({
            message: err.message || messages.saveFailed,
            type   : 'error',
          })
        }

        stop()
        editing.saving = false

        if (saved) {
          replaceRow(saved.ID, saved.row)
          markActiveRow()

          // an empty message for editors whose pages say so themselves
          if (message) {
            dialog({
              message,
            })
          }
        }

        // it was closed, or another item was opened, while it saved
        if (current !== editing) {
          return
        }

        if (saved && !editing.dirty) {
          // show what was saved, like a new slug in a link, where they were
          const scrolled = pane().scrollTop
          render(saved)
          pane().scrollTop = scrolled
          return
        }

        if (saved) {
          // what changed while it saved is saved next time
          item = saved
          editing.item = saved
        }

        refreshButtons()
      }

      /**
       * Activate or deactivate, saving any other changes with it
       *
       * @param value string
       */
      const setStatus = value => {

        if (editing.saving) {
          return
        }

        const was = draft[status.key]
        draft[status.key] = value

        save({
          message: value === status.active ? status.messages.activated : status.messages.deactivated,
          button : document.getElementById(value === status.active ? `${ id }-activate` : `${ id }-deactivate`),
          failed : () => {
            draft[status.key] = was
          },
        })
      }

      const more = moreActions(item)

      show(Div({
        className: 'gh-sidebar-editor-inner display-flex column gap-20',
      }, [
        // stays at the top of the pane while the rest scrolls
        Div({
          id       : `${ id }-editor-header`,
          className: 'gh-panel gh-sidebar-editor-header',
        }, Div({
          className: 'inside display-flex gap-10 align-center',
        }, [
          title ? Div({
            className: 'gh-sidebar-editor-title display-flex gap-10 align-center',
          }, title({
            item,
            draft,
          })) : Input({
            // not `${ id }-name`, which the add forms beside tables use
            id         : `${ id }-editor-name`,
            className  : 'full-width',
            value      : draft[nameField.key] ?? '',
            placeholder: nameField.placeholder ?? '',
            style      : {
              fontSize  : '16px',
              fontWeight: 600,
            },
            onInput    : e => {
              draft[nameField.key] = e.target.value
              changed()
            },
            onKeydown  : e => {
              if (e.key === 'Enter' && editing.dirty) {
                save()
              }
            },
          }),
          toggle ? Div({
            className: 'gh-sidebar-editor-toggle',
            title    : toggle.title ?? '',
          }, Toggle({
            id      : `${ id }-editor-${ toggle.key }`,
            onLabel : toggle.onLabel,
            offLabel: toggle.offLabel,
            checked : draft[toggle.key] === toggle.on,
            onChange: e => {
              draft[toggle.key] = e.target.checked ? toggle.on : toggle.off
              changed()
            },
          })) : null,
          ...headerActions(item),
          Buttons(),
          more.length ? Button({
            id       : `${ id }-more`,
            className: 'gh-button secondary text icon',
            title    : __('More actions', 'groundhogg'),
            onClick  : e => moreMenu(e.currentTarget, {
              items   : more.map(({
                key,
                text,
              }) => ( {
                key,
                text,
              } )),
              onSelect: key => more.find(action => action.key === key).onSelect(item),
            }),
          }, icons.verticalDots) : null,
          Button({
            id       : `close-${ id }-editor`,
            className: 'gh-button secondary text icon',
            title    : __('Close', 'groundhogg'),
            onClick  : () => maybeDiscard().then(close),
          }, Dashicon('no-alt')),
        ])),
        ...panels({
          item,
          draft,
          changed,
        }),
      ]))

      onRender({
        item,
        draft,
        changed,
      })

      markActiveRow()
      setUrlParam(item.ID)
    }

    /**
     * Load an item, then open it
     *
     * @param itemId number
     * @param reopen bool show it again if it's already open, like when its editor was set up differently
     */
    const open = (itemId, reopen = false) => maybeDiscard().then(async () => {

      if (current && current.id === itemId && !reopen) {
        return
      }

      current = null

      try {
        let item = load(itemId)

        // only stand in for the editor when it has to wait
        if (item instanceof Promise) {
          show(EditorSkeleton())
          item = await item
        }

        replaceRow(item.ID, item.row)
        render(item)
      }
      catch (err) {
        close()
        dialog({
          message: err.message || __('That could not be opened.', 'groundhogg'),
          type   : 'error',
        })
      }
    })

    /**
     * An item was deleted, take away its row and close it if it's open
     *
     * @param itemId number
     */
    const removed = itemId => {

      let row = rows ? rowOf(itemId) : null
      if (row) {
        row.remove()
      }

      if (current && current.id === itemId) {
        current.dirty = false
        close()
      }
    }

    /**
     * The status changed outside the editor, like from a row action that saved it. If the item is open, its buttons
     * show the new status, and it isn't an unsaved change.
     *
     * @param itemId number
     * @param value string
     * @param rowHtml string the item's new row
     */
    const statusChanged = (itemId, value, rowHtml = '') => {

      replaceRow(itemId, rowHtml)
      markActiveRow()

      if (!status || !current || current.id !== itemId) {
        return
      }

      current.draft[status.key] = value
      current.refreshButtons()
    }

    $(() => {

      if (editLinks) {
        $(document).on('click', editLinks, e => {

          // new tab, new window
          if (e.ctrlKey || e.metaKey || e.shiftKey) {
            return
          }

          e.preventDefault()
          open(parseInt(e.currentTarget.dataset.id))
        })
      }

      window.addEventListener('resize', () => {
        if (current) {
          fitBelowHeader()
        }
      })

      // keep unsaved changes from being lost by leaving the page
      window.addEventListener('beforeunload', e => {
        if (current && current.dirty) {
          e.preventDefault()
          e.returnValue = ''
        }
      })

      // reopen the item that was open before a reload
      const editing = urlParam ? parseInt(new URL(window.location.href).searchParams.get(urlParam)) : 0

      if (editing) {
        open(editing)
      }
    })

    return {
      open,
      close,
      removed,
      replaceRow,
      statusChanged,
      current: () => current,
    }
  }

  Groundhogg.SidebarEditor = SidebarEditor
  Groundhogg.SidebarEditor.Panel = Panel
  Groundhogg.SidebarEditor.Field = Field
  Groundhogg.SidebarEditor.CopyField = CopyField
  Groundhogg.SidebarEditor.ServerFields = ServerFields
  Groundhogg.SidebarEditor.serializeFields = serializeFields

} )(jQuery)
