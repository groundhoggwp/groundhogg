import React from 'react';
import {makeStyles} from '@material-ui/core/styles';
import Timeline from '@material-ui/lab/Timeline';
import TimelineItem from '@material-ui/lab/TimelineItem';
import TimelineSeparator from '@material-ui/lab/TimelineSeparator';
import TimelineConnector from '@material-ui/lab/TimelineConnector';
import TimelineContent from '@material-ui/lab/TimelineContent';
import TimelineOppositeContent from '@material-ui/lab/TimelineOppositeContent';
import TimelineDot from '@material-ui/lab/TimelineDot';
import Typography from '@material-ui/core/Typography';
import {applyFilters} from "@wordpress/hooks";
import EventIcon from '@material-ui/icons/Event';
import DraftsIcon from '@material-ui/icons/Drafts';
import {useParams} from "react-router-dom";
import {useDispatch, useSelect} from "@wordpress/data";
import {ACTIVITY_STORE_NAME} from "../../../../data";
// import {ACTIVITY_STORE_NAME} from "data/activity";
import {useCallback, useEffect, useState} from "@wordpress/element";
import Button from "@material-ui/core/Button";
import {__} from "@wordpress/i18n";
import {phpKeyTOWords} from "../../../../data/utils"
import {DateTime} from 'luxon'
import TouchAppIcon from '@material-ui/icons/TouchApp';


const useStyles = makeStyles((theme) => ({

    secondaryTail: {
        backgroundColor: theme.palette.primary.main
    },
    timeline: {
        transform: "rotate(90deg)"
    },
    timelineContentContainer: {
        textAlign: "left"
    },
    timelineContent: {
        // display: "inline-block",
        // transform: "rotate(-90deg)",
        // textAlign: "center",
        // minWidth: 50,
        // padding: '6px 16px',
        // flex :0

    },
    timelineIcon: {
        transform: "rotate(-90deg)"
    },
    opposite: {
        flex: 0,
        padding: 0,
        marginRight: 0,

        // text-align: right ,
        // margin-right: 0;
    },
    content: {
        flex: 0
    }

}));

export const ContactTimeline = () => {
    const classes = useStyles();


    let {id} = useParams()

    const {fetchItems} = useDispatch(ACTIVITY_STORE_NAME)
    const [page, setPage] = useState(0)
    const [activity, setActivity] = useState([])
    const [loading, setLoading] = useState(false)
    const [total, setTotal] = useState(0)


    const fetchActivity = async () => {

        let perPage = 10 // todo change as per requirement
        setLoading(true)

        let {items, totalItems} = await fetchItems({
            limit: perPage,
            offset: perPage * page,
            orderBy: 'ID',
            order: 'desc',
            where: {
                contact_id: id
            }
        })

        setTotal(totalItems)
        setActivity([...activity, ...items])

        setLoading(false)
        // setActivity(items )

    }


    useEffect(() => {
        fetchActivity()
    }, [])

    const loadMore = () => {
        setPage(page + 1);
        fetchActivity()
    }

    return (
        <div>
            <Timeline>
                {activity.map(({data}, index) => {
                    return (
                        <TimelineItem>
                            <TimelineOppositeContent className={classes.opposite}/>
                            <TimelineSeparator>
                                <TimelineDot color="primary">
                                    <TimelineIcon type={data.activity_type}/>
                                </TimelineDot>
                                <TimelineConnector className={classes.secondaryTail}/>
                            </TimelineSeparator>
                            <TimelineContent>
                                <Typography variant="h6" component="h1">
                                    {phpKeyTOWords(data.activity_type)}
                                </Typography>
                                <Typography variant="caption" display="block" gutterBottom>
                                    {DateTime.fromSeconds(parseInt(data.timestamp)).toISODate()}</Typography>
                                <Typography variant={"body2"}>
                                    {data.description}
                                </Typography>
                            </TimelineContent>
                        </TimelineItem>

                    )
                })}
            </Timeline>
            <Button variant="contained" color="primary" onClick={loadMore}
                    disabled={loading || (activity.length >= total)}>
                {loading ? __('Loading..', 'groundhogg') : (activity.length >= total) ? __('End of Activity', 'groundhogg') : __('Load More', 'groundhogg')}
            </Button>

        </div>
    );
}

export const TimelineIcon = ({type}) => {

    const mapping = applyFilters('groundhogg.contacts.timeline.icons', {
        'email_opened': {component: DraftsIcon},
        'email_link_click' : {component :TouchAppIcon},


    })
    if (mapping.hasOwnProperty(type)) {
        const mappedComponent = mapping[type];
        return <mappedComponent.component/>
    } else {
        return <EventIcon/>

    }
}