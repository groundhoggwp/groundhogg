import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { BENCHMARK } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { BENCHMARK_TYPE_DEFAULTS } from "components/layout/pages/funnels/editor/steps-types/constants";

const STEP_TYPE = "page_visited";

const stepAtts = {
  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: "page_visited",

  icon: <LocalOfferIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);
