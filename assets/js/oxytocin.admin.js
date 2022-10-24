
// Change default options for ALL charts
Chart.defaults.set('plugins.datalabels', {
    color: '#FE777B'
  });

function new_chart (nodes, id, type, orientation) {

    new Chart(document.getElementById(id).getContext("2d"), {
        plugins: [ChartDataLabels],
        type,
        data: {
            labels: nodes.map((d) => d.name),
            datasets: [{
                pointBackgroundColor: nodes.map((d) => d.color),
                edgeLineBorderColor: '#DDDDDD',
                pointRadius: 20,
                pointHoverRadius: 30,
                directed: true,
                arrowHeadSize: 16,
                arrowHeadOffset: 20,
                datalabels: {
                    color: nodes.map((d) => d.color)
                },
                data: nodes.map((d) => Object.assign({}, d)),
            }]
        },
        options: {
            tree: {
                orientation
            },
            layout: {
                padding: 32
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
            }
        }
    });
}

