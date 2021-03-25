<?php

return array(
    array(
        'name'    => 'wechat',
        'title'   => '微信',
        'type'    => 'array',
        'content' =>
            array(),
        'value'   => [
            'appid'       => 'wx61f9fds307ec7fd5c', // APP APPID
            'app_id'      => 'wx61f9fds307ec7fd5c', // 公众号 APPID
            'miniapp_id'  => 'wxb3fxxxxxxxxxxx', // 小程序 APPID
            'mch_id'      => '1480000000', //支付商户ID
            'key'         => 'T8sFJghxSKVxPdc35UP9KQqzbkXBcwBq',
            'notify_url'  => '/addons/epay/api/notifyx/paytype/wechat', //请勿修改此配置
            'cert_client' => '/epay/certs/apiclient_cert.pem', // 可选, 退款，红包等情况时需要用到
            'cert_key'    => '/epay/certs/apiclient_key.pem',// 可选, 退款，红包等情况时需要用到
            'log'         => 1,
//            'mode'        => 'dev', // optional,设置此参数，将进入沙箱模式
        ],
        'rule'    => '',
        'msg'     => '',
        'tip'     => '微信参数配置',
        'ok'      => '',
        'extend'  => '',
    ),
    array(
        'name'    => 'alipay',
        'title'   => '支付宝',
        'type'    => 'array',
        'content' =>
            array(),
        'value'   => [
            'app_id'         => '2017110309708888',
            'notify_url'     => '/addons/epay/api/notifyx/paytype/alipay', //请勿修改此配置
            'return_url'     => '/addons/epay/api/returnx/paytype/alipay', //请勿修改此配置
            'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAi87l/zOff3Whnd0Nu0KfrtUTvfC4agBnvF9KPdG+CZORbH5SuhLoI3lVOtReA5vomJ0R4r2ws6oLrp0+tAIt/gCyWrbuEIP672tjN8qgOk6EjfbZMcWvACpm4PdeCLKE+OzQn6R6bTKtYE/NoyfaruSaTFFaNSS164+KuZeE4jUUSur4LSHeuzB6770jVlhe7mL6bgg0NoU1mOhdB5llq1jeJS3Ekgnlh5NSSt2YcCzqkV0mnmGe9UB1ZR6KLB9MJyK2EVmCuxylwbmjCVWqBwPAdMMH5NsoNMq8RvYo9h1WyioPR81b3hoCFl9owEWm7bl6fopgOAB58RdOYKsqgQIDAQAB',


            // 加密方式： **RSA2**
            'private_key'    => 'MIIEpAIBAAKCAQEAvlwOuj2vs6buPeM4aO73HqWK9DiQ9QVr00osnvCs9MejvwfY8aLF0cQE3ZXOrHc6S6Xz78z4SbdAeqxPsMU8MK0hptt6cedIIeG1BlVJmumfk0f5zW/d7ALSSlES7sIebxPHNUnqpZao7xkcRqs5y1Ko2+/4+LfBGDcuUH4BDWRYMzjIFBD7Cyx0pF2eAJgSjVFSpdgaTIjpAC+ggkTWiNIrOba8xLtdP5J14s68s+UKgRa1zMpVtA2CYKEKzMnRmgmQCF5hTuTKpXVbcM6NcElEqFgRzC6xYTXWmApzlLktGidfYjkF/2D+yZHvm7u5Aj3eDyNwHVOEsg9PLrYdlwIDAQABAoIBAQCCEM5zTHC/9KA2IwnJEPZCt2OxKPFKqVCaRsUkOFhEzB/DB+6gc9JsWF3mtVRInRJ028hIIinH3HEvIIs2wh01OSaUJsSMDTZJCDozQJURRu2kqXoyd2wPtYHQC4M/Jd27kaz8aSvtxnpZHDQoyRetKCZ+WIIqFwvVquZ3UxEO/0FK2031wfq0O8ArcebCQpG3PLgubTn7jJXC/SLoQXVqMfEFY3gyDtCqzlsJtEpGgHVprBuXo04a4iDZTIKCe2g8jY920WJcCIcp71oNUaGwBJotRQRbQvGtZmozu+rIrfo/V5jYvCyrJVEYg2GIXIkEx4nStHrIT0vyXakbQUARAoGBAPBy/EPJfC2pYtdA/tnNHXSNHnmnbLKkqzAsDiuvdME0kzjraNVx0uflWOgTgatBwh3+ckaBRQOOgbeDmFDCuYrVvy9RtY6BxReqrlFpMnMfQBhMqXIwTAk3/aCWK9AkpWsIZlWj0OCMYQP3HRcI1eMZAVnUL0+6RG8nJ2jq+j/pAoGBAMqrwrHrBzr8hqwrznx2s/D7z6xMOaFzJwXsoloFJgulgrD56dMlStTxOJ97Bn9/papngDvAlnHVsK4JOWP/wCqMTLurE+grPLYsOHg1fvagpg0pUIY8RevAL9xaXhzD+xEiFpvIIZozAo/9eU+IrmJzDszbczHZBy6M8Agx/IF/AoGAYbe5Qas+pifynVwytj0fvWAkhHhAOpGlaJxe3e4eWu6M7lLtdeEeP1P7v8U9q2W8CAiCVJjwfTOLEBOQ8TFRylR3sDlauaGPgcDBuyAveo22tKljK57pJ83zazHceGiMOWVegWtj1f3252+kCNp0YiilXeZXm+UtLqcQ4xirvxECgYBdLEOQqd0kGA4NvwHppGSrKAjcTBq+h4LsLVKiEfXgqtF/bRU7FczmQpNmdheRq+xMf9KrJanEYZodGG6C84Ozy9ZG/KplNONvWLsJQIbC+S39pP25CKKYdD1Mj1ru3IZi5QoByirwifzml4AauVp6Ni0artSxmPW9R9vd2KUeHwKBgQCp1bbgtvl21HsutPV/Kkbde2yWp//XKgxW74GHU1cBnOdPW+5NTLUmu84IFDwmezjVEy0zbCvugi0hp5OPLLV1bK/BY5fFXcKcyKLUPWBFm9BMcJ/GW+Y91wfQZxYjAOJ6wmlfDR175AADYQNFvj2qrx5OQ8mdutxFIEpwbK2fNw==',

            'log'            => 1,

        ],
        'rule'    => 'required',
        'msg'     => '',
        'tip'     => '支付宝参数配置',
        'ok'      => '',
        'extend'  => '',
    ),
    array(

        'name'    => '__tips__',
        'title'   => '温馨提示',
        'type'    => 'array',
        'content' =>
            array(),
        'value'   => '请注意微信支付证书路径位于/addons/epay/certs目录下，请替换成你自己的证书<br>微信:mch_id为微信商户ID,appid为APP的appid,app_id为公众号的appid,miniapp_id为小程序ID,key为微信商户支付的密钥',
        'rule'    => '',
        'msg'     => '',
        'tip'     => '微信参数配置',
        'ok'      => '',
        'extend'  => '',
    )
);
