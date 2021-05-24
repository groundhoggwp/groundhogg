/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment, useState } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";
import { useSelect, useDispatch } from "@wordpress/data";

/**
 * External dependencies
 */
import {
  Button,
  Card,
  CardContent,
  Switch,
  TextField,
} from "@material-ui/core";
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
import { withStyles } from "@material-ui/core/styles";
/**
 * Internal dependencies
 */
import { EMAILS_STORE_NAME } from "../../../../../../../data";
import NewUser from "components/svg/NewUser/";
import { Toggle } from "components/core-ui/toggle/";
import { DropDown } from "components/core-ui/drop-down/";
import { DynamicForm } from "components/core-ui/dynamic-form/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme } from "../../../../../../../theme";

const STEP_TYPE = "apply_owner";

const theme = createTheme({});
const useStyles = makeStyles((theme) => ({
  root: {
    // width: "calc(100% - 50px)",
    // padding: "33px 25px 18px 25px",
  },
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Apply Owner",

  icon: <NewUser />,

  read: ({ data, meta, stats }) => {
    return <>Create User</>;
  },

  edit: ({ data, meta, stats }) => {
    const formElements = [
      {
        label: "User Role",
        component: (
          <>
            <DropDown
              id={formData["owner"]}
              options={["Owner List goes here"]}
              value={formData["owner"]}
              onChange={hanldeFormChange}
            />
            <br />
            Selecting more than 1 owner will create a round robin..
          </>
        ),
      },
      {
        label: "Enable conditional logic:",
        component: (
          <Toggle
            id={"conditional-logic"}
            checked={formData["conditional-logic"]}
            onChange={hanldeFormChange}
            backgroundColor={theme.palette.primary.main}
            name="checked"
          />
        ),
      },
    ];
    return (
      <Card className={classes.root}>
        <CardContent>
          <div className={classes.actionLabel}>Apply Owner</div>

          <DynamicForm
            children={formElements}
            hanldeFormChange={hanldeFormChange}
          />
        </CardContent>
      </Card>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
