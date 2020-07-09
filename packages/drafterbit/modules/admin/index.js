const path = require('path');
const webpack = require('webpack');
const serve = require('koa-static');
const createWebpackConfig = require('./client-side/webpack.config');
const routes = require('./routes');
const Module = require('../../Module');
const SettingSchema = require('./models/Setting');
const koaWebpack = require('koa-webpack');

class CoreModule extends Module {

    constructor(app) {
        super(app);

        this.webpackOutputPath = path.join(app.get('config').get('ROOT_DIR'), 'build');
        app.use(serve(this.webpackOutputPath));

        app.on('pre-start', async () => {
            if(app.get('config').get('NODE_ENV') !== 'production') {
                //
                let webpackConfig = this.prepareWebpackConfig(app, this.webpackOutputPath);
                const compiler = webpack(webpackConfig);
                const middleware = await koaWebpack({
                    compiler,
                    devMiddleware: {
                        publicPath: webpackConfig.output.publicPath,
                        writeToDisk: true
                    }
                });
                app.use(middleware);
            }

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
            production: isProduction,
            projectRoot: app.get('config').get('ROOT_DIR')
        });

        webpackConfig.output.path = webpackOutputPath;

        // Insert module entries
        let clientEntryPoint = webpackConfig.entry.pop();
        console.log('Number of modules:', app._modules.length);
        app._modules.map(mo => {
            if(typeof mo.getAdminClientSideEntry == 'function') {
                let entry = mo.getAdminClientSideEntry();
                if (!!entry) {
                    webpackConfig.entry.push(entry);
                }
            }
        });
        webpackConfig.entry.push(clientEntryPoint);
        return webpackConfig;
    }

    registerClientConfig(serverConfig) {
        return {
            apiBaseURL: serverConfig.get('API_BASE_URL'),
            apiKey: serverConfig.get('API_KEY'),
        };
    }

    getAdminClientSideEntry() {
        return false
    }

    registerSchema(db) {
        db.model('Setting', SettingSchema, '_settings');
    }
}

module.exports = CoreModule;