import AsyncCreatableSelect from 'react-select/async-creatable'
import AsyncSelect from 'react-select/async'
// import {parseArgs} from '../../layout/pages/reports/App'
import apiFetch from "@wordpress/api-fetch";
import {NAMESPACE} from "data/constants";
import {useEffect} from "@wordpress/element";


const FaIcon = ({classes}) => {
    return <i className={'fa ' + classes.map(c => 'fa-' + c).join(' ')}></i>
}

const TagPicker = ({selectProps, onChange, value, isCreatable}) => {

    selectProps = parseArgs(selectProps || {}, {
        cacheOptions: true,
        isMulti: true,
        ignoreCase: true,
        isClearable: true,
        // defaultOptions: [],
    })

    // const promiseOptions = inputValue => new Promise(resolve => {
    //     axios.get(groundhogg.rest_base + '/tags?axios=1&q=' + inputValue).
    //     then(result => {
    //         resolve(result.data.tags)
    //     })
    // })



    const promiseOptions = inputValue  => new Promise(resolve => {
        apiFetch({
            method: 'GET',
            path: NAMESPACE + '/tags?search=' + inputValue,
        }).then(({items}) => {
            console.log(items);
            resolve(items.map((item) => {
                return ({value: item.ID, label: item.data.tag_name + `(${item.data.contact_count})`})
            }));
        })
        //
        // console.log( "HERE IN PROMISE");
        // resolve ([{value: 'a', label: 'b'}]);
    });

    // const TagSelect = isCreatable ? AsyncCreatableSelect : AsyncSelect
    const TagSelect =  AsyncSelect;
    // value = value.map(item  => {return ({value: item}) });

    return (
        // <TagSelect
        //     {...selectProps}
        //     defaultOptions={promiseOptions}
        //     loadOptions={promiseOptions}
        //     // onChange={ onChange }
        //     value={ ['a'] }
        // />

        <AsyncSelect
            {...selectProps}

            cacheOptions
            defaultOptions
            loadOptions={promiseOptions}
            onChange ={onChange}
            // value ={value}
        />
    )
}

export default TagPicker;
