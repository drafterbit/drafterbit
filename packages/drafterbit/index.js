const fs = require('fs');
const Koa = require('koa');

const bodyParser = require('koa-bodyparser');
const cors = require('@koa/cors');
const Config = require('./Config');
const Module = require('./Plugin');
const commander = require('commander');
const winston = require('winston');
const mongoose = require('mongoose');

mongoose.set('useNewUrlParser', true);
mongoose.set('useFindAndModify', true);
mongoose.set('useUnifiedTopology', true);

class Application extends Koa {

    #plugins = [];
    #odmConnections = {};
    #odmDefaultConn = '_default';
    #odmConfig = {};
    projectDir = "";
    #services = [];
    #pluginPaths = [];

    /**
     *
     * @param options
     */
    constructor(options = {}) {
        super(options);
        this.#pluginPaths = options.plugins || [];
    }

    /**
     *
     * @param key
     * @param value
     */
    set(key, value){
        this.#services[key] = value;
    }

    /**
     *
     * @param key
     * @returns {*}
     */
    get(key){
        return this.#services[key];
    }

    /**
     *
     */
    build() {
        this.emit('build');
    }

    /**
     *
     * @returns {Array}
     */
    plugins() {
        return this.#plugins
    }

    /**
     *
     * @returns {Promise<[unknown]>}
     */
    install() {

        // TODO use mongoose transaction
        let installs = this.#plugins.map(m => {
            return m.install(this)
        });

        return Promise.all(installs)
            .then(() => {
                this.get('log').info("Installation Complete.");
                process.exit(0)
            })
            .catch(e => {
                this.get('log').error("Installation Failed.");
                process.exit(1)
            })
    }

    /**
     *
     */
    routing() {
        this.#plugins.map(m => {
            m.loadRoutes();
        });
    }

    /**
     *
     * @param rootDir
     * @returns {Application}
     */
    boot(rootDir) {

        this._booted = false;
        this.projectDir = rootDir;

        // build skeletons
        let options;
        let configFileName = 'config.js';
        let configFile = `${rootDir}/${configFileName}`;
        if (fs.existsSync(configFile)) {
            options = require(configFile);
        } else {
            options = {};
        }

        let config = new Config(rootDir, options);
        let logger = this.createLogger(config.get('DEBUG'));
        this.set('log', logger);
        this.set('config', config);

        this.#odmConfig[this.#odmDefaultConn] = {
            uri: config.get('MONGODB_URI'),
        };

        let cmd = commander;
        cmd
            .version('0.0.1')
            .option('-d, --debug', 'output extra debugging');

        this.set('cmd', cmd);

        // init plugins
        this.#plugins = this.#pluginPaths.map(m => {
            let modulePath = Module.resolve(m, this.projectDir);
            let ModulesClass = require(modulePath);
            let moduleInstance = new ModulesClass(this);
            moduleInstance.setPath(modulePath);

            let db = this.odm();
            moduleInstance.registerSchema(db);

            // register config
            if (moduleInstance.canLoad('config')) {
                config.registerConfig(moduleInstance.require('config'));
            }

            moduleInstance.loadCommands();

            return moduleInstance;
        });

        // Error handling
        this.use(async (ctx, next) => {
            try {
                await next();
            } catch (err) {
                ctx.status = err.status || 500;
                ctx.body = err.message;
                ctx.app.emit('error', err, ctx);
            }
        });

        this.use(cors({
            'origin': '*',
            'methods': 'GET,HEAD,PUT,PATCH,POST,DELETE',
            'exposedHeaders': 'Content-Range,X-Content-Range'
        }));

        this.use(bodyParser());

        this.emit('boot');

        this._booted = true;
        return this;
    }

    /**
     *
     * @param name
     */
    model(name) {
        return this.odm().model(name);
    }

    /**
     *
     * @param name
     * @returns {*}
     */
    odm(name) {

        name = name || this.#odmDefaultConn;
        let {
            uri
        } = this.#odmConfig[name];

        if(!this.#odmConnections[name]) {
            this.#odmConnections[name] = this.createODMConn(uri);
        }

        return this.#odmConnections[name];
    }

    /**
     *
     * @param uri
     * @returns {*}
     */
    createODMConn(uri) {

        let conn = mongoose.createConnection(uri, {
            connectTimeoutMS: 9000,
        }, err => {
            if(err) {
                this.get('log').error(err);
            }
        });

        conn.on('error', err => {
            if(err) {
                this.get('log').error(err);
            }
        });

        return conn;
    }

    /**
     *
     * @param debug
     * @returns {winston.Logger}
     */
    createLogger(debug) {

        // TODO add rotate file logger
        const logger = winston.createLogger({
            level: debug ? 'debug' : 'warn',
            format: winston.format.json(),
            transports: []
        });

        if (process.env.NODE_ENV !== 'production') {
            logger.add(new winston.transports.Console({
                format: winston.format.simple()
            }));
        }

        return logger;
    }

}

module.exports = Application;