import {useCallback, useEffect, useState} from "@wordpress/element";
import {Virtuoso} from "react-virtuoso";
import React from "react";

export const ListView = ({items, fetchItems, DisplayRecord, defaultOrder, defaultOrderBy}) => {

    const [data, setData] = useState(() => [])
    const [loading, setLoading] = useState(false)

    const [perPage, setPerPage] = useState(1)
    const [page, setPage] = useState(0)
    const [order, setOrder] = useState(defaultOrder)
    const [orderBy, setOrderBy] = useState(defaultOrderBy)

    const __fetchItems = () => {

        return fetchItems({
            limit: perPage,
            offset: perPage * page,
            orderBy: orderBy,
            order: order,
        })
    }


    const loadMore = useCallback(() => {

        setLoading(true)


        __fetchItems()
        setData((data) => ([...data, ...items]))


        setLoading(() => false)

    }, [setData, setLoading])

    useEffect(() => {
        // const timeout = loadMore()
        // return () => clearTimeout(timeout)
        __fetchItems()
    }, [
        perPage,
        page,
        order,
        orderBy,])


    return (
        <Virtuoso
            style={{height: 300}}
            data={data}
            // itemContent={(index, data) => {
            //     // return <DisplayRecord index={index} data={data}/>
            //
            //     return <DisplayRecord index = {index} data ={data} />
            // }}
            itemContent={(index, data) => {
                return (<div> ----------- {data.data.content}</div>)
            }}
            components={{
                Footer: () => {
                    return (
                        <div
                            style={{
                                padding: '2rem',
                                display: 'flex',
                                justifyContent: 'center',
                            }}
                        >
                            <button disabled={loading} onClick={loadMore}>
                                {loading ? 'Loading...' : 'Press to load more'}
                            </button>
                        </div>
                    )
                }
            }}
        />
    )

}