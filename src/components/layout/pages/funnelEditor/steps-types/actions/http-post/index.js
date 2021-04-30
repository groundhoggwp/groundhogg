import HttpIcon from "@material-ui/icons/Http";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";

const STEP_TYPE = "http_post";

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Http Post",

  icon: <HttpIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);
