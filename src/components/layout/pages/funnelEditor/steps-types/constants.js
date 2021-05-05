import { makeStyles } from "@material-ui/core/styles";
import { Link, useRouteMatch } from "react-router-dom";
import Typography from "@material-ui/core/Typography";
import Card from "@material-ui/core/Card";
import Grid from "@material-ui/core/Grid";
import Box from "@material-ui/core/Box";
import { unSlash } from "utils/core";

export const ACTION = "action";
export const ACTIONS = "actions";
export const BENCHMARK = "benchmark";
export const BENCHMARKS = "benchmarks";
export const CONDITION = "condition";
export const CONDITIONS = "conditions";

const useStyles = makeStyles((theme) => ({
  card: {
    background: "white",
    // marginBottom: "10px",
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
  link: {
    textDecoration: "none",
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
  flow: ({ ID, icon, name, read, isEditing }) => {
    const classes = useStyles();
    const { url } = useRouteMatch();

    return (
      <Link to={`${unSlash(url)}/${ID}/edit`} className={classes.link}>
        <Box
          alignItems="center"
          display="flex"
          borderRadius={5}
          border={isEditing ? 2 : 1}
          borderColor={`${isEditing ? "" : "grey.200"}`}
          p={2}
          mb={1}
          className={`step-block ${classes.card} ${classes.step}`}
          data-title={`Step id #${ID}}`}
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
      </Link>
    );
  },
};

export const BENCHMARK_TYPE_DEFAULTS = {
  ...STEP_DEFAULTS,

  flow: ({ ID, icon, name, read, isEditing }) => {
    const classes = useStyles();
    const { url } = useRouteMatch();

    return (
      <Link to={`${unSlash(url)}/${ID}/edit`} className={classes.link}>
        <Box
          alignItems="center"
          display="flex"
          borderRadius={5}
          border={isEditing ? 2 : 1}
          borderColor={`${isEditing ? "" : "grey.200"}`}
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
      </Link>
    );
  },
};

export const ACTION_TYPE_DEFAULTS = {
  ...STEP_DEFAULTS,
};
