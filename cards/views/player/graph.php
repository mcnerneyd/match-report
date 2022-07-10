<style>
svg {
	shape-rendering: crispEdges;
}
.rule line {
	stroke: #eee;
}
</style>
<div id='chart'></div>

<script src="https://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script>

function addBar(g,col,y,h) {
	vis.append("rect")
		.attr("fill", "#fee")
		.attr("fill-opacity", 0.5)
		.attr("x", col*50)
		.attr("y", y)
		.attr("width", 50)
		.attr("height", h-y);

	vis.append("line")
		.style("stroke", "#d88")
		.style("stroke-width", "2")
		.attr("x1", col*50)
		.attr("x2", (col+1)*50)
		.attr("y1", y)
		.attr("y2", y);
}

var data = [
				{x:1.25,y:3},
				{x:1.5,y:3},
				{x:2.25,y:2},
				{x:2.5,y:3},
				{x:3.25,y:2},
				{x:3.5,y:3},
				{x:4.75,y:1},
				{x:5.5,y:3},
				{x:5.5,y:1},
				{x:6.5,y:2},
				],
		labels = ['Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar','Apr'],
		w = 400,
		h = 300,
		x = d3.scale.linear().domain([0, 8]).range([0, w]),
		y = d3.scale.linear().domain([8, 0]).range([h, 0]);

var data2 = [
				{x:2,y:3},
				{x:3,y:3},
				{x:4,y:2.5},
				{x:5,y:2.5},
				{x:6,y:1},
				{x:7,y:1.5},
				{x:8,y:1.5},
				];

var vis = d3.select("#chart")
		.data(data)
	.append("svg:svg")
		.attr("width", w + 80)
		.attr("height", h + 80)
	.append("svg:g")
		.attr("transform","translate(20,20)");


var rules = vis.selectAll("g.rule")
		.data(x.ticks(9))
	.enter().append("svg:g")
		.attr("class","rule");

   rules.append("svg:line")
    .attr("x1", x)
    .attr("x2", x)
    .attr("y1", 0)
    .attr("y2", h - 1);

   rules.append("svg:line")
    .attr("class", function(d) { return d ? null : "axis"; })
    .data(y.ticks(8))
    .attr("y1", y)
    .attr("y2", y)
    .attr("x1", 0)
    .attr("x2", w + 10);

   rules.append("svg:text")
    .attr("x", x)
    .attr("y", h + 15)
    .attr("dy", ".71em")
    .attr("text-anchor", "middle")
    .text(x.tickFormat(10))
    .text(function(d,i) { return labels[i]; });

   rules.append("svg:text")
    .data(y.ticks(8))
    .attr("y", y)
    .attr("x", -10)
    .attr("dy", ".35em")
    .attr("text-anchor", "end")
    .text(y.tickFormat(5));

	var vl = d3.svg.line()
			.x(function(d) { return x(d.x); })
			.y(function(d) { return y(d.y); })
			//.interpolate("step-before");
			.interpolate("step-before");

	vis.selectAll("g")
		.append("path")
			.data(data2)
			.attr("fill", "none")
			.attr("stroke", "#d88")
			.attr("stroke-width", "1")
			.attr("d", vl(data2));

	vis.selectAll("circle.line")
		 .data(data)
	 .enter().append("svg:rect")
		 .attr("class", "line")
		 .attr("x", function(d) { return x(d.x)-3; })
		 .attr("y", function(d) { return y(d.y)-3; })
		 .attr("width", 6)
		 .attr("height", 6)
		 .attr("fill", "#eef")
		 //.attr("fill-opacity", 0.5)
		 .attr("stroke", "#88d")
</script>
