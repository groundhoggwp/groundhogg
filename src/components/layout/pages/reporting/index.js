import "./charts/";
import "./panels/";

import { __ } from "@wordpress/i18n";
import { getReportPanel, getReportPanels } from "data/reports-registry";
import { useSelect, useDispatch } from "@wordpress/data";
import { useEffect, useState } from "@wordpress/element";
import { makeStyles } from "@material-ui/core/styles";
import { REPORTS_STORE_NAME } from "data/reports";
import { isObject } from "utils/core";
import {
  HashRouter,
  Switch,
  useLocation,
  useHistory,
  Route,
  Link,
} from "react-router-dom";
import DatePicker from "../../../core-ui/date-picker";
import { DateTime } from "luxon";
import { getLuxonDate } from "utils/index";
import TabPanel from "../../../core-ui/tab-panel";
import Breadcrumb from "../../../core-ui/bread-crumb";
import { addNotification } from "../../../../utils";

const useStyles = makeStyles((theme) => ({
  datePickers: {
    float: "right",
    display: "flex",
    right: "0px",
    width: "350px",
    justifyContent: "flex-end",
    marginTop: "-38px",
    zIndex: "5",
  },
}));

export const ReportsPage = () => {
  const panels = getReportPanels();

  return (
    <>
      <HashRouter>
        <div style={{ padding: 20 }}>
          <Switch>
            <Route path="/" children={<ReportPanel />} />
            <Route path="/:report" children={<ReportPanel />} />
            <Route path="/:report/:subReport" children={<ReportPanel />} />
          </Switch>
        </div>
      </HashRouter>
    </>
  );
};

const ReportPanel = (props) => {
  const classes = useStyles();

  const history = useHistory();
  const location = useLocation();



  const [report, setReport] = useState(location.pathname.split('/')[1]);
  const [startDate, setStartDate] = useState(getLuxonDate("one_year_back"));
  const [endDate, setEndDate] = useState(getLuxonDate("today"));
  const [reports, setReports] = useState({});

  let Panel = getReportPanel(report || "overview");
  let datePickers = <div/>
  // Once we've restored top level routing we can clean this up
  if(location.pathname.split('/')[2]){
    Panel = getReportPanel(`${report}-single` || "overview");
    datePickers = <div className={classes.datePickers}>
        <DatePicker
          dateChange={dateChange}
          selectedDate={startDate}
          label={"start"}
          id={"start"}
        />
        <DatePicker
          dateChange={dateChange}
          selectedDate={endDate}
          label={"end"}
          id={"end"}
        />
    </div>
  }

  const { fetchItems } = useDispatch(REPORTS_STORE_NAME);

  const getReports = async () => {
    fetchItems({
      reports: Panel.reports,
      start: startDate,
      end: endDate,
    }).then((results) => {
      if(results.hasOwnProperty( 'items')){
        setReports(results.items);
      }
    });
  };
  useEffect(() => {
    getReports();
  }, [report]);

  const { isRequesting } = useSelect((select) => {
    const store = select(REPORTS_STORE_NAME);
    return {
      isRequesting: store.isItemsRequesting(),
    };
  }, []);



  const panel = (
    <>
      <Breadcrumb path={["Reporting", Panel.name]} />
      {datePickers}
      <Panel.layout
        isLoading={isRequesting || !isObject(reports)}
        reports={isObject(reports) ? reports : {}}
      />
    </>
  );
  const tabs = [
    {
      label: __("Overview"),
      route: __("overview"),
      component: (classes) => {
        return <div>{panel}</div>;
      },
    },
    {
      label: __("Contacts"),
      route: __("contacts"),
      component: (classes) => {
        return <div>{panel}</div>;
      },
    },
    {
      label: __("Emails"),
      route: __("emails"),
      component: (classes) => {
        return <div>{panel}</div>;
      },
    },
    {
      label: __("Funnels"),
      route: __("funnels"),
      component: (classes) => {
        return <div>{panel}</div>;
      },
    },
    {
      label: __("Broadcasts"),
      route: __("broadcasts"),
      component: (classes) => {
        return <div>{panel}</div>;
      },
    },
    {
      label: __("Forms"),
      route: __("forms"),
      component: (classes) => {
        return <div>{panel}</div>;
      },
    },
    {
      label: __("Pipeline"),
      route: __("pipeline"),
      component: (classes) => {
        return <div>{panel}</div>;
      },
    },
  ];

  const dateChange = (id, newValue) => {
    if (id === "start") {
      setStartDate(newValue);
    } else {
      setEndDate(newValue);
    }
  };

  const tabsHandleChange = (value) => {
    let newCurrentTab = 0;
    tabs.forEach((tab, i) => {
      if (tab.route === value) {
        newCurrentTab = i;
        history.push(tab.route);
      }
    });

    setReport(value);
  };

  return (
    <>
      <TabPanel
        tabs={tabs}
        tabRoute={report}
        handleChangeHook={tabsHandleChange}
      />
    </>
  );
};
