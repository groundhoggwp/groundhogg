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
import { useEffect, useState, useRef, createElement } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import {
  serialize,
  parse,
  pasteHandler,
  rawHandler,
  createBlock,
  getBlockTypes,
  getBlockInsertionPoint,
  getBlocksByClientId,
  setDefaultBlockName
} from "@wordpress/blocks";
import {
  insertBlock,
  insertDefaultBlock
} from "@wordpress/block-editor";


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


// Most Complete Documentation can be found here
// https://developer.wordpress.org/block-editor/reference-guides/data/data-core-block-editor/
// Works in the console! and in the code
// wp.data.dispatch( 'core/block-editor' ).insertBlock( wp.blocks.createBlock( 'groundhogg/paragraph' ) );
// But just standard insertBlock works as well
export default ({ editorItem, history, ...rest }) => {
  const useStyles = makeStyles((theme) => ({
    root: {

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
    contentSideBar:{

    },
    contentFooter:{
      posiion: 'absolute',
      bottom: '0'
    }
  }));
  const classes = useStyles();

  const dispatch = useDispatch(EMAILS_STORE_NAME);

  const { sendEmailById, sendEmailRaw } = useDispatch(EMAILS_STORE_NAME);
  const {
    title: defaultTitleValue,
    subject: defaultSubjectValue,
    pre_header: defaultPreHeaderValue,
    content: defaultContentValue,
    notes: defaultNotes,
    editorType,
  } = editorItem.data;

  // Global States
  const [blocksVersionTracker, setBlocksVersionTracker] = useState(0);
  const [blockVersionHistory, setBlockVersionHistory] = useState([parse(defaultContentValue)]);
  const [noticeText, setNoticeText] = useState('');

  // Header States
  const [altBodyContent, setAltBodyContent] = useState('');
  const [altBodyEnable, setAltBodyEnable] = useState('');
  const [subject, setSubject] = useState(defaultSubjectValue);
  const [preHeader, setPreHeader] = useState(defaultPreHeaderValue);

  // Editor Contents
  const [title, setTitle] = useState(defaultTitleValue);
  const [content, setContent] = useState(defaultContentValue);
  const [blocks, setBlocks] = useState(parse(defaultContentValue).length === 0 ? [createBlock("groundhogg/paragraph", {content: defaultContentValue})] : parse(defaultContentValue)); // Old emails need to be converted to blocks
  const [subTitle, setSubTitle] = useState(defaultTitleValue);
  const [disableSubTitle, setDisableSubTitle] = useState(false);

  // Drag States
  const [dragNDropBlock, setDragNDropBlock] = useState('');
  const [dragPosition, setDragPosition] = useState(0);
  // Drag Timers
  let dragTimer;
  let dragOverTimer;

  // Modal
  const [open, setOpen] = useState(false);

  // Side Bar States
  const [replyTo, setReplyTo] = useState("");
  const [from, setFrom] = useState("");
  const [viewType, setViewType] = useState("desktop");
  const [messageType, setMessageType] = useState("marketing");
  const [emailAlignment, setEmailAlignment] = useState("left")
  const [notes, setNotes] = useState(defaultNotes);

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
        content: serialize(blocks),
        last_updated: getLuxonDate("last_updated"),
        notes //API isn't accepting these
      },
    });

    setNoticeText("Email Updated")
    setTimeout(() => {
      setNoticeText("")
    }, 1000)
  };

  /*
    Email Content Handlers
  */
  const handleSubTitleChange = (e) => {
    setSubTitle(e.target.value)
  }
  const handleInsertReplacement = (e) => {
    console.log(e)
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
  const handleEmailAlignmentChange = (alignment) => {
    setEmailAlignment(alignment);
  };
  const handleMessageType = (e) => {
    setMessageType(e.target.value);
  };

  /*
    Drag & Drop Events
  */
  const handleDrop = () => {
    // This will get re-setup every time the component updates, need to kill it here
    document.addEventListener("dragover", (e)=>{}, false)

    let upperBound = false;
    let lowerBound = false
    let dragIndex = 0;
    // Run through existing blocks to find their coords and where to drop the block
    document.querySelectorAll('.wp-block').forEach((ele, i)=>{
      if(!upperBound && (dragPosition > ele.getBoundingClientRect().y)){
        upperBound = ele.getBoundingClientRect().y
      }

      if(!lowerBound && (dragPosition < ele.getBoundingClientRect().y)){
        dragIndex = i
        lowerBound = ele.getBoundingClientRect().y
      }
    })

    let newBlocks = []
    // For some reason the .splice version won't update the view, no idea why this is the case to a manual splice function is needed
    blockVersionHistory[blocksVersionTracker].forEach((block, i)=>{
      if(dragIndex === i){
        newBlocks.push(createBlock(dragNDropBlock))
      }
      newBlocks.push(block)
    })
    handleUpdateBlocks(newBlocks, {}, false);
  }

  const handleDragStart = (blockName, e) => {
    clearTimeout(dragTimer)
    dragTimer = setTimeout(()=>{
      setDragNDropBlock(blockName)
    }, 10);
  }

  const handleDragEnd = (e, obj) => {
    setDragNDropBlock('')
  }

  document.addEventListener("dragover", (e)=>{
    clearTimeout(dragOverTimer)
    dragOverTimer = setTimeout(()=>{
      e = e || window.event;


      if(setDragPosition !== e.pageY){
        console.log("X: "+e.pageY+" Y: "+e.pageY);
        setDragPosition(e.pageY)
      }
    }, 50)
  }, false);


  /*
    Block Handlers
  */
  const handleUpdateBlocks = (blocks, object, updateFromHistory) => {
    setBlocks(blocks);

    // Build up the history tracker, except when we're going back and forth
    if(!updateFromHistory){
      const newBlocksVersionTracker = blockVersionHistory.length;
      const newBlockVersionHistory = blockVersionHistory
      newBlockVersionHistory.push(blocks);

      setBlocksVersionTracker(newBlocksVersionTracker)
      setBlockVersionHistory(newBlockVersionHistory)
    }
  };

  /*
    Sidebar Handlers
  */
  const handleViewTypeChange = (type) => {
    setViewType(type);
  };

  const sendTestEmail = (e) => {
    if (!matchEmailRegex(replyTo)) {
      return;
    };

    sendEmailRaw({
      to: replyTo,
      from,
      from_name: "Groundhogg Test Email",
      content: content,
      subject: subject,
    });

    setNoticeText(`Test Email Sent`)
  };

  const handleAltBodyContent = (e) => {
    setAltBodyContent(e.target.value);
  };

  const handleAltBodyEnable = (e) => {
    setAltBodyContent(e.target.value);
  };

  const handleChangeNotes = (e) => {
    setNotes(e.target.value);
  };

  const toggleSubTitle = () =>{
    setDisableTitle(disableSubTitle ? false : true )
  }

  // let editorPanel;
  // switch (editorMode) {
  //   case "text":
  //   editorPanel = (
  //     <TextEditor
  //       settings={window.Groundhogg.preloadSettings}
  //       subject={subject}
  //       handleSubjectChange={handleSubjectChange}
  //       preHeader={preHeader}
  //       handlePreHeaderChange={handlePreHeaderChange}
  //       viewType={viewType}
  //       handleUpdateBlocks={handleUpdateBlocks}
  //       blocks={blocks}
  //     />
  //   );
  //
  //     break;
  //   default:
  //     editorPanel = (
  //
  //     );
  // }

  let steps = <div/>
  if(editorType === 'funnel'){
    steps = <div className={classes.contentSideBar}>
      <EditorSteps/>
    </div>
  }
  return (
    <>
      <img src={require('./webpack-test.jpg').default}/>
      <div className="Groundhogg-BlockEditor">
        {steps}
        <SimpleModal open={open}/>

        <FullscreenMode isActive={false} />
        <Notices text={noticeText}/>
        <SlotFillProvider>
          <DropZoneProvider>
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


              <div className={classes.content} onDrop={handleDrop}>
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
              </div>

              <Sidebar handleDragStart={handleDragStart} handleDragEnd={handleDragEnd} sendTestEmail={sendTestEmail} handleViewTypeChange={handleViewTypeChange} handleSetFrom={handleSetFrom} handleSetReplyTo={handleSetReplyTo}  messageType={messageType} handleMessageType={handleMessageType} emailAlignment={emailAlignment} handleEmailAlignmentChange={handleEmailAlignmentChange} notes={notes} handleChangeNotes={handleChangeNotes}/>

              <ComplementaryArea.Slot scope="gh/v4/core" />


          </DropZoneProvider>
        </SlotFillProvider>


      </div>
    </>
  );
};
