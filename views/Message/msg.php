<?php
echo "<h3>我不是在控制器视图目录下，而是以应用主体的视图目录下</h3>";
echo "Hello";
echo "Current timestamp: ".time();
$this->registerJs("$('button.act').on('click',function(){
                $.get('/index.php?r=site/ajax',function(data){
			//	$.get('/index.php?r=site/partial',function(data){
					console.log(data,['data包含2什么？']);
				});
			});");
?>
<table width="80%">
  <tr>
  		<th>第一列的名字</th><th>第二列的名字</th><th>第三列的名字</th>
  </tr>
  <tr data-key="10308">
  		<td>河北大学第1食堂三楼餐厅</td><td>800100</td><td><button class="act">测试AJax</button></td>
  </tr>
  <tr data-key="10308">
  		<td>河南大学第二食堂2楼餐厅</td><td>800131</td><td><button class="act">测试AJax</button></td>
  </tr>
  <tr data-key="10308">
  		<td>东南大学第二食堂三楼餐厅</td><td>800131</td><td><button class="act">测试AJax</button></td>
  </tr>
</table>

<script type="text/javascript" src="static/js/yz_stock_in.js"></script>
<script type="text/javascript">
	yz_stock._bind();	

</script>

