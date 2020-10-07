import { Fragment, useState, useEffect } from '@wordpress/element';
import { makeStyles } from '@material-ui/core/styles';
import List from '@material-ui/core/List';
import ListItem from '@material-ui/core/ListItem';
import ListItemIcon from '@material-ui/core/ListItemIcon';
import ListItemText from '@material-ui/core/ListItemText';
import { getPages } from '../../layout/controller';

const useStyles = makeStyles((theme) => ({
  root: {
    backgroundColor: theme.palette.background.paper,
  },
}));

export default function NavListItems(props) {
  const { push } = props.props.history;
  const classes = useStyles();

  const handleListItemClick = (view) => {
    push(`${view}`);
  };

  return(
    <div className={classes.root}>
      <List component="nav" aria-label="main">
        { getPages().map((page, index) =>
              <ListItem
                button
                onClick={ (event) => {
                  handleListItemClick(page.path)
                } }
                selected={ index === props.props.selectedIndex }
              >
                <ListItemIcon>
                  <page.icon />
                </ListItemIcon>
                <ListItemText primary={page.name}  />
              </ListItem>
            )
          }
      </List>
      </div>
  )
}
