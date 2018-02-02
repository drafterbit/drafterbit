const jss = require('./jss-config');
const {SheetsRegistry} = require('jss');

const sheets = new SheetsRegistry();
const sheet = jss.createStyleSheet(require('./src/common/modules/user/components/Login.style')).attach();
const sheet2 = jss.createStyleSheet(require('./src/common/modules/content/components/ContentEditor.style')).attach();
sheets.add(sheet);
sheets.add(sheet2);

console.log(sheets.toString());