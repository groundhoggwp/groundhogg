import React, {
  useRef,
  useState,
  useEffect
} from 'react';
import { Link as RouterLink } from 'react-router-dom';
import moment from 'moment';
import {
  Avatar,
  Box,
  IconButton,
  Link,
  List,
  ListItem,
  ListItemAvatar,
  ListItemText,
  Popover,
  SvgIcon,
  Tooltip,
  Typography,
  makeStyles
} from '@material-ui/core';
import { Users as UsersIcon } from 'react-feather';
// import { useDispatch, useSelector } from '../../../store';
// import { getContacts } from '../../../slices/chat';
import OnlineIndicator from '../../../components/OnlineIndicator';

const useStyles = makeStyles((theme) => ({
  popover: {
    width: 320,
    padding: theme.spacing(2)
  },
  list: {
    padding: theme.spacing(1, 3)
  },
  listItemText: {
    marginRight: theme.spacing(1)
  },
  lastActivity: {
    whiteSpace: 'nowrap'
  }
}));

const Contacts = () => {
  const classes = useStyles();
  const ref = useRef(null);
  // const dispatch = useDispatch();
  // const { contacts } = useSelector((state) => state.chat);
  const [isOpen, setOpen] = useState(false);

  const handleOpen = () => {
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
  };

  useEffect(() => {
    // dispatch(getContacts());
  }, []);

  return (
    <>
      <Tooltip title="Contacts">
        <IconButton
          color="inherit"
          onClick={handleOpen}
          ref={ref}
        >
          <SvgIcon fontSize="small">
            <UsersIcon />
          </SvgIcon>
        </IconButton>
      </Tooltip>
      <Popover
        anchorOrigin={{
          vertical: 'bottom',
          horizontal: 'center'
        }}
        classes={{ paper: classes.popover }}
        anchorEl={ref.current}
        onClose={handleClose}
        open={isOpen}
      >

        <Typography
          variant="h4"
          color="textPrimary"
        >
          Contacts
        </Typography>
        <Box mt={2}>
          <List disablePadding>
            {/*contacts.allIds.map((contactId) => {
              const contact = contacts.byId[contactId];

              return (
                <ListItem
                  disableGutters
                  key={contact.id}
                >
                  <ListItemAvatar>
                    <Avatar
                      alt="Person"
                      src={contact.avatar}
                    />
                  </ListItemAvatar>
                  <ListItemText
                    className={classes.listItemText}
                    disableTypography
                    primary={(
                      <Link
                        color="textPrimary"
                        component={RouterLink}
                        display="block"
                        underline="none"
                        noWrap
                        to="#"
                        variant="h6"
                      >
                        {contact.name}
                      </Link>
                    )}
                  />
                  {contact.isActive ? (
                    <OnlineIndicator
                      size="small"
                      status="online"
                    />
                  ) : (
                    <Typography
                      color="textSecondary"
                      noWrap
                      variant="caption"
                    >
                      {moment(contact.lastActivity).fromNow()}
                    </Typography>
                  )}
                </ListItem>
              );
            })*/}
          </List>
        </Box>
      </Popover>
    </>
  );
};

export default Contacts;
