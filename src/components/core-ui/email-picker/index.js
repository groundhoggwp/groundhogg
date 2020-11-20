import AsyncCreatableSelect from 'react-select/async-creatable'
import AsyncSelect from 'react-select/async'
import apiFetch from "@wordpress/api-fetch";
import {NAMESPACE} from "data/constants";
import {useEffect} from "@wordpress/element";
import { parseArgs } from 'utils/core'


const FaIcon = ({classes}) => {
    return <i className={'fa ' + classes.map(c => 'fa-' + c).join(' ')}></i>
}

const EmailPicker = ({selectProps, onChange, value, isCreatable}) => {

    selectProps = parseArgs(selectProps || {}, {
        cacheOptions: true,
        isMulti: false,
        ignoreCase: true,
        isClearable: true,
        // defaultOptions: [],
    })

    const promiseOptions = inputValue  => new Promise(resolve => {
        apiFetch({
            method: 'GET',
            path: NAMESPACE + '/emails?search=' + inputValue,
            // body : {
            //     "where" :{
            //         "status" : "ready"
            //     }
            // }
        }).then(({items}) => {
            console.log(items);
            resolve(items.map((item) => {
                return ({value: item.ID, label: item.data.title + `(${item.data.status})`})
            }));
        })
    });

    // const TagSelect = isCreatable ? AsyncCreatableSelect : AsyncSelect
    const TagSelect =  AsyncSelect;
    // value = value.map(item  => {return ({value: item}) });

    return (
        // <TagSelect
        //     {...selectProps}
        //     defaultOptions={promiseOptions}
        //     loadOptions={promiseOptions}
        //     onChange={ onChange }
        //     value={ value }
        // />
        //
        <AsyncSelect
            {...selectProps}
            cacheOptions
            defaultOptions
            loadOptions={promiseOptions}
            onChange ={onChange}
            value ={value}
        />
    )
}

export default EmailPicker;
