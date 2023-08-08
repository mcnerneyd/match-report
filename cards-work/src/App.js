import React from 'react';
import { UserContext } from './Context'
import Navigat from './Navigat'
import Fixtures from './Fixtures'
import Image from 'react-bootstrap/Image';
import './App.scss'
import './Players.scss'
import { ToastContainer } from 'react-toastify'
import 'react-toastify/dist/ReactToastify.css'
import { useJwt } from "react-jwt";
import { useCookies } from "react-cookie";

function App() {
  const [cookies] = useCookies(['jwt-token'])
  console.log("Cookie", cookies['jwt-token'])
  const { decodedToken, isExpired } = useJwt(cookies['jwt-token'])
  const queryParameters = new URLSearchParams(window.location.search)
  const id = queryParameters.get("id")
  const user = decodedToken
  console.log("User", user, id, cookies, decodedToken)

  if (!cookies['jwt-token'] || isExpired) {
    window.location.href = '/Login';
    return null;
  }

  return <UserContext.Provider value={user}>
      <Navigat/>
      <div id='user'>{user?.user}</div>
      <Fixtures cardId={id}/>
      <Image id='improved' src='/assets/img/stars.png'/>
      <ToastContainer/>
    </UserContext.Provider>
}

export default App;
