<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/util/Log.php");
require_once(__DIR__ . "/util/Cache.php");
require_once(__DIR__ . "/crypto/DingtalkCrypt.php");


require_once (__DIR__."/api/RestApiFM.php");
require_once(__DIR__ . "/api/Auth.php");
require_once(__DIR__ . "/api/User.php");
require_once(__DIR__ . "/api/Department.php");

//设置服务器为北京时间
date_default_timezone_set('Asia/Shanghai');



$signature = $_GET["signature"];
$timeStamp = $_GET["timestamp"];
$nonce = $_GET["nonce"];
$postdata = file_get_contents("php://input");
$postList = json_decode($postdata,true);
$encrypt = $postList['encrypt'];
$msg = "";


/*
$signature ="7a907ae2bf3b669c2e8ec7e834bc768eea68582e";
$timeStamp ="1531221286703";
$nonce = "LYWNkLjw";
$postdata = file_get_contents("php://input");
$postList = json_decode($postdata,true);
$encrypt = "pen7TUPuxNQ7MHZHDsXZMGR4QOoneMHWW3RfRqXO2MnfHRL1yin5g0BThfwoyg8wZje64yL7FYJuj3Y9O69AJg==";
$msg = "";*/

/*{"msg_signature":"13a64f22ee5ec180646b5d2e51415c37ea45930a","encrypt":"aX3xSBv4A\/S1GoDqJBhhx+Kpm8W\/04APekUyPC0nih6KVigFMtsLIEpyiIn5jnA1BtmuVo4bKIlTWBMEAdflYA==","timeStamp":"1531221286703","nonce":"LYWNkLjw"}*/

/**
 * TOKEN, ENCODING_AES_KEY, CORPID配置在config文件中
 */
try {
    $crypt = new DingtalkCrypt(TOKEN, ENCODING_AES_KEY, CORPID);
    $errCode = $crypt->DecryptMsg($signature, $timeStamp, $nonce, $encrypt, $msg);
} catch (Exception $e) {
    Log::e("DecryptMsg Exception".$e->getMessage());
    print $e->getMessage();
    exit();
}
$eventMsg = json_decode($msg);
//var_dump($eventMsg);
$eventType = $eventMsg->EventType;
//调试
//$eventType = 'bpms_instance_change' ;

$auth = new Auth();
$user = new User();
$deplist = new Department();
//$host = 'r1w8478651.imwork.net:444';
$host = 'liuzheng750417.imwork.net:442';
$db = 'authtest';
$layout = 'authtest';
$username = '钉钉';
$pass = 'admin0422';
$fm = new fmREST ($host, $db, $username, $pass, $layout);

