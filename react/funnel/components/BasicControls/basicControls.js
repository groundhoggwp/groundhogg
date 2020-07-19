import React, {useEffect, useState} from "react";
import AsyncCreatableSelect from "react-select/async-creatable";
import AsyncSelect from "react-select/async";
import CreatableSelect from "react-select/creatable";
import Select from "react-select";
import axios from "axios";
import {Button} from "react-bootstrap";
import {Dashicon} from "../Dashicon/Dashicon";
import ButtonGroup from "react-bootstrap/ButtonGroup";
import {ReplacementsButton} from "../ReplacementsButton/ReplacementsButton";

import "./component.scss";

function Text({id, options, update, value}) {
    return <input id={id} className={"form-control"}
                  onChange={event => update(id, event.target.value)}
                  value={value} {...options}/>;
}

export function TagPicker({id, options, update, value}) {

    const promiseOptions = inputValue => new Promise(resolve => {
        axios.get(groundhogg_endpoints.tags + "?axios=1&q=" + inputValue).then(result => {
            resolve(result.data.tags);
        });
    });

    return (
        <AsyncCreatableSelect
            id={id}
            cacheOptions
            defaultOptions
            isMulti
            isClearable
            ignoreCase={true}
            loadOptions={promiseOptions}
            onChange={update}
            value={value}
            {...options}
        />
    );
}

function validateEmail(email) {
    const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

/**
 * Email Address picker
 *
 * @param id
 * @param options
 * @param update
 * @param value
 * @returns {*}
 * @constructor
 */
function EmailAddressPicker({id, options, update, value}) {

    const [inputValue, setInputValue] = useState("");

    const values = value && value.split(",").map(function (item) {
        return {value: item, label: item};
    });

    const addedEmail = values => {
        update(id, values && values.map(value => value.value).join(","));
    };

    const handleInputChange = (inputValue) => {
        setInputValue(inputValue);
    };

    const handleKeyDown = (event) => {
        if (!inputValue) {
            return;
        }
        switch (event.key) {
            case "Enter":
            case "Tab":

                if (validateEmail(inputValue)) {
                    setInputValue("");
                    addedEmail([
                        ...values, {
                            label: inputValue,
                            value: inputValue
                        }
                    ]);
                }
        }
    };

    const components = {
        DropdownIndicator: null
    };

    return (
        <CreatableSelect
            components={components}
            isClearable
            isMulti
            menuIsOpen={false}
            onChange={addedEmail}
            onKeyDown={handleKeyDown}
            onInputChange={handleInputChange}
            placeholder={"Type an email address..."}
            inputValue={inputValue}
            value={values}
        />
    );
}

function EmailAddress({id, options, update, value}) {

    const [valid, setValid] = useState(true);
    const [useDefault, setUseDefault] = useState(true);

    const handleOnChange = e => {
        setUseDefault(false);

        if (validateEmail(e.target.value)) {
            setValid(true);
        } else {
            setValid(false);
        }

        update(id, e.target.value);
    };

    const classes = [
        "email-address",
        valid ? "valid" : "invalid"
    ];

    value = !value && useDefault ? options.default : value;

    return (
        <input
            id={id}
            type={"email"}
            onChange={handleOnChange}
            className={classes.join(" ")}
            value={value}
        />
    );

}

/**
 * Pick roles!
 *
 * @param id
 * @param options
 * @param update
 * @param value
 * @returns {*}
 * @constructor
 */
export function RolesPicker({id, options, update, value}) {
    return (
        <Select
            id={id}
			ignoreCase
			isClearable
            isMulti
			onChange={update}
			value={value}
			options={ghEditor.roles}
			{...options}
        />
    );
}

export function EmailPicker({id, options, update, value}) {

    const promiseOptions = inputValue => new Promise(resolve => {
        axios.get(
            groundhogg_endpoints.emails + "?selectReact=1&q=" + inputValue).then(result => {
            resolve(result.data.emails);
        });
    });

    return (
        <>
            <AsyncSelect
                id={id}
                cacheOptions
                defaultOptions
                isClearable
                ignoreCase={true}
                loadOptions={promiseOptions}
                onChange={update}
                value={value}
                {...options}
            />
            <div className={"btn-control-group"}>
                <Button variant="outline-primary"><Dashicon
                    icon={"edit"}/> {"Edit Email"}</Button>
                <Button variant="outline-secondary"><Dashicon
                    icon={"plus"}/> {"Create New Email"}</Button>
            </div>
        </>
    );

}

export function YesNoToggle({id, options, update, value}) {

    return (
        <div className={"yes-no-toggle"}>
            <ButtonGroup>
                <Button
                    onClick={e => update(true)}
                    variant={value ? "primary" : "outline-primary"}
                >
                    {options.yes || "Yes"}
                </Button>
                <Button
                    onClick={e => update(false)}
                    variant={!value ? "secondary" : "outline-secondary"}
                >
                    {options.no || "No"}
                </Button>
            </ButtonGroup>
        </div>

    );
}

YesNoToggle.defaultProps = {
    options: {},
    id: "",
    update: function () {
    },
    value: false
};

/**
 * Number
 *
 * @param props
 * @constructor
 */
function Number(props) {

}

function ClearFix() {
    return <div className={"wp-clearfix"}></div>;
}

/**
 * Textarea
 */
export function TextArea({id, hasReplacements, update, value, options}) {

    const handeReplacementInsert = newValue => {
        update(newValue);
    };

    return (
        <div className={"textarea-control-wrap"}>
            {hasReplacements &&
            <div className={"replacements-wrap"}>
                <ReplacementsButton
                    insertTargetId={id}
                    onInsert={handeReplacementInsert}
                />
                <ClearFix/>
            </div>}
            <textarea
                id={id}
                onChange={e => update(e.target.value)}
                value={value}
                {...options}
            />
        </div>

    );
}

TextArea.defaultProps = {
    id: '',
    hasReplacements: false,
    update: function (v) {},
    value: '',
    options: {},
};

/**
 * Creates a pretty list based on the select results...
 *
 * @param items
 * @returns {*}
 * @constructor
 */
export function ItemsCommaOrList({items}) {

    if (!items) {
        return <></>;
    }

    return (<>{items.map(
            (item, i) => <><b>{item}</b>{i < items.length - 2
                ? ","
                : i ===
                items.length - 2 ? " or" : ""} </>)}</>
    );
}

/**
 *
 * Creates a pretty list based on the select results...
 *
 * @param items
 * @returns {*}
 * @constructor
 */
export function ItemsCommaAndList({items}) {
    if (!items) {
        return <></>;
    }

    return (<>{items.map(
            (item, i) => <><b>{item}</b>{i < items.length - 2
                ? ","
                : i ===
                items.length - 2 ? " and" : ""} </>)}</>
    );
}
