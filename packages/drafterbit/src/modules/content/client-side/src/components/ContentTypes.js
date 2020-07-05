import React, { Fragment } from 'react';
import { Link } from 'react-router-dom';
import withDrafterbit from '@drafterbit/common/client-side/withDrafterbit';
import TypeForm from './TypeForm';
import { setNotifyText }  from '../actions'
import { connect } from 'react-redux'
import { bindActionCreators } from 'redux';
import TablePage from '@drafterbit/common/client-side/components/TablePage';

class ContentTypes extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            newFormOpen: false,
            contentTypes: []
        };
    }

    loadContents = () => {
        let client = this.props.drafterbit.getApiClient();
        return client.getContentTypes()
            .then((contentTypes) => {
                this.setState({
                    contentTypes: contentTypes
                });
            })
    };

    onClickAdd = () => {
        this.setState({
            newFormOpen: true
        })
    };

    render() {

        const columns = [{
            dataField: 'name',
            dataIndex: 'name',
            text: 'Name',
            title: 'Name',
            formatter: (cell, row) => {
                return <Link to={`/content_types/${row._id}`}>{cell}</Link>;
            }
        }];

        return (
            <Fragment>
                <TablePage
                    headerText="Content Types"
                    data={ this.state.contentTypes }
                    contentCount={this.state.contentCount}
                    columns={ columns }
                    select={true}
                    loadContents={this.loadContents}
                    handleDelete={this.handleDelete}
                    onClickAdd={this.onClickAdd}
                />
                <TypeForm visible={this.state.newFormOpen}
                    onCancel={e => {
                        this.setState({
                            newFormOpen: false  
                        })
                    }}
                    onSuccess={contentType => {
                        this.props.actions.setNotifyText("Content Type Saved Successfully !");
                        this.setState({
                            newFormOpen: false
                        })
                        setTimeout(() => {
                            this.props.history.push(`/content_types/${contentType._id}`);
                        }, 2000)
                    }}
                 />
            </Fragment>
        );
    }
}



const mapDispatchToProps = (dispatch) => {
    return {
        actions: bindActionCreators({
            setNotifyText
        }, dispatch)
    };
};

export default connect(null, mapDispatchToProps)(withDrafterbit(ContentTypes));