import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { BENCHMARK } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { BENCHMARK_TYPE_DEFAULTS } from "components/layout/pages/funnels/editor/steps-types/constants";
import SettingsRow from "components/layout/pages/funnelEditor/components/SettingsRow";
import FormControl from "@material-ui/core/FormControl";
import InputLabel from "@material-ui/core/InputLabel";
import Select from "@material-ui/core/Select";
import MenuItem from "@material-ui/core/MenuItem";
import TagPicker from "../../../../../../core-ui/tag-picker";

const STEP_TYPE = "webhook_listener";

const stepAtts = {
  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: "Webhook",

  icon: <LocalOfferIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return <></>;
  },
};

registerStepType(STEP_TYPE, stepAtts);
