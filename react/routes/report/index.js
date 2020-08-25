import React from 'react'

import DatePicker from "../../report/component/DatePicker/DatePicker";
import NavBar from "../../report/component/NavBar/NavBar";
import './style.scss';

export default {
  path: '/report',
  icon: 'line-chart',
  title: 'Reports',
  capabilities: [],
  exact: true,
  render: () => <div className={"groundhogg-report-page"}>
    <DatePicker />
    <NavBar />

  </div>,
}