"use strict";
//Petit Note (c)さとぴあ @satopian 2021-2025 MIT License
//https://paintbbs.sakura.ne.jp/

// コメント入力中画面からの離脱防止
let isForm_Submit = false; //ページ離脱処理で使う
//ブラウザの優先言語が日本語以外の時は英語で表示
const lang = (
    navigator.languages?.[0] ||
    navigator.language ||
    ""
).toLowerCase();
const en = lang.startsWith("ja") ? false : true;

//非同期通信
const res_form_submit = (event, formId = "res_form") => {
    event.preventDefault(); // 通常フォームの送信を中断

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

        submitBtn.disabled = true; // 送信ボタンを無効化

        //自動化ツールによる自動送信を拒否する
        const languages_length0 = navigator.languages.length === 0;
        const webdriver = navigator.webdriver;
        if (webdriver || languages_length0) {
            elem_error_message.innerText = en
                ? "The post has been rejected."
                : "拒絶されました。";
            submitBtn.disabled = false; // 再度有効化しておく
            return;
        }

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
                            isForm_Submit = true; //ページ離脱処理で使う
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
                return (elem_error_message.innerText = en
                    ? "There was a problem with the fetch operation."
                    : "通信エラーが発生しました。");
            });
    }
};

// コメント入力中画面からの離脱防止
document.addEventListener("DOMContentLoaded", (e) => {
    let isForm_Changed = false;
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
const preview = document.getElementById("attach_preview");
const post_com = document.querySelector("#res_form textarea.post_com");

//添付ファイルを削除するボタン
const removeAttachmentBtn = document.getElementById("remove_attachment_btn");
const removePchAttachmentBtn = document.getElementById(
    "remove_pch_attachment_btn"
);

const clear_css_preview = () => {
    if (preview instanceof HTMLImageElement) {
        preview.style.border = ""; // ボーダーを設定
        preview.style.backgroundColor = ""; // ボーダーを設定
        preview.style.borderRadius = ""; // ボーダーを設定
        preview.style.margin = "";
        preview.style.backgroundColor = "";
        preview.style.maxWidth = "";
        preview.style.maxHeight = "";
        preview.style.height = "";
        preview.src = ""; // メモリ上の画像を表示
        preview.style.display = "none";
    }
    if (elem_attach_image instanceof HTMLInputElement) {
        elem_attach_image.value = "";
    }
};
const clear_textarea_height = () => {
    if (post_com instanceof HTMLTextAreaElement) {
        post_com.style.minHeight = ""; //テキストエリアの幅を元に戻す
    }
};

const clear_css_form_submit = () => {
    if (elem_form_submit instanceof HTMLInputElement) {
        elem_form_submit.style.border = ""; // ボーダーを設定
        elem_form_submit.style.backgroundColor = ""; // ボーダーを設定
        elem_form_submit.style.borderRadius = ""; // ボーダーを設定
    }
};

//ファイルサイズチェック
const file_size_check = (
    form_id,
    error_messageid,
    elem_attach_image,
    removeAttachmentBtnId = ""
) => {
    const form = document.getElementById(form_id);
    const max_file_size = form?.querySelector('input[name="MAX_FILE_SIZE"]');
    const removeAttachmentBtn = document.getElementById(removeAttachmentBtnId);
    let maxSize = 0;
    if (max_file_size instanceof HTMLInputElement) {
        maxSize = parseInt(max_file_size?.value ?? "0", 10); //10進数に変換
    }
    const file =
        elem_attach_image instanceof HTMLInputElement
            ? elem_attach_image?.files?.[0]
            : null;
    const elem_error_message = document.getElementById(error_messageid);
    if (elem_error_message) {
        if (maxSize && file && file.size > maxSize) {
            if (elem_attach_image instanceof HTMLInputElement) {
                console.log("File size exceeds the maximum limit.");
                elem_error_message.innerText = en
                    ? "The file is too large."
                    : "ファイルサイズが大きすぎます。";
                clear_css_preview();
                if (
                    removeAttachmentBtn &&
                    removeAttachmentBtn instanceof HTMLElement
                ) {
                    removeAttachmentBtn.style.display = "none"; // 添付ファイル削除ボタンを非表示
                }
                if (elem_attach_image instanceof HTMLInputElement) {
                    elem_attach_image.value = "";
                }
                return;
            }
        }
        elem_error_message.innerText = ""; //エラーメッセージをクリア
    }
};

const paint_form = document.getElementById("paint_forme"); //スペルミスだが変更できない
const paint_form_fileInput = paint_form?.querySelector('input[type="file"]');

paint_form_fileInput?.addEventListener("change", () => {
    if (
        paint_form_fileInput instanceof HTMLInputElement &&
        paint_form_fileInput.files &&
        paint_form_fileInput.files.length > 0
    ) {
        if (removePchAttachmentBtn) {
            removePchAttachmentBtn.style.display = "inline-block";
        }
        file_size_check(
            "paint_forme",
            "error_message_paintform",
            paint_form_fileInput,
            "remove_pch_attachment_btn"
        );
    } else {
        if (removePchAttachmentBtn) {
            removePchAttachmentBtn.style.display = "none";
        }
    }
});
removePchAttachmentBtn?.addEventListener("click", (e) => {
    removePchAttachmentBtn.style.display = "none";
    if (paint_form_fileInput instanceof HTMLInputElement) {
        paint_form_fileInput.value = "";
    }
});

const image_rep_form = document.getElementById("image_rep"); //スペルミスだが変更できない
const image_rep_form_fileInput =
    image_rep_form?.querySelector('input[type="file"]');

image_rep_form_fileInput?.addEventListener("change", () => {
    file_size_check(
        "image_rep",
        "error_message_imgrep",
        image_rep_form_fileInput
    );
});

let paint_com = false;
let setAll_Nsfw = false;
//お絵かきコメント用処理
if (typeof paintcom !== "undefined") {
    paint_com = paintcom;
}
if (typeof setAllNsfw !== "undefined") {
    setAll_Nsfw = setAllNsfw;
}

if (elem_form_submit && (elem_attach_image || paint_com)) {
    const file_attach_image_change = () => {
        if (
            elem_attach_image instanceof HTMLInputElement &&
            elem_attach_image.files &&
            elem_attach_image.files.length > 0
        ) {
            //ファイルサイズチェック
            file_size_check(
                "res_form",
                "error_message",
                elem_attach_image,
                "remove_attachment_btn"
            );
            const file =
                elem_attach_image instanceof HTMLInputElement
                    ? elem_attach_image?.files?.[0]
                    : null;

            //画像プレビュー表示

            const elem_error_message = document.getElementById("error_message");
            const error = () => {
                if (elem_error_message) {
                    elem_error_message.innerText = en
                        ? "This file is an unsupported format."
                        : "対応していないファイル形式です。";
                    clear_css_preview();
                    clear_textarea_height();
                    if (removeAttachmentBtn) {
                        removeAttachmentBtn.style.display = "none";
                    }
                    return;
                }
            };

            const reader = new FileReader();

            reader.onload = (e) => {
                if (reader && preview instanceof HTMLImageElement) {
                    const result = e.target && e.target.result;
                    if (typeof result === "string") {
                        const testImg = new Image();
                        testImg.src = result;
                        testImg.onload = () => {
                            if (testImg.naturalWidth <= 0) {
                                error();
                            }

                            preview.src = result; // メモリ上の画像を表示
                            preview.style.margin = "5px";
                            preview.style.backgroundColor = "white";
                            preview.style.maxWidth = "180px";
                            preview.style.maxHeight = "200px";
                            preview.style.borderRadius = "3px";
                            preview.style.height = "fit-content"; //高さ自動調整
                            preview.style.display = "block"; //表示
                            setTimeout(() => {
                                if (post_com instanceof HTMLTextAreaElement) {
                                    const previewoffsetHeight =
                                        preview.offsetHeight;
                                    post_com.style.minHeight =
                                        previewoffsetHeight > 75
                                            ? previewoffsetHeight + 5 + "px"
                                            : 80 + "px";
                                }
                            }, 10);
                        };
                        testImg.onerror = () => {
                            error();
                        };
                    }
                }
            };
            if (file instanceof Blob) {
                reader.readAsDataURL(file);
            }
        } else {
            clear_css_preview();
            clear_textarea_height();
        }
    };

    const updateFormStyle = () => {
        //閲覧注意に設定されている時は枠線を付ける
        if (
            paint_com ||
            (elem_attach_image instanceof HTMLInputElement &&
                elem_attach_image.files &&
                elem_attach_image.files.length > 0)
        ) {
            const paintComPreview = document.getElementById(
                "paintcom_attach_preview"
            );

            if (
                (elem_hide_thumbnail instanceof HTMLInputElement &&
                    elem_hide_thumbnail.checked) ||
                //すべての画像を閲覧注意にする設定が有効な時は
                //閲覧注意画像の表示/非表示の設定があり、かつ投稿フォームに閲覧注意にする設定が存在しない。
                (set_nsfw_show_hide instanceof HTMLFormElement &&
                    !elem_hide_thumbnail)||
                    //すべて閲覧注意
                    setAll_Nsfw
            ) {
                if (preview instanceof HTMLImageElement) {
                    preview.style.border = "2px solid rgb(255 170 192)"; // ボーダーを設定
                }
                if (paintComPreview instanceof HTMLImageElement) {
                    paintComPreview.style.border = "2px solid rgb(255 170 192)";
                }
            } else {
                if (preview instanceof HTMLImageElement) {
                    preview.style.border = "2px dashed rgb(229 242 255)"; // ボーダーを設定
                }
                if (paintComPreview instanceof HTMLImageElement) {
                    paintComPreview.style.border =
                        "2px dashed rgb(229 242 255)";
                }
            }
            if (elem_check_nsfw) {
                elem_check_nsfw.style.display = "inline-block"; // チェックボックスを表示
            }
            if (removeAttachmentBtn) {
                removeAttachmentBtn.style.display = "inline-block"; // 添付ファイル削除ボタンを非表示
            }
            if (
                elem_hide_thumbnail instanceof HTMLInputElement &&
                elem_hide_thumbnail.checked
            ) {
                elem_form_submit.style.border = "2px solid rgb(255 170 192)"; // ボーダーを設定
                elem_form_submit.style.backgroundColor = "white"; // ボーダーを設定
                elem_form_submit.style.borderRadius = "3px"; // ボーダーを設定
            } else {
                clear_css_form_submit();
            }
        } else {
            clear_css_form_submit();
            if (elem_check_nsfw) {
                elem_check_nsfw.style.display = "none"; // チェックボックスを非表示
            }
            if (removeAttachmentBtn) {
                removeAttachmentBtn.style.display = "none"; // 添付ファイル削除ボタンを非表示
            }
        }
    };
    if (elem_attach_image) {
        elem_attach_image.addEventListener("change", (e) => {
            updateFormStyle();
            file_attach_image_change();
        });
    }
    if (elem_hide_thumbnail) {
        elem_hide_thumbnail.addEventListener("change", updateFormStyle);
    }
    document.addEventListener("DOMContentLoaded", updateFormStyle);

    if (
        removeAttachmentBtn &&
        preview instanceof HTMLImageElement &&
        elem_attach_image instanceof HTMLInputElement
    ) {
        removeAttachmentBtn.addEventListener("click", (e) => {
            if (elem_check_nsfw) {
                elem_check_nsfw.style.display = "none"; // チェックボックスを非表示
            }
            removeAttachmentBtn.style.display = "none";
            clear_css_preview();
            clear_textarea_height();
            clear_css_form_submit();
        });
    }
    window.addEventListener("pageshow", () => {
        clear_css_preview();
        if (paint_form_fileInput instanceof HTMLInputElement) {
            paint_form_fileInput.value = "";
        }
    });
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
window.addEventListener("pageshow", () => {
    // すべてのsubmitボタンを取得
    const submitButtons = document.querySelectorAll('[type="submit"]');
    submitButtons.forEach((btn) => {
        if (btn instanceof HTMLInputElement) {
            // ボタンを有効化
            btn.disabled = false;
        }
    });
});

//スクロールすると出てくるトップに戻るボタン
document.addEventListener("DOMContentLoaded", () => {
    const pagetop = document.getElementById("page_top");
    let scrollTimeout; // スクロールが停止したタイミングをキャッチするタイマー
    if (!pagetop) {
        return; // pagetopが存在しない場合は処理を終了
    }
    // 初期状態で非表示
    const cssOpacity = getComputedStyle(pagetop).opacity; // CSSから最大opacity取得
    // CSSで設定されているopacityの値を動的に取得（上限として使用）
    const maxOpacity = parseFloat(cssOpacity);
    pagetop.style.visibility = "hidden"; // 初期状態で非表示
    pagetop.style.opacity = "0"; // 初期opacityを0に設定

    // フェードイン/フェードアウトを管理する関数
    const fade = (el, to, duration = 500) => {
        const startOpacity = parseFloat(el.style.opacity || 0);
        let startTime = performance.now();

        const fadeStep = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            let opacity = startOpacity + (to - startOpacity) * progress;

            // opacityの上限をmaxOpacity（CSSで指定された値）に設定
            opacity = opacity > maxOpacity ? maxOpacity : opacity; // 上限を超えないようにする

            el.style.opacity = opacity.toFixed(2);

            if (progress < 1) {
                requestAnimationFrame(fadeStep);
            } else {
                if (to === 0) {
                    el.style.visibility = "hidden"; // 完全にフェードアウトしたら非表示
                }
            }
        };

        if (to === 1) {
            el.style.visibility = "visible"; // フェードインで表示
        }

        requestAnimationFrame(fadeStep);
    };

    // スクロール時の処理
    window.addEventListener("scroll", () => {
        // スクロール開始後に表示
        if (window.scrollY > 100 && pagetop?.style.visibility === "hidden") {
            fade(pagetop, 1, 500); // 0.5秒でフェードイン
        }

        // スクロール停止後に非表示
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            if (window.scrollY <= 100) {
                fade(pagetop, 0, 200); // 0.2秒でフェードアウト
            }
        }, 200); // 200msの遅延で非表示
    });

    // スムーススクロール
    const smoothScrollToTop = (duration = 500) => {
        // 0.5秒かけてスクロール
        const start = window.scrollY;
        const startTime = performance.now();

        const scrollStep = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 3); // ease-out効果

            window.scrollTo(0, start * (1 - ease));

            if (progress < 1) {
                requestAnimationFrame(scrollStep);
            } else {
                fade(pagetop, 0, 500); // スクロール完了後にフェードアウト
            }
        };

        requestAnimationFrame(scrollStep);
    };

    // トップに戻るボタンがクリックされたとき
    pagetop?.addEventListener("click", (e) => {
        e.preventDefault();
        smoothScrollToTop(500); // 0.5秒でスクロール
    });
});
// (c)satopian MIT Licence ここまで

jQuery(function () {
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
