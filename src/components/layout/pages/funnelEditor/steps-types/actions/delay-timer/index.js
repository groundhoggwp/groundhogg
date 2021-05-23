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
import { Button, Card, Switch, TextField } from "@material-ui/core";
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
import { withStyles } from "@material-ui/core/styles";
/**
 * Internal dependencies
 */
import { EMAILS_STORE_NAME } from "../../../../../../../data";
import NewUser from "components/svg/NewUser/";
import Tag from "components/svg/Tag/";
import { Toggle } from "components/core-ui/toggle/";
import { DropDown } from "components/core-ui/drop-down/";
import { DynamicForm } from "components/core-ui/dynamic-form/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { DatePicker } from "components/core-ui/date-picker";
import { TimePicker } from "components/core-ui/time-picker";
import { registerStepType } from "data/step-type-registry";
import { createTheme } from "../../../../../../../theme";

const STEP_TYPE = "delay_timer";

const theme = createTheme({});
const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100% - 50px)",
    padding: "33px 25px 18px 25px",
  },
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Delay Timer",

  icon: <Tag />,

  read: ({ data, meta, stats }) => {
    return <>Create User</>;
  },

  edit: ({ data, meta, stats }) => {
    const [formData, setFormData] = useState({});
    const classes = useStyles();

    const hanldeFormChange = (e) => {
      if (e.target.type === "checkbox") {
        formData[e.target.id] = e.target.checked;
      } else {
        formData[e.target.id] = e.target.value;
      }

      setFormData(formData);
    };

    const timeOptions = [
      {
        display: "Minutes",
        value: "minutes",
      },
      {
        display: "Hours",
        value: "hours",
      },
      {
        display: "Days",
        value: "days",
      },
      {
        display: "Weeks",
        value: "weeks",
      },
      {
        display: "Months",
        value: "months",
      },
    ];

    const runTimeOptions = [
      {
        display: "Immediately",
        value: "immediately",
      },
      {
        display: "At time of day",
        value: "at-time-of-day",
      },
    ];

    const formElements = [
      {
        label: "Wait at least:",
        component: (
          <>
            <input
              id="wait-time-number"
              value={formData["wait-time-number"]}
              onChange={hanldeFormChange}
            />
            <DropDown
              id={"wait-time-type"}
              options={timeOptions}
              value={formData["wait-time-type"]}
              onChange={hanldeFormChange}
            />
          </>
        ),
      },
      {
        label: "And run:",
        component: (
          <>
            <DropDown
              id={formData["date-passed"]}
              options={runTimeOptions}
              value={formData["date-passed"]}
              onChange={hanldeFormChange}
            />

            <div>
              Run in the contact's local time.
              <Toggle
                id={"conditional-logic"}
                checked={formData["conditional-logic"]}
                onChange={hanldeFormChange}
                backgroundColor={theme.palette.primary.main}
                name="checked"
              />
            </div>
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
        <div className={classes.actionLabel}>Delay Timer</div>

        <DynamicForm
          children={formElements}
          hanldeFormChange={hanldeFormChange}
        />
      </Card>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
