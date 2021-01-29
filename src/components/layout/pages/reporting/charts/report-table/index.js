
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

  return (
    <Card className={clsx(classes.root, className)} {...rest}>
      <CardHeader title="Latest Orders" />
      <Divider />
      <PerfectScrollbar>
        <Box minWidth={700}>
          <Table>
            <TableHead>
              <TableRow>
                {data.chart.label.map((label) => {
                  return <TableCell>{label}</TableCell>;
                })}
              </TableRow>
            </TableHead>
            <TableBody>
              {data.chart.data.map((data, i) => {
                let row = { id: i };
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
