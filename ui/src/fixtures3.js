import React, { useState, useEffect, useRef } from 'react';
import { Dots } from 'loading-animations-react';
import moment from "moment";
import { useNavigate } from "react-router-dom";

const Fixtures = () => {
    const pageSize = 10

    const navigate = useNavigate();
    const containerRef = useRef(null)
    const topRef = useRef(null)
    const bottomRef = useRef(null)
    const items = useRef([])

    const [range, setRange] = useState(null)
    const [visible, setVisible] = useState({top: true, bottom: true})

    //console.log("Loop ", range, items.current.length, visible)

    useEffect(() => {
        const observer = new IntersectionObserver(callback, { 
            root: containerRef.current, rootMargin: "0px", threshold: 0.0 })
        observer.observe(topRef.current)
        observer.observe(bottomRef.current)

        if (!range) {
            //console.debug("  Empty", nextPage)
            bottomRef.current.firstChild.height = containerRef.current.clientHeight + 10
        } else {
            const allItems = [].slice.call(containerRef.current.firstChild.firstChild.children)

            if (range.firstPage == 0) {
                // Initialization
                const topHeight = topRef.current.firstChild.clientHeight
                const h = containerRef.current.clientHeight - allItems.slice(1,-1)
                .map(x => x.clientHeight).reduce((a,b)=>a+b, 0)
                bottomRef.current.firstChild.height = Math.max(topHeight, h)
            }
        }

        containerRef.current.scrollTop = topRef.current.clientHeight + 5
    }, [range])

    const calcNextPage = () => {
        if (range == null) return 0
        if (range.currentPage != null) return null
        if (visible.bottom && range.lastPage != null) {
            return range.lastPage + 1
        }
        if (visible.top && range.firstPage != null) {
            return range.firstPage - 1;
        }
        return null
    }

    const nextPage = calcNextPage()

    if (nextPage != null) {
        if (range != null) range.currentPage = nextPage;
        //console.debug("  Fetch", nextPage)
        fetch(`http://cards.leinsterhockey.ie/api/fixtures?p=${nextPage}&n=${pageSize}`)
        .then((res) => res.json())
        .then((list) => {
            //console.debug("Page", nextPage, list.fixtures)
            const newFixtures = list.fixtures
            newFixtures.forEach(f => {
                f.date = moment(f.datetimeZ)
            })

            let fp = 0, lp = 0
            if (range) {
                fp = range.firstPage
                lp = range.lastPage
            }

            if (newFixtures.length > 0) {
                if (nextPage < 0) {
                    fp = nextPage
                    items.current = [...newFixtures, ...items.current]
                } else {
                    lp = nextPage
                    items.current = [...items.current, ...newFixtures]
                }
            } else {
                if (nextPage < 0) fp = null
                if (nextPage > 0) lp = null
            }

            let d = null
            items.current.forEach(f => {
                f.previous = d
                d = f.date      
            })
            const newRange = {firstPage: fp, lastPage: lp, currentPage: null}
            //console.debug("   End:", nextPage, range, "->", newRange)
            setRange(newRange)
        })
    }

    function callback(entries) {
        entries.forEach(entry => entry.target.visible = entry.isIntersecting)

        const newVisible = ({top: topRef.current.visible, bottom: bottomRef.current.visible})
        //console.trace("  0 Visible Callback", visible)
        // only rerender if something is now visible
        if ((!visible.top && newVisible.top)||(!visible.bottom && newVisible.bottom)) {
            setVisible(newVisible)
            //console.trace("  + Visible Callback", newVisible)
        } else {
            visible.top = newVisible.top
            visible.bottom = newVisible.bottom
            //console.trace("  - Visible Callback", newVisible)
        }
    }

    const dots = (condition) => {
        if (condition) {
            return <div style={{width:"8rem",padding:"3px 0"}}><Dots text="" dotColors={['#000','#333','#666','#999','#ccc','#fff']}/></div>
        } else {
            return null
        }
    }

    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];

    const getTime = (d) => {
        return d.format('HH:mm')
    }

    const formatRow = (item) => {
        return <React.Fragment key={item.id}>
            {(item.date?.month() != item.previous?.month()) 
                ? <tr>
                    <td colSpan='5' className='month-break'>{monthNames[item.date.month()]} {item.date.year()}</td>
                </tr> 
                : null }
            <tr onClick={() => { navigate(`/${item.id}`)}}>
                <td className='day-break'>{(item.date.month() != item.previous?.month() || 
                    item.date.date() != item.previous?.date()) 
                ? item.date.date()
                : null }
                </td>
                <td className='time-break'>{(item.date.month() != item.previous?.month() || 
                    item.date.date() != item.previous?.date() ||
                    getTime(item.date) != getTime(item.previous)) 
                ? getTime(item.date)
                : null }
                </td>
                <td>{item.competition}</td>
                <td>{item.home.name}</td>
                <td>{item.played == 'yes' 
                    ? item.home.score + "v" + item.away.score
                    : null}</td>
                <td>{item.away.name}</td>
            </tr>
        </React.Fragment>
    }

    return <div ref={containerRef} style={{position:'absolute',top:"5rem",bottom:0,left:0,right:0,overflowY:'auto',height:'auto'}}>
        <table style={{width:'100%',borderSpacing:0}}>
            <tbody>
                <tr key='top' ref={topRef}>
                    <td colSpan='5'>{dots(range?.firstPage != null)}</td>
                </tr>
                {items.current.map(formatRow)}
                <tr key='bottom' ref={bottomRef}>
                    <td colSpan='5'>{ dots(range?.lastPage != null) }</td>
                </tr>
            </tbody>
        </table>
    </div>;
}

export default Fixtures;
