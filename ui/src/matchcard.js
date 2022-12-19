import React, { useEffect, useState, useContext } from 'react';
import { UserContext } from './App'
import './App.scss';
import './matchcard.scss';
import _ from 'lodash';
import moment from 'moment';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faPlus, faMinus, faPersonCirclePlus, faNoteSticky, faArrowUpFromBracket } from '@fortawesome/free-solid-svg-icons'
import redCard from './img/red-card.png'
import yellowCard from './img/yellow-card.png'
import greenCard from './img/green-card.png'
import useMatchcardStore from './matchcardStore';
import { API_BASE } from './constants'

const penaltyCards = {
  'green': ['Green Card'],
  'yellow': [
    'Technical - Breakdown',
    'Technical - Delay/Time Wasting',
    'Technical - Dissent',
    'Technical - Foul/Abusive Language',
    'Technical - Bench/Coach/Team Foul',
    'Physical - Tackle',
    'Physical - Dangerous/Reckless Play'
  ],
  'red': ['Red Card']
}

const cardImg = {
  'red': redCard,
  'yellow': yellowCard,
  'green': greenCard
}

const penalties = Object.keys(penaltyCards).flatMap(x => penaltyCards[x].map(y => ({ detail: y, color: x })))

const roleOptions = [
  { key: 'c', text: 'Captain', shortText: 'Capt', charText: 'C' },
  { key: 'g', text: 'Goalkeeper', shortText: 'GK', charText: 'GK' },
  { key: 'm', text: 'Manager', shortText: 'Mgr', charText: 'M' },
  { key: 'p', text: 'Physio', shortText: 'Phy', charText: 'P' },
  { key: 'cc', text: 'Coach', shortText: 'Coach', charText: 'CC' }
]

const Matchcard = () => {

  const user = useContext(UserContext)
  const card = useMatchcardStore((state) => state.card)
  const playerSelect = useMatchcardStore((state) => state.playersToAdd)

  useEffect(() => {
    if (card != null) {
      const active = getActive(card, user)

      if (active) {
        fetch(`${API_BASE}/registration/list.json?s=${user.section}&t=${active.team}`, {
          headers: { 'X-Auth-Token': `${sessionStorage.getItem("jwtToken")}` }
        })
          .then((res) => res.json())
          .then((data) => {
            const players = data.map(x => {
              const dates = x.history.map(y => moment(y.date))
              x['lastDate'] = Math.min(...dates)
              return x
            })
            console.log("Found ", players)
            setAllPlayers(players)
          })
      }
    }
  }, [])

  if (card == null) return <div className='loading'>Loading...</div>
  const active = getActive(card, user)

  if (active) {
    active.active = true
  }

  const dt = moment(card.datetime)

  return <>
    <header className='matchcard'>
      <h2>{card.competition}</h2>
      <div className='buttons'>
        <button><FontAwesomeIcon icon={faNoteSticky} /> Add Note...</button>
        <button><FontAwesomeIcon icon={faArrowUpFromBracket} /> Submit Card</button>
      </div>
      <div className='detail'>
        <dl>
          <dt>Fixture ID</dt>
          <dd>{card.fixture_id}</dd>
        </dl>
        <dl>
          <dt>Date</dt>
          <dd>{dt.format('YYYY-MM-DD')}</dd>
        </dl>
        <dl>
          <dt>Time</dt>
          <dd>{dt.format('HH:mm')}</dd>
        </dl>
      </div>
    </header>
    <ol className='teams'>
          <li className='team'>
            <Team cardId={card.id} team={card.home} />
          </li>
          <li className='team'>
            <Team cardId={card.id} team={card.away} />
          </li>
        {/* : <li className='team'>
          <header>
            <h3>{active.club} {active.team}</h3>
          </header>
          <button className='success' onClick={() => setKicker(null)}><FontAwesomeIcon icon={faPersonCirclePlus} /> Add Players...</button>
          <ul>
            {allPlayers.map((px, ix) => <li key={px.name}>
              <span className={selectedPlayers.includes(px.name) ? 'selected' : ''}
                onClick={() => {
                  if (selectedPlayers.includes(px.name))
                    setSelectedPlayers(selectedPlayers.filter(x => x != px.name))
                  else setSelectedPlayers([...selectedPlayers, px.name])
                }}>{px.name}</span>
            </li>)}
          </ul>
        </li>} */}
    </ol>
  </>
}

const Team = (props) => {
  const [trigger, setTrigger] = useState(false)

  const team = props.team

  const roles = (player) => player.roles
    ? <>
      {player.roles.map(r => {
        const role = roleOptions.find(x => x.key == r.toLowerCase())
        if (!role) {
          console.log("Unknown role: ", r)
          return null
        }
        const name = role.text.toLowerCase()
        return <li key={'role' + name} className={'role-' + name}>{role.charText}</li>
      })}
    </>
    : null

  const cards = (player) => player.penalties
    ? <>
      {player.penalties.map((x, i) => <li key={'pen' + i} className={'pen-' + x.color} />)}
    </>
    : null

  const score = (player) => {
    if (player) return player.score > 0 ? <li className='score'>{player.score}</li> : null

    const teamScore = team.players.map(x => x.score ?? 0).reduce((a, b) => a + b, 0)
    return teamScore > 0 ? <li className='score'>{teamScore}</li> : null
  }

  return <>
    <header>
      <h3>{team.club} {team.team}</h3>
      <span className='detail'><output>{score()}</output></span>
    </header>
    {team.active &&
      <button className='success' onClick={() => setKicker([])}><FontAwesomeIcon icon={faPersonCirclePlus} /> Add Players...</button>
    }
    <ol>
      {team.players.filter(x => !x.removed).map(pl => {
        const [fullName, lastName, firstName] = pl.name.match(/(.*), ([^ ]*)/)
        const selectName = team.club + team.team + pl.name;
        const selected = (selectName == select);
        return <li className={'player' + (selected ? ' selected' : '')} onClick={() => team.active && setSelect(selectName)} key={selectName}>
          <div>{pl.number}</div>
          <div>{firstName}</div>
          <div>{lastName}</div>
          <ol className='detail'>{roles(pl)}{cards(pl)}{score(pl)}</ol>
          {selected
            ? <div className='edit'>
              <label>Shirt Number</label>
              <input type='number' value={pl.number} />

              <label>Goals</label>
              <input type='number' value={pl.goals} />

              <span className='roles'>
                {roleOptions.map(r => <button className={'role-' + r.text.toLowerCase()}
                  key={'rolebutton' + r} onClick={() => setRole(r.key)}>{r.text}</button>)}
              </span>

              <label>Cards</label>
              <select onChange={(event) => {
                //  player.penalties = player.penalties ?? []
                //  player.penalties.push(penalties.find(x => x.detail == event.target.value))
                //  setTrigger(f => !f)
              }}>

                <option>Select card to add</option>
                {/* {penalties.map(x => <option key={x.detail} className={'card-' + x.color}>
                           {x.detail}
                         </option>)} */}
              </select>
              <ul>
                {(pl.penalties ?? []).map((x, i) => <li key={'pensel' + i}>{x.color} {x.detail}</li>)}
              </ul>
            </div>
            : null}
        </li>
      })}
    </ol>
  </>
}

function getActive(card, user) {
  if (user) {
    if (card.section == user.section) {
      if (card.home.club == user.club) return card.home
      if (card.away.club == user.club) return card.away
    }

    return undefined;
  }
}

export default Matchcard  