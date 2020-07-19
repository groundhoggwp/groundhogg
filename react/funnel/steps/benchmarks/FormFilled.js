import React, {useState} from "react";
import {registerStepType, SimpleEditModal} from "../steps";
import {Col, Row, Tab, Tabs} from "react-bootstrap";
import {CopyInput, LinkPicker, TextArea, YesNoToggle} from "../../components/BasicControls/basicControls";

registerStepType("form_fill", {

    icon: ghEditor.steps.form_fill.icon,
    group: ghEditor.steps.form_fill.group,

    title: ({data, context, settings}) => {
        return <>{"When"} <b>{settings.form_name}</b> {"is filled"}</>;
    },

    edit: ({data, context, settings, updateSettings, commit, done}) => {

        const updateSetting = (name, value) => {
            updateSettings({
                [name]: value
            });
        };

        return (
            <SimpleEditModal
                title={"Form Filled..."}
                done={done}
                commit={commit}
                modalProps={{
                    size: "lg"
                }}
            >
                <Tabs defaultActiveKey="form">
                    <Tab eventKey={"form"} title={"Form"}>
                        <textarea
                            className={"form-content code"}
                            value={settings.form}
                            rows={10}
                            onChange={(e) => updateSetting("form", e.target.value)}
                            onBlur={(e) => updateSetting("form", prettifyForm(e.target.value))}
                        />
                    </Tab>
                    <Tab eventKey={"submit"} title={"Submit"}>
                        <Row className={"step-setting-control"}>
                            <Col sm={4}>
                                <label>{"Stay on page after submit?"}</label>
                                <p className={"description"}>{"This will prevent the contact from being redirected after submitting the form."}</p>
                            </Col>
                            <Col sm={8}>
                                <YesNoToggle
                                    value={settings.enable_ajax}
                                    update={(v) => updateSetting("enable_ajax", v)}
                                />
                            </Col>
                        </Row>
                        {settings.enable_ajax && <Row>
                            <Col sm={4}>
                                <label>{"Success message"}</label>
                                <p className={"description"}>{"Message displayed when the contact submits the form."}</p>
                            </Col>
                            <Col sm={8}>
                                <TextArea
                                    id={"success_message"}
                                    value={settings.success_message}
                                    hasReplacements={true}
                                    update={(v) => updateSetting("success_message", v)}/>
                            </Col>
                        </Row>}
                        {!settings.enable_ajax && <Row>
                            <Col sm={4}>
                                <label>{"Success page"}</label>
                                <p className={"description"}>{"Where the contact will be directed upon submitting the form."}</p>
                            </Col>
                            <Col sm={8}>
                                <LinkPicker value={settings.success_page}
                                            update={(v) => updateSetting("success_page", v)}/>
                            </Col>
                        </Row>}
                    </Tab>
                    <Tab eventKey={"embed"} title={"Embed"}>
                        <Row className={"step-setting-control"}>
                            <Col sm={4}>
                                <label>{"Shortcode"}</label>
                                <p className={"description"}>{"Insert anywhere WordPress shortcodes are accepted."}</p>
                            </Col>
                            <Col sm={8}>
                                <CopyInput
                                    content={context.embed.shortcode}
                                />
                            </Col>
                        </Row>
                        <Row className={"step-setting-control"}>
                            <Col sm={4}>
                                <label>{"iFrame"}</label>
                                <p className={"description"}>{"For use when embedding forms on none WordPress sites."}</p>
                            </Col>
                            <Col sm={8}>
                                <CopyInput
                                    content={context.embed.iframe}
                                />
                            </Col>
                        </Row>
                        <Row className={"step-setting-control"}>
                            <Col sm={4}>
                                <label>{"Raw HTML"}</label>
                                <p className={"description"}>{"For use when embedding forms on none WordPress sites and HTML web form integrations (Thrive)."}</p>
                            </Col>
                            <Col sm={8}>
                                <CopyInput
                                    content={context.embed.html}
                                />
                            </Col>
                        </Row>
                        <Row className={"step-setting-control"}>
                            <Col sm={4}>
                                <label>{"Hosted URL"}</label>
                                <p className={"description"}>{"Direct link to the web form."}</p>
                            </Col>
                            <Col sm={8}>
                                <CopyInput
                                    content={context.embed.hosted}
                                />
                            </Col>
                        </Row>
                    </Tab>
                </Tabs>
            </SimpleEditModal>
        );
    }
});

