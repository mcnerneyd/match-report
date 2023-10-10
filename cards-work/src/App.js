import React, { useState } from 'react';
import { UserContext } from './Context'
import Navigat from './Navigat'
import Fixtures from './Fixtures'
import Image from 'react-bootstrap/Image';
import { Button, Form } from 'react-bootstrap';
import './App.scss'
import './Players.scss'
import { ToastContainer } from 'react-toastify'
import 'react-toastify/dist/ReactToastify.css'
import { decodeToken } from "react-jwt";
import { useCookies } from "react-cookie";
import { login } from './Api';

function App() {
  const [cookies] = useCookies(['jwt-token'])
  const queryParameters = new URLSearchParams(window.location.search)
  const id = queryParameters.get("id")
  const [globalSearch, setGlobalSearch] = useState(null)

  // Loginy
  const jwtCookie = cookies['jwt-token']
  const user = jwtCookie ? decodeToken(jwtCookie) : undefined

  if (user) setInterval(() => window.location.reload(), user.exp * 1000 - Date.now())

  console.log("User", user)

  return <UserContext.Provider value={user}>
    <Navigat search={setGlobalSearch}/>
    {user == null
        ? <Form id='user' onSubmit={(e) => {
          e.preventDefault()
          login(e.target['user'].value, e.target['password'].value)
        }}>
            <Form.Control type='text' name='user' placeholder='Username'/>
            <Form.Control type='password' name='password' placeholder='Password'/>
            <Button type='submit'>Login</Button>
        </Form>
        : <div id='user'>{user?.user}</div>}
    <Fixtures cardId={id} search={globalSearch} />
    <Image id='improved' src='/assets/img/stars.png' />
    <ToastContainer />
  </UserContext.Provider>
}

export default App;
