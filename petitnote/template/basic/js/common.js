function res_form_submit(event){
	const form = document.getElementById("res_form");
	const submitBtn = form.querySelector('input[type="submit"]');
	if(form){
		event.preventDefault(); // フォームの送信を中断
		const formData = new FormData(form);
		fetch("./", {
			method: "POST",
			mode: 'same-origin',
		headers: {
			'X-Requested-With': 'validate'
			,
		},
		body: formData
		})
		.then(response => {
			if (response.ok) {
				console.log(response.url); 
				console.log(response.redirected); 
				if(response.redirected){
					submitBtn.disabled = true;
					return window.location.href=response.url;
				}
				submitBtn.disabled = false; 
				response.text().then((text) => {
				console.log(text);		
				return document.getElementById('error_message').innerHTML='<div>'+text+'</div>';
				})
				return; 
			}
			let response_status = response.status; 
			let resp_error_msg = '';
			switch (response_status) {
				case 400:
				resp_error_msg = "Bad Request";
				  break;
				case 401:
				  resp_error_msg = "Unauthorized";
				  break;
				case 403:
				  resp_error_msg = "Forbidden";
				  break;
				case 404:
				  resp_error_msg = "Not Found";
				  break;
				case 500:
				  resp_error_msg = "Internal Server Error";
				  break;
				case 502:
					resp_error_msg = "bad gateway";
					break;
				case 503:
				  resp_error_msg = "Service Unavailable";
				  break;
				default:
				  resp_error_msg = "Unknown Error";
				  break;
			  }
			submitBtn.disabled = false;
			return document.getElementById('error_message').innerHTML='<div>'+response_status+' '+resp_error_msg+'</div>';

		})
		.catch(error => {
			submitBtn.disabled = false;
			return document.getElementById('error_message').innerHTML='<div>There was a problem with the fetch operation:</div>';
		});
	}
}

jQuery(function() {
	window.onpageshow = function(){
		//URLクエリからresidを取得して指定idへページ内を移動
		const urlParams = new URLSearchParams(window.location.search);
		const resid = urlParams.get('resid');
		const document_res_id = document.getElementById(resid);
		if(document_res_id){
			document_res_id.scrollIntoView();
		}
		var $btn = $('[type="submit"]');
		//disbledを解除
		$btn.prop("disabled", false);
		$btn.click(function(){//送信ボタン2度押し対策
			$(this).prop('disabled',true);
			$(this).closest('form').submit();
		});
	}
	// https://cotodama.co/pagetop/
	var pagetop = $('#page_top');   
	pagetop.hide();
	$(window).scroll(function () {
		if ($(this).scrollTop() > 100) {  //100pxスクロールしたら表示
			pagetop.fadeIn();
		} else {
			pagetop.fadeOut();
		}
	});
	pagetop.click(function () {
		$('body,html').animate({
			scrollTop: 0
		}, 500); //0.5秒かけてトップへ移動
		return false;
	});
	// https://www.webdesignleaves.com/pr/plugins/luminous-lightbox.html
	const luminousElems = document.querySelectorAll('.luminous');
	//取得した要素の数が 0 より大きければ
	if( luminousElems.length > 0 ) {
		luminousElems.forEach( (elem) => {
		new Luminous(elem);
		});
	}
});
