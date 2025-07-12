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
            // 基于准备好的dom，初始化echarts实例
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');
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
                series: [{
                    name: '平台服务费',
                    type: 'line',
                    smooth: true,
                    symbol:'none',
                    data: Config.value
                }
                ]
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);

            $(window).resize(function () {
                myChart.resize();
            });

            $(document).on("click", ".btn-refresh", function () {
                setTimeout(function () {
                    myChart.resize();
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