switch ($eventType){
    case "user_add_org":
        //通讯录用户增加 do something
        Log::i("【callback】:user_add_org_action".$msg);

        //通过$msg中的user_ID得到userinfo
        $accessToken = $auth->getAccessToken();
        $userId = $eventMsg->UserId[0];
        $userInfo = $user->get($accessToken, $userId);

         dataPreparation($userInfo,$fmRecord);
         $fmRecord['fieldData']['userId'] = $userId;

        $result = $fm->createRecord($fmRecord);
         $fm->resultJudge($result,'createRecord');
         $fm->logout();
        break;
    case "user_modify_org":

        //通讯录用户更改 do something
        Log::i("【callback】:user_modify_org_action".$msg);

        //通过$msg中的user_ID得到userinfo
        $accessToken = $auth->getAccessToken();
        $userId = $eventMsg->UserId[0];
        $userInfo = $user->get($accessToken, $userId);
//        Log::i("[get_userinfo]".json_encode($userInfo));

        //通过restApi中的findRecord,用钉钉ID->recordID(文件中的唯一ID),再editRecord ($recordID, $record),更新FM员工档案文件
        //find records
        $request1['userId'] = $userId;
        $query = array ($request1);
        $data['query'] = $query;
        $data['script'] = "delete portal roles";
        $result = $fm->findRecords($data);
        //fm操作判断
        if ($result['messages'][0]['code'] === '0'  ){
            //fm唯一记录值
            $recordId = $result['response']['data'][0]['recordId'];
            dataPreparation($userInfo,$fmRecord);
            $result = $fm->editRecord($recordId,$fmRecord);
            $fm->resultJudge($result,'editRecord');
        }
        else if($result['messages'][0]['code'] === '401' /*未找到,则新建记录*/ ){
            dataPreparation($userInfo,$fmRecord);
            $fmRecord['fieldData']['userId'] = $userId;
            $result = $fm->createRecord($fmRecord);
            $fm->resultJudge($result,'editRecord');

        }
        else{
            Log::i("【fm failure 】: operationType".$operationType."code".$result['messages'][0]['code']."msg:".$result['messages'][0]['message']);
            exit();
        }
        $fm->logout();

        break;
    case "user_leave_org":
        //通讯录用户离职  do something
        Log::i("【callback】:user_leave_org_action".$msg);

        $userId = $eventMsg->UserId[0];
//        Log::i("[get_userinfo]".json_encode($userInfo));

        //通过restApi中的findRecord,用钉钉ID->recordID(文件中的唯一ID),再editRecord ($recordID, $record),更新FM员工档案文件
        //find records
        $request1['userId'] = $userId;
        $query = array ($request1);
        $data['query'] = $query;

        $result = $fm->findRecords($data);
        if ($fm->resultJudge($result)) {
            $recordId = $result['response']['data'][0]['recordId'];
            $result = $fm->deleteRecord($recordId);
            $fm->resultJudge($result,'deleteRecord');
        }
        $fm->logout();

        break;
    case "org_admin_add":
        //通讯录用户被设为管理员 do something
        Log::i("【callback】:org_admin_add_action".$msg);
        break;
    case "org_admin_remove":
        //通讯录用户被取消设置管理员 do something
        Log::i("【callback】:org_admin_remove_action".$msg);
        break;
    case "org_dept_create":
        //通讯录企业部门创建 do something
        Log::i("【callback】:org_dept_create_action".$msg);
        break;
    case "org_dept_modify":
        //通讯录企业部门修改 do something
        Log::i("【callback】:org_dept_modify_action".$msg);
        break;
    case "org_dept_remove":
        //通讯录企业部门删除 do something
        Log::i("【callback】:org_dept_remove_action".$msg);
        break;
    case "org_remove":
        //企业被解散 do something
        Log::i("【callback】:org_remove_action".$msg);
        break;
    case "bpms_task_change":
        Log::i("【callback】: 流程任务变化".$msg);
        break;
    case "bpms_instance_change":
        Log::i("【callback】:流程实例变化".$msg);
        $instanceId = $eventMsg->processInstanceId;
        $stage = $eventMsg->type; //阶段：start，finish
        $result = $eventMsg->type; //结果：refuse，agree
        $templateId = $eventMsg->processCode; //结果：钉钉模版ID

        //得到实例详情
        $processInstance = getDingdingInstance($instanceId);
        if (!$processInstance){
            break;
        }
        //调试
//        $processInstance = getDingdingInstance("40567822-2ca5-4074-9200-2e2abc3de579");
      //得到实例详情中的相关值,结构如下
        /*"process_instance":{
        "title":"实例标题",
        "create_time":"2018-11-21 12:00:00",
        "finish_time":"2018-11-21 12:00:00",
        "originator_userid":"manager1",
        "originator_dept_id":"1",
        "status":"NEW",
        "cc_userids":"manager1,manager2",
        "form_component_values":[
            {
                "name":"名称",
                "value":"示例值",
                "ext_value":"示例值"
            }
        ],
        "result":"agree",
        "business_id":"2017111111",
        "operation_records":[
            {
                "userid":"manager1",
                    "date":"2018-11-21 12:00:00",
                    "operation_type":"EXECUTE_TASK_NORMAL",
                    "operation_result":"AGREE",
                    "remark":"评论"
            }
        ],*/

        //将数据传入fm流程集合-2，建立实例DingdingId作为主键之一；
        writeWorkflowInstanceToFm($instanceId,$templateId,$processInstance);
//        writeWorkflowInstanceToFm("40567822-2ca5-4074-9200-2e2abc3de579",$templateId,$processInstance);
        break;
    case "check_url"://do something
    default : //do something
        break;
}

