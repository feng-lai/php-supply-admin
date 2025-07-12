define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'orderlog/index' + location.search,
                    table: 'orderlog'
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
                        {field: 'nickname', title: '用户昵称', operate: 'LIKE'},
                        {field: 'mobile', title: '手机', operate: 'LIKE'},
                        {field: 'pay_type', title: '支付方式', operate: 'LIKE', formatter: function (value, row, index) {
                            if(value == '0'){
                                return "未选择";
                            }else if(value == '1'){
                                return "微信";
                            }else if(value == '2'){
                                return "支付宝";
                            }else if(value == '3'){
                                return "线下";
                            }
                        }},

                        {field: 'sn', title: '订单号', operate: 'LIKE'},
                        {field: 'total', title: '金额', operate: 'LIKE'},
                        {field: 'createtime', title: '交易时间', operate: 'LIKE'}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
