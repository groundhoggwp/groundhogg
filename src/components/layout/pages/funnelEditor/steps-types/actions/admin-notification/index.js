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
import Tag from "components/svg/Tag/";
import { Toggle } from "components/core-ui/toggle/";
import { DropDown } from "components/core-ui/drop-down/";
import { DynamicForm } from "components/core-ui/dynamic-form/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme } from "../../../../../../../theme";

const STEP_TYPE = "admin_notification";

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

  name: "Admin Notification",

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

    const formElements = [
      {
        label: "Send as SMS:",
        component: (
          <>
            <Toggle
              id={"hide-admin-links"}
              checked={formData["hide-admin-links"]}
              onChange={hanldeFormChange}
              backgroundColor={theme.palette.primary.main}
              name="checked"
            />
            Send as text message instead of as an email
          </>
        ),
      },
      {
        label: "Send To:",
        component: (
          <>
            <TextField
              id="send-to"
              rows={4}
              value={note}
              onChange={handleNoteChange}
              defaultValue="Default Value"
            />
            <div>Use any valid replacement codes</div>
          </>
        ),
      },
      {
        label: "From:",
        component: (
          <>
            <TextField
              id="from"
              rows={4}
              value={note}
              onChange={handleNoteChange}
              defaultValue="Default Value"
            />
            <div>Use any valid replacement codes</div>
          </>
        ),
      },
      {
        label: "Reply To:",
        component: (
          <>
            <TextField
              id="reply-to"
              rows={4}
              value={note}
              onChange={handleNoteChange}
              defaultValue="Default Value"
            />
            <div>Use any valid replacement codes</div>
          </>
        ),
      },
      {
        label: "Subject:",
        component: (
          <>
            <TextField
              id="subject"
              rows={4}
              value={note}
              onChange={handleNoteChange}
              defaultValue="Default Value"
            />
            <div>Use any valid replacement codes</div>
          </>
        ),
      },
      {
        label: "Content:",
        component: (
          <>
            <TextField
              id="content"
              rows={4}
              value={note}
              onChange={handleNoteChange}
              defaultValue="Default Value"
            />
            <div>Use any valid replacement codes</div>
          </>
        ),
      },
      {
        label: "Content:",
        component: (
          <>
            <TextField
              id="content"
              label="Multiline"
              multiline
              rows={4}
              value={formData["content"]}
              onChange={handleNoteChange}
              defaultValue="Default Value"
            />
            <div>Use any valid replacement codes</div>
          </>
        ),
      },
      {
        label: "Hide admin links:",
        component: (
          <Toggle
            id={"hide-admin-links"}
            checked={formData["hide-admin-links"]}
            onChange={hanldeFormChange}
            backgroundColor={theme.palette.primary.main}
            name="checked"
          />
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
        <div className={classes.actionLabel}>Admin Notification</div>

        <DynamicForm
          children={formElements}
          hanldeFormChange={hanldeFormChange}
        />
      </Card>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
