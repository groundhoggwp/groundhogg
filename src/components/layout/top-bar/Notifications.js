import React, {
  useRef,
  useState,
  useEffect
} from 'react';
import { Link as RouterLink } from 'react-router-dom';
import {
  Avatar,
  Box,
  Button,
  IconButton,
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
import {
  Bell as BellIcon,
  Package as PackageIcon,
  MessageCircle as MessageIcon,
  Truck as TruckIcon
} from 'react-feather';
// import { useDispatch, useSelector } from 'src/store';
// import { getNotifications } from 'src/slices/notification';

const iconsMap = {
  order_placed: PackageIcon,
  new_message: MessageIcon,
  item_shipped: TruckIcon
};

const useStyles = makeStyles((theme) => ({
  popover: {
    width: 320
  },
  icon: {
    backgroundColor: theme.palette.secondary.main,
    color: theme.palette.secondary.contrastText
  }
}));

const Notifications = () => {
  const classes = useStyles();
  // const { notifications } = useSelector((state) => state.notifications);  
  const notifications = [];
  const ref = useRef(null);
  // const dispatch = useDispatch();
  const [isOpen, setOpen] = useState(false);



  const handleOpen = () => {
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
  };

  useEffect(() => {
    // dispatch(getNotifications());
  }, []);
  // }, [dispatch]);

  return (
    <>
      <Tooltip title="Notifications">
        <IconButton
          color="inherit"
          ref={ref}
          onClick={handleOpen}
        >
          <SvgIcon>
            <BellIcon />
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
        <Box p={2}>
          <Typography
            variant="h5"
            color="textPrimary"
          >
            Notifications
          </Typography>
        </Box>
        {notifications.length === 0 ? (
          <div/>
          /*<Box p={2}>
            <Typography
              variant="h6"
              color="textPrimary"
            >
              There are no notifications
            </Typography>
          </Box>*/
        ) : (

          <>/*
            <List disablePadding>
              {notifications.map((notification) => {
                const Icon = iconsMap[notification.type];

                return (
                  <ListItem
                    component={RouterLink}
                    divider
                    key={notification.id}
                    to="#"
                  >
                    <ListItemAvatar>
                      <Avatar
                        className={classes.icon}
                      >
                        <SvgIcon fontSize="small">
                          <Icon />
                        </SvgIcon>
                      </Avatar>
                    </ListItemAvatar>
                    <ListItemText
                      primary={notification.title}
                      primaryTypographyProps={{ variant: 'subtitle2', color: 'textPrimary' }}
                      secondary={notification.description}
                    />
                  </ListItem>
                );
              })}
            </List>*/
            <Box
              p={1}
              display="flex"
              justifyContent="center"
            >
              <Button
                component={RouterLink}
                size="small"
                to="#"
              >
                Mark all as read
              </Button>
            </Box>
          </>
        )}
      </Popover>
    </>
  );
};

export default Notifications;
