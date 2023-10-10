import React, { useContext } from 'react';
import Nav from 'react-bootstrap/Nav';
import Navbar from 'react-bootstrap/Navbar';
import NavDropdown from 'react-bootstrap/NavDropdown';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faChalkboardTeacher, faSearch, faSignInAlt, faSignOutAlt } from '@fortawesome/free-solid-svg-icons'
import { UserContext } from './Context'
import { Button, Form } from 'react-bootstrap';

function Navigat({search}) {

    const user = useContext(UserContext)
    const allowed = (perm) => user?.perms?.includes(perm)

    return <>
        <Navbar expand="lg" bg="dark" variant="dark">
            <Navbar.Brand>Leinster Hockey{user && user['section-title'] ? <span>{user['section-title']}</span> : null}</Navbar.Brand>
            <Navbar.Toggle aria-controls="basic-navbar-nav" />

            <Navbar.Collapse id="basic-navbar-nav" className="gap-5">
                <Nav>
                    <Nav.Link href="/cards/ui">Matches</Nav.Link>
                    {allowed("registration.view")
                    ? <NavDropdown title='Registration'>
                        <NavDropdown.Item href="/Registration">Registrations</NavDropdown.Item>
                        <NavDropdown.Item href="/Registration/Info">Club Info</NavDropdown.Item>
                    </NavDropdown>
                    : null}
                    <NavDropdown title='Reports'>
                        <NavDropdown.Item href="/Report/Scorers">Top Scorers</NavDropdown.Item>
                        <NavDropdown.Item href="/Report/Grid">Grids</NavDropdown.Item>
                        {allowed("umpire_reports.view")
                        ? <NavDropdown.Item href="/Report/Cards">Red/Yellow Cards</NavDropdown.Item>
                        : null}
                        {allowed("system_reports.view")
                        ? <>
                        <NavDropdown.Item href="/Report/Mismatch">Mismatch Results</NavDropdown.Item>
                        <NavDropdown.Item href="/Report/RegSec">Anomalies</NavDropdown.Item>
                        </>
                        : null}
                    </NavDropdown>
                </Nav>
                <Form className="d-flex me-auto flex-grow-1">
                    <Form.Control
                        type="search"
                        placeholder="Search Club, Competition, Date or Card/Fixture ID"
                        className="me-2"
                        onChange={e => {
                            const v = e.target.value.toLowerCase().split(/[^a-z0-9]/g).filter(x => x !== "")

                            if (v.length === 0) search(null)
                            else search(v)
                        }}
                    />
                    <Button variant="outline-success"><FontAwesomeIcon icon={faSearch} /></Button>
                </Form>
                <Nav>
                    <Nav.Link href="/help" id='help-me'>
                        <FontAwesomeIcon icon={faChalkboardTeacher} /> Help!
                    </Nav.Link>
                    {allowed("configuration.view")
                    ? <NavDropdown title='Admin'>
                        <NavDropdown.Item href="/competitions">Competitiions</NavDropdown.Item>
                        <NavDropdown.Item href="/clubs">Clubs</NavDropdown.Item>
                        <NavDropdown.Item href="/fixtures">Fixtures</NavDropdown.Item>
                        <NavDropdown.Divider/>
                        <NavDropdown.Item href="/fines">Fines</NavDropdown.Item>
                        <NavDropdown.Item href="/users">Users</NavDropdown.Item>
                        <NavDropdown.Divider/>
                        <NavDropdown.Item href="/Admin/Config">Configuration</NavDropdown.Item>
                        <NavDropdown.Item href="/Admin/Log">System Log</NavDropdown.Item>
                    </NavDropdown>
                    : null}
                    <Nav.Link href='/Login'>
                    {user == null 
                    ? <><FontAwesomeIcon icon={faSignInAlt} /> Login</>
                    : <><FontAwesomeIcon icon={faSignOutAlt} /> Logout</>}
                    </Nav.Link>
                </Nav>
            </Navbar.Collapse>
        </Navbar>
    </>
}

export default Navigat;
