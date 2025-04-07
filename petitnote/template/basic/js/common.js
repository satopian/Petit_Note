"use strict";
//Petit Note 2021-2025 (c)satopian MIT Licence
//https://paintbbs.sakura.ne.jp/
// コメント入力中画面からの離脱防止
let isForm_Submit = false; //ページ離脱処理で使う
//非同期通信
const res_form_submit = (event, formId = "res_form") => {
    //第二引数が未指定の時はformId = 'res_form'
    let error_message_Id;
    if (formId === "res_form") {
        error_message_Id = "error_message"; //エラーメッセージを表示する箇所のidを指定
    } else if (formId === "image_rep") {
        error_message_Id = "error_message_imgrep";
    } else if (formId === "paint_forme") {
        error_message_Id = "error_message_paintform";
    } else if (formId === "download_forme") {
        error_message_Id = "error_message_download";
    } else if (formId === "before_delete") {
        error_message_Id = "error_message_beforedelete";
    } else if (formId === "aikotoba_form") {
        error_message_Id = "error_message_aikotobaform";
    } else {
        console.error("Invalid form ID specified!");
        return;
    }

    const form = document.getElementById(formId);
    if (form instanceof HTMLFormElement) {
        const submitBtn = form.querySelector('input[type="submit"]');
        const elem_error_message = document.getElementById(error_message_Id);
        if (!elem_error_message || !(submitBtn instanceof HTMLInputElement)) {
            return;
        }
        event.preventDefault(); // 通常フォームの送信を中断
        submitBtn.disabled = true; // 送信ボタンを無効化

        const formData = new FormData(form);
        formData.append("asyncflag", "true"); //画像差し換えそのものは非同期通信で行わない。
        fetch("./", {
            method: "POST",
            mode: "same-origin",
            headers: {
                "X-Requested-With": "asyncflag",
            },
            body: formData,
        })
            .then((response) => {
                if (response.ok) {
                    console.log(response.url);
                    console.log(response.redirected);
                    if (response.redirected) {
                        isForm_Submit = true; //ページ離脱処理で使う
                        return (window.location.href = response.url);
                    }
                    response.text().then((text) => {
                        if (text.startsWith("error\n")) {
                            submitBtn.disabled = false;
                            console.log(text);
                            const error_message = text
                                .split("\n")
                                .slice(1)
                                .join("\n"); //"error\n"を除去
                            return (elem_error_message.innerText =
                                error_message);
                        }
                        if (formId === "aikotoba_form") {
                            return location.reload();
                        }
                        if (formId !== "res_form") {
                            if (formId === "download_forme") {
                                submitBtn.disabled = false;
                            }
                            //ヘッダX-Requested-Withをチェックしてfetchでの投稿をPHP側で中断し、
                            //エラーメッセージが返ってこなければ
                            return form.submit(); // 通常のフォームの送信を実行
                        }
                    });
                    return;
                }
                let response_status = response.status;
                let resp_error_msg = "";
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
                        resp_error_msg = "Bad Gateway";
                        break;
                    case 503:
                        resp_error_msg = "Service Unavailable";
                        break;
                    default:
                        resp_error_msg = "Unknown Error";
                        break;
                }
                submitBtn.disabled = false;
                return (elem_error_message.innerText =
                    response_status + " " + resp_error_msg);
            })
            .catch((error) => {
                submitBtn.disabled = false;
                return (elem_error_message.innerText =
                    "There was a problem with the fetch operation.");
            });
    }
};

// コメント入力中画面からの離脱防止
let isForm_Changed = false;
document.addEventListener("DOMContentLoaded", (e) => {
    isForm_Changed = false;
    const resForm = document.getElementById("res_form");
    if (!resForm) {
        return;
    }
    const textarea = resForm.querySelector("textarea");
    if (textarea) {
        textarea.addEventListener("change", () => {
            isForm_Changed = true;
        });
    }
    window.addEventListener("beforeunload", (e) => {
        if (isForm_Changed && !isForm_Submit) {
            //isForm_Submitは非同期通信で設定
            e.preventDefault();
        }
    });
});

//formDataの送信とリロード
const postFormAndReload = (formData) => {
    fetch("./", {
        method: "post",
        mode: "same-origin",
        body: formData,
    })
        .then((response) => {
            // レスポンスの処理
            console.log("Data sent successfully");
            location.reload();
        })
        .catch((error) => {
            // エラーハンドリング
            console.error("Error:", error);
        });
};

//年齢制限付きの掲示板に設定されている時はボタンを押下するまで表示しない
const view_nsfw = (event) => {
    event.preventDefault(); // 通常フォームの送信を中断
    const formData = new FormData();
    formData.append("mode", "view_nsfw");
    formData.append("view_nsfw", "on");
    postFormAndReload(formData);
};

//年齢確認ボタンを押下するまで表示しない
const age_check = (event) => {
    event.preventDefault(); // 通常フォームの送信を中断
    const formData = new FormData();
    formData.append("mode", "age_check");
    formData.append("agecheck_passed", "on");
    postFormAndReload(formData);
};

