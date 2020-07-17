import React  from 'react';
import Field from './Field';
import { Row, Col, Card, Form, Button, message, PageHeader, Select } from 'antd';
import withDrafterbit from '@drafterbit/common/client-side/withDrafterbit';
import styled from 'styled-components';

const FieldType = require('@drafterbit/common/FieldType');


const CardWrapper = styled.div`
    padding: 0 0 0 15px;
`;

// TODO move this to editor module
let richTextInitialValue = [
    {
        type: 'paragraph',
        children: [{ text: 'A line of text in a paragraph.' }],
    },
]

let testInitValue = [
    {
        type: "paragraph",
        html_text: "<p></p"
    }
];

class ContentEdit extends React.Component {

    formRef = React.createRef();
    state = {
        ctFields: [],
    };

    componentDidMount() {
        let params = this.props.match.params;
        let contentId = params.content_id;
        let typeName = params.type_name;

        let client = this.props.$dt.getApiClient();
        Promise.all([
            client.getType(typeName),
            client.getEntry(typeName, contentId)
        ]).then(resList => {
            let [ type, entry ] = resList;

            this.formRef.current.setFieldsValue(entry);

            this.setState({
                ctFields: type.fields,
                loading: false
            });

        });
    }

    renderRichText(f,i) {

        let editorValue = this.formRef.current.getFieldValue(f.name);

        return <Field value={editorValue} onChange={(value) => {
            this.formRef.current.setFieldsValue({
                [f.name]: value
            })

        }} key={i} field={f} />;
    }

    renderRelation(f,i) {
        return <Field key={i} field={f} />;
    }

    onFinish = values => {

        let params = this.props.match.params;
        let contentId = params.content_id;
        let typeName = params.type_name;

        let data = {};
        this.state.ctFields.map(f => {
            data[f.name] = values[f.name];
        });

        let client = this.props.$dt.getApiClient();
        client.updateEntry(typeName, contentId, data)
            .then(() => {
                message.success('Content successfully updated')
            });

    };

    render() {
        return (
            <>
            <Form
                ref={this.formRef}
                layout="vertical"
                initialValues={{
                    status: "draft"
                }}
                onFinish={this.onFinish} >
                <PageHeader
                    // onBack={() => window.history.back()}
                    title="Edit Entry"
                    // subTitle="This is a subtitle"
                    extra={[ <Button key="save" type="primary" htmlType="submit">Save</Button>]}
                >
                    <Row>
                        <Col span={16}>
                            <Card>
                                {this.state.ctFields.map((f,i) => {
                                    if (!f.show_in_form) {
                                        return
                                    }

                                    if(f.type_name === FieldType.RICH_TEXT) {
                                        return this.renderRichText(f,i)
                                    }

                                    if (FieldType.primitives().indexOf(f.type_name) !== -1) {
                                        return <Field key={i} field={f} />;
                                    }

                                    return this.renderRelation(f,i);
                                })}
                            </Card>
                        </Col>
                        <Col span={8}>
                            <CardWrapper>
                                <Card>
                                    <Form.Item label="Status" name="status">
                                        <Select >
                                            <Select.Option value="">Select Status</Select.Option>
                                            <Select.Option value="draft">Draft</Select.Option>
                                            <Select.Option value="published">Published</Select.Option>
                                        </Select>
                                    </Form.Item>
                                </Card>
                            </CardWrapper>
                        </Col>
                    </Row>
                </PageHeader>
            </Form>
            </>);
    }
}

export default withDrafterbit(ContentEdit);