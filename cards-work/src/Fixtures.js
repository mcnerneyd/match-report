import React, { useState, useEffect, useContext, Fragment } from 'react';
import moment from 'moment'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faEnvelope, faNoteSticky, faRefresh } from '@fortawesome/free-solid-svg-icons'
import { getFixtures, getFixtures2, selectCard, emailDetail } from './Api'
import { Fixture } from './Fixture'
import { UserContext } from './Context'
import { takeWhile } from './Util.js'
import { Button, ButtonGroup, ToggleButton, Form } from 'react-bootstrap';

function Fixtures({ cardId, search }) {

  const user = useContext(UserContext)

  const [fixtures, setFixtures] = useState({ ts: 0, fixtures: [] })
  const [checked, setChecked] = useState({ results: true, fixtures: true })
  const [filter, setFilter] = useState(user?.club ? [{ type: 'club', value: user.club }] : [])
  const [card, setCard] = useState(null)
  const [adminFilter, setAdminFilter] = useState("")

  useEffect(() => {
    getFixtures(user, fixtures)
      .then(setFixtures)
      .then(getFixtures(user, fixtures, true).then(setFixtures))
    //getFixtures2(user).then(setFixtures)
  }, [user])

  const adminRole = (user?.roles ?? []).includes("Administrators")

  console.log("Card", card, cardId, fixtures?.fixtures?.length)

  if ((card == null) && (cardId != null) && (fixtures.fixtures.length > 0)) {
    console.log("Get Card", cardId)
    const selectedFixture = fixtures.fixtures.find(x => String(x.fixtureID) === String(cardId))
    if (selectedFixture) {
      console.log("Selected fixture", selectedFixture, fixtures, selectedFixture.fixtureID, cardId)
      selectCard(selectedFixture).then(setCard)
    }
    return <div class='user-alert alert alert-warning alert-small'>
      This fixture (#{cardId}) does not exist
    </div>
  }

  if (card) return <Fixture card={{ ...card }} close={() => setCard(null)} updateCard={setCard} />

  const renderFixture = (fixture) => {
    const cn = ['fixture', 'status-' + fixture.status]
    if (fixture.played) cn.push("fixture-played")
    const e0 = fixture.played && (fixture.home.score !== fixture.home.reported)
    const e1 = fixture.played && (fixture.away.score !== fixture.away.reported)
    if (e0 || e1) cn.push("error-score")
    const e2 = fixture.played && !fixture.home?.players
    const e3 = fixture.played && !fixture.away?.players
    if (e2 || e3) cn.push("error-player")
    if (!fixture.played && moment().isAfter(moment(fixture.datetimeZ).endOf("day")))
      cn.push("error-late")

    const display = (adminFilter != "todo") || (cn.includes("error-late") || cn.includes("error-score") || cn.includes("error-player") || (fixture.notes ? fixture.notes.filter(n => !n.r).length > 0 : false))
    const key = `${fixture.section}.${fixture.competition}.${fixture.home.name}.${fixture.away.name}`.toLowerCase().replace(/ /g, "")

    return <Fragment key={fixture.section + fixture.fixtureID}>
      {fixture.monthBreak
        ? <tr className='month-marker'>
          <th colSpan={20}>{fixture.datetime.format("MMMM YYYY")}</th>
        </tr>
        : null}
      {display ?
        <tr data-fixtureid={fixture.fixtureID} data-key={key} className={cn.join(" ")} data-searchstring={fixture.searchString}
          onClick={() => {
            console.log("Selected fixture", fixture)
            window.document.location = "/cards/index.php?site=&controller=card&action=get&fid=" + fixture.fixtureID + "&s=" + fixture.section
          }}>
          <td className='date'>{fixture.datetime.format("D")}</td>
          <td className='time'>{fixture.datetime.format("HH:mm")}</td>
          <td>{fixture.section}</td>
          <td><span className='badge label-league'>{fixture.competition}</span></td>
          <td className={e0 || e2 ? 'team-error' : null}><span className='long'>{fixture.home.name}</span><span className='short'>{fixture.home_club} v {fixture.away_club}</span></td>
          <td className={e1 || e3 ? 'team-error' : null}><span className='long'>{fixture.away.name}</span>
            {fixture.notes ? <span class='annotations'><FontAwesomeIcon icon={faNoteSticky} /></span> : null}
          </td>
          <td className='mail-btn' onClick={(e) => {
            e.stopPropagation()
            emailDetail(fixture.fixtureID).then((data) => {
              const url = "mailto:" + data.to.join() + "?cc=" + data.cc.join()
                + "&subject=" + encodeURIComponent(data.subject)
                + "&body=" + encodeURIComponent(data.body);
              window.open(url, 'blank')
            })
          }}>
            <FontAwesomeIcon icon={faEnvelope} />
          </td>
        </tr>
        : null}
      {fixture.notes
        ? fixture.notes.filter(n => !n.r).map((note, ix) => <tr className='note' key={fixture.id + "-" + ix}>
          <td colSpan={20}><FontAwesomeIcon icon={faNoteSticky} /> <i>{note.u}</i> {note.v}</td>
        </tr>)
        : null}
    </Fragment>
  }

  var fixturesSet = fixtures.fixtures.filter(x => x.active && ((x.played && checked.results) || (!x.played && checked.fixtures)))

  if (search !== null) {
    console.log("Search", search)
    fixturesSet = fixturesSet.filter(f => {
      return search.every(s => f.searchString.includes(s))
    })
  }

  var clubs = []
  var competitions = []
  var sections = []

  var checkFilters = [...filter]
  if (!checkFilters.find(x => x.type === 'section')) checkFilters.push({ type: "section", value: user?.section })
  if (!checkFilters.find(x => x.type === 'club')) checkFilters.push({ type: "club" })
  if (!checkFilters.find(x => x.type === 'competition')) checkFilters.push({ type: "competition" })
  checkFilters.forEach((fv) => {
    if (fv.type === "section") {
      sections = [...new Set(fixturesSet.map(f => f.section))]
      if (fv.value) fixturesSet = fixturesSet.filter(x => x.section === fv.value)
    }
    if (fv.type === "club") {
      clubs = [...new Set(fixturesSet.flatMap(f => [f.home_club, f.away_club]))]
      if (fv.value) fixturesSet = fixturesSet.filter(x => x.home_club === fv.value || x.away_club === fv.value)
    }
    if (fv.type === "competition") {
      competitions = [...new Set(fixturesSet.map(f => f.competition))]
      if (fv.value) fixturesSet = fixturesSet.filter(x => x.competition === fv.value)
    }
  })

  clubs.sort()
  competitions.sort()
  sections.sort()

  var lastMonth = ""
  fixturesSet.forEach(f => {
    const m = f.datetime.format("MMMM YYYY")
    if (m !== lastMonth) {
      f.monthBreak = true
      lastMonth = m
    } else f.monthBreak = false
  })

  const updateFilter = (s, v) => {
    var vs = takeWhile(filter, x => x.type === s)
    if (v) {
      vs.push({ type: s, value: v })
    }
    setFilter(vs)
  }

  const filterValue = (type) => {
    const match = filter.find(x => x.type === type)
    return match ? match.value : ""
  }

  console.log("Rendering fixtures", checkFilters, fixturesSet.length < 20 ? fixturesSet : fixturesSet.length)

  return <>
    <div id='fixtures-tab'>
      {adminRole ? <div>
        <label>Admin</label>
        <Form.Select onChange={e => setAdminFilter(e.target.value)}>
          <option value="">Filter...</option>
          <option value="todo">Todo</option>
        </Form.Select>
        {/* <Button className="btn" onClick={() => localStorage.removeItem("fixtures_timestamp")}><FontAwesomeIcon icon={faRefresh}/></Button> */}
      </div>
        : null}
      <form className={user?.section ? "with-section" : null}>
        <Form.Select id='pills-section' className='custom-select' onChange={(e) => updateFilter('section', e.target.value)}>
          <option key='all-sections' value="">All Sections</option>
          {sections.map(c => <option key={'section-' + c}>{c}</option>)}
        </Form.Select>
        <Form.Select id='pills-club' value={filterValue('club')} className='custom-select' onChange={(e) => updateFilter('club', e.target.value)}>
          <option key='all-clubs' value="">All Clubs</option>
          {clubs.map(c => <option key={'club-' + c}>{c}</option>)}
        </Form.Select>
        <Form.Select id='pills-competition' className='custom-select' onChange={(e) => updateFilter('competition', e.target.value)}>
          <option key='all-competitions' value="">All Competitions</option>
          {competitions.map(c => <option key={'competition-' + c}>{c}</option>)}
        </Form.Select>
        <ButtonGroup>
          <ToggleButton type="checkbox" checked={checked.results} onClick={() => { setChecked({ results: !checked.results, fixtures: checked.fixtures || checked.results }) }}>Results</ToggleButton>
          <ToggleButton type="checkbox" checked={checked.fixtures} onClick={() => { setChecked({ results: checked.fixtures || checked.results, fixtures: !checked.fixtures }) }}>Fixtures</ToggleButton>
        </ButtonGroup>
      </form>
    </div>
    <div id='fixtures-container' className={user?.section ? "with-section" : null}>
      <table id='fixtures'>
        <tbody>
          {fixturesSet.map(renderFixture)}
        </tbody>
      </table>
    </div>
  </>
}

export default Fixtures;
