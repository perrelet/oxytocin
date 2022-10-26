
/* // Change default options for ALL charts
Chart.defaults.set('plugins.datalabels', {
    color: '#FE777B'
  });
 */
  

function new_chart (nodes, id, type, orientation) {

    /* var img = new Image();
    img.src ='https://digitalis.ca/wp-content/uploads/2022/09/home-brew.01.svg';
    img.src ='https://digitalis.ca/wp-content/uploads/2022/08/bug.512-300x300.png'; */

    new Chart(document.getElementById(id).getContext("2d"), {
        plugins: [ChartDataLabels],
        type,
        data: {
            /* labels: nodes.map((d) => d.info), */
            datasets: [{
                pointBackgroundColor: nodes.map((d) => d.color),
                edgeLineBorderColor: '#fff',
                edgeLineBorderWidth: 10,
                pointRadius: 20,
                pointBorderWidth: 8,
                pointBorderColor: '#fff',
                pointHoverRadius: 30,
                pointHoverBorderWidth: 8,
                pointHoverBorderColor: "#fff",
                directed: true,
                arrowHeadSize: 0,
                arrowHeadOffset: 20,
                datalabels: {
                    color: nodes.map((d) => d.label_color)
                },
                clip: 100,
                //pointStyle: img,
                data: nodes.map((d) => Object.assign({}, d)),
            }]
        },
        options: {
            /* maintainAspectRatio: false, */
            tree: {
                orientation
            },
            layout: {
                padding: {
                    top: 0,
                    bottom: 0,
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
                        size: 18,
                        weight: '500',
                        family: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif"
                    },
                    labels: {
                    },
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

                //let url = data[index].hasOwnProperty('url') ? data[index].url : false;
                //if (url) window.open(url, '_self');

                //const context = document.createElement("div");
                //context.id = 'chart-context-menu';
                
            },
            onHover: (e, els) => {
                const target = e.native ? e.native.target : e.target;
                target.style.cursor = els[0] ? 'pointer' : 'default';
            }
        }
    });

}

