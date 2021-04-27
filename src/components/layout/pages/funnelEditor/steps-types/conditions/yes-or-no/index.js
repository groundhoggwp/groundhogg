import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import { CONDITION, STEP_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { makeStyles } from "@material-ui/core/styles";
import Typography from "@material-ui/core/Typography";

const STEP_TYPE = "yes_no_condition";

const useStyles = makeStyles((theme) => ({
  root: {
    marginBottom: 30,
  },
  icon: {
    height: 30,
    width: 30,
    float: "left",
  },
  read: {
    marginLeft: 70,
  },
  actionIcon: {
    backgroundColor: "green",
    borderRadius: 50,
  },
  benchmarkIcon: {
    backgroundColor: "orange",
    borderRadius: 5,
  },

  conditionIcon: {
    backgroundColor: "purple",
    borderRadius: 5,
    transform: "rotate(45deg)",
  },
}));

const stepAtts = {
  ...STEP_DEFAULTS,

  type: STEP_TYPE,

  group: CONDITION,

  name: "Yes/No",

  icon: <LocalOfferIcon />,

  // read: ({ data, meta, stats }) => {
  //   return <></>;
  // },

  edit: ({ data, meta, stats }) => {
    return <></>;
  },

  flow: ({ icon, read }) => {
    const classes = useStyles();

    return (
      <>
        <div className={classes.root}>
          <div className={classes.icon + " " + classes.conditionIcon}>
            {icon}
          </div>
          <div className={classes.read}>
            <Typography variant={"p"} component={"div"}>
              {read}
            </Typography>
          </div>
        </div>
      </>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
