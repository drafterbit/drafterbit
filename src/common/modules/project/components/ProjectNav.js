import React from 'react';
import { Link } from 'react-router-dom';
import Style from './ProjectNav.style';
import withStyle from '../../../withStyle';
import { withRouter } from 'react-router';

class ProjectNav extends React.Component {
	render() {

		let { classNames, match } = this.props;

		return (
			<nav className={`col-md-2 d-none d-md-block bg-light ${classNames.sidebar}`}>
				<div className={classNames.sidebarSticky}>
					<h6 className={`${classNames.sidebarHeading} d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted`}>
						<span>General</span>
						<Link className="d-flex align-items-center text-muted" to="/project/projectId/content-types/new">
							<span data-feather="plus-circle"/>
						</Link>
					</h6>
					<ul className="nav flex-column">
						<li className="nav-item">
							<Link className="nav-link" to={`/project/${match.params.project_id}`}>
								<span data-feather="home"/>
								Dashboard <span className="sr-only">(current)</span>
							</Link>
						</li>
					</ul>

					<h6 className={`${classNames.sidebarHeading} d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted`}>
						<span>Content</span>
						<Link className="d-flex align-items-center text-muted" to="/project/projectId/content-types/new">
							<span data-feather="plus-circle"/>
						</Link>
					</h6>
					<ul className="nav flex-column mb-2">
						<li className="nav-item">
							<Link className="nav-link" to="/project/projectId/content-types/ctId">
							<span data-feather="file-text"/>
								Article
							</Link>
						</li>
					</ul>

					<h6 className={`${classNames.sidebarHeading} d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted`}>
						<span>Setting</span>
						<Link className="d-flex align-items-center text-muted" to="/project/projectId/content-types/new">
							<span data-feather="plus-circle"/>
						</Link>
					</h6>
					<ul className="nav flex-column mb-2">
						<li className="nav-item">
							<Link className="nav-link" to="/project/projectId/content-types/ctId">
								<span data-feather="file-text"/>
								Content Types
							</Link>
						</li>
						<li className="nav-item">
							<Link className="nav-link" to="/project/projectId/content-types/ctId">
								<span data-feather="file-text"/>
								Credentials
							</Link>
						</li>
					</ul>
				</div>
			</nav>
		);
	}
}

export default withRouter(withStyle(Style)(ProjectNav));