<?php
/**
 * Created by PhpStorm.建立,跟踪,结束各类流程
 * User: liuzheng
 * Date: 2019/5/8
 * Time: 10:04 PM
 */



header("Access-Control-Allow-Origin: *");
header('content-type:text/html;charset=utf8');
//设置服务器为北京时间
date_default_timezone_set('Asia/Shanghai');

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../util/Log.php");
require_once(__DIR__ . "/../util/Cache.php");
require_once(__DIR__ . "/../api/Auth.php");
require_once(__DIR__ . "/../util/Http.php");
require_once(__DIR__ . "/../api/user.php");

class Workflow {
    private $auth;
    private $user;
    private $http;

    public function __construct() {
        $this->http = new Http();
        $this->auth = new auth();
        $this->user = new User();
    }

    public function createWorkflow($progress_code,$originator_user_id,$dept_id,$form_component_values=null)
    {
        $accessToken = $this->auth->getAccessToken();

        $opt['process_code'] = $progress_code;
        $opt['originator_user_id'] = $originator_user_id;
        $opt['form_component_values'] = $form_component_values;
        $opt['dept_id'] = $dept_id;

        /*
        $opt['originator_user_id'] = 1960580858678987;
        $opt['dept_id'] = 76408150;
        //$opt['process_code'] = 'PROC-RIYJUE5W-ESNW8ITXOOCISY4HMD1T1-U6GEGBJJ-O1';
        $opt['process_code'] = 'PROC-6AA9FF64-D13D-402A-82E8-4ED79BFA6FC8';
        $form_component_values = array(
            array("name"=>"事由","value"=>"事假1","ext_value"=>"总天数:1"),
            array("name"=>"客户名称","value"=>"重庆长安有限公司"),
            array("name"=>'["开始时间","结束时间"]',"value"=>'["2019-03-09","2019-04-09"]'),
            array("name"=>"图片","value"=>'[
            "https://www.9669.com/uploadfile/2016/0422/20160422032911845.jpg",
            "https://static.dingtalk.com/media/lADPDgQ9qrANd87NBdzNBGQ_1124_1500.jpg"
            ]'),
            // "["https://static.dingtalk.com/media/lADPDgQ9qrAL_k_NBdzNBGQ_1124_1500.jpg","https://static.dingtalk.com/media/lADPDgQ9qrANd87NBdzNBGQ_1124_1500.jpg"]"
            array(
                "name"=>"明细表","value"=>'[
                            [{"name":"第一行","value":"要"},
                             {"name":"第二行","value":"人"}],
                             [{"name":"第一行","value":"sdg"},
                             {"name":"第二行","value":"dfadf"}]
                ]'),
        );
        $opt['form_component_values'] = $form_component_values;*/

        $response = $this->http->post("/topapi/processinstance/create",
            array("access_token" => $accessToken),
            $opt);
        return $response;
    }
    public function finishWorkflow($progress_code,$workflowName)
    {

    }
    public function changeWorkflow($progress_code,$workflowName) //流程任务更改,比如步骤变更,从批准到审核
    {

    }
}

