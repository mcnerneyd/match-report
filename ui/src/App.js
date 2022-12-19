import React, { useEffect, useState, useContext } from 'react';
import './App.scss';
import Fixtures from './fixtures';
import Matchcard from './matchcard';
import { db } from './db'
import useMatchcardStore from './matchcardStore'
import { API_BASE } from './constants'
import { makeAutoObservable } from "mobx"
import { observer } from "mobx-react"

export const UserContext = React.createContext(null)

class Card {
  constructor() {
    makeAutoObservable(this)
  }

  card = null

  setCard(id) {
    card = id
  }
}

function App() {

  const [userData, setUserData] = useState()

  const search = window.location.search;
  const params = new URLSearchParams(search);
  const token = params.get("token")
  if (token) {
    sessionStorage.setItem("jwtToken", token)
  }

  if (!userData) {
    fetch(`${API_BASE}/users`, {
      headers: { 'X-Auth-Token': `${sessionStorage.getItem("jwtToken")}` }
    })
      .then((res) => res.json())
      .then((data) => {
        console.log(data)
        setUserData(data)
      })
  }

  function formatUser(user) {
    const v = user.match(/(.*) \((.*)\)/)
    return <>
      <span>{v[1]}</span>
      <span>{v[2]}</span>
    </>
  }

  function reloadDb(page) {
    fetch(`${API_BASE}/fixtures?p=${page}&n=1000`)
      .then((res) => res.json())
      .then((list) => {
        console.log("Page", page, list.fixtures.length)
        list.fixtures.forEach(m => {
          m['homeclub'] = m['home']['club']
          db.fixtures.put(m, parseInt(m.id))
        })
        if (page >= 0) {
          if (list.fixtures.length > 0) {
            reloadDb(page + 1)
          } else {
            reloadDb(-1)
          }
        } else {
          if (list.fixtures.length > 0) reloadDb(page - 1)
        }
      })
  }

  useEffect(() => {
    // reloadDb(0)
  })

  const card = useMatchcardStore((state) => state.card)
  const myCard = new Card()
  const CardView = observer(({ card }) => <div>Card {card.card}</div>)

  return <UserContext.Provider value={userData}>
    <header>
      <h1>Leinster Hockey Matchcards</h1>
      <span className='login'>{userData ? formatUser(userData.user) : <button>Login</button>}</span>
      <input type='text' name='search' placeholder='Search for fixtures by club, competition or date' spellCheck='false' />
      <nav>
        {/* <NavLink to='/'>Fixtures</NavLink>
        <NavLink to='/'>Reports</NavLink>
        <NavLink to='/'>Registration</NavLink>
          <NavLink to='/'>Admin</NavLink> */}
        <span className='user-info'>
        </span>
      </nav>
    </header>
    <main>
      <CardView card={myCard}/>
      <button onClick={() => myCard.card=5}/>
      { card != null 
        ? <Matchcard />
        : <Fixtures />
      }
    </main>
  </UserContext.Provider>
}

export default App;