/**
 * Draws the flow editor's canvas from the step store, see flow-store.js
 *
 * The markup matches what the server used to render with Funnel_Step::sortable_item(), which the editor's drag and drop,
 * saving, and logic lines, and step type JS written for it, rely on. Step types that override sortable_item() are
 * drawn from the HTML the server sends for them instead.
 *
 * Elements are made with h( tag, attributes, children ), MakeEl's signature. The editor passes MakeEl.makeEl, the tests
 * pass one that builds HTML strings, see tests/js
 */
( function (root, factory) {

  const FlowCanvas = factory(typeof module === 'object' && module.exports ? require('./flow-store') : root.Groundhogg.FlowStore)

  if (typeof module === 'object' && module.exports) {
    module.exports = FlowCanvas
    return
  }

  root.Groundhogg.FlowCanvas = FlowCanvas

} )(typeof self !== 'undefined' ? self : this, function (FlowStore) {

  const { isBenchmark } = FlowStore

  const escHTML = text => String(text ?? '').
    replace(/&/g, '&amp;').
    replace(/</g, '&lt;').
    replace(/>/g, '&gt;').
    replace(/"/g, '&quot;').
    replace(/'/g, '&#039;')

  /**
   * @param h function( tag, attributes, children ) makes an element
   * @param stepTypes Object type => { name, icon, svg }, Groundhogg.rawStepTypes
   * @param defaultIcon string the icon URL for step types without one
   * @return {{render: (function(Object): Array)}}
   */
  const createCanvas = ({
    h,
    stepTypes = {},
    defaultIcon = '',
  }) => {

    const Dashicon = icon => h('span', { className: `dashicons dashicons-${ icon }` }, '')

    const FlowLine = () => h('div', { className: 'flow-line' }, '')

    /**
     * Draw a store's steps, the children of #step-sortable
     *
     * @param store Object see FlowStore.createStore()
     * @param editing bool whether the flow is being edited, which adds the buttons for adding and changing steps
     * @param reporting bool whether to add the places the reporting page puts each step's stats
     * @param debug bool whether to add each step's ID and position
     * @param previewOf function( step ) what to show instead of the server's while a change is saved: { title,
     *                  errors: [{ code, message }], branches: [{ key, name, classes }] }, each optional, or null
     * @return Array
     */
    const render = ({
      store,
      editing = true,
      reporting = false,
      debug = false,
      previewOf = () => null,
    }) => {

      const canvasOf = step => store.getCanvas(step.ID) ?? {}

      /**
       * The classes on a step's card, with the warnings the preview has instead of the server's
       */
      const classesOf = (step, canvas, preview) => {

        const classes = canvas.classes ?? [
          step.data.step_group,
          step.data.step_type,
          step.data.step_status,
          // steps just added in the editor don't have the server's yet
          'pending',
        ]

        if (!preview?.errors) {
          return classes
        }

        const serverCodes = ( canvas.errors ?? [] ).map(error => error.code)

        return [
          ...classes.filter(name => name !== 'has-errors' && !serverCodes.includes(name)),
          ...( preview.errors.length ? ['has-errors', ...preview.errors.map(error => error.code)] : [] ),
        ]
      }

      /**
       * The branches a logic step has, the server's, or the preview's until the server says
       */
      const branchesOf = step => canvasOf(step).branches ?? previewOf(step)?.branches?.map(({
        key,
        name,
        classes = '',
      }) => ( {
        id: `${ step.ID }-${ key }`,
        name,
        classes,
      } ))

      const AddStepButton = ({
        id,
        tooltip = 'Add action',
        className = 'add-action',
      }) => editing ? h('button', {
        type     : 'button',
        id,
        className: `add-step ${ className }`,
      }, [
        Dashicon('plus-alt2'),
        h('div', { className: 'gh-tooltip top' }, escHTML(tooltip)),
      ]) : null

      const ActionButton = (title, icon, className, kind = 'secondary') => h('button', {
        title,
        type     : 'button',
        className: `gh-button ${ kind } text icon ${ className }`,
      }, [
        Dashicon(icon),
        h('div', { className: 'gh-tooltip top' }, title),
      ])

      const StatWrap = (tooltip, icon, className, alignment) => h('div', { className: `display-flex ${ alignment } full-width` }, [
        h('div', { className: 'stat-wrap' }, [
          h('div', { className: 'gh-tooltip top' }, tooltip),
          Dashicon(icon),
          h('div', { className }, ''),
        ]),
      ])

      /**
       * The step's card, like Funnel_Step::__sortable_item()
       */
      const Card = step => {

        const canvas = canvasOf(step)
        const preview = previewOf(step)
        const {
          step_type,
          step_group,
          step_status,
          step_level,
          step_order,
          step_title,
          branch,
        } = step.data

        const type = stepTypes[step_type] ?? {}
        const name = canvas.name ?? type.name ?? step_type
        const icon = canvas.icon ?? type.icon ?? ''
        const svg = canvas.svg ?? type.svg ?? ''
        const isSvg = icon && icon.endsWith('.svg')

        return h('div', {
          id       : `step-${ step.ID }`,
          dataId   : step.ID,
          dataType : step_type,
          dataGroup: step_group,
          dataLevel: step_level,
          className: ['step', ...classesOf(step, canvas, preview)].join(' '),
          tabindex : 0,
        }, [
          h('input', {
            type : 'hidden',
            name : 'step_ids[]',
            value: step.ID,
          }),
          h('input', {
            type : 'hidden',
            id   : `step_${ step.ID }_branch`,
            name : `steps[${ step.ID }][branch]`,
            value: branch,
          }),
          h('div', { className: 'step-labels display-flex gap-10' }, [
            debug ? h('div', { className: 'step-label' }, `ID: ${ step.ID }`) : null,
            debug ? h('div', { className: 'step-label' }, `Pos: ${ step_order },${ step_level }`) : null,
            canvas.labels || null,
            canvas.notes ? h('span', { className: 'dashicons dashicons-admin-comments' }, [
              h('span', { className: 'gh-tooltip right' }, canvas.notes),
            ]) : null,
            canvas.entry ? Dashicon('migrate') : null,
            canvas.conversion ? Dashicon('flag') : null,
            canvas.locked ? Dashicon('lock') : null,
            canvas.extra_labels || null,
          ]),
          canvas.inside || null,
          editing ? h('div', { className: 'actions has-box-shadow' }, [
            ActionButton('Lock', 'lock', 'lock-step'),
            ActionButton('Unlock', 'unlock', 'unlock-step'),
            ActionButton('Duplicate', 'admin-page', 'duplicate-step'),
            ActionButton('Delete', 'trash', 'delete-step', 'danger'),
          ]) : null,
          h('div', { className: 'hndle' }, [
            isSvg ? h('div', { className: 'hndle-icon' }, svg || '') : h('img', {
              className: 'hndle-icon',
              src      : icon || defaultIcon,
            }),
            h('div', {}, [
              h('span', { className: 'step-title' }, preview?.title ?? canvas.title ?? escHTML(step_title)),
              h('span', { className: 'step-name' }, name),
            ]),
          ]),
          reporting && step_group !== 'logic' ? h('div', { className: 'step-reporting' }, [
            StatWrap('Pending', 'hourglass', 'waiting', 'flex-end'),
            StatWrap('Completed', 'admin-users', 'complete', 'flex-start'),
          ]) : null,
        ])
      }

      /**
       * An action or logic step, like Funnel_Step::sortable_item()
       */
      const StepItem = step => h('div', { className: `sortable-item ${ step.data.step_group }` }, [
        AddStepButton({ id: `before-${ step.ID }` }),
        FlowLine(),
        Card(step),
        FlowLine(),
      ])

      /**
       * A logic step that stops the flow, drawn with a stop line under it
       */
      const StopItem = step => h('div', { className: 'sortable-item action' }, [
        AddStepButton({ id: `before-${ step.ID }` }),
        FlowLine(),
        Card(step),
        canvasOf(step).has_filters ? FlowLine() : h('div', { style: { height: '40px' } }, ''),
        h('div', { className: 'logic-line line-end' }, h('span', { className: 'path-indicator' }, 'Stop')),
      ])

      /**
       * A logic step with a column for each branch, like Branch_Logic::sortable_item()
       * Branches the step no longer has, but that still have steps, are drawn after the others so they can be seen and fixed
       */
      const BranchLogicItem = step => {

        const branches = branchesOf(step) ?? []
        const known = branches.map(branch => branch.id)

        const unused = [...new Set(store.subSteps(step).map(sub => sub.data.branch))].
          filter(branch => !known.includes(branch)).
          map(branch => ( {
            id     : branch,
            name   : 'Unused branch',
            classes: 'unused-branch',
          } ))

        return h('div', {
          className: 'sortable-item logic branch-logic',
          dataType : step.data.step_type,
          dataGroup: step.data.step_group,
        }, [
          AddStepButton({ id: `before-${ step.ID }` }),
          FlowLine(),
          Card(step),
          h('div', { className: 'display-flex align-top step-branches' }, [...branches, ...unused].map(branch => h('div', {
            className: `split-branch ${ branch.classes ?? '' }`,
          }, [
            h('div', { className: 'logic-line line-above' }, h('span', {
              id       : `branch-name-indicator-${ branch.id }`,
              className: 'path-indicator',
            }, escHTML(branch.name))),
            h('div', {
              id        : `branch-${ branch.id }`,
              className : 'step-branch',
              dataBranch: branch.id,
            }, [
              ...Branch(branch.id),
              AddStepButton({
                id     : `in-branch-${ branch.id }`,
                tooltip: `Add step in ${ branch.name }`,
              }),
            ]),
            h('div', { className: 'logic-line line-below' }, ''),
            h('div', { className: 'logic-line line-below-after' }, ''),
          ]))),
        ])
      }

      /**
       * A trigger in an OR group, with the steps in its own branch, like Benchmark::sortable_item()
       */
      const BenchmarkItem = (step, inGroup) => {

        const subs = store.branchSteps(`${ step.ID }`)

        return h('div', {
          className: `sortable-item benchmark ${ parseInt(step.data.can_passthru) ? 'passthru' : '' }`,
          dataType : step.data.step_type,
          dataGroup: step.data.step_group,
        }, [
          Card(step),
          subs.length || inGroup ? h('div', {
            className : 'step-branch',
            dataBranch: `${ step.ID }`,
          }, [
            subs.length ? FlowLine() : null,
            ...Branch(`${ step.ID }`),
            subs.length ? null : FlowLine(),
            AddStepButton({ id: `end-inside-${ step.ID }` }),
          ]) : null,
        ])
      }

      /**
       * Adjacent triggers in a branch form an OR group, any of them starts or continues the flow
       */
      const BenchmarkGroup = group => {

        const first = group[0]
        const last = group[group.length - 1]
        const starting = Boolean(first.is_starting)

        return h('div', { className: `sortable-item benchmarks ${ starting ? 'starting' : '' }` }, [
          starting ? null : AddStepButton({ id: `before-group-${ first.ID }` }),
          starting ? null : FlowLine(),
          h('div', {
            className : 'step-branch benchmarks',
            dataBranch: first.data.branch,
          }, [
            ...group.flatMap((step, i) => [
              i > 0 ? h('span', { className: 'benchmark-or' }, 'OR') : null,
              BenchmarkItem(step, group.length > 1),
            ]),
            AddStepButton({
              id       : `add-to-group-after-${ last.ID }`,
              tooltip  : 'Add trigger',
              className: 'add-benchmark',
            }),
          ]),
        ])
      }

      const Item = step => {
        switch (canvasOf(step).layout ?? ( branchesOf(step) ? 'branches' : 'default' )) {
          case 'html':
            return canvasOf(step).html
          case 'stop':
            return StopItem(step)
          case 'branches':
            return BranchLogicItem(step)
          default:
            return StepItem(step)
        }
      }

      /**
       * The steps in a branch
       *
       * @param branch string
       * @return Array
       */
      const Branch = branch => {

        const steps = store.branchSteps(branch)
        const items = []

        const isGroupable = step => isBenchmark(step) && canvasOf(step).layout !== 'html'

        for (let i = 0; i < steps.length; i++) {

          if (!isGroupable(steps[i])) {
            items.push(Item(steps[i]))
            continue
          }

          const group = [steps[i]]

          while (i + 1 < steps.length && isGroupable(steps[i + 1])) {
            group.push(steps[++i])
          }

          items.push(BenchmarkGroup(group))
        }

        return items
      }

      return Branch(FlowStore.MAIN)
    }

    return {
      render,
    }
  }

  return {
    createCanvas,
    escHTML,
  }
})