/**对返回信息进行加密**/
$res = "success";
$encryptMsg = "";
$errCode = $crypt->EncryptMsg($res, $timeStamp, $nonce, $encryptMsg);
if ($errCode == 0)
{
    echo $encryptMsg;
    Log::i("【callback】:RESPONSE:SUCCESS " . $encryptMsg);
}
else
{
    Log::e("RESPONSE ERR: " . $errCode);
}



/**递归将对象转换成数组,包括数组里面的对象
 * @param $array
 * @return array
 */
/*function object_array($array) {
    if(is_object($array)) {
        $array = (array)$array;
    } if(is_array($array)) {
        foreach($array as $key=>$value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}*/

//记录准备,针对建立和修改
function dataPreparation($userInfo,& $fmRecord){
    //将ding userinfo 转为fm 结构
    //edit record

    /*            Log::i("【fm success 】: operationType:".$operationType."msg:".$result['messages'][0]['message']);

     orderInDepts = "{1:176387551904584512,69525103:180016051395587460}"
        position = "总经理"
        remark = ""
        department = {array} [2]
     * tel = ""
    //userid = "1960580858678987"
    //isSenior = false
    //workPlace = "渝北区长凯路空港工业园区405号1"
    //dingId = "$:LWCP_v1:$DLL+ySXjRZiQAkAU0uxV77XC7oL8zpjY"
    //isBoss = false
    //name = "刘正"
    //errmsg = "ok"
    //stateCode = "86"
    //avatar = ""
    //errcode = 0
    //jobnumber = ""
    //isLeaderInDepts = "{1:false,69525103:true}"
    //email = "lz001@vip.163.com"
    //roles = {array} [4]
    //active = true
    //isAdmin = true
    //openId = "Xii0iPl0k4SDE4FKKXIZ1ArgiEiE"
    //mobile = "18680807785"
      unionid = "Xii0iPl0k4SDE4FKKXIZ1ArgiEiE"
    isLeaderInDepts = "{1:false,69525103:true}"
    isHide = false*/

    $record['name'] = $userInfo->name?$userInfo->name:"";
    $record['workPlace'] = $userInfo->workPlace?$userInfo->workPlace:"";

    $record['tel'] = $userInfo->tel? $userInfo->tel:"";
    $record['remark'] = $userInfo->remark?$userInfo->remark:"";
    $record['position'] = $userInfo->position?$userInfo->position:"";

    $record['isSenior'] = $userInfo->isSenior?1:0;
    $record['dingId'] = $userInfo->dingId?$userInfo->dingId:"";
    $record['jobnumber'] = $userInfo->jobnumber? $userInfo->jobnumber:""; //工号
    $record['email'] = $userInfo->email?$userInfo->email:"";
    $record['active'] = $userInfo->active?1:0;
    $record['isAdmin'] = $userInfo->isAdmin?1:0;
    $record['mobile'] = $userInfo->mobile?$userInfo->mobile:"";  //唯一值,登录用
//    $record['openId'] = $userInfo->isAopenIddmin; //null
    $record['unionid'] = $userInfo->unionid?$userInfo->unionid:"";

    $record['avatar'] = $userInfo->avatar?$userInfo->avatar:"";  //头像

    $record['orderInDepts'] = $userInfo->orderInDepts?ext_json_decode($userInfo->orderInDepts,$mode=false):""; //json字符串
    //json字符中
    $record['isLeaderInDepts'] = $userInfo->isLeaderInDepts?ext_json_decode($userInfo->isLeaderInDepts, $mode=false):"";

    $fmRecord['fieldData'] =  $record;

    $portalRecord = array();
    foreach ($userInfo->roles as $v=>$role) {
        array_push($portalRecord, array('roles::name' => $role->name,'roles::id'=>$role->id,'roles::groupName'=>$role->groupName,'roles::type'=>$role->type));
    }
    $portal['rolesTable'] = $portalRecord;

    $portalRecord2 = array();
    foreach ($userInfo->department as $dept) {
        array_push($portalRecord2, array('useranddept::deptID foreign ID' => $dept));
    }
    $portal['departmentPortal'] = $portalRecord2;

    $fmRecord['portalData'] = $portal;

}

