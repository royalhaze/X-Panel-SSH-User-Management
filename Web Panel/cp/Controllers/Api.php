<?php
include_once("Models/Api_Model.php");
class Api extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->model = new Api_Model();
        $key = htmlentities($_GET['key']);
        $token = $this->model->check_token($key);
        if($token == 'allowed'){
            $this->index();
        }else{

            echo 'invalid api key';

        }

    }
    public function index()
    {
        //list user
        if(isset($_GET['method']) && $_GET['method'] == "listuser"){
            $list_user = $this->model->list_user();
            $this->response($list_user) ;
        }

        //sort status user
        if(isset($_GET['method']) && $_GET['method'] == "users" && !empty($_GET['status'])){
            $status_user = $this->model->status_user($_GET['status']);
            $this->response($status_user) ;
        }

        //add user
        if(isset($_GET['method']) && $_GET['method'] == "adduser"){
            $username = htmlentities($_POST['username']);
            $password = htmlentities($_POST['password']);
            $email = htmlentities($_POST['email']);
            $mobile = htmlentities($_POST['mobile']);
            $multiuser = htmlentities("1") ;
            if(isset($_POST['multiuser'])){
                $multiuser = htmlentities($_POST['multiuser']);
            }
            $connection_start = htmlentities($_POST['connection_start']);
            $traffic = htmlentities('0');
            if(isset($_POST['traffic'])){
                $traffic = htmlentities($_POST['traffic']);
            }
            $type_traffic = htmlentities($_POST['type_traffic']);
            $expdate = htmlentities($_POST['expdate']);
            $desc = htmlentities($_POST['desc']);
            if(!empty($connection_start)) { $st_date=''; }
            else { $st_date=date("Y-m-d"); }
            if ($type_traffic == "gb") {
                $traffic = $traffic * 1024;
            } else {
                $traffic = $traffic;
            }
            if(!empty($username) and !empty($password)) {
                $data_sybmit = array(
                    'username' => $username,
                    'password' => $password,
                    'email' => $email,
                    'mobile' => $mobile,
                    'multiuser' => $multiuser,
                    'startdate' => $st_date,
                    'finishdate' => $expdate,
                    'finishdate_one_connect' => $connection_start,
                    'enable' => 'true',
                    'traffic' => $traffic,
                    'referral' => '',
                    'info' => $desc
                );
                $this->model->submit_index($data_sybmit);
            }
            else
            {
                echo "invalid empty username and password";
            }

        }

        //show user
        if(isset($_GET['method']) && $_GET['method'] == "user" && !empty($_GET['username'])){
            $usernme = htmlentities($_GET['username']);
            $show_user = $this->model->show_user($usernme);
            $this->response($show_user) ;
        }
        //edit user
        if(isset($_GET['method']) && $_GET['method'] == "edituser"){
            $username = htmlentities($_POST['username']);
            $password = htmlentities($_POST['password']);
            $email = htmlentities($_POST['email']);
            $mobile = htmlentities($_POST['mobile']);
            $multiuser = htmlentities($_POST['multiuser']);
            $traffic = htmlentities($_POST['traffic']);
            $type_traffic = htmlentities($_POST['type_traffic']);
            $expdate = htmlentities($_POST['expdate']);
            $desc = htmlentities($_POST['desc']);
            if ($type_traffic == "gb") {
                $traffic = $traffic * 1024;
            } else {
                $traffic = $traffic;
            }
            if(!empty($usernme) && !empty($password)) {
                $data_sybmit = array(
                    'username' => $username,
                    'password' => $password,
                    'email' => $email,
                    'mobile' => $mobile,
                    'multiuser' => $multiuser,
                    'finishdate' => $expdate,
                    'traffic' => $traffic,
                    'info' => $desc
                );
                $edit_user = $this->model->edit_user($data_sybmit);
                $this->response($edit_user);
            }
            else
            {
                echo "invalid empty username and password";
            }
        }
        // delete user
        if(isset($_GET['method']) && $_GET['method'] == "deleteuser"){
            $usernme = htmlentities($_POST['username']);
            if(!empty($usernme)) {
                $data_sybmit = array(
                    'username' => $usernme
                );
                $this->model->delete_user($data_sybmit);
            }
            else
            {
                echo "invalid empty username";
            }
        }

        if(isset($_GET['method']) && $_GET['method'] == 'online'){
            $duplicate = [];
            $m = 1;
            $data = [];
            $dropb = shell_exec("cat /var/log/auth.log | grep -i dropbear | grep -i \"Password auth succeeded\" | grep \"for 'ttes'\" | awk '{print $5}'");

            $dropbear = shell_exec("ps aux | grep -i dropbear | awk '{print $2}'");
            $dropbear = preg_split("/\r\n|\n|\r/", $dropbear);
            $drop_dup = [];
            $list = shell_exec("sudo lsof -i :" . PORT . " -n | grep -v root | grep ESTABLISHED");
            $onlineuserlist = preg_split("/\r\n|\n|\r/", $list);
            foreach ($onlineuserlist as $user) {
                $user = preg_replace("/\\s+/", " ", $user);
                if (strpos($user, ":AAAA") !== false) {
                    $userarray = explode(":", $user);
                } else {
                    $userarray = explode(" ", $user);
                }
                if (strpos($userarray[8], "->") !== false) {
                    $userarray[8] = strstr($userarray[8], "->");
                    $userarray[8] = str_replace("->", "", $userarray[8]);
                    $userip = substr($userarray[8], 0, strpos($userarray[8], ":"));
                } else {
                    $userip = $userarray[8];
                }
                $color = "#dc2626";
                if (!in_array($userarray[2], $duplicate)) {
                    $color = "#269393";
                    array_push($duplicate, $userarray[2]);
                }
                if (!empty($userarray[2]) && $userarray[2] !== "sshd") {
                    $drop_dup = $userarray[2];

                    $data[] = [
                        'username' =>  $userarray[2],
                        'ip' => $userip
                    ];
                }
            }
            $this->response($data);
        }

    }

    function response($data){

        $res= [
            'status' => 200,
            'data'   => $data

        ];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res,  JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    }

}
