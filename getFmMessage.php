<?php

header("Access-Control-Allow-Origin: *");
header('content-type:text/html;charset=utf8');

error_reporting(1);
ini_set('display_errors', true );

include_once ('api/RestApiFM.php');


if (!isset ($_REQUEST['action'])) $_REQUEST['action']='';
$result = array();

$host = 'liuzheng750417.imwork.net:442';
$db = '销售管理';
$layout = '客户列表';
$user = '刘正';
$pass = '030528';
$fm = new fmREST ($host, $db, $user, $pass, $layout);


if  ($_REQUEST['action'] == 'getcustomlist') {
    /*"query":[
   {"Group": "=Surgeon"},
   {"Work State" : "NY", "omit" : "true"}],
 "sort":[
   {"fieldName": "Work State","sortOrder": "ascend"},
   {"fieldName": "First Name", "sortOrder": "ascend"} ]*/

    //查找日程ID的记录
    $request1['公司'] = "*";//所有公司
    $query = array ($request1);
    $data['query'] = $query;
    $data['limit'] = 1000;
    $result = $fm -> findRecords ($data);
    if($result['messages'][0]['code'] == 0 ){

        $resultData= array();
        foreach ($result['response']['data'] as $val) {
            $resultData[]= array('name'=>$val['fieldData']['公司']);
        }
        $returnResult = array('success' => true, 'content' => array('data' => $resultData));

    } elseif ( $result['messages'][0]['code'] == "401") {
        $returnResult = array('success'=>true,'content'=>array());
    }
    else{
        $returnResult = array('success'=> false,'error'=> ['errorCode' => $result['messages'][0]['code'] ,'errorMsg' =>$result['messages'][0]['message']]);

    }
    echo json_encode($returnResult,JSON_UNESCAPED_SLASHES);
} elseif ($_REQUEST['action'] == 'login') {
    //login
    $result = $fm -> login ();
    echo $result;
} elseif ($_REQUEST['action'] == 'logout') {
    //logout
    $result = $fm -> logout ();
}


