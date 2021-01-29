import Grid from "@material-ui/core/Grid";
import {makeStyles} from '@material-ui/core/styles';
import Card from '@material-ui/core/Card';
import CardActions from '@material-ui/core/CardActions';
import CardContent from '@material-ui/core/CardContent';
import Button from '@material-ui/core/Button';
import Typography from '@material-ui/core/Typography';
import {Link} from "react-router-dom";
import {__} from "@wordpress/i18n";
import {useHistory} from 'react-router-dom';

const useStyles = makeStyles({
    root: {
        maxWidth: 345,
    },
});

export const ToolsGrid = (props) => {
    // const classes = useStyles();
    const {description, title, path} = props;
    let history = useHistory();

    const onViewClick = () => {
        history.push(path);
    }

    return (
        <Grid item xs={12} sm={6} md={4} lg={3}>
            <Card>
                <CardContent>
                    <Typography gutterBottom variant="h5" component="h2">
                        {title}
                    </Typography>
                    <Typography variant="body2" color="textSecondary" component="p">
                        {description}
                    </Typography>
                </CardContent>
                <CardActions>
                    <Button variant="contained" size="small" color="primary" onClick={onViewClick}>
                        View
                    </Button>
                </CardActions>
            </Card>
        </Grid>
    );
}