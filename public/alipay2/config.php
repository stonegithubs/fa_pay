<?php
$config = array (	
		//应用ID,您的APPID。
		'app_id' => "2017110309708888",

		//商户私钥
		'merchant_private_key' => "MIIEpAIBAAKCAQEAvlwOuj2vs6buPeM4aO73HqWK9DiQ9QVr00osnvCs9MejvwfY8aLF0cQE3ZXOrHc6S6Xz78z4SbdAeqxPsMU8MK0hptt6cedIIeG1BlVJmumfk0f5zW/d7ALSSlES7sIebxPHNUnqpZao7xkcRqs5y1Ko2+/4+LfBGDcuUH4BDWRYMzjIFBD7Cyx0pF2eAJgSjVFSpdgaTIjpAC+ggkTWiNIrOba8xLtdP5J14s68s+UKgRa1zMpVtA2CYKEKzMnRmgmQCF5hTuTKpXVbcM6NcElEqFgRzC6xYTXWmApzlLktGidfYjkF/2D+yZHvm7u5Aj3eDyNwHVOEsg9PLrYdlwIDAQABAoIBAQCCEM5zTHC/9KA2IwnJEPZCt2OxKPFKqVCaRsUkOFhEzB/DB+6gc9JsWF3mtVRInRJ028hIIinH3HEvIIs2wh01OSaUJsSMDTZJCDozQJURRu2kqXoyd2wPtYHQC4M/Jd27kaz8aSvtxnpZHDQoyRetKCZ+WIIqFwvVquZ3UxEO/0FK2031wfq0O8ArcebCQpG3PLgubTn7jJXC/SLoQXVqMfEFY3gyDtCqzlsJtEpGgHVprBuXo04a4iDZTIKCe2g8jY920WJcCIcp71oNUaGwBJotRQRbQvGtZmozu+rIrfo/V5jYvCyrJVEYg2GIXIkEx4nStHrIT0vyXakbQUARAoGBAPBy/EPJfC2pYtdA/tnNHXSNHnmnbLKkqzAsDiuvdME0kzjraNVx0uflWOgTgatBwh3+ckaBRQOOgbeDmFDCuYrVvy9RtY6BxReqrlFpMnMfQBhMqXIwTAk3/aCWK9AkpWsIZlWj0OCMYQP3HRcI1eMZAVnUL0+6RG8nJ2jq+j/pAoGBAMqrwrHrBzr8hqwrznx2s/D7z6xMOaFzJwXsoloFJgulgrD56dMlStTxOJ97Bn9/papngDvAlnHVsK4JOWP/wCqMTLurE+grPLYsOHg1fvagpg0pUIY8RevAL9xaXhzD+xEiFpvIIZozAo/9eU+IrmJzDszbczHZBy6M8Agx/IF/AoGAYbe5Qas+pifynVwytj0fvWAkhHhAOpGlaJxe3e4eWu6M7lLtdeEeP1P7v8U9q2W8CAiCVJjwfTOLEBOQ8TFRylR3sDlauaGPgcDBuyAveo22tKljK57pJ83zazHceGiMOWVegWtj1f3252+kCNp0YiilXeZXm+UtLqcQ4xirvxECgYBdLEOQqd0kGA4NvwHppGSrKAjcTBq+h4LsLVKiEfXgqtF/bRU7FczmQpNmdheRq+xMf9KrJanEYZodGG6C84Ozy9ZG/KplNONvWLsJQIbC+S39pP25CKKYdD1Mj1ru3IZi5QoByirwifzml4AauVp6Ni0artSxmPW9R9vd2KUeHwKBgQCp1bbgtvl21HsutPV/Kkbde2yWp//XKgxW74GHU1cBnOdPW+5NTLUmu84IFDwmezjVEy0zbCvugi0hp5OPLLV1bK/BY5fFXcKcyKLUPWBFm9BMcJ/GW+Y91wfQZxYjAOJ6wmlfDR175AADYQNFvj2qrx5OQ8mdutxFIEpwbK2fNw==",
		
		//异步通知地址
		'notify_url' => "http://pay.kellyjob.cn/api/index",
		
		//同步跳转
		'return_url' => "http://pay.kellyjob.cn/api/index",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvlwOuj2vs6buPeM4aO73HqWK9DiQ9QVr00osnvCs9MejvwfY8aLF0cQE3ZXOrHc6S6Xz78z4SbdAeqxPsMU8MK0hptt6cedIIeG1BlVJmumfk0f5zW/d7ALSSlES7sIebxPHNUnqpZao7xkcRqs5y1Ko2+/4+LfBGDcuUH4BDWRYMzjIFBD7Cyx0pF2eAJgSjVFSpdgaTIjpAC+ggkTWiNIrOba8xLtdP5J14s68s+UKgRa1zMpVtA2CYKEKzMnRmgmQCF5hTuTKpXVbcM6NcElEqFgRzC6xYTXWmApzlLktGidfYjkF/2D+yZHvm7u5Aj3eDyNwHVOEsg9PLrYdlwIDAQAB",
);