import { createStore, applyMiddleware } from  'redux';
import thunk from 'redux-thunk';
import createRootReducer from './reducers';

const middlewares = [thunk];

export default function storeFromState(defaultState, drafterbit) {
    return createStore(createRootReducer(drafterbit), defaultState, applyMiddleware(...middlewares));
}