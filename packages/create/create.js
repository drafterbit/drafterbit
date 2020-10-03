#!/usr/bin/env node

const path = require('path');
const fs = require('fs');
const mkdirp = require('mkdirp');
const execa = require('execa');

function log(...msg) {
    // eslint-disable-next-line no-console
    console.log(...msg);
}

function logError(...msg) {
    // eslint-disable-next-line no-console
    console.error(...msg);
}

function copy(srcDir, dstDir) {

    let list = fs.readdirSync(srcDir);
    let src, dst;

    function skip(file) {
        return /.*node_modules.*/.test(file) || /.env$/.test(file)
            || /package-lock.json$/.test(file);
    }

    list.forEach(function(file) {
        src = path.join(srcDir,file);
        dst = path.join(dstDir,file);

        if (skip(file)) {
            return;
        }

        let stat = fs.statSync(src);
        if (stat && stat.isDirectory()) {
            try {
                log('creating dir: ' + dst);
                fs.mkdirSync(dst);
            } catch(e) {
                log('cannot create dir: ' + dst);
                logError(e);
            }

            return copy(src, dst);
        }

        try {
            log('copying file: ' + dst);
            fs.writeFileSync(dst, fs.readFileSync(src));
        } catch(e) {
            log('could\'t copy file: ' + dst);
            logError(e);
        }
    });
}

function runInstall(wd) {
    execa.sync('npm', ['install'], {
        cwd: wd
    });
}

try {

    let destDir = process.cwd();
    let argv2 = process.argv[2];
    if (argv2 !== undefined) {
        destDir = path.join(process.cwd(), argv2);
        if (fs.existsSync(destDir)) {
            let fileList = fs.readdirSync(destDir);
            if (fileList.length >= 1) {
                logError(`directory ${destDir} is not empty`);
                process.exit(1)
            }
        } else {
            log('creating app dir', destDir);
            mkdirp.sync(destDir);
        }
    }

    let stubDir = __dirname;
    let stub = path.join(stubDir, 'app/.');

    copy(stub, destDir);
    runInstall(destDir)

} catch (e) {
    logError(e)
}