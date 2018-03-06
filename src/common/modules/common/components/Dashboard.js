import React from 'react';
import Layout from './Layout';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import translate from '../../../../translate';

class Dashboard extends React.Component {

		renderProjects() {
			const { projects } = this.props;
			let t = this.props.t;

			if(!projects.length) {
				return (
					<div className="col-4">
						{t('dashboard:no_project_text')} &nbsp;
					</div>
				)
			}

			return (
				<ul>
					{projects.map((item,i) => {
						return <li key={i}><Link to={`/project/${item.id}`}>{item.name}</Link></li>
					})}
				</ul>
			)
		}

    render() {
	    let t = this.props.t;

	    return (
		    <Layout title={t('dashboard:layout_title')}>
		        <div className="row justify-content-center mt-4">
			        {this.renderProjects()}
			        <br/>
				        <Link className="btn btn-primary" to="/project/new">{t('dashboard:add_project_btn_text')}</Link>
		        </div>
	        </Layout>
        );
    }
}

const mapStateToProps = (state) => {
	return {
		projects: state.project.projects
	};
};

export default translate(['dashboard'])(connect(mapStateToProps)(Dashboard));