import React from 'react';
import { Link as RouterLink } from 'react-router-dom';
import { useParams } from 'react-router-dom';
import PropTypes from 'prop-types';
import clsx from 'clsx';
import moment from 'moment';
import {
  Avatar,
  Box,
  Checkbox,
  IconButton,
  Hidden,
  Tooltip,
  Typography,
  colors,
  makeStyles
} from '@material-ui/core';
import LabelIcon from '@material-ui/icons/Label';
import StarBorderIcon from '@material-ui/icons/StarBorder';
import LabelImportantIcon from '@material-ui/icons/LabelImportant';
import LabelImportantOutlinedIcon from '@material-ui/icons/LabelImportantOutlined';
// import getInitials from 'src/utils/getInitials';
// import { useSelector } from 'src/store';

const getTo = (params, mailId) => {
  const { systemLabel, customLabel } = params;
  const baseUrl = '/app/mail';

  if (systemLabel) {
    return `${baseUrl}/${systemLabel}/${mailId}`;
  }

  if (customLabel) {
    return `${baseUrl}/label/${customLabel}/${mailId}`;
  }

  return baseUrl;
};

const useStyles = makeStyles((theme) => ({
  root: {
    backgroundColor: theme.palette.background.default,
    padding: theme.spacing(2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    display: 'flex',
    alignItems: 'center',
    width: 'calc(100% - 30px)',
    '&:hover': {
      backgroundColor: theme.palette.action.hover
    }
  },
  unread: {
    position: 'relative',
    '&:before': {
      content: '" "',
      height: '100%',
      position: 'absolute',
      left: 0,
      top: 0,
      width: 4,
      backgroundColor: theme.palette.error.main
    },
    '& $name, & $subject': {
      fontWeight: theme.typography.fontWeightBold
    }
  },
  selected: {
    backgroundColor: theme.palette.action.selected
  },
  filterActive: {
    color: colors.amber[400]
  },
  content: {
    cursor: 'pointer',
    textDecoration: 'none'
  },
  details: {
    [theme.breakpoints.up('md')]: {
      display: 'flex',
      alignItems: 'center',
      flexGrow: 1
    }
  },
  name: {
    // [theme.breakpoints.up('md')]: {
      minWidth: 'calc(100% - 150px)',
      flexBasis: 480
    // }
  },
  subject: {
    maxWidth: 400,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis'
  },
  message: {
    maxWidth: 800,
    flexGrow: 1,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    marginRight: 'auto'
  },
  label: {
    fontFamily: theme.typography.fontFamily,
    fontSize: theme.typography.pxToRem(12),
    color: theme.palette.common.white,
    paddingLeft: 4,
    paddingRight: 4,
    paddingTop: 2,
    paddingBottom: 2,
    borderRadius: 2,
    '& + &': {
      marginLeft: theme.spacing(1)
    }
  },
  date: {
    whiteSpace: 'nowrap'
  }
}));

const ListItem = ({
  className,
  mail,
  onDeselect,
  onSelect,
  selected,
  title,
  id,
  status,
  date,
  ...rest

}) => {
  const classes = useStyles();
  const params = useParams();
  // const { labels } = useSelector((state) => state.mail);

  const handleCheckboxChange = (event) => (event.target.checked ? onSelect() : onDeselect());

  const handleStarToggle = () => {
    // dispatch action
  };

  const handleImportantToggle = () => {
    // dispatch action
  };

  // const to = getTo(params, mail.id);

  return (
    <div className={classes.root}
    >
      <Hidden smDown>
        <Box
          mr={1}
          display="flex"
          alignItems="center"
        >
          <LabelIcon color="primary"/>
        </Box>
      </Hidden>
      <Box
        minWidth="1px"
        display="flex"
        flexGrow={1}
        component={RouterLink}
        to={`funnels/${id}`}
        className={classes.content}
      >
        {/*<Avatar src={'asdfasdf'}>
          getInitials(mail.from.name)
        </Avatar>*/}

        <Box
          minWidth="1px"
          ml={1}
          className={classes.details}
        >
          <Typography
            variant="body2"
            color="textPrimary"
            className={classes.name}
          >
            {title}
          </Typography>
          {/*<Typography
            variant="body2"
            color="textSecondary"
            className={classes.subject}
          >
            {'mail.subject'}
          </Typography>*/}
          {/*<Hidden smDown>*/}
            {/*<Typography
              variant="body2"
              color="textSecondary"
              className={classes.message}
            >
              <Box
                component="span"
                ml={2}
              >
                -
              </Box>
              {'mail.message'}
            </Typography>*/}
              <Box
                display="flex"
                mx={2}
              >



                    <span
                      style={{ backgroundColor: status === 'active' ? 'rgb(30, 136, 229)' : 'rgb(67, 160, 71)' }}
                      key={'label.id'}
                      className={classes.label}
                    >
                      {status}
                    </span>


              </Box>
          {/*</Hidden>*/}
          <Typography
            className={classes.date}
            color="textSecondary"
            variant="caption"
          >
            {moment(date).format('DD MMM YYYY')}
          </Typography>
        </Box>
      </Box>
    </div>
  );
};

ListItem.propTypes = {
  className: PropTypes.string,
  mail: PropTypes.object.isRequired,
  onDeselect: PropTypes.func,
  onSelect: PropTypes.func,
  selected: PropTypes.bool.isRequired
};

ListItem.defaultProps = {
  onDeselect: () => {},
  onSelect: () => {}
};

export default ListItem;
