
/**
 * 截取地址栏的参数
 */
function getUrlParam() {
	
	var data = {};
	var currentUrl = location.href;
	var index = currentUrl.indexOf("?");
	if (index == -1) {
		return data;
	}
	
	var params = currentUrl.substring(index + 1);
	var paramsSeq = params.split("&");
	
	for (var i = 0, j = paramsSeq.length; i < j; i++) {
		var split = paramsSeq[i].split("=");
		var k = split[0];
		var v = decodeURI(split[1]);
		data[k] = v;
	}
	
	return data;
}

/*数字键盘*/
function check(){
	/*输入金额*/
	var oEnter = document.getElementsByClassName("enter");
	var oAmount = document.getElementById("amount");
	var oDelete = document.getElementById("can_r");
	var oDeleteImet = document.getElementById("can_rImet");
	var oPoint = document.getElementById("point");
	var oPointImet = document.getElementById("pointImet");
	console.log("ok");
	for (var i = 0; i<oEnter.length; i++) {
		oEnter[i].ontouchend = function(){
			document.getElementById("placeholder").style.display = "none";
			if(!(oAmount.innerText.indexOf('.') == -1)){
				var pointIndex = oAmount.innerText.indexOf('.');
				var pNum = oAmount.innerText.substr(0,pointIndex);
				var pLength = pNum.length;
				var sNum = oAmount.innerText.substr(pointIndex,oAmount.innerText.length-1);
				var sLength = sNum.length;
				if(pLength > 6){
					return;
				}
				if(sLength > 2){
					return;
				}else{
					var oNum = this.innerText;
					oAmount.innerHTML = oAmount.innerText + oNum;
				}
			}else{
				var pLength = oAmount.innerText.length;
				if(pLength > 5){
					return;
				}
				var oNum = this.innerText;
				oAmount.innerHTML = oAmount.innerText + oNum;
			}
			var payBtns = document.getElementsByClassName("top");
			for (var i = 0 ; i < payBtns.length ; i++) {
				console.log("1");
				payBtns[i].style.opacity="1.0"
			}
		}
	}
	oDelete.ontouchend = function(){
		if (oAmount.innerText.length == 1) {
			document.getElementById("placeholder").style.display = "flex";
				var payBtns = document.getElementsByClassName("top");
			for (var i = 0 ; i < payBtns.length ; i++) {
				console.log("2");
				payBtns[i].style.opacity="0.5"
			}
		}
		oAmount.innerHTML = oAmount.innerText.substr(0,oAmount.innerText.length-1);
		
	}
	oDeleteImet.ontouchend = function(){
		if (oAmount.innerText.length == 1) {
			document.getElementById("placeholder").style.display = "flex";
			var payBtns = document.getElementsByClassName("top");
			for (var i = 0 ; i < payBtns.length ; i++) {
				payBtns[i].style.opacity="0.5"
			}
		}
		oAmount.innerHTML = oAmount.innerText.substr(0,oAmount.innerText.length-1);
		
	}
	oPoint.ontouchend = function(){
		if(oAmount.innerText.indexOf('.') == -1){
			oAmount.innerText = oAmount.innerText + oPoint.innerText;
		}else{
			oAmount.innerHTML = oAmount.innerText;
		}
	}
	oPointImet.ontouchend = function(){
		if(oAmount.innerText.indexOf('.') == -1){
			oAmount.innerText = oAmount.innerText + oPoint.innerText;
		}else{
			oAmount.innerHTML = oAmount.innerText;
		}
	}

	
}
