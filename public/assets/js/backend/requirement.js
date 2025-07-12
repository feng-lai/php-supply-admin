define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'layui'], function ($, undefined, Backend, Table, Form, layui) {

    var Controller = {
        index: function () {
            console.log(location)
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: location.pathname + location.search,
                    add_url: 'requirement/add',
                    edit_url: 'requirement/edit',
                    del_url: 'requirement/del',
                    multi_url: 'requirement/multi',
                    import_url: 'requirement/import',
                    export: 'requirement/export' + location.search,
                    table: 'requirement',
                }
            });


            var table = $("#table");

            // 自定义导出
            var submitForm = function (ids, layero) {
                var options = table.bootstrapTable('getOptions');
                //console.log(options);
                var columns = [];
                var searchList = {};
                $.each(options.columns[0], function (i, j) {
                    if (j.field && !j.checkbox && j.visible && j.field != 'operate') {
                        columns.push(j.field);

                        // 保存 searchList 传递给后台处理
                        if (j.searchList) {
                            searchList[j.field] = j.searchList;
                        }
                    }
                });

                var search = options.queryParams({});
                // 指定具体 form 否则选择条件查询后 导出会卡一下
                var form = document.getElementById('export');

                $("input[name=search]", layero).val(options.searchText);
                // $("input[name=filter]", layero).val(search.filter);
                $("input[name=op]", layero).val(search.op);
                $("input[name=columns]", layero).val(columns.join(','));
                $("input[name=sort]", layero).val(options.sortName);
                $("input[name=order]", layero).val(options.sortOrder);
                $("input[name=searchList]", layero).val(JSON.stringify(searchList));
                //$("form", layero).submit();
                form.submit(ids, layero);
            };

            // 自定义导出
            var submitForm = function (ids, layero) {
                var options = table.bootstrapTable('getOptions');
                //console.log(options);
                var columns = [];
                var searchList = {};
                $.each(options.columns[0], function (i, j) {
                    if (j.field && !j.checkbox && j.visible && j.field != 'operate') {
                        columns.push(j.field);

                        // 保存 searchList 传递给后台处理
                        if (j.searchList) {
                            searchList[j.field] = j.searchList;
                        }
                    }
                });

                var search = options.queryParams({});
                // 指定具体 form 否则选择条件查询后 导出会卡一下
                var form = document.getElementById('export');

                $("input[name=search]", layero).val(options.searchText);
                $("input[name=ids]", layero).val(ids);
                $("input[name=filter]", layero).val(search.filter);
                $("input[name=op]", layero).val(search.op);
                $("input[name=columns]", layero).val(columns.join(','));
                $("input[name=sort]", layero).val(options.sortName);
                $("input[name=order]", layero).val(options.sortOrder);
                $("input[name=searchList]", layero).val(JSON.stringify(searchList));
                //$("form", layero).submit();
                form.submit(ids, layero);
            };

            $(document).on("click", ".btn-export", function () {

                var url = $.fn.bootstrapTable.defaults.extend.export; // 导出方法的控制器 url

                var ids = Table.api.selectedids(table);
                var page = table.bootstrapTable('getData');
                var all = table.bootstrapTable('getOptions').totalRows;
                console.log(ids, page, all);

                // 这里有个 form 表单 里面的 input 和 submitForm 中对应，想传其他参数，可以继续增加 input
                Layer.confirm("请选择导出的选项<form action='" + Fast.api.fixurl(url) + "' id='export' method='post' target='_blank'><input type='hidden' name='ids' value='' /><input type='hidden' name='filter' ><input type='hidden' name='op'><input type='hidden' name='sort'><input type='hidden' name='order'><input type='hidden' name='search'><input type='hidden' name='columns'><input type='hidden' name='searchList'></form>", {
                    title: '导出数据',
                    btn: ["选中项(" + ids.length + "条)", "本页(" + page.length + "条)", "全部(" + all + "条)"],
                    success: function (layero, index) {
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    }
                    , yes: function (index, layero) {
                        submitForm(ids.join(","), layero);
                        return false;
                    }
                    ,
                    btn2: function (index, layero) {
                        var ids = [];
                        $.each(page, function (i, j) {
                            ids.push(j.id);
                        });
                        submitForm(ids.join(","), layero);
                        return false;
                    }
                    ,
                    btn3: function (index, layero) {
                        submitForm("all", layero);
                        return false;
                    }
                })
            });

            $(document).ready(function () {
                $('.tag').click(function () {
                    Fast.config.openArea = ['800px', '800px'];
                    Fast.api.open('specialist/seltag?type=1', __('Choose'), {
                        callback: function (data) {
                            const combinedNames = data.map(item => item.name).join(',');
                            $(".tag").val(combinedNames)
                        }
                    });
                });
                $(document).on("change", "#c-menuswitch", function () {
                    var menuswitch = $("#c-menuswitch").val();
                    Fast.api.ajax({
                        url: "general.config/edit",
                        data: {
                            'row[automatic_matching]': menuswitch
                        },
                    }, function (data, ret) {
                    });
                });


            });

            // //新增 配置调用接口
            // table.on('post-common-search.bs.table', function (event, table) {
            //     var form = $("form", table.$commonsearch);
            //     $("input[name='industry_ids']", form).addClass("selectpage").data("source", "tag/index?type=industry_ids").data("multiple",true).data("primaryKey", "id").data("field", "name").data("orderBy", "id desc");
            //     $("input[name='skill_ids']", form).addClass("selectpage").data("source", "tag/index?type=industry_ids").data("multiple",true).data("primaryKey", "id").data("field", "name").data("orderBy", "id desc");
            //     $("input[name='area_ids']", form).addClass("selectpage").data("source", "tag/index?type=industry_ids").data("multiple",true).data("primaryKey", "id").data("field", "name").data("orderBy", "id desc");
            //
            //     Form.events.cxselect(form);
            //     Form.events.selectpage(form);
            // });
            var columns = [];
            if (Config.type == 1) {
                columns = [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'sn', title: "需求编号"},
                        {field: 'title', title: "需求名称"},
                        {field: 'content', title: "需求描述"},
                        {
                            field: 'ids', title: '关联标签', operate: 'LIKE', formatter: function (value, row, index) {
                                const combinedArray = [...row.industry_arr, ...row.skill_arr, ...row.area_arr];
                                const combinedNames = combinedArray.map(item => item.name).join(' ');
                                return combinedNames;
                            }
                        },
                        {
                            field: 'status_text',
                            title: __('Status'),
                            searchList: {"1": __('Status 1')},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'createtime_text',
                            title: "发布时间",
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false
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
                                    url: 'requirement/detail',
                                    callback: function (data) {
                                        //Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                        console.log(213)
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }

                            ]
                        }
                    ]
                ];
            } else {
                columns = [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'title',
                            title: "需求标题",
                            operate: 'LIKE',
                            table: table,
                            class: 'autocontent',
                            formatter: Table.api.formatter.content
                        },
                        {field: 'content', title: "需求描述"},
                        {
                            field: 'ids', title: '关联标签', operate: 'LIKE', formatter: function (value, row, index) {
                                const combinedArray = [...row.industry_arr, ...row.skill_arr, ...row.area_arr];
                                const combinedNames = combinedArray.map(item => item.name).join(' ');
                                return combinedNames;
                            }
                        },
                        {field: 'price', title: "服务费起点"},
                        {
                            field: 'user_type',
                            title: "发布主体",
                            searchList: {"1": __('User_type 1'), "2": __('User_type 2')},
                            formatter: Table.api.formatter.normal
                        },
                        {field: 'user_name', title: "用户昵称", operate: 'LIKE'},
                        {
                            field: 'createtime_text',
                            title: "发布时间",
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text: "查看详情",
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            // buttons: [
                            //     {
                            //         name: 'detail',
                            //         title: '详情',
                            //         text: '详情',
                            //         classname: 'btn btn-xs btn-primary btn-dialog',
                            //         // icon: 'fa fa-list',
                            //         url: 'requirement/detail',
                            //         callback: function (data) {
                            //             // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                            //         },
                            //         visible: function (row) {
                            //             //返回true时按钮显示,返回false隐藏
                            //             return true;
                            //         }
                            //     }
                            //
                            // ]
                        }
                    ]
                ];
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                // searchFormVisible: true,
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                fixedRightNumber: 1,
                columns: columns,
                visible: false,//浏览模式(卡片切换)、显示隐藏列、导出、通用搜索全部隐藏
                showToggle: false,//浏览模式可以切换卡片视图和表格视图两种模式
                showColumns: false,//列，可隐藏不显示的字段
                search: false,//快速搜索，搜索框
            });
            Fast.config.openArea = ['100%', '100%'];
            // $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
            // 绑定TAB事件 防止冒泡
            $('a[data-toggle="tab"]').on('click', function (e) {
                console.info('ssss1');
                //
                var keywords = $('#keywords').val();

                var field = 'tab';
                var value = $(this).attr("href").replace('#', '');
                var options = table.bootstrapTable('getOptions');

                options.pageNumber = 1;
                var queryParams = options.queryParams;

                options.queryParams = function (params) {
                    params = queryParams(params);
                    console.log(params, 'params')
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    if (value !== '' && value != 'all') {
                        filter[field] = value;
                    } else {
                        delete filter[field];
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                $('a[data-toggle="tab"]').parents().removeClass('active');
                $(this).parents().addClass('active');

                console.info(options);
                e.stopPropagation();
                table.bootstrapTable('refresh', {});
                return false;
            })
            // $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            // var field = 'type';
            // var value = $(this).attr("href").replace('#', '');
            // var options = table.bootstrapTable('getOptions');
            // options.pageNumber = 1;
            // var queryParams = options.queryParams;
            // options.queryParams = function (params) {
            //     params = queryParams(params);
            //     var filter = params.filter ? JSON.parse(params.filter) : {};
            //     if (value !== '' && value != 'all') {
            //         filter[field] = value;
            //     } else {
            //         delete filter[field];
            //     }
            //     params.filter = JSON.stringify(filter);
            //     return params;
            // };
            // e.stopPropagation();
            // table.bootstrapTable('refresh', {});
            // return false;
            // });

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
                url: 'requirement/recyclebin' + location.search,
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
                                    url: 'requirement/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'requirement/destroy',
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

            $('.form-group').on('click', '.sel', function (e) {
                var type = $(this).data('type');
                Fast.api.open('specialist/seltag?type=' + type, __('Choose'), {
                    callback: function (data) {
                        getType(data);
                    }
                });
            })

            function getType(data) {

                let industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                let skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];

                let area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                data.forEach((item, index) => {
                    console.log(item);

                    if (item.type.toString() === '1' && !industry_ids.includes(item.id.toString())) {
                        industry_ids.push(item.id);
                        $(".industry_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    } else if (item.type.toString() == '2' && !skill_ids.includes(item.id.toString())) {
                        skill_ids.push(item.id);
                        $(".skill_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');

                    } else if (item.type.toString() == '3' && !area_ids.includes(item.id.toString())) {
                        area_ids.push(item.id);
                        $(".area_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }
                })

                // $(".industry_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');
                // $(".skill_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');
                // $(".area_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');

                $('#c-industry_ids').val(industry_ids.join(','));
                $('#c-skill_ids').val(skill_ids.join(','));
                $('#c-area_ids').val(area_ids.join(','));
            }

            $(document).on('click', '.tag-remove', function (e) {
                var id = $(this).data('id');
                var type = $(this).data('type');
                var industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                var skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];
                var area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                if (type == '1') {
                    var join = industry_ids.filter(item => item != id);
                    $('#c-industry_ids').val(join.join(','));
                } else if (type == '2') {
                    var join = skill_ids.filter(item => item != id);
                    $('#c-skill_ids').val(join.join(','));
                } else if (type == '3') {
                    var join = area_ids.filter(item => item != id);
                    $('#c-area_ids').val(join.join(','));
                }
                $(".dropup_" + id).remove();

            })


            Controller.api.bindevent();
        },
        novsclick: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            $('.form-group').on('click', '.sel', function (e) {
                var type = $(this).data('type');
                Fast.api.open('specialist/seltag?type=' + type, __('Choose'), {
                    callback: function (data) {
                        getType(data);
                    }
                });
            })
            getType(Config.tag);

            function getType(data) {

                let industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                let skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];

                let area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                data.forEach((item, index) => {
                    console.log(item);

                    if (item.type.toString() === '1' && !industry_ids.includes(item.id.toString())) {
                        industry_ids.push(item.id);
                        $(".industry_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    } else if (item.type.toString() == '2' && !skill_ids.includes(item.id.toString())) {
                        skill_ids.push(item.id);
                        $(".skill_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');

                    } else if (item.type.toString() == '3' && !area_ids.includes(item.id.toString())) {
                        area_ids.push(item.id);
                        $(".area_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }
                })

                // $(".industry_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');
                // $(".skill_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');
                // $(".area_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');

                $('#c-industry_ids').val(industry_ids.join(','));
                $('#c-skill_ids').val(skill_ids.join(','));
                $('#c-area_ids').val(area_ids.join(','));
            }

            $(document).on('click', '.tag-remove', function (e) {
                var id = $(this).data('id');
                var type = $(this).data('type');
                var industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                var skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];
                var area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                if (type == '1') {
                    var join = industry_ids.filter(item => item != id);
                    $('#c-industry_ids').val(join.join(','));
                } else if (type == '2') {
                    var join = skill_ids.filter(item => item != id);
                    $('#c-skill_ids').val(join.join(','));
                } else if (type == '3') {
                    var join = area_ids.filter(item => item != id);
                    $('#c-area_ids').val(join.join(','));
                }
                $(".dropup_" + id).remove();

            })

            Controller.api.bindevent();
        },
        detail: function () {
            layui.use(['upload', 'element', 'layer'], function() {
                var $ = layui.jquery
                    , upload = layui.upload
                    , element = layui.element
                    , layer = layui.layer;
                //多图片上传
                upload.render({
                    elem: '#upload_files_arr'
                    ,url: '/api/common/upload' //此处配置你自己的上传接口即可
                    ,multiple: true
                    ,done: function(res){
                        //上传完毕
                        let img = $('#files').val()
                        if(!img){
                            img = []
                        }else{
                            img = JSON.parse(img)
                        }
                        img.push({name:res.data.file_name,url:res.data.url})
                        $('#files').val(JSON.stringify(img))
                        $('#preview').append('<li title="'+res.data.file_name+'"><a href="'+res.data.fullurl+'" target="_blank"><i class="fa fa-file" style="font-size: -webkit-xxx-large;"></i></a><a href="javascript:void(0)" class="btn btn-danger btn-xs btn-trash" data-url="'+res.data.url+'"><i class="fa fa-trash"></i></a></li>')
                        $('.btn-trash').click(function () {
                            let img = $('#files').val()
                            img = JSON.parse(img)
                            let imgs = []
                            for(let i in img){
                                if(img[i].url != $(this).attr('data-url')){
                                    imgs.push(img[i])
                                }
                            }
                            $('#files').val(JSON.stringify(imgs))
                            $(this).closest('li').remove()
                        })
                    }
                });
            })
            $('.btn-trash').click(function () {
                let img = $('#files').val()
                img = JSON.parse(img)
                let imgs = []
                for(let i in img){
                    if(img[i].url != $(this).attr('data-url')){
                        imgs.push(img[i])
                    }
                }
                $('#files').val(JSON.stringify(imgs))
                $(this).closest('li').remove()
            })
            $('.sel').on('click', function (e) {
                var type = $(this).data('type');
                Fast.api.open('specialist/seltag?type=' + type, __('Choose'), {
                    callback: function (data) {
                        getType(data);
                    }
                });
            })

            function getType(data) {

                let industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                let skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];

                let area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                data.forEach((item, index) => {
                    console.log(item);

                    if (item.type.toString() == '1' && !industry_ids.includes(item.id.toString())) {
                        industry_ids.push(item.id);
                        $(".industry_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    } else if (item.type.toString() == '2' && !skill_ids.includes(item.id.toString())) {
                        skill_ids.push(item.id);
                        $(".skill_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');

                    } else if (item.type.toString() == '3' && !area_ids.includes(item.id.toString())) {
                        area_ids.push(item.id);
                        $(".area_ids").prepend('<div class="btn-group dropup dropup_' + item.id + '"><button type="button" class="btn btn-primary">' + item.name + '</button><button data-id="' + item.id + '" data-type="' + item.type + '" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }
                })

                // $(".industry_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');
                // $(".skill_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');
                // $(".area_ids").append('<a class="btn btn-primary dropup sel" href="javascript:" >选择</a>');

                $('#c-industry_ids').val(industry_ids.join(','));
                $('#c-skill_ids').val(skill_ids.join(','));
                $('#c-area_ids').val(area_ids.join(','));
            }

            $(document).on('click', '.tag-remove', function (e) {
                var id = $(this).data('id');
                var type = $(this).data('type');
                var industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                var skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];
                var area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                if (type == '1') {
                    var join = industry_ids.filter(item => item != id);
                    $('#c-industry_ids').val(join.join(','));
                } else if (type == '2') {
                    var join = skill_ids.filter(item => item != id);
                    $('#c-skill_ids').val(join.join(','));
                } else if (type == '3') {
                    var join = area_ids.filter(item => item != id);
                    $('#c-area_ids').val(join.join(','));
                }
                $(".dropup_" + id).remove();

            })
            layui.use(["form", "upload"], function (form, upload) {

            });
            let ids = $("#ids").val()

            function vsclick(ids) {
                // $('#text').html(info)

                $(document).ready(function () {
                    $('.tag').click(function () {
                        Fast.config.openArea = ['800px', '500px'];
                        Fast.api.open('specialist/seltag?type=1', __('Choose'), {
                            callback: function (data) {
                                const combinedNames = data.map(item => item.name).join(',');
                                $(".tag").val(combinedNames)
                            }
                        });
                    });
                });

                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'specialist/vslist/ids/' + ids + location.search,
                        add_url: 'specialist/add',
                        edit_url: 'specialist/edit',
                        del_url: 'specialist/del',
                        multi_url: 'specialist/multi',
                        import_url: 'specialist/import',
                        seltag_url: 'specialist/seltag',
                        table: 'specialist',
                    }
                });

                function detailData(type, event) {
                    var $tr = $(event.target).parents("tr");
                    var len = $('#process-table').bootstrapTable('getData').length;
                    var idx = $tr.index();//行在table中的位置
                    var next_idx = 0;
                    var array = $('#process-table').bootstrapTable('getData');
                    $tr.fadeOut().fadeIn();
                    if (type == 'up') {
                        if ($tr.index() == 0) {
                            alert("首行数据不可上移!");
                            return
                        }
                        next_idx = idx - 1;
                        $tr.prev().before($tr);
                    } else if (type == 'down') {
                        if ($tr.index() == len - 1) {
                            alert("尾行数据不可下移!");
                            return
                        }
                        ;
                        next_idx = idx + 1;
                        $tr.next().after($tr);
                    }
                    //交换元素
                    var temp = array[idx];
                    array[idx] = array[next_idx];
                    array[next_idx] = temp;
                    //后台操作修改数据库顺序
                }


                var table = $("#table");

                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    fixedColumns: true,
                    fixedRightNumber: 1,
                    maintainSelected: true,
                    commonSearch: true,
                    // searchFormVisible:true,
                    searchFormVisible: true,
                    searchFormTemplate: 'customformtpl',
                    columns: [
                        [
                            {checkbox: true},
                            {field: 'user_id', title: __('Id')},
                            {
                                field: 'nickname',
                                title: __('Nickname'),
                                operate: 'LIKE',
                                table: table,
                                class: 'autocontent',
                                formatter: Table.api.formatter.content
                            },
                            {
                                field: 'mobile',
                                title: '手机',
                                operate: 'LIKE',
                                table: table,
                                class: 'autocontent',
                                formatter: Table.api.formatter.content
                            },
                            {
                                field: 'name',
                                title: __('Name'),
                                operate: 'LIKE',
                                table: table,
                                class: 'autocontent',
                                formatter: Table.api.formatter.content
                            },
                            {
                                field: 'avg_score',
                                title: __('服务评分'),
                                operate: 'LIKE',
                                table: table,
                                class: 'autocontent',
                                formatter: Table.api.formatter.content
                            },
                            {field: 'order_count', title: __('总订单数'), operate: 'LIKE'},
                            {field: 'tags', title: __('关联标签'), operate: 'LIKE', formatter: Table.api.formatter.flag},
                            {
                                field: 'createtime_text',
                                title: __('Createtime'),
                                operate: 'RANGE',
                                addclass: 'datetimerange',
                                autocomplete: false
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
                                        url: 'specialist/edit',
                                        callback: function (data) {
                                            // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                        },
                                        visible: function (row) {
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }
                                    },
                                    {
                                        name: 'checkout',
                                        title: '勾选',
                                        text: '勾选',
                                        classname: 'btn btn-xs btn-primary btn-click',
                                        click: function (index) {
                                            var index = index.rowIndex;
                                            var isChecked = $('#table').find('tbody tr:eq(' + index + ')').hasClass('selected');
                                            console.log(isChecked);
                                            console.log(index);
                                            if (isChecked) {
                                                $('#table').bootstrapTable('uncheck', index);
                                            } else {
                                                $('#table').bootstrapTable('check', index);
                                            }
                                        },
                                        visible: function (row) {
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }
                                    },
                                    {
                                        name: 'cancel',
                                        title: '移除',
                                        text: '移除',
                                        classname: 'btn btn-xs btn-primary btn-click',
                                        click: function (data, row, aa, index) {
                                            $(this).closest('tr').remove();

                                        },
                                        visible: function (row) {
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }
                                    },
                                    {
                                        name: 'up',
                                        title: '上移',
                                        text: '上移',
                                        classname: 'btn btn-xs btn-primary btn-click',
                                        click: function (data) {
                                            detailData("up", event);
                                        },
                                        visible: function (row) {
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }
                                    },
                                    {
                                        name: 'down',
                                        title: '下移',
                                        text: '下移',
                                        classname: 'btn btn-xs btn-primary btn-click',
                                        click: function (data) {
                                            detailData("down", event);
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

                Fast.config.openArea = ['100%', '100%'];
                // 为表格绑定事件
                Table.api.bindevent(table);

                Layer.open({
                    type: 1,
                    area: ['100%', '100%'],
                    btn: ['确认推荐', '取消'],
                    shade: 0.3,
                    zIndex: 10,
                    closeBtn: 1,
                    title: "匹配推荐",
                    content: $('#layer-vs'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                    yes: function (index, layero) {
                        //按钮【按钮一】的回调
                        console.log("yes")
                        let sel = $('#table').bootstrapTable('getAllSelections');
                        let sel2 = Table.api.selectedids(table);
                        let sel3 = Table.api.selecteddata(table);

                        console.log(sel)
                        console.log(sel2)
                        console.log(sel3)
                        let selArr = [];
                        sel3.forEach(function (item) {
                            selArr.push(item.user_id)
                        })
                        console.log(selArr)
                        // return
                        // let vsids = join(sel2, ',');
                        //js将数组sel2转化为字符串
                        var vsids = selArr.join(',');
                        // var text = $('#text').val();
                        if (selArr.length == 0) {

                        } else {
                            console.log(vsids)

                            Fast.api.ajax({
                                url: "requirement/vs/ids/" + ids,
                                data: {
                                    'vsids': vsids
                                },
                            }, function (data, ret) {
                                parent.Layer.closeAll();
                                parent.location.reload();
                                //return false;
                            });
                        }
                        layer.close(index, layero);
                    },
                    success() {
                        console.log("success")
                    }
                });
            }

            let s_word = $("#s_word").val()
            if (s_word) {
                s_word = '当前需求存在敏感词：' + s_word + '<br/>'
            }

            let s_files = $("#s_files").val()
            if (s_files) {
                s_files = s_files.split(',')
                s_word += '当前需求存在疑似违规图片：<br/><br/>'
                for (let i in s_files) {
                    s_word += '<img src="https://qianqiance-1322901684.cos.ap-guangzhou.myqcloud.com' + s_files[i] + '" data-tips-image height="100">&nbsp;&nbsp;&nbsp;'
                }
            }

            if (!s_word) {
                s_word = '是否确认通过审核';
            }
            $('#pass').click(function () {
                var formData = $('#add-form').serializeArray();
                let content = ''
                let files = ''
                for (let i in formData) {
                    formData[i].name == 'content' ? content = formData[i].value : ''
                    formData[i].name == 'files' ? files = formData[i].value : ''
                }

                let data = {
                    content: content,
                    files:files,
                }
                Layer.confirm(s_word, {
                        btn: [__('OK'), __('Cancel')] // 按钮
                    }, function () {
                        Fast.api.ajax({
                            url: "requirement/pass/ids/" + ids,
                            data: data,
                        }, function (data, ret) {
                            var automatic_matching = Config.automatic_matching;
                            if (automatic_matching === '0') {
                                Layer.confirm('是否进入专家推荐流程', {
                                    btn: [__('OK'), __('Cancel')] // 按钮
                                }, function () {
                                    Layer.closeAll();
                                    vsclick(ids)
                                }, function () {
                                    parent.Layer.closeAll();
                                    parent.location.reload();
                                })
                            } else {
                                parent.Layer.closeAll();
                                parent.location.reload();
                            }


                            //return false;
                        });

                    }, function () {
                        parent.Layer.closeAll();
                        parent.location.reload();
                    }
                )
            })
            $('#nopass').click(function () {

                Layer.prompt({
                    title: "输入不通过原因",
                    success: function (layero) {
                        $("input", layero).prop("placeholder", "填写拒绝理由");
                    }
                }, function (value) {
                    var formData = $('#add-form').serializeArray();
                    let content = ''
                    let files = ''

                    for (let i in formData) {
                        formData[i].name == 'content' ? content = formData[i].value : ''
                        formData[i].name == 'files' ? files = formData[i].value : ''
                    }

                    let data = {
                        content: content,
                        files:files
                    }
                    Fast.api.ajax({
                        url: "requirement/nopass/ids/" + ids,
                        data: data,
                    }, function (data, ret) {
                        parent.Layer.closeAll();
                        parent.location.reload();
                        //return false;
                    });
                });
            })

            // $('.specialistpass').on('click','.item',function(e){
            //     var spid =  $(e.target).data('field-index')
            //     console.log(spid);

            // })
            $('.specialistpass').click(function (e) {
                let spid = $(e.target).data('field-index')
                console.log(spid);
                Layer.confirm('是否确认通过专家申请', {
                        btn: [__('OK'), __('Cancel')] // 按钮
                    }, function () {

                        console.log(spid)
                        Fast.api.ajax({
                            url: "requirement/specialist_pass/ids/" + spid,
                            data: {},
                        }, function (data, ret) {
                            parent.Layer.closeAll();
                            parent.location.reload();
                        });
                    }, function () {

                    }
                )
            })
            $('.specialistnopass').click(function (e) {
                let spid = $(e.target).data('field-index')

                Layer.prompt({
                    title: "不通过原因",
                    success: function (layero) {
                        $("textarea", layero).prop("placeholder", "请输入原因");
                    }
                }, function (value) {
                    var pap = {
                        reason: value
                    }
                    Fast.api.ajax({
                        url: "requirement/specialist_nopass/ids/" + spid,
                        data: pap
                    }, function (data, ret) {
                        parent.Layer.closeAll();
                        parent.location.reload();
                        return false;
                    });
                });
            })

            $(".click_reason").click(function () {
                var reason = $(this).data("reason");
                Layer.msg(reason);
            })

            //沟通审核
            $('.meetingpass').click(function (e) {
                let msg = $(e.target).data('msg')
                let spid = $(e.target).data('field-index')
                let meetingid = $(e.target).data('meeting-id')
                console.log(msg)
                Layer.confirm("申请理由：<br>" + msg, {
                        title: "沟通申请审核",
                        btn: ['通过', '不通过'] // 按钮
                    }, function () {

                        console.log(spid)
                        Fast.api.ajax({
                            url: "requirement/meetingpass/ids/" + meetingid,
                            data: {},
                        }, function (data, ret) {
                            parent.Layer.closeAll();
                            parent.location.reload();
                            //return false;
                        });
                    }, function () {
                        Layer.prompt({
                            title: "不通过理由",
                            success: function (layero) {
                                $("input", layero).prop("placeholder", "填写不通过理由");
                            }
                        }, function (value) {
                            Fast.api.ajax({
                                url: "requirement/meetingnopass/ids/" + meetingid,
                                data: {reason: value},
                            }, function (data, ret) {
                                parent.Layer.closeAll();
                                parent.location.reload();
                            });
                        });
                    }
                )
            })


            //沟通信息
            $('.meetingdetail').click(function (e) {
                let meetingid = $(e.target).data('meeting-id')
                let info = $(e.target).data('info')
                $('#text').html(info)
                Layer.open({
                    type: 1,
                    area: ['80%', '300px'],
                    btn: ['确认', '取消'],
                    shade: 0.3,
                    zIndex: 10,
                    closeBtn: 1,
                    title: "沟通信息",
                    yes: function (index, layero) {
                        //按钮【按钮一】的回调
                        console.log("yes")
                        var text = $('#text').val();
                        if (text == '') {

                        } else {
                            console.log(text)

                            Fast.api.ajax({
                                url: "requirement/meetingdetail/ids/" + meetingid,
                                data: {
                                    'info': text
                                },
                            }, function (data, ret) {
                                parent.Layer.closeAll();
                                parent.location.reload();
                                //return false;
                            });
                        }
                        // layer.close(index,layero);
                    },
                    content: $('#layer-info'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                    success() {
                        console.log("success")
                    }
                });
            })

            function novsclick() {
                var id = $(this).data('id');
                Fast.api.ajax({
                    url: "requirement/novsclick/ids/" + id
                }, function (data, ret) {
                    parent.location.reload();
                    parent.layer.closeAll();
                });
            }

            //立即推荐
            $('#vs').click(function () {
                // console.log($(this).data('id'));
                vsclick($(this).data('id'));
            })
            $('#novs').click(novsclick)

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


