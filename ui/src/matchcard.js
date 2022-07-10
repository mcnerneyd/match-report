import React, { useEffect, useState, useContext } from 'react';
import { UserContext } from './App'
import './App.scss';
import './matchcard.scss';
import _ from 'lodash';
import moment from 'moment';
import { useParams } from "react-router-dom";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faPlus, faMinus, faPersonCirclePlus, faNoteSticky, faArrowUpFromBracket } from '@fortawesome/free-solid-svg-icons'
import redCard from './img/red-card.png'
import yellowCard from './img/yellow-card.png'
import greenCard from './img/green-card.png'
import PlayerSelect from './playerselect';

const Matchcard = () => {  

  const user = useContext(UserContext)

  const [card, setCard] = useState(null)  
  const [selected, setSelected] = useState(null)
  const [kicker, setKicker] = useState(false)
  const { id } = useParams()

  useEffect(() => {
    fetch(`http://cards.leinsterhockey.ie/api/fixtures/${id}`)
    .then((res) => res.json())
    .then((data) => setCard(tweak(data.data)))
  }, [])

  if (card == null) return <div className='loading'>Loading...</div>
  const active = getActive(card, user)

  if (active) active.active = true

  if (kicker) {
    return <PlayerSelect setKicker={setKicker} team={active.team}/>
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
          <Team cardId={id} team={card.home} setKicker={setKicker} selected={selected} setSelected={setSelected}/>
        </li>
        <li className='team'>
          <Team cardId={id} team={card.away} setKicker={setKicker} selected={selected} setSelected={setSelected} />
        </li>
      </ol>
      <dialog>
        <header>
          <h1>Player NAME</h1>
        </header>
             <table><tbody>
               <tr>
                 <td>12</td>
                 {/* <td colSpan='2'>{f[2]} {f[1]} <span className='detail'>
                   {roles()}
                   {cards()}
                   x0
                   </span></td> */}
               </tr>
               <tr>
                 <td colSpan={3}>
                     Shirt Number <input type='number' size={3} onChange={(event) => {
                       //player.number = event.target.valueAsNumber
                       //setTrigger(f => !f)
                     }} style={{width:'3rem'}} value={0}/>
                     <div className='goals'>
                       <FontAwesomeIcon icon={faPlus} style={{color:'#484'}} onClick={(event)=>modScore(event, true)}/>
                       <span>x{0}</span>
                       <FontAwesomeIcon icon={faMinus} onClick={(event)=>modScore(event, false)}/>
                   </div>
                 </td>
               </tr>
               <tr className='roles'>
                 <td colSpan={3}>
                   <span>
                   //{roleOptions.map(r => <button key={'rolebutton' + r} onClick={()=>setRole(r.key)} style={{backgroundColor:r.back, color:r.fore}}>{r.text}</button>)}
                   </span>
                 </td>
               </tr>
               <tr>
                 <td colSpan={3} className='penalties'>
                   Penalties <select onChange={(event) => {
                      //  player.penalties = player.penalties ?? []
                      //  player.penalties.push(penalties.find(x => x.detail == event.target.value))
                      //  setTrigger(f => !f)
                     }}>

                       <option>Select card to add</option>
                       {/* {penalties.map(x => <option key={x.detail} className={'card-' + x.color}>
                           {x.detail}
                         </option>)} */}
                     </select><br/>
                     {/* {(player.penalties ?? []).map((x,i) => <p key={'pensel' + i}>          
                         <img src={ cardImg[x.color] }/> {x.detail}
                       </p>)} */}
                   </td>
                 </tr>
                 <tr>
                   <td colSpan={3}>
                     </td>
                 </tr>
                 </tbody>
               </table>
          <footer>
          <button className='danger' onClick={()=>{ 
                      //  player.removed = moment()
                      //  setSelect(null)
                     }}>
                       Remove Player</button>
          </footer>
      </dialog>
    </>
  }

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

const roleOptions = [
  {key:'c', text:'Captain', shortText:'Capt', charText:'C'},
  {key:'g', text:'Goalkeeper', shortText:'GK', charText:'GK'},
  {key:'m', text:'Manager', shortText:'Mgr', charText:'M'},
  {key:'p', text:'Physio', shortText:'Phy', charText:'P'},
  {key:'cc', text:'Coach', shortText:'Coach', charText:'CC'}
]
    
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
  const setKicker = props.setKicker
  // const formatPlayer = (player) => {
  //   const modScore = (event, add) => {
  //     event.stopPropagation()
  //     if (add) {
  //       player.score = (player.score ?? 0) + 1
  //     } else {
  //       if (player.score) player.score = player.score - 1
  //     }
  //     setTrigger(f => !f)
  //   }

  //   const setRole = (role) => {
  //     if (player.roles && player.roles.includes(role)) {
  //       player.roles = player.roles.filter(x => x != role)
  //     } else {
  //       if (!player.roles) player.roles = []
  //       player.roles.push(role)
  //     }
  //     console.log("Roles", player.roles)
  //     setTrigger(f => !f)
  //   }

    const roles = (player) => player.roles 
      ? <>
        {player.roles.map(r => {    
        const role = roleOptions.find(x => x.key == r.toLowerCase())
        if (!role) {
          console.log("Unknown role: ", r)
          return null
        }
        const name = role.text.toLowerCase()
        return <li key={'role' + name} className={'role-' + name}>{role.charText}</li>})}
        </>
      : null

    const cards = (player) => player.penalties
      ? <>
        {player.penalties.map((x,i) => <li key={'pen' + i} className={'pen-' + x.color}/>)}
        </>
      : null
  
  //   const f = player.name.match(/(.*), ([^ ]*)/)
  //   return <React.Fragment key={player.name}>
  //     {select == player.name && team.active 
  //       ? <tr className='selected' onClick={(event) => {
  //         if (event.target.nodeName.startsWith('T')) setSelect(null) }}>
  //           <td colSpan={3}>
  //           </td>
  //         </tr>
  //       : <tr >
  //       <div>{player.number}</div>
  //       <div>{f[2]}</div>
  //       <div>{f[1]} <span className='detail'>{roles()}{cards()}{scorex}</span></div>
  //     }
  //   </React.Fragment>
  // }

  const score = (player) => {
    if (player) return player.score > 0 ? <li className='score'>{player.score}</li> : null

    const teamScore = team.players.map(x => x.score ?? 0).reduce((a,b) => a+b, 0)
    return teamScore > 0 ? <li className='score'>{teamScore}</li> : null
  }

  return <>
      <header>
        <h3>{team.club} {team.team}</h3>
        <span className='detail'><output>{score()}</output></span>
      </header>
      { team.active &&
      <button className='success' onClick={()=>setKicker(true)}><FontAwesomeIcon icon={faPersonCirclePlus}/> Add Players...</button>
      }
      <ol>
        {team.players.filter(x => !x.removed).map(pl => {
          const [fullName, lastName, firstName] = pl.name.match(/(.*), ([^ ]*)/)
          return <li className='player' onClick={() => setSelect(pl.name)} key={pl.name}> 
            <div>{pl.number}</div>
            <div>{firstName}</div>
            <div>{lastName}</div>
            <ol className='detail'>{roles(pl)}{cards(pl)}{score(pl)}</ol>
          </li>})}
      </ol>
    </>
}

const tweak = (js) => {
  const tweakTeam = (jsTeam) => {
    jsTeam.players = Object.keys(jsTeam.players).map(p => {
      const k = jsTeam.players[p]
      const d = k.detail ? JSON.parse(k.detail) : {}
      const player = { name:p,
        ...d,
        ts: k.date
      }
      const s = jsTeam.scorers[p]
      if (s) player.score = s
      const n = k.number
      if (n) player.number = n
      return player
    })
    delete jsTeam.scorers
    delete jsTeam.captain
    delete jsTeam.goals
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

  console.log("Card", js)

  return js
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