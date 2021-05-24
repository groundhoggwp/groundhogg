import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { BENCHMARK } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { BENCHMARK_TYPE_DEFAULTS } from "components/layout/pages/funnels/editor/steps-types/constants";
import {
  Card,
  CardContent,
  MenuItem,
  TextField,
  FormHelperText,
  Select,
} from "@material-ui/core";
import SettingsRow from "components/layout/pages/funnelEditor/components/SettingsRow";

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
    return (
      <Card>
        <CardContent>
          <SettingsRow>
            <TextField
              label="Which field:"
              variant="outlined"
              helperText="Will run when the given field is changed."
            />
          </SettingsRow>
          <SettingsRow>
            <Select variant="outlined">
              <MenuItem value="">
                <em>None</em>
              </MenuItem>
              <MenuItem value={10}>Ten</MenuItem>
            </Select>
            <FormHelperText>When Set To:</FormHelperText>
          </SettingsRow>
        </CardContent>
      </Card>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
