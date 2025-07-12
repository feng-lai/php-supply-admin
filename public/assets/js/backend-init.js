define(['backend'], function (Backend) {
    $('body').on('click', '[data-tips-image]', function () {
        var img = new Image();
        var imgWidth = this.getAttribute('data-width') || '480px';
        img.onload = function () {
            var $content = $(img).appendTo('body').css({background: '#fff', width: imgWidth, height: 'auto'});
            Layer.open({
                type: 1, area: imgWidth, title: false, closeBtn: 1,
                skin: 'layui-layer-nobg', shadeClose: true, content: $content,
                end: function () {
                    $(img).remove();
                },
                success: function () {

                }
            });
        };
        img.onerror = function (e) {

        };
        img.src = this.getAttribute('data-tips-image') || this.src;
    });

    $('#c-content1').on('summernote.change',
        function (we, contents, $editable) {
            let num = parseInt(contents.replace(/(<([^>]+)>)/ig, '').length)
            $('#num1').text(num)
            // 此处编写数据处理逻辑
            if(num > 300){
                Layer.msg('超出字数')
            }
        });

    $('#c-content2').on('summernote.change',
        function (we, contents, $editable) {
            let num = parseInt(contents.replace(/(<([^>]+)>)/ig, '').length)
            $('#num2').text(num)
            // 此处编写数据处理逻辑
            if(num > 240){
                Layer.msg('超出字数')
            }
        });
});