<?php

namespace Install\Controller;

// 随机数类：
//     银行名称        资源
//     支付公司        资源
//     地址               资源
//     城市               资源
//     姓名               资源
//     手机号码        
//     借记卡
//     信用卡
//     身份证
//     日期
//     数字
//     中文字符串
//     英文字符串
//     图片路径
  
  
class AutoRand 
{
    public $res = array();
    public $res_total = array();
    
    public function __construct()
    {
        $this->res["pay"] = array("富友", "钱宝", "支付宝", "快钱", "易宝支付", "财付通", "宝付", "银联在线", "通联支付", "讯付");
        $this->res_total["pay"] = count($this->res["pay"]);
        
        $this->res["bank"] = array("中国银行", "工商银行", "建设银行", "农业银行", "广发银行", "招商银行", "平安银行",
                 "民生银行", "光大银行", "邮政储蓄");
        $this->res_total["bank"] = count($this->res["bank"]);
        
        $this->res["city"] = array("广州", "上海", "深圳", "北京", "东莞", "佛山", "珠海", "重庆", "成都", "长沙", 
                "南昌", "福州", "厦门", "江门", "天津", "南京");
        $this->res_total["city"] = count($this->res["city"]);
        
        $str = "赵,钱,孙,李,周,吴,郑,王,冯,陈,褚,卫,蒋,沈,韩,杨,朱,秦,尤,许,云,苏,潘,葛,奚,范,彭,郎,鲁,韦,昌,马,苗,凤,花,";
        $str .= "方,俞,任,袁,柳,万俟,司马,上官,欧阳,夏侯,诸葛,子车,温,别,庄,晏,柴,瞿,阎,充,慕,连";
        $this->res["family"] = explode(",", $str);
        $this->res_total["family"] = count($this->res["family"]);
        
        $str = "一,乙,二,十,丁,厂,七,卜,八,人,入,儿,九,几,了,乃,刀,力,又,书,,幻,玉,刊,败,贩,购,图,叮,叶,甲,申,三,干,于";
        $str .= ",亏,士,土,工,才,下,,拐,拖,者,拍,顶,拆,拥,抵,拘,势,抱,垃,拉,拦,幸,拌,招,坡,披,拨,";
        $str .= "久,勺,丸,夕,凡,及,广,亡,门,义,之,尸,已,弓,己,卫,子,也,女,飞,刃,";
        $str .= "习,叉,马,乡,丰,王,井,开,夫,天,寸,丈,大,驼,绍,经,贯,择,抬,泳,泥,沸,波,泼,泽,治,怖,性,怕,怜,怪,学,宝,宗,";
        $str .= "定,宜,审,宙,官,冈,见,手,午,牛,毛,气,升,长,仁, 什,片,仆,化,仇,币,仍,仅,斤,爪,今,凶,分,乏,公,仓,月,";
        $str .= "氏,勿,风,欠,丹,匀,乌,勾,凤,六,文,方,火,昌,畅,明,易,昂,典,固,忠,咐,呼,鸣,咏,呢,岸,岩,帖,罗,帜,岭,";
        $str .= "凯,为,斗,忆,计,订,户,认,心,尺,引,丑,巴,孔,队,办,以,允,予,劝,双";
        $this->res["chinese"] = explode(",", $str);
        
        $this->res_total["chinese"] = count($this->res["family"]);
        
        $ms = explode(" ", microtime());
        srand((float)($ms[0]));
    }
    
    public function bank()
    {
        return $this->res["bank"][mt_rand(0, $this->res_total["bank"] - 1)];
    }
    
    public function pay()
    {
        return $this->res["pay"][mt_rand(0, $this->res_total["pay"] - 1)];
    }
    
    public function proxy()
    {
        return $this->res["city"][mt_rand(0, $this->res_total["city"] - 1)]."代理";
    }
    
    public function sub()
    {
        return $this->res["city"][mt_rand(0, $this->res_total["city"] - 1)]."分站";
    }
    
    public function addr()
    {
        return $this->res["city"][mt_rand(0, $this->res_total["city"] - 1)]."市";
    }
    
    public function uname()
    {
        $name = $this->res["family"][mt_rand(0, $this->res_total["family"] - 1)];
        
        if (strlen($name) == 6)
            $num = mt_rand(1, 2);
        else
            $num = mt_rand(1, 3);
        
        for ($i = 0; $i < $num; $i++)
        {
            $name .= $this->res["chinese"][mt_rand(0, $this->res_total["chinese"] - 1)];
        }
        
        return $name;
    }
    
    static public function number($n = 1)
    {
        $str = mt_rand(1, 9);
        for ($i = 1; $i < $n; $i++)
        {
            $str .= mt_rand(0, 9);
        }
        return $str;
    }
    
    public function phone()
    {
        $sec = array(3, 5, 7, 8);
        return "1".$sec[mt_rand(0, 3)].$this->number(9);
    }
    
    public function card($type = 1)
    {
        if ($type)
//             return CreditCardRand::card();  
            return $this->number(16);
        else
            return $this->number(20);
    }
    
    public function identity()
    {
        $v = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'X');
        return $this->number(6).date("Ymd", mt_rand(strtotime("1960-01-01"), strtotime("1997-01-01"))).
            $this->number(3).$v[array_rand($v)];
    }
    
    public function date($start = "2014-05-01", $end = "2014-06-03", $time = false)
    {
        if ($time)
            return date("Y-m-d H:i:s", mt_rand(strtotime($start), strtotime($end)));
        return date("Y-m-d", mt_rand(strtotime($start), strtotime($end)));
    }
    
    public function txt($n = 1)
    {
        $str = "";
        for ($i = 0; $i < $n; $i++)
        {
            $str .= $this->res["chinese"][mt_rand(0, $this->res_total["chinese"] - 1)];
        }
        return $str;
    }
}
