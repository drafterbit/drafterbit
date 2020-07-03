import React from 'react';
import ReactDOM from 'react-dom';
import moment from 'moment';
import i18next from 'i18next';
import Shell from './components/Shell';
import storeFromState  from './storeFromState';
import createDefaultState from './createDefaultState';
import getConfig from './getConfig';

// CSS dependency first
import 'simple-line-icons/css/simple-line-icons.css';
import 'bootstrap/dist/css/bootstrap.css';
import './index.css';

// TODO
const drafterbit = window.$dt;

drafterbit.initApiClient();

const i18n = i18next.createInstance();
i18n.init({
    lng: 'id',
    fallbackLng: 'en',
    debug: !!parseInt(getConfig("debug")),
    resources: [],
});

moment.locale('id', {
    months: 'januari_februari_maret_april_mei_juni_juli_agustus_september_oktober_november_desember'.split('_'),
    monthsShort: 'jan_feb_mar_apr_mei_jun_jul_agu_sep_okt_nov_des'.split('_')
});

let languageContext = {namespaces: [], i18n};

let defaultState = createDefaultState(drafterbit);

let preRenderActions =  drafterbit.modules.map(mo => {
    if(typeof mo.preRenderAction === "function") {
        return mo.preRenderAction(defaultState);
    }
}).filter(i => !!i);

Promise.all(preRenderActions)
    .then(() => {
        renderApp(defaultState);
    })
    .catch(e => {
        console.error(e);
        let message = "Oops, Please try again in few minutes";
        ReactDOM.render(<div style={{margin: "25px"}}>{ message }</div>, document.getElementById('app'));
    });

function renderApp(dState) {

    const store = storeFromState(dState, drafterbit);
    drafterbit.store = store;

    ReactDOM.render(
        <Shell
                store={store}
                drafterbit={drafterbit}
                languageContext={languageContext} />, document.getElementById('app'));
}