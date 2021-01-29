import { dispatch, registerStore, select } from '@wordpress/data'

export const REPORTS_REGISTRY_STORE_NAME = 'gh/reportsRegistry'

const DEFAULT_STATE = {
  types: {}
}

const actions = {
  addPanel (panel, atts) {
    return {
      type: 'ADD_PANEL',
      panel,
      atts
    }
  },
  addChart (chart, atts) {
    return {
      type: 'ADD_CHART',
      chart,
      atts
    }
  }
}

registerStore(REPORTS_REGISTRY_STORE_NAME, {
  reducer (state = DEFAULT_STATE, { type, panel, chart, atts }) {
    switch (type) {
      case 'ADD_PANEL':
        return {
          ...state,
          panels: {
            ...state.panels,
            [panel]: {
              id: panel,
              ...atts
            }
          }
        }
      case 'ADD_CHART':
        return {
          ...state,
          charts: {
            ...state.charts,
            [chart]: atts
          }
        }

    }

    return state
  },

  actions,

  selectors: {
    getPanels (state) {
      return Object.values(state.panels)
    },
    getPanel (state, panel) {
      return state.panels[panel]
    },
    getChart (state, chart) {
      return state.charts[chart]
    }
  }
})

/**
 * Register a reports panel
 *
 * @param panel
 * @param atts
 */
export function registerReportsPanel (panel, atts) {
  dispatch(REPORTS_REGISTRY_STORE_NAME).addPanel(panel, atts)
}

/**
 * Register a reports panel
 *
 * @param chart
 * @param atts
 */
export function registerChartType (chart, atts) {
  dispatch(REPORTS_REGISTRY_STORE_NAME).addChart(chart, atts)
}

/**
 * Get the panel
 *
 * @param panel
 * @returns {*|string}
 */
export function getReportPanel (panel) {
  return select(REPORTS_REGISTRY_STORE_NAME).getPanel(panel)
}

/**
 * Get the panel
 *
 * @returns {*|string}
 */
export function getReportPanels () {
  return select(REPORTS_REGISTRY_STORE_NAME).getPanels()
}

/**
 * Get the chart type
 *
 * @param type
 * @returns {*|string}
 */
export function getChartType (type) {
  return select(REPORTS_REGISTRY_STORE_NAME).getChart(type)
}