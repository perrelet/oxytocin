
/* // Change default options for ALL charts
Chart.defaults.set('plugins.datalabels', {
    color: '#FE777B'
  });
 */
  

function new_chart (nodes, id, type, orientation) {
    new Chart(document.getElementById(id).getContext("2d"), {
        plugins: [ChartDataLabels],
        type,
        data: {
            labels: nodes.map((d) => d.name),
            datasets: [{
                pointBackgroundColor: nodes.map((d) => d.color),
                edgeLineBorderColor: '#e8e8e8',
                edgeLineBorderWidth: 10,
                pointRadius: 20,
                pointHoverRadius: 30,
                directed: true,
                arrowHeadSize: 32,
                arrowHeadOffset: 10,
                datalabels: {
                    color: nodes.map((d) => d.color)
                },
                clip: 100,
                data: nodes.map((d) => Object.assign({}, d)),
            }]
        },
        options: {
            tree: {
                orientation
            },
            layout: {
                padding: {
                    top: 20,
                    bottom: 20,
                    left: 100,
                    right: 100,
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                },
                datalabels: {
                    formatter: function(value, context) {
						//return value.name;// + "\n" + "(" + value.type + ")";

                        let label = value.name;
                        if (value.hasOwnProperty('current') && value.current) label += "\n(Current " + value.type + ")";
						return label;
					},
                    align: 'end',
                    anchor: 'end',
                    textAlign: 'center',
                    font: {
                        size: 16
                    }
                    /* display: true,
                    color: '#36A2EB',
                    backgroundColor: 'red',
                     */
                }
            },
            onClick: (e, els) => {
                if (!els.length) return;
                /* console.log(e);
                console.log(els);
                console.log(e.chart.canvas.id);
                console.log(e.chart.getDatasetMeta(0)); */

                let chart_index = e.chart.canvas.getAttribute('data-index');
                let data = chart_data[chart_index];
                let index = els[0].index;
                let url = data[index].hasOwnProperty('url') ? data[index].url : false;

                if (url) window.open(url, '_self');
                
            },
            onHover: (e, els) => {
                const target = e.native ? e.native.target : e.target;
                target.style.cursor = els[0] ? 'pointer' : 'default';
            }
        }
    });
}

