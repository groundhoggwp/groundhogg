import TimerIcon from "@material-ui/icons/Timer";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";

const STEP_TYPE = "delay_timer";

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Delay Timer",

  icon: <TimerIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);
