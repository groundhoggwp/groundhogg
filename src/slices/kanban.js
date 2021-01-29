import { createSlice } from '@reduxjs/toolkit';
import _ from 'lodash';
import axios from 'src/utils/axios';
import objFromArray from 'src/utils/objFromArray';

const initialState = {
  isLoaded: false,
  lists: {
    byId: {},
    allIds: []
  },
  cards: {
    byId: {},
    allIds: []
  },
  members: {
    byId: {},
    allIds: []
  }
};

const slice = createSlice({
  name: 'kanban',
  initialState,
  reducers: {
    getBoard(state, action) {
      const { board } = action.payload;

      state.lists.byId = objFromArray(board.lists);
      state.lists.allIds = Object.keys(state.lists.byId);
      state.cards.byId = objFromArray(board.cards);
      state.cards.allIds = Object.keys(state.cards.byId);
      state.members.byId = objFromArray(board.members);
      state.members.allIds = Object.keys(state.members.byId);
      state.isLoaded = true;
    },
    createList(state, action) {
      const { list } = action.payload;

      state.lists.byId[list.id] = list;
      state.lists.allIds.push(list.id);
    },
    updateList(state, action) {
      const { list } = action.payload;

      state.lists.byId[list.id] = list;
    },
    clearList(state, action) {
      const { listId } = action.payload;
      const { cardIds } = state.lists.byId[listId];

      state.lists.byId[listId].cardIds = [];
      state.cards.byId = _.omit(state.cards.byId, cardIds);
      _.pull(state.cards.allIds, ...cardIds);
    },
    deleteList(state, action) {
      const { listId } = action.payload;

      state.lists.byId = _.omit(state.lists.byId, listId);
      _.pull(state.lists.allIds, listId);
    },
    createCard(state, action) {
      const { card } = action.payload;

      state.cards.byId[card.id] = card;
      state.cards.allIds.push(card.id);
      state.lists.byId[card.listId].cardIds.push(card.id);
    },
    updateCard(state, action) {
      const { card } = action.payload;

      _.merge(state.cards.byId[card.id], card);
    },
    moveCard(state, action) {
      const { cardId, position, listId } = action.payload;
      const { listId: sourceListId } = state.cards.byId[cardId];

      // Remove card from source list
      _.pull(state.lists.byId[sourceListId].cardIds, cardId);

      // If listId arg exists, it means that
      // we have to add the card to the new list
      if (listId) {
        state.cards.byId[cardId].listId = listId;
        state.lists.byId[listId].cardIds.splice(position, 0, cardId);
      } else {
        state.lists.byId[sourceListId].cardIds.splice(position, 0, cardId);
      }
    },
    deleteCard(state, action) {
      const { cardId } = action.payload;
      const { listId } = state.cards.byId[cardId];

      state.cards.byId = _.omit(state.cards.byId, cardId);
      _.pull(state.cards.allIds, cardId);
      _.pull(state.lists.byId[listId].cardIds, cardId);
    },
    addComment(state, action) {
      const { comment } = action.payload;
      const card = state.cards.byId[comment.cardId];

      card.comments.push(comment);
    },
    addChecklist(state, action) {
      const { cardId, checklist } = action.payload;
      const card = state.cards.byId[cardId];

      card.checklists.push(checklist);
    },
    updateChecklist(state, action) {
      const { cardId, checklist } = action.payload;
      const card = state.cards.byId[cardId];

      card.checklists = _.map(card.checklists, (_checklist) => {
        if (_checklist.id === checklist.id) {
          return checklist;
        }

        return _checklist;
      });
    },
    deleteChecklist(state, action) {
      const { cardId, checklistId } = action.payload;
      const card = state.cards.byId[cardId];

      card.checklists = _.reject(card.checklists, { id: checklistId });
    },
    addCheckItem(state, action) {
      const { cardId, checklistId, checkItem } = action.payload;
      const card = state.cards.byId[cardId];

      _.assign(card, {
        checklists: _.map(card.checklists, (checklist) => {
          if (checklist.id === checklistId) {
            _.assign(checklist, {
              checkItems: [...checklist.checkItems, checkItem]
            });
          }

          return checklist;
        })
      });
    },
    updateCheckItem(state, action) {
      const {
        cardId,
        checklistId,
        checkItem
      } = action.payload;
      const card = state.cards.byId[cardId];

      card.checklists = _.map(card.checklists, (checklist) => {
        if (checklist.id === checklistId) {
          _.assign(checklist, {
            checkItems: _.map(checklist.checkItems, (_checkItem) => {
              if (_checkItem.id === checkItem.id) {
                return checkItem;
              }

              return _checkItem;
            })
          });
        }

        return checklist;
      });
    },
    deleteCheckItem(state, action) {
      const { cardId, checklistId, checkItemId } = action.payload;
      const card = state.cards.byId[cardId];

      card.checklists = _.map(card.checklists, (checklist) => {
        if (checklist.id === checklistId) {
          _.assign(checklist, {
            checkItems: _.reject(checklist.checkItems, { id: checkItemId })
          });
        }

        return checklist;
      });
    }
  }
});

