hljs.initHighlighting()

var _hmt = _hmt || [];
(function () {
    var hm = document.createElement("script");
    hm.src = "https://hm.baidu.com/hm.js?f0f276cefa10aa31a20ae3815a50b795";
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(hm, s);
})();

function getCookie(name) {
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");

    if (arr = document.cookie.match(reg))

        return unescape(arr[2]);
    else
        return null;
}

function setCookie(name, value) {
    var Days = 30;
    var exp = new Date();
    exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
}

s_theme=getCookie("_theme");
if(s_theme === undefined) s_theme = "default";
document.body.setAttribute("data-md-color-scheme", s_theme)
var name = document.querySelector("#__code_0 code span:nth-child(7)")
name.textContent = s_theme

s_primary=getCookie("_primary_color");
document.body.setAttribute("data-md-color-primary", s_primary);
var name2 = document.querySelector("#__code_2 code span:nth-child(7)");
name2.textContent = s_primary.replace("-", " ");
