import { createSlice } from '@reduxjs/toolkit';
import axios from 'src/utils/axios';
import objFromArray from 'src/utils/objFromArray';

const initialState = {
  activeThreadId: null,
  contacts: {
    byId: {},
    allIds: []
  },
  threads: {
    byId: {},
    allIds: []
  },
  participants: [],
  recipients: []
};

const slice = createSlice({
  name: 'chat',
  initialState,
  reducers: {
    getContacts(state, action) {
      const { contacts } = action.payload;

      state.contacts.byId = objFromArray(contacts);
      state.contacts.allIds = Object.keys(state.contacts.byId);
    },
    getThreads(state, action) {
      const { threads } = action.payload;

      state.threads.byId = objFromArray(threads);
      state.threads.allIds = Object.keys(state.threads.byId);
    },
    getThread(state, action) {
      const { thread } = action.payload;

      if (thread) {
        state.threads.byId[thread.id] = thread;
        state.activeThreadId = thread.id;
  
        if (!state.threads.allIds.includes(thread.id)) {
          state.threads.allIds.push(thread.id);
        }
      } else {
        state.activeThreadId = null;
      }
    },
    markThreadAsSeen(state, action) {
      const { threadId } = action.payload;
      const thread = state.threads.byId[threadId];

      if (thread) {
        thread.unreadCount = 0;
      }
    },
    resetActiveThread(state) {
      state.activeThreadId = null;
    },
    getParticipants(state, action) {
      const { participants } = action.payload;

      state.participants = participants;
    },
    addRecipient(state, action) {
      const { recipient } = action.payload;
      const exists = state.recipients.find((_recipient) => _recipient.id === recipient.id);

      if (!exists) {
        state.recipients.push(recipient);
      }
    },
    removeRecipient(state, action) {
      const { recipientId } = action.payload;

      state.recipients = state.recipients.filter((recipient) => recipient.id !== recipientId);
    }
  }
});

export const reducer = slice.reducer;

export const getContacts = () => async (dispatch) => {
  const response = await axios.get('/api/chat/contacts');

  dispatch(slice.actions.getContacts(response.data));
};

export const getThreads = () => async (dispatch) => {
  const response = await axios.get('/api/chat/threads');

  dispatch(slice.actions.getThreads(response.data));
};

export const getThread = (threadKey) => async (dispatch) => {
  const response = await axios.get('/api/chat/thread', {
    params: {
      threadKey
    }
  });

  dispatch(slice.actions.getThread(response.data));
};

export const markThreadAsSeen = (threadId) => async (dispatch) => {
  await axios.get('/api/chat/thread/mark-as-seen', {
    params: {
      threadId
    }
  });

  dispatch(slice.actions.markThreadAsSeen({ threadId }));
};

export const resetActiveThread = () => (dispatch) => {
  dispatch(slice.actions.resetActiveThread());
};

export const getParticipants = (threadKey) => async (dispatch) => {
  const response = await axios.get('/api/chat/participants', {
    params: {
      threadKey
    }
  });

  dispatch(slice.actions.getParticipants(response.data));
};

export const addRecipient = (recipient) => (dispatch) => {
  dispatch(slice.actions.addRecipient({ recipient }));
};

export const removeRecipient = (recipientId) => (dispatch) => {
  dispatch(slice.actions.removeRecipient({ recipientId }));
};

export default slice;
