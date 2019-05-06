<?php
require_once(__DIR__ . "/../util/Http.php");

class Department
{
    private $http ;
    public function __construct() {
        $this->http = new Http();
    }

    public  function createDept($accessToken, $dept)
    {
        $response = $this->http->post("/department/create",
            array("access_token" => $accessToken), 
            json_encode($dept));
        return $response;
    }


    /**
     * @param $accessToken
     * @return mixed|null
     */
    public  function listDept($accessToken,$fetch_child)
    {

//        $response = Http::get("/department/list",
//            array("access_token" => $accessToken));
        $response = $this->http->get("/department/list",  array("access_token" => $accessToken,"fetch_child"=>$fetch_child));
        return $response;
    }
    
    
    public  function deleteDept($accessToken, $id)
    {
        $response = $this->http->get("/department/delete",
            array("access_token" => $accessToken, "id" => $id));
        return $response;
    }

    //获取部门用户id列表
    public function getDeptUserList($accessToken,$deptID)
    {
        $response = $this->http->get("/user/getDeptMember", array("access_token" => $accessToken,"deptId"=>$deptID));
        return $response;

    }

    //获取部门用户详情列表

    public function getDeptUserdetailsList($accessToken,$deptID)
    {
        $response = $this->http->get("/user/list", array("access_token" => $accessToken,"department_id"=>$deptID));
        return $response;

    }

}