function prettifyForm(form) {

    form = form.trim().replace(/(\])\s*(\[)/gm, "$1$2").replace(/(\])/gm, "$1\n");
    // form = form.trim().replace(/(\])\s*(\[)/gm, "$1$2").replace(/(\])/gm, "$1\n");
    let codes = form.split("\n");

    console.debug(codes);

    let depth = 0;
    let pretty = "";

    codes.forEach(function (shortcode, i) {

        shortcode = shortcode.trim();

        if (!shortcode) {
            return;
        }

        if (shortcode.match(/\[(col|row)\b[^\]]*\]/)) {
            pretty += " ".repeat(4).repeat(depth) + shortcode;
            depth++;
        } else if (shortcode.match(/\[\/(col|row)\]/)) {
            depth--;
            pretty += " ".repeat(4).repeat(depth) + shortcode;
        } else {
            pretty += " ".repeat(4).repeat(depth) + shortcode;
        }

        pretty += "\n";
    });

    return pretty;

}

function FormBuilder({form, onChange}) {

    const fields = [
        {shortcode: "first", name: "First", attributes: ["required", "label", "placeholder", "id", "class"]},
        {shortcode: "last", name: "Last", attributes: ["required", "label", "placeholder", "id", "class"]},
        {shortcode: "email", name: "Email", attributes: ["label", "placeholder", "id", "class"]},
        {shortcode: "phone", name: "Phone", attributes: ["required", "label", "placeholder", "id", "class"]},
        {shortcode: "gdpr", name: "GDPR", attributes: ["label", "tag", "id", "class"]},
        {shortcode: "terms", name: "Terms", attributes: ["label", "tag", "id", "class"]},
        {shortcode: "recaptcha", name: "reCaptcha", attributes: ["captcha-theme", "captcha-size", "id", "class"]},
        {shortcode: "submit", name: "Submit", attributes: ["text", "id", "class"]},
        {shortcode: "text", name: "Text", attributes: ["required", "label", "placeholder", "name", "id", "class"]},
        {
            shortcode: "textarea",
            name: "Textarea",
            attributes: ["required", "label", "placeholder", "name", "id", "class"]
        },
        {shortcode: "number", name: "Number", attributes: ["required", "label", "name", "min", "max", "id", "class"]},
        {
            shortcode: "dropdown",
            name: "Dropdown",
            attributes: ["required", "label", "name", "default", "options", "multiple", "id", "class"]
        },
        {shortcode: "radio", name: "Radio", attributes: ["required", "label", "name", "options", "id", "class"]},
        {
            shortcode: "checkbox",
            name: "Checkbox",
            attributes: ["required", "label", "name", "value", "tag", "id", "class"]
        },
        {shortcode: "address", name: "Address", attributes: ["required", "label", "id", "class"]},
        {shortcode: "birthday", name: "Birthday", attributes: ["required", "label", "id", "class"]},
        {
            shortcode: "date",
            name: "Date",
            attributes: ["required", "label", "name", "min_date", "max_date", "id", "class"]
        },
        {
            shortcode: "time",
            name: "Time",
            attributes: ["required", "label", "name", "min_time", "max_time", "id", "class"]
        },
        {
            shortcode: "file",
            name: "File",
            attributes: ["required", "label", "name", "max_file_size", "file_types", "id", "class"]
        }
    ];

}

function FieldBuilder({shortcode, attributes, onBuild}) {

    const [field, setField] = useState({});

    const updateAttribute = (id, value) => {
        setField({
            [id]: value
        });
    };

    const _attributes = {
        required: ({value}) => {
            return (
                <Row className={"field-attribute-control"}>
                    <Col sm={4}>
                        <label>{"Is this field required?"}</label>
                    </Col>
                    <Col sm={8}>
                        <YesNoToggle
                            value={value}
                            update={(value) => updateAttribute("required", value)}
                        />
                    </Col>
                </Row>
            );
        }
    };

}