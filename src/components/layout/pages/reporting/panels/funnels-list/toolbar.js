import React from 'react';
import PropTypes from 'prop-types';
import clsx from 'clsx';
import {
  Box,
  Checkbox,
  Hidden,
  IconButton,
  Input,
  Paper,
  Tooltip,
  Typography,
  makeStyles,
  SvgIcon
} from '@material-ui/core';
import RefreshIcon from '@material-ui/icons/Refresh';
import MoreIcon from '@material-ui/icons/MoreVert';
import KeyboardArrowLeftIcon from '@material-ui/icons/KeyboardArrowLeft';
import KeyboardArrowRightIcon from '@material-ui/icons/KeyboardArrowRight';
import {
  Search as SearchIcon,
  Menu as MenuIcon
} from 'react-feather';
// import { useDispatch } from 'src/store';
// import { openSidebar } from 'src/slices/mail';

const useStyles = makeStyles((theme) => ({
  root: {
    width: 'calc(100% - 30px)',
    backgroundColor: theme.palette.background.default,
    padding: theme.spacing(2),
    height: 68,
    display: 'flex',
    alignItems: 'center'
  },
  searchContainer: {
    alignItems: 'center',
    display: 'flex',
    marginLeft: theme.spacing(2),
    paddingBottom: theme.spacing(0.5),
    paddingLeft: theme.spacing(1),
    paddingRight: theme.spacing(1),
    paddingTop: theme.spacing(0.5)
  },
  searchInput: {
    marginLeft: theme.spacing(2),
    flexGrow: 1
  }
}));

const Toolbar = ({
  className,
  mails,
  onDeselectAll,
  onSelectAll,
  selectedMails,
  ...rest
}) => {
  const classes = useStyles();
  // const dispatch = useDispatch();

  const handleOpenSidebar = () => {
    // dispatch(openSidebar());
  };

  const handleCheckboxChange = (event) => (event.target.checked ? onSelectAll() : onDeselectAll());

  const selectedAllMails = selectedMails === mails && mails > 0;
  const selectedSomeMails = selectedMails > 0 && selectedMails < mails;

  return (
    <div
      className={clsx(classes.root, className)}
      {...rest}
    >
      <Hidden mdUp>
        <IconButton onClick={handleOpenSidebar}>
          <SvgIcon fontSize="small">
            <MenuIcon />
          </SvgIcon>
        </IconButton>
      </Hidden>
      <Hidden smDown>
        <Box
          display="flex"
          alignItems="center"
        >
          <Checkbox
            checked={selectedAllMails}
            indeterminate={selectedSomeMails}
            onChange={handleCheckboxChange}
          />
          <Typography
            variant="h6"
            color="textPrimary"
          >
            Select all
          </Typography>
        </Box>
        <Box flexGrow={1} />
        <Paper
          className={classes.searchContainer}
          variant="outlined"
        >
          <SvgIcon
            color="action"
            fontSize="small"
          >
            <SearchIcon />
          </SvgIcon>
          <Input
            className={classes.searchInput}
            disableUnderline
            placeholder="Search mail"
          />
        </Paper>
        <Tooltip title="Refresh">
          <IconButton>
            <RefreshIcon fontSize="small" />
          </IconButton>
        </Tooltip>
        <Tooltip title="More options">
          <IconButton>
            <MoreIcon fontSize="small" />
          </IconButton>
        </Tooltip>
        <Box
          display="flex"
          alignItems="center"
        >
          <Tooltip title="Next page">
            <IconButton>
              <KeyboardArrowLeftIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          {mails > 0 && (
            <>
              <Typography
                noWrap
                variant="body2"
                color="textSecondary"
              >
                1 -
                {' '}
                {mails}
                {' '}
                of
                {' '}
                {mails}
              </Typography>
              <Tooltip title="Previous page">
                <IconButton>
                  <KeyboardArrowRightIcon fontSize="small" />
                </IconButton>
              </Tooltip>
            </>
          )}
        </Box>
      </Hidden>
    </div>
  );
};

Toolbar.propTypes = {
  className: PropTypes.string,
  mails: PropTypes.number.isRequired,
  onDeselectAll: PropTypes.func,
  onSelectAll: PropTypes.func,
  selectedMails: PropTypes.number.isRequired
};

Toolbar.defaultProps = {
  onDeselectAll: () => {},
  onSelectAll: () => {}
};

export default Toolbar;
