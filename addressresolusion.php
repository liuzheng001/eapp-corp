<?php
/**
 * Created by PhpStorm.
 * User: liuzheng
 * Date: 2019/5/4
 * Time: 12:03 PM
 * 通过经纬度解析地址,钉钉e应用crm使用
 */
//php可以通过echo返回js代码,让浏览器执行
$long =$_REQUEST['long'] ;
$lat = $_REQUEST['lat'];
/*echo ('
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=6YIT5WMqe5FCmUDgPaBCQdRv"></script>
</head>
<body>
	<div id="allmap"></div>
</body>
</html>
<script type="text/javascript">
	// 百度地图API功能
	var point = new BMap.Point('
.$long.
    ','.
$lat.
    ');
	var geoc = new BMap.Geocoder();

        geoc.getLocation(point, function(rs){
            var addComp = rs.addressComponents;
            alert(addComp.province + ", " + addComp.city + ", " + addComp.district + ", " + addComp.street + ", " + addComp.streetNumber);
        });
  
</script>');*/

echo ('
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<style type="text/css">
    body, html{width: 100%;height: 100%;margin:0;font-family:"微软雅黑";font-size:14px;}
		#allmap {width:100%;height:500px;}
	</style>
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=6YIT5WMqe5FCmUDgPaBCQdRv"></script>
	<title>逆地址解析</title>
</head>
<body>
	<div id="allmap"></div>
	<p>点击地图展示详细地址</p>
</body>
</html>
<script type="text/javascript">
	// 百度地图API功能
	var map = new BMap.Map("allmap");
	var point = new BMap.Point('
    .$long.
    ','.
    $lat.
    ');
	map.centerAndZoom(point,18);
	var geoc = new BMap.Geocoder();

	map.addEventListener("click", function(e){
        var pt = e.point;
        geoc.getLocation(pt, function(rs){
            var addComp = rs.addressComponents;
            alert(addComp.province + ", " + addComp.city + ", " + addComp.district + ", " + addComp.street + ", " + addComp.streetNumber);
        });
    });
</script>');