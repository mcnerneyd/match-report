import React from 'react';
import './scroller.scss';

class Scroller extends React.Component {

  constructor(props) {
    super(props);
    this.state = { rows: [], top: 0, bottom: 0 };
    this.ticking = false;

    this.getData = (start, finish) => {
      console.log("Fetch " + start + " - " + finish);
      return new Promise((resolve, reject) => {
        resolve(this.props.data(start, finish).then(x => x.filter(y => y != null)));
      });
    }

    this.handleScroll = (e) => {
        this.addRows(3);
    }

    this.addRows = (k) => {
      if (this.container === undefined) {
        console.warn("Container is not defined");
        return;
      }

      console.log("k=" + k);

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

        console.log("Range: " + this.state.top + " => " + this.state.bottom + " st:" + this.container.scrollTop);

        // add new bottom rows first
        if (this.state.bottom >= 0 && currentBottom.offsetTop < (visibleHeight + this.container.scrollTop)) { // top of last row is visible
          this.getData(this.state.bottom + 1, this.state.bottom + 5)
            .then(x => {
              if (x.length == 0) {
                this.setState({
                  bottom: -1
                });
                return;
              }
              console.log("bottom");
              const validRows = x.filter(y => !this.state.rows.map(z => z.fixtureID).includes(y.fixtureID));
              if (validRows.length > 0) {
                this.setState({
                  rows: this.state.rows.concat(validRows),
                  bottom: this.state.bottom + validRows.length,
                });
              }
            });
        } else if (this.state.top <= 0 && currentTop.offsetTop + currentTop.clientHeight > this.container.scrollTop) { // bottom of first row is visible
          console.log("top: " + this.state.top);
          this.getData(this.state.top - 1, this.state.top - 5)
            .then(x => {
              if (x.length == 0) {
                this.setState({
                  top: 1
                });
                return;
              }
              const validRows = x.filter(y => !this.state.rows.map(z => z.fixtureID).includes(y.fixtureID));
              console.log("  +top: " + x.length, validRows);
              if (validRows.length > 0) {
                this.setState({
                  rows: validRows.concat(this.state.rows),
                  top: this.state.top - validRows.length,
                });

                this.ticking = true;
                console.log("scrollTo");
                this.container.scrollTo(0, this.container.scrollTop + 1);
                console.log("scrollTo done");
              }
            });
        }
      }
    }
  }

  componentDidMount() {
    this.addRows(1);
  }

  componentDidUpdate() {
    this.addRows(2);
  }

  render() {
    return <div className='infinite-scroll' ref={(el) => this.container = el}>
          {this.state.rows
            .filter(x => x)
            .map((x,i) => this.props.render ? this.props.render(x,i) : `Row [${i}]: ${x}`)
          }
    </div>
  }
}

export default Scroller;
