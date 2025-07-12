define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'specialist/index' + location.search,
                    add_url: 'specialist/add',
                    edit_url: 'specialist/edit',
                    del_url: 'specialist/del',
                    multi_url: 'specialist/multi',
                    import_url: 'specialist/import',
                    seltag_url: 'specialist/seltag',
                    table: 'specialist',
                }
            });

            var table = $("#table");
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
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'user_nickname', title: __('Nickname'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'mobile', title: '手机号', operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'avg_score', title: "服务评分", operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'wechat', title: __('Wechat'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'id_no_front_image', title: __('Id_no_front_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'id_no_backend_image', title: __('Id_no_backend_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        
                        // {field: 'addr', title: __('Addr'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        //
                        {field: 'ids', title: '关联标签', operate: 'LIKE', formatter: function (value, row, index) {
                                const combinedArray = [...row.industry_arr, ...row.skill_arr, ...row.area_arr];
                                const combinedNames = combinedArray.map(item => item.name).join(' ');

                                return combinedNames;
                        }},
                        // {field: 'skill_ids', title: __('Skill_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'area_ids', title: __('Area_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'level_ids', title: __('Level_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'keywords_json', title: __('Keywords_json')},
                        // {field: 'lowest_price', title: __('Lowest_price'), operate:'BETWEEN'},
                        // {field: 'case_json', title: __('Case_json')},
                        // {field: 'certificate_ids', title: __('Certificate_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'edu_ids', title: __('Edu_ids'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'feature_json', title: __('Feature_json')},
                        {field: 'createtime_text', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'status', title: __('Status'), searchList: {"0":'待审核',"1":'正常',"2":"禁用"}, formatter: Table.api.formatter.status},
                        // {field: 'tags', title: __('Tags'), operate: 'LIKE', formatter: Table.api.formatter.flag},

                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '查看详情',
                                    text: '查看详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'specialist/edit',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'detail',
                                    title: '编辑',
                                    text: '编辑',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'specialist/detail',
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
                                    visible: function (row) {
                                        if(row.status !== '1'){
                                            return true;
                                        }else{
                                            return false;
                                        }

                                    },
                                    click: function (e, row) {
                                        Layer.confirm('是否确认通过', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "specialist/multi",
                                                    data: {'ids':row.id,'params':'status=1'},
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
                                        if(row.status !== '2'){
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
                                                    url: "specialist/multi",
                                                    data: {'ids':row.id,'params':'status=2'},
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
                url: 'specialist/recyclebin' + location.search,
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
                                    url: 'specialist/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'specialist/destroy',
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
            let certArr = []
            //添加证书
            $('#addcert').click(function(){
                Layer.open({
                    type: 1,
                    area: ['80%', '300px'],
                    btn: ['确认', '取消'],
                    shade: 0.3,
                    zIndex:10,
                    closeBtn: 1,
                    title:"新增证书",
                    yes: function(index, layero){
                        //按钮【按钮一】的回调
                        console.log("yes")
                        let form = $("#add-certificate-form");
                        console.log(form)
                        var name = form.find("input[name='row[name]']").val();
                        var certifiimage = form.find("input[name='row[certifiimage]']").val();
                        var certifitime = form.find("input[name='row[certifitime]']").val();
                        var certifi_company = form.find("input[name='row[certifi_company]']").val();
                        
                        let length = certArr.length
                        let idx = 0
                        if(length > 0){
                            let lastTmp = certArr[length - 1]
                            idx = lastTmp.idx + 1
                        }
                        let tmpCert = {
                            idx,
                            name,
                            certifiimage,
                            certifitime,
                            certifi_company
                        }
                        certArr.push(tmpCert)
                        

                        // 创建一个新的行
                        var newRow = $("<tr id='row"+idx+"' style='line-height:80px;'>");
                        
                        // 创建新的单元格
                        var nameCell = $("<td class='td'>").text(name);
                        var img = Fast.api.cdnurl(certifiimage, true);
                        var certifiimageCell = $("<td class='td'>").html('<a class="thumbnail"><image style="height:80px;" src="'+img+'" height="80px" /></a>');
                        var certifitimeCell = $("<td class='td'>").text(certifitime);
                        var certifi_companyCell = $("<td class='td'>").text(certifi_company);
                        
                        // 将单元格添加到行中
                        // newRow.append("<td></td>");
                        newRow.append(nameCell);
                        newRow.append(certifitimeCell);
                        newRow.append(certifiimageCell);
                        newRow.append(certifi_companyCell);
                        newRow.append('<a  class="btn btn-warning delcert" data-index="'+idx+'">删除</a>');
                        // 将行添加到表格中
                        $("#table2").append(newRow);
                        // Fast.api.close();
                        layer.close(index,layero);
                    },
                    content: $('#add-certificate-form'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                    success(){
                        console.log("success")
                        
                    }
                });
            })
            //删除证书
            $('#table2').on('click','.delcert',function(e){
               let idx =  $(e.target).data('index')
                console.log(idx)
                let tmpArr = []
                certArr.forEach((item,index)=>{
                    if(idx !== item.idx){
                        tmpArr.push(item)
                    }
                })
                certArr = tmpArr
                $("#row"+idx).remove()

            })
            //提交
            $('#sub').click(function(e){
                if (!$('#add-form').isValid() ) {
                    return;
                }
                // 获取表单所有数据
                let form = $('#add-form').serializeArray();
                // 追加证书数据
                let postCert = []
                let certJson = JSON.stringify(certArr)
                form.push({
                        name: 'row[certificate_json]',
                        value: certJson
                    })
                Fast.api.ajax({
                    url: "specialist/add",
                    dataType:"json",
                    data:form
                },function(data, ret){
                    console.log("提交数据")
                    console.log(data)
                    console.log(ret)
                    if(ret && ret.code == 1){
                        window.top.location.href ="/kfSypMgbqw.php/specialist/index?ref=addtabs"
                    }else{
                        // Toastr.error(data.msg);
                        // Fast.api.close(); // 关闭窗体并回传数据
                        // parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
                    }

                })
                
                
            })


            $('.form-group').on('click','.sel',function(e){
                var type = $(this).data('type');
                Fast.api.open('specialist/seltag?type='+type,__('Choose'),{
                    callback: function (data) {
                        getType(data);
                    }
                });
            })

            function getType(data){

                let industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                let skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];

                let area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                data.forEach((item,index)=>{
                    console.log(item);

                    if(item.type.toString() === '1' && !industry_ids.includes(item.id.toString())){
                        industry_ids.push(item.id);
                        $(".industry_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }else if(item.type.toString() == '2' && !skill_ids.includes(item.id.toString())){
                        skill_ids.push(item.id);
                        $(".skill_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');

                    }else if(item.type.toString() == '3' && !area_ids.includes(item.id.toString())){
                        area_ids.push(item.id);
                        $(".area_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }
                })

                $('#c-industry_ids').val(industry_ids.join(','));
                $('#c-skill_ids').val(skill_ids.join(','));
                $('#c-area_ids').val(area_ids.join(','));
            }

            $(document).on('click','.tag-remove',function(e){
                var id = $(this).data('id');
                var type = $(this).data('type');
                var industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                var skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];
                var area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                if(type == '1'){
                    var join = industry_ids.filter(function(num) {
                        return num != id;
                    });
                    $('#c-industry_ids').val(join.join(','));
                }else if(type == '2'){
                    var join = skill_ids.filter(function(num) {
                        return num != id;
                    });
                    $('#c-skill_ids').val(join.join(','));
                }else if(type == '3'){
                    var join = area_ids.filter(function(num) {
                        return num != id;
                    });
                    $('#c-area_ids').val(join.join(','));
                }
                $(".dropup_"+id).remove();

            })

            
            Controller.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            let ids = $("#ids").val()

            $(".LevelSum").click(function(){
                var uid = $('#myTabContent').data('value');
                var level_ids = $("#level_ids").val();
                Fast.api.ajax({
                    url: "specialist/edit",
                    data: {level_ids:level_ids,uid:uid},
                }, function (data, ret) {
                    Layer.closeAll();
                    $(".btn-refresh").trigger("click");
                    //return false;
                });
            })

            $('#pass').click(function(){
                Layer.confirm('是否确认通过审核', {
                        btn: [__('OK'),__('Cancel')] // 按钮
                    }, function(){
                        Fast.api.ajax({
                            url: "specialist/pass/ids/" + ids,
                            data: {},
                        }, function (data, ret) {
                            Layer.closeAll();
                            $(".btn-refresh").trigger("click");
                            //return false;
                        });
                    }, function(){

                    }
                )
            })
            $('#nopass').click(function(){
                Layer.prompt({
                    title: "输入不通过原因",
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
                        url: "specialist/nopass/ids/" + ids,
                        data: pap,
                    }, function (data, ret) {
                        Layer.closeAll();
                        $(".btn-refresh").trigger("click");
                        //return false;
                    });
                });
            })

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
            $(document).ready(function(){
                // 在这里编写你想要执行的代码
                console.log("ready")
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
            var table = $("#table");
            // 初始化表格-需求记录
            table.bootstrapTable({
                url:'requirement/indexspec/type/1/uid/'+uid,
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
                        // {field: 'sn', title: __('需求编号'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'title', title: __('需求名称'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        
                        {field: 'tag', title: __('关联标签'), formatter: Table.api.formatter.flag},
                        {field: 'content', title: __('需求描述')},
                        {field: 'status_text', title: __('状态'), searchList: {"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        // {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime_text', title: __('发布时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        // {field: 'user_name', title: __('User_name'), operate: 'LIKE'},
                        // {field: 'user_type', title: __('User_type'), searchList: {"1":__('User_type 1'),"2":__('User_type 2')}, formatter: Table.api.formatter.normal},
                        
                        
                        
                       
                        // {field: 'begin', title: __('Begin'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'end', title: __('End'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        
                        // {field: 'files', title: __('Files'), operate: false, formatter: Table.api.formatter.files},
                        // {field: 'open_price_data', title: __('Open_price_data'), searchList: {"0":__('Open_price_data 0'),"1":__('Open_price_data 1')}, formatter: Table.api.formatter.normal},
                        // {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        
                        // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        // {field: 'publishtime', title: __('Publishtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
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

            // 初始化表格-订单记录
            table_order.bootstrapTable({
                url: 'order/order/indexspec/role_type/2/uid/'+uid,
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
                        {field: 'status_text', title: __('状态')},
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


            var table_invoice = $("#table-invoice");

            // 初始化表格-订单记录
            table_invoice.bootstrapTable({
                url: 'billmanage/index/type/1/uid/'+uid,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                searchFormVisible: true,
                searchFormTemplate: 'invoicecustomformtpl',
                columns: [
                    [
                        {field: 'index', title: '序号', formatter: function (value, row, index) {return index + 1;}},
                        {field: 'total', title: '冻结服务费金额', operate: 'LIKE'},
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
                        {field: 'createtime', title: '时间', operate: 'LIKE'}
                    ]
                ]
            });


            var table_sendinvoice = $("#table-sendinvoice");

            // 初始化表格-订单记录
            table_sendinvoice.bootstrapTable({
                url: 'billmanage/index/type/4/uid/'+uid,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                searchFormVisible: true,
                searchFormTemplate: 'invoicecustomformtpl',
                columns: [
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
                        {field: 'name', title: '收款人', operate: 'LIKE'},
                        {field: 'createtime', title: '申请时间', operate: 'LIKE'},
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
                                    title: '查看订单详情',
                                    text: '查看订单详情',
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        vertifylist: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'specialist/index' + location.search,
                    vertify_list: 'specialist/vertifylist' + location.search,
                    add_url: 'specialist/add',
                    edit_url: 'specialist/edit',
                    del_url: 'specialist/del',
                    multi_url: 'specialist/multi',
                    import_url: 'specialist/import',
                    table: 'vertify',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.vertify_list,
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
                        // {field: 'user_id', title: "认证类型", operate: 'LIKE'},
                        {field: 'user_id', title: '认证类型', searchable:false,operate: 'LIKE', formatter: function (value, row, index) {
                                return "专家";
                        }},
                        {field: 'name', title: "真实姓名", operate: 'LIKE'},
                        {field: 'createtime', title: "提交时间", formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'status', title: "审核状态", searchList: {"0":"未审核","1":"通过","2":"拒绝"}, formatter: Table.api.formatter.status},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            text: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '查看详情',
                                    text: '查看详情',
                                    extend: 'data-area=\'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    // icon: 'fa fa-list',
                                    url: 'specialist/review_details/ids/{id}/auth/1',
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
                                    visible: function (row) {
                                        if(row.status == '0'){
                                            return true;
                                        }else{
                                            return false;
                                        }

                                    },
                                    click: function (e, row) {
                                        Layer.confirm('是否确认通过', {
                                                btn: [__('OK'),__('Cancel')] // 按钮
                                            }, function(){
                                                Fast.api.ajax({
                                                    url: "specialist/multi2",
                                                    data: {'ids':row.id,'status':1},
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
                                    title: '拒绝',
                                    text: '拒绝',
                                    classname: 'btn btn-xs btn-success btn-click',
                                    visible: function (row) {
                                        if(row.status == '0'){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    },
                                    click: function (e, row) {
                                        Layer.prompt({
                                            title: "拒绝理由",
                                            success: function (layero) {
                                                $("input", layero).prop("placeholder", "填写拒绝理由");
                                                $("input", layero).prop("value", " ");
                                            }
                                        }, function (value) {
                                            Fast.api.ajax({
                                                url: "specialist/multi2",
                                                data: {reason: value,'ids':row.id,'status':2},
                                            }, function (data, ret) {
                                                Layer.closeAll();
                                                $(".btn-refresh").trigger("click");
                                                //return false;
                                            });
                                        });
                                        return false;
                                    }
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        review_details:function(){
            let certArr = Config.certificate_json
            //添加证书
            $('#addcert').click(function(){
                Layer.open({
                    type: 1,
                    area: ['80%', '300px'],
                    btn: ['确认', '取消'],
                    shade: 0.3,
                    zIndex:10,
                    closeBtn: 1,
                    title:"新增证书",
                    yes: function(index, layero){
                        //按钮【按钮一】的回调
                        let form = $("#add-certificate-form");
                        var name = form.find("input[name='row[name]']").val();
                        var certifiimage = form.find("input[name='row[certifiimage]']").val();
                        var certifitime = form.find("input[name='row[certifitime]']").val();
                        var certifi_company = form.find("input[name='row[certifi_company]']").val();

                        let length = certArr.length
                        let idx = 0
                        if(length > 0){
                            let lastTmp = certArr[length - 1]
                            idx = lastTmp.idx + 1
                        }
                        let tmpCert = {
                            idx,
                            name,
                            certifiimage,
                            certifitime,
                            certifi_company
                        }

                        certArr.push(tmpCert)

                        // 创建一个新的行
                        var newRow = $("<tr id='row"+idx+"' style='line-height:80px;'>");

                        // 创建新的单元格
                        var nameCell = $("<td class='td'>").text(name);
                        var img = Fast.api.cdnurl(certifiimage, true);
                        var certifiimageCell = $("<td class='td'>").html('<a class="thumbnail"><image style="height:80px;" src="'+img+'" height="80px" /></a>');
                        var certifitimeCell = $("<td class='td'>").text(certifitime);
                        var certifi_companyCell = $("<td class='td'>").text(certifi_company);
                        var certifi_del = $("<td class='td'>").html('<a  class="btn btn-warning delcert" data-index="'+idx+'">删除</a>');

                        // 将单元格添加到行中
                        // newRow.append("<td></td>");
                        newRow.append(nameCell);
                        newRow.append(certifitimeCell);
                        newRow.append(certifiimageCell);
                        newRow.append(certifi_companyCell);
                        newRow.append(certifi_del);
                        // 将行添加到表格中
                        $("#table2").append(newRow);
                        // Fast.api.close();
                        layer.close(index,layero);
                    },
                    content: $('#add-certificate-form'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                    success(){
                        console.log("success")

                    }
                });
            })
            //删除证书
            $('#table2').on('click','.delcert',function(e){
                let idx =  $(e.target).data('index')
                console.log(idx)
                let tmpArr = []
                certArr.forEach((item,index)=>{
                    if(idx !== item.idx){
                        tmpArr.push(item)
                    }
                })
                certArr = tmpArr
                $("#row"+idx).remove()

            })
            $('.sel2').click(function(e){
                var type = $(this).data('type');
                Fast.api.open('specialist/seltag?type='+type,__('Choose'),{
                    callback: function (data) {
                        getType(data);
                    }
                });
            })

            function getType(data){
                let industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                let skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];

                let area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                data.forEach((item,index)=>{

                    if(item.type.toString() === '1' && !industry_ids.includes(item.id.toString())){
                        industry_ids.push(item.id);
                        $(".industry_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }else if(item.type.toString() == '2' && !skill_ids.includes(item.id.toString())){
                        skill_ids.push(item.id);
                        $(".skill_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');

                    }else if(item.type.toString() == '3' && !area_ids.includes(item.id.toString())){
                        area_ids.push(item.id);
                        $(".area_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }
                })

                $('#c-industry_ids').val(industry_ids.join(','));
                $('#c-skill_ids').val(skill_ids.join(','));
                $('#c-area_ids').val(area_ids.join(','));
            }

            $(document).on('click','.tag-remove',function(e){
                var id = $(this).data('id');
                var type = $(this).data('type');
                var industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                var skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];
                var area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                if(type == '1'){
                    var join = industry_ids.filter(function(num) {
                        return num != id;
                    });
                    console.log(join)
                    $('#c-industry_ids').val(join.join(','));
                }else if(type == '2'){
                    var join = skill_ids.filter(function(num) {
                        return num != id;
                    });
                    $('#c-skill_ids').val(join.join(','));
                }else if(type == '3'){
                    var join = area_ids.filter(function(num) {
                        return num != id;
                    });
                    $('#c-area_ids').val(join.join(','));
                }
                $(".dropup_"+id).remove();

            })



            $("#pass").click(function(){
                let formData = $('#review_details').serializeArray();

                var id = $(this).data("id");
                formData.push({name:"ids",value:id})
                formData.push({name:"status",value:1})
                // 追加证书数据
                let postCert = []
                let certJson = JSON.stringify(certArr)
                formData.push({
                    name: 'row[certificate_json]',
                    value: certJson
                })
                Layer.confirm('是否确认通过', {
                        btn: [__('OK'),__('Cancel')] // 按钮
                    }, function(){
                        Fast.api.ajax({
                            url: "specialist/multi2",
                            data: formData,
                        }, function (data, ret) {
                            parent.Layer.closeAll();
                            parent.location.reload();
                        });
                    }
                )
            })
            $("#nopass").click(function(){
                let formData = $('#review_details').serializeArray();
                var id = $(this).data("id");
                formData.push({name:"ids",value:id})
                formData.push({name:"status",value:2})
                // 追加证书数据
                let postCert = []
                let certJson = JSON.stringify(certArr)
                formData.push({
                    name: 'row[certificate_json]',
                    value: certJson
                })
                Layer.prompt({
                    title: "拒绝理由",
                    success: function (layero) {
                        $("input", layero).prop("placeholder", "填写拒绝理由");
                        $("input", layero).prop("value", " ");
                    }
                }, function (value) {
                    formData.push({name:"reason",value:value})
                    Fast.api.ajax({
                        url: "specialist/multi2",
                        data: formData,
                    }, function (data, ret) {
                        parent.Layer.closeAll();
                        parent.location.reload();
                    });
                });
                return false;
                /**Fast.api.ajax({
                    url: "specialist/multi2",
                    data: formData,
                }, function (data, ret) {
                    parent.Layer.closeAll();
                    parent.location.reload();
                });**/

            })

            Controller.api.bindevent();
        },
        seltag: function () {
            // Select
            var tagarr = [];//
            $(document).ready(function () {

                $('#search').keydown(function(event) {
                    if (event.keyCode === 13) {
                        var val = $(this).val();
                        if(val == ''){
                            return;
                        }
                        Fast.api.ajax({
                            url: "specialist/search",
                            dataType:"json",
                            data:{
                                name: val
                            }
                        },function(data, ret){
                            if(!data){
                                layer.msg("未查到信息!")
                            }
                            if(tagarr.some(item => item.id === data.id)){
                                return;
                            }

                            let option = {
                                id:data.id,
                                pid:data.pid,
                                level:data.level,
                                name:data.name,
                                sel:data.sel,
                                path:data.path,
                                type:data.type
                            }
                            tagarr.push(option);
                            $(".sel-tag").append('<div class="btn-group dropup dropup_'+data.id+'"><button type="button" class="btn btn-primary">'+data.name+'</button><button data-id="'+data.id+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>')
                            return false;
                        })
                    }
                });

                // 标签类型点击
                $('[data-toggle="tab"]').click(function (e) {

                    var type = $(e.target).data('value')
                    // 清空区域列表
                    $(".tag-one").html("")
                    $(".tag-two").html("")
                    $(".tag-three").html("")
                    // 获取一级列表
                    Fast.api.ajax({
                        url: "specialist/seltag",
                        dataType:"json",
                        data:{
                            type: type,
                            level: '1',
                            pid: 0
                        }
                    },function(data, ret){
                        data.forEach((item,index)=>{
                            $(".tag-one").append('<div data-value="'+item.id+'" data-type="'+item.type+'" data-pid="'+item.pid+'"  data-sel="'+item.sel+'"  data-level="'+item.level+'"  data-name="'+item.name+'" data-path="'+item.path+'"  class="item">'+item.name+'</div>');
                        })
                        return false;
                    })
                })
                
                // 一级标签点击
                $('.tag-one').on('click','.item',function(e){
                    var type = $('[data-field="type"] .active a').data('value')
                    var pid =  $(e.target).data('value')
                    // 清空所有所选样式
                    // $('.tag-two .item').removeClass('checked')
                    // $('.tag-three .item').removeClass('checked')
                    $(e.target).siblings().removeClass('checked')
                    $(e.target).addClass('checked');
                    console.log(pid,"pid")
                    // 清空区域列表
                    // $(".tag-one").html("")
                    $(".tag-two").html("")
                    $(".tag-three").html("")
                    // 获取一级列表
                    Fast.api.ajax({
                        url: "specialist/seltag",
                        dataType:"json",
                        data:{
                            type: type,
                            level: '2',
                            pid: pid
                        }
                    },function(data, ret){
                        data.forEach((item,index)=>{
                            $(".tag-two").append('<div data-value="'+item.id+'"  data-type="'+item.type+'" data-pid="'+item.pid+'"  data-sel="'+item.sel+'"  data-level="'+item.level+'"  data-name="'+item.name+'" data-path="'+item.path+'"   class="item">'+item.name+'</div>');
                        })
                        return false;
                    })
                })

                // 二级标签点击
                $('.tag-two').on('click','.item',function(e){
                    var type = $('[data-field="type"] .active a').data('value')
                    var pid =  $(e.target).data('value')
                    var sel =  $(e.target).data('sel')
                    $(e.target).siblings().removeClass('checked')
                    $(e.target).addClass('checked');
                    console.log(pid,"pid")
                    // 清空区域列表
                    // $(".tag-one").html("")
                    // $(".tag-two").html("")
                    $(".tag-three").html("")
                    if(sel == '0'){
                        // 获取列表
                        Fast.api.ajax({
                            url: "specialist/seltag",
                            dataType:"json",
                            data:{
                                type: type,
                                level: '3',
                                pid: pid
                            }
                        },function(data, ret){
                            data.forEach((item,index)=>{
                                $(".tag-three").append('<div data-value="'+item.id+'" data-type="'+item.type+'" data-pid="'+item.pid+'"  data-sel="'+item.sel+'"  data-level="'+item.level+'" data-name="'+item.name+'" data-path="'+item.path+'"   class="item">'+item.name+'</div>');
                            })
                            return false;
                        })
                    }else{
                        // 选中当前元素
                        // 清除当前元素的所有子选项
                        let option_id = $(e.target).data('value')
                        let option_pid = $(e.target).data('pid')
                        let option_level = $(e.target).data('level')
                        let option_name = $(e.target).data('name')
                        let option_sel = $(e.target).data('sel')
                        let option_path = $(e.target).data('path')
                        let option_type = $(e.target).data('type')
                        let option = {
                            id:option_id,
                            pid:option_pid,
                            level:option_level,
                            name:option_name,
                            sel:option_sel,
                            path:option_path,
                            type:option_type
                        }
                        let tmpArr = tagarr
                        let optionArr = []
                        optionArr.push(option)
                        tmpArr.forEach((item,index)=>{
                            // 排除自己
                            if(item.id == option.id){
                                return
                            }
                            // 排除同级
                            // if(item.pid !== option.id){
                            //     optionArr.push(item)
                            // }
                            // 排除不同 子菜单
                            var pathArr = item.path.split('-')
                            console.log(pathArr,"patharr")
                            var idstr = option.id+""
                            if(pathArr.indexOf(idstr) === -1){
                                // 不在数组中，即不是item的上级
                                optionArr.push(item)
                            }


                        })
                        tagarr = optionArr
                         // 更新数量
                         $('.sel-num').text('已选中'+tagarr.length+'个标签:')
                        // 更新页面
                        $('.sel-tag').html('');
                        optionArr.forEach((item,index)=>{
                            $(".sel-tag").append('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove" data-id="'+item.id+'"></span></button></div>')
                        })
                        $(".sel-tag").append('<a class="btn btn-link btn-primary tag-empty">清空</a>')
                            

                    }
                    
                })

                // 三级标签点击
                $('.tag-three').on('click','.item',function(e){
                    var type = $('[data-field="type"] .active a').data('value')
                    var pid =  $(e.target).data('value')
                    var level =  $(e.target).data('level')
                    console.log(level,"level")
                    
                    if( level === 2 ){
                        // 点击的全部选项
                        $(e.target).siblings().removeClass('checked')
                    }else{
                        // 去除全部选项
                        $(e.target).first().parent().children().first().removeClass('checked')
                    }
                    $(e.target).addClass('checked');

                    // 选中当前元素
                    // 清除当前元素的所有子选项
                    let option_id = $(e.target).data('value')
                    let option_pid = $(e.target).data('pid')
                    let option_level = $(e.target).data('level')
                    let option_name = $(e.target).data('name')
                    let option_sel = $(e.target).data('sel')
                    let option_path = $(e.target).data('path')
                    let option_type = $(e.target).data('type')
                    let option = {
                        id:option_id,
                        pid:option_pid,
                        level:option_level,
                        name:option_name,
                        sel:option_sel,
                        path:option_path,
                        type:option_type
                    }
                    let tmpArr = tagarr
                    let optionArr = []
                    optionArr.push(option)
                    tmpArr.forEach((item,index)=>{
                        if(item.id == option.id){
                            return
                        }
                        var pathArr = option.path.split('-')
                        console.log(pathArr,"patharr")
                        var idstr = item.id+""

                        var pathArr2 = item.path.split('-')
                        console.log(pathArr2,"patharr2")
                        var idstr2 = option.id+""


                        if((pathArr.indexOf(idstr) === -1)&&(pathArr2.indexOf(idstr2) === -1)){
                            // 既不在上级的全选中，又不包含子选项
                            optionArr.push(item)
                        }
                        
                    })
                    tagarr = optionArr
                    // 更新数量
                    $('.sel-num').text('已选中'+tagarr.length+'个标签:')
                    // 更新页面
                    $('.sel-tag').html('');
                    optionArr.forEach((item,index)=>{
                        console.log(item)
                        $(".sel-tag").append('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>')
                    })
                    $(".sel-tag").append('<a class="btn btn-link btn-primary tag-empty">清空</a>')
                            
                   
                })

                // 确认选择
                $(document).on('click', '#confirm', function(){
                    //data为要传输的数据，只能放一个数据，所以传输的数据多的时候可以拼接一个字符串
                    Fast.api.close(tagarr);
                });

                $(document).on('click', '#cancel', function(){
                    Fast.api.close();
                });

                $(document).on('click','.tag-remove',function(e){
                    var id = $(this).data('id');
                    console.log(id)
                    tagarr = tagarr.filter(item => item.id !== id);
                    $(".dropup_"+id).remove();
                })

                $(document).on('click','.tag-empty',function(e){
                    tagarr = [];
                    $('.sel-num').text('已选中0个标签:')
                    $(".dropup").remove();
                })

            })



        },
        detail: function () {
            let ids = $("#ids").val()
            let certArr = Config.certificate_json
            //添加证书
            $('#addcert').click(function(){
                Layer.open({
                    type: 1,
                    area: ['80%', '300px'],
                    btn: ['确认', '取消'],
                    shade: 0.3,
                    zIndex:10,
                    closeBtn: 1,
                    title:"新增证书",
                    yes: function(index, layero){
                        //按钮【按钮一】的回调
                        let form = $("#add-certificate-form");
                        var name = form.find("input[name='row[name]']").val();
                        var certifiimage = form.find("input[name='row[certifiimage]']").val();
                        var certifitime = form.find("input[name='row[certifitime]']").val();
                        var certifi_company = form.find("input[name='row[certifi_company]']").val();

                        let length = certArr.length
                        let idx = 0
                        if(length > 0){
                            let lastTmp = certArr[length - 1]
                            idx = lastTmp.idx + 1
                        }
                        let tmpCert = {
                            idx,
                            name,
                            certifiimage,
                            certifitime,
                            certifi_company
                        }

                        certArr.push(tmpCert)

                        // 创建一个新的行
                        var newRow = $("<tr id='row"+idx+"' style='line-height:80px;'>");

                        // 创建新的单元格
                        var nameCell = $("<td class='td'>").text(name);
                        var img = Fast.api.cdnurl(certifiimage, true);
                        var certifiimageCell = $("<td class='td'>").html('<a class="thumbnail"><image style="height:80px;" src="'+img+'" height="80px" /></a>');
                        var certifitimeCell = $("<td class='td'>").text(certifitime);
                        var certifi_companyCell = $("<td class='td'>").text(certifi_company);
                        var certifi_del = $("<td class='td'>").html('<a  class="btn btn-warning delcert" data-index="'+idx+'">删除</a>');

                        // 将单元格添加到行中
                        // newRow.append("<td></td>");
                        newRow.append(nameCell);
                        newRow.append(certifitimeCell);
                        newRow.append(certifiimageCell);
                        newRow.append(certifi_companyCell);
                        newRow.append(certifi_del);
                        // 将行添加到表格中
                        $("#table2").append(newRow);
                        // Fast.api.close();
                        layer.close(index,layero);
                    },
                    content: $('#add-certificate-form'), //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                    success(){
                        console.log("success")

                    }
                });
            })
            //删除证书
            $('#table2').on('click','.delcert',function(e){
                let idx =  $(e.target).data('index')
                console.log(idx)
                let tmpArr = []
                certArr.forEach((item,index)=>{
                    if(idx !== item.idx){
                        tmpArr.push(item)
                    }
                })
                certArr = tmpArr
                $("#row"+idx).remove()

            })
            //提交
            $('#sub').click(function(e){
                if (!$('#edit-form').isValid() ) {
                    return;
                }
                // 获取表单所有数据
                let form = $('#edit-form').serializeArray();
                // 追加证书数据
                let postCert = []
                let certJson = JSON.stringify(certArr)
                form.push({
                    name: 'row[certificate_json]',
                    value: certJson
                })
                Fast.api.ajax({
                    url: "specialist/detail",
                    dataType:"json",
                    data:form
                },function(data, ret){
                     Fast.api.close(); // 关闭窗体并回传数据
                    parent.$(".btn-refresh").trigger("click"); // 触发窗体的父页面刷新
                })
            })

            $('.sel2').click(function(e){
                var type = $(this).data('type');
                Fast.api.open('specialist/seltag?type='+type,__('Choose'),{
                    callback: function (data) {
                        getType(data);
                    }
                });
            })

            function getType(data){
                let industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                let skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];

                let area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                data.forEach((item,index)=>{

                    if(item.type.toString() === '1' && !industry_ids.includes(item.id.toString())){
                        industry_ids.push(item.id);
                        $(".industry_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }else if(item.type.toString() == '2' && !skill_ids.includes(item.id.toString())){
                        skill_ids.push(item.id);
                        $(".skill_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');

                    }else if(item.type.toString() == '3' && !area_ids.includes(item.id.toString())){
                        area_ids.push(item.id);
                        $(".area_ids").prepend('<div class="btn-group dropup dropup_'+item.id+'"><button type="button" class="btn btn-primary">'+item.name+'</button><button data-id="'+item.id+'" data-type="'+item.type+'" type="button" class="btn btn-primary tag-remove" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove"></span></button></div>');
                    }
                })

                $('#c-industry_ids').val(industry_ids.join(','));
                $('#c-skill_ids').val(skill_ids.join(','));
                $('#c-area_ids').val(area_ids.join(','));
            }

            $(document).on('click','.tag-remove',function(e){
                var id = $(this).data('id');
                var type = $(this).data('type');
                var industry_ids = $('#c-industry_ids').val();
                industry_ids = industry_ids ? industry_ids.split(',') : [];
                var skill_ids = $('#c-skill_ids').val();
                skill_ids = skill_ids ? skill_ids.split(',') : [];
                var area_ids = $('#c-area_ids').val();
                area_ids = area_ids ? area_ids.split(',') : [];

                if(type == '1'){
                    var join = industry_ids.filter(function(num) {
                        return num != id;
                    });
                    console.log(join)
                    $('#c-industry_ids').val(join.join(','));
                }else if(type == '2'){
                    var join = skill_ids.filter(function(num) {
                        return num != id;
                    });
                    $('#c-skill_ids').val(join.join(','));
                }else if(type == '3'){
                    var join = area_ids.filter(function(num) {
                        return num != id;
                    });
                    $('#c-area_ids').val(join.join(','));
                }
                $(".dropup_"+id).remove();

            })
            Controller.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});
