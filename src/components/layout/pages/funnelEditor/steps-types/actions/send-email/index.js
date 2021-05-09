import Mail from "components/svg/Mail/";

import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";

const STEP_TYPE = "send_email";

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Send Email",

  icon: <Mail />,

  read: ({ data, meta, stats }) => {
    return <>Email is sent</>;
  },

  edit: ({ data, meta, stats }) => {
  },
};

registerStepType(STEP_TYPE, stepAtts);
