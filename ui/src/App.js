import React, { useEffect, useState } from 'react';
import { BrowserRouter, Routes, Route, NavLink } from "react-router-dom";
import './App.scss';
import Fixtures from './fixtures2';
import Matchcard from './matchcard';
import { db } from './db'

export const UserContext = React.createContext(null)

function App() {

  const [userData, setUserData] = useState()

  const search = window.location.search;
  const params = new URLSearchParams(search);
  const token = params.get("token")
  if (token) {
    sessionStorage.setItem("jwtToken", token)
  }

  if (!userData) {
    fetch("http://cards.leinsterhockey.ie/api/users", {
      headers: {'X-Auth-Token': `${sessionStorage.getItem("jwtToken")}`}})
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
    fetch(`http://cards.leinsterhockey.ie/api/fixtures?p=${page}&n=1000`)
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
    //reloadDb(0)
  })

  return <BrowserRouter> 
    <UserContext.Provider value={userData}>
      <header>
        <h1>Leinster Hockey Matchcards</h1>
        <span className='login'>{ userData ? formatUser(userData.user) : <button>Login</button>}</span>
        <input type='text' name='search' placeholder='Search for card by club, competition or date' spellCheck='false'/>
        <nav>
          <NavLink to='/'>Fixtures</NavLink>
          <NavLink to='/'>Reports</NavLink>
          {/* <NavLink to='/'>Registration</NavLink>
          <NavLink to='/'>Admin</NavLink> */}
          <span className='user-info'>
          </span>
        </nav>
      </header>
      <main>
        <Routes>
          <Route path="/:id" element={<Matchcard/>}/>
          <Route path="/" element={<Fixtures/>}/>
          <Route render={() => <h1>Page not found</h1>} />
        </Routes>
      </main>
    </UserContext.Provider>
  </BrowserRouter>
}

export default App;