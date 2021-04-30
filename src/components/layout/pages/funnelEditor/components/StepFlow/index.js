import { useState } from "react";
import { Link, Route, useParams, useRouteMatch } from "react-router-dom";
import { DragDropContext, Droppable, Draggable } from "react-beautiful-dnd";
import { makeStyles } from "@material-ui/core/styles";
import { unSlash } from "utils/core";
import { getStepType, useStepType } from "data/step-type-registry";

const useStyles = makeStyles((theme) => ({
  root: {},
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

      children.forEach((child) => processNode(child, level + 1));
      parents.forEach((parent) => processNode(parent, level - 1));
    }
  }

  processNode(steps[0], 1);

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
  // some basic styles to make the items look a bit nicer
  userSelect: "none",
  padding: 10 * 2,
  margin: `0 0 ${10}px 0`,

  // change background colour if dragging
  background: isDragging ? "lightgreen" : "grey",

  // styles we need to apply on draggables
  ...draggableStyle,
});

const getListStyle = (isDraggingOver) => ({
  background: isDraggingOver ? "lightblue" : "lightgrey",
  padding: 10,
  width: 250,
});

const MainPath = ({ steps, edges }) => {
  window.console.log("MainPath");

  const [stepPath, updateStepPath] = useState(processPath(steps, edges));

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

    updateStepPath(items);
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
              return (
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
                      // style={getItemStyle(
                      //   snapshot.isDragging,
                      //   provided.draggableProps.style
                      // )}
                    >
                      <StepFlow
                        icon={StepIcon}
                        name={StepName}
                        read={<StepRead {...step} />}
                      />
                    </div>
                  )}
                </Draggable>
              );
            })}
            {provided.placeholder}
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

const StepLink = (step) => {
  const { url } = useRouteMatch();

  return (
    <li>
      <Link to={`${unSlash(url)}/${step.ID}/edit`}>
        {step.ID}: {step.data.step_type}
      </Link>
    </li>
  );
};
