import React, { useEffect, useState, useContext } from 'react';
import { UserContext } from './App'
import './App.scss';
import './playerselect.scss';
import moment from 'moment';

const PlayerSelect = (props) => {

    const user = useContext(UserContext)

    const [viewState, setViewState] = useState('all')
    const [allPlayers, setAllPlayers] = useState([])
    const [selectedPlayers, setSelectedPlayers] = useState([])

    useEffect(() => fetch(`http://cards.leinsterhockey.ie/api/registration/list.json?s=${user.section}&t=${props.team}`,{
            headers: {'X-Auth-Token': `${sessionStorage.getItem("jwtToken")}`}})
        .then((res) => res.json())
        .then((data) => {
            const players = data.map(x => {
                const dates = x.history.map(y => moment(y.date))
                x['lastDate'] = Math.min(...dates)
                return x
            })
            setAllPlayers(players)
        }), [])
    

    const players = [...allPlayers]

    if (viewState == 'all') {
        players.sort((a,b) =>{return a.name.localeCompare(b.name)})
    } else if (viewState == 'recent') {
        players.sort((a,b) => {return a.lastDate ? (a.lastDate - b.lastDate) : 1})
    }

    const addPlayersText = () => {
        if (selectedPlayers.length == 0) return "Add players"
        if (selectedPlayers.length == 1) return "Add 1 player"
        return "Add " + selectedPlayers.length + " players"
    }

    return <div id='player-select'>
        <div className='buttons-bar'>
            <button className='success' disabled={!selectedPlayers}>{addPlayersText()}</button>
            <button className='warning' onClick={() => props.setKicker(false)}>Cancel</button>
        </div>

        <div className='buttons-bar'>
            <button onClick={() => setViewState('all')}>All (A-Z)</button>
            <button onClick={() => setViewState('recent')}>Recent</button>
        </div>

        <div className='players'>
        { viewState == 'free' 
            ? <>
                <label>Player Name <input type='text'/></label>
                <button>Add Player</button>
            </>
            : <table>
                <tbody>
                    { players.map( (p,i) => <tr key={p.name}>
                    <td className={selectedPlayers.includes(p.name) ? 'selected' : ''}
                        onClick={() => {
                            if (selectedPlayers.includes(p.name)) 
                                setSelectedPlayers(selectedPlayers.filter(x => x != p.name))
                            else setSelectedPlayers([...selectedPlayers, p.name])
                        }}>{p.name}</td>
                </tr>)}
                </tbody>
            </table>
        }
        </div>
    </div>
}

export default PlayerSelect