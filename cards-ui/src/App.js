import React, { useState, useEffect } from 'react';
import { UserContext } from './Context'
import Navigat from './Navigat'
import Fixtures from './Fixtures'
import Image from 'react-bootstrap/Image';
import './App.scss'
import './Players.scss'
import { getUser } from './Api.js'
import { ToastContainer } from 'react-toastify'
import 'react-toastify/dist/ReactToastify.css'

function App() {
  const [user, setUser] = useState(null) 

  useEffect(() => { getUser().then(setUser) }, [])

  return <UserContext.Provider value={user}>
      <Navigat/>
      <div id='user'>{user?.user}</div>
      <Fixtures/>
      <Image id='improved' src='/assets/img/stars.png'/>
      <ToastContainer/>
    </UserContext.Provider>
}

export default App;
