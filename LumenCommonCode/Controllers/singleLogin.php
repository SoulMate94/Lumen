<?php
<?php
session_start();
//ini_set('session.auto_start', 0);                    //关闭session自动启动
//ini_set('session.cookie_lifetime', 0);//设置session在浏览器关闭时失效
//ini_set('session.gc_maxlifetime', 3600);  //session在浏览器未关闭时的持续存活时间 


//防止同一用户的重复登录
$server = '127.0.0.1';
$username = 'root';
$password = '';
$conn = mysql_connect($server, $username, $password);
$e = mysql_select_db('test',$conn);
mysql_query("SET NAMES UTF8");

//访问url，http://www.test.com/single_login.php?name=zx&pwd=zx

header("Content-type:text/html;charset=utf-8");

$username =$_GET['name'];

$password =$_GET['pwd'];

//$ipdress = $_SERVER['REMOTE_ADDR'];
//
//$login_time = time();//登陆时间更新
//
//$session_id = session_id();
//
//$_SESSION['name']=$_GET['name'];

//var_dump($session_id);

//$sql = "INSERT INTO tongji VALUES ('NULL','$data_id','$time','$ipdress','$session_id')";

//$result =mysql_query($sql);

$sql = "select * from slogin where username = '$username' and password = '$password'";
//var_dump($sql);
$result =mysql_query($sql);
$s = mysql_fetch_array($result);

//权限验证的时候一定要判断数据库写入session_id是不是和$_session里面的session_id是不是一致，如果不一致就退出，这个防止同一个账户被多个人同时登陆的实现方法就是基于这个session_id
//一定要设置session的存活时间，和session关掉页面就session消除
//如果有其他人登陆的时候，会写入新的session_id，这样旧的用户就会被权限验证不通过，但是不能做到实时效果，这样就挤掉了旧用户，这里有个缺陷，就是每次验证权限的时候就需要查询数据库，如果可以吧这个数据放在redis就比较好
//其实其他的验证方法，也类似使用这种方法，有个验证的凭证，只不过这种方法需要查询数据库，但是放在redis之后就比较好
//或者统一管理session的时候，比如放在数据库就更好了，记住吧session_id也存入数据库，或者放在memcash，redis就比较方便，比如在redis进行管理的时候，就可以直接更新掉存在session_id
    if($s){
        //    var_dump($s);
        //    echo '<br>';
        //    echo session_id();
        if($s['session_id'] === session_id()){
          //unset($s['session_id']);
        }  else {
            $ipdress = $_SERVER['REMOTE_ADDR'];
            $login_time = time();
            $session_id = session_id();//重新赋予一个session_id
            $sql = "update slogin set ip = '$ipdress',login_time= '$login_time',session_id = '$session_id'"; 
            $result1 =mysql_query($sql);
           // $ss = mysql_fetch_array($result);
            if($result1){
                echo 'success';
            }  else {
                echo 'fail';
            }
        }
    }else {
        echo 'FAIL';
    }