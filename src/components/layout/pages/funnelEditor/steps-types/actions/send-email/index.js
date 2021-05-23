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
import Mail from "components/svg/Mail/";
import { Toggle } from "components/core-ui/toggle/";
import { DropDown } from "components/core-ui/drop-down/";
import { DynamicForm } from "components/core-ui/dynamic-form/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme } from "../../../../../../../theme";

const STEP_TYPE = "send_email";

const theme = createTheme({});
const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100% - 50px)",
    padding: "33px 25px 18px 25px",
  },
  actionLabel: {
    color: "#102640",
    width: "250px",
    display: "inline-block",
    marginBottom: "10px",
    fontSize: "16px",
    fontWeight: "500",
  },
  newEmailButton: {
    display: "flex",
    alignItems: "center",
    fontSize: "14px",
    fontWeight: "400",
    color: theme.palette.primary.main,
    float: "right",
    "& svg": {
      border: `0.3px solid ${theme.palette.primary.main}`,
      borderRadius: "4px",
      marginRight: "5px",
      padding: "4px",
    },
  },
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Send Email",

  icon: <Mail />,

  read: ({ data, meta, stats }) => {
    return <>Email is sent</>;
  },

  edit: ({ data, meta, stats }) => {
    const { items } = useSelect((select) => {
      const store = select(EMAILS_STORE_NAME);

      return {
        items: store.getItems(),
      };
    }, []);

    const { createItem, fetchItems, deleteItems } = useDispatch(
      EMAILS_STORE_NAME
    );

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

    const emailFormated = items.map((email) => {
      return {
        display: email.data.title,
        value: email.ID,
      };
    });

    const formElements = [
      {
        label: "Select an email to send:",
        component: (
          <DropDown
            id={"email"}
            options={emailFormated}
            value={formData["email"]}
            onChange={hanldeFormChange}
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
        <div className={classes.actionLabel}>Select an email to send:</div>

        <div className={classes.newEmailButton}>
          <svg
            width="6"
            height="6"
            viewBox="0 0 6 6"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M5.64932 2.46589V2.31589H5.49932H3.62312V0.389893V0.239893H3.47312H2.48331H2.33331V0.389893V2.31589H0.46875H0.31875V2.46589V3.54589V3.69589H0.46875H2.33331V5.60989V5.75989H2.48331H3.47312H3.62312V5.60989V3.69589H5.49932H5.64932V3.54589V2.46589Z"
              fill="#0075FF"
              stroke="#0075FF"
              stroke-width="0.3"
            />
          </svg>
          new email
        </div>

        <DynamicForm
          children={formElements}
          hanldeFormChange={hanldeFormChange}
        />
      </Card>
    );
  },
};

registerStepType(STEP_TYPE, stepAtts);
