import React from 'react'

import DatePicker from "../../report/component/DatePicker/DatePicker";
import NavBar from "../../report/component/NavBar/NavBar";
import {Col, Row} from "react-bootstrap";
import './style.scss';

export default {
    path: '/report',
    icon: 'line-chart',
    title: 'Reports',
    capabilities: [],
    exact: true,
    render: () => <div className={"groundhogg-report-page"}>
        {/*<Col>*/}
        {/*    <Row>*/}
                <DatePicker />
        {/*    </Row>*/}
        {/*    <Row>*/}
                <NavBar/>
        {/*    </Row>*/}
        {/*</Col>*/}
    </div>,
}