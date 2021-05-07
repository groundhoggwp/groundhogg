import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { BENCHMARK } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { BENCHMARK_TYPE_DEFAULTS } from "components/layout/pages/funnels/editor/steps-types/constants";

const STEP_TYPE = "field_changed";

const stepAtts = {
  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: "Field Changed",

  icon: <LocalOfferIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);
