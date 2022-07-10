import React, { useState, useEffect, useRef } from 'react';
import moment from "moment";
import { useNavigate } from "react-router-dom";
import { useLiveQuery } from "dexie-react-hooks";
import { db } from './db'
import './fixtures.scss'

const Fixtures = () => {

    const [section, setSection] = useState('all')
    const [club, setClub] = useState('all')
    const [competition, setCompetition] = useState('all')
    const [team, setTeam] = useState('all')

    const navigate = useNavigate()
    const fixtures = useLiveQuery(() => db.fixtures.orderBy('datetimeZ').toArray());

    const sections = [...new Set(fixtures?.map(x => x.section))]
    sections.sort()
    const clubs = [...new Set(fixtures?.filter(x => section == 'all' || section == x.section).map(x => x.home.club))]
    clubs.sort()
    const competitions = [...new Set(fixtures?.filter(x => section == 'all' || section == x.section).map(x => x.competition))]
    competitions.sort()
    const teams = [...new Set(fixtures?.filter(x => section == 'all' || section == x.section)
        .filter(x => club == 'all' || club == x.home.club)
        .map(x => x.home.club + " " + x.home.team))]
    teams.sort()

    const selectedFixtures = fixtures?.filter(x => {
        if (club != 'all' && !(x.home.club == club || x.away.club == club)) return false;
        if (section != 'all' && x.section != section) return false;
        if (competition != 'all' && x.competition != competition) return false;
        return true;
    })

    selectedFixtures?.forEach((v, i, a) => {
        v.date = moment(v.datetimeZ)
        if (i>0) {
            const prev = a[i-1]
            v.prev = prev
            if (prev.date.month() != v.date.month()) {
                v.daybreak = true
                v.monthbreak = true
                prev.nextdaybreak = true
                prev.nextmonthbreak = true
            } else if (prev.date.date() != v.date.date()) {
                v.daybreak = true
                prev.nextdaybreak = true
            }
        } else {
            v.monthbreak = true
            v.daybreak = true
        }
    })

    selectedFixtures?.forEach(x => console.log(x))

    return <>
        <h1>Fixtures</h1>
        <header className='fixtures'>
            <select onChange={e => setSection(e.target.value)}>
                <option value='all'>All Sections</option>
                {sections.map(x => <option key={'section:' + x}>{x}</option>)}
            </select>
            <select onChange={e => setClub(e.target.value)}>
                <option value='all'>All Clubs</option>
                {clubs.map(x => <option key={'club:' + x}>{x}</option>)}
            </select>
            <select onChange={e => setCompetition(e.target.value)}>
                <option value='all'>All Competitions</option>
                {competitions.map(x => <option key={'comp:' + x}>{x}</option>)}
            </select>
            <select onChange={e => setTeam(e.target.value)}>
                <option value='all'>All Teams</option>
                {teams.map(x => <option key={'team:' + x}>{x}</option>)}
            </select>
        </header>
        <ol>
        {selectedFixtures?.map(item => {
            return <>
                {item.monthbreak
                ? <h2 className='month-break' key={'m' + item.id}>
                    <time dateTime={item.date.format('YYYY-MM')}>{item.date.format('MMMM YYYY')}</time>
                  </h2>
                : null}

                {item.daybreak
                ? <h3 className='day-break' key={'d' + item.id}>
                    <time dateTime={item.date.format('YYYY-MM-DD')}>
                        <span className='sm day-date'>{item.date.format('dddd D')}</span>
                        <span className='lg date'>{item.date.format('D')}</span>
                        <span className='lg day'>{item.date.format('dddd')}</span>
                    </time>
                  </h3>
                : null}

                { item.daybreak || item.date.format('HH:mm') != item.prev?.date.format('HH:mm')
                ? <h4 className='time-break'><time dateTime={item.date.format('HH:mm')}>{item.date.format('h:mm')}</time></h4>
                : null}

            <li className={'fixture' + (item.nextdaybreak ? ' day-break-after' : '')} key={item.id} onClick={() => { navigate(`/${item.id}`)}}>
                <div className='lg'>{item.competition}</div>
                <div className='sm'>{item['competition-code']}</div>
                <div className='lg'>{item.home.name}</div>
                <div className='lg'>{item.played == 'yes' 
                    ? item.home.score + "v" + item.away.score
                    : null}</div>
                <div className='lg'>{item.away.name}</div>
                <div className='sm'>{item.home.name} <strong>v</strong> {item.away.name}</div>
            </li></>})}
        </ol>
    </>
}

export default Fixtures;