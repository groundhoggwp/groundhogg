import clsx from "clsx";
import PerfectScrollbar from "react-perfect-scrollbar";
import PropTypes from "prop-types";
import {
  Box,
  Button,
  Card,
  CardHeader,
  Divider,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  TableSortLabel,
  Tooltip,
  makeStyles,
} from "@material-ui/core";
import _ from "lodash";
import React from "react";
import Typography from "@material-ui/core/Typography";
// import NavigateNextIcon from "@material-ui/icons/NavigateNext";
// import Label from "src/components/Label";

const labelColors = {
  complete: "success",
  pending: "warning",
  rejected: "error",
};

const useStyles = makeStyles(() => ({
  root: {},
}));

export const ReportTable = ({ className, title, data, loading, ...rest }) => {
  const classes = useStyles();

  // const columns = Object.keys(data.chart.data[0]).map((label, i) => {
  //   // The server data model isn't ideal for this component, sometimes labels are valid and exist
  //   // Sometimes they need to be inferred from row data.
  //   if (tableLabels[i]) {
  //     label = tableLabels[i];
  //   }
  //
  //   return {
  //     field: tableFields[i],
  //     headerName: _.capitalize(label),
  //     headerAlign: "center",
  //     sortable: true,
  //   };
  // });
  //
  // console.log(columns);

  if (loading || !data.chart) {
    return <div />;
  }

  const tableLabels = data.chart.label;
  const tableData = data.chart.data.map((row) => {
    Object.keys(row).map((key) => {
      if (typeof row[key] === "object") {
        delete row[key];
      }
    });
    return row;
  });
  const tableFields = Object.keys(tableData[0]);

  // Manual width calc is required because each container is dynamic and data grid can't use % then
  // let columnWidth = (((window.innerWidth-300)/2)/(tableFields.length));
  // if(fullWidth){
  //   columnWidth = ((window.innerWidth)/(tableFields.length*2));
  // }

  // console.log(tableFields.length, window.innerWidth, columnWidth)

  const columns = Object.keys(data.chart.data[0]).map((label, i) => {
    // The server data model isn't ideal for this component, sometimes labels are valid and exist
    // Sometimes they need to be inferred from row data.
    if (tableLabels[i]) {
      label = tableLabels[i];
    }

    return {
      field: tableFields[i],
      headerName: _.capitalize(label),
      // width: columnWidth,
      // headerAlign:'center',
      // sortable: true
    };
  });

  // Remove funnels and other garbage from here
  const rows = data.chart.data.map((data, i) => {
    // let row = { id: i};
    let row = {};
    Object.keys(data).forEach((field, ii) => {
      row[columns[ii]["field"]] = data[field];
    });
    return row;
  });

  return (
    <Card className={clsx(classes.root, className)} {...rest}>
      <CardHeader title="Latest Orders" />
      <Divider />
      <PerfectScrollbar>
        <Box minWidth={700}>
          <Table>
            {/*{data.chart.hasOwnProperty('label') ? (*/}
            {/*  <TableHead>*/}
            {/*    <TableRow>*/}
            {/*      {data.chart.label.map((label) => {*/}
            {/*        return (*/}
            {/*          <TableCell>*/}
            {/*            {" "}*/}
            {/*            <b>{label}</b>*/}
            {/*          </TableCell>*/}
            {/*        );*/}
            {/*      })}*/}
            {/*    </TableRow>*/}
            {/*  </TableHead>*/}
            {/*) : (*/}
            {/*  " "*/}
            {/*)} */}
            {columns ? (
              <TableHead>
                <TableRow>
                  {columns.map((label) => {
                    return (
                      <TableCell>
                        <b>{label.headerName}</b>
                      </TableCell>
                    );
                  })}
                </TableRow>
              </TableHead>
            ) : (
              " "
            )}
            <TableBody>
              {rows.map((data, i) => {
                return (
                  <TableRow key={i}>
                    {Object.keys(data).map((field, ii) => {
                      {
                        return <TableCell key={ii}>{data[field]}</TableCell>;
                      }
                    })}
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </Box>
      </PerfectScrollbar>
    </Card>
  );
};

ReportTable.propTypes = {
  className: PropTypes.string,
};

export default ReportTable;
