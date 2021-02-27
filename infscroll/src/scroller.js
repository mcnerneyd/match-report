import React from 'react';
import './scroller.scss';

class Scroller extends React.Component {

  constructor(props) {
    super(props);
    this.state = { rows: [], top: 0, bottom: 0 };

    this.getData = (start, finish) => {
      console.log("Fetch " + start + " - " + finish);
      return new Promise((resolve, reject) => {
        resolve(this.props.data(start, finish).then(x => x.filter(y => y != null)));
      });
    }

    this.handleScroll = () => {
      if (this.container === undefined) {
        console.warn("Container is not defined");
        return;
      }

      if (this.state.bottom > 50) return;

      if (this.container.children.length === 0) {  // if it is empty, add the first row
        this.container.addEventListener('scroll', this.handleScroll);

        this.getData(0, 5).then(x => {
          this.setState({
            rows: x,
            bottom: x.length,
          });
        });
      } else {
        const currentTop = this.container.children[0];
        const currentBottom = this.container.children[this.container.children.length - 1];
        const visibleHeight = this.container.clientHeight;

        //console.log("Range: " + currentTop.textContent + " => " + currentBottom.textContent);

        // add new bottom rows first
        if (this.state.bottom >= 0 && currentBottom.offsetTop < (visibleHeight + this.container.scrollTop)) { // top of last row is visible
          this.getData(this.state.bottom + 1, this.state.bottom + 5)
            .then(x => {
              console.log("bottom");
              if (x.length > 0) {
                this.setState({
                  rows: this.state.rows.concat(x),
                  bottom: this.state.bottom + x.length,
                });
              } else {
                this.setState({
                  bottom: -1
                })
              }
            });
        } else if (this.state.top <= 0 && currentTop.offsetTop + currentTop.clientHeight > this.container.scrollTop) { // bottom of first row is visible
          console.log("top");
          this.getData(this.state.top - 1, this.state.top - 5)
            .then(x => {
              if (x.length > 0) {
                this.setState({
                  rows: x.reverse().concat(this.state.rows),
                  top: this.state.top - x.length,
                });

                this.container.scrollTo(0, this.container.scrollTop + 1);
              } else {
                this.setState({
                  top: 1
                })
              }
            });
        }
      }
    }
  }

  componentDidMount() {
    this.handleScroll();
  }

  componentDidUpdate() {
    this.handleScroll();
  }

  render() {
    return <div className='infinite-scroll' ref={(el) => this.container = el}>
          {this.state.rows.filter(x => x)
            .map((x,i) => <tr className='infinite-scroll-row' key={i} keyx={x[this.props.keyField]}>
              <td>{x.datetimeZ} </td>
              <td>{x.competition} </td>
              <td>{x.home} </td>
              <td> v </td>
              <td>{x.away} </td>
              <td style={{ width: "300px" }}> {x.i} / {x.fixtureID}</td>
              {/*this.props.render ? this.props.render(x) : "Row " + x[this.props.keyField]*/}
            </tr>
            )}
    </div>
  }
}

export default Scroller;
