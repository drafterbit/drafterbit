export default function reducer(state = {}, action) {
    switch (action.type) {
	    case '@api_key/SET_API_KEYS':
		    return Object.assign({}, state, {
			    apiKeys: action.payload
		    });
    }

    return state;
}