import React from 'react'

// import DatePicker from "../../report/conponent/DatePicker/DatePicker";
import NavBar from "../../report/component/NavBar/NavBar";
import './style.scss';

export default {
  path: '/report',
  icon: 'line-chart',
  title: 'Reports',
  capabilities: [],
  exact: true,
  render: () => <div>
    {/*<DatePicker className={"olwytik-datepicker"}/>*/}
    <NavBar />

  </div>,
}