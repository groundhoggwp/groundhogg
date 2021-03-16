/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
  SlotFillProvider,
  DropZoneProvider,
  FocusReturnProvider,
  Panel,
  PanelBody,
  PanelRow
} from "@wordpress/components";
import { useEffect, useState, useRef } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import {
  serialize,
  parse,
  pasteHandler,
  rawHandler,
  createBlock,
  insertBlock,
  insertBlocks,
  insertDefaultBlock,
  getBlockTypes,
  getBlockInsertionPoint,
  setDefaultBlockName
} from "@wordpress/blocks";

/**
 * External dependencies
 */
 import { Card, TextField, FormControl, FormHelperText, FormControlLabel, Select, Switch, MenuItem } from "@material-ui/core";
 import { makeStyles } from "@material-ui/core/styles";
import {
  InterfaceSkeleton,
  FullscreenMode,
  ComplementaryArea,
} from "@wordpress/interface";
import interact from "interactjs";
import { DateTime } from "luxon";
import { withStyles } from '@material-ui/core/styles';

/**
 * Internal dependencies
 */
import Notices from "./components/notices";
import Header from "./components/header";
import Sidebar from "./components/sidebar";
import BlockEditor from "./components/block-editor";
import TextEditor from "./components/text-editor";
import EditorSteps from "./components/editor-steps";
import SimpleModal from "./components/Modal";
import { getLuxonDate, matchEmailRegex } from "utils/index";
import EditPen from "components/svg/EditPen/";

import { CORE_STORE_NAME, EMAILS_STORE_NAME } from "data";

import "./index.scss";
import "./components/blocks";

let draggedBlockIndex = {};
let draggedBlock = {};
let startInteractJS = false;


const IOSSwitch = withStyles((theme) => ({
  root: {
    width: 34,
    height: 20,
    padding: 0,
    margin: theme.spacing(1),
  },
  switchBase: {
    padding: 1,
    '&$checked': {
      transform: 'translateX(14px)',
      color: theme.palette.common.white,
      '& + $track': {
        backgroundColor: '#0075FF',
        opacity: 1,
        border: 'none',
      },
    },
    '&$focusVisible $thumb': {
      color: '#52d869',
      border: '6px solid #fff',
    },
  },
  thumb: {
    width: 18,
    height: 18,
  },
  track: {
    borderRadius: 26 / 2,
    border: `1px solid ${theme.palette.grey[400]}`,
    backgroundColor: theme.palette.grey[50],
    opacity: 1,
    transition: theme.transitions.create(['background-color', 'border']),
  },
  checked: {},
  focusVisible: {},
}))(({ classes, ...props }) => {
  return (
    <Switch
      focusVisibleClassName={classes.focusVisible}
      disableRipple
      classes={{
        root: classes.root,
        switchBase: classes.switchBase,
        thumb: classes.thumb,
        track: classes.track,
        checked: classes.checked,
      }}
      {...props}
    />
  );
});

