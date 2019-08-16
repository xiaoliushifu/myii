/**
 * Created by chaitao on 16/3/2.
 */
(function ($) {
    /*全选按钮,以及所包含的元素,元素得到后绑定事件,如果有取消勾选的,那么全选按钮去掉*/
    $(':checkbox.select-all').bind('click', function (e) {
        var _this = $(this), children = $(":checkbox." + _this.data('class'));
        children.bind('click', function (e) {
            var __this = $(this)
            //遍历所有子选项, 如果有自选项是为选中,那么标题的复选框取消,反之,则全部选中
            var checkArray = $.map(children, function (e) {
                return $(e).prop("checked")
            })

            if ($.inArray(false, checkArray) >=0) {
                _this.prop("checked", false)
            } else {
                _this.prop("checked", true)
            }

        })

        $(":checkbox." + _this.data('class')).prop('checked', _this.prop("checked"))

    })
})(jQuery)