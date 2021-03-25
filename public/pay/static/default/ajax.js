/**
 * 异步提交
 */
var Ajax = {
		
	post : function(params) {
	
		var url = params.url;
		var content = params.data;
		var dataType = params.dataType;
		
		if (typeof(params.data) == "object") {
			content = "";
			for (var key in params.data) {
				
				content += "&" + key + "=" + params.data[key];
			}
			content = content.substring(1, content.length);
//			content = JSON.stringify(params.data);
//			headerContent = "application/json";
		}

		var req = Ajax.createXMLHTTPRequest();
		if (req) {
			req.open("POST", url, true);
			req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8;");
			req.send(content);
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					if (req.status == 200) {
						var response = req.responseText;
						var call = params.success;
						if (null != call) {
							if (dataType == "json") {
								call(JSON.parse(response));
							} else {
								call(response);
							}
						}
					} else {
						
					}
				}
			}
		}
		
	},

	get : function(params) {

		var url = params.url;
		var dataType = params.dataType;

		var req = Ajax.createXMLHTTPRequest();
		
		if (req) {
			req.open("GET", url, true);
			req.setRequestHeader("Content-Type",
					"application/x-www-form-urlencoded; charset=utf-8;");
			req.onreadystatechange = function() {
				if (req.readyState == 4) {
					if (req.status == 200) {
						var response = req.responseText;
						var call = params.success;
						if (null != call) {
							if (dataType == "json") {
								call(JSON.parse(response));
							} else {
								call(response);
							}
						}
					} else {

					}
				}
			}
			req.send();
		}
	},

	createXMLHTTPRequest : function() {
		// 1.创建XMLHttpRequest对象
		// 这是XMLHttpReuquest对象无部使用中最复杂的一步
		// 需要针对IE和其他类型的浏览器建立这个对象的不同方式写不同的代码
		var xmlHttpRequest;
		if (window.XMLHttpRequest) {
			// 针对FireFox，Mozillar，Opera，Safari，IE7，IE8
			xmlHttpRequest = new XMLHttpRequest();
			// 针对某些特定版本的mozillar浏览器的BUG进行修正
			if (xmlHttpRequest.overrideMimeType) {
				xmlHttpRequest.overrideMimeType("text/xml");
			}
		} else if (window.ActiveXObject) {
			// 针对IE6，IE5.5，IE5
			// 两个可以用于创建XMLHTTPRequest对象的控件名称，保存在一个js的数组中
			// 排在前面的版本较新
			var activexName = [ "MSXML2.XMLHTTP", "Microsoft.XMLHTTP" ];
			for (var i = 0; i < activexName.length; i++) {
				try {
					// 取出一个控件名进行创建，如果创建成功就终止循环
					// 如果创建失败，回抛出异常，然后可以继续循环，继续尝试创建
					xmlHttpRequest = new ActiveXObject(activexName[i]);
					if (xmlHttpRequest) {
						break;
					}
				} catch (e) {
				}
			}
		}
		return xmlHttpRequest;
	}
}