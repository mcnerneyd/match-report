import React, { useState, useEffect, useContext, Fragment } from 'react';
import moment from 'moment'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faEnvelope } from '@fortawesome/free-solid-svg-icons'
import Button from 'react-bootstrap/Button'
import Form from 'react-bootstrap/Form'
import { getFixtures, selectCard, emailDetail } from './Api'
import { Fixture } from './Fixture'
import { UserContext } from './Context'

function Fixtures({cardId}) {

  const [fixtures, setFixtures] = useState({ts:0,fixtures:[]})
  const [filter, setFilter] = useState({})
  const [card, setCard] = useState(null)

  const user = useContext(UserContext)

  useEffect(() => {
    if (user != null) getFixtures(user, fixtures).then(setFixtures)
  }, [user])

  if (!card && cardId && fixtures.fixtures) {
    const selectedFixture = fixtures.fixtures.find(x => x.fixtureID === cardId)
    if (selectedFixture) selectCard(selectedFixture).then(setCard)
  }

  if (card) return <Fixture card={{...card}} close={() => setCard(null)} updateCard={setCard} /> 

  const renderFixture = (fixture) => {
    const cn = ['fixture']
    const e0 =  fixture.played && fixture.home.score !== fixture.home.match_score
    const e1 =  fixture.played && fixture.away.score !== fixture.away.match_score
    if (e0 || e1) cn.push("error-score")
    const e2 = fixture.played && !fixture.home?.players
    const e3 = fixture.played && !fixture.away?.players
    if (e2 || e3) cn.push("error-player")
    if (!fixture.played && moment().isAfter(moment(fixture.datetimeZ).endOf("day"))) 
      cn.push("error-late")

    return <Fragment key={fixture.section + fixture.fixtureID}>
      {fixture.monthBreak
      ? <tr className='month-marker'>
        <th colSpan={20}>{fixture.datetime.format("MMMM YYYY")}</th>
      </tr>
      : null}    
      <tr data-fixtureid={fixture.fixtureID} className={cn.join(" ")}
        onClick={() => window.document.location = "/cards/index.php?site=&controller=card&action=get&fid="+fixture.fixtureID+"&s="+fixture.section}>
      <td className='date'>{fixture.datetime.format("D")}</td>
      <td className='time'>{fixture.datetime.format("HH:mm")}</td>
      { user?.section == null ? <td>{fixture.section}</td> : null }
      <td><span className='badge label-league'>{fixture.competition}</span></td>
      <td className={e0 || e2 ? 'team-error' : null}>{fixture.home.name}</td>
      <td className={e1 || e3 ? 'team-error' : null}>{fixture.away.name}</td>
      <td className='mail-btn' onClick={(e) => {
        e.stopPropagation()
        emailDetail(fixture.fixtureID).then((data) => {
          const url = "mailto:" + data.to.join() + "?cc="+data.cc.join()
            +"&subject="+ encodeURIComponent(data.subject)
            +"&body="+ encodeURIComponent(data.body);
          window.open(url, 'blank')
        })}}>
        <FontAwesomeIcon icon={faEnvelope} />
      </td>
    </tr>
    </Fragment>
  }

  var fixturesSet = fixtures.fixtures
    .filter(f => !filter.club || filter.club === f.home_club || filter.club === f.away_club)
    .filter(f => !filter.competition || filter.competition === f.competition)

  var lastMonth = ""
  fixturesSet.forEach(f => {
    const m = f.datetime.format("MMMM YYYY")
    if (m !== lastMonth) {
      f.monthBreak = true
      lastMonth = m
    } else f.monthBreak = false
  })

  const clubs = [...new Set(fixturesSet.flatMap(f => [f.home_club, f.away_club]))]
  const competitions = [...new Set(fixturesSet.map(f => f.competition))]
  clubs.sort()
  competitions.sort()

  console.debug("Rendering fixtures", filter)

  return <>
  <form id='fixtures-tab'>
    <Form.Select id='pills-club' className='custom-select' onChange={(e) => setFilter({...filter, 'club':e.target.value})}>
      <option key='all-clubs' value="">All Clubs</option>
      { clubs.map(c => <option key={'club-'+c}>{c}</option>) }
    </Form.Select>
    <Form.Select id='pills-competition' className='custom-select' onChange={(e) => setFilter({...filter, 'competition':e.target.value}) }>
      <option key='all-competitions' value="">All Competitions</option>
      { competitions.map(c => <option key={'competition-'+c}>{c}</option>) }
    </Form.Select>
    <div className="btn-group btn-group-toggle d-flex" data-toggle="buttons">
      <Button className="btn active btn-secondary w-100">Results</Button>
      <Button className="btn active btn-secondary w-100">Fixtures</Button>
    </div>
  </form>
  <div id='fixtures-container'>
    <table id='fixtures'>
      <tbody>
        { fixturesSet.map(renderFixture) }
      </tbody>
    </table>
    </div>
  </>
}

export default Fixtures;
