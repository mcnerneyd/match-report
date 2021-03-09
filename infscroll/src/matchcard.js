import React from 'react';

import { Row, Col } from 'antd';
import dateFormat from "dateformat";

import Teamsheet from './teamsheet';
import "./teamsheet.scss";

class Matchcard extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    const card = this.props.card;

    if (card === undefined) return false;

    console.log(card);

    const dt = new Date(card.date);

    return <div className='matchcard'>
      <Row>
        <Col span={16}>
        {card.competition}
      </Col>
      <Col span={5}>
      <dl>
          <dt>Date</dt>
          <dd>{dateFormat(dt, "dd mmmm, yyyy")}</dd>
        </dl>
        </Col>
      <Col span={3}>
        <dl>
          <dt>Time</dt>
          <dd>{dateFormat(dt, "H:MM")}</dd>
        </dl>
        </Col>
      </Row>
      {/* <Row>
        <dl>
          <dt>Fixture ID</dt>
          <dd>{card.fixture_id}</dd>
        </dl>
      </Row> */}
      <Row gutter={[16,16]}>
        <Col span={12}>
        <Teamsheet teamsheet={card.home} active={true}/>
      </Col>
      <Col span={12}>
        <Teamsheet teamsheet={card.away}/>
      </Col>
      </Row>
    </div>
  }
}

export default Matchcard;