define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template', 'layui','bootstrap-daterangepicker'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template , undefined,daterangepicker) {

    var Controller = {
        index: function () {
            $(".datetimepicker").daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD',
                    customRangeLabel: __("Custom Range"),
                    applyLabel: __("Apply"),
                    cancelLabel: __("Clear"),
                },
                "autoUpdateInput": false,
            });
            $('.datetimepicker').on('apply.daterangepicker', function(ev, picker) {
                $('.datetimepicker').val(picker.startDate.format('YYYY-MM-DD')+' - '+picker.endDate.format('YYYY-MM-DD'));
            });
            $('.datetimepicker').on('cancel.daterangepicker', function(ev, picker) {
                $('.datetimepicker').val('');
            });


            // 需求方数据折线图
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');
            let data = [
                {
                    name: '新增用户数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.value.new
                },
                {
                    name: '活跃用户数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.value.active
                },
                {
                    name: '新增实名数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.value.new_verify
                },
                {
                    name: '新增企业用户数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.value.company_user
                },
                {
                    name: '活跃企业数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.value.company_active_user
                },
                {
                    name: '企业禁用数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.value.company_disable_user
                }
            ]

            //专家数据折线图
            var s_myChart = Echarts.init(document.getElementById('s_echart'), 'walden');
            let s_data = [
                {
                    name: '新增专家数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.s_value.s_new
                },
                {
                    name: '活跃专家数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.s_value.s_active
                },
                {
                    name: '专家禁用数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.s_value.s_disable
                }
            ]

            //需求分析折线图
            var re_myChart = Echarts.init(document.getElementById('re_echart'), 'walden');
            let re_data = [
                {
                    name: '新增需求数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.re_value
                },
            ]

            //订单分析折线图
            var or_myChart = Echarts.init(document.getElementById('or_echart'), 'walden');
            let or_data = [
                {
                    name: '新增订单数',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.or_value
                },
            ]


            // 指定图表的配置项和数据
            var option = {
                title: {
                    show: false, // 是否显示标题，默认true
                    right: 'center',
                    text: "平台服务费营收趋势图" //主标题文本，支持使用 \n 换行。
                 },
                color: ['#ffe68b', '#F47100'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        crossStyle: {
                            color: '#999'
                        },
                        lineStyle: {
                            type: 'dashed'
                        }
                    }
                },
                legend: {
                    icon: "rect",
                    show: true,
                    height:20,
                    top: 'bottom'
                },
                xAxis: {
                    type: 'category',
                    data: Config.dates

                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        color: '#999',
                        textStyle: {
                            fontSize: 12
                        }
                    }
                },
                series: data
            };

            var s_option = {
                title: {
                    show: false, // 是否显示标题，默认true
                    right: 'center',
                    text: "平台服务费营收趋势图" //主标题文本，支持使用 \n 换行。
                },
                color: ['#ffe68b', '#F47100'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        crossStyle: {
                            color: '#999'
                        },
                        lineStyle: {
                            type: 'dashed'
                        }
                    }
                },
                legend: {
                    icon: "rect",
                    show: true,
                    height:20,
                    top: 'bottom'
                },
                xAxis: {
                    type: 'category',
                    data: Config.s_dates

                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        color: '#999',
                        textStyle: {
                            fontSize: 12
                        }
                    }
                },
                series: s_data
            };

            var re_option = {
                title: {
                    show: false, // 是否显示标题，默认true
                    right: 'center',
                    text: "平台服务费营收趋势图" //主标题文本，支持使用 \n 换行。
                },
                color: ['#ffe68b', '#F47100'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        crossStyle: {
                            color: '#999'
                        },
                        lineStyle: {
                            type: 'dashed'
                        }
                    }
                },
                legend: {
                    icon: "rect",
                    show: true,
                    height:20,
                    top: 'bottom'
                },
                xAxis: {
                    type: 'category',
                    data: Config.re_dates

                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        color: '#999',
                        textStyle: {
                            fontSize: 12
                        }
                    }
                },
                series: re_data
            };

            var or_option = {
                title: {
                    show: false, // 是否显示标题，默认true
                    right: 'center',
                    text: "平台服务费营收趋势图" //主标题文本，支持使用 \n 换行。
                },
                color: ['#ffe68b', '#F47100'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        crossStyle: {
                            color: '#999'
                        },
                        lineStyle: {
                            type: 'dashed'
                        }
                    }
                },
                legend: {
                    icon: "rect",
                    show: true,
                    height:20,
                    top: 'bottom'
                },
                xAxis: {
                    type: 'category',
                    data: Config.or_dates

                },
                yAxis: {
                    type: 'value',
                    axisLabel: {
                        color: '#999',
                        textStyle: {
                            fontSize: 12
                        }
                    }
                },
                series: or_data
            };


            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);

            s_myChart.setOption(s_option);

            re_myChart.setOption(re_option);

            or_myChart.setOption(or_option);

            $(window).resize(function () {
                myChart.resize();
                s_myChart.resize();
                re_myChart.resize();
                or_myChart.resize();
            });

            $(document).on("click", ".btn-refresh", function () {
                setTimeout(function () {
                    myChart.resize();
                    s_myChart.resize();
                    re_myChart.resize();
                    or_myChart.resize();
                }, 0);
            });
        }

    };

    $(".btn-filter").click(function(){
        var time = $(this).data('time');
        $(".datetimerange").val(time);
        $("#form1").submit();
    })

    return Controller;
});
