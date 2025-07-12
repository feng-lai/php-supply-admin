define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'news/index' + location.search,
                    add_url: 'news/add',
                    edit_url: 'news/edit',
                    del_url: 'news/del',
                    multi_url: 'news/multi',
                    import_url: 'news/import',
                    table: 'news',
                    dragsort_url:'',
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
                sortName: 'weigh',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,buttons: [
                                {
                                    name: 'status',
                                    title: '下架',
                                    text: '下架',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'news/edit',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if(row.status == '1'){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    click: function (e, row) {
                                        Layer.confirm('确认下架', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "news/multi",
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
                                },
                                {
                                    name: 'status',
                                    title: '上架',
                                    text: '上架',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'news/edit',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if(row.status == '2'){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    click: function (e, row) {
                                        Layer.confirm('确认上架', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "news/multi",
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
                                }

                            ], formatter: Table.api.formatter.operate}
                    ]
                ]
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
                url: 'news/recyclebin' + location.search,
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
                                    url: 'news/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'news/destroy',
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
