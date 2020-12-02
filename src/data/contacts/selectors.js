/**
 * Internal dependencies
 */

/**
 *
 * @param {Object} state - Reducer state
 * @returns {*}
 */
export const getContactFiles = (state) => {
  return state.files
}

/**
 * Get totalFiles from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getTotalFiles = (state) => {
  return state.totalFiles

}

