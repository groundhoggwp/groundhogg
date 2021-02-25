import "./index.scss";
import "./components/blocks";
import { setDefaultBlockName } from "@wordpress/blocks";

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
  Popover,
  SlotFillProvider,
  DropZoneProvider,
  FocusReturnProvider,
  Panel,
  PanelBody,
  PanelRow
} from "@wordpress/components";
import { useEffect, useState } from "@wordpress/element";
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
} from "@wordpress/blocks";

/**
 * External dependencies
 */
 import { makeStyles } from "@material-ui/core/styles";
import {
  InterfaceSkeleton,
  FullscreenMode,
  ComplementaryArea,
} from "@wordpress/interface";
import interact from "interactjs";
import { DateTime } from "luxon";

/**
 * Internal dependencies
 */
import Notices from "./components/notices";
import Header from "./components/header";
import Sidebar from "./components/sidebar";
import BlockEditor from "./components/block-editor";
import TextEditor from "./components/text-editor";
import EditorSteps from "./components/editor-steps";
import { getLuxonDate, matchEmailRegex } from "utils/index";

import { CORE_STORE_NAME, EMAILS_STORE_NAME } from "data";

let draggedBlockIndex = {};
let draggedBlock = {};
let startInteractJS = false;
export default ({ document, history }) => {
  setDefaultBlockName("groundhogg/paragraph");

  const dispatch = useDispatch(EMAILS_STORE_NAME);
  const { sendEmailById, sendEmailRaw } = useDispatch(EMAILS_STORE_NAME);
  const {
    title: defaultTitleValue,
    subject: defaultSubjectValue,
    pre_header: defaultPreHeaderValue,
    content: defaultContentValue,
    editorType,
  } = document.data;


  // Editor Contents
  const [title, setTitle] = useState(defaultTitleValue);
  const [content, setContent] = useState(defaultContentValue);
  const [blocks, updateBlocks] = useState(parse(defaultContentValue));

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

  // Unused Old
  // const [testEmail, setTestEmail] = useState([]);

  const { editorMode, isSaving, item } = useSelect(
    (select) => ({
      editorMode: select(CORE_STORE_NAME).getEditorMode(),
      isSaving: select(CORE_STORE_NAME).isItemsUpdating(),
      item: select(EMAILS_STORE_NAME).getItem(document.ID),
    }),
    []
  );

  if (!item.hasOwnProperty("ID")) {
    return null;
  }

  const handleTitleChange = (e) => {
    setTitle(e.target.value);
  };

  const handleSubjectChange = (e) => {
    setSubject(e.target.value);
  };
  const handlePreHeaderChange = (e) => {
    setPreHeader(e.target.value);
  };

  const handleContentChangeDraggedBlock = () => {
    // if(!startInteractJS){return;}
    let newBlocks = blocks;
    newBlocks.splice(draggedBlockIndex, 0, createBlock(draggedBlock.name));
    handleUpdateBlocks(newBlocks);
    startInteractJS = false;
  };

  const handleUpdateBlocks = (blocks) => {
    console.log("update", blocks);
    // console.log("update", serialize(blocks));
    updateBlocks(blocks);

    if (Array.isArray(blocks)) {
    setContent(serialize(blocks));
    }
  };

  // Delete this
  // const saveDraft = (e) => {
  //   dispatch.updateItem(document.ID, {
  //     data: {
  //       subject,
  //       title,
  //       pre_header: preHeader,
  //       status: "draft",
  //       content,
  //       last_updated: getLuxonDate("last_updated"),
  //     },
  //   });
  // };

  const updateDoc = (e) => {
    dispatch.updateItem(document.ID, {
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

  const closeEditor = (e) => {
    // Doesn't work without local routing
    // history.goBack();
  };

  const dragMoveListener = (event) => {
    const target = event.target;
    event.target.classList.add("drop-active");

    draggedBlock = JSON.parse(target.getAttribute("data-block"));
    // setDraggedBlock(JSON.parse(target.getAttribute("data-block"));

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
    let adjustedY = y + 433+55; //Offset by header and block size
    let classToApply = '';
    let saveBlock = false

    document.querySelectorAll('.wp-block').forEach((block, i)=>{
      block.classList.remove('dragged-over-top')

      if(adjustedY >= block.getBoundingClientRect().top && adjustedY <= block.getBoundingClientRect().bottom ){
        draggedBlockIndex = i
        saveBlock = block
        // if(block.getBoundingClientRect().bottom - block.getBoundingClientRect().top &&)
      }

      // if(draggedBlock === 0){
      //   console.log(block, y, block.getBoundingClientRect().bottom)
      // }
    })


    if(draggedBlockIndex === 0){
      console.log(event.target)
      return;
    }
    document.querySelectorAll('.wp-block')[draggedBlockIndex].classList.add('dragged-over-top');
  };

  const dragStartListener = (event) => {
    console.log('drag start', event)
    document.querySelector('.interface-interface-skeleton__sidebar').scrollTop = 0;
    document.querySelector('.interface-interface-skeleton__sidebar').classList.add("show-overflow");
  };
  const dragEndListener = (event) => {
    document.querySelector('.interface-interface-skeleton__sidebar').classList.remove("show-overflow");
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

  const setupInteractJS = async () => {
    interact(".groundhogg-email-editor__email-content").dropzone({
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

    interact(".side-bar-drag-drop-block").draggable({
    // interact(".side-bar-drag-drop-block, .wp-block").draggable({
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
  };

  const handleViewTypeChange = (type) => {
    console.log(type)
    setViewType(type);
  };


  const sendTestEmail = (e) => {
    if (!matchEmailRegex(testEmail)) {
      return;
    }
    console.log("valid let send", testEmail);
    sendEmailRaw({
      to: replyTo,
      from,
      from_name: "TEST D",
      content: content,
      subject: subject,
    });
  };
  // const handleTestEmailChange = (e) => {
  //   setTestEmail(e.target.value);
  // };
  const handleAltBodyContent = (e) => {
    console.log('alt body content', altBodyContent)
    setAltBodyContent(e.target.value);
  };
  const handleAltBodyEnable = (e) => {
    console.log('alt body enable',   altBodyEnable)
    setAltBodyContent(e.target.value);
  };

  useEffect(() => {
    if (content) {
      handleUpdateBlocks(() => parse(content));
    }

    console.log('use effect')
    setupInteractJS();
  }, []);


  const useStyles = makeStyles((theme) => ({
    root: {

    },
    contentMain:{

    },
    contentSideBar:{

    }
  }));

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

  console.log('rebuild', blocks)

  return (
    <>
      <div className="Groundhogg-BlockEditor">
        {steps}
        <FullscreenMode isActive={false} />
        <SlotFillProvider>
          <DropZoneProvider>
            <FocusReturnProvider>
              <Header
                document={document}
                history={history}
                updateDoc={updateDoc}
                closeEditor={closeEditor}
                isSaving={isSaving}
                title={title}
                handleTitleChange={handleTitleChange}
                editorType={editorType}
              />


              <div className={classes.content}>
                  {/*Notices probably needs to be re-wrote*/}
                  <Notices />
                  {editorPanel}
              </div>

              <Sidebar isInspecting={isInspecting} sendTestEmail={sendTestEmail} handleViewTypeChange={handleViewTypeChange} />

              <ComplementaryArea.Slot scope="gh/v4/core" />

            </FocusReturnProvider>
          </DropZoneProvider>
        </SlotFillProvider>

        <div className={classes.contentFooter}>
          <Panel header={__("Blocks")} style={{marginTop:'500px'}}>
            {/*icon={ more }*/}]
            <PanelBody title="My Block Settings"  initialOpen={ true } style={{backgroundColor:'#ccc'}}>
                <PanelRow>My Panel Inputs and Labels</PanelRow>
            </PanelBody>
          </Panel>
        </div>
      </div>
    </>
  );
};