/**
 * @param $str
 * @param bool $mode
 * @return mixed
 * 将json字符串的key值加\"key\",以便fm json操作
 */
function ext_json_decode($str, $mode=false){
    if(preg_match('/\w:/', $str)){
        $str = preg_replace('/(\w+):/is', '"$1":', $str);
    }
    return $str;
}

/**
 * @param
 * @return
 * 得到钉钉实例中的相关数据详情，为传入fm做准备
 */
function getDingdingInstance($instanceId){
    $auth = new Auth();
    $http = new Http();

    $accessToken = $auth->getAccessToken();
    $opt['process_instance_id'] = $instanceId;
    $result = $http->post("/topapi/processinstance/get",
        array("access_token" => $accessToken),
        $opt);
//    echo(json_encode($result));
    if($result->errcode === 0){
        return $result->process_instance;
    }else{
        //记录失败日志
        Log::i("【callback】失败:bpms_instance_change".$result->errcode);
        return false;
    }

}

/**
 * @param $instanceId 流程实例ID,$processInstance 钉钉实例值,包括实例审核时间,控件值等信息
 * @return
 * 通过$instanceId,在fm流程集合中建立或修改相关实例记录，根据模版名建立相关记录，比如请假，急件考核等
 *
 */
function writeWorkflowInstanceToFm($instanceId,$templateId,$processInstance){

    $host = 'liuzheng750417.imwork.net:442';
    $db = '流程集合-2';
    $username = '钉钉';
    $pass = 'admin0422';
    $layout = '审核工作流模版';


    $fmInstance = new fmREST ($host, $db, $username, $pass, $layout);
    //通过查找$templateId,得到审核模版以既 记录表名(layout名)比如请假是 请假记录则后续操作是在请假记录 layout上操作
    $request1['钉钉模版ID'] = $templateId;
    $query = array ($request1);
    $data['query'] = $query;
    $result = $fmInstance->findRecords($data);
    $request1 =[];$data=[];

    //fm操作判断
    if ($result['messages'][0]['code'] === '0'  ){
        $workflowLayout =  $result['response']['data'][0]['fieldData']['记录表名'];
        $exception =  $result['response']['data'][0]['fieldData']['例外项'];
    }else{
        $fmInstance->resultJudge($result,'该流程尚未关联钉钉审批,或查找记录表名(layout)失败');
        return;
    }

    //打开记录表名的布局
    $fmInstance = new fmREST ($host, $db, $username, $pass, $workflowLayout);

    //通过rest api将数据写入fm
    //查找$instanceId是否存在,存在则修改,不存在则建立
    $values = $processInstance->form_component_values;
    $opeaResult = $processInstance->operation_records;  //钉钉审批记录,相当于节点记录

    $request1['审核工作流实例::钉钉实例ID'] = $instanceId;
    $query = array ($request1);
    $data['query'] = $query;
    $data["script"] = "delete portalData for instance node";
    $result = $fmInstance->findRecords($data);

    $request1 =[];$data=[];


    //fm操作判断
    if ($result['messages'][0]['code'] === '0'  ){
        //fm唯一记录值
        $recordId = $result['response']['data'][0]['recordId'];

        $record['审核工作流实例::钉钉模版ID'] = $templateId;
        $record['审核工作流实例::流程发起人钉钉ID'] = $processInstance->originator_userid;
        $record['审核工作流实例::流程开始时间'] =date('m/d/Y h:i:s A',strtotime($processInstance->create_time)) ;
        $record['审核工作流实例::流程结束时间'] = date('m/d/Y h:i:s A',strtotime($processInstance->finish_time));
        $record['审核工作流实例::钉钉审核状态'] = $processInstance->result;


       /* $record['请假类型'] = $values[0]->value;
        $record['请假开始日期'] = date("m/d/Y",strtotime($values[1]->value));
        $record['请假开始阶段'] = $values[2]->value;
        $record['请假结束日期'] = date("m/d/Y",strtotime($values[3]->value));
        $record['请假结束阶段'] = $values[4]->value;
//        $record['请假时长'] = $values[5]->vaue;
        $record['事由'] = $values[6]->value;*/
//        $record['审核工作流实例::钉钉实例ID'] = $instanceId;

        $fmRecord['fieldData'] = $record;
        $recValues = convertFormatToFm($values,$exception);
        $fmRecord['fieldData'] =   array_merge($fmRecord['fieldData'], $recValues['fieldData']);


        if ($recValues['portalData']) { //有入口值
            $fmRecord['portalData'] = $recValues['portalData'];
        }


        //将审批步骤加入fm
        $operateResult = operateResult($opeaResult);
        if ($operateResult) {
         /*   if ($fmRecord['portalData']) {
              array_push($fmRecord['portalData'],$operateResult);
            }else{
                $fmRecord['portalData'] = $operateResult;
            }*/
            $fmRecord['portalData']['审核工作流实例节点'] = $operateResult;

        }
        $recResult = $fmInstance->editRecord($recordId,$fmRecord);
        $fmInstance->resultJudge($recResult,'editRecord');
    }
    else if($result['messages'][0]['code'] === '401' /*未找到,则新建记录*/ ){
/*        dataPreparation($userInfo,$fmRecord);*/
        $record['审核工作流实例::钉钉模版ID'] = $templateId;
        $record['审核工作流实例::流程发起人钉钉ID'] = $processInstance->originator_userid;
        $record['审核工作流实例::流程开始时间'] =$processInstance->create_time?date('m/d/Y h:i:s A',strtotime($processInstance->create_time)):"" ;
        $record['审核工作流实例::流程结束时间'] =$processInstance->finish_time?date('m/d/Y h:i:s A',strtotime($processInstance->finish_time)):"";
        $record['审核工作流实例::钉钉审核状态'] = $processInstance->result;
        $record['审核工作流实例::钉钉实例ID'] = $instanceId;

        /*$record['请假类型'] = $values[0]->value;
//       注意日期格式到fm使用m/d/Y,且是字符串,如6/23/2019
        $record['请假开始日期'] = date("m/d/Y",strtotime($values[1]->value));
        $record['请假开始阶段'] = $values[2]->value;
        $record['请假结束日期'] = date("m/d/Y",strtotime($values[3]->value));
//        $record['请假结束日期'] = "6/23/2019";
        $record['请假结束阶段'] = $values[4]->value;
//        $record['请假时长'] = $values[5]->vaue;
        $record['事由'] = $values[6]->value;*/

        $fmRecord['fieldData'] = $record;
        $recValues = convertFormatToFm($values,$exception);
        $fmRecord['fieldData'] =   array_merge($fmRecord['fieldData'], $recValues['fieldData']);
        $fmRecord['script'] = "建立流程实例初始化";

        if ($recValues['portalData']) { //有入口值
            $fmRecord['portalData'] = $recValues['portalData'];
        }

        //将审批步骤加入fm
        $operateResult = operateResult($opeaResult);
        if ($operateResult) {
            /*if ($fmRecord['portalData']) {
                array_push($fmRecord['portalData'],$operateResult);
            }else{
                $fmRecord['portalData'] = $operateResult;
            }*/
            $fmRecord['portalData']['审核工作流实例节点'] = $operateResult;
        }
        $recResult = $fmInstance->createRecord($fmRecord);
        $fmInstance->resultJudge($recResult,'createRecord');
    }
    $fmInstance->logout();
}

