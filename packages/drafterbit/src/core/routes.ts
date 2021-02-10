import Application from "../Application";

const { Router } = require('../index');
const path = require('path');
let router = new Router();
const matter = require('gray-matter');
const fs = require('fs');

const DEFAULT_TEMPLATE = 'default.html';

function resolveContentFile(ctx: any): string {
    let ctxPath = ctx.path;
    let contentRoot = path.join(ctx.app.dir, 'content');


    if (ctxPath === "/") {
        return path.join(contentRoot, 'index.md');
    }


    ctxPath = ctxPath.replace(/\/$/, "");
    let contentIndexDir = path.join(contentRoot, ctxPath, 'index.md');

    if (fs.existsSync(contentIndexDir)) {
        return contentIndexDir
    }

    let contentFilePath = path.join(contentRoot, `${ctxPath}.md`);

    if (fs.existsSync(contentFilePath)) {
        return contentFilePath;
    }

    return ""
}

router.get("main", "/(.*)", async (ctx: Application.Context, next: Application.Next) => {

    let contentFile: string = resolveContentFile(ctx);

    const marked = ctx.app.get('marked');
    if (contentFile === "") {
        ctx.status = 404;
        return next();
    }

    const file = matter.read(contentFile);
    let template = file.data.template || DEFAULT_TEMPLATE;

    let htmlContent = marked(file.content);

    let baseURL = ctx.app.options.base_url ?  ctx.app.config.get('base_url')
        : `${ctx.protocol}://${ctx.host}`;

    let data = {
        theme_url: `${baseURL}/themes/${ctx.app.theme}`,
        base_url: baseURL,
        app: {
            name: ctx.app.config.get('app_name'),
        },
        page: {
            title: file.data.title,
            content: htmlContent
        }
    };

    ctx.body =  ctx.app.render(template, data);
    return next();

});

module.exports = router;