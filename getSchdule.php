<?php
/**
 * Created by PhpStorm.
 * User: liuzheng
 * Date: 2019/6/22
 * Time: 9:54 AM
 * copy corp_php-master文件
 */

header("Access-Control-Allow-Origin: *");
header('content-type:text/html;charset=utf8');

error_reporting(1);
ini_set('display_errors', true );

include_once ('api/RestApiFM.php');


if (!isset ($_REQUEST['action'])) $_REQUEST['action']='';
$result = array();


//$debug=1;


$host = 'liuzheng750417.imwork.net:442';

$db = '日程方案';
//$layout = '日程表';
$user = '刘正';
$pass = '030528';
//$fmSchedule = new fmREST ($host, $db, $user, $pass, $layout);


if  ($_REQUEST['action'] ==  'get_schedule_list_month') {

    /*"query":[
    {"Group": "=Surgeon"},
    {"Work State" : "NY", "omit" : "true"}],
  "sort":[
    {"fieldName": "Work State","sortOrder": "ascend"},
    {"fieldName": "First Name", "sortOrder": "ascend"} ]*/

    //得到year和month的,useId的所有日程,并排序
//    $request1['userId'] = $_REQUEST['userId'];
    $request1['日历表::belong'] = "==".$_REQUEST['username'];
    $request1['日历表::year'] = $_REQUEST['year'];
    $request1['日历表::month'] = $_REQUEST['month'];

    $query = array ($request1);
    $data['query'] = $query;
    $data['limit'] = 200;
    $sort1 = array('fieldName'=>'签到时间','sortOrder'=>"ascend");
    $allSort = array($sort1);
    $data['sort'] = $allSort;
    /*$sort1 = array ('fieldName'=>'日历日期','sortOrder'=>'descend');
    $data['sort'] = $sort1;*/

    $fmSchedule = new fmREST ($host, $db, $user, $pass, "日程表");
    $result = $fmSchedule -> findRecords ($data);
//    echo json_encode( $result);

//    return数据格式
    /*success: response.success,
      content: response.content,
      error: {
        errorMsg: response.errorMsg,
        errorCode: response.errorCode,
        errorLevel: response.errorLevel,
    $returnResult  ;*/
    if($result['messages'][0]['code'] === '0' ){
        $returnResult = array('success'=>true,'content'=>$result['response']['data']);
    } elseif ( $result['messages'][0]['code'] == "401") {
        $returnResult = array('success'=>true,'content'=>array());
    }
    else{
        $returnResult = array('success'=> false,'error'=> ['errorCode' => $result['messages'][0]['code'] ,'errorMsg' =>$result['messages'][0]['message']]);

    }
    echo json_encode($returnResult,JSON_UNESCAPED_SLASHES);
    $fmSchedule->logout();

} elseif ($_REQUEST['action'] == 'updateSignIn') {
    $eventID = $_REQUEST['eventID'];
    $signTime= $_REQUEST['signTime'] ;
    $jingdu = $_REQUEST['jingdu'];
    $weidu = $_REQUEST['weidu'] ;
    $tripMode = $_REQUEST['tripMode'];
    $address = $_REQUEST['address'];

    //查找日程ID的记录
    $request1['日程ID'] = "==".$eventID;
    $query = array ($request1);
    $data['query'] = $query;

    $fmSchedule = new fmREST ($host, $db, $user, $pass, "日程表");
    $result = $fmSchedule -> findRecords ($data);
    if($result['messages'][0]['code'] === '0' ){
        //得到记录的记录标识ID,fm自动分配的,在方案中通过计算字段得到,修改时要用
        $recordContent = $result['response']['data'][0];
        $recordId = $recordContent['fieldData']['recordid'];

        //edit record
        $record['签到地址'] =$address;
        $record['签到时间'] = $signTime;
        $record['经度'] =$jingdu;
        $record['纬度'] =$weidu;
        $record['到达方式'] = $tripMode;

        $data = [];
        $data['fieldData'] =  $record;

        $result = $fmSchedule -> editRecord ($recordId, $data);

        if($result['messages'][0]['code'] === '0' ){
            $returnResult = array('success'=>true,'content'=>array('response'=>array('data'=>'上传成功')));
        }elseif ( $result['messages'][0]['code'] == "401") {
            $returnResult = array('success'=>true,'content'=>array('response'=>array('data'=>'记录丢失')));
        }
        else{
            $returnResult = array('success'=> false,'error'=> ['errorCode' => $result['messages'][0]['code'] ,'errorMsg' =>$result['messages'][0]['message']]);
        }
        echo json_encode($returnResult,JSON_UNESCAPED_SLASHES);
    }else{
        echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>'上传失败'))),JSON_UNESCAPED_SLASHES);

    }
}
//上传出勤地址
elseif ($_REQUEST['action'] == 'attendanceUpdate') {
    //将数据写入fm数据库
    //需要日历外外键ID,从前端得到
    /* $sign = MyEventModel::getInstance();

     if (!$_POST['calendarID'] or !$_POST['jingdu'] or !$_POST['weidu']){
         $this->ajaxReturn('出勤标注失败', 'failure', 1);
     }
     $data['日历ID']= $_POST['calendarID'];

 //        $_POST['jingdu']? $data['经度'] =$_POST['jingdu']:null;
 //        $_POST['weidu']?$data['纬度'] = $_POST['weidu']:null;
 //        $_POST['signTime']?$data['签到时间'] =date($_POST['signTime']):null;
     //  $booking->setField('arrival_date', date ( "n/j/Y H:i:s A" ) );

     $timestamp = date('m/d/Y h:i:s A', time());

     $type = $_POST['type'];
     if ($type == '出勤'){
         $data['出勤类型'] = $_POST['chuqiType'];
         $data['出勤经度'] =$_POST['jingdu'];
         $data['出勤纬度'] = $_POST['weidu'];
         $data['出勤时间戳'] = $timestamp;
     }else{
         $data['收工经度'] =$_POST['jingdu'];
         $data['收工纬度'] = $_POST['weidu'];
         $data['收工时间戳'] =   $timestamp;
     }



     $result = $sign->modifySignStatus($data);
     if($result == "success"){
         $returnData['经度'] = $_POST['jingdu'];
         $returnData['纬度'] = $_POST['weidu'];
         $returnData['时间戳'] = $timestamp;

         $this->ajaxReturn($returnData
             ,'success',0);
     }else {
         $this->ajaxReturn($result, 'failure', 2);
     }*/

    //出勤或收工
    $type = $_REQUEST['chuqiType'];
    $tripMode = $_REQUEST['AttendanceMode'];

    $timestamp = date('m/d/Y h:i:s A', time());


    //查找日程ID的记录
    $request1['日历外键ID'] = "==".$_REQUEST['calendarID'];
    $query = array ($request1);
    $data['query'] = $query;

    $fmSchedule = new fmREST ($host, $db, $user, $pass, "日程表");
    $result = $fmSchedule -> findRecords ($data);
    if($result['messages'][0]['code'] === '0' ){
        //得到记录的记录标识ID,fm自动分配的,在方案中通过计算字段得到,修改时要用
        $recordContent = $result['response']['data'][0];
        $recordId = $recordContent['fieldData']['recordid'];

        //edit record
        if ($type == '出勤'){
//            $record['日历表::出勤类型'] = $AttendanceMode;
            $record['日历表::出勤经度'] =$_REQUEST['jingdu'];
            $record['日历表::出勤纬度'] = $_REQUEST['weidu'];
            $record['日历表::出勤时间戳'] = $timestamp;
        }else{
            $record['日历表::收工经度'] =$_REQUEST['jingdu'];
            $record['日历表::收工纬度'] = $_REQUEST['weidu'];
            $record['日历表::收工时间戳'] =   $timestamp;
            $record['日历表::收工到达方式'] =   $tripMode;
        }



        $data = [];
        $data['fieldData'] =  $record;

        $result = $fmSchedule -> editRecord ($recordId, $data);

        if($result['messages'][0]['code'] === '0' ){
            $returnResult = array('success'=>true,'content'=>array('response'=>array('data'=>'上传成功')));
        }elseif ( $result['messages'][0]['code'] == "401") {
            $returnResult = array('success'=>true,'content'=>array('response'=>array('data'=>'记录丢失')));
        }
        else{
            $returnResult = array('success'=> false,'error'=> ['errorCode' => $result['messages'][0]['code'] ,'errorMsg' =>$result['messages'][0]['message']]);
        }
        echo json_encode($returnResult,JSON_UNESCAPED_SLASHES);
    }
    //查找calendarID失败时
    else{
        echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>'上传失败'))),JSON_UNESCAPED_SLASHES);

    }

} elseif ($_REQUEST['action']==="getschedule") {  //得到某天的日程和签到信息,未使用

    /*$host = 'liuzheng750417.imwork.net:442';
//    $host = 'filemaker.ckkj.net.cn:442';
    $db = '日程方案';
    $layout = '日历详情';
    $user = '刘正';
    $pass = '030528';*/
    $scheduleID = $_REQUEST['scheduleID'];

    $fm = new fmREST ($host, $db, $user, $pass, '日历详情');
    if($scheduleID!==""){ //为空字符串,代表没有ID,需建立新日历记录
        //查找日历ID的记录
        $request1['日历ID'] = "==".$scheduleID;
        $query = array ($request1);
        $data['query'] = $query;
        $result = $fm -> findRecords ($data);

        if($result['messages'][0]['code'] === '0' ) { //读取正常
            $resultData = $result['response']['data'][0];

            $dailyContent = $resultData['fieldData']['日志内容']; //日志内容

            $detailed = []; //日程内容

            foreach ($resultData['portalData']['portalevents'] as $item) {
                $event['signTime'] = $item['日程表::签到时间'];
                $event['eventID'] = $item['日程表::日程ID'];
                $event['event'] = $item['日程表::日程内容'];
                $event['signAddress'] = $item['日程表::签到地址'];
                $detailed[] = $event;
            }

            $medias = [];//媒体内容
            foreach ($resultData['portalData']['媒体表'] as $item) {
                $media['src'] = $item['媒体表::媒体容器'];
                $medias[] = $media;
            }
            $returnResult = array('success'=>true,'content'=>array('response'=>array('data'=> array("detailed"=>$detailed,"dailyContent"=>$dailyContent,"medias"=>$medias))));
            echo json_encode($returnResult, JSON_UNESCAPED_SLASHES);

        } else{
            echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>"失败code:".$result['messages'][0]['code']))),JSON_UNESCAPED_SLASHES);

        }
    }else{ //建立新日历记录
        echo "没有这个日历";
        exit;
    }
}
elseif($_REQUEST['action'] == 'updateSchedule'){  //编辑日历详情
    //通过scheduleId查询日历,得到日志集
    //前端数据遍历,无eventID,则新建;有eventID,则更新,有删除标志则删除
    $fm = new fmREST ($host, $db, $user, $pass, "日历详情");
    $fmSchedule = new fmREST ($host, $db, $user, $pass, "日程表");

    $newEvents = json_decode($_REQUEST['events'],true);
    $scheduleID = $_REQUEST['scheduleID'];
//分离到日程更新中操作    $scheduleContent = $_REQUEST['scheduleContent'];
    $isDelSchedule = $_REQUEST['isDelSchedule'];

    if ($scheduleID != '0') {  //已有日历
        //更新日志内容,布局使用日历详情
        /*$host = 'liuzheng750417.imwork.net:442';
        $db = '日程方案';
        $layout = '日历详情';
        $user = '刘正';
        $pass = '030528';*/
//        $fm = new fmREST ($host, $db, $user, $pass, "日历详情");

        $request1['日历ID'] = $scheduleID;

        $query = array ($request1);
        $data['query'] = $query;

        $result = $fm -> findRecords ($data);
        $data = [];$request1=[];

        if ($result['messages'][0]['code'] !== '0') {
            echo json_encode(array('success' => false, 'content' => array('response' => array('data' => "失败code:" . $result['messages'][0]['code']))), JSON_UNESCAPED_SLASHES);
            return;
        }
        /*echo json_encode($result,JSON_UNESCAPED_SLASHES);
        exit;*/

        $recordId = $result['response']['data'][0]['recordId'];

        //若前端有删除schedule标志,则删除;而且该表与日程连接表慕本之间可以删除,则将直接删除events,不需要再通过查找删除events,因此若正确,则返回
        if ($isDelSchedule === "true") {
            $result = $fm->deleteRecord($recordId);
            if ($result['messages'][0]['code'] !== '0') {
                echo json_encode(array('success' => false, 'content' => array('response' => array('data' => "失败code:" . $result['messages'][0]['code']))), JSON_UNESCAPED_SLASHES);
            }else{
                echo json_encode(array('success'=>true,'content'=>array('response'=>array('data'=>"成功"))),JSON_UNESCAPED_SLASHES);
                $fm->logout();
            }
            return;
        }
        /* //更新日志内容或其它

         $record['日志内容'] = $scheduleContent;

         $data['fieldData'] =  $record;

         $result = $fm -> editRecord ($recordId, $data);
 //        $fm->logout();

         $data=[];$record=[];
         if ($result['messages'][0]['code'] !== '0'){
             echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>"失败code:".$result['messages'][0]['code']))),JSON_UNESCAPED_SLASHES);
             return;
         }*/


        //更新日程表
        $request1['日历外键ID'] = $scheduleID;

        $query = array ($request1);
        $data['query'] = $query;

//        $fmSchedule = new fmREST ($host, $db, $user, $pass, "日程表");

        $result = $fmSchedule -> findRecords ($data);

        $data = [];$request1=[];

        $oldEvents = $result['response']['data'];
        if ($result['messages'][0]['code'] !== '0') {
            echo json_encode(array('success' => false, 'content' => array('response' => array('data' => "失败code:" . $result['messages'][0]['code']))), JSON_UNESCAPED_SLASHES);
            return;
        }
        //比较新旧events,更新日志
        /*echo json_encode($newEvents,JSON_UNESCAPED_SLASHES);
        echo json_encode($oldEvents,JSON_UNESCAPED_SLASHES);
        exit;*/
        foreach ($newEvents as $event) {
            foreach ($oldEvents as $oldEvent) {
                if ($event['eventID'] == '0') {  //约定eventID=0代表新建
                    $record['日程内容'] =$event['event'];
                    $record['签到地址'] =$event['signAddress'];
                    $record['签到时间'] =$event['signTime'];
                    $record['经度'] =$event['lat']?$event['lat']:"";
                    $record['纬度'] =$event['lang']?$event['lat']:"";
                    $record['日历外键ID'] =$scheduleID;

                    $data['fieldData'] =  $record;

                    $result = $fmSchedule -> createRecord ($data);
                    $record = [];$data=[];
                    if ($result['messages'][0]['code'] !== '0'){
                        echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>"失败code:".$result['messages'][0]['code']))),JSON_UNESCAPED_SLASHES);
                        return;
                    }
                    break;
                }
                if ($event['eventID'] === $oldEvent['fieldData']['日程ID']) { //update或delete

                    if ($event['isDelete']) {  //delete
                        $recordId = $oldEvent['recordId'];
                        $result = $fmSchedule->deleteRecord($recordId);
                        if ($result['messages'][0]['code'] !== '0') {
                            echo json_encode(array('success' => false, 'content' => array('response' => array('data' => "失败code:" . $result['messages'][0]['code']))), JSON_UNESCAPED_SLASHES);
                            return;
                        }

                    } else { //update

                        $recordId = $oldEvent['recordId'];

                        $record['日程内容'] = $event['event'];
//                        $record['签到地址'] = $event['signAddress'];
//                        $record['签到时间'] = $event['signTime'];
//                        $record['经度'] =$event['lat']?$event['lat']:"";
//                        $record['纬度'] =$event['lang']?$event['lat']:"";

                        $data['fieldData'] = $record;
                        $result = $fmSchedule->editRecord($recordId, $data);
                        $data = [];
                        $record = [];
                        if ($result['messages'][0]['code'] !== '0') {
                            echo json_encode(array('success' => false, 'content' => array('response' => array('data' => "失败code:" . $result['messages'][0]['code']))), JSON_UNESCAPED_SLASHES);
                            return;
                        }
                    }
                    break;
                }
            }
        }
    } else {
        //新建日历
        /* $host = 'liuzheng750417.imwork.net:442';
         $db = '日程方案';
         $layout = '日历详情';
         $user = '刘正';
         $pass = '030528';*/
//        $fm = new fmREST ($host, $db, $user, $pass, "日历详情");

        $record['belong'] = $_REQUEST['username'];
        $record['日历日期'] = $_REQUEST['scheduleDate'];

        $data['fieldData'] = $record;


        $result = $fm -> createRecord ($data);
        if ($result['messages'][0]['code'] !== '0'){
            echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>"新建日历失败code:".$result['messages'][0]['code']))),JSON_UNESCAPED_SLASHES);
            return;
        }
        $data=[];$record=[];

        $recordId = $result['response']['recordId'];

        $result = $fm->getRecord($recordId);
