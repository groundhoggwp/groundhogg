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
   * @return number 0 for the main branch
   */
  const branchOwnerId = branch => branch === MAIN ? 0 : parseInt(String(branch).split('-')[0]) || 0

  const isBenchmark = step => step.data.step_group === 'benchmark'

  /**
   * Sort by step_order, the same as the server
   */
  const byOrder = (a, b) => ( parseInt(a.data.step_order) || 0 ) - ( parseInt(b.data.step_order) || 0 ) || a.ID - b.ID

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
   * A store for one flow
   *
   * @param steps Object[] the steps, as Funnel::get_steps() in editing mode sends them
   * @param canvas Object step ID => what the server says about drawing it, see Funnel_Step::get_canvas_data()
   */
  const createStore = ({
    steps = [],
    canvas = {},
  } = {}) => ( {

    steps,
    canvas,

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

  return {
    MAIN,
    branchOwnerId,
    isBenchmark,
    byOrder,
    branchSteps,
    subSteps,
    stepLevels,
    createStore,
  }
})
