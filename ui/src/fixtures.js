import React, { useState, useEffect, useRef, useLayoutEffect } from 'react';
import _ from 'lodash';

function Fixtures() {

    const pageSize = 10
    const containerRef = useRef(null)
    const topRef = useRef(null)
    const bottomRef = useRef(null)

    const [starting, setStarting] = useState(true)
    const [active, setActive] = useState({top: false, bottom: false})
    const [items, setItems] = useState([])
    const [fixtures, setFixtures] = useState({
        bottomAdd: 0, topAdd: 0,
        topDone: false, bottomDone: false,
        firstPage:0, lastPage:-1})

    //  console.log("State:", active, fixtures)

    useEffect(() => {
        const observer = new IntersectionObserver(callback, { 
            root: containerRef.current, rootMargin: "0px", threshold: 0.0 })
        observer.observe(topRef.current)
        observer.observe(bottomRef.current)
        containerRef.current.scrollTop = topRef.current.offsetHeight + 2
        topRef.current.visible = false
        console.log("Use Effect", containerRef.current.scrollTop)
        console.log(starting, '- Has changed')
        containerRef.current.addEventListener("scroll", (e) => {
            console.log("Scroll",e)
        })
        return () => {
            console.log("Cleanup")
        }
    })

    useLayoutEffect(() => {
        const allItems = [].slice.call(containerRef.current.firstChild.firstChild.children)
        const iitems = allItems.slice(1,-1)
        const s0 = containerRef.current.scrollTop
        console.log("Use Layout Effect", iitems.length, s0, starting)

        if (starting) {
            fixtures.bottomAdd = 0
            let h = iitems.map(x => x.offsetHeight).reduce((a,b) => a+b, 0)
            if (h > containerRef.current.offsetHeight || fixtures.bottomDone) {
                console.log("starting done", containerRef.current.offsetHeight, h)
                bottomRef.current.firstChild.height = containerRef.current.offsetHeight - h
                containerRef.current.scrollTop = 2
                setStarting(false)
            } else {
                get(fixtures.lastPage + 1)
            }
        }
    })

    function get(page) {
        console.log("  Get page", items.length, page, active, starting)
        if (page >= 0) { // bottom
            if (!active.bottom) return
            console.log("  Getting bottom")
            fetch(`http://cards.leinsterhockey.ie/api/fixtures?p=${page}&n=${pageSize}`)
                .then((res) => res.json())
                .then((list) => {
                    const newFixtures = list.fixtures
                    if (newFixtures.length > 0 && page <= 0) {
                        console.log("    Add " + newFixtures.length + " at bottom", page)
                        for (let i=0;i<pageSize;i++) newFixtures[i].date = i + (page*pageSize)
                        setFixtures({
                            lastPage: page,
                            bottomAdd: fixtures.bottomAdd + newFixtures.length})
                        setItems([...items, ...newFixtures ])
                    } else {
                        setFixtures({
                            ...fixtures,
                            bottomDone: true
                        })
                    }
                });
        } else { // top
            if (page < -3) return
            if (!active.top) return
            if (starting) return
            console.log("  Getting top")
            fetch(`http://cards.leinsterhockey.ie/api/fixtures?p=${page}&n=${pageSize}`)
                .then((res) => res.json())
                .then((list) => {
                    const newFixtures = list.fixtures
                    if (newFixtures.length > 0) {
                        console.log("    Add " + newFixtures.length + " at top", page, containerRef.current.topLastAdded)
                        for (let i=0;i<pageSize;i++) newFixtures[i].date = (i + (page*pageSize))
                        setFixtures({...fixtures,
                            firstPage: page,
                            topAdd: fixtures.topAdd + newFixtures.length})
                        setItems([ ...newFixtures,  ...items])
                    } else {
                        setFixtures({
                            ...fixtures,
                            topDone: true
                        })
                    }
                });
        }
    }

    function callback(entries) {
        entries.forEach(entry => { entry.target.visible = entry.isIntersecting })

        const newBottom = bottomRef.current.visible || false
        const newTop = topRef.current.visible || false 
        console.log("Callback", newTop, newBottom, active)

        const bottomChangedToVisible = newBottom && !active.bottom
        active.bottom = newBottom
        if (bottomChangedToVisible) {
            get(fixtures.lastPage + 1)
        }
        const topChangedToVisible = newTop && !active.top
        active.top = newTop
        if (topChangedToVisible) {
            get(fixtures.firstPage - 1)
        }
    }

    console.log("Rendering", fixtures)

    return <div ref={containerRef} style={{height:'300px',overflowY:'auto'}}>
        <table>
        <tbody>
            <tr ref={topRef} key='top' style={{backgroundColor:"green", display: starting ? 'none' : 'table-row'}}>
                <td>Top {fixtures.firstPage}</td>
            </tr>
        {items.map(fixture => {
            return <tr key={fixture.id}>
                <td>{fixture.date}</td>
                <td>{fixture.competition}</td>
                <td>{fixture.home.name}</td>
                <td>{fixture.home.score + "v" + fixture.away.score}</td>
                <td>{fixture.away.name}</td>
            </tr>
        })}
            <tr ref={bottomRef} key='bottom' style={{backgroundColor:"red"}}><td>Bottom {fixtures.lastPage}</td></tr>
        </tbody>
    </table>
    </div>;
}

export default Fixtures;