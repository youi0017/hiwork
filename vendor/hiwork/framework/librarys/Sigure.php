<?php namespace hw\librarys;

/*
 * url签名/验证库
 * 20190521
 */

class Sigure
{
    use \hw\Staticer;
    
    // 用于签名的个人字符串，用于加强签名的安全性
    const PSK='hw-chy-20201001';//请修改为自已的值

    /*
     * 生成签名字串
     * $get [必] array|queryStr 生成签名的原始数据
     * return queryStr
     *
     * 示例：
        $get=['uid'=>4,'nick'=>'abc',];
        var_dump(\hw\lib\Sigure::generate($get));
        exit;
     */
    function generateStatic($get='')
    {
        if($get==false)
            $get=$_GET;
        else if(is_string($get))
            parse_str($get, $get);

        // var_dump($get);
        if(empty($get) || count($get)<1) return '';

        // 计算签名值
        $sigure = $this->calculate($get);
        // 加入queryStr
        $get['sigure']=$sigure;
        // 返回签名queryStr
        return http_build_query($get);
    }


    /*
     * 验证签名
     * $get [必] array|queryStr 要验证的数据（含sigure）
     * return queryStr
     *
     * 示例：
        $str = 'uid=4&nick=abc&sigure=26bca48bcac7573e4d611de1766687c342843778';
        var_dump(\hw\lib\Sigure::validate($str));exit;
    */
    function validateStatic($get='')
    {       
        if(is_string($get)){
            if($get==='')
                $get=$_GET;
            else
                parse_str($get, $get);
        }
        // var_dump($get);
        
        // 未传入
        if(empty($get['sigure'])) return false;

        // 取出传入的签名，删除原有sigure单元（用于计算验证）
        $sigure = $get['sigure'];
        unset($get['sigure']);

        // 传入比较
        // var_dump($sigure, $this->calculate($get));
        return $sigure == $this->calculate($get);
    }

    /*
     * 计算签名
     * $get [必] array 要签名的数据
     * return sigureStr
     *
     * 示例：
        $get=['uid'=>4,'nick'=>'abc',];
        $r=\hw\lib\Sigure::calculate($get);
        var_dump($r);//uid=4&nick=abc&sigure=e6a6b641a48a85910dd67be18a15efb0f8ee643b
     */
    public function calculateStatic($get)
    {
    
        // 加入私钥
        $get['psk']=self::PSK;
        // 排序
        ksort($get);
        // 加密与返回
        return sha1(http_build_query($get));
    }	
 
}
