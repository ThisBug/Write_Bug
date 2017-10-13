<?php
namespace app\index\controller;
use think\Controller;
session_start();
class Index extends Controller
{
    public function index()
    {
        session('count',session('count')+1);
        session('token',md5(substr(time(),8,16).'chiji'));
        if(session('count')>10)
        {
            session('count',0);
            throw new \think\exception\HttpException(403, '操作太频繁');
        }
        $db_order = model('Index');
        $num = $db_order->count('id');
        $this->assign('token',session('token'));
        $this->assign('shop_name','幸运吃鸡辅助季卡(3个月有效期)');
        $this->assign('money',88);
        $this->assign('num',$num+1500);
        return $this->fetch();
    }

    /**
     * 接收数据创建订单
     * 返回支付页面
     * @return bool
     */
    public function from()
    {
        /*对比token*/
        if($_POST['token'] <> session('token'))
        {
            exit("<script>alert('页面失效,请刷新重试!');history.back(-1);</script>");
        }
        $db_order = model('Index');
        $db_order->shop_name = $_POST['shop_name'];
        $db_order->money = floatval($_POST['money']);
        $db_order->num = intval($_POST['num']);
        $db_order->mobile = $_POST['mobile'];
        $db_order->pay = intval($_POST['pay']);
        $db_order->save();
        $post = [
            'user' => $db_order->id,
            'price'=> 88,
            'remarks' => $_POST['mobile'],
            'type' => intval($_POST['pay']),
        ];
        $judge = $this->vcurl($_SERVER['HTTP_REFERER'].'codepay/codepay.php',$post);
        /*成功添加数据后消除token*/
        session('token',null);
        /*更新数据库成功信息*/
        if(strlen($judge)>1000)
        {
            $db_order->status = 1;
            $db_order->save();
            return $judge;
        }else{
            exit("<script>alert('页面失效,请刷新重试!');history.back(-1);</script>");
        }
    }

    /**
     * 接收用户付款成功数据
     */
    public function callback()
    {
        $file = realpath( '/home/wwwlogs/' ).'/apilogs/other/'.date('Y-m-d').'.log';
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file),0777, true);
        }
        file_put_contents($file,'['.date('Y-m-d H:i:s').'][post:'.json_encode($_REQUEST).'][return:'.json_encode($_POST).']'.PHP_EOL, FILE_APPEND);
    }

    /**
     * 远程请求函数
     * @param string $url    请求网址
     * @param string $post   post参数
     * @param string $cookie
     * @param string $cookiejar
     * @param string $referer
     * @return bool
     */
    public function vcurl($url, $post = '', $cookie = '', $cookiejar = '', $referer = '',$json='') {
        $tmpInfo = '';
        $cookiepath = getcwd() . './' . $cookiejar;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if($json)
        {
            $headers = array("Content-type: application/json;charset=UTF-8","Accept: application/json","Cache-Control: no-cache", "Pragma: no-cache",);
            curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
        }
        if ( is_array($post) && isset($post['HTTP_USER_AGENT']))
        {
            curl_setopt($curl, CURLOPT_USERAGENT, $post['HTTP_USER_AGENT']);
            unset($post['HTTP_USER_AGENT']);
        } else {
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }
        if ($referer) {
            curl_setopt($curl, CURLOPT_REFERER, $referer);
        } else {
            curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        }
        if ($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        if ($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        if ($cookiejar) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiepath);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiepath);
        }
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $tmpInfo = curl_exec($curl);
        /* if (curl_errno ( $curl )) {
         echo '<pre><b>错误:</b><br />' . curl_error ( $curl );
        } */
        curl_close($curl);
        return $tmpInfo;
    }
}
