define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/user/user_archive/index' + location.search,
                    add_url: '/user/user_archive/add',
                    edit_url: '/user/user_archive/edit',
                    del_url: '/user/user_archive/del',
                    multi_url: '/user/user_archive/multi',
                    import_url: '/user/user_archive/import',
                    table: 'user_archive',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'group_id', title: __('Group_id')},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'password', title: __('Password'), operate: 'LIKE'},
                        {field: 'salt', title: __('Salt'), operate: 'LIKE'},
                        {field: 'email', title: __('Email'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'avatar', title: __('Avatar'), operate: 'LIKE', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'level', title: __('Level')},
                        {field: 'gender', title: __('Gender')},
                        {field: 'birthday', title: __('Birthday'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'bio', title: __('Bio'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'score', title: __('Score')},
                        {field: 'successions', title: __('Successions')},
                        {field: 'maxsuccessions', title: __('Maxsuccessions')},
                        {field: 'prevtime', title: __('Prevtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'logintime', title: __('Logintime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'loginip', title: __('Loginip'), operate: 'LIKE'},
                        {field: 'loginfailure', title: __('Loginfailure')},
                        {field: 'joinip', title: __('Joinip'), operate: 'LIKE'},
                        {field: 'jointime', title: __('Jointime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'token', title: __('Token'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"30":__('Status 30')}, formatter: Table.api.formatter.status},
                        {field: 'verification', title: __('Verification'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'id_no', title: __('Id_no'), operate: 'LIKE'},
                        {field: 'id_no_front_image', title: __('Id_no_front_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'id_no_backend_image', title: __('Id_no_backend_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'typedata', title: __('Typedata'), searchList: {"个人":__('个人'),"企业":__('企业')}, formatter: Table.api.formatter.normal},
                        {field: 'company_name', title: __('Company_name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'company_id_no', title: __('Company_id_no'), operate: 'LIKE'},
                        {field: 'company_id_no_image', title: __('Company_id_no_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'company_attachfile', title: __('Company_attachfile'), operate: false, formatter: Table.api.formatter.file},
                        {field: 'verify_status', title: __('Verify_status'), searchList: {"0":__('Verify_status 0'),"1":__('Verify_status 1'),"2":__('Verify_status 2')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
