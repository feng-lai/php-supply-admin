define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            parent.window.$(".layui-layer-iframe").find(".layui-layer-close").on('click',function () {
                parent.$(".btn-refresh").trigger("click");
            });

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'offline/index' + location.search,
                    multi_url: 'carousel/multi',
                    import_url: 'carousel/import',
                    table: 'offline'
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                visible: false,//浏览模式(卡片切换)、显示隐藏列、导出、通用搜索全部隐藏
                showToggle: false,//浏览模式可以切换卡片视图和表格视图两种模式
                showColumns: false,//列，可隐藏不显示的字段
                search:false,//快速搜索，搜索框
                pk: 'id',
                sortName: 'id',
                searchFormVisible:true,
                searchFormTemplate: 'customformtpl',
                columns: [
                    [
                        {field: 'pay_sn', title: '付款编号', operate: 'LIKE',formatter:Table.api.formatter.content},
                        {field: 'total_count', title: '订单总金额', operate: 'LIKE',formatter:Table.api.formatter.content},
                        {field: 'ownership_no', title: '所属订单编号', operate: 'LIKE',formatter:Table.api.formatter.content},
                        {field: 'idx', title: '支付状态', operate: 'LIKE',formatter:Table.api.formatter.content},
                        {field: 'pay_file', title: '支付凭证', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'pay_count', title: '本期应收金额', operate: 'LIKE',formatter:Table.api.formatter.search},
                        {field: 'pay_tip', title: '备注信息', operate: 'LIKE',formatter:Table.api.formatter.search},
                        {field: 'user.nickname', title: '提交用户', operate: 'LIKE',formatter:Table.api.formatter.search},
                        // {field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {
                            field: 'order.status',
                            title: "订单状态",
                            formatter: function (value) {
                                if(value === '0'){
                                    return "服务中-待付款";
                                }else if(value === '1'){
                                    return "服务中-待审核";
                                }else if(value === '2'){
                                    return "服务中-服务中";
                                }else if(value === '3'){
                                    return "服务中-待验收";
                                }else if(value === '4'){
                                    return "服务中-待跟进";
                                }else if(value === '5'){
                                    return "已完成";
                                }else if(value === '6'){
                                    return "已取消";
                                }
                            }
                        },
                        {
                            field: 'status',
                            title: "审核状态",
                            formatter: function (value) {
                                if(value === 0){
                                    return "待审核";
                                }else if(value === 1){
                                    return "已通过";
                                }else if(value === 2){
                                    return "未通过";
                                }
                            }
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text: "查看详情",
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '详情',
                                    text: '详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'offline/detail',
                                    callback: function (data) {
                                        console.log(data)
                                    },
                                    visible: function (row) {
                                        console.log(row)
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'status',
                                    title: '通过',
                                    text: '通过',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'offline/status',
                                    callback: function (data) {
                                        console.log(data)
                                    },
                                    visible: function (row) {
                                        if(row.status == 0){
                                            return true
                                        }else{
                                            return false
                                        }
                                    }
                                },
                                {
                                    name: 'nopass',
                                    title: '拒绝',
                                    text: '拒绝',
                                    classname: 'btn btn-xs btn-default btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'offline/status',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if(row.status == 0){
                                            return true
                                        }else{
                                            return false
                                        }
                                    },
                                    click: function (e, row) {
                                        Layer.prompt({
                                            title: "拒绝理由",
                                            success: function (layero) {
                                                $("input", layero).prop("placeholder", "填写拒绝理由");
                                            }
                                        }, function (value) {
                                            console.log(value)
                                            var pap = {
                                                vertify_refuse_reason: value
                                            }
                                            Fast.api.ajax({
                                                url: "order/order/nopass/ids/" + row.order_id,
                                                data: pap,
                                            }, function (data, ret) {
                                                Layer.closeAll();
                                                $(".btn-refresh").trigger("click");
                                                //return false;
                                            });
                                        });
                                        return false;
                                    }
                                }

                            ],
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () {
            Form.api.bindevent($("form[role=form]"), function(data, ret){
                Toastr.success("成功");
                Fast.api.close(); // 关闭窗体并回传数据
                parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
            }, function(data, ret){
                Toastr.success("失败");
            }, function(success, error){
                return false;
            });
            Controller.api.bindevent();
        },
        status: function () {
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
