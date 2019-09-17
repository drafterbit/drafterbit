import React from 'react';
import { Container, Card, CardBody, CardHeader } from 'reactstrap';

import './AuthCard.css';

class AuthCard extends React.Component {

    render() {
        return (
            <div className="h-100 my-login-page">
                <Container className="h-100">
                    <div className="row justify-content-md-center h-100">
                        <div className="auth_card-cardWrapper">
                            <div className={'brand auth_card-brandContainer'}>
                                <img className="auth_card-brandImg" src="/assets/img/dtlogo3-black.png" />
                                <small className="auth_card-versionBadge">alpha</small>
                            </div>
                            <Card className="fat">
		                            <CardHeader>
			                            {this.props.title}
		                            </CardHeader>
                                <CardBody>
                                    {this.props.children}
                                </CardBody>
                            </Card>
                            <div className="auth_card-loginFooter">
                                Copyright &copy; 2019 &mdash; drafterbit
                            </div>
                        </div>
                    </div>
                </Container>
            </div>
        );
    }
}

export default AuthCard;