/**
 * 将form_component_values":[
    {
    "name":"名称",
    "value":"示例值",
    "ext_value":"示例值"
 *   component_type = "DDDateField"

    },
    {name: "明细表", value: [
        [{"name": "第一行", "value": "要"},
        {"name": "第二行", "value": "人"}],
        [{"name": "第一行", "value": "sdg"},
        {"name": "第二行", "value": "dfadf"}]
        ]
    }
},
]格式
 * 转换为
 * fields['名称'] = '示例值'  fieldData数据
 * portals['明细表'] = [['第一行'=>"要",'第二行'=>'人'],['第一行'=>"sdg",'第二行'=>'dfadf']] portalData数据
 * 计算字段不计入,比如请假的时长
 * $exception数组 指例外项,比如请假记录中的时长,是计算字段,不能赋值
 *
 */
function convertFormatToFm($values,$exception){
    $fields = [];
    $portals = [];
    foreach ($values as $key=>$item){
        if (is_array($item->value)) { //是入口数据
            //暂缺
        } else { //说明是field数据
            //$exception,例外项在fm中申明,不写入,比如计算字段,否则出错
            if (!$exception || !strstr($exception,$item->name."¶") ) {
                //目前只考虑了DDDateField,没有涉及日期区间等
                switch ($item->component_type){
                    case "DDDateField" : //钉钉传过来数据是日期
                        $fields[$item->name] = $item->value?date("m/d/Y",strtotime($item->value)):"";
                        break;
                    case "DDPhotoField" : //图片组件
                        $imageUrls = json_decode($item->value,true); //转为数组
                        foreach ($imageUrls as $num=>$imageUrl) {
                        /*    '重复字段(1)' => '1',
                             '重复字段(2)' => '2',*/
                            $fields[$item->name.'('.($num+1).')'] = $imageUrl;
                        }
                        break;
                    case "TableField": //明细
                        $portalName = $item->name;//例样品明细
                        $portalValues = json_decode($item->value,true); //转为数组
                        $portals[$portalName]=[];

                        foreach ($portalValues as $portalItem){
                            $rec = [];
                            if (!$portalItem['rowValue']) {
                                continue;  //没有入口值
                            }
                            foreach ($portalItem['rowValue'] as $field){
                                if ($field['componentType'] === "DDDateField"){
                                    $rec[$portalName . "::" . $field['label']] = $field['value']?date("m/d/Y",strtotime($field['value'])):"";
                                }else {
                                    $rec[$portalName . "::" . $field['label']] = $field['value'];
                                }
                            }
                            if ($rec) {
                                array_push($portals[$portalName],$rec);
                            }
                        }
                        break;
                    default:
                        if ($item->name != "") {
                            $fields[$item->name] = $item->value;
                        }
                }
            }
        }
    }
    $data['fieldData']=$fields;
    if($portals){
        $data['portalData']=$portals;
    }
    return $data;
}


//钉钉审批操作记录,转换成fm api 数组,下一步写入fm
function operateResult($result) //
{
    $approvalPortal = [];
    foreach ($result as $item) {
        $record = [];
        $record['审核工作流实例节点::userid'] = $item->userid;
//        $record['审核工作流实例节点::审核时间'] = $item->date?date('m/d/Y h:i:s A',strtotime($item->date)):"" ;
        $record['审核工作流实例节点::operation_result'] = $item->operation_result;
        $record['审核工作流实例节点::remark'] = $item->remark==null?"":$item->remark;
//        array_push($approvalPortal["审核工作流实例节点"],$record);
        $approvalPortal[]=$record;
    }
    return $approvalPortal;

}