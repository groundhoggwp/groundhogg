import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";

const STEP_TYPE = "remove_tag";

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Remove Tag",

  icon: <LocalOfferIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);
