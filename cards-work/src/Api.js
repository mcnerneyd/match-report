import moment from 'moment'

function calcpath(path) {
    const base = window.location.origin
  
    return `${base}/${path}`
}

function getApi(path) {
    return fetch(calcpath(path))
    .then(response => { 
        if (!response.ok) return response.text().then(r => { throw new Error(r) })
        return response 
    })}

function postApi(path) {
    return fetch(calcpath(path), {method:'POST'})
    .then(response => { 
        if (!response.ok) return response.text().then(r => { throw new Error(r) })
        return response 
    })
}

function api(path) {
    return getApi(path)
    .then((res) => {
        if (res.status === 404) return null
        else return res.json()
    })
}
  
/*export function getUser() {
    return getApi("userapi")
    .then((res) => res.status === 401 ? {} : res.json())
    .then((user) => {
      console.log("User:", user)
      return user
    })
    .catch((err) => console.log("Error", err))
}*/

export function getFixtures(user, fixtures) {
    console.log("Get fixtures user", user)
    return api(`fixtureapi/index2` + (user.section != null ? "?section=" + user.section : ""))
    .then((newFixtures) => {
        debugger;
        console.debug("Fixtures:", newFixtures.ts, fixtures.ts)
        newFixtures.fixtures.forEach(f => {
            f.datetime = moment(f.datetimeZ)
            f.played = f.played === 'yes'
            f.home = { score: f.home_score, name: f.home_club + " " + f.home_team }
            f.away = { score: f.away_score, name: f.away_club + " " + f.away_team  }
        })
        newFixtures.fixtures.sort((a,b) => { return a.datetime.valueOf() - b.datetime.valueOf() })
        console.log("Fixtures retrieved", newFixtures)
        return newFixtures
    })
}

export function addPlayer(user, cardId, playerName) {
    return postApi(`cardapi/player?player=${playerName}&club=${user.club}&matchardid=${cardId}`)
}

export function emailDetail(fixtureId) {
    return api("fixtureapi/contact?id=" + fixtureId)
    .then((data) => {
        return {
            body: "Link to card: " + window.location.origin + "/card/" + fixtureId,
            cc: data.cc,
            to: data.to,
            subject: data.subject
        }
    })
}

export function selectCard(fixture) {
    const card = {
        competition: fixture.competition,
        date: moment(fixture.datetimeZ),
        fixture_id:fixture.fixtureID,
        home: {
            club: fixture.home_club,
            team: fixture.home_team,
            players: [],
        },
        away: {
            club: fixture.away_club,
            team: fixture.away_team,
            players: []
        }
    }    
    return api("api/fixtures/" + card.fixture_id)
    .then((res) => {
    if (res == null) {
        return card
    }

    const convertPlayers = (team) => Object.keys(team.players).map(n => {
        const ns = n.split(", ")
        const m = team.players[n]
        return {
            "name": n,
            "firstname": ns[1],
            "lastname": ns[0],
            "number": m.number,
            "date": m.date,
            "detail": m.detail ? JSON.parse(m.detail) : {}
        }
    })

    const cardValue = res.data
    cardValue.date = moment(cardValue.date, "YYYY.MM.DD HH:mm") // FIXME api should send standard date format
    cardValue.home.players = convertPlayers(cardValue.home)
    cardValue.away.players = convertPlayers(cardValue.away)
    cardValue.away.scorers = []
    return cardValue
    })
}