define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'tag/index' + location.search,
                    add_url: 'tag/add',
                    edit_url: 'tag/edit',
                    del_url: 'tag/del',
                    multi_url: 'tag/multi',
                    import_url: 'tag/import',
                    option_url: 'tag/option',
                    table: 'tag',
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
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false,formatter: Table.api.formatter.datetime},
                        {field: 'pid', title: __('Pid'),searchable:false},
                        /**{field: 'path', title: __('Path'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},**/
                        {field: 'level', title: __('Level'), searchList: {"1":__('Level 1'),"2":__('Level 2'),"3":__('Level 3')}, formatter: Table.api.formatter.normal},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false,formatter: Table.api.formatter.datetime},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3')}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                url: 'tag/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
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
                                    url: 'tag/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'tag/destroy',
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
            $.validator.config({
                rules: {
                    diyname: function (element) {
                        console.log(element.value)
                        if(element.value.length > 20){
                            return '标题不能超过20个字'
                        }
                        let reg =  /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
                        if(reg.test(element.value)){
                            return '标题不能包含特殊字符'
                        }
                        if(element.value.includes(' ')){
                            return '标题不能包含空格'
                        }
                        //如果直接返回文本，则表示失败的提示文字
                        //如果返回true表示成功
                        //如果返回Ajax对象则表示远程验证

                    }
                }
            });
            $(document).on("change", "#c-level", function(e,v){
                //变更后的回调事件
                console.log(e,"change")
                console.log($('#c-level').val(),"v")
                let level = $('#c-level').val()
                let type = $('#c-type').val()
                $("#c-pid").empty();
                if(level === '1'){
                    // 隐藏上级
                    $("#pid-box").hide();
                }else{
                    // 获取数据
                    $("#pid-box").show();
                    Fast.api.ajax({
                        url: "tag/options",
                        dataType:"json",
                        data:{
                            level: level,
                            type: type
                        }
                    },function(data, ret){
                        console.log(data);
                        data.forEach((item,index)=>{
                            $("#c-pid").append('<option value ='+item.id+' >'+item.name+'</option>');
                        })
                        $('.selectpicker').selectpicker('refresh')
                    })
                }
                
            });

            Controller.api.bindevent();
        },
        edit: function () {
            $(document).on("change", "#c-level", function(e,v){
                //变更后的回调事件
                console.log(e,"change")
                console.log($('#c-level').val(),"v")
                let level = $('#c-level').val()
                let type = $('#c-type').val()
                let id = $('#c-id').val()
                $("#c-pid").empty();
                if(level === '1'){
                    // 隐藏上级
                    $("#pid-box").hide();
                }else{
                    // 获取数据
                    $("#pid-box").show();
                    Fast.api.ajax({
                        url: "tag/options",
                        dataType:"json",
                        data:{
                            level: level,
                            type: type,
                            id: id
                        }
                    },function(data, ret){
                        console.log(data);
                        data.forEach((item,index)=>{
                            $("#c-pid").append('<option value ='+item.id+' >'+item.name+'</option>');
                        })
                        $('.selectpicker').selectpicker('refresh')
                    })
                }

            });
            $.validator.config({
                rules: {
                    diyname: function (element) {
                        console.log(element.value)
                        if(element.value.length > 20){
                            return '标题不能超过20个字'
                        }
                        let reg =  /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/;
                        if(reg.test(element.value)){
                            return '标题不能包含特殊字符'
                        }
                        if(element.value.includes(' ')){
                            return '标题不能包含空格'
                        }
                        //如果直接返回文本，则表示失败的提示文字
                        //如果返回true表示成功
                        //如果返回Ajax对象则表示远程验证

                    }
                }
            });

            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        sel: function () {
            // Select
            console.log("选择标签")
        }
    };
    return Controller;
});
