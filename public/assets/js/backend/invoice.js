define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            let type = $('#type').val();
            console.log('type',type)
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invoice/index/type/'+type+ location.search,
                    add_url: 'invoice/add',
                    edit_url: 'invoice/edit',
                    del_url: 'invoice/del',
                    multi_url: 'invoice/multi',
                    import_url: 'invoice/import',
                    table: 'invoice',
                }
            });
            console.log(location)

            var table = $("#table");
            var col = [
                [
                    {checkbox: true},
                    {field: 'id', title: __('Id')},
                    {field: 'order_id', title: __('Order_id')},
                    {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                    {field: 'title', title: __('Title'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'company_number', title: __('Company_number'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                    {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                    {field: 'apply_user_id', title: __('Apply_user_id')},
                    {field: 'rate', title: __('Rate'), operate:'BETWEEN'},
                    {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                    {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                ]
            ];
            if(parseInt(type) === 1){
                col = [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'type', title: "票据类型", searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'title', title: "开票抬头", operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'company_number', title: __('Company_number'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'sn', title: __('所属订单')},
                        {field: 'order_price', title: __('订单总金额'), operate:'BETWEEN'},

                        {
                            field: 'rate',
                            title: __('Rate'),
                            formatter: function (value) {
                                if(value > 0) {
                                    return value;
                                }else{
                                    return "暂未录入";
                                }
                            }
                        },
                        {field: 'amount', title: '应税金额', operate:'BETWEEN'},
                        
                        {field: 'username', title: __('用户名称')},
                        {
                            field: 'status',
                            title: "审核状态",
                            formatter: function (value) {
                                if(value == 0){
                                    return "待审核";
                                }else if(value == 1){
                                    return "审核通过";
                                }else if(value == 2){
                                    return "审核不通过";
                                }
                            }
                        },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    title: '详情',
                                    text: '详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'invoice/edit',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'pass',
                                    title: '通过',
                                    text: '通过',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'invoice/edit',
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
                                        Layer.confirm('确认是否通过', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "invoice/multi",
                                                    data: {ids:row.id,'params':'status=1'},
                                                }, function (data, ret) {
                                                    Layer.closeAll();
                                                    $(".btn-refresh").trigger("click");
                                                    //return false;
                                                });
                                            }, function(){

                                            }
                                        )
                                        return false;
                                    }
                                },
                                {
                                    name: 'pass',
                                    title: '拒绝',
                                    text: '拒绝',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'invoice/edit',
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
                                        Layer.confirm('确认是否拒绝', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "invoice/multi",
                                                    data: {ids:row.id,'params':'status=2'},
                                                }, function (data, ret) {
                                                    Layer.closeAll();
                                                    $(".btn-refresh").trigger("click");
                                                    //return false;
                                                });
                                            }, function(){

                                            }
                                        )
                                        return false;
                                    }
                                }
                            ],
                            formatter:function (value, row, index) {
                                var that = $.extend({}, this);
                                var table = $(that.table).clone(true);
                                $(table).data("operate-del", null);
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }}
                    ]
                ]
            }else{
                col = [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'type', title: "票据类型", searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        // {field: 'title', title: __('Title'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'company_number', title: __('Company_number'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'bank_name', title: __('开户银行')},
                        {field: 'bank_account', title: __('开户账号')},
                        {field: 'sn', title: __('所属订单')},
                        {field: 'order_price', title: __('订单总金额'), operate:'BETWEEN'},
                        {field: 'rate', title: __('Rate'), operate:'BETWEEN'},
                        {field: 'amount', title: '应税金额', operate:'BETWEEN'},
                        
                        {field: 'title', title: __('企业名称')},
                        {
                            field: 'status',
                            title: "审核状态",
                            formatter: function (value) {
                                if(value == 0){
                                    return "待审核";
                                }else if(value == 1){
                                    return "审核通过";
                                }else if(value == 2){
                                    return "审核不通过";
                                }
                            }
                        },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    title: '详情',
                                    text: '详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'invoice/edit',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'pass',
                                    title: '通过',
                                    text: '通过',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'invoice/edit',
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
                                        Layer.confirm('确认是否通过', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "invoice/multi",
                                                    data: {ids:row.id,'params':'status=1'},
                                                }, function (data, ret) {
                                                    Layer.closeAll();
                                                    $(".btn-refresh").trigger("click");
                                                    //return false;
                                                });
                                            }, function(){

                                            }
                                        )
                                        return false;
                                    }
                                },
                                {
                                    name: 'pass',
                                    title: '拒绝',
                                    text: '拒绝',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'invoice/edit',
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
                                        Layer.confirm('确认是否拒绝', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "invoice/multi",
                                                    data: {ids:row.id,'params':'status=2'},
                                                }, function (data, ret) {
                                                    Layer.closeAll();
                                                    $(".btn-refresh").trigger("click");
                                                    //return false;
                                                });
                                            }, function(){

                                            }
                                        )
                                        return false;
                                    }
                                }
                            ],
                            formatter:function (value, row, index) {
                                var that = $.extend({}, this);
                                var table = $(that.table).clone(true);
                                $(table).data("operate-del", null);
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }}
                    ]
                ]
            }

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
                searchFormVisible:true,
                searchFormTemplate: 'customformtpl',
                columns: col
            });

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
                url: 'invoice/recyclebin' + location.search,
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
                                    url: 'invoice/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'invoice/destroy',
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
