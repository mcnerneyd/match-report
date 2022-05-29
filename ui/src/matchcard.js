import React, { useEffect, useState } from 'react';
import './App.scss';
import './matchcard.scss';
import _ from 'lodash';
import moment from 'moment';
import {  Link, useParams } from "react-router-dom";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faPlus, faMinus, faPersonCirclePlus } from '@fortawesome/free-solid-svg-icons'
import redCard from './red-card.png'
import yellowCard from './yellow-card.png'
import greenCard from './green-card.png'

const p = {
  'green':['Green Card'],
  'yellow':[
    'Technical - Breakdown',
    'Technical - Delay/Time Wasting',
    'Technical - Dissent',
    'Technical - Foul/Abusive Language',
    'Technical - Bench/Coach/Team Foul',
    'Physical - Tackle',
    'Physical - Dangerous/Reckless Play'
  ],
  'red':['Red Card']
}
const penalties = Object.keys(p).flatMap(x => p[x].map(y => ({detail:y, color:x}))) 

const cardImg = {
  'red':redCard,
  'yellow':yellowCard,
  'green':greenCard
}

const Team = (props) => {
  const [trigger, setTrigger] = useState(false)

  const team = props.team;
  const setSelect = props.setSelected
  const select = props.selected
  const formatPlayer = (player) => {
    console.log("Props", props)
    const modScore = (event, add) => {
      event.stopPropagation()
      if (add) {
        player.score = (player.score ?? 0) + 1
      } else {
        if (player.score) player.score = player.score - 1
      }
      setTrigger(f => !f)
    }

    const setRole = (role) => {
      if (player.roles && player.roles.includes(role)) {
        player.roles = player.roles.filter(x => x != role)
      } else {
        if (!player.roles) player.roles = []
        player.roles.push(role)
      }
      console.log("Roles", player.roles)
      setTrigger(f => !f)
    }

    const roleOptions = [
      {key:'c', text:'Captain', shortText:'Capt', charText:'C', back:'black', fore:'white'},
      {key:'g', text:'Goalkeeper', shortText:'GK', charText:'GK', back:'green', fore:'white'},
      {key:'m', text:'Manager', shortText:'Mgr', charText:'M', back:'blue', fore:'white'},
      {key:'p', text:'Physio', shortText:'Phy', charText:'P', back:'orange', fore:'black'},
      {key:'cc', text:'Coach', shortText:'Coach', charText:'CC', back:'red', fore:'white'}
    ]
        
    const roles = () => player.roles 
      ? <ol className='roles'>
        {player.roles.map(r => {    
        const role = roleOptions.find(x => x.key == r.toLowerCase())
        if (!role) {
          console.log("Unknown role: ", r)
          return null
        }
        return <li key={'rolebadge' + r} style={{backgroundColor:role.back,color:role.fore}}>{role.charText}</li>})}
        </ol>
      : null

    const cards = () => player.penalties
      ? <ol className='penalties'>
        {player.penalties.map((x,i) => <li key={'pen' + i}>
          <img src={ cardImg[x.color] }/>
        </li>)}
        </ol>
      : null
  
    const f = player.name.match(/(.*), ([^ ]*)/)
    const scorex = player.score > 0 ? "x" + player.score : ""
    return <React.Fragment key={player.name}>
      {select == player.name && team.active 
        ? <tr className='selected' onClick={(event) => {
          if (event.target.nodeName.startsWith('T')) setSelect(null) }}>
            <td colSpan={3}>
              <table><tbody>
                <tr>
                  <td>{player.number}</td>
                  <td colSpan='2'>{f[2]} {f[1]} <span className='detail'>
                    {roles()}
                    {cards()}
                    x{player.score ?? 0}
                    </span></td>
                </tr>
                <tr>
                  <td colSpan={3}>
                      Shirt Number <input type='number' size={3} onChange={(event) => {
                        player.number = event.target.valueAsNumber
                        setTrigger(f => !f)
                      }} style={{width:'3rem'}} value={player.number}/>
                      <div className='goals'>
                        <FontAwesomeIcon icon={faPlus} style={{color:'#484'}} onClick={(event)=>modScore(event, true)}/>
                        <span>x{player.score ?? 0}</span>
                        <FontAwesomeIcon icon={faMinus} onClick={(event)=>modScore(event, false)}/>
                    </div>
                  </td>
                </tr>
                <tr className='roles'>
                  <td colSpan={3}>
                    <span>
                    {roleOptions.map(r => <button key={'rolebutton' + r} onClick={()=>setRole(r.key)} style={{backgroundColor:r.back, color:r.fore}}>{r.text}</button>)}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td colSpan={3} className='penalties'>
                    Penalties <select onChange={(event) => {
                        player.penalties = player.penalties ?? []
                        player.penalties.push(penalties.find(x => x.detail == event.target.value))
                        setTrigger(f => !f)
                      }}>

                      <option>Select card to add</option>
                      {penalties.map(x => <option key={x.detail} className={'card-' + x.color}>
                          {x.detail}
                        </option>)}
                    </select><br/>
                    {(player.penalties ?? []).map((x,i) => <p key={'pensel' + i}>          
                        <img src={ cardImg[x.color] }/> {x.detail}
                      </p>)}
                  </td>
                </tr>
                <tr>
                  <td colSpan={3}>
                    <button className='danger' onClick={()=>{ 
                      player.removed = moment()
                      setSelect(null)
                    }}>
                      Remove Player</button></td>
                </tr>
                </tbody>
              </table>
            </td>
          </tr>
        : <tr onClick={() => setSelect(player.name) }>
        <td>{player.number}</td>
        <td>{f[2]}</td>
        <td>{f[1]} <span className='detail'>{roles()}{cards()}{scorex}</span></td>
      </tr>
      }
    </React.Fragment>
  }

  const teamScore = team.players.map(x => x.score ?? 0).reduce((a,b) => a+b, 0)
  const score = teamScore > 0 ? "x" + teamScore : null

  return <table>
      <caption>
        {team.club} {team.team}<span className='detail'>{score}</span>
      </caption>
      <thead>
        { team.active && <tr>
          <td colSpan="3">
            {<Link to={`/${props.cardId}/select`}><FontAwesomeIcon icon={faPersonCirclePlus}/> Add Players...</Link>}
            {/* <button className='success'><FontAwesomeIcon icon={faPersonCirclePlus}/> Add Players...</button> */}
          </td>
        </tr> }
      </thead>
      <tbody>
          {team.players.filter(x => !x.removed).map(formatPlayer)}
      </tbody>
    </table>
}

