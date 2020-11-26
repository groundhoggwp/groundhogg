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
import { serialize, parse, pasteHandler, rawHandler } from "@wordpress/blocks";

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
import { getLuxonDate } from "utils/index";
import { CORE_STORE_NAME, EMAILS_STORE_NAME } from "data";

export default ({ settings, email, history }) => {
  const dispatch = useDispatch(EMAILS_STORE_NAME);

  const {
    title: defaultTitleValue,
    subject: defaultSubjectValue,
    pre_header: defaultPreHeaderValue,
    content: defaultContentValue,
  } = email.data;

  const [title, setTitle] = useState(defaultTitleValue);
  const [subject, setSubject] = useState(defaultSubjectValue);
  const [preHeader, setPreHeader] = useState(defaultPreHeaderValue);
  const [content, setContent] = useState(defaultContentValue);

  const { editorMode, isSaving, item } = useSelect(
    (select) => ({
      editorMode: select(CORE_STORE_NAME).getEditorMode(),
      isSaving: select(CORE_STORE_NAME).isItemsUpdating(),
      item: select(EMAILS_STORE_NAME).getItem(email.ID),
    }),
    []
  );

  // console.log(email.data)
  //email data shape
  // {
  // 	ID,
  // 	content,
  // 	subject,
  // 	pre_header,
  // 	from_user,
  // 	author,
  // 	last_updated,
  // 	date_created,
  // 	status,
  // 	is_template,
  // 	title
  // }

  const handleTitleChange = (e) => {
    setTitle(e.target.value);
  };
  if (!item.hasOwnProperty("ID")) {
    return null;
  }

  const handleSubjectChange = (e) => {
    setSubject(e.target.value);
  };
  const handlePreHeaderChange = (e) => {
    setPreHeader(e.target.value);
  };
  const handleContentChange = (blocks) => {
    setContent(serialize(blocks));
  };
  const handleBlockResize = (width, height) => {
    let modifiedBlocks = parse(content)
    console.log(modifiedBlocks[2])
    modifiedBlocks[2].attributes.width = width;
    modifiedBlocks[2].attributes.height= height;
    console.log(modifiedBlocks[2])
    setContent(serialize(modifiedBlocks));
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

  useEffect(() => {
    const dragMoveListener = (event) => {

      let target = event.target;

      // keep the dragged position in the data-x/data-y attributes
      let x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
      let y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

      // translate the element
      target.style.webkitTransform = target.style.transform =
        "translate(" + x + "px, " + y + "px)";

      // update the posiion attributes
      target.setAttribute("data-x", x);
      target.setAttribute("data-y", y);
    };

    interact(".dropzone").dropzone({
      // only accept elements matching this CSS selector
      accept: "#block-editor-droppable-area",
      // Require a 75% element overlap for a drop to be possible
      overlap: 0.75,

      // listen for drop related events:

      ondropactivate: (event) => {
        // add active dropzone feedback
        event.target.classList.add("drop-active");
      },
      ondragenter: function (event) {
        var draggableElement = event.relatedTarget;
        var dropzoneElement = event.target;

        // feedback the possibility of a drop
        dropzoneElement.classList.add("drop-target");
        draggableElement.classList.add("can-drop");
        draggableElement.textContent = "Dragged in";
      },
      ondragleave: (event) => {
        // remove the drop feedback style
        event.target.classList.remove("drop-target");
        event.relatedTarget.classList.remove("can-drop");
        event.relatedTarget.textContent = "Dragged out";
      },
      ondrop: function (event) {
        event.relatedTarget.textContent = "Dropped";
      },
      ondropdeactivate: function (event) {
        // remove active dropzone feedback
        event.target.classList.remove("drop-active");
        event.target.classList.remove("drop-target");
      },
    });

    var x = 0;
    var y = 0;

    // interact(".wp-block, .drag-drop")
      interact('.drag-drop')
      .resizable({
        // resize from all edges and corners
        edges: { left: true, right: true, bottom: true, top: true },

        listeners: {
          move(event) {
            var target = event.target;
            var x = parseFloat(target.getAttribute("data-x")) || 0;
            var y = parseFloat(target.getAttribute("data-y")) || 0;


            // update the element's style
            target.style.width = event.rect.width + "px";
            target.style.height = event.rect.height + "px";

            // translate when resizing from top or left edges
            x += event.deltaRect.left;
            y += event.deltaRect.top;

            target.style.webkitTransform = target.style.transform =
              "translate(" + x + "px," + y + "px)";

            target.setAttribute("data-x", x);
            target.setAttribute("data-y", y);
            // target.textContent =
            //   Math.round(event.rect.width) +
            //   "\u00D7" +
            //   Math.round(event.rect.height);
            console.log(target)
            console.log(target.children[0])
            handleBlockResize(target.style.width , target.style.height)

          },
        },
        modifiers: [
          // keep the edges inside the parent
          interact.modifiers.restrictEdges({
            outer: "parent",
          }),

          // minimum size
          interact.modifiers.restrictSize({
            min: { width: 100, height: 50 },
          }),
        ],

        inertia: true,
      })
      .draggable({
        inertia: true,
        modifiers: [
          interact.modifiers.snap({
            targets: [interact.createSnapGrid({ x: 30, y: 30 })],
            range: Infinity,
            relativePoints: [{ x: 0, y: 0 }],
          }),
          interact.modifiers.restrictRect({
            restriction: ".dropzone",
            // restriction: "parent",
            endOnly: true,
          }),
        ],
        autoScroll: true,
        // dragMoveListener from the dragging demo above
        listeners: { move: dragMoveListener },
      });
  });

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
          content={content}
          handleContentChange={handleContentChange}
        />
      );
      break;
    case "text":
      editorPanel = <PostTextEditor />;
      break;
    case "drag-and-drop-test":
      editorPanel = (
        <Fragment>
          <div id="block-editor-droppable-area" className="drag-drop">
            {" "}
            #yes-drop{" "}
          </div>
          <div id="inner-dropzone" className="dropzone">
            #inner-dropzone
          </div>
        </Fragment>
      );
      break;
    default:
      editorPanel = (
        <BlockEditor
          settings={settings}
          subject={subject}
          handleSubjectChange={handleSubjectChange}
          preHeader={preHeader}
          handlePreHeaderChange={handlePreHeaderChange}
          content={content}
          handleContentChange={handleContentChange}
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
                  <br />
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
