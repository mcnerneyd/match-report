import React, { useState, useContext } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faChalkboardTeacher, faSearch, faSignOutAlt } from '@fortawesome/free-solid-svg-icons'
import { UserContext } from './App'

function Nav() {
    const user = useContext(UserContext)

    return <>
    <div id='user'>{user?.user}</div>
    <nav className="navbar navbar-dark bg-dark navbar-expand-lg fixed-top">
        <div className="navbar-brand"></div>
        <button type="button" className="navbar-toggler" data-toggle="collapse" data-target="#navBarDropdown" aria-expanded="false">
            <span className="navbar-toggler-icon"></span>
        </button>

        <div className="collapse navbar-collapse" id="navBarDropdown">
            <ul className="navbar-nav">
                <li className="nav-item">
                    <a className="nav-link" href="/cards/ui">Matches</a>
                </li>
                <li className="nav-item dropdown">
                    <a className="nav-link" href="#" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        Registration
                    </a>
                    <div className="dropdown-menu">
                        <a className="dropdown-item" href="/Registration">Registrations</a>
                        <a className="dropdown-item" href="/Registration/Info">Club Info</a>
                    </div>
                </li>
                <li className="nav-item dropdown">
                    <a href="#" className="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        Reports
                    </a>
                    <div className="dropdown-menu">
                        <a className="dropdown-item" href="/Report/Scorers">Top Scorers</a>
                        <a className="dropdown-item" href="/Report/Grid">Grids</a>
                        <a className="dropdown-item" href="/Report/Cards">Red/Yellow Cards</a>              
                        <a className="dropdown-item" href="/Report/Mismatch">Mismatch Results</a>
                        <a className="dropdown-item" href="/Report/RegSec">Anomalies</a>
                    </div>
                </li>
            </ul>

            <form id="search" className="form-inline mr-auto">
                <input type="search" className="form-control mr-sm-2" placeholder="Search Club, Competition, Date or Card/Fixture ID"/>
                <button className="btn btn-outline-info my-2 my-sm-0" type="submit"><FontAwesomeIcon icon={faSearch}/></button>
            </form>

            <ul className="navbar-nav">
                <li className="nav-item">
                    <a className="nav-link disabled" id="help-me">
                    <FontAwesomeIcon icon={faChalkboardTeacher}/> Help!
                    </a>
                </li>
                <li className="nav-item dropdown">
                    <a href="#" className="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        Admin
                    </a>
                    <div className="dropdown-menu">
                        <a className="dropdown-item" href="/competitions">Competitions</a>
                        <a className="dropdown-item" href="/clubs">Clubs</a>
                        <div className="dropdown-divider"></div>
                        <a className="dropdown-item" href="/fixtures">Fixtures</a>
                        <a className="dropdown-item" href="/fines">Fines</a>
                        <a className="dropdown-item" href="/users">Users</a>
                        <div className="dropdown-divider"></div>
                        <a className="dropdown-item" href="/Admin/Config">Configuration</a>
                        <a className="dropdown-item" href="/Admin/Log">System Log</a>
                    </div>
                </li>
                <li className="nav-item">
                    <a id="logout" className="nav-link" href="/Login"><FontAwesomeIcon icon={faSignOutAlt}/> Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    </>
}

export default Nav;
