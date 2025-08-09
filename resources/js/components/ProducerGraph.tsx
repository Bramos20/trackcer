import React, { useEffect, useRef, forwardRef, useImperativeHandle } from 'react';
import * as d3 from 'd3';

const ProducerGraph = forwardRef((props, ref) => {
  const containerRef = useRef(null);

  useImperativeHandle(ref, () => ({
    requestFullscreen: () => {
      const el = containerRef.current;
      if (!document.fullscreenElement) el.requestFullscreen();
      else document.exitFullscreen();
    },
  }));

  useEffect(() => {
    let currentSvg = null;
    let currentG = null;
    let currentSimulation = null;

    const updateGraphDimensions = (container, svg, g) => {
      const width = container.offsetWidth;
      const height = container.offsetHeight;
      svg.attr("width", width).attr("height", height);
      g.attr("transform", `translate(${width / 2}, ${height / 2})`);
    };

    const fetchGraphData = () => {
      fetch(route('producer.graph.data'))
        .then(res => res.json())
        .then(data => {
          const container = d3.select(containerRef.current);
          const width = container.node().offsetWidth || 1200;
          const height = container.node().offsetHeight || 500;

          container.select("svg").remove();

          const svg = container
            .append("svg")
            .attr("width", width)
            .attr("height", height)
            .style("background-color", "#1e1e1e")
            .classed("rounded-lg", true)
            .call(d3.zoom().scaleExtent([0.5, 2])
              .on("zoom", (event) => g.attr("transform", event.transform))
            );

          const g = svg.append("g")
            .attr("transform", `translate(${width / 2}, ${height / 2})`);

          currentSvg = svg;
          currentG = g;

          const simulation = d3.forceSimulation(data.nodes)
            .force("link", d3.forceLink(data.links).id(d => d.id).distance(100))
            .force("charge", d3.forceManyBody().strength(-200))
            .force("center", d3.forceCenter(0, 0));

          currentSimulation = simulation;

          const link = g.append("g")
            .selectAll("line")
            .data(data.links)
            .enter()
            .append("line")
            .attr("stroke-width", 2)
            .attr("stroke", "#888");

          const node = g.append("g")
            .selectAll("circle")
            .data(data.nodes)
            .enter()
            .append("circle")
            .attr("r", 10)
            .attr("fill", (d) =>
              d.group === "producer" ? "#FF5555" :
              d.group === "track" ? "#EA6115" : "#6A4BFB"
            )
            .call(d3.drag()
              .on("start", (event, d) => {
                if (!event.active) simulation.alphaTarget(0.3).restart();
                d.fx = d.x;
                d.fy = d.y;
              })
              .on("drag", (event, d) => {
                d.fx = event.x;
                d.fy = event.y;
              })
              .on("end", (event, d) => {
                if (!event.active) simulation.alphaTarget(0);
                d.fx = null;
                d.fy = null;
              })
            );

          const labels = g.append("g")
            .selectAll("text")
            .data(data.nodes)
            .enter()
            .append("text")
            .text(d => d.label)
            .attr("font-size", "12px")
            .attr("fill", "#ffffff")
            .attr("dx", 12)
            .attr("dy", 4);

          simulation.on("tick", () => {
            link
              .attr("x1", d => d.source.x)
              .attr("y1", d => d.source.y)
              .attr("x2", d => d.target.x)
              .attr("y2", d => d.target.y);
            node
              .attr("cx", d => d.x)
              .attr("cy", d => d.y);
            labels
              .attr("x", d => d.x)
              .attr("y", d => d.y);
          });
        });
    };

    document.addEventListener("fullscreenchange", () => {
      if (currentSvg && currentG) {
        updateGraphDimensions(containerRef.current, currentSvg, currentG);
        currentSimulation?.alpha(0.3).restart();
      }
    });

    fetchGraphData();
  }, []);

  return <div ref={containerRef} className="w-full h-full rounded-lg" />;
});

export default ProducerGraph;
