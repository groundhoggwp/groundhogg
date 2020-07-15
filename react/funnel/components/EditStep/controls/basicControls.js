import React, {useEffect, useState} from "react";
import AsyncCreatableSelect from "react-select/async-creatable";
import axios from "axios";
import {Button} from "react-bootstrap";
import {Dashicon} from "../../Dashicon/Dashicon";

export const basicControls = {
    text: Text,
    textarea: TextArea,
    number: Number,
    tag_picker: TagPicker,
    email_picker: EmailPicker
};

function Text({id, options, update, value}) {
    return <input id={id} className={"form-control"}
                  onChange={event => update(id, event.target.value)}
                  value={value} {...options}/>;
}

function TagPicker({id, options, update, value}) {

    const [tagValues, setTagValues] = useState([]);
    const [loaded, setLoaded] = useState(false);

    const promiseOptions = inputValue => new Promise(resolve => {
        axios.get(groundhogg_endpoints.tags + "?axios=1&q=" + inputValue).then(result => {
            !loaded && setTagValues(
                result.data.tags.filter(tag => value.includes(parseInt(tag.value))));
            setLoaded(true);
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
            onChange={value => {
                update(id, value.map(tag => tag.value));
                setTagValues(value);
            }}
            value={tagValues}
            {...options}
        />
    );

}

function EmailPicker({id, options, update, value}) {

    const [emailValue, setEmailValue] = useState({});
    const [loaded, setLoaded] = useState(false);

    const promiseOptions = inputValue => new Promise(resolve => {
        axios.get(groundhogg_endpoints.emails + "?selectReact=1&q=" + inputValue).then(result => {
            !loaded && setEmailValue(
                result.data.emails.find(email => parseInt(value) === parseInt(email.value)));
            setLoaded(true);
            resolve(result.data.emails);
        });
    });

    return (
        <>
            <AsyncCreatableSelect
                id={id}
                cacheOptions
                defaultOptions
                isClearable
                ignoreCase={true}
                loadOptions={promiseOptions}
                onChange={value => {
                    update(id, value ? value.value : false, true );
                    setEmailValue(value);
                }}
                value={emailValue}
                {...options}
            />
            <div className={'btn-control-group'}>
                <Button variant="outline-primary"><Dashicon icon={'edit'}/> { 'Edit Email' }</Button>
                <Button variant="outline-secondary"><Dashicon icon={'plus'}/> { 'Create New Email' }</Button>
            </div>
        </>
    );

}

function Number(props) {

}

function TextArea(props) {

}

