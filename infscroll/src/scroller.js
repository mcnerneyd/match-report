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

        console.log("Range: " + currentTop.fixtureId + " => " + currentBottom.fixtureId + " st:" + this.container.scrollTop);

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
          console.log("top: " + this.state.top);
          this.getData(this.state.top - 1, this.state.top - 5)
            .then(x => {
              if (this.state.top > 0 && x.length > 0) {
                this.setState({
                  rows: x.reverse().concat(this.state.rows),
                  //top: this.state.top - x.length,
                  top: 1
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
          {this.state.rows
            .filter(x => x)
            .map((x,i) => this.props.render ? this.props.render(x,i) : `Row [${i}]: ${x}`)
          }
    </div>
  }
}

export default Scroller;
