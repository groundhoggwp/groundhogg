import React, { useState } from 'react';
import { Link as RouterLink } from 'react-router-dom';
import PerfectScrollbar from 'react-perfect-scrollbar';
// import { useSnackbar } from 'notistack';
import {
  Box,
  Button,
  CircularProgress,
  Drawer,
  IconButton,
  InputAdornment,
  Link,
  SvgIcon,
  TextField,
  Tooltip,
  Typography,
  makeStyles
} from '@material-ui/core';
import {
  Search as SearchIcon,
  XCircle as XIcon
} from 'react-feather';
import axios from 'axios';

const useStyles = makeStyles(() => ({
  drawer: {
    width: 500,
    maxWidth: '100%'
  }
}));

const Search = () => {
  const classes = useStyles();
  // const { enqueueSnackbar } = useSnackbar();
  const [value, setValue] = useState('');
  const [isOpen, setOpen] = useState(false);
  const [isLoading, setLoading] = useState(false);
  const [results, setResults] = useState([]);

  const handleOpen = () => {
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
  };

  const handleSearch = async () => {
    try {
      setLoading(true);

      const response = await axios.get('/api/search');

      setResults(response.data.results);
    } catch (err) {
      console.error(err);
      // enqueueSnackbar('Something went wrong', {
      //   variant: 'error'
      // });
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Tooltip title="Search">
        <IconButton
          color="inherit"
          onClick={handleOpen}
        >
          <SvgIcon fontSize="small">
            <SearchIcon />
          </SvgIcon>
        </IconButton>
      </Tooltip>
      <Drawer
        anchor="right"
        classes={{ paper: classes.drawer }}
        ModalProps={{ BackdropProps: { invisible: true } }}
        onClose={handleClose}
        open={isOpen}
        variant="temporary"
      >
        <PerfectScrollbar options={{ suppressScrollX: true }}>
          <Box p={3}>
            <Box
              display="flex"
              justifyContent="space-between"
              alignItems="center"
            >
              <Typography
                variant="h4"
                color="textPrimary"
              >
                Search
              </Typography>
              <IconButton onClick={handleClose}>
                <SvgIcon fontSize="small">
                  <XIcon />
                </SvgIcon>
              </IconButton>
            </Box>
            <Box mt={2}>
              <TextField
                fullWidth
                InputProps={{
                  startAdornment: (
                    <InputAdornment position="start">
                      <SvgIcon
                        fontSize="small"
                        color="action"
                      >
                        <SearchIcon />
                      </SvgIcon>
                    </InputAdornment>
                  )
                }}
                onChange={(event) => setValue(event.target.value)}
                placeholder="Search people &amp; places"
                value={value}
                variant="outlined"
              />
            </Box>
            <Box
              mt={2}
              display="flex"
              justifyContent="flex-end"
            >
              <Button
                color="secondary"
                variant="contained"
                onClick={handleSearch}
              >
                Search
              </Button>
            </Box>
            <Box mt={4}>
              {isLoading ? (
                <Box
                  display="flex"
                  justifyContent="center"
                >
                  <CircularProgress />
                </Box>
              ) : (
                <>
                  {results.map((result, i) => (
                    <Box
                      key={i}
                      mb={2}
                    >
                      <Link
                        variant="h4"
                        color="textPrimary"
                        component={RouterLink}
                        to="/app"
                      >
                        {result.title}
                      </Link>
                      <Typography
                        variant="body2"
                        color="textPrimary"
                      >
                        {result.description}
                      </Typography>
                    </Box>
                  ))}
                </>
              )}
            </Box>
          </Box>
        </PerfectScrollbar>
      </Drawer>
    </>
  );
};

export default Search;
