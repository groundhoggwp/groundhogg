import { makeStyles } from "@material-ui/core/styles";
import Typography from "@material-ui/core/Typography";
import Card from "@material-ui/core/Card";
import Grid from "@material-ui/core/Grid";
import Box from "@material-ui/core/Box";

export const ACTION = "action";
export const ACTIONS = "actions";
export const BENCHMARK = "benchmark";
export const BENCHMARKS = "benchmarks";
export const CONDITION = "condition";
export const CONDITIONS = "conditions";

const useStyles = makeStyles((theme) => ({
  card: {
    marginBottom: "10px",
    "& h4": {
      color: "black",
    },
  },
  step: {
    color: "#F58115",
  },
  benchmark: {
    color: "#90C71C",
  },
  name: {
    color: "black",
  },
  type: {
    textTransform: "uppercase",
  },
  // root: {
  //   marginBottom: 35,
  // },
  // icon: {
  //   height: 35,
  //   width: 35,
  //   float: "left",
  // },
  // read: {
  //   marginLeft: 70,
  // },
  // actionIcon: {
  //   backgroundColor: "green",
  //   borderRadius: 50,
  // },
  // benchmarkIcon: {
  //   backgroundColor: "orange",
  //   borderRadius: 5,
  // },
  //
  // conditionIcon: {
  //   backgroundColor: "purple",
  //   borderRadius: 5,
  //   transform: "rotate(45deg)",
  // },
}));

export const STEP_DEFAULTS = {
  icon: <></>,
  name: "",
  edit: ({}) => {
    return <></>;
  },
  read: ({ ID, data }) => {
    return (
      <>
        {ID}: {data.step_type}
      </>
    );
  },
  flow: ({ icon, name, read }) => {
    const classes = useStyles();

    return (
      <Box
        alignItems="center"
        display="flex"
        border={1}
        borderRadius={5}
        borderColor="grey.200"
        p={2}
        mb={1}
        className={`step-block ${classes.card} ${classes.step}`}
      >
        <Grid item pr={2} xs={2}>
          {icon}
        </Grid>
        <Grid item xs={10} className="read">
          <Typography component={"div"} className={classes.name}>
            {read}
          </Typography>
          <small className={classes.type}>{name}</small>
        </Grid>
      </Box>
    );
  },
};

export const BENCHMARK_TYPE_DEFAULTS = {
  ...STEP_DEFAULTS,

  flow: ({ icon, name, read }) => {
    const classes = useStyles();

    return (
      <Box
        alignItems="center"
        display="flex"
        border={1}
        borderRadius={5}
        borderColor="grey.200"
        p={2}
        mb={1}
        className={`step-block ${classes.card} ${classes.benchmark}`}
      >
        <Grid item pr={2} xs={2}>
          {icon}
        </Grid>
        <Grid item xs={10} className="read">
          <Typography component={"div"} className={classes.name}>
            {read}
          </Typography>
          <small className={classes.type}>{name}</small>
        </Grid>
      </Box>
    );
  },
};

export const ACTION_TYPE_DEFAULTS = {
  ...STEP_DEFAULTS,
};
