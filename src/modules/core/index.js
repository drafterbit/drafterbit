
const webpack = require('webpack');
const express = require('express');
const webpackDevMiddleware = require('webpack-dev-middleware');
const createWebpackConfig = require('./client/webpack.config');
const routes = require('./routes');

class CoreModule {

    constructor(app) {

        app.on('boot', () => {
            
            this.webpackOutputPath = app._root+'/build';            
            app.use('/', express.static(this.webpackOutputPath));

            if(app.get('config').get('debug')) {
                let webpackConfig = this.prepareWebpackConfig(app, this.webpackOutputPath);
                const compiler = webpack(webpackConfig);
                app.use(
                    webpackDevMiddleware(compiler, {
                        publicPath: webpackConfig.output.publicPath,
                        writeToDisk: true
                    })
                );
            }
        });

        app.on('routing', function () {
            app.use(routes);
        });

        app.on('build', () => {

            let webpackConfig = this.prepareWebpackConfig(app, this.webpackOutputPath);
            const compiler = webpack(webpackConfig);

            compiler.run((err, stats) => {
                console.log('Webpack build done.');
                process.exit(0);
            });
        });
    }

    prepareWebpackConfig(app, webpackOutputPath) {

        let isProduction = (app.get('config').get('NODE_ENV') === 'production');

        let webpackConfig = createWebpackConfig({
            outputPath: webpackOutputPath,
            production: isProduction
        });

        webpackConfig.output.path = webpackOutputPath;

        // Insert module entries
        let clientEntryPoint = webpackConfig.entry.pop();
        app._modules.map(mo => {
            if(typeof mo.getAdminClientEntry == 'function') {
                webpackConfig.entry.push(mo.getAdminClientEntry());
            }
        });
        webpackConfig.entry.push(clientEntryPoint);
        return webpackConfig;
    }

    registerConfig(config) {
        config.use('core', {
            type: 'literal',
            store: {
                'admin.api_base_url': '/',
                'admin.api_key': ''
            }
        });
    }

    registerClientConfig(serverConfig) {
        return {
            apiBaseURL: serverConfig.get('admin.api_base_url'),
            apiKey: serverConfig.get('admin.api_key'),
        };
    }

    getAdminClientEntry() {
        return this._modulePath+'/client/src/modules/common/index.js';
    }
}

module.exports = CoreModule;