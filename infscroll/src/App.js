import './App.css';
import React from 'react';

import Scroller from './scroller'
import { Row, Col } from 'antd';


function App() {
  
  return <Scroller
      render= {(x, i) => <Row key={x.fixtureID}>
        <Col span={5}>{x.datetimeZ}</Col>
        <Col span={5}>{x.competition}</Col>
        <Col span={8}>{x.home} v {x.away}</Col>
        <Col span={6}>{x.fixtureID}/{i}/{x.index}</Col>
      </Row>}
      data={async (i0, i1) => {

        const response = await fetch(`http://cards.leinsterhockey.ie/public/api/fixtures?c=Bray&site=lhamen&i0=${i0}&i1=${i1}`,
          {
            mode: "cors",
            method: 'GET'
          });
        const response_1 = await response.text();
        const response_2 = response_1 === '' ? [] : JSON.parse(response_1);
        console.log("" + response_2.length + ` row(s) ${i0} = ${i1}`);
        response_2.forEach((x, i) => x.i = (i0 < 0 ? i0 - i : i0 + i));
        return response_2;

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
    ></Scroller>;
}

export default App;
