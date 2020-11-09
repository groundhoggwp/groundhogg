/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { makeStyles } from '@material-ui/core/styles';
import AppBar from '@material-ui/core/AppBar';
import Tabs from '@material-ui/core/Tabs';
import Tab from '@material-ui/core/Tab';
import Typography from '@material-ui/core/Typography';
import Box from '@material-ui/core/Box';
import Card from '@material-ui/core/Card';

function TabPanel(props) {
  const { children, value, index, ...other } = props;

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`scrollable-auto-tabpanel-${index}`}
      aria-labelledby={`scrollable-auto-tab-${index}`}
      {...other}
    >
      {value === index && (
        <Box p={3}>
          <Typography>{children}</Typography>
        </Box>
      )}
    </div>
  );
}

TabPanel.propTypes = {
  children: PropTypes.node,
  index: PropTypes.any.isRequired,
  value: PropTypes.any.isRequired,
};

function a11yProps(index) {
  return {
    id: `scrollable-auto-tab-${index}`,
    'aria-controls': `scrollable-auto-tabpanel-${index}`,
  };
}

const useStyles = makeStyles((theme) => ({
  root: {
    flexGrow: 1,
    width: '100%',
    backgroundColor: theme.palette.background.paper,
  },
  kpiTitle: {
    fontSize: '24px',
    fontStyle: 'bold'
  },
  kpiMetric: {
    fontSize: '16px'
  }
}));

export default function ScrollableTabsButtonAuto( { tabs, history, selectedPanel, handlePanelChange} ) {
  const classes = useStyles();
  const value = selectedPanel;

  return (
    <div className={classes.root}>
      <AppBar position="static" color="default">
        <Tabs
          value={value}
          onChange={handlePanelChange}
          indicatorColor="primary"
          textColor="primary"
          variant="scrollable"
          scrollButtons="auto"
          aria-label="scrollable auto tabs example"
        >
          {
            tabs.map( ( tab, index ) => (
               <Tab key={index} label={tab.label} {...a11yProps( { index } )} />
              )
            )
          }
        </Tabs>
      </AppBar>
        {
          tabs.map( ( tab, index ) => (
            <TabPanel value={value} index={index} key={index}>
              <tab.component classes={classes} />
            </TabPanel>
           )
          )
        }
    </div>
  );
}
