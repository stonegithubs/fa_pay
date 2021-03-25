<?php
namespace panshi;

class ParamCheckTool
{

    /**
     * @param array $map
     * @param $signkey
     * @param $mer_private_key
     * @param $org_public_key
     * @return string
     */
    public static function sendPack(array $map, $signkey, $mer_private_key, $org_public_key)
    {
        //签名
        $signdata = TdsPayUtil::getSignData($map);
        Log::println("signdata:".$signdata);

		$sign = TdsPayUtil::getShaSign($signdata . $signkey);
		Log::println("sign:".$sign);
        $map['sign'] = $sign;

		$paramStr = json_encode($map);
        Log::println("paramStr:".$paramStr);

        $aesKey = AES::getAutoCreateAESKey();
        Log::println("aesKey:".$aesKey);
        $aes_data = MerchantUtil::encryptDataByAES($paramStr, $aesKey);
        Log::println("aes_data:".$aes_data);

        $aes_key_encrypt = MerchantUtil::encryptDataByRSA($aesKey, $org_public_key);
        Log::println("aes_key_encrypt:" . $aes_key_encrypt);


        $returnMap = [];
        $returnMap['aes_data'] = $aes_data;
        $returnMap['aes_key_encrypt'] = $aes_key_encrypt;


        $signV = MerchantUtil::sign($aesKey, $mer_private_key, "RSA");
        Log::println("signV:" .$signV);

        $returnMap['sign'] = $signV;

        $req_msg = json_encode($returnMap);
        return http_build_query([
            'req_msg'=>$req_msg,
            'partnerId'=>$map['partnerId'],
        ]);
    }

    public static function checkRes($resStr, $signkey, $mer_private_key, $org_public_key)
    {

        $resMap = json_decode($resStr, true);
		$RSP_MSG = $resMap["RSP_MSG"];


        $resMap = json_decode(urldecode($RSP_MSG), true);

		$aes_key_encrypt = $resMap["aes_key_encrypt"];
		$aes_data = $resMap["aes_data"];
		$sign = $resMap["sign"];
		Log::println("aes_key_encrypt:".$aes_key_encrypt);
		Log::println("aes_data:".$aes_data);
		Log::println("sign:".$sign);
        $aes_key = MerchantUtil::decryptDataByRSA($aes_key_encrypt, $mer_private_key);
        Log::println("aes_key:".$aes_key);

        //var_dump($aes_key, $sign,  $org_public_key, MerchantUtil::SIGNTYPE_RSA);
        $isTrue = MerchantUtil::verify($aes_key, $sign,  $org_public_key, MerchantUtil::SIGNTYPE_RSA);
        if($isTrue){
            Log::println("验签通过:".json_encode($isTrue));
            $paramStr = MerchantUtil::decryptDataByAES($aes_data, $aes_key);

            $paramStr = str_replace("\t", '\\t', $paramStr);

            $paramMap = json_decode($paramStr, true);

            $signValue = $paramMap["sign"];
            unset($paramMap["sign"]);

            $data = TdsPayUtil::getSignData($paramMap);

            $signdata = TdsPayUtil::getShaSign($data . $signkey);

            //var_dump($paramMap);

            if($signdata == $signValue){
                Log::println("内部验签通过:123");
                return $paramStr;
            }else{
                return "参数验签不符1";
            }
        } else {
            return "证书验签失败";
        }
    }

}