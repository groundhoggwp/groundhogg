import { dispatch, registerStore, select } from "@wordpress/data";

export const STEP_TYPES_STORE_NAME = "gh/stepTypes";

const DEFAULT_STATE = {
  types: {},
};

const actions = {
  addType(stepType, atts) {
    return {
      type: "ADD_TYPE",
      stepType,
      atts,
    };
  },
};

registerStore(STEP_TYPES_STORE_NAME, {
  reducer(state = DEFAULT_STATE, { type, stepType, atts }) {
    switch (type) {
      case "ADD_TYPE":
        return {
          ...state,
          types: {
            ...state.types,
            [stepType]: atts,
          },
        };
    }

    return state;
  },

  actions,

  selectors: {
    getType(state, type) {
      return state.types[type];
    },

    getGroup(state, group) {
      return Object.values(state.types).filter((type) => type.group === group);
    },
  },
});

/**
 * Register a step type
 *
 * @param type
 * @param atts
 */
export function registerStepType(type, atts) {
  atts.type = type;
  dispatch(STEP_TYPES_STORE_NAME).addType(type, atts);
}

/**
 *
 * @param type
 * @returns {{StepFlow: (boolean|(function({data: *, meta: *, read: *}): *)|STEP_DEFAULTS.flow|(function({data: *, meta: *, icon: *, read: *}): *)|BENCHMARK_TYPE_DEFAULTS.flow|(function({data: *, meta: *, read: *}): *)|*), StepRead: *, StepEdit: *}}
 */
export function useStepType(type) {
  let StepType = getStepType(type);

  return {
    StepEdit: StepType.edit,
    StepRead: StepType.read,
    StepFlow: StepType.flow,
    StepIcon: StepType.icon,
  };
}

/**
 * Get the step type
 *
 * @param type
 * @returns {*|string}
 */
export function getStepType(type) {
  let StepType = select(STEP_TYPES_STORE_NAME).getType(type);

  if (!StepType) {
    StepType = select(STEP_TYPES_STORE_NAME).getType("error");
  }

  return StepType;
}

/**
 *
 * @param group
 * @returns {*}
 */
export function getStepGroup(group) {
  return select(STEP_TYPES_STORE_NAME).getGroup(group);
}

/**
 *
 * @param group
 * @returns {*}
 */
export function getAllSteps(group) {
  return select(STEP_TYPES_STORE_NAME).getGroup(group);
}