//        $fm->layout();

        if ($result['messages'][0]['code'] !== '0'){
            echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>"获取scheduleID,失败code:".$result['messages'][0]['code']))),JSON_UNESCAPED_SLASHES);
            return;
        }
        $scheduleID = $result['response']['data'][0]['fieldData']['日历ID'];
        //建立日程
        foreach ($newEvents as $event) { //建立日程
            $record['日程内容'] =$event['event'];
            $record['签到地址'] =$event['signAddress'];
            $record['签到时间'] =$event['signTime'];
            $record['经度'] =$event['lat']?$event['lat']:"";
            $record['纬度'] =$event['lang']?$event['lat']:"";
            $record['日历外键ID'] =$scheduleID;

            $data['fieldData'] =  $record;

//                $fmSchedule = new fmREST ($host, $db, $user, $pass, '日程表');

            $result = $fmSchedule -> createRecord ($data);
            $record = [];$data=[];
            if ($result['messages'][0]['code'] !== '0'){
                echo json_encode(array('success'=>false,'content'=>array('response'=>array('data'=>"失败code:".$result['messages'][0]['code']))),JSON_UNESCAPED_SLASHES);
                return;
            }
        }
    }
    echo json_encode(array('success'=>true,'content'=>array('response'=>array('data'=>"成功"))),JSON_UNESCAPED_SLASHES);
    $fm->logout();$fmSchedule->logout();
}

