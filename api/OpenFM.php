<?php
/**
 * Created by PhpStorm.
 * User: liuzheng
 * Date: 2018/8/1
 * Time: 下午2:08
 * 判断是钉钉链接,而且是新建公司人员使用,则重定向到filemaker,否则不能访问
 */


class OpenFM
{   //钉钉登录fm密码和账户
    private $user = "钉钉";
    private $pwd = "030528";
    public function judgeFM( $url,$userID)
    {
        //userID目前只是做有无效验,应需加强,与fm数据库中的userId对比
        if ($userID  and is_string($url)) {
            header("location:" . $url."%20".$this->user."%20".$this->pwd);
        } else {
            //header("location:http://liuzheng750417.imwork.net:591/fmi/webd#");
            echo "只能在钉钉打开";

        }
    }

}