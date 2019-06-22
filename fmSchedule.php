<?php

header("Access-Control-Allow-Origin: *");
header('content-type:text/html;charset=utf8');

error_reporting(1);
ini_set('display_errors', true );

include_once ('api/RestApiFM.php');


if (!isset ($_REQUEST['action'])) $_REQUEST['action']='';
$result = array();

$host = 'liuzheng750417.imwork.net:442';
$db = '日程方案';
$layout = '日程表';
$user = '刘正';
$pass = '030528';
$fm = new fmREST ($host, $db, $user, $pass, $layout);


if  ($_REQUEST['action'] == 'scheduleContent') {  //编辑日程,日志,多媒体
    //
    /*"query":[
   {"Group": "=Surgeon"},
   {"Work State" : "NY", "omit" : "true"}],
 "sort":[
   {"fieldName": "Work State","sortOrder": "ascend"},
   {"fieldName": "First Name", "sortOrder": "ascend"} ]*/

    $events = $_REQUEST['detailed'];  //日程内容
    $dailyContent = $_REQUEST['dailyContent']; //日志内容
    $medias = $_REQUEST['medias'];
    $scheduleID = $_REQUEST['scheduleID'];

    //查找日程ID的记录
    $request1['日程ID'] = "==" . $eventID;
    $query = array($request1);
    $data['query'] = $query;
    $result = $fmSchedule->findRecords($data);
    if ($result['messages'][0]['code'] == 0) {
        //得到记录的记录标识ID,fm自动分配的,在方案中通过计算字段得到,修改时要用
        $recordContent = $result['response']['data'][0];
        $recordId = $recordContent['fieldData']['recordid'];

        //edit record
        $record['签到地址'] = $address;
        $record['签到时间'] = $signTime;
        $record['经度'] = $jingdu;
        $record['纬度'] = $weidu;

        $data = [];
        $data['fieldData'] = $record;

        $result = $fmSchedule->editRecord($recordId, $data);

        if ($result['messages'][0]['code'] == 0) {
            $returnResult = array('success' => true, 'content' => array('response' => array('data' => '上传成功')));
        } elseif ($result['messages'][0]['code'] == "401") {
            $returnResult = array('success' => true, 'content' => array('response' => array('data' => '记录丢失')));
        } else {
            $returnResult = array('success' => false, 'error' => ['errorCode' => $result['messages'][0]['code'], 'errorMsg' => $result['messages'][0]['message']]);
        }
        echo json_encode($returnResult, JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(array('success' => false, 'content' => array('response' => array('data' => '上传失败'))), JSON_UNESCAPED_SLASHES);

    }
}
elseif ($_REQUEST['action'] == 'getSchedule'){
    $scheduleID = $_REQUEST['scheduleID'];

}
/*else{

}*/


