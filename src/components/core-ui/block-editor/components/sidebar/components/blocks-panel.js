/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment, useState } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";
import { getBlockTypes } from "@wordpress/blocks";

/**
 * External dependencies
 */
import { Button, Card, TextField } from "@material-ui/core";
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
/**
 * Internal dependencies
 */
 import BlocksDivider from "components/svg/block-editor/BlocksDivider/";
 import BlocksHeading from "components/svg/block-editor/BlocksHeading/";
 import BlocksImage from "components/svg/block-editor/BlocksImage/";
 import BlocksSpacer from "components/svg/block-editor/BlocksSpacer/";
 import BlocksText from "components/svg/block-editor/BlocksText/";

export default function ({blocks, sideBarBlockDisplayType  }) {
  const useStyles = makeStyles((theme) => ({
    root: {
      marginTop: "20px",
      overflow: "visible",
      "&:last-of-type": {
        paddingBottom: "20px",
      },
    },
    blocksTitles: {
        color: 'rgba(0,0,0,0.3)',
        display: "inline-block",
        fontSize: "18px",
        width: "100%",
        textAlign: "center",
        fontWeight: "500",
        paddingTop: "20px",
        '& span':{
          cursor: 'pointer',
          margin: '18px 5px 5px 5px'
        }
    },
    panelTitle: {
      color: 'rgba(0,0,0,1.0)'
    },
    block: {
      position: "relative",
      display: "inline-block",
      margin: "10px",
      width: "82px",
      height: "78px",
      border: "1.2px solid rgba(0, 117, 255, 0.2)",
      borderRadius: "5px",
      textAlign: "center",
      fontWeight: "500",
      color: "#102640",
      "& svg, & path": {
        stroke: "#102640",
      },
      "&:hover": {
        color: "#fff",
        background: theme.palette.primary.main,
      },
      "&:hover svg, &:hover path": {
        stroke: "#fff",

        color: "#fff",
      },
    },
    fillHover: {
      '&:hover svg, &:hover path': {
        fill: "#fff",
      }
    },
    icon: {
      margin: "15px 0 0 0",
    },
    name: {
      position: "absolute",
      bottom: "5px",
      width: "100%",
      textAlign: "center",
      fontWeight: "500",
    },
  }));

  const classes = useStyles();
  // const blocks = ;

  const blockPanel = sideBarBlockDisplayType === 'inspector' ? (
    <InspectorSlot bubblesVirtually />
  ) : (
    <>
      {getBlockTypes().map((block) => {
        const title = block.title.replace("Groundhogg - ", "");

        let icon = <BlocksImage />;
        let fillHoverClass = ''
        switch (title) {
          case "Spacer":
            icon = <BlocksSpacer stroke={""} fill={"none"} />;
            break;
          case "Divider":
            icon = (
              <BlocksDivider stroke={""} fill={"#000"} fillSecondary={"#ccc"} />
            );
            break;
          case "HTML":
            icon = <BlocksImage />;
            fillHoverClass = classes.fillHover;
            break;
          case "Button":
            icon = <BlocksImage />;
            fillHoverClass = classes.fillHover;
            break;
          case "Image":
            icon = <BlocksImage />;
            fillHoverClass = classes.fillHover;
            break;
          case "Heading":
            icon = <BlocksHeading />;
            fillHoverClass = classes.fillHover;
            break;
          case "paragraph":
            icon = <BlocksText />;
            fillHoverClass = classes.fillHover;
            break;
        }

        return (
          <div
            data-block={JSON.stringify(block)}
            className={
              `${classes.block} ${fillHoverClass} block-editor-block side-bar-drag-drop-block`
            }
          >
            <div className={classes.icon}>{icon}</div>
            <div className={classes.name}>{title}</div>
          </div>
        );
      })}
    </>
  );

  return (
    <Card className={classes.root}>
      <div className={classes.blocksTitles}>
      <span className={sideBarBlockDisplayType === 'blocks' ? classes.panelTitle : ''}>Blocks</span>
      <span className={sideBarBlockDisplayType === 'inspector' ? classes.panelTitle : ''}>Inspector</span>
      </div>
      {blockPanel}
    </Card>
  );
}
