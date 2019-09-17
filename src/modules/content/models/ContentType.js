const mongoose = require('mongoose');


let ContentTypeSchema = new mongoose.Schema({
    name: String,
    slug: String,
    description: String,
    is_structured: false,
    fields: [{
        related_content_type_slug: String,
        type_id: Number,
        name: String,
        label: String,
        validation_rules: String
    }]
});

/**
 *
 * @param contentTypeId
 * @param field
 * @return {Promise}
 */
ContentTypeSchema.statics.addField = function(contentTypeId, field) {
    return new Promise((resolve, reject) => {

        this.update({ _id: contentTypeId }, { $push: { fields: field } }, function(err, res) {
            if (err) return reject(err);
            return resolve(res);
        });
    });
};


/**
 * @param contentTypeId
 * @return {Promise}
 */
ContentTypeSchema.statics.getContentType = function(contentTypeId) {
    return new Promise((resolve, reject) => {

        let ObjectId = mongoose.Types.ObjectId;
        let condition;
        if(ObjectId.isValid(contentTypeId)) {
            condition = {_id: contentTypeId};
        } else {
            condition = {slug: contentTypeId};
        }

        this.findOne(condition, function(err, contentType) {
            if (err) return reject(err);
            return resolve(contentType);
        });
    });
};


/**
 * @return {Promise}
 */
ContentTypeSchema.statics.getContentTypes = function() {
    return this.find().select(['-__v']).exec();
};


/**
 *
 * @param slug
 * @return {Promise}
 */
ContentTypeSchema.statics.getContentTypeBySlug = function(slug) {
    return new Promise((resolve, reject) => {

        this.findOne({slug: slug}, function(err, contentType) {
            if (err) return reject(err);
            return resolve(contentType);
        });

    });
};


/**
 *
 * @param name
 * @param slug
 * @param description
 * @param fields
 * @return {Promise}
 */
ContentTypeSchema.statics.createContentType = function(name, slug, description, fields) {

    return new Promise((resolve, reject) => {

        let newContentType = new this({
            name,
            slug,
            description,
            fields: fields,
        });

        newContentType.save((err, newContentType) => {
            if (err) return reject(err);
            resolve(newContentType);
        });

    });
};


/**
 *
 * @param contentTypeId
 * @return {Promise}
 */
ContentTypeSchema.statics.deleteContentType = function(contentTypeId) {
    return new Promise((resolve, reject) => {
        this.deleteOne({_id: contentTypeId}, function(err) {
            if (err) return reject(err);
            return resolve(true);
        });
    });
};


/**
 *
 * @param contentTypeId
 * @param payload
 * @return {Promise}
 */
ContentTypeSchema.statics.updateContentType = function(contentTypeId, payload) {
    return new Promise((resolve, reject) => {

        this.updateOne({ _id: contentTypeId }, payload, function(err, res) {
            if (err) return reject(err);
            return resolve(res);
        });
    });
};


/**
 *
 * @param contentTypeId
 * @param fieldId
 * @param payload
 * @return {Promise}
 */
ContentTypeSchema.statics.updateContentTypeField = function(contentTypeId, fieldId, payload) {
    return new Promise((resolve, reject) => {

        let setter = {};
        for (let k of Object.keys(payload)) {
            setter[`fields.$.${k}`] = payload[k];
        }

        this.updateOne({ _id: contentTypeId, 'fields._id': fieldId }, {
            $set: setter
        }, function(err, res) {
            if (err) return reject(err);
            return resolve(res);
        });
    });
};

module.exports = ContentTypeSchema;