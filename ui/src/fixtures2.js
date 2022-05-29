import React, { useState, useEffect, useRef, useLayoutEffect } from 'react';

const Fixtures = () => {
    const pageSize = 5

    const containerRef = useRef(null)
    const topRef = useRef(null)
    const bottomRef = useRef(null)

    const [nextPage, setNextPage] = useState(0)
    const [items, setItems] = useState([])
    const [range, setRange] = useState(null)
    const [visibles, setVisibles] = useState(true)

    console.log("Loop np=", nextPage, range, items.length)

    useEffect(() => {
        const observer = new IntersectionObserver(callback, { 
            root: containerRef.current, rootMargin: "0px", threshold: 0.0 })
        observer.observe(topRef.current)
        observer.observe(bottomRef.current)
        console.log("  useEffect", items.length, nextPage, "tb", topRef.current.visible, bottomRef.current.visible)

        if (range) {
            const allItems = [].slice.call(containerRef.current.firstChild.firstChild.children)
            if (nextPage != null) {
                const h = containerRef.current.clientHeight - allItems.slice(1,-1)
                    .map(x => x.clientHeight).reduce((a,b)=>a+b, 0)
                bottomRef.current.firstChild.height = Math.max(20, h - 5)
                // if (bottomRef.current.visible) {
                //     setNextPage(range.lastPage + 1)
                // } else if (topRef.current.visible) {
                //     setNextPage(range.firstPage - 1)
                // }
                if (bottomRef.current.visible) {
                    setNextPage(np => range.lastPage + 1)
                }
            }
        } else {
            console.log("  Empty", nextPage)
            bottomRef.current.firstChild.height = containerRef.current.clientHeight + 10
        }

        containerRef.current.scrollTop = topRef.current.offsetHeight + 1
        console.log("  useEffect done")

    }, [nextPage, items, range, visibles])

    if (items.length < 30)
    if (range == null || (nextPage == range.lastPage + 1 || nextPage == range.firstPage - 1)) {
        console.log("  Fetch", nextPage)
        fetch(`http://cards.leinsterhockey.ie/api/fixtures?p=${nextPage}&n=${pageSize}`)
        .then((res) => res.json())
        .then((list) => {
            console.log("  Resultsx:", nextPage, list.fixtures)
            const newFixtures = list.fixtures
            for (let i=0;i<newFixtures.length;i++) newFixtures[i].date = i + (nextPage*pageSize)

            let fp = 0, lp = 0
            if (range) {
                fp = range.firstPage
                lp = range.lastPage
            }

            if (newFixtures.length > 0) {
                if (nextPage < 0) {
                    fp = nextPage - 1
                    setItems(f => [...newFixtures, ...f])
                    console.log("   A")
                } else {
                    lp = nextPage + 1
                    setItems(f => [...f, ...newFixtures])
                    console.log("   B")
                }
            } else {
                console.log("   C")
                if (nextPage < 0) fp = null
                if (nextPage > 0) lp = null
            }

            console.log("   End:", nextPage, range)
            setRange({firstPage: fp, lastPage: lp})
        })
    }

    function callback(entries) {
        entries.forEach(entry => { entry.target.visible = entry.isIntersecting })
        if (visibles.top != topRef.current.visible || visibles.bottom != bottomRef.current.visible) {
            console.log("tb", visibles, topRef.current.visible, bottomRef.current.visible)
            //setVisibles({top: topRef.current.visible, bottom: bottomRef.current.visible})
        }
    }

    return <div>
        <p>Page: {nextPage} n={items.length} f={range?.firstPage} l={range?.lastPage}</p>
    <div ref={containerRef} style={{height:'300px',overflowY:'auto'}}>
    <table style={{width:'100%',borderSpacing:0}}>
    <tbody>
        <tr ref={topRef} key='top' style={{backgroundColor:"green"}}>
            <td colSpan='5'>Top</td>
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
        <tr ref={bottomRef} key='bottom'>
            <td colSpan='5'></td>
        </tr>
    </tbody>
</table>
</div></div>;

}

export default Fixtures;
