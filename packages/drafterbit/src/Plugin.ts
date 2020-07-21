import path from 'path';
import fs from 'fs';
import Application  from "./index"
import Config from "./Config";
import mongoose from 'mongoose';

class Plugin {

    _path: string = '';
    _app: any = null;

    /**
     *
     * @param app drafterbit app instance
     */
    constructor(app: any) {
        this._app = app;
    }

    /**
     *
     * @param p
     */
    setPath(p: string): void {
        this._path = p;
    }


    getPath(): string {
        return this._path;
    }

    /**
     * Load Routes to app
     */
    loadRoutes(): void {
        if (this.canLoad('routes')) {
            let routes = this.require('routes');
            this._app.use(routes);
        }
    }

    /**
     * Load commands to app
     */
    loadCommands() {
        if (this.canLoad('commands')) {
            let commands = this.require('commands');
            commands.map((c: any) => {
                this._app.get('cmd').command(c.command)
                    .description(c.description)
                    .action(c.createAction(this._app));
            });
        }
    }

    /**
     *
     * @param file
     * @returns {any}
     */
    require(file: string): any {
        let p: string = path.join(this._path, file);
        return require(p);
    }

    /**
     *
     * @param db
     */
    registerSchema(db: mongoose.Connection): any {}

    /**
     *
     * @param serverConfig
     * @returns {{}}
     */
    registerClientConfig(serverConfig: Config) {
        return {};
    }

    /**
     *
     * @returns {string|boolean}
     */
    getAdminClientSideEntry(): any {
        let entryPath = this._path+'/client-side/src/index.js';
        if (fs.existsSync(entryPath)) {
            return entryPath;
        }

        return false;
    }

    /**
     *
     * @param files
     * @returns {boolean}
     */
    canLoad(files: string) {
        let resolvingPath = path.join(this._path,files);
        try {
            require.resolve(resolvingPath);
            return true;
        } catch (e) {
            if (e instanceof Error && (e as any).code === 'MODULE_NOT_FOUND') {
                return false;
            } else {
                throw e;
            }
        }
    }

    /**
     *
     * @param filename
     * @returns {boolean}
     * @private
     */
    static _isRelative(filename: string) {
        return (filename.indexOf('./') === 0 || filename.indexOf('../') === 0);
    }

    /**
     *
     * @param filePath
     * @returns {boolean}
     */
    static isDTPlugin(filePath: string) {
        return (filePath.indexOf('drafterbit') === 0);
    }

    /**
     *
     * @param filePath
     * @param root
     * @returns {void | string|string|*}
     */
    static resolve(filePath: string, root: string) {
        if(Plugin.isDTPlugin(filePath)){
            return filePath.replace(/^drafterbit/gi, __dirname);
        }

        if(path.isAbsolute(filePath)) {
            return filePath;
        }

        if(Plugin._isRelative(filePath)) {
            return path.resolve(root, filePath);
        }

        try {
            return path.dirname(require.resolve(filePath));
        } catch (e) {
            if (e instanceof Error && (e as any).code === 'MODULE_NOT_FOUND') {
                return filePath;
            } else {
                throw e;
            }
        }
    }

    /**
     *
     * @param app
     * @returns {Promise<[unknown]>}
     */
    install(app: Application) {
        return Promise.all([]);
    }
}

export = Plugin