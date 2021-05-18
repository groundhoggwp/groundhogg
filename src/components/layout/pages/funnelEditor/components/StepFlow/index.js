import { useState } from "react";
import { Link, Route, useParams, useRouteMatch } from "react-router-dom";
import { DragDropContext, Droppable, Draggable } from "react-beautiful-dnd";
import { makeStyles } from "@material-ui/core/styles";
import { unSlash } from "utils/core";
import { useDispatch } from "@wordpress/data";
import { FUNNELS_STORE_NAME } from "data/funnels";
import { getStepType, useStepType } from "data/step-type-registry";
import Button from "@material-ui/core/Button";
import AddIcon from "@material-ui/icons/Add";

const useStyles = makeStyles((theme) => ({
  root: {},
  block: {
    alignItems: "center",
    display: "flex",
    justifyContent: "center",
    width: "100%",
  },
  draggableWrapper: {
    position: "relative",
  },
  addStepDivider: {
    alignItems: "center",
    bottom: "-23px",
    cursor: "pointer",
    display: "flex",
    left: 0,
    opacity: 0,
    position: "absolute",
    width: "100%",
    zIndex: 1,
    "&:hover": {
      opacity: 1,
    },
    "& div": {
      background: "white",
      display: "flex",
      width: 36,
      height: 36,
      alignItems: "center",
      justifyContent: "center",
      borderRadius: "50%",
    },
    "& div svg": {
      border: "2px solid #0075FF",
      borderRadius: "4px",
      color: "#0075FF",
      height: ".618rem",
      width: ".618rem",
    },
    "&::before": {
      background: "#0075FF",
      content: '""',
      display: "block",
      flexGrow: 1,
      height: 1,
    },
    "&::after": {
      background: "#0075FF",
      content: '""',
      display: "block",
      flexGrow: 1,
      height: 1,
    },
  },
  link: {
    textDecoration: "none",
  },
}));

/**
 * Sorts the nodes into the best order based on the state of the graph
 * @param steps
 * @param edges
 */
function processPath(steps, edges) {
  const levels = {};

  function processNode(node, prev_level, level) {
    if (levels.hasOwnProperty(node)) {
      if (levels[node] === level) {
        return;
      } else if (prev_level < level && prev_level < levels[node]) {
        return;
      } else if (prev_level > level && prev_level > levels[node]) {
        return;
      }
    }

    levels[node] = level;

    if (edges) {
      const children = edges.filter((edge) => edge.from_id === node);
      const parents = edges.filter((edge) => edge.to_id === node);

      children.forEach((child) => processNode(child.to_id, level, level + 1));
      parents.forEach((parent) =>
        processNode(parent.from_id, level, level - 1)
      );
    }
  }

  processNode(steps[0].ID, 0, 1);

  steps.sort((a, b) => {
    return levels[a.ID] - levels[b.ID];
  });

  return steps;
}

export default (props) => {
  const classes = useStyles();
  const { path } = useRouteMatch();

  return (
    <>
      <Route path={`${path}/branch/:branchId/:branchPath`}>
        <BranchPath {...props} />
      </Route>
      <Route path={"/"}>
        <MainPath {...props} />
      </Route>
    </>
  );
};

const reorder = (list, startIndex, endIndex) => {
  const result = Array.from(list);
  const [removed] = result.splice(startIndex, 1);
  result.splice(endIndex, 0, removed);

  return result;
};

const getItemStyle = (isDragging, draggableStyle) => ({
  userSelect: "none",
  boxShadow: isDragging ? "5px 5px 8px 0px #00000040" : "none",
  marginBottom: "10px",
  ...draggableStyle,
});

const MainPath = ({ steps, edges, ID }) => {
  window.console.log("MainPath", steps, edges);

  const classes = useStyles();
  const stepPath = processPath(steps, edges);
  const { url, path } = useRouteMatch();
  const { updateEdges } = useDispatch(FUNNELS_STORE_NAME);

  function onDragEnd(result) {
    // dropped outside the list
    if (!result.destination) {
      return;
    }

    const items = reorder(
      stepPath,
      result.source.index,
      result.destination.index
    );

    // Create a new levels object with the new order.
    const newEdges = [];
    items.map((item, i) => {
      if (!items[i + 1]) {
        return false;
      }

      newEdges.push({
        from_id: item.ID,
        to_id: items[i + 1].ID,
        funnel_id: ID,
      });

      return true;
    });

    updateEdges(ID, newEdges);
  }

  return (
    <DragDropContext onDragEnd={onDragEnd}>
      <Droppable droppableId="droppable">
        {(provided, snapshot) => (
          <div
            {...provided.droppableProps}
            ref={provided.innerRef}
            // style={getListStyle(snapshot.isDraggingOver)}
          >
            {stepPath.map((step, index) => {
              const { StepIcon, StepRead, StepFlow, StepName } = useStepType(
                step.data.step_type
              );

              let match = useRouteMatch(`${unSlash(path)}/${step.ID}/edit`);

              return (
                <div className={classes.draggableWrapper}>
                  <Draggable
                    key={step.ID}
                    draggableId={String(step.ID)}
                    index={index}
                  >
                    {(provided, snapshot) => (
                      <div
                        ref={provided.innerRef}
                        {...provided.draggableProps}
                        {...provided.dragHandleProps}
                        style={getItemStyle(
                          snapshot.isDragging,
                          provided.draggableProps.style
                        )}
                      >
                        <StepFlow
                          isEditing={!!match}
                          ID={step.ID}
                          icon={StepIcon}
                          name={StepName}
                          read={<StepRead {...step} />}
                        />
                      </div>
                    )}
                  </Draggable>
                  <Link to={`${unSlash(url)}/add`} className={classes.link}>
                    <div className={classes.addStepDivider}>
                      <div>
                        <AddIcon />
                      </div>
                    </div>
                  </Link>
                </div>
              );
            })}
            {provided.placeholder}
            <Link to={`${unSlash(url)}/add`} className={classes.link}>
              <Button
                variant="outlined"
                color="primary"
                className={classes.block}
              >
                <AddIcon />
                Add new Step
              </Button>
            </Link>
          </div>
        )}
      </Droppable>
    </DragDropContext>
  );
};

const BranchPath = ({ steps, edges }) => {
  window.console.log("BranchPath");
  const { branch, branchPath } = useParams();
  const stepPath = processPath(steps, edges).filter((step) => {
    return step.data.path === branchPath && step.data.branch === branch;
  });

  return (
    <>
      {"Branch..."}
      {stepPath.map((step) => {
        const { StepIcon, StepRead, StepFlow, StepName } = useStepType(
          step.data.step_type
        );

        return (
          <StepFlow
            icon={<StepIcon />}
            name={StepName}
            read={<StepRead {...step} />}
          />
        );
      })}
    </>
  );
};
