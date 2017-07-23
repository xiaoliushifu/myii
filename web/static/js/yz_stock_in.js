var yz_stock = {
	
		/*入库操作开始*/
		  //绑定入库类型的选择事件
		    _stockin_type_bind: function () {
		    	var self = this;
		    	//如果是通过点击浏览器的（回退|前进)按钮来到本页面的
		    	$('#multi-form-test')[0].reset();
		    	$('#stockin-stockin_type').change(function(){
		    		var item = $(this).val();
		    		if(item==1) {
		    			//采购
			    		
		    			
		    			$("div[class*=field-stockin]").removeClass('hidden');
		    			$("div[class*=field-datetimepicker_st]").removeClass('hidden');
		    			
		    			$("div[class*=field-stockin-stockout]").addClass('hidden');
		    			$("div[class*=field-stockout]").addClass('hidden');
		    			
		    		}else if(item==2){
		    			//返厂维修换新
		    			$("div[class*=field-stockin-stockout]").removeClass('hidden');
		    			$("div[class*=field-stockout]").removeClass('hidden');
		    			
		        		$("div[class*=field-stockin-sku_name]").addClass('hidden');
		        		$("div[class*=field-stockin-category_primary_id]").addClass('hidden');
		        		$("div[class*=field-stockin-provider_short_name]").addClass('hidden');
		        		$("div[class*=field-stockin-brand]").addClass('hidden');
		        		$("div[class*=field-stockin_attachment]").addClass('hidden');
		        		$("div[class*=field-stockin_display]").addClass('hidden');
		        		$("div[class*=field-stockin-serial_code]").addClass('hidden');
		        		$("div[class*=field-stockin_table]").addClass('hidden');
		        		$("div[class*=field-datetimepicker_st]").addClass('hidden');
		    		} else {
		    			//隐藏
		        		
		        		//清空
		        		$('#multi-form-test')[0].reset();
		        		$("div[class*=field-stockin]").removeClass('hidden');
		        		$("div[class*=field-stockin-stockout]").addClass('hidden');
		        		
		        		$("div[class*=field-datetimepicker_st]").removeClass('hidden');
		        		$("div[class*=field-stockin-stockin_type]").removeClass('hidden');
		    		}
		    	});
		    },
		
		
    /**更新汇总的设备数量*/
    _update_equipment_num: function () {
    	//获得
        var equipmentNum = $('table.tablelist').find('.check_serial_code');
        //更新
        $('div.serial_code_count').find('span').html(equipmentNum.length);
    },
    /*添加商品序列号有误时 展示错误信息*/
    _show_error: function (obj, msg) {
    	//开启错误格式
        $(obj).parents(".form-group").addClass('has-error');
        //放置错误信息在下方
        $(obj).next('div').html(msg);
    },
    //格式化重复的序列号，避免太长
    _format_error:function(arr){
    	errMsg='';
    	while(arr.length>10) {
    		errMsg +=arr.splice(0,10).join(',')+"<BR>";
		}
    	return errMsg +=arr.splice(0,10).join(",");
    },
    
  /*关闭错误*/
    _hidden_error: function (obj) {
    	//关闭错误格式
        $(obj).parents(".form-group").removeClass('has-error');
        //清空错误信息
        $(obj).next('div').text('');
    },
    
  /*检测该设备是否已添加到展示列表里,不要重复添加
   * 有一定难度
   * */
    _check_added_goods: function (serialCode) {
    	var allInput=[];
    	var exceptArr=[];
    	//获取已经添加到列表里的所有的序列号并转换成数组
        var serialCodeObjs = $('table.tablelist').find('input.check_serial_code');
        $.each(serialCodeObjs,function(k,v){
        	allInput.push(v.getAttribute('value'));
        });
        //列表还没有添加
        if(allInput.length==0) {
        	return serialCode;
        }
        //遍历serialCode,不在列表里的都留下
        $.each(serialCode, function (k, v) {
        	if($.inArray(v,allInput) == -1) {
        		exceptArr.push(v);
        	}
        });
        return exceptArr;
    },
    //从页面的列表里删除已添加商品
    _delete_goods: function () {
    	var self = this;
        $('table.tablelist').on('click', '.delete_goods', function () {
            $(this).parents('td').remove();
             //更新设备展示数量
            self._update_equipment_num();
        });
    },
    
    //新建入库单页的添加操作
    _add_stockin_goods: function () {
        var self = this;
        var arr=[];
        var errMsg='';
        formName='Stockin';
        $('#add_serial_btn').on('click', function () {
            //收集用户输入数据
            var codeObj = $('#stockin-serial_code');
            var serialCode = codeObj.val().trim();
            //检查: 设备序列号是否填写
            if  ('' == serialCode) {
                self._show_error(codeObj, '请填写商品序列号');
                return false;
            }
            //拆分（filter过滤）
            arr = serialCode.split("\n").filter(function(x){return x;});
            //本次添加的数据自身是否有重复(trim)
            res = self.__check_repeat(arr);
            if(res.length != arr.length) {
            	//记录有错，不立即展示
            	errMsg='入库商品序列号有重复，系统已帮您过滤';
            	arr=res;
            }
            
            //序列号是否字母和数字组合
            if (!self._check_alnum(arr) ) {
            	self._show_error(codeObj, '商品序列号输入不符合规范（只允许字母+数字)');
            	return;
            }
            
            res = self._check_added_goods(arr);
            //本次添加的是否和列表展示区里的有重复
            if(res.length != arr.length) {
            	errMsg='入库商品序列号有重复，系统已帮您过滤';
                arr=res;
            }
            //有可能都过滤了
            if(arr.length==0) {
            	self._show_error(codeObj, errMsg);
            	return;
            }
            //请求: 查询设备明细兼检测与库中设备是否重复
            var toUrl = '/yz/stockin/sku-detail';
            var sendContent = {sc:arr,rpid:$('input.nothing').val()}
            //ajax获取
            $.post(toUrl, sendContent, function (data) {
                if (data.hasOwnProperty("code")) {
                	//说明有已经在库的设备
                	if (data.code == 1) {
                		self._show_error(codeObj, "入库商品序列号: "+self._format_error(data.data)+"之前已入库，添加失败");
                		self._show_stockin_serial_code(data.ok, formName);
                		self._update_equipment_num();
                		return;
                	}
                	if (data.code == 2) {
                		self._show_error(codeObj, "入库商品序列号: "+self._format_error(data.data)+"之前已入库，添加失败");
                		self._update_equipment_num();
                		return;
                	}
                    //尚未在库，就渲染页面
                    self._show_stockin_serial_code(arr, formName);
                    
                    codeObj.val('');//clear the textarea
                    //更新设备展示数量
                    self._update_equipment_num();
                    self._show_error(codeObj,errMsg);
                    errMsg='';
                } else {
                    self._show_error(codeObj, "设备序列号有误");
                }
            },'json');
            
        });
    },
    
    _show_stockin_serial_code: function (data, formName) {
    	n=5;
    	var tdNum=0;
    	for(i in data) {
    		tdNum=$('table.tablelist').find('tr:last').find('td').length;
    		serialCode = data[i];
	    	if(tdNum%n == 0) {
	    		//生成一个<tr><td>xx</td></tr>
	    		var _html  = '<tr>';
	            _html += '<td>' + serialCode;
	            _html += '<a href="javascript:;" class="delete_goods"><i title="删除">X</i></a>';
	            //增加冗余方便检查
	            _html += '<input type="hidden" name="'+formName+'[stockin_detail][serial_code][]" class="check_serial_code" value="'+serialCode+'" />';
	            _html += '</td></tr>';
	            $('table.tablelist').find('tbody').append(_html);
	    	}else{
	    	//一个<td>xx</td>即可
	    		var _html  = '<td>';
	            _html += serialCode;
	            _html += '<a href="javascript:;" class="delete_goods"><i title="删除">X</i></a>';
	            //增加冗余方便检查
	            _html += '<input type="hidden" name="'+formName+'[stockin_detail][serial_code][]" class="check_serial_code" value="'+serialCode+'" /></td>';
	            $('table.tablelist').find('tr:last').append(_html);
	    	}
    	}
    },
    //检测本次输入数据是否有重复
    __check_repeat:function(arr) {
    	//先排序，然后比较紧邻的两个元素
    	arr.sort();
    	arr[0]=$.trim(arr[0]);
		var re=[arr[0]];
		for(var i = 1; i < arr.length; i++) {
			arr[i] = $.trim(arr[i]);
			if ( arr[i] !== re[re.length-1]) {
				re.push(arr[i]);
			}
		}
		return re;
    },
    //判断字符串是否为数字和字母组合
    _check_alnum:function(arr) {
    	var re =  /^[0-9a-zA-Z]*$/;
    	for(i in arr) {
    		if (!re.test(arr[i]))  {  
	   	        return false;  
    		}
    	}
    	return true;
    },
    /*****/
    /*表单项提交前的几个特殊验证 其他交给ActiveForm*/
    _before_submit:function()  {
    	var self=this;
    	$('#multi-form-test').on('submit',function(){
    		option = $('#stockin-stockin_type').val();
    		if(option=='') {
    			return;
    		}
    		if (option=="1") {
    			return self._purchase();
    		}
    		if (option=="2") {
    			return self._repair();
    		}
    		return true;
    	});
    },
    _stockin_init:function(){
	    	//当前页面操作初始化
    	this._stockin_type_bind();
    	this._add_stockin_goods();
    	this._delete_goods();
    	this._before_submit();
    	this._retract();
    },
    /*弹框提示*/
    _notice_dialog:function() {
    	//为什么第一次提交会弹窗两次？
    	if(window.d == undefined) {
	    	window.d = dialog({
	            title: '提示',
	            width: 350,
	            content:'存在“出库-返厂维修/换新”的商品还未变更状态，请根据实际维修情况及时更改',
	            //cancel: false,
	        	okValue: '知道了',
	        	skin: 'min-dialog tips',
	        	ok:function(){
	        		this.close().remove();
	        		window.d=null;
	        		return true;
	        	}
	        }).showModal();
    	}
    },
    /*采购类型认证*/
    _purchase:function() {
    	var self=this;
    	var trs = $('div.field-stockin_table').find('tbody').children();
		if (trs.length <= 0) {
			var codeObj = $('#stockin-serial_code');
    		self._show_error(codeObj,'还没有添加商品序列号');
    		return false;
		}
    },
    /*返厂维修/换新类型认证*/
    _repair:function() {
    	var self = this;
    	var arr = $('input[name*="[exchange_status]"]');
    	for(var i=0;i<arr.length;i++) {
    		if (arr[i].value == '') {
    			self._notice_dialog();
    			return false;
    		}
    	}
    	return true;
    },
    //收起返厂订单列表
    _retract:function(){
    	var obj = $('div.field-stockout_stockout_detail');
    	$('#retract').on('click',function() {
    		obj.toggleClass('hidden');
    		if (obj.hasClass('hidden')) {
    			$(this).text('展开');
    		} else {
    			$(this).text('收起');
    		}
    	});
    }
};
