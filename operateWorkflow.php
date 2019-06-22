<?php
/**
 * Created by PhpStorm.
 * 接收前端参数,由于传数组过来,所以前端使用JSON.stringify方法建立workflow
 * User: liuzheng
 * Date: 2019/5/8
 * Time: 10:37 PM
 */

require_once (__DIR__."/api/Workflow.php");
$values = json_decode($_REQUEST['values'],true); //json_decode字符串转对象,加true参数为转数组

$progress_code = $values['progress_code'];
$form_component_values = $values['form_values'];

//发起人id和department
$originator_user_id = $values['originatorUserId'];
$dept_id = $values['dept_id'];

$workflow = new Workflow();
$result = $workflow->createWorkflow($progress_code,$originator_user_id,$dept_id,$form_component_values);
echo json_encode($result, JSON_UNESCAPED_SLASHES);
