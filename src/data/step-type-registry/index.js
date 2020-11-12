import { dispatch, registerStore, select } from '@wordpress/data'

export const STEP_TYPES_STORE_NAME = 'gh/stepTypes'

const DEFAULT_STATE = {
  types: {}
}

const actions = {
  addType (stepType, atts) {
    return {
      type: 'ADD_TYPE',
      stepType,
      atts
    }
  }
}

registerStore(STEP_TYPES_STORE_NAME, {
  reducer (state = DEFAULT_STATE, { type, stepType, atts }) {
    switch (type) {
      case 'ADD_TYPE':
        return {
          ...state,
          types: {
            ...state.types,
            [stepType]: atts
          }
        }
    }

    return state
  },

  actions,

  selectors: {
    getType (state, type) {
      return state.types[type];
    },

    getGroup (state, group) {
      return Object.values( state.types ).filter( type => type.group === group );
    }
  }
});

/**
 * Register a step type
 *
 * @param type
 * @param atts
 */
export function registerStepType ( type, atts ) {
  atts.type = type;
  dispatch( STEP_TYPES_STORE_NAME ).addType( type, atts )
}

/**
 * Get the step type
 *
 * @param type
 * @returns {*|string}
 */
export function getStepType ( type ) {
  return select(STEP_TYPES_STORE_NAME).getType(type)
}