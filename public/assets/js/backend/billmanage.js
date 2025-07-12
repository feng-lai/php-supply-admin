define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'layui'], function ($, undefined, Backend, Table, Form, layui) {

    var Controller = {
        index: function () {
            var type = Config.type;
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'billmanage/index/type/'+type + location.search,
                    type_url: 'billmanage/index/type/',
                    edit_url: 'billmanage/edit',
                    multi_url: 'order/order/multi',
                    import_url: 'order/order/import',
                    table: 'billmanage'
                }
            });

            var table = $("#table");

            var columns = [];
            if (type == 1) {
                columns = [
                    [
                        {field: 'index', title: '序号', formatter: function (value, row, index) {return index + 1;}},
                        {field: 'totals', title: '冻结服务费金额', operate: 'LIKE'},
                        {field: 'sn', title: '关联订单', operate: 'LIKE'},
                        {field: 'status', title: '订单状态', operate: 'LIKE', formatter: function (value, row, index) {
                                return {
                                    '0': '待收款',
                                    '1': '待审核',
                                    '2': '服务中',
                                    '3': '待验收',
                                    '4': '待跟进',
                                    '5': '已完成',
                                    '6': '已取消'
                                }[value];
                            }},
                        {field: 'nickname', title: '需求方名称', operate: 'LIKE'},
                        {field: 'specia_name', title: '服务专家', operate: 'LIKE'},
                        {field: 'createtime', title: '时间'}
                    ]
                ];
            } else if (type == 2) {
                columns = [
                    [
                        {field: 'index', title: '序号', formatter: function (value, row, index) {return index + 1;}},
                        {field: 'total', title: '待发放专家服务费', operate: 'LIKE'},
                        {field: 'sn', title: '关联订单', operate: 'LIKE'},
                        {field: 'status', title: '订单状态', operate: 'LIKE', formatter: function (value, row, index) {
                                return {
                                    '0': '待收款',
                                    '1': '待审核',
                                    '2': '服务中',
                                    '3': '待验收',
                                    '4': '待跟进',
                                    '5': '已完成',
                                    '6': '已取消'
                                }[value];
                            }},
                        {field: 'nickname', title: '需求方', operate: 'LIKE'},
                        {field: 'need_invoice', title: '是否开票', operate: 'LIKE', formatter: function (value, row, index) {
                                return {
                                    '0': '不需要',
                                    '1': '普通增值税专用发票'
                                }[value];
                            }},
                        {field: 'specia_name', title: '服务专家', operate: 'LIKE'},
                        {field: 'id_no_bank_name', title: '收款银行', operate: 'LIKE'},
                        {field: 'id_no_bank_id', title: '收款账户', operate: 'LIKE'},
                        {field: 'id_no_bank_user', title: '收款人', operate: 'LIKE'},
                        {field: 'createtime', title: '时间'},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: "立即发放",
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'billmanage/send/ids/{ids}',
                                    callback: function (data) {
                                        location.reload()
                                    }
                                }
                            ]
                        }
                    ]
                ];
            } else if (type == 3) {
                columns = [
                    [
                        {field: 'index', title: '序号', formatter: function (value, row, index) {return index + 1;}},
                        {field: 'sn', title: '关联订单', operate: 'LIKE'},
                        {field: 'total', title: '应支付专家服务费', operate: 'LIKE', formatter: function (value, row, index) {
                                var result = row.total * (row.debit_per / 100);
                                result = result.toFixed(2);
                                result = Number(result);
                                return result;
                        }},
                        {field: 'total', title: '应退还需求方服务费', operate: 'LIKE', formatter: function (value, row, index) {
                                var total = row.total;
                                var debitPer = row.debit_per;
                                var result = total - (total * (debitPer / 100));
                                result = result.toFixed(2);
                                result = Number(result);
                                return result;
                        }},
                        {field: 'status', title: '订单状态', operate: 'LIKE', formatter: function (value, row, index) {
                                return '已中止';
                        }},
                        {field: 'nickname', title: '需求方', operate: 'LIKE'},
                        {field: 'specia_name', title: '服务专家', operate: 'LIKE'},
                        {field: 'createtime', title: '时间'},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: "立即发放",
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'billmanage/other/ids/{ids}',
                                    callback: function (data) {
                                        location.reload()
                                    }
                                }
                            ]
                        }
                    ]
                ];
            } else if (type == 4) {
                columns = [
                    [
                        {field: 'index', title: '序号', formatter: function (value, row, index) {return index + 1;}},
                        {field: 'real_total', title: '待发放专家服务费', operate: 'LIKE'},
                        {field: 'sn', title: '关联订单', operate: 'LIKE'},
                        {field: 'status', title: '订单状态', operate: 'LIKE', formatter: function (value, row, index) {
                                return '已完成';
                            }},
                        {field: 'nickname', title: '需求方', operate: 'LIKE'},
                        {field: 'need_invoice', title: '是否开票', operate: 'LIKE', formatter: function (value, row, index) {
                                return {
                                    '0': '不需要',
                                    '1': '普通增值税专用发票'
                                }[value];
                            }},
                        {field: 'specia_name', title: '服务专家', operate: 'LIKE'},
                        {field: 'id_no_bank_name', title: '收款银行', operate: 'LIKE'},
                        {field: 'id_no_bank_id', title: '收款账户', operate: 'LIKE'},
                        {field: 'id_no_bank_user', title: '收款人', operate: 'LIKE'},
                        {field: 'createtime', title: '时间'},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: "查看详情",
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'billmanage/infos/ids/{ids}'
                                }
                            ]
                        }
                    ]
                ];
            } else if (type == 5) {
                columns = [
                    [
                        {field: 'index', title: '序号', formatter: function (value, row, index) {return index + 1;}},
                        {field: 'sn', title: '关联订单', operate: 'LIKE'},
                        {field: 'total', title: '应支付专家服务费', operate: 'LIKE', formatter: function (value, row, index) {
                                var result = row.total * (row.debit_per / 100);
                                result = result.toFixed(2);
                                result = Number(result);
                                return result;
                            }},
                        {field: 'bill_total', title: '应退还需求方服务费', operate: 'LIKE', formatter: function (value, row, index) {
                                var result = row.bill_total * ((100 - row.debit_per) / 100);
                                result = result.toFixed(2);
                                result = Number(result);
                                return result;
                            }},
                        {field: 'status', title: '订单状态', operate: 'LIKE', formatter: function (value, row, index) {
                                return '已中止';
                            }},
                        {field: 'nickname', title: '需求方', operate: 'LIKE'},
                        {field: 'specia_name', title: '服务专家', operate: 'LIKE'},
                        {field: 'createtime', title: '时间'},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'edit',
                                    text: "查看详情",
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'billmanage/otherinfo/ids/{ids}'
                                }
                            ]
                        }
                    ]
                ];
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
                columns: columns
            });

            Fast.config.openArea = ['100%','100%'];
            // 为表格绑定事件
            Table.api.bindevent(table);

            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var value = $(this).attr("href").replace('#', '');
                var currentDomainWithProtocol = window.location.protocol + "//" + window.location.hostname;
                window.location = currentDomainWithProtocol+"/kfSypMgbqw.php/billmanage/index/type/"+value;
                return false;
            });


        },
        send: function () {
            // 获取输入数字
// 使用 jQuery 选取元素
            var rate_fee_per = $(".rate_fee_per");

// 监听 c-sys_fee 输入框的变化事件
            rate_fee_per.on('input', function() {
                var  rate_fee_per_val = rate_fee_per.val();
                var is_rate = Config.is_rate;

                var total = $(".total").val();
                var price = 0;
                if(is_rate == 1){
                    price = Config.rate_rate * total;
                }else{
                    price = Config.rate_rate * total;
                }
                var price_total = total * rate_fee_per_val * 0.01;
                $(".points_price").val(price_total.toFixed(2));
                $(".system_price").val(price/100);
                var price_totala = total - price/100 - price_total;
                $(".real_total").val(price_totala.toFixed(2))

            });

            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"), function(data, ret){
                //这里是表单提交处理成功后的回调函数，接收来自php的返回数据
                Fast.api.close(data);//这里是重点
            });
        },
        other: function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"), function(data, ret){
                //这里是表单提交处理成功后的回调函数，接收来自php的返回数据
                Fast.api.close(data);//这里是重点
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