//閲覧注意画像を隠す/隠さない
const set_nsfw_show_hide = document.getElementById("set_nsfw_show_hide");
if (set_nsfw_show_hide instanceof HTMLFormElement) {
    set_nsfw_show_hide.addEventListener("change", () => {
        const formData = new FormData(set_nsfw_show_hide);
        postFormAndReload(formData);
    });
}
//ダークモード
const set_darkmode = document.getElementById("set_darkmode");
if (set_darkmode instanceof HTMLFormElement) {
    set_darkmode.addEventListener("change", () => {
        const formData = new FormData(set_darkmode);
        postFormAndReload(formData);
    });
}
//ペイントツールを選択可能にする
const set_app_select_submit = (event) => {
    const set_app_select_enabled = document.getElementById(
        "set_app_select_enabled"
    );
    event.preventDefault(); // 通常フォームの送信を中断
    if (set_app_select_enabled instanceof HTMLFormElement) {
        const formData = new FormData(set_app_select_enabled);
        postFormAndReload(formData);
    }
};

//ファイルが添付されていない時は｢閲覧注意にする｣のチェックボックスを表示しない
const elem_attach_image = document.getElementById("attach_image");
const elem_check_nsfw = document.getElementById("check_nsfw");
const elem_hide_thumbnail = document.getElementById("hide_thumbnail");
const elem_form_submit = document.getElementById("form_submit");
let paint_com = false;
//お絵かきコメント用処理
if (typeof paintcom !== "undefined") {
    paint_com = paintcom;
}

if (elem_form_submit && (elem_attach_image || paint_com)) {
    const updateFormStyle = () => {
        if (
            paint_com ||
            (elem_attach_image instanceof HTMLInputElement &&
                elem_attach_image.files.length > 0)
        ) {
            if (elem_check_nsfw) {
                elem_check_nsfw.style.display = "inline-block"; // チェックボックスを表示
            }
            if (
                elem_hide_thumbnail instanceof HTMLInputElement &&
                elem_hide_thumbnail.checked
            ) {
                elem_form_submit.style.border = "2px solid rgb(255 170 192)"; // ボーダーを設定
                elem_form_submit.style.backgroundColor = "white"; // ボーダーを設定
                elem_form_submit.style.borderRadius = "3px"; // ボーダーを設定
            } else {
                elem_form_submit.style.border = ""; // ボーダーを設定
                elem_form_submit.style.backgroundColor = ""; // ボーダーを設定
                elem_form_submit.style.borderRadius = ""; // ボーダーを設定
            }
        } else {
            elem_form_submit.style.border = ""; // ボーダーを設定
            elem_form_submit.style.backgroundColor = ""; // ボーダーを設定
            elem_form_submit.style.borderRadius = ""; // ボーダーを設定
            if (elem_check_nsfw) {
                elem_check_nsfw.style.display = "none"; // チェックボックスを非表示
            }
        }
    };
    if (elem_attach_image) {
        elem_attach_image.addEventListener("change", updateFormStyle);
    }
    if (elem_hide_thumbnail) {
        elem_hide_thumbnail.addEventListener("change", updateFormStyle);
    }
    document.addEventListener("DOMContentLoaded", updateFormStyle);
}

//shareするSNSのserver一覧を開く
var snsWindow = null; // グローバル変数としてウィンドウオブジェクトを保存する

const open_sns_server_window = (event, width = 600, height = 600) => {
    event.preventDefault(); // デフォルトのリンクの挙動を中断

    // 幅と高さが数値であることを確認
    // 幅と高さが正の値であることを確認
    if (isNaN(width) || width < 300 || isNaN(height) || height < 300) {
        width = 600; // デフォルト値
        height = 600; // デフォルト値
    }
    let url = event.currentTarget.href;
    let windowFeatures = "width=" + width + ",height=" + height; // ウィンドウのサイズを指定

    if (snsWindow && !snsWindow.closed) {
        snsWindow.focus(); // 既に開かれているウィンドウがあればフォーカスする
    } else {
        snsWindow = window.open(url, "_blank", windowFeatures); // 新しいウィンドウを開く
    }
    // ウィンドウがフォーカスを失った時の処理
    snsWindow.addEventListener("blur", () => {
        if (snsWindow.location.href === url) {
            snsWindow.close(); // URLが変更されていない場合はウィンドウを閉じる
        }
    });
};

addEventListener("DOMContentLoaded", () => {
    //URLクエリからresidを取得して指定idへページ内を移動
    const urlParams = new URLSearchParams(window.location.search);
    const resid = urlParams.get("resid");
    const document_resid = resid ? document.getElementById(resid) : null;
    if (document_resid) {
        document_resid.scrollIntoView();
    }
});
window.addEventListener("pageshow", function () {
    // すべてのsubmitボタンを取得
    const submitButtons = document.querySelectorAll('[type="submit"]');
    submitButtons.forEach(function (btn) {
        // ボタンを有効化
        btn.disabled = false;
    });
});
// (c)satopian MIT Licence ここまで

jQuery(function () {
    // https://cotodama.co/pagetop/
    var pagetop = $("#page_top");
    pagetop.hide();
    $(window).on("scroll", function () {
        if ($(this).scrollTop() > 100) {
            //100pxスクロールしたら表示
            pagetop.fadeIn();
        } else {
            pagetop.fadeOut();
        }
    });
    pagetop.on("click", function () {
        $("body,html").animate(
            {
                scrollTop: 0,
            },
            500
        ); //0.5秒かけてトップへ移動
        return false;
    });
    if (typeof lightbox !== "undefined") {
        lightbox.option({
            alwaysShowNavOnTouchDevices: true,
            disableScrolling: true,
            fadeDuration: 0,
            resizeDuration: 500,
            imageFadeDuration: 500,
            wrapAround: true,
        });
    }
});
