import { faEraser } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import React, { useState, useContext, useRef } from 'react';
import Button from 'react-bootstrap/Button'
import ToggleButton from 'react-bootstrap/ToggleButton'
import ToggleButtonGroup from 'react-bootstrap/ToggleButtonGroup';

export function Players({card, onClose}) {
    const [selected, setSelected] = useState([])
    const [filter, setFilter] = useState(0)
    const [inProp, setInProp] = useState(false)
    const nodeRef = useRef(null)

    const players = card.players.values

    const togglePlayer = (playerName) => {
        if (selected.includes(playerName)) {
            setSelected(selected.filter(x => x !== playerName))
        } else {
            setSelected([...selected, playerName].sort())
        }
        setInProp(true)
    }

    const unselected = players
        .filter(p => !selected.includes(p.name))
        .filter(p => {
            if (filter == 2) return p.history
            if (filter == 3) return !p.history
            return true
        })
        .map(p => p.name)

    return <>
        <div className='players central-toolbar'>
            <p>{card.home.club} {card.home.team} v {card.away.club} {card.away.team}</p>
            <p>{card.date.format("DD MMMM, YYYY")}</p>
            <Button variant="success" onClick={() => onClose(players.filter(p => selected.includes(p.name)))}>Submit Team</Button>
            <Button variant="warning">Postponed</Button>
            <span>
            <p>{card[card.user].club} {card[card.user].team}</p>
            <p>{selected.length == 0 ? "No" : selected.length} player{selected.length == 1 ? null : "s"} selected</p>
            </span>
        </div>
        <div className='players central-block'>
            <ol>
                {selected.map(x =><li key={x} className="selected" onClick={() => togglePlayer(x)}>{x}</li>)}
                <div id='filter-block'>
                    <ToggleButtonGroup type='radio' name='f1' value={filter} onChange={(v)=>setFilter(v)}>
                        <ToggleButton id='ft-0' type='radio' value={0}>All</ToggleButton>
                        <ToggleButton id='ft-1' type='radio' value={1}>Last</ToggleButton>
                        <ToggleButton id='ft-2' type='radio' value={2}>Played</ToggleButton>
                        <ToggleButton id='ft-3' type='radio' value={3}>Unplayed</ToggleButton>
                    </ToggleButtonGroup>
                    <Button variant="danger" onClick={() => setSelected([])}>Clear</Button>
                </div>
                {unselected.map(x =><li key={x} onClick={() => togglePlayer(x)}>{x}</li>)}
            </ol>
        </div>
    </>
}
