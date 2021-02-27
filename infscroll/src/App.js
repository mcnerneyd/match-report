import './App.css';
import React from 'react';

import Scroller from './scroller'


function App() {

  return (
    <Scroller
      render={x => {
        <>
          <span>{x.name}</span>
        </>
      }}
      keyField='fixtureID'
      data={(i0, i1) => {

        return fetch(`http://cards.leinsterhockey.ie/public/api/fixtures?c=Bray&site=lhamen&i0=${i0}&i1=${i1}`,
             {
               mode: "cors",
               method: 'GET'
             })
             .then(response => response.text())
             .then(response => response === '' ? [] : JSON.parse(response))
             .then(response => { console.log("" + response.length + ` row(s) ${i0} = ${i1}`); response.forEach((x, i) => x.i = (i0 < 0 ? i0 - i : i0 + i)); return response;})

        // fetch('http://cards.leinsterhockey.ie/public/api/fixtures?c=Bray&site=lhamen&pagesize=' + d + '&page',
        // //fetch('http://cards.leinsterhockey.ie/public/api/cards?site=lhamen',
        // //fetch('http://cards.leinsterhockey.ie/api/1.0/lhamen/cards',
        //     {
        //       mode: "cors",
        //       method: 'GET'
        //     })
        //     .then(response => response.json());

        // if (i0 > 20) return [];
        // if (i1 > 20) i1 = 20;
        // if (i0 < -10) return [];
        // if (i1 < -10) i1 = -10;

        // const d = Math.abs(i1 - i0);
        // const page = i0 / 5;
        

        // console.log("A:" + i0 + " " + i1 + " =" + d);
        // const a= Array.from(Array(d))
        //   .map((x,i) => i0 < 0 ? i0 - i : i0 + i)
        //   .map(x => ({
        //     index: x,
        //     name: 'name' + x,
        //   }));
        //   console.log(a);
        //   return a;
        }}
    ></Scroller>
  );
}

export default App;
