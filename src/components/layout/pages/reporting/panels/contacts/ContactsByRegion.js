/**
 * NOTE : TODO
 *
 * This component works as expected but there is somethings needs to be change..
 *
 * When this component makes a GET Request to fetch the single region component.
 * Loading is set to true by the Fetch items actions defined in base object which totally renders this component from the scretch.
 * In the end breaks the whole component.
 *
 * solution : Disable setIsRequestingItems functions inside fetchItems in data/base-object/actions.js
 */



import {DonutChart} from "components/layout/pages/reporting/charts/donut-chart";
import {LoadingReport} from "../../charts/loading-report";
import React from "react";
import {useEffect, useState} from "@wordpress/element";
import {useDispatch} from "@wordpress/data";
import {REPORTS_STORE_NAME} from "../../../../../../data";


export const SelectRegion = ({data, selectionChange, selected}) => {
    if (!data || !data.hasOwnProperty("chart")) {
        return <div/>;
    }

    if (!data.chart) {
        console.log(data.chart);
        return <div/>;
    }

    return (
        <select onChange={selectionChange} value={selected}>
            {Object.entries(data.chart).map((obj, i) => {
                return (
                    <option key={i} value={obj[0]}>
                        {" "}
                        {obj[1]}
                    </option>
                );
            })}
        </select>
    );
};

export const ContactsByRegion = (props) => {
    const {
        id,
        className,
        title,
        icon,
        loading,
        dropdown,
        dropdown_title,
        startDate,
        endDate,
        data,
        ...rest
    } = props;



    const [newData, setNewData] = useState();
    const [selected, setSelected] = useState();

    const {fetchItems} = useDispatch(REPORTS_STORE_NAME);
    

    if (loading || !data || !data.hasOwnProperty("chart")) {
        return <LoadingReport className={className} title={title}/>;
    }
    const selectionChange = (e) => {


        setNewData({});
        fetchItems({
            // reports: [],
            reports: [id],
            start: startDate,
            end: endDate,
            context: {
                [dropdown_title]: e.target.value
            }
        }).then((results) => {
            // console.log(results.items[id])
            setNewData(results.items[id]);
            // console.log("HERER AFTER DATA ");
            // console.log(data)

        });
    };

    // console.log(data);
    console.log(newData)

    newData ? console.log("newData") : console.log("data");
    return (
        <div>
            <DonutChart
                title={"Contacts By Region"}
                id={"chart_contacts_by_region"}
                data={newData?newData :data}
                loading={loading}
                dropdown={
                    <SelectRegion
                        data={dropdown}
                        selectionChange={selectionChange}
                        selected={selected}
                    />
                }
                dropdown_title="ddl_region"
            />
        </div>
    );
};
