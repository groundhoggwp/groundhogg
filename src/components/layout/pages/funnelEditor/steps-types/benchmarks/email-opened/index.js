import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { BENCHMARK } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { BENCHMARK_TYPE_DEFAULTS } from "components/layout/pages/funnels/editor/steps-types/constants";

/**
 * External dependencies
 */
import { Card, CardContent, TextField } from "@material-ui/core";
import MuiAlert from "@material-ui/lab/Alert";
import { makeStyles } from "@material-ui/core/styles";
import SettingsRow from "components/layout/pages/funnelEditor/components/SettingsRow";

const STEP_TYPE = "email_opened";

const stepAtts = {
  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: "Email Opened",

  icon: <LocalOfferIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },
  edit: ({ data, meta, stats }) => {
    return (
      <Card>
        <CardContent>
          <MuiAlert severity="info">
            Tracking email opens is not always an exact science. Results may
            vary depending on the email client used by your contacts, false
            positives and false negatives are possible. We highly recommend you
            use the Link Clicked benchmark instead. Use with caution.
          </MuiAlert>

          <SettingsRow>
            <TextField
              label="Select email steps:"
              variant="outlined"
              helperText="Update the funnel to show new email steps in the email step picker."
            />
          </SettingsRow>
        </CardContent>
      </Card>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
