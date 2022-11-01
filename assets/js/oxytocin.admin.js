
/* // Change default options for ALL charts
Chart.defaults.set('plugins.datalabels', {
    color: '#FE777B'
  });
 */
  

function new_chart (nodes, index, type, orientation, theme) {

    var themes = {
        light: {
            colors: {
                edge: '#fff',
                label: '#999',
                node: {
                    template: '#25d1a0',
                    part: '#f9bb3e',
                    section: '#cd55fc',
                    post: '#cd55fc',
                }
            }
        },
        dark: {
            colors: {
                edge: '#443961',
                label: '#fbf0ff',
                node: {
                    template: '#25d1a0',
                    part: '#f9bb3e',
                    section: '#cd55fc',
                    post: '#cd55fc',
                }
            }
        },
    }

    let oxytocin_chart = {

        index: null,
        chart: null,

        el: {
            context: {
                menu: null,
                edit: null,
                builder: null,
                info: null,
                notes: null,
            },
            wrap: null,
        },

        theme: theme,

        init: function (index) {

            this.index = index;
            this.chart = this.create_chart(nodes, index, type, orientation);

            document.addEventListener('DOMContentLoaded', this.ready.bind(this));

        },

        ready: function () {

            this.el.wrap = this.chart.canvas.parentElement;
            this.el.wrap.classList.add('theme-' + this.theme);

            this.el.context.menu = document.getElementById('chart-context-menu');
            this.el.context.edit = document.getElementById('chart-context-edit');
            this.el.context.builder = document.getElementById('chart-context-builder');
            this.el.context.info = document.getElementById('chart-context-info');
            this.el.context.notes = document.getElementById('chart-context-notes');

            this.el.context.menu.addEventListener('mouseleave', this.ctx_close.bind(this));

        },

        create_chart: function (nodes, index, type, orientation) {

            var OxtocinPlugin = {

                afterRender: function(chart, options) {

                    if (chart.$rendered) return;
                    
                    chart.$rendered = true;

                    chart.canvas.parentElement.classList.remove('loading');

                    setTimeout(function(){
                        chart.canvas.parentElement.classList.add('loaded');
                    }.bind(chart), 1000);

                }

            };

            return new Chart(document.getElementById('oxytocin-graph-' + index).getContext("2d"), {
                plugins: [OxtocinPlugin, ChartDataLabels],
                type,
                data: {
                    /* labels: nodes.map((d) => d.info), */
                    datasets: [{
                        //pointBackgroundColor:       nodes.map((d) => d.color),
                        pointBackgroundColor:       nodes.map((d) => themes[this.theme].colors.node[d.type]),
                        edgeLineBorderColor:        themes[this.theme].colors.edge,
                        edgeLineBorderWidth:        10,
                        pointRadius:                nodes.map((d) => d.type == 'section' ? 12 : 20),
                        pointBorderWidth:           8,
                        pointBorderColor:           themes[this.theme].colors.edge,
                        pointHoverRadius:           nodes.map((d) => d.type == 'section' ? 18 : 30),
                        pointHoverBorderWidth:      8,
                        pointHoverBorderColor:      themes[this.theme].colors.edge,
                        directed:                   true,
                        arrowHeadSize:              0,
                        arrowHeadOffset:            20,
                        datalabels: {
                            color: nodes.map((d) => d.current ? d.color : themes[this.theme].colors.label)
                        },
                        clip: 100,
                        //pointStyle: img,
                        data: nodes.map((d) => Object.assign({}, d)),
                    }]
                },
                options: {
                    maintainAspectRatio: false,
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
                                //return value.name;// + "\n" + "(" + value.post_type + ")";
                                let label = value.name;
                                if (value.hasOwnProperty('current') && value.current) label += "\n(Current " + value.post_type + ")";
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
        
                        //let chart_index = e.chart.canvas.getAttribute('data-index');
                        //let data = chart_data[chart_index];

                        let index = els[0].index;
                        let x = e.native.clientX - 46;
                        let y = e.native.clientY - 46;
                        let open_context = this.ctx_open.bind(this);

                        open_context(index, x, y);
        
                        //let url = data[index].hasOwnProperty('url') ? data[index].url : false;
                        //if (url) window.open(url, '_self');
                        
                    },
                    onHover: (e, els) => {
                        const target = e.native ? e.native.target : e.target;
                        target.style.cursor = els[0] ? 'pointer' : 'default';
                    }
                }
            });

        },

        ctx_open: function (index, x, y) {

            this.ctx_show_all();

            pt = chart_data[this.index][index];

            this.ctx_update_item('edit', pt, 'url', 'href');
            this.ctx_update_item('edit', pt, 'open_label', 'innerText');
            this.ctx_update_item('builder', pt, 'builder', 'href');
            if(!this.ctx_update_item('notes', pt, 'notes', 'innerText')) this.el.context.info.style.display = 'none';

            this.el.context.menu.style.left = x + "px";
            this.el.context.menu.style.top = y + "px";
            this.el.context.menu.classList.add(pt.type);
            this.el.context.menu.classList.add('open');

        },

        ctx_close: function () {

            this.el.context.menu.className = "";

        },

        ctx_show_all: function () {

            this.el.context.edit.style.display = 'block';
            this.el.context.builder.style.display = 'block';
            this.el.context.builder.style.display = 'block';
            this.el.context.info.style.display = 'block';
            this.el.context.notes.style.display = 'block';

        },

        ctx_update_item: function (item, pt, key, prop, is_critical = true) {

            if (pt.hasOwnProperty(key) && pt[key]) {
                this.el.context[item][prop] = pt[key];
                return true;
            } else {
                if (is_critical) this.el.context[item].style.display = 'none';
                return false;
            }

        }

    };

    oxytocin_chart.init(index);

}



