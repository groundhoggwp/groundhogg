import NewUser from "components/svg/NewUser/";

import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";

const STEP_TYPE = "create_user";

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Create User",

  icon: <NewUser />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);