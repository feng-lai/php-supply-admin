define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    require_url: 'requirement/index/type/1',
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    vertifylist_url: 'user/user/vertifylist',
                    table: 'user',
                }
            });

            var table = $("#table");

            $('a[data-toggle="tab"]').on('click', function (e) {
                //console.info('ssss1');

                var keywords = $('#keywords').val();

                var field = 'status';
                //var value = $(this).attr("data-value");
                //console.log(value)
                var options = table.bootstrapTable('getOptions');

                options.pageNumber = 1;
                var queryParams = options.queryParams;

                options.queryParams = function (params) {
                    params = queryParams(params)
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var value = $('.nav-tabs').children('.active').children('a').attr('data-value');
                    if (value !== '') {
                        filter[field] = value;
                    } else {
                        delete filter[field];
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                $('a[data-toggle="tab"]').parents().removeClass('active');
                $(this).parents().addClass('active');

                //console.info(options);
                e.stopPropagation();
                table.bootstrapTable('refresh', {});
                return false;
            })

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                visible: false,//浏览模式(卡片切换)、显示隐藏列、导出、通用搜索全部隐藏
                showToggle: false,//浏览模式可以切换卡片视图和表格视图两种模式
                showColumns: false,//列，可隐藏不显示的字段
                search:false,//快速搜索，搜索框
                fixedColumns: true,
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'nickname', title: '昵称'},
                        {field: 'mobile', title: '手机'},
                        {field: 'typedata', title: '认证信息', formatter: function (value, row, index) {
                                if(value === '1'){
                                    return row.id_no_name;
                                }else{
                                    return row.company_name;
                                }
                        }},
                        {field: 'createtime', title: "注册时间", formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'logintime', title: "最后登录日期", formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'status', title: "状态", searchList: {"normal":'正常',"locked":'禁用'}, formatter: Table.api.formatter.status},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate, 
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '查看详情',
                                    text: '查看详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'user/user/detail',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }
                                },
                                {
                                    name: 'detail',
                                    title: '编辑',
                                    text: '编辑',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'user/user/edit',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }
                                },
                                {
                                    name: 'pass',
                                    title: '解禁',
                                    text: '解禁',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    visible: function (row) {
                                        if(row.status !== 'normal'){
                                            return true;
                                        }else{
                                            return false;
                                        }

                                    },
                                    click: function (e, row) {
                                        Layer.confirm('是否确认解禁', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "user/user/multi",
                                                    data: {'ids':row.id,'params':'status=normal'},
                                                }, function (data, ret) {
                                                    Layer.closeAll();
                                                    $(".btn-refresh").trigger("click");
                                                });
                                            }, function(){

                                            }
                                        )
                                        return false;
                                    }
                                },
                                {
                                    name: 'passa',
                                    title: '禁用',
                                    text: '禁用',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    visible: function (row) {
                                        if(row.status !== 'locked'){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    click: function (e, row) {
                                        Layer.confirm('是否确认禁用', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "user/user/multi",
                                                    data: {'ids':row.id,'params':'status=locked'},
                                                }, function (data, ret) {
                                                    Layer.closeAll();
                                                    $(".btn-refresh").trigger("click");
                                                });
                                            }, function(){

                                            }
                                        )
                                        return false;
                                    }
                                },
                                
                            ]
                        }
                    ]
                ]
            });

            Fast.config.openArea = ['100%','100%'];

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            $.validator.config({
                rules: {
                    diypwd: function (element) {
                        if (!element.value.toString().match(/\d+/)) {
                            return '请输入8位或以上的大小写字母和数字组合';
                        }
                        if (!element.value.toString().match(/[A-Z]+/)) {
                            return '请输入8位或以上的大小写字母和数字组合';
                        }
                        if (!element.value.toString().match(/[a-z]+/)) {
                            return '请输入8位或以上的大小写字母和数字组合';
                        }
                        if (element.value.length < 8) {
                            return '请输入8位或以上的大小写字母和数字组合';
                        }
                    }
                }
            });
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function (data, ret) {
                    if(ret.code == 1){
                        setTimeout('location.reload()',2000)
                    }


                });
            },
            vartify_status: function (value, row, index) {
                console.log(index,'================================')
                var custom = {'1': 'success', '0': 'gray', '2': 'danger'};
                
                this.icon = 'fa fa-circle';
                var colorArr = ["primary", "success", "danger", "warning", "info", "gray", "red", "yellow", "aqua", "blue", "navy", "teal", "olive", "lime", "fuchsia", "purple", "maroon"];
               
                value = value == null || value.length === 0 ? '' : value.toString();
                var keys = typeof this.searchList === 'object' ? Object.keys(this.searchList) : [];
                var index = keys.indexOf(value);
                var color = value && typeof custom[value] !== 'undefined' ? custom[value] : null;
                var display = index > -1 ? this.searchList[value] : null;
                var icon = typeof this.icon !== 'undefined' ? this.icon : null;
                if (!color) {
                    color = index > -1 && typeof colorArr[index] !== 'undefined' ? colorArr[index] : 'primary';
                }
                if (!display) {
                    display = __(value.charAt(0).toUpperCase() + value.slice(1));
                }
                var html = '<span class="text-' + color + '">' + (icon ? '<i class="' + icon + '"></i> ' : '') + display + '</span>';
                if (this.operate != false) {
                    html = '<a href="javascript:;" class="searchit" data-toggle="tooltip" title="' + __('Click to search %s', display) + '" data-field="' + this.field + '" data-value="' + value + '">' + html + '</a>';
                }
                console.log(html,'+++++++')
                return html;
            },
            
        },
        vertifylist: function () {
            var type = Config.type;
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/vertifylist',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    vertifylist_url: 'user/user/vertifylist/type/'+type,
                    table: 'table',
                }
            });

            var table = $("#table");

            var columns;
            if(type == '2'){
                columns = [
                    [
                        {checkbox: true},
                        {field: 'company_name', title: '认证企业'},
                        {field: 'mobile', title: __('经办人手机')},
                        {field: 'id_no_name', title: __('经办人姓名')},
                        {field: 'updatetime', title: "提交时间", formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'enterprise_status', title: __('审核状态'), formatter: Table.api.formatter.vartify_status, searchList: {'0' : __('待审核'), '1' : __('已通过'), '2' : __('已拒绝')}},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text:"查看详情",
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '用户详情',
                                    text: '用户详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'user/user/vertifydetail?is=1',
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
                                    url: 'user/user/vertifydetail',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if(row.enterprise_status > 0){
                                            return false;
                                        }else{
                                            return true;
                                        }

                                    },
                                    click: function (e, row) {
                                        Layer.confirm('是否确认通过企业认证', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "user/user/vertifyEnterprise/ids/" + row.ac_id,
                                                    data: {},
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
                                    name: 'passa',
                                    title: '拒绝',
                                    text: '拒绝',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    visible: function (row) {
                                        if(row.enterprise_status > 0){
                                            return false;
                                        }else{
                                            return true;
                                        }
                                    },
                                    click: function (e, row) {
                                        Layer.confirm('是否确认拒绝', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "user/user/multi",
                                                    data: {'ids':row.id,'params':'enterprise_status=2'},
                                                }, function (data, ret) {
                                                    Layer.closeAll();
                                                    $(".btn-refresh").trigger("click");
                                                });
                                            }, function(){

                                            }
                                        )
                                        return false;
                                    }
                                },

                            ]
                        }
                    ]
                ];
            }else{
                columns = [
                    [
                        {checkbox: true},
                        {field: 'typedata', title: "认证类型", formatter: function(d, row, index){
                                if (row.typedata == '1') {
                                    return "个人";
                                } else if (row.typedata == '2') {
                                    return "企业";
                                } else {
                                    return '';
                                }
                            }},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'id_no_name', title: "真实姓名/企业名称", formatter: function(d, row, index){
                                if (row.typedata == '1') {
                                    return row.id_no_name;
                                } else if (row.typedata == '2') {
                                    return row.company_name;
                                } else {
                                    return '';
                                }
                            }},
                        {field: 'updatetime', title: "提交时间", formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'verify_status', title: __('Verify_status'), formatter: Table.api.formatter.vartify_status, searchList: {'0' : __('Verify_status 0'), '1' : __('Verify_status 1'), '2' : __('Verify_status 2')}},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text:"查看详情",
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '用户详情',
                                    text: '用户详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'user/user/vertifydetail',
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
                                    title: '审核通过',
                                    text: '通过',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'user/user/vertifydetail',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if(row.verify_status > 0){
                                            return false;
                                        }else{
                                            return true;
                                        }

                                    },
                                    click: function (e, row) {
                                        Layer.confirm('是否确认通过信息认证', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "user/user/vertifyPass/ids/" + row.id,
                                                    data: {},
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
                                    name: 'nopass',
                                    title: '审核拒绝',
                                    text: '拒绝',
                                    classname: 'btn btn-xs btn-default btn-click',
                                    // icon: 'fa fa-list',
                                    url: 'user/user/vertifydetail',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        if(row.verify_status > 0){
                                            return false;
                                        }else{
                                            return true;
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
                                                refuse_reason: value
                                            }
                                            console.log(pap)
                                            Fast.api.ajax({
                                                url: "user/user/vertifyNopass/ids/" + row.id,
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
                ];
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.vertifylist_url,
                visible: false,//浏览模式(卡片切换)、显示隐藏列、导出、通用搜索全部隐藏
                showToggle: false,//浏览模式可以切换卡片视图和表格视图两种模式
                showColumns: false,//列，可隐藏不显示的字段
                search:false,//快速搜索，搜索框
                pk: 'id',
                sortName: 'id',
                columns:columns
            });

            Fast.config.openArea = ['100%','100%'];
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        vertifydetail: function () {
            console.log("11")
            $(document).ready(function(){
                // 在这里编写你想要执行的代码
                $('input').prop('disabled', true);
                $('.input-group button').prop('disabled', true);
                $('#p-company_id_no_image a').prop('disabled', true);
                $('#p-company_id_no_image a').prop('disabled', true);
                $('#p-id_no_backend_image a').prop('disabled', true);
                $('#p-id_no_front_image a').prop('disabled', true);
                
              });
            $(document).on("change", "#c-switch", function(e){
                //开关切换后的回调事件
                var is_edit = $('#c-switch').val()
                console.log(is_edit)
                if(is_edit == '1'){
                    
                    $('input').prop('disabled', false);
                    $('.input-group button').prop('disabled', false);
                    $('#p-company_id_no_image a').prop('disabled', false);
                    $('#p-company_attachfile a').prop('disabled', false);
                    $('#p-id_no_backend_image a').prop('disabled', false);
                    $('#p-id_no_front_image a').prop('disabled', false);
                    
                }else{
                    $('input').prop('disabled', true);
                    $('.input-group button').prop('disabled', true);
                    $('#p-company_id_no_image a').prop('disabled', true);
                    $('#p-company_id_no_image a').prop('disabled', true);
                    $('#p-id_no_backend_image a').prop('disabled', true);
                    $('#p-id_no_front_image a').prop('disabled', true);
                }
            });
            // 未通过提交
            $('#nopass').click(function(){
                console.log('nopass')
                Layer.confirm('是否确认拒绝用户认证', {
                    btn: [__('OK'),__('Cancel')] // 按钮
                }, function(){
                    // Layer.closeAll('dialog');
                    // Fast.api.close(1); // 关闭窗体并回传数据
                    // parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
                    //self this
                    // Form.api.submit(self, success, error);
                    layer.prompt(function(val, index){
                        // layer.msg('得到了'+val);
                        layer.close(index);
                        var formData = {};
                        $('form :input').each(function() {
                            var name = $(this).attr('name');
                            var value = $(this).val();
                            if (name) {
                                formData[name] = value;
                            }
                        });
                        // 
                        formData["row[verify_status]"] = '2';
                        formData["row[refuse_reason]"] = val
                        console.log(formData);
                        // $("form[role=form]").submit()
                        Fast.api.ajax({
                            url: "",
                            dataType:"json",
                            data:formData
                        },function(data, ret){
                            console.log(data);
                            console.log(ret);
                            Fast.api.close(); // 关闭窗体并回传数据
                            parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
                        })
                    });
                    
                    return false;
                }, function(){
                    console.log('确认拒绝原因')
                    Layer.closeAll('dialog');
                    return false;
                });
                return false;
            })
            // 通过提交
            $('#pass').click(function(){
                console.log('pass')
                Layer.confirm('是否确认通过用户认证', {
                    btn: [__('OK'),__('Cancel')] // 按钮
                }, function(){
                    // Layer.closeAll('dialog');
                    // Fast.api.close(1); // 关闭窗体并回传数据
                    // parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
                    //self this
                    // var formData = $("#add-form-vertify").serialize();
                    var formData = {};
                    $('form :input').each(function() {
                        var name = $(this).attr('name');
                        var value = $(this).val();
                        if (name) {
                            formData[name] = value;
                        }
                    });
                    // 
                    formData["row[verify_status]"] = '1';
                    // formData["row[refuse_reason]"] = ""
                    console.log(formData);
                    // $("form[role=form]").submit()
                    Fast.api.ajax({
                        url: "",
                        dataType:"json",
                        data:formData
                    },function(data, ret){
                        console.log(data);
                        console.log(ret);
                        Fast.api.close(); // 关闭窗体并回传数据
                        parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
                    })
                    return false;
                }, function(){
                    console.log('确认拒绝原因')
                    Layer.closeAll('dialog');
                    return false;
                });
                return false;
            })
            Form.api.bindevent($("form[role=form]"), function(data, ret){
                //如果我们需要在提交表单成功后做跳转，可以在此使用location.href="链接";进行跳转
                Toastr.success("成功");
                Fast.api.close(); // 关闭窗体并回传数据
                parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
            }, function(data, ret){
                  Toastr.success("失败");
            }, function(success, error){
                //bindevent的第四个参数为提交前的回调
                //如果我们需要在表单提交前做一些数据处理，则可以在此方法处理
                //注意如果我们需要阻止表单，可以在此使用return false;即可
                //如果我们处理完成需要再次提交表单则可以使用submit提交,如下
                //Form.api.submit(this, success, error);
                return false;
            });
            Controller.api.bindevent();
        },
        detail: function () {

            $(document).ready(function() {
                $('.tag').click(function() {
                    Fast.config.openArea = ['800px','800px'];
                    Fast.api.open('specialist/seltag?type=1',__('Choose'),{
                        callback: function (data) {
                            const combinedNames = data.map(item => item.name).join(',');
                            $(".tag").val(combinedNames)
                        }
                    });
                });
            });

            Table.api.init({
                extend: {
                    require_url: 'requirement/index/type/1',
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    vertifylist_url: 'user/user/vertifylist',
                    table: 'user',
                }
            });
            var uid = $('#myTabContent').data('value');
            console.log("uid:"+uid)
            $(document).ready(function(){
                // 在这里编写你想要执行的代码
                console.log("ready")
            });
            var table = $("#table");
            // 初始化表格-需求记录
            table.bootstrapTable({
                url:'requirement/indexs/type/1/uid/'+uid,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                // searchFormVisible: true,
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('需求编号')},
                        {field: 'title', title: __('需求名称'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'ids', title: '关联标签', operate: 'LIKE', formatter: function (value, row, index) {
                                const combinedArray = [...row.industry_arr, ...row.skill_arr, ...row.area_arr];
                                const combinedNames = combinedArray.map(item => item.name).join(' ');
                                return combinedNames;
                        }},
                        {field: 'content', title: __('需求描述')},
                        {field: 'status_text', title: __('状态'), searchList: {"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'createtime_text', title: __('发布时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            text:"查看详情",
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
                                    url: 'requirement/detail',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }
                                
                            ]
                        }
                    ]
                ]
            });

            var table_order = $("#table-order");

            // 初始化表格
            table_order.bootstrapTable({
                url: 'order/order/indexs/uid/'+uid,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                searchFormVisible: true,
                searchFormTemplate: 'ordercustomformtpl',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'sn', title: __('订单编号'), operate: 'LIKE'},
                        // {field: 'rid', title: __('Rid')},
                        {field: 'title', title: __('订单名称'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'user_id_no_name', title: __('用户')},
                        // {field: 'specialist_id_no_name', title: __('专家')},
                        {field: 'total', title: __('订单总金额'), operate:'BETWEEN'},
                        {field: 'num', title: __('付款总期次')},
                        {field: 'now_point', title: __('当前节点')},
                        {
                            field: 'status',
                            title: "订单状态",
                            formatter: function (value,row) {
                                if (row.is_stop === 2) {
                                    return "已中止";
                                }else if (row.is_excp === 1) {
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
                        // {field: 'specialist_source', title: __('Specialist_source')},
                        // {field: 'need_acceptance', title: __('Need_acceptance'), searchList: {"0":__('Need_acceptance 0'),"1":__('Need_acceptance 1')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime_text', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'finishtime', title: __('Finishtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            text:"查看详情",
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
                                    url: 'order/order/edit',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }
                                
                            ]
                        }
                    ]
                ]
            });


            var table_invoice = $("#table_invoice");

            // 初始化表格
            table_invoice.bootstrapTable({
                url: 'invoice/indexs/uid/'+uid,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                searchFormVisible:true,
                searchFormTemplate: 'invoicecustomformtpl',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'sn', title: __('关联订单')},
                        {field: 'title', title: __('订单名称')},
                        {field: 'type', title: "开票种类", searchList: {"1":"普通发票类型","2":"专用发票类型"}, formatter: Table.api.formatter.normal},
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
                        {field: 'company_tel', title: __('联系电话')},
                        {field: 'createtime', title: __('申请时间')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text:"查看详情",
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '查看详情',
                                    text: '查看详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'invoice/edit',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        return true;
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });


            var table_comment = $("#table-comment");
            table_comment.bootstrapTable({
                url: 'order/comment/indexs/uid/'+uid,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                searchFormVisible:true,
                searchFormTemplate: 'commentcustomformtpl',
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'comment',
                            title: "评价等级",
                            formatter: function (value,row) {
                                if(row.points <= 2){
                                    return "差评";
                                }else if(row.points == 3){
                                    return "中评";
                                }else if(row.points > 3){
                                    return "好评";
                                }
                            }
                        },
                        {field: 'points', title: __('评分')},
                        {field: 'desc', title: __('评价内容')},
                        {field: 'sn', title: __('关联订单编号')},
                        {field: 'nickname', title: __('评价对象')},
                        {field: 'files', title: __('评价附图'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'createtime', title: __('申请时间')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text:"查看详情",
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '查看详情',
                                    text: '查看详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'order/order/edit',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        return true;
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 标签类型点击
            $('[data-toggle="tab"]').click(function (e) {
                
                // return
                var type = $(e.target).data('value')
                console.log(type)
                if(type != "") {
                    $('.tab-pane').removeClass('active')
                    $('#'+type).addClass('active')
                }
                return
                // 清空区域列表
                $(".tag-one").html("")
                $(".tag-two").html("")
                $(".tag-three").html("")
                // 获取一级列表
                // Fast.api.ajax({
                //     url: "specialist/seltag",
                //     dataType:"json",
                //     data:{
                //         type: type,
                //         level: '1',
                //         pid: 0
                //     }
                // },function(data, ret){
                //     data.forEach((item,index)=>{
                //         $(".tag-one").append('<div data-value="'+item.id+'" data-pid="'+item.pid+'"  data-sel="'+item.sel+'"  data-level="'+item.level+'"  data-name="'+item.name+'" data-path="'+item.path+'"  class="item">'+item.name+'</div>');
                //     })
                // })
            })
            Table.api.bindevent(table);
            Controller.api.bindevent();
        },
    };
    return Controller;
});