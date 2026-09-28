/**
 * The flow editor's step store: the steps of the flow being edited, and what the server says about drawing them.
 * The canvas is drawn from it, see flow-canvas.js
 *
 * No DOM access here, so the tests can load it in Node, see tests/js
 */
( function (root, factory) {

  const FlowStore = factory()

  if (typeof module === 'object' && module.exports) {
    module.exports = FlowStore
    return
  }

  root.Groundhogg = root.Groundhogg || {}
  root.Groundhogg.FlowStore = FlowStore

} )(typeof self !== 'undefined' ? self : this, function () {

  const MAIN = 'main'

  /**
   * The ID of the step that owns a branch, "123" for a benchmark's branch or "123-yes" for a logic step's
   * Branch keys can contain dashes, so the owner is always before the first one
   *
   * @param branch string
   * @return number|string 0 for the main branch, a string for steps that aren't saved yet
   */
  const branchOwnerId = branch => {

    if (branch === MAIN) {
      return 0
    }

    const owner = String(branch).split('-')[0]

    return isTempId(owner) ? owner : parseInt(owner) || 0
  }

  /**
   * Steps added in the editor get a temporary ID until the server says what their real one is.
   * They can't have dashes, which separate a branch's owner from its key.
   */
  const isTempId = id => /^tmp_[a-z0-9]+$/.test(String(id))

  let tempIds = 0

  const newTempId = () => `tmp_${ Date.now().toString(36) }${ ( tempIds++ ).toString(36) }`

  const isBenchmark = step => step.data.step_group === 'benchmark'

  /**
   * Sort by step_order, the same as the server
   */
  const byOrder = (a, b) => ( parseInt(a.data.step_order) || 0 ) - ( parseInt(b.data.step_order) || 0 ) || String(a.ID).localeCompare(String(b.ID), undefined, { numeric: true })

  /**
   * The steps in a branch, in order
   *
   * @param steps Object[]
   * @param branch string
   * @return Object[]
   */
  const branchSteps = (steps, branch) => steps.filter(step => step.data.branch === branch).sort(byOrder)

  /**
   * The steps directly in a step's branches, in order, like Step::get_sub_steps()
   *
   * @param steps Object[]
   * @param step Object
   * @return Object[]
   */
  const subSteps = (steps, step) => steps.filter(sub => isBenchmark(step)
    ? sub.data.branch === `${ step.ID }`
    : sub.data.branch.startsWith(`${ step.ID }-`)).sort(byOrder)

  /**
   * The order, level, branch path, and ancestors of each step, derived from the branches the same way as
   * Funnel::set_step_levels(), which stays the source of truth
   *
   * @param steps Object[] the steps of the flow, with data.branch, data.step_group, and data.step_order
   * @param isBranchLogic function whether a step is a branching logic step
   * @return {Object} step ID => { step_order, step_level, branch_path, ancestors }
   */
  const stepLevels = (steps, isBranchLogic) => {

    const byId = Object.fromEntries(steps.map(step => [step.ID, step]))
    const levels = {}
    let order = 0

    const parentOf = step => byId[branchOwnerId(step.data.branch)] ?? null

    // like Step::update_branch_path_in_db()
    const branchPath = step => {
      const branches = [step.data.branch]
      const ancestors = []
      let parent = parentOf(step)

      while (parent) {
        branches.push(parent.data.branch)
        ancestors.push(parent.ID)
        parent = parentOf(parent)
      }

      return {
        branch_path: branches.join('|'),
        ancestors  : ancestors.join('|'),
      }
    }

    const walk = (branch, level) => {

      let prev = null
      let maxDepth = level

      for (const step of branchSteps(steps, branch)) {

        levels[step.ID] = branchPath(step)

        if (isBenchmark(step)) {
          levels[step.ID].step_level = level
          levels[step.ID].step_order = ++order
          maxDepth = Math.max(maxDepth, walk(`${ step.ID }`, level + 1))
          prev = step
          continue
        }

        if (prev && isBenchmark(prev)) {
          level = maxDepth
        }

        levels[step.ID].step_level = level
        levels[step.ID].step_order = ++order

        level++

        if (isBranchLogic(step)) {
          const branches = [...new Set(subSteps(steps, step).map(sub => sub.data.branch))]
          maxDepth = level
          for (const sub of branches) {
            maxDepth = Math.max(maxDepth, walk(sub, level))
          }
          level = maxDepth
        }

        prev = step
      }

      return Math.max(level, maxDepth)
    }

    walk(MAIN, 1)

    return levels
  }

  /**
   * An edit-flow position for the start or end of a branch
   *
   * @param branch string
   * @param position string 'start' or 'end'
   * @return Object
   */
  const branchPosition = (branch, position = 'end') => {

    if (branch === MAIN) {
      return {
        branch: MAIN,
        position,
      }
    }

    const owner = branchOwnerId(branch)
    const key = String(branch).slice(String(owner).length + 1)

    return {
      branch_of: owner,
      // a benchmark's branch is the steps that only run after it
      branch   : key || 'then',
      position,
    }
  }

  /**
   * The branch string an edit-flow position's branch_of and branch refer to
   */
  const positionBranch = at => {

    if (at.branch_of === undefined || at.branch_of === null) {
      return MAIN
    }

    return at.branch === 'then' ? `${ at.branch_of }` : `${ at.branch_of }-${ at.branch }`
  }

  /**
   * Replace temporary IDs with real ones anywhere in a value, like operations and undo history.
   * Branch strings of temporary steps, like "tmp_1-yes", change too.
   *
   * @param value any
   * @param ids Object temporary ID => real ID
   * @return any
   */
  const rewriteIds = (value, ids) => {

    if (typeof value === 'string') {

      if (ids.hasOwnProperty(value)) {
        return ids[value]
      }

      const owner = value.split('-')[0]

      if (owner !== value && ids.hasOwnProperty(owner)) {
        return `${ ids[owner] }${ value.slice(owner.length) }`
      }

      return value
    }

    if (Array.isArray(value)) {
      return value.map(item => rewriteIds(item, ids))
    }

    if (value && typeof value === 'object') {
      return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, rewriteIds(item, ids)]))
    }

    return value
  }

  const clone = value => JSON.parse(JSON.stringify(value))

  const FLAGS = ['is_entry', 'is_conversion', 'can_passthru']

  /**
   * How a step's settings, title, and benchmark flags changed, as the updates to go from one to the other and back
   *
   * @param old Object the step before
   * @param step Object the step after
   * @return {{undo: Object, redo: Object}|null} null if nothing changed
   */
  const stepChanges = (old, step) => {

    const was = {
      op  : 'update',
      step: step.ID,
    }

    const now = {
      op  : 'update',
      step: step.ID,
    }

    const keys = [...new Set([...Object.keys(old.meta ?? {}), ...Object.keys(step.meta ?? {})])]

    keys.forEach(key => {

      const before = old.meta?.[key] ?? null
      const after = step.meta?.[key] ?? null

      if (JSON.stringify(before) === JSON.stringify(after)) {
        return
      }

      was.meta = {
        ...was.meta,
        [key]: before,
      }

      now.meta = {
        ...now.meta,
        [key]: after,
      }
    })

    if (old.data.step_title !== step.data.step_title) {
      was.title = old.data.step_title
      now.title = step.data.step_title
    }

    FLAGS.forEach(flag => {

      const before = Boolean(parseInt(old.data[flag] ?? 0))
      const after = Boolean(parseInt(step.data[flag] ?? 0))

      if (before !== after) {
        was.flags = {
          ...was.flags,
          [flag]: before,
        }
        now.flags = {
          ...now.flags,
          [flag]: after,
        }
      }
    })

    if (!was.meta && was.title === undefined && !was.flags) {
      return null
    }

    return {
      undo: was,
      redo: now,
    }
  }

  /**
   * A step's settings from its settings panel's fields, named like steps[ID][setting] or steps[ID][setting][key][],
   * in the shape PHP would get them in $_POST['steps'][ID]
   *
   * @param fields {name, value}[] like jQuery's serializeArray()
   * @param id the step
   * @return Object
   */
  const formToSettings = (fields, id) => {

    const prefix = `steps[${ id }]`
    const settings = {}

    fields.forEach(({
      name,
      value,
    }) => {

      if (!name.startsWith(`${ prefix }[`)) {
        return
      }

      const keys = name.slice(prefix.length).match(/\[[^\]]*\]/g).map(key => key.slice(1, -1))

      let target = settings

      keys.forEach((key, i) => {

        const last = i === keys.length - 1

        // steps[ID][tags][] appends
        if (key === '') {
          key = Object.keys(target).length
        }

        if (last) {
          target[key] = value
          return
        }

        if (typeof target[key] !== 'object' || target[key] === null) {
          target[key] = {}
        }

        target = target[key]
      })
    })

    // objects keyed 0, 1, 2 were lists
    const lists = value => {

      if (!value || typeof value !== 'object') {
        return value
      }

      const keys = Object.keys(value)
      const entries = keys.map(key => [key, lists(value[key])])

      if (keys.length && keys.every((key, i) => key === String(i))) {
        return entries.map(([, item]) => item)
      }

      return Object.fromEntries(entries)
    }

    return lists(settings)
  }

  /**
   * A store for one flow
   *
   * Edits are applied here first, so the canvas redraws right away, as groundhogg/edit-flow operations that are then
   * sent to the server. Applying an operation returns the operations that reverse it, for undo.
   *
   * @param steps Object[] the steps, as Funnel::get_steps() in editing mode sends them
   * @param canvas Object step ID => what the server says about drawing it, see Funnel_Step::get_canvas_data()
   * @param stepTypes Object type => { name, group }, for steps added here
   * @param defaults function( type ) the settings steps added here start with
   */
  const createStore = ({
    steps = [],
    canvas = {},
    stepTypes = {},
    defaults = () => ( {} ),
  } = {}) => ( {

    steps,
    canvas,
    stepTypes,
    defaults,

    // deleted steps, so they can be restored, step ID => step
    trash: {},

    /**
     * Replace the steps with the server's, after a save
     */
    load ({
      steps = [],
      canvas = {},
    }) {
      this.steps = steps
      this.canvas = canvas
    },

    getStep (id) {
      return this.steps.find(step => step.ID == id)
    },

    getCanvas (id) {
      return this.canvas?.[id] ?? null
    },

    branchSteps (branch) {
      return branchSteps(this.steps, branch)
    },

    subSteps (step) {
      return subSteps(this.steps, step)
    },

    isBranchLogic (step) {
      return Boolean(this.getCanvas(step.ID)?.branch_logic)
    },

    stepLevels () {
      return stepLevels(this.steps, step => this.isBranchLogic(step))
    },

    /**
     * A step and every step in its branches, parents first
     *
     * @param id
     * @return Array
     */
    descendantIds (id) {
      const step = this.getStep(id)
      return step ? [step.ID, ...this.subSteps(step).flatMap(sub => this.descendantIds(sub.ID))] : []
    },

    /**
     * Where a step is, as an edit-flow position, so it can be put back there
     *
     * @param id
     * @return Object
     */
    positionOf (id) {

      const step = this.getStep(id)
      const siblings = this.branchSteps(step.data.branch)
      const index = siblings.findIndex(sibling => sibling.ID == id)

      if (index > 0) {
        return { after: siblings[index - 1].ID }
      }

      return branchPosition(step.data.branch, 'start')
    },

    /**
     * The branch and index in it that an edit-flow position refers to, like Flow_Operations::resolve_position()
     *
     * @param at Object
     * @param moving the step being moved, which isn't counted
     * @return {{branch: string, index: number}}
     */
    resolvePosition (at = {}, moving = null) {

      const siblingsOf = branch => this.branchSteps(branch).filter(step => step.ID != moving)

      const ref = at.after ?? at.before

      if (ref !== undefined && ref !== null) {

        const step = this.getStep(ref)

        if (!step) {
          throw new Error(`Step ${ ref } isn't in the flow.`)
        }

        const siblings = siblingsOf(step.data.branch)

        return {
          branch: step.data.branch,
          index : siblings.findIndex(sibling => sibling.ID == ref) + ( at.after !== undefined ? 1 : 0 ),
        }
      }

      const branch = positionBranch(at)

      return {
        branch,
        index: at.position === 'start' ? 0 : siblingsOf(branch).length,
      }
    },

    /**
     * Put steps in a branch at an index, in order
     */
    place (steps, branch, index) {

      const siblings = this.branchSteps(branch).filter(sibling => !steps.includes(sibling))

      siblings.splice(index, 0, ...steps)

      steps.forEach(step => step.data.branch = branch)

      // only the order within the branch matters until relayout()
      siblings.forEach((sibling, i) => sibling.data.step_order = i + 1)
    },

    /**
     * Derive the order and levels after a change, the server derives them the same way
     */
    relayout () {

      const levels = this.stepLevels()

      this.steps.forEach(step => {

        Object.assign(step.data, levels[step.ID] ?? {})

        // like Step::is_starting()
        step.is_starting = isBenchmark(step) && step.data.branch === MAIN && ( step.data.step_level == 1 || step.data.step_order == 1 )
      })
    },

    /**
     * Apply an operation, in groundhogg/edit-flow's format
     *
     * @param operation Object
     * @return Object[] the operations that reverse it
     */
    apply (operation) {

      const inverse = this[`apply_${ operation.op }`]?.(operation)

      if (!inverse) {
        throw new Error(`Can't apply "${ operation.op }".`)
      }

      this.relayout()

      return inverse
    },

    apply_add ({
      at,
      steps: nodes,
    }) {

      const {
        branch,
        index,
      } = this.resolvePosition(at)

      const added = nodes.map(({
        type,
        id,
      }) => ( {
        ID         : id,
        data       : {
          step_type    : type,
          step_group   : this.stepTypes[type]?.group ?? 'action',
          step_title   : this.stepTypes[type]?.name ?? type,
          step_status  : 'inactive',
          branch,
          step_order   : 0,
          step_level   : 0,
          is_locked    : 0,
          is_entry     : 0,
          is_conversion: 0,
          can_passthru : 0,
        },
        meta       : clone(this.defaults(type) ?? {}),
        is_starting: false,
        is_entry   : false,
      } ))

      this.steps = [...this.steps, ...added]
      this.place(added, branch, index)

      return [...added].reverse().map(step => ( {
        op  : 'delete',
        step: step.ID,
      } ))
    },

    apply_move ({
      step: id,
      at,
    }) {

      const step = this.getStep(id)

      if (!step) {
        throw new Error(`Step ${ id } isn't in the flow.`)
      }

      const from = this.positionOf(id)

      const {
        branch,
        index,
      } = this.resolvePosition(at, step.ID)

      // like Flow_Operations::is_inside()
      for (let owner = branchOwnerId(branch); owner; owner = branchOwnerId(this.getStep(owner)?.data.branch ?? MAIN)) {
        if (owner == step.ID) {
          throw new Error('A step can\'t be moved into its own branches.')
        }
      }

      this.place([step], branch, index)

      return [
        {
          op  : 'move',
          step: step.ID,
          at  : from,
        },
      ]
    },

    apply_delete ({ step: id }) {

      const ids = this.descendantIds(id)

      if (!ids.length) {
        throw new Error(`Step ${ id } isn't in the flow.`)
      }

      const at = this.positionOf(id)

      ids.forEach(stepId => this.trash[stepId] = this.getStep(stepId))

      this.steps = this.steps.filter(step => !ids.includes(step.ID))

      return [
        {
          op   : 'restore',
          step : ids[0],
          at,
          steps: ids.slice(1),
        },
      ]
    },

    /**
     * A copy of a step, right away, the server's copies of the steps in its branches arrive with its reply
     */
    apply_duplicate ({
      step: id,
      at,
      id: copyId,
      type,
    }) {

      const step = this.getStep(id)

      // copying a step from another flow, what it is shows until the server's copy arrives
      if (!step) {

        if (!type || !at) {
          throw new Error(`Step ${ id } isn't in the flow.`)
        }

        return this.apply_add({
          at,
          steps: [
            {
              type,
              id: copyId,
            },
          ],
        })
      }

      const copy = {
        ...clone(step),
        ID         : copyId,
        is_starting: false,
      }

      copy.data.step_status = 'inactive'

      const {
        branch,
        index,
      } = this.resolvePosition(at ?? { after: step.ID })

      this.steps = [...this.steps, copy]
      this.place([copy], branch, index)

      return [
        {
          op  : 'delete',
          step: copyId,
        },
      ]
    },

    apply_lock ({ step: id }) {
      return this.setLocked(id, true)
    },

    apply_unlock ({ step: id }) {
      return this.setLocked(id, false)
    },

    /**
     * Lock or unlock a step, with its card's lock showing right away
     */
    setLocked (id, locked) {

      const step = this.getStep(id)

      if (!step) {
        throw new Error(`Step ${ id } isn't in the flow.`)
      }

      const was = Boolean(parseInt(step.data.is_locked))

      step.data.is_locked = locked ? 1 : 0

      const canvas = this.getCanvas(step.ID)

      if (canvas) {
        this.canvas = {
          ...this.canvas,
          [step.ID]: {
            ...canvas,
            locked,
            classes: [...( canvas.classes ?? [] ).filter(name => name !== 'locked'), ...( locked ? ['locked'] : [] )],
          },
        }
      }

      return [
        {
          op  : was ? 'lock' : 'unlock',
          step: step.ID,
        },
      ]
    },

    apply_restore ({
      step: id,
      at,
      steps: ids = [],
    }) {

      // applied again when the server's steps are loaded while it's waiting to be sent, so the deleted steps are kept
      // in the trash, and ones that are back already aren't added twice
      const bring = stepId => this.getStep(stepId) ?? ( this.trash[stepId] ? clone(this.trash[stepId]) : null )

      const root = bring(id)

      if (!root) {
        throw new Error(`Step ${ id } wasn't deleted here.`)
      }

      const rest = ids.map(bring).filter(Boolean)

      const {
        branch,
        index,
      } = this.resolvePosition(at, root.ID)

      this.steps = [...this.steps.filter(step => ![root, ...rest].some(back => back.ID == step.ID)), root, ...rest]

      this.place([root], branch, index)

      return [
        {
          op  : 'delete',
          step: root.ID,
        },
      ]
    },

    apply_update ({
      step: id,
      meta,
      title,
      flags,
    }) {

      const step = this.getStep(id)

      if (!step) {
        throw new Error(`Step ${ id } isn't in the flow.`)
      }

      const inverse = {
        op  : 'update',
        step: step.ID,
      }

      if (meta) {

        inverse.meta = {}

        step.meta = { ...step.meta }

        Object.entries(meta).forEach(([key, value]) => {

          inverse.meta[key] = step.meta.hasOwnProperty(key) ? clone(step.meta[key]) : null

          if (value === null) {
            delete step.meta[key]
            return
          }

          step.meta[key] = clone(value)
        })
      }

      if (title !== undefined) {
        inverse.title = step.data.step_title
        step.data.step_title = title
      }

      if (flags) {
        inverse.flags = {}
        Object.entries(flags).forEach(([flag, value]) => {
          inverse.flags[flag] = Boolean(parseInt(step.data[flag] ?? 0))
          step.data[flag] = value ? 1 : 0
        })
      }

      return [inverse]
    },

    /**
     * Replace temporary IDs with the real ones the server gave the steps
     *
     * @param ids Object temporary ID => real ID
     */
    rewriteIds (ids) {

      if (!Object.keys(ids).length) {
        return
      }

      this.steps = rewriteIds(this.steps, ids)
      this.trash = Object.fromEntries(Object.entries(rewriteIds(this.trash, ids)).map(([id, step]) => [ids[id] ?? id, step]))
    },

    /**
     * What changed between two sets of steps, as the operations to go from one to the other and back.
     * For saves the server made from posted settings, so they can be undone like the operations applied here.
     *
     * @param before Object[] the steps before
     * @return {{undo: Object[], redo: Object[]}}
     */
    changesSince (before) {

      const undo = []
      const redo = []
      const had = Object.fromEntries(before.map(step => [step.ID, step]))

      this.steps.forEach(step => {

        const old = had[step.ID]

        // added, like a duplicate, only the top step, the rest go with it
        if (!old) {

          if (had[branchOwnerId(step.data.branch)] || step.data.branch === MAIN) {
            const ids = this.descendantIds(step.ID)
            undo.unshift({
              op  : 'delete',
              step: step.ID,
            })
            redo.push({
              op   : 'restore',
              step : step.ID,
              at   : this.positionOf(step.ID),
              steps: ids.slice(1),
            })
          }

          return
        }

        const changes = stepChanges(old, step)

        if (changes) {
          undo.push(changes.undo)
          redo.push(changes.redo)
        }
      })

      return {
        undo,
        redo,
      }
    },

    /**
     * Where the derived order and levels differ from the server's, for catching drift between this and
     * Funnel::set_step_levels() while developing
     *
     * @return Object[] { ID, key, server, derived }
     */
    levelDifferences () {

      const levels = this.stepLevels()
      const differences = []

      this.steps.forEach(step => {
        Object.entries(levels[step.ID] ?? {}).forEach(([key, derived]) => {
          if (String(step.data[key] ?? '') !== String(derived)) {
            differences.push({
              ID    : step.ID,
              key,
              server: step.data[key],
              derived,
            })
          }
        })
      })

      return differences
    },
  } )

  /**
   * Sends operations to the server one request at a time, in the order they were made. Operations made while a
   * request is out go together in the next one. Other saves can wait their turn with exclusive().
   *
   * @param send function( operations ) => Promise of the server's reply, { success, data }. Rejects if the
   *                    request didn't get through, and it's retried.
   * @param onSaved function( data, operations ) after the server applied them
   * @param onRefused function( data, operations ) after the server refused them, none were applied
   * @param onRetry function( error, attempt ) before retrying a request that didn't get through
   * @param retryDelay function( attempt ) ms to wait before a retry
   */
  const createQueue = ({
    send,
    onSaved = () => {},
    onRefused = () => {},
    onRetry = () => {},
    retryDelay = attempt => Math.min(30000, 1000 * 2 ** attempt),
  }) => ( {

    // operations waiting to be sent
    outbox: [],

    // operations sent and waiting for a reply
    sending: [],

    tail: Promise.resolve(),

    /**
     * Whether anything hasn't been saved yet
     */
    isBusy () {
      return this.outbox.length > 0 || this.sending.length > 0
    },

    /**
     * Queue operations to send
     *
     * @param operations Object[]
     * @return Promise resolves once they're saved or refused
     */
    push (operations) {
      this.outbox.push(...operations)
      return this.flush()
    },

    /**
     * Send what's in the outbox, after anything sent before
     */
    flush () {
      return this.then(() => this.sendOutbox())
    },

    /**
     * Run something that saves another way, like the flow editor's settings, once what's queued before it is saved
     *
     * @param job function returning a promise
     * @return Promise of the job's result
     */
    exclusive (job) {
      return this.then(() => this.sendOutbox()).then(() => job())
    },

    then (job) {
      const result = this.tail.then(job)
      // keep going after a failed job
      this.tail = result.catch(() => {})
      return result
    },

    async sendOutbox () {

      if (!this.outbox.length) {
        return
      }

      this.sending = this.outbox.splice(0)

      let attempt = 0

      while (true) {

        let reply

        try {
          reply = await send(this.sending)
        }
        catch (error) {
          onRetry(error, attempt)
          await new Promise(resolve => setTimeout(resolve, retryDelay(attempt)))
          attempt++
          continue
        }

        const operations = this.sending
        this.sending = []

        if (reply.success) {
          // later operations can refer to the steps these added
          this.rewriteIds(reply.data.ids ?? {})
          onSaved(reply.data, operations)
        }
        else {
          onRefused(reply.data ?? {}, operations)
        }

        return reply
      }
    },

    rewriteIds (ids) {
      if (Object.keys(ids).length) {
        this.outbox = rewriteIds(this.outbox, ids)
      }
    },
  } )

  /**
   * Once steps added in the editor are saved, redoing the add brings back the same steps, which undoing it deleted,
   * instead of adding new ones
   *
   * @param operation Object
   * @return Object[]
   */
  const addedToRestored = operation => {

    if (operation.op !== 'add' || operation.steps.some(node => isTempId(node.id))) {
      return [operation]
    }

    return operation.steps.map((node, i) => ( {
      op   : 'restore',
      step : node.id,
      at   : i === 0 ? operation.at : { after: operation.steps[i - 1].id },
      steps: [],
    } ))
  }

  /**
   * Undo and redo, each entry is the operations that make a change and the ones that reverse it
   *
   * @param size number how many entries to keep
   */
  const createHistory = ({ size = 50 } = {}) => ( {

    entries: [],

    // how many entries are applied, the next undo is entries[ pointer - 1 ]
    pointer: 0,

    record ({
      undo,
      redo,
    }) {

      if (!undo.length && !redo.length) {
        return
      }

      // a new change replaces anything that was undone
      this.entries = this.entries.slice(0, this.pointer)
      this.entries.push({
        undo,
        redo,
      })

      if (this.entries.length > size) {
        this.entries.shift()
      }

      this.pointer = this.entries.length
    },

    canUndo () {
      return this.pointer > 0
    },

    canRedo () {
      return this.pointer < this.entries.length
    },

    /**
     * @return Object[] the operations to apply to undo, or none
     */
    undo () {
      return this.canUndo() ? clone(this.entries[--this.pointer].undo) : []
    },

    /**
     * @return Object[] the operations to apply to redo, or none
     */
    redo () {
      return this.canRedo() ? clone(this.entries[this.pointer++].redo) : []
    },

    rewriteIds (ids) {

      if (!Object.keys(ids).length) {
        return
      }

      this.entries = rewriteIds(this.entries, ids).map(entry => ( {
        ...entry,
        redo: entry.redo.flatMap(addedToRestored),
      } ))
    },

    clear () {
      this.entries = []
      this.pointer = 0
    },
  } )

  return {
    MAIN,
    branchOwnerId,
    isTempId,
    newTempId,
    isBenchmark,
    byOrder,
    branchSteps,
    subSteps,
    stepLevels,
    branchPosition,
    positionBranch,
    rewriteIds,
    stepChanges,
    formToSettings,
    createStore,
    createQueue,
    createHistory,
  }
})
