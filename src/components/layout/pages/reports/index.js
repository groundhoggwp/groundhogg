/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { makeStyles } from '@material-ui/core/styles';
import { __ } from '@wordpress/i18n';
import Card from '@material-ui/core/Card';
import Typography from '@material-ui/core/Typography';

/**
 * Internal dependencies
 */
 import Spinner from '../../../core-ui/spinner';
 import TabPanel from '../../../core-ui/tab-panel';
import { REPORTS_STORE_NAME } from '../../../../data/reports'
import Chart from '../../../core-ui/chart';

const useStyles = makeStyles((theme) => ({
  container: {
		marginBottom: theme.spacing(1),
		textAlign: 'center'
    // paddingTop: theme.spacing(4),
    // paddingBottom: theme.spacing(4),
  },
}));

export function Reports (props) {
  const classes = useStyles();
  const storeName = 'gh/v4/reports';

  const {
    reports,
    getAllReports,
    // isRequesting
  } = useSelect((select) => {
    const store = select(REPORTS_STORE_NAME)

    return {
      reports: store.getAllReports(),
      // isRequesting: store.isItemsRequesting(),
    }
  }, [] )

  const {
    fetchItems,
    // deleteItems
  } = useDispatch( REPORTS_STORE_NAME );


  console.log('reports', reports)
  const tabs = [
    {
      label : __( 'Overview' ),
      component : ( classes ) => {
        return (
          <Fragment>
            <Card className={classes.container}><Chart type='line'/></Card>
            <Card className={classes.container}>
              <Typography className={classes.kpiTitle} component="h1" color="textSecondary">{`KPI`}</Typography>
              <Typography className={classes.kpiMetric} component="div" color="textSecondary">{`${Math.round(Math.random()*1000)/10}%`}</Typography>
            </Card>
          </Fragment>
        );
      }
    },
    {
      label : __( 'Contacts' ),
      component : ( classes ) => {
        return (
          <Card className={classes.container}><Chart type='doughnut'/></Card>
        );
      }
    },
    {
      label : __( 'Email' ),
      component : ( classes ) => {
        return (
          <Card className={classes.container}><Chart type='doughnut'/></Card>
        );
      }
    },
    {
      label : __( 'Funnels' ),
      component : () => {
        return 'Item Four'
      }
    },
    {
      label : __( 'Broadcasts' ),
      component : () => {
        return 'Item Five'
      }
    },
    {
      label : __( 'Forms' ),
      component : () => {
        return 'Item Six'
      }
    },
    {
      label : __( 'Pipeline' ),
      component : () => {
        return 'Item Seven'
      }
    },
  ]

	return (
			<Fragment>
				<TabPanel tabs={ tabs } />
			</Fragment>

	);
}
