/**
 * 新增分站页 组织架构选择及关联城市的确定
 */
var repository = {

	zNodes:'',
	//页面中点击选择组织架构按钮 展示弹框  在主页中执行
    _org_init: function () {
        $("#choose_org_btn").click(function () {
            var url = "/yz/repo/search-org?org_id=" + $("#repository-org_id").val();
            window.artDialog = dialog({
                title: '请选择组织架构',
                url: url,
                width: 700,
                onclose: function () {
                    //暂时不需要其他操作
                }
            }).showModal();
        });
    },
    //弹窗视图加载后 渲染出组织架构树 在子视图中（也就是dialog）中执行 所以有parent
    _dialog_search_org: function () {
        var self = this;
        var setting = {
            check: {
                enable: true,
                chkStyle: "radio",
                radioType: "all",
                chkboxType: { "Y": "p", "N": "s" }
            },
            view: {
                selectedMulti: false,
                showIcon: false
            },
            data: {
                key: {
                    name: "org_name",
                    title: 'org_name'
                },
                simpleData: {
                    enable: true,
                    idKey: "org_id",
                    pIdKey: "parent_id"
                }
            },
            callback: {
                beforeCheck: beforeCheck
            }
        };

        //初始化zTree插件，绑定到页面的#treeView上 该插件使用请自行搜索
        var ztree = $.fn.zTree.init($("#treeView"), setting, self.zNodes);
        if (ztree) {
            //设置弹窗 组织架构显示区默认高度
            var h = $(".zTreeDemoBackground").height();
            if (h < 200) {
                h = 200;
            }
            $(".zTreeDemoBackground").css({'height': h, 'overflow': 'auto'});
        }

        //单选按钮选中事件 暂未实现取消选中事件
        function beforeCheck(treeId, treeNode, clickFlag) {
            $("#choose_org_sure").removeAttr('disabled');
            $("#choose_org_name b").html(treeNode.org_name);
            $("#choose_org_name").show();
        }

        //确定按钮的点击事件
        $("#choose_org_sure").click(function () {
            //获取选中的组织架构
            var treeObj = $.fn.zTree.getZTreeObj("treeView");
            //选中当前的
            var nodes = treeObj.getCheckedNodes(true);//选中的
            var orgName='', orgId = '', aesOrgId;
            orgId = nodes[0].org_id;
            aesOrgId=nodes[0].aes_org_id;
            //因为要显示三级组织架构，下面获取所有父节点进行处理
            nodes = nodes[0].getPath();
            nodes.reverse();
            for (var i in nodes) {
                orgName = "——"+nodes[i].org_name+orgName;
                if(i>=2)
                	break;
            }
            //截掉前面的——
            orgName = orgName.substring(2);
            //赋值
            parent.$("#choose_org_name span").html(orgName);
            parent.$("#choose_org_name").show();
            //赋值给母页中的元素，一般是存储架构ID的表单项
            parent.$("#repository-org_id").val(aesOrgId);
            parent.$("#repository-org_name").val(orgName);
            //再去关联城市
            self._getCityByOrgId(aesOrgId);
        });
        //弹窗的取消按钮
        $("#choose_org_clear").click(function () {
            //获取选中的组织架构
//            var treeObj = $.fn.zTree.getZTreeObj("treeView");
//            var nodes = treeObj.getCheckedNodes(true);//选中的
//            var orgName, orgId = '';
//            for (var i = 0; i < nodes.length; i++) {
//                orgName = nodes[i].org_name;
//                orgId = nodes[i].org_id;
//            }
//            for (var i = 0, l = nodes.length; i < l; i++) {
//                treeObj.checkNode(nodes[i], false, false);
//            }
            //parent.$("#repository-org_id").attr('value', '');
            //parent.$("repository-org_name").val('');
            
            //再去关联城市
            //self._getCityByOrgId(aesOrgId,false);
            // 关闭弹出框,
            parent.artDialog.close().remove();
        });
    },
    
    //获得完组织架构ID后，请求关联城市名
    _getCityByOrgId:function($orgid,$act=true)
    {
    	//取消城市操作
    	if(!$act){
    		parent.$("#repository-city_name").val('--');
    		return;
    	}
    	$.post('/yz/repo/get-city', {org_id:$orgid}, function (data) {
    		//var data=eval("("+data+")");
    		parent.$("#repository-city_name").attr('readonly',false);
    		parent.$("#repository-city_name").val(data);
    		parent.$("#repository-city_name").attr('readonly',true);
    		// 一定要等待ajax的结果，才能关闭弹出框
            parent.artDialog.close().remove();
        });
    },

    //新建分站提交
    _repository_button_submit: function(){
        $(document).on('click', '.repository_submit', function(){
            //验证组织架构
            var orgId = $('#repository-org_id').val();
            if(undefined == orgId || '' == orgId) {
                $('#repository-org_name').parents(".form-group").addClass('has-error');
                $('#repository-org_name').parents(".form-group").find('.help-block-error').text('组织架构不能为空');
                return false;
            }

            $('#repository-org_name').parents(".form-group").remove('has-error');
            $('#repository-org_name').parents(".form-group").find('.help-block-error').text('');
            $('#repository-form').submit();
        });
    }
};
