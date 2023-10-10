import { createContext } from 'react';

/*export function fadeOut(id) {
    const elm = document.getElementById(id)
    var op = 100
    if (elm) {
        var timer = setInterval(function() {
            if (op < 10) {
                clearInterval(timer)
                elm.style.display = 'none'
            }
            elm.style.opacity = op
            elm.style.filter = 'alpha(opacity=' + op * 100 + ')'
            op *= 0.9 
        }, 50)
    }
}*/

export const UserContext = createContext()
