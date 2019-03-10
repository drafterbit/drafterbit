const express = require('express');
const user = require('./user');
const content_type = require('./content_type');
const content = require('./content');
const api_key = require('./api_key');

let router = express.Router();
router.use(user);
router.use(content_type);
router.use(api_key);
router.use(content);

module.exports = router;