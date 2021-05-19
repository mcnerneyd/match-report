import React from "react";

import { Row, Col, Button, Input, Tabs, Dropdown, Menu, Modal } from "antd";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faUserPlus,
  faTshirt,
  faPlusCircle,
  faMinusCircle,
} from "@fortawesome/free-solid-svg-icons";

class Teamsheet extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    console.log(this.props.teamsheet);

    const sheet = this.props.teamsheet;

    const incidents = {
      greencard: {
        name: "Green Card",
        icon: "green-card.png",
      },
      yellowcard: {
        name: "Yellow Card",
        icon: "yellow-card.png",
        options: [
          "Technical - Breakdown",
          "Technical - Delay/Time Wasting",
          "Technical - Dissent",
          "Technical - Foul/Abusive Language",
          "Technical - Bench/Coach/Team Foul",
          "Physical - Tackle",
          "Physical - Dangerous/Reckless Play",
        ],
      },
      redcard: {
        name: "Red Card",
        icon: "red-card.png",
      },
      injury: {
        name: "Injury",
        icon: "red-cross.png",
        options: ["Head Injury", "Blood Injury", "Break/Fracture", "Other"],
      },
    };

    const roles = [
      { name: "Captain", color: "red", code: "Cpt" },
      { name: "MVP", color: "purple", code: "MVP" },
      { name: "Goalkeeper", color: "green", code: "GK" },
      { name: "Coach", color: "blue", code: "C" },
      { name: "Physio", color: "grey", code: "P" },
      { name: "Manager", color: "orange", code: "Mgr" },
    ];

    const roleMenu = (
      <Menu>
        {roles.map((x) => {
          <Menu.Item>{x.name}</Menu.Item>;
        })}
      </Menu>
    );

    const incidentMenu = (
      <Menu>
        {Object.keys(incidents)
          .map((x) => {
            const v = incidents[x];
            if (v.options)
              return v.options.map((x) => ({
                group: v.name,
                icon: v.icon,
                name: x,
              }));
            return { group: v.name, icon: v.icon, name: v.name };
          })
          .flat()
          .map((x) => (
            <Menu.Item>
              <img src={x.icon} alt={x.group} style={{ width: "20px" }} />{" "}
              {x.name}
            </Menu.Item>
          ))}
          <Menu.Item>No incident</Menu.Item>
      </Menu>
    );
    console.log(incidentMenu);

    return (
      <div className="team">
        <Row>
          <Col span={22}>
            {sheet.club} {sheet.team}
          </Col>
          <Col className="score" span={2}>
            {Object.values(sheet.players)
              .map((x) => x.score || 0)
              .reduce((a, b) => a + b, 0)}
          </Col>
        </Row>
        {Object.keys(sheet.players).map((x, i) => {
          const v = sheet.players[x];
          const [lastName, firstName] = x.split(",", 2);
          return (
            <Row className="player" key={i}>
              <Col span={2}>{v.number}</Col>
              <Col span={8}>{firstName}</Col>
              <Col span={9}>{lastName}</Col>
              <Col span={5}>
                {!v.score || <span className="score">{v.score}</span>}
              </Col>
            </Row>
          );
        })}
        {this.props.active !== true || (
          <Row>
            <Button type="primary">
              Add Players&nbsp;
              <FontAwesomeIcon icon={faUserPlus} />
            </Button>
          </Row>
        )}
        <Modal className="addbox" visible={false}>
          <Row>
            <Col span={24}>
              <Button type="primary">Close</Button>
            </Col>
          </Row>
          <Tabs defaultActiveKey="registered">
            <Tabs.TabPane tab="Registered" key="registered">
              Registered Players
            </Tabs.TabPane>
            <Tabs.TabPane tab="Last Match" key="last-match">
              Last Match Players
            </Tabs.TabPane>
            <Tabs.TabPane tab="Anyone" key="anyone">
              Add any name:
              <Input></Input>
              <Row>
                <Col span={24}>
                  <Button type="primary">Add</Button>
                </Col>
              </Row>
              <p>Note: this does not mean the player is eligible</p>
            </Tabs.TabPane>
          </Tabs>
        </Modal>
        <Modal
          title="Edit Player"
          className="editbox"
          visible
          footer={[
            <Button danger>Clear</Button>,
            <Button type="primary" danger>
              Remove Player
            </Button>,
          ]}
        >
          <h2>Alan MCCORMACK</h2>
          <Input
            placeholder="Shirt Number"
            prefix={<FontAwesomeIcon icon={faTshirt} />}
          />
          <Button onClick={(e) => e.preventDefault()}>Add Goal</Button>
          <Dropdown overlay={incidentMenu}>
            <Button onClick={(e) => e.preventDefault()}>Add Incident</Button>
          </Dropdown>
          <Dropdown overlay={roleMenu}>
            <Button onClick={(e) => e.preventDefault()}>Add Role</Button>
          </Dropdown>
        </Modal>
      </div>
    );
  }
}

export default Teamsheet;