elseif ($_REQUEST['action'] == 'getDailyRecord'){
    $fm = new fmREST ($host, $db, $user, $pass, "日历详情");
    $scheduleID = $_REQUEST['scheduleID'];

    $request1['日历ID'] = $scheduleID;
    $query = array ($request1);
    $data['query'] = $query;

    $result = $fm -> findRecords ($data);
    $data = [];$request1=[];
    /*var_dump($result);
    exit;*/

    if ($result['messages'][0]['code'] !== '0') {
        echo json_encode(array('success' => false, 'content' => "失败code:" . $result['messages'][0]['code']), JSON_UNESCAPED_SLASHES);
        return;
    }

    $record = $result['response']['data'][0];
    $dailyContent = $record['fieldData']['日志内容'];
    $dailyQuestion= $record['fieldData']['问题'];
    $dailyReply = $record['fieldData']['回复'];
    echo json_encode(array('success'=>true,'content'=>array('dailyContent'=>$dailyContent,'dailyQuestion'=>$dailyQuestion,'dailyReply'=>$dailyReply)),JSON_UNESCAPED_SLASHES);
    $fm->logout();
}

elseif ($_REQUEST['action'] == 'updateDailyRecord'){
    $fm = new fmREST ($host, $db, $user, $pass, "日历详情");
    $scheduleID = $_REQUEST['scheduleID'];
    $dailyContent = $_REQUEST['dailyContent'];
    $dailyQuestion= $_REQUEST['dailyQuestion'];
    $dailyReply = $_REQUEST['dailyReply'];

    $request1['日历ID'] = $scheduleID;
    $query = array ($request1);
    $data['query'] = $query;

    $result = $fm -> findRecords ($data);
    $data = [];$request1=[];

    $recordId = $result['response']['data'][0]['recordId'];
    /*var_dump($result);
    exit;*/
    //更新日志内容或其它

    $record['日志内容'] = $dailyContent;
    $record['问题'] = $dailyQuestion;
    $record['回复'] = $dailyReply;
    $data['fieldData'] =  $record;

    $result = $fm -> editRecord ($recordId, $data);
    /*var_dump($result);
    exit;*/

    $data=[];$record=[];
    if ($result['messages'][0]['code'] !== '0'){
        echo json_encode(array('success'=>false,'content'=>'日志更新失败' . $result['messages'][0]['code']),JSON_UNESCAPED_SLASHES);
        return;
    }

    echo json_encode(array('success'=>true,'content'=>'日志更新成功'),JSON_UNESCAPED_SLASHES);
    $fm->logout();
}

elseif ($_REQUEST['action'] == 'login') {
    //login
    $result = $fmSchedule -> login ();
//    var_dump( $result);
}

elseif ($_REQUEST['action'] == 'logout') {
    //logout
    $result = $fmSchedule -> logout ();
//    var_dump( $result);

}


