/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { makeStyles } from '@material-ui/core/styles';
import { __ } from '@wordpress/i18n';
import Card from '@material-ui/core/Card';
import Typography from '@material-ui/core/Typography';
import TextField from '@material-ui/core/TextField';

/**
 * Internal dependencies
 */
 import Chart from '../../../core-ui/chart';
 import Spinner from '../../../core-ui/spinner';
import { REPORTS_STORE_NAME } from '../../../../data/reports'

const useStyles = makeStyles((theme) => ({
  container: {
		marginBottom: theme.spacing(1),
		textAlign: 'center'
    // paddingTop: theme.spacing(4),
    // paddingBottom: theme.spacing(4),
  },

	kpiTitle: {
		fontSize: '24px',
		fontStyle: 'bold'
	},
	kpiMetric: {
		fontSize: '16px'
	}
}));

export function Reports (props) {
  const classes = useStyles();
  const storeName = 'gh/v4/reports';
  // const [perPage, setPerPage] = useState(10)
  // const [page, setPage] = useState(1)
  // const [order, setOrder] = useState('asc')
  // const [orderBy, setOrderBy] = useState('ID')
  // const [selected, setSelected] = useState([])

  const { items, getItems, isRequesting, isUpdating } = useSelect((select) => {
    const store = select(storeName)

    return {
      items: store.getItems( {
        limit: 10
      } ),
      getItems: store.getItems,
      isRequesting: store.isItemsRequesting(),
      isUpdating: store.isItemsUpdating(),
    }
  }, [])

  const { fetchItems } = useDispatch( storeName );

  // fetchItems( { limit : 10 } )

  console.log(props.query)
	return (
			<Fragment>
				<Card className={classes.container}><Chart type='line'/></Card>
				<Card className={classes.container}>
					<Typography className={classes.kpiTitle} component="h1" color="textSecondary">{`KPI`}</Typography>
					<Typography className={classes.kpiMetric} component="div" color="textSecondary">{`${Math.round(Math.random()*1000)/10}%`}</Typography>
				</Card>
				<Card className={classes.container}>
					<Typography className={classes.kpiTitle} component="h1" color="textSecondary">{`KPI`}</Typography>
					<Typography className={classes.kpiMetric} component="div" color="textSecondary">{`${Math.round(Math.random()*1000)/10}%`}</Typography>
				</Card>
				<Card className={classes.container}>
					<Typography className={classes.kpiTitle} component="h1" color="textSecondary">{`KPI`}</Typography>
					<Typography className={classes.kpiMetric} component="div" color="textSecondary">{`${Math.round(Math.random()*1000)/10}%`}</Typography>
				</Card>
				<Card className={classes.container}>
					<Typography className={classes.kpiTitle} component="h1" color="textSecondary">{`KPI`}</Typography>
					<Typography className={classes.kpiMetric} component="div" color="textSecondary">{`${Math.round(Math.random()*1000)/10}%`}</Typography>
				</Card>

			</Fragment>

	);
}
