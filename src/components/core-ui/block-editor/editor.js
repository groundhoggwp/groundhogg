/**
 * WordPress dependencies
 */
import {
  Popover,
  SlotFillProvider,
  DropZoneProvider,
  FocusReturnProvider,
} from "@wordpress/components";

import {
  InterfaceSkeleton,
  FullscreenMode,
  ComplementaryArea,
} from "@wordpress/interface";

import { PostTextEditor } from "@wordpress/editor";
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
import interact from "interactjs";
import { DateTime } from "luxon";

/**
 * Internal dependencies
 */
import Notices from "./components/notices";
import Header from "./components/header";
import Sidebar from "./components/sidebar";
import BlockEditor from "./components/block-editor";
import { getLuxonDate, matchEmailRegex } from "utils/index";

import { CORE_STORE_NAME, EMAILS_STORE_NAME } from "data";

let draggedBlock = {};
let startInteractJS = false;
export default ({ settings, email, history }) => {
  const dispatch = useDispatch(EMAILS_STORE_NAME);
  const { sendEmailById, sendEmailRaw } = useDispatch(EMAILS_STORE_NAME);
  const {
    title: defaultTitleValue,
    subject: defaultSubjectValue,
    pre_header: defaultPreHeaderValue,
    content: defaultContentValue,
  } = email.data;

  const [title, setTitle] = useState(defaultTitleValue);
  // const [draggedBlock, setDraggedBlock] = useState(null);
  const [subject, setSubject] = useState(defaultSubjectValue);
  const [preHeader, setPreHeader] = useState(defaultPreHeaderValue);
  const [content, setContent] = useState(defaultContentValue);
  const [viewType, setViewType] = useState("desktop");
  const [blocks, updateBlocks] = useState(parse(defaultContentValue));

  const { editorMode, isSaving, item } = useSelect(
    (select) => ({
      editorMode: select(CORE_STORE_NAME).getEditorMode(),
      isSaving: select(CORE_STORE_NAME).isItemsUpdating(),
      item: select(EMAILS_STORE_NAME).getItem(email.ID),
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
    let newBlocks = blocks;
    newBlocks.push(createBlock(draggedBlock.name));
    handleUpdateBlocks(newBlocks);
  };

  const handleUpdateBlocks = (blocks) => {
    // console.log("update", blocks);
    updateBlocks(blocks);
    if (Array.isArray(blocks)) {
      setContent(serialize(blocks));
    }
  };

  const saveDraft = (e) => {
    dispatch.updateItem(email.ID, {
      data: {
        subject,
        title,
        pre_header: preHeader,
        status: "draft",
        content,
        last_updated: getLuxonDate("last_updated"),
      },
    });
  };

  const publishEmail = (e) => {
    dispatch.updateItem(email.ID, {
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
    history.goBack();
  };

  const setupInteractJS = async () => {
    const dragMoveListener = (event) => {
      const target = event.target;
      event.target.classList.add("drop-active");
      draggedBlock = JSON.parse(target.getAttribute("data-block"));
      // setDraggedBlock(JSON.parse(target.getAttribute("data-block"));

      // keep the dragged position in the data-x/data-y attributes
      const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
      const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

      console.log(x, y);

      // translate the element
      target.style.webkitTransform = target.style.transform =
        "translate(" + x + "px, " + y + "px)";

      // update the posiion attributes
      target.setAttribute("data-x", x);
      target.setAttribute("data-y", y);
    };
    const dragEndListener = (event) => {
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

    // if(interact.isSet(".groundhogg-email-editor__email-content")){
    //   console.log('unsett')
    //   interact(".groundhogg-email-editor__email-content").unset();
    // }
    interact(".groundhogg-email-editor__email-content").dropzone({
      overlap: 0.75,
      ondropactivate: (event) => {},
      ondragenter: (event) => {
        // var draggableElement = event.relatedTarget;
        var dropzoneElement = event.target.classList.add("active");
      },
      ondragleave: (event) => {
        var dropzoneElement = event.target.classList.remove("active");
      },
      ondrop: (event) => {
        console.log(
          "dropped",
          event.relatedTarget.offsetTop,
          event.target.offsetTop
        );
        console.log(
          "dropped",
          event.relatedTarget.offsetLeft,
          event.target.offsetLeft
        );
        var dropzoneElement = event.target.classList.remove("active");
        handleContentChangeDraggedBlock();
      },
      ondropdeactivate: (event) => {},
    });

    // if(interact.isSet(".side-bar-drag-drop-block, .wp-block")){
    //   interact(".side-bar-drag-drop-block, .wp-block").unset();
    // }

    interact(".side-bar-drag-drop-block, .wp-block").draggable({
      cursorChecker(action, interactable, element, interacting) {
        return "grab";
      },
      autoScroll: true,
      onend: dragEndListener,
      listeners: { move: dragMoveListener },
      modifiers: [
        // interact.modifiers.snap({
        //   // targets: [
        //   //   interact.snappers.grid({ x: 30, y: 30 })
        //   // ],
        //   range: Infinity,
        //   relativePoints: [ { x: 0, y: 0 } ]
        // }),
        interact.modifiers.restrict({
          restriction: interact(".groundhogg-email-editor__email-content"),
          elementRect: { top: 0, left: 0, bottom: 1, right: 1 },
          endOnly: true,
        }),
      ],
      // inertia: true
    });
  };

  const handleViewTypeChange = (type) => {
    setViewType(type);
  };

  const [testEmail, setTestEmail] = useState([]);
  const sendTestEmail = (e) => {
    if (!matchEmailRegex(testEmail)) {
      return;
    }
    console.log("valid let send", testEmail);
    sendEmailRaw({
      to: testEmail,
      from_email: "dhrumit.groundhogg@gmail.com",
      from_name: "TEST D",
      content: content,
      subject: subject,
    });
  };
  const handleTestEmailChange = (e) => {
    setTestEmail(e.target.value);
  };

  useEffect(() => {
    if (content?.length) {
      handleUpdateBlocks(() => parse(content));
    }

    console.log(blocks);
    setupInteractJS();
  }, [draggedBlock]);

  let editorPanel;
  switch (editorMode) {
    case "visual":
      editorPanel = (
        <BlockEditor
          settings={settings}
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
    case "text":
      editorPanel = <PostTextEditor />;
      break;
    default:
      editorPanel = (
        <BlockEditor
          settings={settings}
          subject={subject}
          handleSubjectChange={handleSubjectChange}
          preHeader={preHeader}
          handlePreHeaderChange={handlePreHeaderChange}
          viewType={viewType}
          handleUpdateBlocks={handleUpdateBlocks}
          blocks={blocks}
        />
      );
  }

  return (
    <div className="Groundhogg-BlockEditor">
      <FullscreenMode isActive={false} />
      <SlotFillProvider>
        <DropZoneProvider>
          <FocusReturnProvider>
            <InterfaceSkeleton
              header={
                <Header
                  email={email}
                  history={history}
                  saveDraft={saveDraft}
                  publishEmail={publishEmail}
                  closeEditor={closeEditor}
                  isSaving={isSaving}
                  handleTitleChange={handleTitleChange}
                  handleViewTypeChange={handleViewTypeChange}
                  sendTestEmail={sendTestEmail}
                  handleTestEmailChange={handleTestEmailChange}
                  title={title}
                />
              }
              sidebar={
                <>
                  <Sidebar />
                  <ComplementaryArea.Slot scope="gh/v4/core" />
                </>
              }
              content={
                <>
                  <Notices />
                  {editorPanel}
                </>
              }
            />
            <Popover.Slot />
          </FocusReturnProvider>
        </DropZoneProvider>
      </SlotFillProvider>
    </div>
  );
};
