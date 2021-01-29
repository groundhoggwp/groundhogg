import React, {
  useState,
  useRef
} from 'react';
import PropTypes from 'prop-types';
import clsx from 'clsx';
import {
  Box,
  Card,
  CardContent,
  Divider,
  IconButton,
  Input,
  Paper,
  Tooltip,
  makeStyles
} from '@material-ui/core';
import SendIcon from '@material-ui/icons/Send';
import AddPhotoIcon from '@material-ui/icons/AddPhotoAlternate';
import AttachFileIcon from '@material-ui/icons/AttachFile';
import useAuth from 'src/hooks/useAuth';

const useStyles = makeStyles((theme) => ({
  root: {},
  inputContainer: {
    flexGrow: 1,
    paddingBottom: theme.spacing(0.5),
    paddingLeft: theme.spacing(2),
    paddingRight: theme.spacing(2),
    paddingTop: theme.spacing(0.5)
  },
  divider: {
    height: 24,
    width: 1
  },
  fileInput: {
    display: 'none'
  }
}));

const PostAdd = ({ className, ...rest } ) => {
  const classes = useStyles();
  const fileInputRef = useRef(null);
  const [value, setValue] = useState('');
  const { user } = useAuth();

  const handleChange = (event) => {
    event.persist();
    setValue(event.target.value);
  };

  const handleAttach = () => {
    fileInputRef.current.click();
  };

  return (
    <Card
      className={clsx(classes.root, className)}
      {...rest}
    >
      <CardContent>
        <Box
          display="flex"
          alignItems="center"
        >
          <Paper
            className={classes.inputContainer}
            variant="outlined"
          >
            <Input
              disableUnderline
              fullWidth
              onChange={handleChange}
              placeholder={`What's on your mind, ${user.name}`}
            />
          </Paper>
          <Tooltip title="Send">
            <IconButton color={value.length > 0 ? 'primary' : 'default'}>
              <SendIcon />
            </IconButton>
          </Tooltip>
          <Divider className={classes.divider} />
          <Tooltip title="Attach image">
            <IconButton
              edge="end"
              onClick={handleAttach}
            >
              <AddPhotoIcon />
            </IconButton>
          </Tooltip>
          <Tooltip title="Attach file">
            <IconButton
              edge="end"
              onClick={handleAttach}
            >
              <AttachFileIcon />
            </IconButton>
          </Tooltip>
          <input
            className={classes.fileInput}
            ref={fileInputRef}
            type="file"
          />
        </Box>
      </CardContent>
    </Card>
  );
};

PostAdd.propTypes = {
  className: PropTypes.string
};

export default PostAdd;
