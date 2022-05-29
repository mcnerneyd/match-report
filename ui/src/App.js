import React from 'react';
import { BrowserRouter, Routes, Route, NavLink } from "react-router-dom";
import './App.scss';
import Fixtures from './fixtures3';
import Matchcard from './matchcard';
import PlayerSelect from './playerselect';

function App() {
  return <BrowserRouter> 
  <nav>
    <NavLink to='/'>Fixtures</NavLink>
  </nav>
  <main>
    <Routes>
      <Route path="/:id/select" element={<PlayerSelect/>}/>
      <Route path="/:id" element={<Matchcard/>}/>
      <Route path="/" element={<Fixtures/>}/>
      <Route render={() => <h1>Page not found</h1>} />
    </Routes>
  </main>
  </BrowserRouter>
}

export default App;