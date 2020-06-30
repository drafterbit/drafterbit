import React, { lazy } from 'react';
import stateReducer from './stateReducer';
import { getCookie } from '../../../core/client/src/cookie';
import NavBarMenu from './components/NavBarMenu';

const Login = lazy(() => import('./components/Login'));
const Register = lazy(() => import('./components/Register'));
const ResetPassword = lazy(() => import('./components/ResetPassword'));
const ForgotPassword = lazy(() => import('./components/ForgotPassword'));
const RegisterSuccess = lazy(() => import('./components/RegisterSuccess'));

function createAuthClientModule() {

    return {
        name: "user",
        stateReducer: stateReducer,
        pageRoutes: [
            {path: "/login", component: Login},               
            {path: "/register", component: Register},
            {path: "/reset-password", component: ResetPassword},               
            {path: "/forgot-password", component: ForgotPassword},
            {path: "/register-success", component: RegisterSuccess}
        ],
        generalMenus: [
            {link: "/users", label: "Users", iconClass: "icon-user"}
        ],
        processRoute(route, location, state) {

            if(location.pathname === "/login") {
                return route;
            }

            if(!state.USER.token) {
                route.redirect = {
                    pathname: '/login',
                    state: {
                        referrer: route.location
                    }
                }
            }

            return route;
        },
        preRenderAction(state) {
            let t = getCookie('dt_auth_token');
            if (!!t) {
                // TODO get logged in user detail here
                // Send token to server to get user detail
                // And set to redux user
                state.USER.token = t;
            }
        },
        renderNavBarMenu(i) {
            return <NavBarMenu key={i} />
        }
    }
}

window.__DRAFTERBIT__.addModule(createAuthClientModule(window.__DRAFTERBIT__));