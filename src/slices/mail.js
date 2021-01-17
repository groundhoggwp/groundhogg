import { createSlice } from '@reduxjs/toolkit';
import axios from 'src/utils/axios';
import objFromArray from 'src/utils/objFromArray';

const initialState = {
  mails: {
    byId: {},
    allIds: []
  },
  labels: [],
  isSidebarOpen: false,
  isComposeOpen: false
};

const slice = createSlice({
  name: 'mail',
  initialState,
  reducers: {
    getLabels(state, action) {
      const { labels } = action.payload;

      state.labels = labels;
    },
    getMails(state, action) {
      const { mails } = action.payload;

      state.mails.byId = objFromArray(mails);
      state.mails.allIds = Object.keys(state.mails.byId);
    },
    getMail(state, action) {
      const { mail } = action.payload;

      state.mails.byId[mail.id] = mail;

      if (!state.mails.allIds.includes(mail.id)) {
        state.mails.allIds.push(mail.id);
      }
    },
    openSidebar(state) {
      state.isSidebarOpen = true;
    },
    closeSidebar(state) {
      state.isSidebarOpen = false;
    },
    openCompose(state) {
      state.isComposeOpen = true;
    },
    closeCompose(state) {
      state.isComposeOpen = false;
    }
  }
});

export const reducer = slice.reducer;

export const getLabels = () => async (dispatch) => {
  const response = await axios.get('/api/mail/labels');

  dispatch(slice.actions.getLabels(response.data));
};

export const getMails = (params) => async (dispatch) => {
  const response = await axios.get('/api/mail/mails', {
    params
  });

  dispatch(slice.actions.getMails(response.data));
};

export const getMail = (mailId) => async (dispatch) => {
  const response = await axios.get('/api/mail/mail', {
    params: {
      mailId
    }
  });

  dispatch(slice.actions.getMail(response.data));
};

export const openSidebar = () => async (dispatch) => {
  dispatch(slice.actions.openSidebar());
};

export const closeSidebar = () => async (dispatch) => {
  dispatch(slice.actions.closeSidebar());
};

export const openCompose = () => async (dispatch) => {
  dispatch(slice.actions.openCompose());
};

export const closeCompose = () => async (dispatch) => {
  dispatch(slice.actions.closeCompose());
};

export default slice;
