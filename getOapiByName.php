<?php
header("Access-Control-Allow-Origin: *");
header('content-type:text/html;charset=utf8');
//设置服务器为北京时间
date_default_timezone_set('Asia/Shanghai');

require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/util/Log.php");
require_once(__DIR__ . "/util/Cache.php");
require_once(__DIR__ . "/api/Auth.php");
require_once(__DIR__ . "/api/User.php");
require_once(__DIR__ . "/api/Message.php");

require_once(__DIR__ . "/api/Department.php");
require_once (__DIR__ ."/util/Http.php");






$auth = new Auth();
$user = new User();
$message = new Message();
$deplist = new Department();
$http = new Http();

$event = $_REQUEST["event"];
switch($event){
    case '':
        echo json_encode(array("error_code"=>"4000"));
        break;
    case 'getuserid':
        $accessToken = $auth->getAccessToken();
        $code = $_POST["code"];
        $userInfo = $user->getUserInfo($accessToken, $code);
        Log::i("[USERINFO-getuserid]".json_encode($userInfo));
        echo json_encode($userInfo, true);
        break;

    case 'get_userinfo':
        $accessToken = $auth->getAccessToken();
        $userid = $_GET["userid"];
        $userInfo = $user->get($accessToken, $userid);
        Log::i("[get_userinfo]".json_encode($userInfo));
        echo json_encode($userInfo, true);
        break;
    case 'jsapi-oauth':
        $href = $_GET["href"];
        $configs = $auth->getConfig($href);
        $configs['errcode'] = 0;
        echo json_encode($configs, JSON_UNESCAPED_SLASHES);
        break;
    case 'get_department_list':
        $fetch_child = $_GET["fetch_child"];
        $accessToken = $auth->getAccessToken();

        $deplist = $deplist->listDept($accessToken,$fetch_child);
//        echo $deplist;
        echo json_encode($deplist, JSON_UNESCAPED_SLASHES);
        break;
    case 'send_message':
        $accessToken = $auth->getAccessToken();
        $sender =$_GET['sender'];
        $cid = $_GET['cid'];
//        $messageOpt =  array("sender" => $sender,"cid" => $cid, "msgtype" => "image", "image" => array("media_id"=>"@lADPBY0V45N22z_NAZDNAZA"));
        $messageOpt =  array("sender" => $sender,"cid" => $cid, "msgtype" => "text", "text" => array("content"=>"测试api发送"));
       $result =$message->sendToConversation($accessToken,$messageOpt);
        echo json_encode($result, JSON_UNESCAPED_SLASHES);

        break;
    case 'pushFM':
        $accessToken = $auth->getAccessToken();
        $touser = $_GET['touser'];
        $programme = $_GET['programme'];
        $script = $_GET['script'];
        //前端多参数用|隔开
        $instanceID = $_GET['instanceID'];
        $startMan = $_GET['startMan'];
        $nodeName = $_GET['nodeName'];
        $workflowName = $_GET['workflowName'];

        //返回的url地址是微应用的那个indexFm中需要的地址,是post提交的可以保密
//        indexFM.html?programme=流程集合-2&script=钉钉转到相关的记录和布局php&param=2303|刘正

//        $url = "http://localhost:3001/indexfm.html?programme=".$programme."&script=".$script."&param=".$param;

        //特殊字符要转义,%22代表双引号
//        $url = 'http://localhost:3001/#/home/

//      内网调试
//      $url = 'http://192.168.0.102:3001/#/home/%22programme%22:%22'.$programme.'%22,%22script%22:%22'.$script.'%22,%22instanceID%22:%22'.$instanceID.'%22,%22startMan%22:%22'.$startMan.'%22,%22nodeName%22:%22'.$nodeName.'%22';

//      dist
//        $url = 'http://liuzheng750417.imwork.net:8088/ding-fm-master/#/home/%22programme%22:%22'.$programme.'%22,%22script%22:%22'.$script.'%22,%22instanceID%22:%22'.$instanceID.'%22,%22startMan%22:%22'.$startMan.'%22,%22nodeName%22:%22'.$nodeName.'%22';
        $url = 'http://liuzheng750417.imwork.net:8088/ding-fm-master/?dd_orientation=auto#/home/%22programme%22:%22'.$programme.'%22,%22script%22:%22'.$script.'%22,%22instanceID%22:%22'.$instanceID.'%22,%22startMan%22:%22'.$startMan.'%22,%22nodeName%22:%22'.$nodeName.'%22';

        $title = "来自流程:".$workflowName."(".$instanceID."),发起人:".$startMan.",阶段:".$nodeName;
//        {"touser":"123|321","agentid":"4117797","msgtype":"link","link":{"messageUrl":"","picUrl":"","title":"","text":""}}
        //遍历touser,得到不同的$url,$param中的userID值

        $messageOpt =  array("touser" => $touser,"agentid" => AGENTID,"msgtype"=>"link","link"=>
                                     array("messageUrl"=>$url,"picUrl"=>"","title"=>date("Y-m-d h:i:sa"),"text"=>$title));
        $result =$message->send($accessToken,$messageOpt);
        echo $url;
        echo json_encode($result, JSON_UNESCAPED_SLASHES);

        break;


    case 'uploadimg':
        $accessToken = $auth->getAccessToken();
        //上传文件改名,因为钉钉服务器不能识别tmp_name,所以需加更名
        $date=date('Ymdhis');//得到当前时间,如;20070705163148
        $fileName=$_FILES['media']['name'];//得到上传文件的名字
        $name=explode('.',$fileName);//将文件名以'.'分割得到后缀名,得到一个数组
        $newPath=$date.'.'.$name[1];//得到一个新的文件为'20070705163148.jpg',即新的路径
        $oldPath=$_FILES['media']['tmp_name'];//临时文件夹,即以前的路径
        $file = dirname($_FILES['media']['tmp_name']);

        rename($oldPath,$file.'/'.$newPath);
        $_FILES['media']['tmp_name'] = $file.'/'.$newPath;

//        $messageOpt = array('media'=>__DIR__.'/1515633654.jpg');
        /*$result = $message->uploadImg($accessToken,$_FILES['media']);
        echo json_encode($result, JSON_UNESCAPED_SLASHES);*/



        //        $data['media'] = new CurlFile($messageOpt['uploadImg']['tmp_name']);
        $path = $_FILES['media']['tmp_name'];
//        $name = $_FILES['media']['name'];
        $data['media'] = new CurlFile($path);
       $data['media']->mime = $_FILES['media']['type'];
//        $data['media']['postname'] = 'abc';

//        new \CURLFile(realpath($val["tmp_name"]),$val["type"],$val["name"])
//        $data['type'] = 'image';
        $url = "https://oapi.dingtalk.com/media/upload?access_token=".$accessToken."&type=file";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result = curl_exec($ch);
        curl_close($ch);
        echo json_encode($result, JSON_UNESCAPED_SLASHES);



//        $response = $this->http->post("/media/upload",
//            array("access_token" => $accessToken,"type"=>'image'),
//            $messageOpt,true
//        );
//        echo $response;
        break;
    case "downloadFile":
        break;
    case "createworkflow":
        $accessToken = $auth->getAccessToken();
//        $AgentID = $_POST['agentID'];
        $opt['process_code'] = $_POST['process_code'];
        $opt['originator_user_id'] = $_POST['originator_user_id'];
        $opt['dept_id'] = $_POST['dept_id'];
        $opt['approvers'] = $_POST['approvers'];
//        $opt['cc_list'] = $_POST['CcList'];
//        $opt['cc_position'] ="START";
      /*  $form_component_values = array();
        $form_component_values->name="第一项";
        $form_component_values->value="事假";
        $form_component_values->ext_value="总天数:1";
        $form_component = array(json_encode($form_component_values));*/
//        $form_values = json_encode(array(array("name"=>"第一项","value"=>"事假1","ext_value"=>"总天数:1"),array("name"=>"第二项","value"=>"事假2","ext_value"=>"总天数:1")));
        $form_values = array(array("name"=>"第一项","value"=>"事假1","ext_value"=>"总天数:1"),array("name"=>"第二项","value"=>"事假2","ext_value"=>"总天数:1"));
        $opt['form_component_values'] = $form_values;


        $response = $http->post("/topapi/processinstance/create",
            array("access_token" => $accessToken),
            $opt);
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;
    case "listWorkflow":
        $accessToken = $auth->getAccessToken();
        $opt['process_code'] = $_POST['process_code'];
        $opt['start_time'] = $_POST['start_time'];
        $opt['end_time'] = $_POST['end_time'];
        $response = $http->post("/topapi/processinstance/listids",
            array("access_token" => $accessToken),
            $opt);
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;
    case "getWorkflow":
        $accessToken = $auth->getAccessToken();
        $opt['process_instance_id'] = $_POST['process_instance_id'];
        $response = $http->post("/topapi/processinstance/get",
            array("access_token" => $accessToken),
            $opt);
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;
    case "getTodoWorkflowNum":
        $accessToken = $auth->getAccessToken();
        $opt['userid'] = $_POST['userid'];
        $response = $http->post("/topapi/process/gettodonum",
        array("access_token" => $accessToken),
        $opt);
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;
    case "registerCallback":
        $accessToken = $auth->getAccessToken();
        /* call_back_tag": ["user_add_org", "user_modify_org", "user_leave_org"],
             "token": "123456",
           "aes_key": "1234567890123456789012345678901234567890123",
           "url":"http://test001.vaiwan.com/eventreceive"*/
//        $opt['call_back_tag'] = array("user_add_org","user_leave_org","user_modify_org", "bpms_task_change","bpms_instance_change");
        $opt['call_back_tag'] = array("user_add_org","user_leave_org","user_modify_org","bpms_instance_change");
//        $opt['call_back_tag'] = array( "bpms_task_change","bpms_instance_change");

        $opt['aes_key'] = "1234567890123456789012345678901234567890123";
        $opt['token'] = "123456";
        //一个企业好像只能申请一个回调服务器.目前使用liuzheng750417.imwork.net:8088,调试可切换r1w8478651.imwork.net:9998
        $opt['url'] = "http://r1w8478651.imwork.net:9998/eapp-corp/DingCallback.php";
//        $opt['url'] = "http://liuzheng750417.imwork.net:8088/corp_php-master/DingCallback.php";

        $response = $http->post("/call_back/register_call_back",
            array("access_token" => $accessToken),
            json_encode($opt));
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;
    case "callbackState":
        $accessToken = $auth->getAccessToken();
        $response = $http->get("/call_back/get_call_back",
            array("access_token" => $accessToken));
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;
    case "updatecallback":
        $accessToken = $auth->getAccessToken();
        $opt['call_back_tag'] = array("user_add_org","user_leave_org","user_modify_org", "bpms_task_change","bpms_instance_change");
        $opt['aes_key'] = "1234567890123456789012345678901234567890123";
        $opt['token'] = "123456";
//      $opt['url'] = "http://r1w8478651.imwork.net:9998/corp_demo_php-master/callback.php";
        $opt['url'] = WORK_HOST.WORK_CORP."DingCallback.php";

        $response = $http->post("/call_back/update_call_back",
            array("access_token" => $accessToken),
            json_encode($opt));
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;

    case "deletecallback":
             $accessToken = $auth->getAccessToken();
             $response = $http->get("/call_back/delete_call_back",
                 array("access_token" => $accessToken));
             echo json_encode($response, JSON_UNESCAPED_SLASHES);
             break;
    case "testapi":
            $opt['username'] =  "admin";
            $opt['password'] = "admin";
            $opt['layout'] = "authtest";
        $response = $http->fm_post_https(json_encode($opt));
        echo $response;
        break;
    case "openFM":
        $userID = $_POST['userID'];//钉钉容器版本
        $host= $_POST['host'];
        $programme= $_POST['programme'];
        $script= $_POST['script'];
        $param= $_POST['param'];
       $param = $param."%20".$userID;

        $url = $host.$programme."?script=".$script."&param=".$param;

         $FM->judgeFM($url,$userID);
        break;
    case "getscopes": //获取通讯员权限
        $accessToken = $auth->getAccessToken();
        $scopes = $auth->getScopes($accessToken);
        echo json_encode($scopes, JSON_UNESCAPED_SLASHES);
    case "getDeptUserdetailsList": //获取部门用户ID
        $accessToken = $auth->getAccessToken();
        $deptId = $_GET['DeptId'];
        $deptUserList= $deplist->getDeptUserdetailsList($accessToken,$deptId);
        echo json_encode($deptUserList, JSON_UNESCAPED_SLASHES);
        break;
    case "getProgressDingTalkSpaceId": //得到钉钉上传附件的钉盘空间
        $accessToken = $auth->getAccessToken();
        $opt['user_id'] = "1960580858678987";
        $response = $http->post("/topapi/processinstance/cspace/info",
            array("access_token" => $accessToken),
            $opt);
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        break;
    default:
        echo json_encode(array("error_code"=>"4000"));
        break;


}
