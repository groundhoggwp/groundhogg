import EmailIcon from "@material-ui/icons/Email";
import { registerStepType } from "data/step-type-registry";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";

const STEP_TYPE = "admin_notification";

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Admin Notification",

  icon: <EmailIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },

  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);
