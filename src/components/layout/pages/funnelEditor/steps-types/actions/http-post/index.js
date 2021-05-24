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
import Tag from "components/svg/Tag/";
import { Toggle } from "components/core-ui/toggle/";
import { DropDown } from "components/core-ui/drop-down/";
import { DynamicForm } from "components/core-ui/dynamic-form/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { DatePicker } from "components/core-ui/date-picker";
import { TimePicker } from "components/core-ui/time-picker";
import { registerStepType } from "data/step-type-registry";
import { createTheme } from "../../../../../../../theme";

const STEP_TYPE = "http_post";

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

  name: "http post",

  icon: <Tag />,

  read: ({ data, meta, stats }) => {
    return <>HTTP Post</>;
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

    const methodOptions = [
      {
        display: "POST",
        value: "post",
      },
      {
        display: "PUT",
        value: "put",
      },
      {
        display: "PATCH",
        value: "patch",
      },
      {
        display: "GET",
        value: "GET",
      },
      {
        display: "DELETE",
        value: "delete",
      },
    ];
    const contentTypeOptions = [
      {
        display: "x-www-form-urlencoded",
        value: "x-www-form-urlencoded",
      },
      {
        display: "JSON",
        value: "json",
      },
    ];

    const formElements = [
      {
        label: "Method:",
        component: (
          <>
            <DropDown
              id={"method"}
              options={methodOptions}
              value={formData["method"]}
              onChange={hanldeFormChange}
            />
          </>
        ),
      },
      {
        label: "Target URL:",
        component: (
          <>
            <input
              id="target-url"
              value={formData["target-url"]}
              onChange={handleFormChange}
              defaultValue="Default Value"
            />
          </>
        ),
      },
      {
        label: "Content-Type:",
        component: (
          <>
            <DropDown
              id={"method"}
              options={contentTypeOptions}
              value={formData["method"]}
              onChange={hanldeFormChange}
            />
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
          <div className={classes.actionLabel}>HTTP Post</div>

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
