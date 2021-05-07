import { useState } from "@wordpress/element";
import ButtonGroup from "@material-ui/core/ButtonGroup";
import Button from "@material-ui/core/Button";
import Grid from "@material-ui/core/Grid";
import Box from "@material-ui/core/Box";
import { useCurrentFunnel } from "components/layout/pages/funnelEditor/utils/hooks";
import {
  ACTION,
  ACTIONS,
} from "components/layout/pages/funnelEditor/steps-types/constants";
import { getStepGroup } from "data/step-type-registry";
import { makeStyles } from "@material-ui/core/styles";

const useStyles = makeStyles((theme) => ({
  root: {
    display: "flex",
    justifyContent: "center",
  },
  "& button": {
    background: "white",
  },
  step: {
    textAlign: "center",
  },
  stepIcon: {
    background: "white",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    height: "4rem",
    width: "4rem",
    margin: "0 auto 1rem",
  },
  svg: {
    width: "2rem",
    height: "2rem",
  },
}));

export default () => {
  const { funnelId, createStep } = useCurrentFunnel();
  const [stepGroup, setStepGroup] = useState(ACTION);
  const classes = useStyles();

  const steps = getStepGroup(stepGroup);

  const choseStepType = (type, group) => {
    createStep(funnelId, {
      data: {
        funnel_id: funnelId,
        step_type: type,
        step_group: group,
      },
    });
  };

  return (
    <>
      <div className={classes.root}>
        <ButtonGroup aria-label=" button group">
          <Button>Actions</Button>
          <Button>Benchmarks</Button>
          <Button>Conditional Logic</Button>
          <Button>All</Button>
        </ButtonGroup>
      </div>

      <Grid container spacing={3}>
        {steps.map((StepType) => {
          const { icon } = StepType;

          return (
            <Grid
              item
              xs={12}
              sm={7}
              md={2}
              className={`${classes.step}`}
              onClick={() => choseStepType(StepType.type, StepType.group)}
            >
              <Box borderRadius={5} mb={1} className={`${classes.stepIcon}`}>
                {icon}
              </Box>
              {StepType.name}
            </Grid>
          );
        })}
      </Grid>
    </>
  );
};
