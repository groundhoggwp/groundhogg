import { createSlice } from '@reduxjs/toolkit';
import _ from 'lodash';
import axios from 'src/utils/axios';

const initialState = {
  events: [],
  isModalOpen: false,
  selectedEventId: null,
  selectedRange: null
};

const slice = createSlice({
  name: 'calendar',
  initialState,
  reducers: {
    getEvents(state, action) {
      const { events } = action.payload;

      state.events = events;
    },
    createEvent(state, action) {
      const { event } = action.payload;

      state.events = [...state.events, event];
    },
    selectEvent(state, action) {
      const { eventId = null } = action.payload;

      state.isModalOpen = true;
      state.selectedEventId = eventId;
    },
    updateEvent(state, action) {
      const { event } = action.payload;

      state.events = _.map(state.events, (_event) => {
        if (_event.id === event.id) {
          return event;
        }

        return _event;
      });
    },
    deleteEvent(state, action) {
      const { eventId } = action.payload;

      state.events = _.reject(state.events, { id: eventId });
    },
    selectRange(state, action) {
      const { start, end } = action.payload;

      state.isModalOpen = true;
      state.selectedRange = {
        start,
        end
      };
    },
    openModal(state) {
      state.isModalOpen = true;
    },
    closeModal(state) {
      state.isModalOpen = false;
      state.selectedEventId = null;
      state.selectedRange = null;
    }
  }
});

export const reducer = slice.reducer;

export const getEvents = () => async (dispatch) => {
  const response = await axios.get('/api/calendar/events');

  dispatch(slice.actions.getEvents(response.data));
};

export const createEvent = (data) => async (dispatch) => {
  const response = await axios.post('/api/calendar/events/new', data);

  dispatch(slice.actions.createEvent(response.data));
};

export const selectEvent = (eventId) => async (dispatch) => {
  dispatch(slice.actions.selectEvent({ eventId }));
};

export const updateEvent = (eventId, update) => async (dispatch) => {
  const response = await axios.post('/api/calendar/events/update', {
    eventId,
    update
  });

  dispatch(slice.actions.updateEvent(response.data));
};

export const deleteEvent = (eventId) => async (dispatch) => {
  await axios.post('/api/calendar/events/remove', {
    eventId
  });

  dispatch(slice.actions.deleteEvent({ eventId }));
};

export const selectRange = (start, end) => (dispatch) => {
  dispatch(slice.actions.selectRange({
    start: start.getTime(),
    end: end.getTime()
  }));
};

export const openModal = () => (dispatch) => {
  dispatch(slice.actions.openModal());
};

export const closeModal = () => (dispatch) => {
  dispatch(slice.actions.closeModal());
};

export default slice;
