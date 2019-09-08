const path = require('path');
const nconf = require('nconf');
const _ = require('lodash');
const admin = require('./modules/admin');
const swagger = require('./modules/swagger');
const content = require('./modules/content');

/**
 *
 * @param options
 * @return {Provider}
 */
function createConfig(options) {

    const defaultConfig = {
        "DEBUG": true,
        "PORT": 3000,
        "SESSION_SECRET": "secr3t",
        "DOCS_VERSION": "1.0",
        "DOCS_TITLE": "Drafterbit",
        "REDIS_HOST": "localhost",
        "REDIS_PORT": 6379,
        "REDIS_DB": 0,
        "MAILJET_APIKEY_PUBLIC": "",
        "MAILJET_APIKEY_PRIVATE": "",
        "MONGODB_PROTOCOL": "mongodb+srv",
        "MONGODB_URL": "",
        "MONGODB_HOST": "",
        "MONGODB_PORT": "",
        "MONGODB_USER": "",
        "MONGODB_PASS": "",
        "ADMIN_API_KEY": "test",
        "modules": [
            './src/modules/content'
        ]
    };

    if (typeof options == "string") {
        options = require(options);
    }

    let config = _.merge(defaultConfig, options);

    nconf
        .env([
            'DEBUG',
            'PORT',
            'SESSION_SECRET',
            'NODE_ENV',
            'PORT',
            'REDIS_HOST',
            'REDIS_PORT',
            'REDIS_DB',
            'REGISTERED_API_KEY_LIST',
            'IMG_BASE_URL',
            'MAILJET_APIKEY_PUBLIC',
            'MAILJET_APIKEY_PRIVATE',
            'API_BASE_URL',
            'MONGODB_URL',
            'MONGODB_HOST',
            'MONGODB_PORT',
            'MONGODB_USER',
            'MONGODB_PASS',
            'ADMIN_API_KEY',
            'BASIC_AUTH_USER',
            'BASIC_AUTH_PASS',
            'modules'
        ])
        .defaults(config);

    return nconf;
}


module.exports = createConfig;
