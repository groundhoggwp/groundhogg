import React from "react";
import {Dropdown} from "react-bootstrap";
import SplitButton from "react-bootstrap/SplitButton";
import {FadeIn} from "../../Animations/Animations";
import {Dashicon} from "../../Dashicon/Dashicon";

import "./component.scss";
import Button from "react-bootstrap/Button";

export const StepControls = (props) => {
    return (
        <div className={"step-controls"}>
            <FadeIn>
                <SplitButton
                    id={"step-controls"}
                    variant={"secondary"}
                    title={
                        <span>
                            <Dashicon icon={"admin-generic"}/>
                            { ' Edit' }
                        </span>
                    }
                    size={"sm"}
                    onSelect={props.handleSelect}
                    onClick={props.handleClick}
                >
                    <Dropdown.Item eventKey="edit"><Dashicon
                        icon={"edit"}/> {"Edit"}</Dropdown.Item>
                    <Dropdown.Item eventKey="duplicate"><Dashicon
                        icon={"admin-page"}/> {"Duplicate"}</Dropdown.Item>
                    <Dropdown.Divider/>
                    <Dropdown.Item eventKey="delete" className={"text-danger"}>
                        <Dashicon icon={"trash"}/> {"Delete"}
                    </Dropdown.Item>
                    <Dropdown.Divider/>
                    <Dropdown.Item eventKey="add_action"><Dashicon
                        icon={"plus"}/> {"Insert Action Below"}
                    </Dropdown.Item>
                    <Dropdown.Item eventKey="add_benchmark"><Dashicon
                        icon={"flag"}/> {"Insert Benchmark Below"}
                    </Dropdown.Item>
                </SplitButton>
                <span className={"sortable-handle"}>
					<Dashicon icon={"menu-alt2"}/>
				</span>
            </FadeIn>
        </div>
    );
};