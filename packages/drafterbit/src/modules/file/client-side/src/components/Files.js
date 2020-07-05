import path from 'path';
import React, {Fragment} from 'react';
import withDrafterbit from '@drafterbit/common/client-side/withDrafterbit';
import TablePage from '@drafterbit/common/client-side/components/TablePage';
import ApiClient from '../ApiClient'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faFolder, faFile} from '@fortawesome/free-regular-svg-icons';
import {Link} from 'react-router-dom';
import querystring from "querystring";
import DropZone from './DropZone';

class Files extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            files: []
        }
    }

    loadContents = (match, page, sortBy, sortDir, fqStr, qs) => {

        let client = this.props.drafterbit.getApiClient();
        let path = qs['path'] || "/";
        return client.getFiles(path)
            .then(files => {
                this.setState({
                    files
                })
            })
    };

    fileDidUpload = (r) => {

        let qs = querystring.parse(this.props.location.search.substr(1));
        let sortBy = qs['sort_by'];
        let sortDir = qs['sort_dir'];
        let fqStr = qs['fq'];
        let page = qs['page'];

        this.loadContents(this.props.match, page, sortBy, sortDir, fqStr, qs)
    };

    render() {

        const columns = [{
            dataField: 'text',
            dataIndex: 'text',
            text: 'Name',
            title: 'Name',
            render: (cell, row) => {
                if (row.type === "dir") {
                    return <span><Link to={`/files?path=${row.path}`}>
                        <FontAwesomeIcon icon={faFolder} /> {cell}
                    </Link></span>;
                }

                return <span><FontAwesomeIcon icon={faFile} /> {cell}</span>;
            }
        }];

        let qs = querystring.parse(this.props.location.search.substr(1));
        let paths = [{
            label: "Files",
            path: "/files?path=/"
        }];

        let uploadPath = "/";
        if (qs['path']) {
            uploadPath = qs['path'];
            let restPath = decodeURIComponent(qs['path']).split("/").filter( p => !!p);
            let cp = "";
            let ps = [];
            restPath.forEach(p => {
                cp = path.join(cp, p);
                ps.push({
                    label: p,
                    path: `/files?path=${cp}`
                })
            });
            paths = paths.concat(ps);
        }

        return (
            <Fragment>
                <DropZone path={uploadPath} fileDidUpload={this.fileDidUpload}/>
                {paths.map((p,i) => {
                    return <span key={i}> <Link to={p.path}>{p.label.trim()}</Link> / </span>
                })}
                <TablePage
                    headerText="Files"
                    data={ this.state.files }
                    contentCount={0}
                    columns={ columns }
                    // select={true}
                    loadContents={this.loadContents}
                    // handleDelete={this.handleDelete}
                    // onClickAdd={this.onClickAdd}
                    render={(filter, table, pagination) => {
                        return(
                            <div>
                                <div className="mb-2"/>
                                {filter}

                                <div className="mb-2"/>
                                {table}
                                {pagination}
                            </div>
                        )
                    }}
                />
            </Fragment>
        );
    }
}


export default withDrafterbit(Files);