import React from 'react';
import Layout from '../../common/components/Layout';
import ProjectNav from './ProjectNav';
import actions from '../actions';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

class ProjectLayout extends React.Component {

	componentDidMount(){
		//TODO check if this is server-preloaded
		this.props.getProject(this.props.match.params.project_id);
	}

	render() {

		return (
			<Layout title={this.props.titel}>
				<ProjectNav />
				<main role="main" className={`col-md-9 ml-sm-auto col-lg-10 pt-3 px-4`}>
					<h2>{this.props.title}</h2>
					{this.props.children}
				</main>
			</Layout>
		);
	}
}

Layout.defaultProps = {
	title: "Untitled Page"
};

const mapStateToProps = (state) => {
	return {
		project: state.project.project
	};
};

const mapDispatchToProps = (dispatch) => {
	return bindActionCreators(actions, dispatch);
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(ProjectLayout));