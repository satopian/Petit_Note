function res_form_submit(event){
	const form = document.getElementById("res_form");
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
			let response_status = response.status; 
			if (response.ok) {
				console.log(response.url); 
				console.log(response.redirected); 
				if(response.redirected){
					return window.location.href=response.url;
				}
				form.querySelector('input[type="submit"]').disabled = false; 
				response.text().then((text) => {
				console.log(text);		
				return document.getElementById('error_message').innerHTML=text;	
				})
				return 
			}
			throw new Error("Network response was not ok.");
		})
		.then(data => {
			console.log(data);
		})
		.catch(error => {
			form.querySelector('input[type="submit"]').disabled = false; 
			console.error("There was a problem with the fetch operation:", error);
		});
	}
}

jQuery(function() {
	window.onpageshow = function(){
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