export const reducer = slice.reducer;

export const getBoard = () => async (dispatch) => {
  const response = await axios.get('/api/kanban/board');

  dispatch(slice.actions.getBoard(response.data));
};

export const createList = (name) => async (dispatch) => {
  const response = await axios.post('/api/kanban/lists/new', {
    name
  });

  dispatch(slice.actions.createList(response.data));
};

export const updateList = (listId, update) => async (dispatch) => {
  const response = await axios.post('/api/kanban/list/update', {
    listId,
    update
  });

  dispatch(slice.actions.updateList(response.data));
};

export const clearList = (listId) => async (dispatch) => {
  await axios.post('/api/kanban/lists/clear', {
    listId
  });

  dispatch(slice.actions.clearList({ listId }));
};

export const deleteList = (listId) => async (dispatch) => {
  await axios.post('/api/kanban/lists/remove', {
    listId
  });

  dispatch(slice.actions.deleteList({ listId }));
};

export const createCard = (listId, name) => async (dispatch) => {
  const response = await axios.post('/api/kanban/cards/new', {
    listId,
    name
  });

  dispatch(slice.actions.createCard(response.data));
};

export const updateCard = (cardId, update) => async (dispatch) => {
  const response = await axios.post('/api/kanban/cards/update', {
    cardId,
    update
  });

  dispatch(slice.actions.updateCard(response.data));
};

export const moveCard = (cardId, position, listId) => async (dispatch) => {
  await axios.post('/api/kanban/cards/move', {
    cardId,
    position,
    listId
  });

  dispatch(slice.actions.moveCard({
    cardId,
    position,
    listId
  }));
};

export const deleteCard = (cardId) => async (dispatch) => {
  await axios.post('/api/kanban/cards/remove', {
    cardId
  });

  dispatch(slice.actions.deleteCard({ cardId }));
};

export const addComment = (cardId, message) => async (dispatch) => {
  const response = await axios.post('/api/kanban/comments/new', {
    cardId,
    message
  });

  dispatch(slice.actions.addComment(response.data));
};

export const addChecklist = (cardId, name) => async (dispatch) => {
  const response = await axios.post('/api/kanban/checklists/new', {
    cardId,
    name
  });
  const { checklist } = response.data;

  dispatch(slice.actions.addChecklist({
    cardId,
    checklist
  }));
};

export const updateChecklist = (cardId, checklistId, update) => async (dispatch) => {
  const response = await axios.post('/api/kanban/checklists/update', {
    cardId,
    checklistId,
    update
  });
  const { checklist } = response.data;

  dispatch(slice.actions.updateChecklist({
    cardId,
    checklist
  }));
};

export const deleteChecklist = (cardId, checklistId) => async (dispatch) => {
  await axios.post('/api/kanban/checklists/remove', {
    cardId,
    checklistId
  });

  dispatch(slice.actions.deleteChecklist({
    cardId,
    checklistId
  }));
};

export const addCheckItem = (cardId, checklistId, name) => async (dispatch) => {
  const response = await axios.post('/api/kanban/checkitems/new', {
    cardId,
    checklistId,
    name
  });
  const { checkItem } = response.data;

  dispatch(slice.actions.addCheckItem({
    cardId,
    checklistId,
    checkItem
  }));
};

export const updateCheckItem = (cardId, checklistId, checkItemId, update) => async (dispatch) => {
  const response = await axios.post('/api/kanban/checkitems/update', {
    cardId,
    checklistId,
    checkItemId,
    update
  });
  const { checkItem } = response.data;

  dispatch(slice.actions.updateCheckItem({
    cardId,
    checklistId,
    checkItem
  }));
};

export const deleteCheckItem = (cardId, checklistId, checkItemId) => async (dispatch) => {
  await axios.post('/api/kanban/checkitems/remove', {
    cardId,
    checklistId,
    checkItemId
  });

  dispatch(slice.actions.deleteCheckItem({
    cardId,
    checklistId,
    checkItemId
  }));
};

export default slice;
