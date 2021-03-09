import React from 'react';

import { Row, Col, Button } from 'antd';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faUserPlus } from "@fortawesome/free-solid-svg-icons";

class Teamsheet extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    console.log(this.props.teamsheet);

    const sheet = this.props.teamsheet;

    return <div className='team'>
      <Row>
        <Col span={22}>
        {sheet.club} {sheet.team}
        </Col>
        <Col className='score' span={2}>
          {Object.values(sheet.players).map(x => x.score || 0).reduce((a,b) => a + b, 0)}
        </Col>
      </Row>
      {Object.keys(sheet.players).map((x,i) => {
        const v = sheet.players[x];
        const [lastName, firstName] = x.split(",", 2);
        return <Row className='player' key={i}>
          <Col span={2}>{v.number}</Col>
          <Col span={8}>{firstName}</Col>
          <Col span={9}>{lastName}</Col>
          <Col span={5}>{!v.score || <span className='score'>{v.score}</span>}</Col>
        </Row>
          })}
        {this.props.active !== true || <Row>
            <Button type="primary">Add Players&nbsp;<FontAwesomeIcon icon={faUserPlus} /></Button>
        </Row> }
    </div>
  }
}

export default Teamsheet;