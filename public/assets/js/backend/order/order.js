define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'layui'], function ($, undefined, Backend, Table, Form, layui) {
    var status_x = false;
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/order/index' + location.search,
                    add_url: 'order/order/add',
                    edit_url: 'order/order/edit',
                    del_url: 'order/order/del',
                    multi_url: 'order/order/multi',
                    import_url: 'order/order/import',
                    table: 'order',
                }
            });

            var table = $("#table");


            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var that = $(this);
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    filter['sn'] = $("#sn").val();
                    filter['id'] = $("#id").val();
                    filter['title'] = $("#title").val();
                    filter['user.nickname'] = $('#myTabContent .form-group:eq(3) input:eq(1)').val();
                    filter['specialist.id_no_name'] = $('#myTabContent .form-group:eq(4) input:eq(1)').val();
                    if($("#total-min").val()){
                        filter['total'] = $("#total-min").val()+','+$("#total-max").val();
                    }

                    filter['num'] = $("#num").val();
                    filter['now_point'] = $("#now_point").val();
                    filter['createtime'] = $("#createtime").val();
                    console.log(filter);
                    for (var key in filter) {
                        if (filter[key] === '') {
                            delete filter[key];
                        }
                    }
                    var op = {};
                    if($("#total-min").val()){
                        op['total'] = 'BETWEEN';
                    }

                    if($('#myTabContent .form-group:eq(3) input:eq(1)').val()){
                        op['user.nickname'] = 'like';
                    }

                    if($('#myTabContent .form-group:eq(4) input:eq(1)').val()){
                        op['specialist.id_no_name'] = 'like';
                    }

                    switch(value){
                        case 'all':
                            break;//如果tab的value是all，那就是没有附加参数，就什么也不做
                        case 8:
                            filter.is_excp =  '1';
                            break;
                        case 9:
                            filter.is_stop =  '2';
                            break;
                        default:
                            if (value >= 0 && value <= 7) {
                                filter.status =  value;
                            }
                            break;
                    }
                    //下面就要把filter，op序列化之后写进params对象，bootstrapTable('refresh', {})会用新的params去请求后端服务器，这里如果我说的不够清楚的话，可以看一下浏览器开发者工具里面对应的url。
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;

            });


            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                visible: false,//浏览模式(卡片切换)、显示隐藏列、导出、通用搜索全部隐藏
                showToggle: false,//浏览模式可以切换卡片视图和表格视图两种模式
                showColumns: false,//列，可隐藏不显示的字段
                search:false,//快速搜索，搜索框
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'sn', title: __('Sn'), operate: 'LIKE'},
                        // {field: 'rid', title: __('Rid')},
                        {field: 'title', title: __('Title'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'user.nickname', title: __('User_id'), operate: 'LIKE'},
                        {field: 'specialist.id_no_name', title: __('Specialist_id'), operate: 'LIKE'},
                        {field: 'total', title: __('Total'), operate:'BETWEEN'},
                        {field: 'num', title: __('Num')},
                        {field: 'now_point', title: __('Now_point')},
                        {
                            field: 'status',
                            title: "订单状态",
                            searchable:false,
                            formatter: function (value,row) {
                                if (row.is_stop === 2) {
                                    return "已中止";
                                }else if (row.is_excp === 1 && row.is_stop === 1) {
                                    return "异常待处理";
                                } else if (value === '0') {
                                    return "服务中-待付款";
                                } else if (value === '1') {
                                    return "服务中-待审核";
                                } else if (value === '2') {
                                    return "服务中-服务中";
                                } else if (value === '3') {
                                    return "服务中-待验收";
                                } else if (value === '4') {
                                    return "服务中-待跟进";
                                } else if (value === '5') {
                                    return "已完成";
                                } else if (value === '6') {
                                    return "已取消";
                                } else if (value === '7') {
                                    return "未确认收款";
                                }
                            }
                        },
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'specialist_source', title: __('Specialist_source')},
                        // {field: 'need_acceptance', title: __('Need_acceptance'), searchList: {"0":__('Need_acceptance 0'),"1":__('Need_acceptance 1')}, formatter: Table.api.formatter.normal},
                        // {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'finishtime', title: __('Finishtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: "查看详情",
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'order/order/edit'
                                },
                                {
                                    name: 'detail',
                                    title: '立即处理',
                                    text: '立即处理',
                                    classname: 'btn btn-xs btn-primary btn-click',
                                    // icon: 'fa fa-list',
                                    // url: 'order/order/edit',
                                    click: function (data,row) {
                                        console.log(row)
                                        Fast.api.open("order/order/edit/ids/"+row.ids+'?tab=4' , '立即处理')
                                    },
                                    visible: function (row) {
                                        if (row.is_excp === 1 && row.is_stop !== 2) {
                                            return true;
                                        }else{
                                            return false;
                                        }

                                    }
                                },{
                                    name: 'offline',
                                    title: '收款审核',
                                    text: '收款审核',
                                    classname: 'btn btn-xs btn-primary btn-click',
                                    // icon: 'fa fa-list',
                                    // url: 'order/order/edit',
                                    click: function (data,row) {
                                        Fast.api.open("offline/index?order_id="+row.id, '收款审核')
                                    },
                                    visible: function (row) {
                                        if (row.offline_id > 0  && row.is_stop !== 2) {
                                            return true;
                                        }else{
                                            return false;
                                        }

                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            Fast.config.openArea = ['100%','100%'];
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'order/order/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '140px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'order/order/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'order/order/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            let choose = false
            layui.use(["form", "upload"], function (form, upload) {
                if(Config.tab == 4){
                    $(".nav-tabs li").removeClass("active");
                    $(".nav-tabs li:nth-child(4)").addClass("active");
                    $(".body1").hide()
                    $(".body2").hide()
                    $(".body3").hide()
                    $(".body4").hide()
                    $(".body4").show()
                }
                var form = layui.form;

                // 监听开关按钮状态
                form.on('switch', function(data){
                    // data.elem 指向当前 checkbox DOM 元素
                    var isChecked = data.elem.checked;
                    choose = isChecked
                    // 打印选中状态
                    console.log('开关按钮是否选中：' + isChecked);
            
                    // 可以在这里进行其他操作，根据 isChecked 的值执行相应逻辑

                    if(isChecked){
                        $("#debit").show();
                    }else{
                        $("#debit").hide();
                    }
                });
            
                // 这一步是必须的，用于渲染开关按钮
                form.render('checkbox');
            });

            $('[data-toggle="tab"]').click(function (e) {
                
                var type = $(e.target).data('value')
                // 清空区域列表

                $(".body1").hide()
                $(".body2").hide()
                $(".body3").hide()
                $(".body4").hide()
                $(".body"+type).show()
                
            })

            //付款审核
            $(document).on('click', '#pay-vertify', function(e) { 
                let orderid = $(e.target).data('order-id')
                // let info = $(e.target).data('info')
                // $('#text').html(info)
                Layer.open({
                    type: 1,
                    area: ['80%', '80%'],
                    btn: ['确认通过', '不通过'],
                    shade: 0.3,
                    zIndex:10,
                    closeBtn: 1,
                    title:"支付信息确认",
                    yes: function(index, layero){
                        //按钮【按钮一】的回调
                        console.log("yes")
                        Layer.open({
                            type: 1,
                            area: ['80%', '300px'],
                            btn: ['确认通过', '不通过'],
                            shade: 0.3,
                            zIndex:10,
                            closeBtn: 1,
                            title:"审核通过",
                            yes: function(index2, layero2){
                                //按钮【按钮一】的回调
                                let img = $('#c-avatar').val()
                                console.log("img",img)
                                var text = '';
                                Fast.api.ajax({
                                    url: "order/order/pass/ids/" + orderid,
                                    data: {
                                        'info':img
                                    },
                                }, function (data, ret) {
                                    // Layer.closeAll();
                                    // $(".btn-refresh").trigger("click");
                                    //return false;
                                });
                                layer.close(index2,layero2);
                            },
                            content: $('#layer-pass'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                            success(){
                                console.log("success111")
                            }
                        });
                        // layer.close(index,layero);
                    },
                    content: $('#layer-info'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                    success(){
                        console.log("success111")
                    }
                });

             })
            //异常审核-excp
            $('.excp').click(function(e) { 
                console.log('excp')
                let id = $(e.target).data('id')
                layer.confirm('请选择操作类型', {
                    btn: ['正常进行', '订单终止'] //可以无限个按钮
                }, function(index, layero){
                    layer.close(index,layero);
                    //按钮【按钮一】的回调
                    console.log('正常进行')
                    Layer.open({
                        type: 1,
                        area: ['500px', '300px'],
                        btn: ['确认', '取消'],
                        shade: 0.3,
                        zIndex:10,
                        closeBtn: 1,
                        title:"正常进行",
                        yes: function(index2, layero2){
                            //按钮【按钮一】的回调
                            console.log("yes")
                            let dealinfo = $('#dealinfo').val()
                            console.log("dealinfo",dealinfo)
                            var text = '';
                            Fast.api.ajax({
                                url: "order/order/excp_pass/ids/" + id,
                                data: {
                                    'dealinfo':dealinfo
                                },
                            }, function (data, ret) {
                                // Layer.closeAll();
                                $(".excp_"+id).html("正常进行");
                                console.log(e,"================")
                            });
                            layer.close(index2,layero2);
                            parent.location.reload();
                            // layer.close(index,layero);
                        },
                        content: $('#excp-pass'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                        success(){
                            console.log("11111111111111")
                        }
                    });
                }, function(index){
                    //按钮【按钮二】的回调
                    console.log('终止订单')
                    Layer.open({
                        type: 1,
                        area: ['80%', '80%'],
                        btn: ['确认', '取消'],
                        shade: 0.3,
                        zIndex:10,
                        closeBtn: 1,
                        title:"订单终止",
                        yes: function(index2, layero2){
                            //按钮【按钮一】的回调
                            console.log("yes")
                            let dealinfo = $('#dealinfo2').val()
                            let is_debit_fee = choose?'1':'0'
                            let debit_per = $('#debit-per').val()
                            let debit_explan = $('#debit-explan').val()
                            console.log("is_debit_fee:",choose)
                            
                            var text = '';
                            Fast.api.ajax({
                                url: "order/order/excp_nopass/ids/" + id,
                                data: {
                                    'dealinfo':dealinfo,
                                    'is_debit_fee':is_debit_fee,
                                    'debit_per':debit_per,
                                    'debit_explan':debit_explan
                                },
                            }, function (data, ret) {
                                $(".excp_"+id).html("订单终止");
                                // Layer.closeAll();
                                // $(".btn-refresh").trigger("click");
                                //return false;
                            });
                            layer.close(index2,layero2);
                            // layer.close(index,layero);
                        },
                        content: $('#excp-nopass'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                        success(){
                            console.log("success111")
                        }
                    });
                });
                return
                // let info = $(e.target).data('info')
                // $('#text').html(info)
                

             })

            var inputElement = document.getElementById('debit-per');
            inputElement.addEventListener('blur', function() {
                var inputValue = parseInt(inputElement.value);
                console.log(inputValue);
                // 判断是否是0到100的整数
                if(Number.isInteger(inputValue) && inputValue >= 0 && inputValue <= 100) {
                    var price = $("#price").val();
                    var count_price = price - price * (inputValue / 100);
                    count_price = count_price.toFixed(2)
                    count_price = Math.round(count_price * 100) / 100;
                    var return_price = price-count_price;
                    return_price = return_price.toFixed(2)
                    return_price = Math.round(return_price * 100) / 100;
                    $("#count_price").html(return_price+'元')
                    $("#return_price").html(count_price+'元')
                } else {
                    $("#price").val(0);
                    layer.msg('请输入0到100之间的整数');
                }
            });

            $(document).on("change", "#is-debit-fee", function(){
                var menuswitch = $("#is-debit-fee").val();
                console.log(menuswitch);
            });

            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
