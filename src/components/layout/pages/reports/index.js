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
// import { TAGS_STORE_NAME } from '../../../../data';

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


export const Reports = ( props ) => {
	const classes = useStyles();
	// const [ stateTagValue, setTagValue ] = useState( '' );
	//
	// const { updateTags } = useDispatch( TAGS_STORE_NAME );
	//
	// const { tags, isRequesting, isUpdating } = useSelect( ( select ) => {
	// 	const store = select( TAGS_STORE_NAME );
	// 	return {
	// 		tags : store.getTags(),
	// 		isRequesting : store.isTagsRequesting(),
	// 		isUpdating: store.isTagsUpdating()
	// 	}
	// } );
	//
	// if ( isRequesting || isUpdating ) {
	// 	return <Spinner />;
	// }

	return (
			<Fragment>
				<Card className={classes.container}><Chart type='line'/></Card>
				<Card className={classes.container}><Chart type='bar'/></Card>
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
