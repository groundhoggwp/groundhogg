import { Card } from '@material-ui/core';
import { Route, useRouteMatch, useParams } from 'react-router-dom';
import { makeStyles } from '@material-ui/core/styles';
import StepsPath from 'components/layout/pages/funnelEditor/components/StepFlow';
import { unSlash } from 'utils/core';
import EditStep from '../EditStep';
import AddStep from '../AddStep';
import StepNotes from '../StepNotes';
import Grid from '@material-ui/core/Grid';

const useStyles = makeStyles((theme) => ({
	StepsPath: {
		background: 'white',
		padding: '100px 25px 10px',
		minHeight: '100vh',
	},
	StepOptions: {
		background: '#F6F9FB',
		minHeight: '100vh',
		padding: '100px 25px 25px',
	},
}));

export default ({ funnel }) => {
	const classes = useStyles();

	const { steps, edges } = funnel;
	const { path } = useRouteMatch();

	return (
		<>
			<Grid container>
				<Grid item xs={3} className={classes.StepsPath}>
					<StepsPath steps={steps} edges={edges} />
				</Grid>
				<Grid className={classes.StepOptions} item xs={9}>
					test
				</Grid>
			</Grid>
			{/*}
			<div className={classes.root}>
				<StepsPath steps={steps} edges={edges} />
				<Card className={classes.card}>
					<Route path={`${unSlash(path)}/:stepId/edit`}>
						<EditStep />
					</Route>
					<Route path={`${unSlash(path)}/add`}>
						<AddStep />
					</Route>
				</Card>
			</div>
			*/}
		</>
	);
};
