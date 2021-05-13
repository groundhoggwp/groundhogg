import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { BENCHMARK, BENCHMARK_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import Select from "@material-ui/core/Select";
import MenuItem from "@material-ui/core/MenuItem";
import FormControl from "@material-ui/core/FormControl";
import TextField from "@material-ui/core/TextField";
import { makeStyles } from "@material-ui/core/styles";

const STEP_TYPE = "tag_removed";

const useStyles = makeStyles((theme) => ({
  p: {
    alignItems: "center",
    display: "flex",
    fontSize: "1rem",
  },
}));

const stepAtts = {
  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: "Tag Removed",

  icon: <LocalOfferIcon />,

  edit: ({ data, meta, stats }) => {
    window.console.log({ data });
    window.console.log({ meta });
    const classes = useStyles();

    return (
      <>
        <p className={classes.p}>
          This benchmark runs when
          <FormControl variant="outlined">
            <Select value={"any"}>
              <MenuItem value={"any"}>any</MenuItem>
              <MenuItem value={"all"}>all</MenuItem>
            </Select>
          </FormControl>
          of the following tags are removed:
        </p>
        <p>
          <TextField
            id="outlined-basic"
            label="Tag(s) to remove"
            helperText="Add new tags by hitting enter or by typing a comma."
            variant="outlined"
          />
        </p>
      </>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
