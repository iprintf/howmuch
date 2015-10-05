<?php

namespace Install\Controller;

//随机合法信用卡卡号
class CreditCardRand 
{
    public $cardlist;
    
    static public function completed_number($prefix, $length) 
    {
        $ccnumber = $prefix;
        # generate digits
        while ( strlen($ccnumber) < ($length - 1) ) 
        {
            $ccnumber .= rand(0,9);
        }
    
    # Calculate sum
    
        $sum = 0;
        $pos = 0;
    
        $reversedCCnumber = strrev( $ccnumber );
    
        while ( $pos < $length - 1 ) 
        {
            $odd = $reversedCCnumber[ $pos ] * 2;
            if ( $odd > 9 ) 
                $odd -= 9;
    
            $sum += $odd;
    
            if ( $pos != ($length - 2) )
                $sum += $reversedCCnumber[ $pos +1 ];
            $pos += 2;
        }
    # Calculate check digit
    
        $checkdigit = (( floor($sum/10) + 1) * 10 - $sum) % 10;
        $ccnumber .= $checkdigit;
        
        return $ccnumber;
    }
    
    static public function credit_card_number($prefixList, $length, $howMany) 
    {
    
        for ($i = 0; $i < $howMany; $i++) 
        {
            $ccnumber = $prefixList[array_rand($prefixList)];
            $result[] = CreditCardRand::completed_number($ccnumber, $length);
        }
    
        return $result;
    }
    
    static public function card($name = "", $len = 1)
    {
        $cardlist = "";
        
        $cardlist["visa"] = array("len" => array(16, 13),
                "list" => array("4539","4556", "4916", "4532", "4929", "40240071", "4485", "4716", "4"));
        $cardlist["mastercard"] =  array("len" => array(16),
                "list" => array("51", "52", "53", "54", "55"));
        $cardlist["amex"] =  array("len" => array(15), "list" => array("34", "37"));
        $cardlist["discover"] = array("len" => array(16), "list" => array("6011"));
        $cardlist["diners"] =  array("len" => array(14), "list" => array("300", "301", "302", "303", "36", "38"));
        $cardlist["enRoute"] =  array("len" => array(15), "list" => array("2014", "2149"));
        $cardlist["jcb"] =  array("len" => array(16), "list" => array("35"));
        $cardlist["voyager"] = array("len" => array(15), "list" => array("8699"));
        
        if ($name)
            $data = $cardlist[$name];
        else
            $data = $cardlist[array_rand($cardlist)];
        
        dump($data);
        
        $ret = CreditCardRand::credit_card_number($data["list"], $data["len"][mt_rand(0, count($data["len"]) - 1)], $len);
        if ($len == 1)
            return $ret[0];
        return $ret;
    }
}