const tweak = (js) => {
  const tweakTeam = (js) => {
    js.players = Object.keys(js.players).map(p => {
      const k = js.players[p]
      const d = k.detail ? JSON.parse(k.detail) : {}
      const player = { name:p,
        ...d,
        ts: k.date
      }
      const s = js.scorers[p]
      if (s) player.score = s
      const n = k.number
      if (n) player.number = n
      return player
    })
    delete js.scorers
    delete js.captain
    delete js.goals
  }

  tweakTeam(js.home)
  delete js.away_id
  delete js.away_name
  delete js.away_team
  tweakTeam(js.away)
  delete js.home_id
  delete js.home_name
  delete js.home_team
  delete js.goals
  delete js.comment

  return js
}


const Matchcard = (props) => {  

  const [card, setCard] = useState(null)  
  const [selected, setSelected] = useState(null)
  const { id } = useParams()

  useEffect(() => {
    fetch(`http://cards.leinsterhockey.ie/api/fixtures/${id}`)
    .then((res) => res.json())
    .then((data) => { setCard(tweak(data.data)) })
  }, [])

  if (card == null) return null;

  card.home.active = true

  const dt = moment(card.datetime)

  return <div className='matchcard'>
      <h1>{card.competition}</h1>
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
      <div className='teams pure-g'>
        <Team cardId={id} team={card.home} selected={selected} setSelected={setSelected}/>
        <Team cardId={id} team={card.away} selected={selected} setSelected={setSelected} />
      </div>
    </div>
  }

export default Matchcard  