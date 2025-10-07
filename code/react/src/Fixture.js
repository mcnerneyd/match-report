import React, { useState, useContext, useRef } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faCheck, faPaperclip, faPlus, faBan, faEraser, faStickyNote, faSignature, faUserPlus } from '@fortawesome/free-solid-svg-icons'
import Button from 'react-bootstrap/Button'
import ButtonGroup from 'react-bootstrap/ButtonGroup'
import Modal from 'react-bootstrap/Modal'
import { Players } from './Players'
import { UserContext } from './Context'
import { Form, ToggleButton } from 'react-bootstrap';
import { addPlayer } from './Api.js'
import { toast } from 'react-toastify'

const penalties = {
    "green": "Green Card",
    "yellow": {
        "name": "Yellow Card",
        "values": [
            "Technical - Breakdown",
            "Technical - Delay/Time Wasting",
            "Technical - Dissent",
            "Technical - Foul/Abusive Language",
            "Technical - Bench/Coach/Team Foul",
            "Technical - Tackle",
            "Technical - Dangerous/Reckless Play",
        ]
    },
    "red": "Red Card"
}
const roles = {}
{
    const rolesList = {
        "captain": "Capt",
        "goalkeeper": "GK",
        "manager": "Mgr",
        "physio": "Phys"
    }
    Object.keys(rolesList).forEach(k => {
        const v = rolesList[k]
        roles[k] = {
            full: k,
            short: v,
            initials: v.replaceAll(/[^A-Z]/g, "") 
        }
    })
}
const getRole = (key) => {
    return roles[Object.keys(roles).find(role => role === key || roles[role].initials === key || roles[role].initials[0] === key)]
}
console.log("Roles", roles)

