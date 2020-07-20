// @ts-nocheck
let chai = require('chai');
let should = chai.should();
let expect = chai.expect;

import Config from '../src/Config';

describe('createConfig', () => {

    it('should load config from file', () => {
        let config = new Config(__dirname, {
            MONGODB_NAME: 'test_db_name'
        });
        expect(config.get('MONGODB_NAME')).to.equal('test_db_name');
    });

});