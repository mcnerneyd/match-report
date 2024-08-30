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
        debugger;
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

export function login(username, password) {
    console.log("Logging in", username, password)
    fetch(calcpath('Login'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded' // FIXME not secure
        },
        body: "user="+username+"&pin="+password
    })
    .then(() => { window.location.reload() })
}

export function getFixtures(user, fixtures, expand = false) {
    const ts = localStorage.getItem("fixtures_timestamp")
    
    console.log("Get fixtures user", user, moment(parseInt(ts)).format())

    const clean = (s) => s ? (""+s).replaceAll(/[^A-Za-z0-9]/g, "") : ""

    const params = {}
    if (user?.section != null) params['section'] = user.section
    //if (user?.roles?.includes('Administrators')) params['expand'] = 'true'
    if (expand) params['expand'] = 'true'
    const headers = {}
    if (ts) headers['If-Modified-Since'] = moment(parseInt(ts)).format()
    console.log("headers", headers)
    return fetch(calcpath(`fixtureapi/index2?` + new URLSearchParams(params).toString()), { headers })
        .then(response => { 
            if (response.status === 404) return null
            if (response.status === 304) return null
            if (!response.ok) return response.text().then(r => { throw new Error(r) })
            return response.json() 
        })
        .then((fs) => {
            var newFixtures = fs
            localStorage.setItem("fixtures_timestamp", Date.now())
            if (newFixtures !== null) {
                console.log("New fixtures:", Date.now(), newFixtures.fixtures, newFixtures.ts, fixtures.ts)
                newFixtures.fixtures.forEach(f => {
                    if (!f.fixtureID) console.error("Bad fixture:", f)
                    f.datetime = moment(f.datetimeZ).local()
                    f.played = f.played === 'yes'
                    f.home = { score: f.home_score, name: f.home_club + " " + f.home_team, 
                                players: f.home_players ?? '0',
                                reported: f.home_reported_score ?? '0' }
                    f.away = { score: f.away_score, name: f.away_club + " " + f.away_team, 
                                players: f.away_players ?? '0',
                                reported: f.away_reported_score ?? '0' }
                    f.searchString = (clean(f.home_club) + clean(f.home_team) + " " + clean(f.away_club) + clean(f.away_team) + " " + 
                    clean(f.competition) + " " + 
                    f.datetime.format("YYYY/MM/DD") + " " + 
                    f.section + " " + f.fixtureID).toLowerCase()
                    f.active = (f.status ? f.status === 'active' : true)  // active defaults to true
                })
                newFixtures.fixtures.sort((a,b) => { return a.datetime.valueOf() - b.datetime.valueOf() })
            } else {
                console.log("Existing fixtures:", Date.now())
                newFixtures = JSON.parse(localStorage.getItem("fixtures"))
            }

            newFixtures.fixtures.forEach(f => {
                f.datetime = moment(f.datetimeZ).local()
            })
            
            console.log("Fixtures retrieved", Date.now(), newFixtures)
            localStorage.setItem("fixtures", JSON.stringify(newFixtures))
            return newFixtures
        })
}

export function getFixtures2(user) {
    const ts = localStorage.getItem("fixtures_timestamp")
    
    console.log("Get fixtures user", user)

    const params = {}
    if (user?.section != null) params['section'] = user.section
    const headers = {}
    //const sameSection = user?.section !== localStorage.getItem("section")
    //if (sameSection && ts) headers['If-Modified-Since'] = moment(parseInt(ts)).format() 
    console.log("headers", headers)
    return fetch(calcpath(`fixtureapi/index21?` + new URLSearchParams(params).toString()), 
        { headers })
    .then(response => { 
        if (response.status === 404) return null
        if (response.status === 304) return localStorage.getItem("fixtures")
        if (!response.ok) return response.text().then(r => { throw new Error(r) })
        return response.json() 
    })
    .then((newFixtures) => {
        if (newFixtures === null) return []
        newFixtures.fixtures.sort((a,b) => { return a.datetime.valueOf() - b.datetime.valueOf() })
        console.log("Fixtures retrieved", newFixtures)
        localStorage.setItem("fixtures_timestamp", Date.now())
        localStorage.setItem("fixtures", newFixtures)
        localStorage.setItem("section", user?.section)
        return newFixtures
    })
}

export function addPlayer(club, cardId, playerName) {
    return postApi(`cardapi/player?player=${playerName}&club=${club}&matchcardid=${cardId}`)
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
    console.log("Fetching card", fixture)
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