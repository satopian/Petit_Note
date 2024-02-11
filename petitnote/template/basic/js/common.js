//Petit Note 2021-2023 (c)satopian MIT Licence
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
							const error_message = text.split("\n").slice(1).join("\n");//"error\n"を除去
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
//検索画面設定項目 閲覧注意画像を隠す/隠さない
function form_submit_set_nsfw_show_hide(event) {
	const form = document.getElementById("set_nsfw_show_hide");	
	const submitBtn = form.querySelector('input[type="submit"]');	
	if (form) {
		event.preventDefault(); // 通常フォームの送信を中断
		const formData = new FormData(form);
		fetch("./", {
		method: "post",
		mode: 'same-origin',
		body: formData
		})
		.then(response => {
		// レスポンスの処理
		console.log("Data sent successfully");
		submitBtn.disabled = false;
		location.reload();
		})
		.catch(error => {
		// エラーハンドリング
		console.error("Error:", error);
		submitBtn.disabled = false;
		});
		
		return false;
	}
}

//ファイルが添付されていない時は｢閲覧注意にする｣のチェックボックスを表示しない
const elem_attach_image = document.getElementById("attach_image");
const elem_check_nsfw = document.getElementById("check_nsfw");
const elem_hide_thumbnail = document.getElementById("hide_thumbnail");
const elem_form_submit = document.getElementById("form_submit");

//お絵かきコメント用処理
if (typeof paintcom === "undefined") {
	paintcom = false;
}

if (elem_form_submit && (elem_attach_image||paintcom)) {

	const updateFormStyle = function() {
		if (paintcom || elem_attach_image.files.length > 0){
			if(elem_check_nsfw){
				elem_check_nsfw.style.display = "inline-block"; // チェックボックスを表示
			}
			if(elem_hide_thumbnail && elem_hide_thumbnail.checked){
				elem_form_submit.style.border = "2px solid rgb(255 170 192)"; // ボーダーを設定
				elem_form_submit.style.backgroundColor = "white"; // ボーダーを設定
				elem_form_submit.style.borderRadius = "3px"; // ボーダーを設定
			}else{
				elem_form_submit.style.border = ""; // ボーダーを設定
				elem_form_submit.style.backgroundColor = ""; // ボーダーを設定
				elem_form_submit.style.borderRadius = ""; // ボーダーを設定
			}
		}else{
			elem_form_submit.style.border = ""; // ボーダーを設定
			elem_form_submit.style.backgroundColor = ""; // ボーダーを設定
			elem_form_submit.style.borderRadius = ""; // ボーダーを設定
			if(elem_check_nsfw){
				elem_check_nsfw.style.display = "none"; // チェックボックスを非表示
			}
		}
	};
	if(elem_attach_image){
		elem_attach_image.addEventListener("change", updateFormStyle);
	}
	if(elem_hide_thumbnail){
		elem_hide_thumbnail.addEventListener("change", updateFormStyle);
	}
	document.addEventListener("DOMContentLoaded",updateFormStyle);	
}

//shareするSNSのserver一覧を開く
var snsWindow = null; // グローバル変数としてウィンドウオブジェクトを保存する

function open_sns_server_window(event,width=350,height=490) {
	event.preventDefault(); // デフォルトのリンクの挙動を中断

	// 幅と高さが数値であることを確認
	// 幅と高さが正の値であることを確認
	if (isNaN(width) || width <= 0 || isNaN(height) || height <= 0) {
		width = 350; // デフォルト値
		height = 490; // デフォルト値
	}		
	var url = event.currentTarget.href;
	var windowFeatures = "width="+width+",height="+height; // ウィンドウのサイズを指定
	
	if (snsWindow && !snsWindow.closed) {
		snsWindow.focus(); // 既に開かれているウィンドウがあればフォーカスする
		} else {
		snsWindow = window.open(url, "_blank", windowFeatures); // 新しいウィンドウを開く
		}
}
// (c)satopian MIT Licence ここまで

jQuery(function() {

	//URLクエリからresidを取得して指定idへページ内を移動
	const urlParams = new URLSearchParams(window.location.search);
	const resid = urlParams.get('resid');
	const document_resid = document.getElementById(resid);
	if(document_resid){
		document_resid.scrollIntoView();
	}

	window.onpageshow = function(){

		$('[type="submit"]').each(function() {
			const $btn = $(this);
			const $form = $btn.closest('form');
			const isTargetBlank = $form.prop('target') === '_blank';
		
			$btn.prop('disabled', false);
			// ボタンが target="_blank" の場合は無効化しない
			if (!isTargetBlank) {
				$btn.on('click', function() {//ボタンをクリックすると
				$btn.prop('disabled', true);//ボタンを無効化して
				$form.trigger('submit');//送信する
				});
			}
		});
	};

	// https://cotodama.co/pagetop/
	var pagetop = $('#page_top');
	pagetop.hide();
	$(window).on('scroll',function () {
		if ($(this).scrollTop() > 100) {  //100pxスクロールしたら表示
			pagetop.fadeIn();
		} else {
			pagetop.fadeOut();
		}
	});
	pagetop.on('click', function () {
		$('body,html').animate({
			scrollTop: 0
		}, 500); //0.5秒かけてトップへ移動
		return false;
	});
	if(typeof lightbox!=='undefined'){
		lightbox.option({
		'alwaysShowNavOnTouchDevices': true,
		'disableScrolling': true,
		'fadeDuration': 0,
		'resizeDuration': 500,
		'imageFadeDuration': 500,
		'wrapAround': true
		});
	}
});