export default ({ editorItem, history, ...rest }) => {
  setDefaultBlockName("groundhogg/paragraph");

  const dispatch = useDispatch(EMAILS_STORE_NAME);
  const { sendEmailById, sendEmailRaw } = useDispatch(EMAILS_STORE_NAME);
  const {
    title: defaultTitleValue,
    subject: defaultSubjectValue,
    pre_header: defaultPreHeaderValue,
    content: defaultContentValue,
    editorType,
  } = editorItem.data;

  // Global States
  const [blocksVersionTracker, setBlocksVersionTracker] = useState(0);
  const [blockVersionHistory, setBlockVersionHistory] = useState([parse(defaultContentValue)]);

  // Editor Contents
  const [title, setTitle] = useState(defaultTitleValue);
  const [content, setContent] = useState(defaultContentValue);
  const [blocks, updateBlocks] = useState(parse(defaultContentValue));
  const [subTitle, setSubTitle] = useState(defaultTitleValue);
  const [disableSubTitle, setDisableSubTitle] = useState(false);

  // Modal
  const [open, setOpen] = useState(false);

  // Side Bar States
  const [replyTo, setReplyTo] = useState("");
  const [from, setFrom] = useState("");
  const [viewType, setViewType] = useState("desktop");

  // Unused
  const [isInspecting, setIsInspecting] = useState(false);
  const [altBodyContent, setAltBodyContent] = useState('');
  const [altBodyEnable, setAltBodyEnable] = useState('');
  const [subject, setSubject] = useState(defaultSubjectValue);
  const [preHeader, setPreHeader] = useState(defaultPreHeaderValue);


  const { editorMode, isSaving, item } = useSelect(
    (select) => ({
      editorMode: select(CORE_STORE_NAME).getEditorMode(),
      isSaving: select(CORE_STORE_NAME).isItemsUpdating(),
      item: select(EMAILS_STORE_NAME).getItem(editorItem.ID),
    }),
    []
  );

  /*
   Header Handlers
  */
  const handleTitleChange = (e) => {
    setTitle(e.target.value);
  };

  const handleOpen = () => {
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
  };

  const emailStepBackward = () => {
    const newBlocksVersionTracker = blocksVersionTracker-1
    if(!blockVersionHistory[newBlocksVersionTracker]){
      return;
    }
    setBlocksVersionTracker(newBlocksVersionTracker)
    handleUpdateBlocks(blockVersionHistory[newBlocksVersionTracker], {}, true);

  }
  const emailStepForward = () => {
    const newBlocksVersionTracker = blocksVersionTracker+1
    if(!blockVersionHistory[newBlocksVersionTracker]){
      return;
    }
    setBlocksVersionTracker(newBlocksVersionTracker)
    handleUpdateBlocks(blockVersionHistory[newBlocksVersionTracker], {}, true);
  }

  /*
    Saves Funnel or Email
  */
  const updateItem = (e) => {
    dispatch.updateItem(editorItem.ID, {
      data: {
        subject,
        title,
        pre_header: preHeader,
        status: "ready",
        content,
        last_updated: getLuxonDate("last_updated"),
      },
    });
  };

  /*
    Email Content Handlers
  */
  const handleSubTitleChange = (e) => {
    setSubTitle(e.target.value)
  }

  const toggleSubTitleDisable = () => {
    setDisableSubTitle(disableSubTitle ? false : true)
  }

  const handleSubjectChange = (e) => {
    setSubject(e.target.value);
  };
  const handlePreHeaderChange = (e) => {
    setPreHeader(e.target.value);
  };


  /*
    Side Bar
  */
  const handleSetFrom = (e) => {
    setFrom(e.target.value.trim());
  };
  const handleSetReplyTo = (e) => {
    setReplyTo(e.target.value.trim());
  };


  /*
    Block Handlers
  */
  const handleContentChangeDraggedBlock = () => {
    // if(!startInteractJS){return;}
    // console.log('asdfasdf', draggedBlockIndex)
    let newBlocks = blocks;
    newBlocks.splice(draggedBlockIndex, 0, createBlock(draggedBlock.name));
    handleUpdateBlocks(newBlocks, {},false);
    startInteractJS = false;
  };

  const handleUpdateBlocks = (blocks, selectionObj, updateFromHistory) => {
    // On load this stops a null error
    console.log(blocks, selectionObj, updateFromHistory)
    if (!Array.isArray(blocks)) {
      return;
    }

    // Standard calls for the block editor
    updateBlocks(blocks);
    setContent(serialize(blocks));


    if(!updateFromHistory){
      // Build up the history tracker, except when we're going back and forth
      const newBlocksVersionTracker = blockVersionHistory.length;
      const newBlockVersionHistory = blockVersionHistory
      newBlockVersionHistory.push(blocks);

      setBlocksVersionTracker(newBlocksVersionTracker)
      setBlockVersionHistory(newBlockVersionHistory)
    }
  };

  /*
    Drag Handlers
  */
  const dragMoveListener = (event) => {
    const target = event.target;
    event.target.classList.add("drop-active");

    draggedBlock = JSON.parse(target.getAttribute("data-block"));

    // keep the dragged position in the data-x/data-y attributes
    const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
    const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

    // translate the element
    target.style.webkitTransform = target.style.transform =
      "translate(" + x + "px, " + y + "px)";

    // update the posiion attributes
    target.setAttribute("data-x", x);
    target.setAttribute("data-y", y);


    if(!y){return;}
    draggedBlockIndex = 0;
    let adjustedY = y + 505+55; //Offset by header and block size
    let classToApply = '';
    let saveBlock = false

    document.querySelectorAll('.wp-block').forEach((block, i)=>{
      block.style.borderTop = ''
      block.style.borderBottom = ''


      if(adjustedY >= block.getBoundingClientRect().top && adjustedY <= block.getBoundingClientRect().bottom ){
        draggedBlockIndex = i
        saveBlock = block
        console.log(block.getBoundingClientRect().bottom, block.getBoundingClientRect().top)
      }

      if(draggedBlock === 0){
        // console.log(block, y, block.getBoundingClientRect().bottom)
      }

    })


    if(draggedBlockIndex === 0){
      // console.log(event.target)
      // return;
    }
    document.querySelectorAll('.wp-block')[draggedBlockIndex].style.borderBottom = '1px solid #0075FF';
  };

  const dragStartListener = (event) => {
    // console.log('drag start', event)
    // document.querySelector('.interface-interface-skeleton__sidebar').scrollTop = 0;
    // document.querySelector('.interface-interface-skeleton__sidebar').classList.add("show-overflow");
  };
  const dragEndListener = (event) => {
    document.querySelectorAll('.wp-block').forEach((block, i)=>{
      block.style.borderTop = ''
      block.style.borderBottom = ''
    });

    const target = event.target;

    // keep the dragged position in the data-x/data-y attributes
    const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
    const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

    // translate the element
    target.style.webkitTransform = target.style.transform =
      "translate(" + 0 + "px, " + 0 + "px)";

    // update the posiion attributes
    target.setAttribute("data-x", 0);
    target.setAttribute("data-y", 0);
  };
  const setupDragNDrop = () =>{
      interact(".block-editor__typewriter").unset()
      interact(".block-editor__typewriter").dropzone({
        overlap: 0.75,
        ondropactivate: (event) => {},

        ondragenter: (event) => {
          // var draggableElement = event.relatedTarget;
          console.log('drag enter')
          startInteractJS = true
          var dropzoneElement = event.target.classList.add("active");

        },
        ondragleave: (event) => {
          var dropzoneElement = event.target.classList.remove("active");
        },
        ondrop: (event) => {
          console.log('dropped')
          var dropzoneElement = event.target.classList.remove("active");

          handleContentChangeDraggedBlock();
        },
        ondropdeactivate: (event) => {},
      });

      interact(".side-bar-drag-drop-block").unset()
      interact(".side-bar-drag-drop-block").draggable({
        cursorChecker(action, interactable, element, interacting) {
          return "grab";
        },
        onstart: dragStartListener,
        onend: dragEndListener,
        listeners: { move: dragMoveListener },
        modifiers: [
          interact.modifiers.restrict({
            restriction: interact(".groundhogg-email-editor__email-content"),
            elementRect: { top: 0, left: 0, bottom: 1, right: 1 },
            endOnly: true,
          }),
        ],
      });
      }


  /*
    Sidebar Handlers
  */
  const handleViewTypeChange = (type) => {
    setViewType(type);
  };


  const sendTestEmail = (e) => {
    if (!matchEmailRegex(replyTo)) {
      // console.log('invliad email', replyTo, from, content, subject)
      return;
    }
    // console.log("valid let send");
    sendEmailRaw({
      to: replyTo,
      from,
      from_name: "TEST D",
      content: content,
      subject: subject,
    });
  };
  const handleAltBodyContent = (e) => {
    // console.log('alt body content', altBodyContent)
    setAltBodyContent(e.target.value);
  };
  const handleAltBodyEnable = (e) => {
    // console.log('alt body enable',   altBodyEnable)
    setAltBodyContent(e.target.value);
  };

  const useStyles = makeStyles((theme) => ({
    root: {

    },
    contentMain:{

    },
    sendEmailComponent:{
      position: 'absolute',
      top: '147px',
      left: editorType === 'email' ? '0px' : '305px',
      width: editorType === 'email' ? 'calc(100% - 412px)' : 'calc(100% - 729px)',
      padding: '33px 25px 18px 25px'
    },
    sendEmailComponentLabel:{
      color: '#102640',
      width: '250px',
      display: 'inline-block',
      marginBottom: '20px',
      fontSize: '16px',
      fontWeight: '500'
    },
    skipEmail:{
      fontSize: '14px',
      fontWeight: '300',
      margin: '10px 0px 0px 20px',
      "& label": {
        fontSize: "14px",
        fontWeight: '300'
      },
    },
    newEmailButton:{
      display: 'flex',
      alignItems: 'center',
      fontSize: '14px',
      fontWeight: '400',
      color: '#0075FF',
      float: 'right',
      '& svg':{
        border: '0.3px solid #0075FF',
        borderRadius: '4px',
        marginRight: '5px',
        padding: '4px'
      }
    },
    sendEmailSelect:{
      // Importants are needed while we are still inside wordpress, remove this later
      display: 'block',
      width: 'calc(100%) !important',
      maxWidth: 'calc(100%) !important',
      padding: '5px 20px 5px 20px',
      border: '1.2px solid rgba(16, 38, 64, 0.15) !important'
    },
    subTitleContainer: {
      position: 'absolute',
      top: '347px',
      left: editorType === 'email' ? '20px' : '320px',
      "& label": {
        fontSize: "12px",
      },
      "& svg": {
        cursor: 'pointer',
        marginTop: '10px'
      },
      '& input[type="text"], & input[type="text"]:focus': {
        color: '#000',
        background: 'none',
        fontSize: "16px",
        outline: "none",
        border: "none",
        boxShadow: "none",
        padding: "0",
        marginLeft: "-1px",
      },
    },
    contentSideBar:{

    },
    contentFooter:{
      posiion: 'absolute',
      bottom: '0'
    }

  }));

  const toggleSubTitle = () =>{
    setDisableTitle(disableSubTitle ? false : true )
  }

  useEffect(() => {
    setupDragNDrop()
  }, [blocks]);




  // console.log('blocks', blocks)
  const classes = useStyles();

  let editorPanel;
  switch (editorMode) {
    case "text":
    editorPanel = (
      <TextEditor
        settings={window.Groundhogg.preloadSettings}
        subject={subject}
        handleSubjectChange={handleSubjectChange}
        preHeader={preHeader}
        handlePreHeaderChange={handlePreHeaderChange}
        viewType={viewType}
        handleUpdateBlocks={handleUpdateBlocks}
        blocks={blocks}
      />
    );

      break;
    default:
      editorPanel = (
        <BlockEditor
          settings={window.Groundhogg.preloadSettings}
          subject={subject}
          handleSubjectChange={handleSubjectChange}
          preHeader={preHeader}
          handlePreHeaderChange={handlePreHeaderChange}
          viewType={viewType}
          handleUpdateBlocks={handleUpdateBlocks}
          blocks={blocks}
          editorType={editorType}
        />
      );
  }

  let steps = <div/>
  if(editorType === 'funnel'){
    steps = <div className={classes.contentSideBar}>
      <EditorSteps/>
    </div>
  }

  return (
    <>
      <div className="Groundhogg-BlockEditor">
        {steps}
        <SimpleModal open={open}/>
        <FullscreenMode isActive={false} />
        <SlotFillProvider>
          <DropZoneProvider>
            <FocusReturnProvider>
              <Header
                editorItem={editorItem}
                history={history}
                updateItem={updateItem}
                closeEditor={() => {}}
                isSaving={isSaving}
                title={title}
                handleTitleChange={handleTitleChange}
                editorType={editorType}
                handleOpen={handleOpen}
                emailStepBackward={emailStepBackward}
                emailStepForward={emailStepForward}
              />


              <div className={classes.content}>
                  {/*Notices probably needs to be re-wrote*/}
                  <Notices />
                  <Card className={classes.sendEmailComponent}>
                    <div className={classes.sendEmailComponentLabel}>Select an email to send:</div>

                    <div className={classes.newEmailButton}>
                      <svg width="6" height="6" viewBox="0 0 6 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M5.64932 2.46589V2.31589H5.49932H3.62312V0.389893V0.239893H3.47312H2.48331H2.33331V0.389893V2.31589H0.46875H0.31875V2.46589V3.54589V3.69589H0.46875H2.33331V5.60989V5.75989H2.48331H3.47312H3.62312V5.60989V3.69589H5.49932H5.64932V3.54589V2.46589Z" fill="#0075FF" stroke="#0075FF" stroke-width="0.3"/>
                      </svg>

                      new email
                    </div>

                    <select
                      className={classes.sendEmailSelect}
                      value={''}
                      onChange={()=>{}}
                      label=""
                    >
                      <option value={10}>none</option>
                      <option value={20}>Marketing</option>
                    </select>

                    <div className={classes.skipEmail}>
                      <label>Skip email step if confirmed:</label>
                      <IOSSwitch checked={true} onChange={()=>{}} name="checkedB" />
                    </div>
                  </Card>
                  <div className={classes.subTitleContainer}>
                    <TextField
                      label=""
                      value={subTitle}
                      onChange={handleSubTitleChange}
                      InputProps={{ disableUnderline: true, disabled: disableSubTitle }}
                    />
                    <EditPen onClick={toggleSubTitleDisable}/>
                  </div>

                  {editorPanel}
              </div>

              <Sidebar isInspecting={isInspecting} sendTestEmail={sendTestEmail} handleViewTypeChange={handleViewTypeChange} handleSetFrom={handleSetFrom} handleSetReplyTo={handleSetReplyTo} />

              <ComplementaryArea.Slot scope="gh/v4/core" />

            </FocusReturnProvider>
          </DropZoneProvider>
        </SlotFillProvider>
      </div>
    </>
  );
};
