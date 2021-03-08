import "./App.scss";
import React, { useState, useEffect } from "react";

import Scroller from "./scroller";
import { Row, Col, Layout, Menu } from "antd";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faEnvelope } from "@fortawesome/free-solid-svg-icons";
import dateFormat from "dateformat";
import Matchcard from "./matchcard";

const { Header, Content, Footer } = Layout;

function App() {
  const [card, setCard] = useState({});
  useEffect(() => {
    const url = `http://cards.leinsterhockey.ie/public/api/cards/6107?site=lhamen`;
    return fetch(url, {
      mode: "cors",
      method: "GET",
    })
      .then((response) => response.text())
      .then((t) => {
        console.log("Text:" + t);
        setCard(t === "" ? {} : JSON.parse(t));
      })
      .catch((error) => {
        console.error("Err:", error);
      });
  }, []);

  return (
    <Layout>
      <Header>
        <Menu theme="dark" mode="horizontal">
          <Menu.Item></Menu.Item>
          <Menu.Item>Fixtures</Menu.Item>
          <Menu.Item>Registration</Menu.Item>
          <Menu.Item>Reports</Menu.Item>
          <Menu.Item>Help</Menu.Item>
          <Menu.Item>Admin</Menu.Item>
        </Menu>
      </Header>
      <Content>
        <Matchcard card={card} />
      </Content>
      <Footer></Footer>
    </Layout>
  );
}

function MyScroller() {
  return (
    <Scroller
      render={(x, i) => {
        const dt = new Date(x.datetimeZ);

        let dateBreak = null;

        if (
          x.previous === undefined ||
          x.previous.datetimeZ.substring(0, 7) !== x.datetimeZ.substring(0, 7)
        ) {
          dateBreak = (
            <Row className="date-break" key={x.fixtureID + "-break"}>
              <Col span={24}>{dateFormat(dt, "mmmm yyyy")}</Col>
            </Row>
          );
        }

        return (
          <>
            {dateBreak}
            <Row className="fixture-row" key={x.fixtureID}>
              <Col span={1}>{dateFormat(dt, "d")}</Col>
              <Col span={2}>{dateFormat(dt, "H:MM")}</Col>
              <Col span={5}>
                <span className="label-league">{x.competition}</span>
              </Col>
              <Col span={6}>{x.home}</Col>
              <Col span={6}>{x.away}</Col>
              <Col offset={3} span={1}>
                <FontAwesomeIcon icon={faEnvelope} />
              </Col>
            </Row>
          </>
        );
      }}
      keyField="fixtureID"
      data={async (i0, i1) => {
        const label = `${i0}-${i1}`;
        console.time(label);
        const url = `http://cards.leinsterhockey.ie/public/api/fixtures?c=Bray&site=lhamen&i0=${i0}&i1=${i1}`;
        const response = await fetch(url, {
          mode: "cors",
          method: "GET",
        });
        const response_1 = await response.text();
        console.timeEnd(label);
        const response_2 = response_1 === "" ? [] : JSON.parse(response_1);
        console.log(
          "" + response_2.length + ` row(s) ${i0} = ${i1} (url=${url})`
        );
        response_2.forEach((x, i) => (x.i = i0 < 0 ? i0 - i : i0 + i));
        response_2.forEach((x, i) => (x.mark = `[${i}]:${label}`));
        if (response_2.length > 0) {
          response_2[0].breakBefore = true;
        }
        return response_2;
      }}
    ></Scroller>
  );
}

export default App;