export function Fixture({ card, close, updateCard }) {
    const user = useContext(UserContext)

    const [player, setPlayer] = useState(null)
    const [addingPlayer, setAddingPlayer] = useState(false)
    const addFormRef = useRef()

    if (card == null) {
        console.warn("No card specified");
        return null;
    }

    card.user = "none"
    if (user?.club === card.home_name || user?.club === card.away_name) {
        card.user = user.club === card.home_name ? 'home' : 'away'
    }
    console.log("Card:", card)

    if (card.user !== 'none' && card[card.user].players.length === 0 && card.players.values.length > 0) {
        return <Players card={card} onClose={(p) => {
            card[card.user].players = p
            updateCard(card)
        }} />
    }

    const renderTeam = (team, side, active) => {
        console.log("Team", team)
        return <table className="team-table">
            <thead>
                <tr key={side + "-head"}>
                    <th colSpan={100}>{team.club} {team.team} <div className='scores'><span>{team.goals}</span><span>0</span></div></th>
                </tr>
            </thead>
            <tbody>
                {team.players.map(player => {
                    const score = team.scorers[player.name]
                    return <tr className='player' key={player.name} onClick={() => active && setPlayer(player)}>
                        <th>{player.number}</th>
                        <td>{player.firstname}</td>
                        <td>{player.lastname}
                            <div className='player-annotations'>
                                {player?.detail?.cards?.map(x => {
                                    const ps = x.split(" ", 1)[0].toLowerCase()
                                    return <div className={"card-penalty card-" + ps}/>                                    
                                })}
                                {player?.detail?.roles?.map(x => {
                                    const r = getRole(x)
                                    console.debug("Role", r, x)
                                    return <div className={"role role-" + r.full}>{r.initials}</div>})
                                }
                                {score ? <span className='score'>{score}</span> : null}
                            </div>
                        </td>
                    </tr>
                })}
                {Array.from({ length: 16 - team.players.length }, (_, index) =>
                    <tr className='filler' key={'filler-' + side + "-" + index}><td colSpan={4}>&nbsp;</td></tr>)}
            </tbody>
        </table>
    }

    const addPlayerDialog = () => {
        const handleSubmit = (club, side) => (e) => { 
            e.preventDefault()
            console.log("submit", e, club)
            setAddingPlayer(false)

            const playerList = (e.currentTarget.form[0].value + "\n" + e.currentTarget.form[1].value)
                .split("\n")
                .filter(x => x.trim() !== "")
            console.info("Players to add", playerList, card)
            playerList.forEach( newPlayer => {
                addPlayer(club, card.id, newPlayer)
                .then(f => f.json())
                .then(f => {
                    console.log("New Player:", newPlayer, f)
                    card[side].players = [...card[side].players, f]
                    updateCard(card)
                })
                .catch(e => { console.error("Err", e); toast.error("Unable to add " + newPlayer + " to team") })
            })
        }
        return <div className="modal show" style={{ display: 'block', position: 'initial' }}>
            <Modal show={addingPlayer} onHide={() => setAddingPlayer(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Add a Player</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form ref={addFormRef}>
                        <Form.Control as="select">
                            <option key='addplayer-selectnone' value={""}>Select a player&hellip;</option>
                            {card.players?.values.map(x => 
                                <option key={x.name} value={x.name}>{x.lastname + ", " + x.firstname}</option>)}
                        </Form.Control>
                        <p>or</p>
                        <div>
                            <label>Name</label>
                            <textarea name='player' className='form-control'></textarea>
                        </div>
                        {user?.roles.includes('Administrators') 
                        ? <>
                            <Button variant='success' type='submit' onClick={handleSubmit(card.home.club, 'home')}>
                                <FontAwesomeIcon icon={faPlus} /> Add Home
                            </Button>
                            <Button variant='success' type='submit' onClick={handleSubmit(card.away.club, 'away')}>
                                <FontAwesomeIcon icon={faPlus} /> Add Away
                            </Button>
                        </>
                        : <Button variant='success' type='submit' onClick={handleSubmit(user.club, card.user)}><FontAwesomeIcon icon={faPlus} /> Add</Button>
                            }
                    </Form>
                </Modal.Body>
            </Modal>
        </div>
    }

    const editPlayerDialog = (player) => {
        const save = () => {
            updateCard(card)
            console.log("Save card", card)
            setPlayer(null)
        }
        const team = card[card.user]
        const setScore = (s) => {
            team.scorers[player.name] = s
            card[card.user].goals = Object.values(team.scorers).reduce((a, x) => a + x, 0)
            save()
        }
        const setPenalty = (e) => {
            console.log("Penalty", e.target.value)
            if (player?.detail?.cards === undefined) player.detail.cards = []
            if (e.target.value === 'no-card') player.detail.cards = []
            else player.detail.cards = [...player.detail.cards, e.target.value]
            save()
        }
        const removePlayer = () => {
            team.players = team.players.filter(x => x !== player)
            save()
        }
        const toggleRole = (role) => {
            if (player?.detail?.roles === undefined) player.detail.roles = []
            console.log("Toggle", player, player.detail?.roles, role)
            if (player.detail?.roles?.includes(role)) 
                player.detail.roles = player.detail.roles.filter(x => x !== role)
            else 
                player.detail.roles = [...player.detail?.roles, role]
            save()
        }
        return <div className="modal show" style={{ display: 'block', position: 'initial' }}>
            <Modal show={player != null ? true : false} onHide={() => { setPlayer(null); }}>
                <Modal.Header closeButton>
                    <Modal.Title>{player.firstname} {player.lastname}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Button className='w-100' variant='danger' size='lg' onClick={removePlayer}>Remove Player</Button>

                    <hr />

                    <div id='set-number'>
                        <label>Shirt Number</label>
                        <div className='input-group'>
                            <input type='number' onChange={(e) => player.number = e.target.value} name='shirt-number' className='form-control' />
                            <Button variant='success' onClick={() => save()}>
                                <FontAwesomeIcon icon={faCheck} />
                            </Button>
                        </div>
                    </div>

                    <hr />

                    <label>Role</label>
                    <ButtonGroup id='select-role' className='w-100'>
                        {Object.keys(roles).map(k => {
                            return <ToggleButton key={k} className={'btn btn-xs role-' + k}
                                onClick={() => toggleRole(k)}
                                value={player.detail?.roles?.includes(k)}>{getRole(k).short}</ToggleButton>
                        })}
                    </ButtonGroup>

                    <hr />

                    <div className='form-group' id='card-addx'>
                        <label>Add Penalty Card</label>
                        <select className='form-control' id='card-add' onChange={(e) => setPenalty(e)}>
                            <option>Select card to add&hellip;</option>
                            <option value='no-card' style={{ color: "blue", fontWeight: "bold" }}><FontAwesomeIcon icon={faEraser} /> Clear Cards</option>
                            {Object.keys(penalties).map(k => {
                                const v = penalties[k]
                                return typeof v === 'object'
                                    ? <optgroup label={v.name}>
                                        {v.values.map(n => <option value={v.name + "!" + n} className={"card-" + k}>{n}</option>)}
                                    </optgroup>
                                    : <option value={v} className={"card-" + k}>{v}</option>
                            })
                            }
                        </select>
                    </div>

                    <hr />

                    <ButtonGroup className='w-100'>
                        <Button variant='success' onClick={() => setScore((team.scorers[player.name] || 0) + 1)}>
                            <FontAwesomeIcon icon={faPlus} /> Add Goal
                        </Button>
                        <Button variant='warn' onClick={() => setScore(0)}>
                            <FontAwesomeIcon icon={faBan} /> Clear Goals
                        </Button>
                    </ButtonGroup>
                </Modal.Body>
            </Modal>
        </div>
    }

    return <>
        <div id='match-card'>
            <h1 id='competition'>{card.competition}</h1>
            <div className='detail'>
                <dl>
                    <dt>Fixture ID</dt>
                    <dd>{card.fixture_id}</dd>
                </dl>
                <dl>
                    <dt>Card ID</dt>
                    <dd>{card.id}</dd>
                </dl>
                <dl>
                    <dt>Date</dt>
                    <dd>{card.date.format("YYYY-MM-DD")}</dd>
                </dl>
                <dl>
                    <dt>Time</dt>
                    <dd>{card.date.format("HH:mm")}</dd>
                </dl>
                <a href={window.location.origin + `/card/${card.fixture_id}`} title='Permalink'>
                    <FontAwesomeIcon icon={faPaperclip} />
                </a>
            </div>

            <div id='teams'>
                <div id='matchcard-home' className={'team ' + (card.user === 'home' ? "ours" : "theirs")}>
                    {renderTeam(card.home, 'home', card.user === 'home')}
                </div>
                <div id='matchcard-away' className={'team ' + (card.user === 'away' ? "ours" : "theirs")}>
                    {renderTeam(card.away, 'away', card.user === 'away')}
                </div>
            </div>
            {player
                ? editPlayerDialog(player)
                : null}
            {addingPlayer
                ? addPlayerDialog()
                : null}
        </div>
        <form id="submit-card">
            <Button onClick={close} variant='success'>
                <FontAwesomeIcon icon={faSignature} /> Submit Card
            </Button>
            <span className='spacer' />
            <Button variant='danger' onClick={() => setAddingPlayer(true)} tabIndex={20}>
                <FontAwesomeIcon icon={faUserPlus} /> Add Player
            </Button>
            <Button variant='primary'>
                <FontAwesomeIcon icon={faStickyNote} /> Add Note
            </Button>
        </form>
    </>
}
