//Petit Note 2022-2023 (c)satopian MIT LICENCE
//https://paintbbs.sakura.ne.jp/
function res_form_submit(event, formId = 'res_form') {//第二引数が未指定の時はformId = 'res_form'
	let error_message_Id;
	if (formId === "res_form") {
		error_message_Id = "error_message";//エラーメッセージを表示する箇所のidを指定
	} else if (formId === "image_rep") {
		error_message_Id = "error_message_imgrep";
	} else if (formId === "paint_forme") {
		error_message_Id = "error_message_paintform";
	} else if (formId === "download_forme") {
		error_message_Id = "error_message_download";
	} else if (formId === "before_delete") {
		error_message_Id = "error_message_beforedelete";
	} else {
		console.error("Invalid form ID specified!");
		return;
	}

	const form = document.getElementById(formId);
	const submitBtn = form.querySelector('input[type="submit"]');
	if (form) {
		event.preventDefault(); // 通常フォームの送信を中断
		const formData = new FormData(form);
		formData.append('asyncflag', 'true'); //画像差し換えそのものは非同期通信で行わない。
		fetch("./", {
			method: "POST",
			mode: 'same-origin',
			headers: {
				'X-Requested-With': 'asyncflag',
			},
			body: formData
		})
		.then(response => {
			if (response.ok) {
				console.log(response.url);
				console.log(response.redirected);
				if (response.redirected) {
					submitBtn.disabled = true;
					return window.location.href = response.url;
				}
				submitBtn.disabled = false;
				response.text().then((text) => {
					if (text.startsWith("error\n")) {
							console.log(text);
							const error_message = text.split("\n").slice(1).join("\n");
							return document.getElementById(error_message_Id).innerText = error_message;
					}
					if (formId !== "res_form") {
						//ヘッダX-Requested-Withをチェックしてfetchでの投稿をPHP側で中断し、
						//エラーメッセージが返ってこなければ
						return form.submit(); // 通常のフォームの送信を実行
					}
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
					resp_error_msg = "Bad gateway";
					break;
				case 503:
					resp_error_msg = "Service Unavailable";
					break;
				default:
					resp_error_msg = "Unknown Error";
					break;
			}
			submitBtn.disabled = false;
			return document.getElementById(error_message_Id).innerText = response_status + ' ' + resp_error_msg;

		})
			.catch(error => {
				submitBtn.disabled = false;
				return document.getElementById(error_message_Id).innerText = 'There was a problem with the fetch operation:';
			});
	}
}
// (c)satopian MIT LICENCE ここまで

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
