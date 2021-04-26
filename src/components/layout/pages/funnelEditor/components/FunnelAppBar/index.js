import AppBar from '@material-ui/core/AppBar';
import { FUNNELS_STORE_NAME } from 'data/funnels';
import { useSelect } from '@wordpress/data';
import Toolbar from '@material-ui/core/Toolbar';
import makeStyles from '@material-ui/core/styles/makeStyles';
import Box from '@material-ui/core/Box';
import Button from '@material-ui/core/Button';
import MoreHorizIcon from '@material-ui/icons/MoreHoriz';

import FunnelTitleEdit from '../FunnelTitleEdit';
import FunnelStatus from '../FunnelStatus';

const useStyles = makeStyles((theme) => ({
	appBar: {
		'& .MuiAppBar-colorPrimary': {
			color: '#000',
			backgroundColor: '#ffffff',
		},
	},
}));

export default ({ funnel, width }) => {
	const classes = useStyles();

	const { id, data } = {
		id: funnel.ID,
		data: funnel.data,
	};

	return (
		<div className={classes.appBar}>
			<AppBar style={{ top: 32, width: width }}>
				<Toolbar>
					<Box display={'flex'} flexGrow={1}>
						<Box>
							<FunnelTitleEdit id={id} data={data} />
						</Box>
						<Button variant="outlined" size={'small'}>
							<MoreHorizIcon />
						</Button>
						<Box display={'flex'} flexGrow={1} justifyContent={'flex-end'}>
							<FunnelStatus id={id} data={data} />
						</Box>
					</Box>
				</Toolbar>
			</AppBar>
		</div>
	);
};
