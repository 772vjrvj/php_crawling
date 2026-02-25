function login() {

    if ($('input[name="j_username"]').val() == "") {
        alert('아이디를 입력해 주세요.');
        $('input[name="j_username"]').focus();
        return false;
    }
    if ($('input[name="j_password"]').val() == "") {
        alert('비밀번호를 입력해 주세요.');
        $('input[name="j_password"]').focus();
        return false;
    }

    var result = "Y"
    $.ajax({
        url: "https://www.ihappynanum.com/Nanum/nanum/comm/checkLite",
        type: "POST",
        data: {
            orgaId: $('input[name="j_username"]').val(),
            orgaPw: $('input[name="j_password"]').val()
        },
        success: function (data) {
            console.log(data)
            if (data == '') {
                alert("아이디/비밀번호를 확인해주세요");
            } else {

                /*                                                if(data.TRANS_FLAG=='Y'){
                                                                    $("#loginForm").attr('action', "https://lite.ihappynanum.com/welcome");
                                                                    $("#loginForm").submit();
                                                                    result='N'
                                                                }else{
                                                                    $("#loginForm").attr('action',"https://www.ihappynanum.com/Nanum/nanum/user/j_spring_security_check");
                                                                    $("#loginForm").submit();
                                                                    result='N'
                                                                }*/

                grecaptcha.enterprise.ready(function () {
                    grecaptcha.enterprise.execute('6LfpTXYaAAAAALS8nWZYYGhvHet0nxcsolDfZ5Cx', {action: 'login'}).then(function (token) {
                        $.ajax({
                            url: "https://www.ihappynanum.com/Nanum/nanum/comm/checkRecaptchar",
                            type: "POST",
                            data: {
                                token: token,
                                orgaUniqNum: data.ORGA_UNIQ_NUM
                            },
                            success: function (d) {
                                //if(d.body>=0.5){
                                if (d.body > 0) {
                                    if (data.TRANS_FLAG == 'Y') {
                                        $("#loginForm").attr('action', "https://lite.ihappynanum.com/welcome");
                                        $("#loginForm").submit();
                                        result = 'N'
                                    } else {
                                        $("#loginForm").attr('action', "https://www.ihappynanum.com/Nanum/nanum/user/j_spring_security_check");
                                        $("#loginForm").submit();
                                        result = 'N'
                                    }
                                } else {
                                    if ($('input[name="j_username"]').val() == "junkwang"
                                        || $('input[name="j_username"]').val() == "junkwang2"
                                        || $('input[name="j_username"]').val() == "junkwang3"
                                        || $('input[name="j_username"]').val() == "junkwang4"
                                        || $('input[name="j_username"]').val() == "junkwang5"
                                        || $('input[name="j_username"]').val() == "junkwang6"
                                        || $('input[name="j_username"]').val() == "junkwang7"
                                        || $('input[name="j_username"]').val() == "junkwang8"
                                        || $('input[name="j_username"]').val() == "junkwang9"
                                        || $('input[name="j_username"]').val() == "junkwang10"
                                        || $('input[name="j_username"]').val() == "severance"
                                        || $('input[name="j_username"]').val() == "give2020"
                                        || $('input[name="j_username"]').val() == "oicd5879"
                                        || $('input[name="j_username"]').val() == "ibkhappy"
                                        || $('input[name="j_username"]').val() == "hopeon"
                                        || $('input[name="j_username"]').val() == "ynaenea8"
                                        || $('input[name="j_username"]').val() == "kip19300"
                                        || $('input[name="j_username"]').val() == "gnuch1078") {

                                        if (data.TRANS_FLAG == 'Y') {
                                            $("#loginForm").attr('action', "https://lite.ihappynanum.com/welcome");
                                            $("#loginForm").submit();
                                            result = 'N';
                                        } else {
                                            $("#loginForm").attr('action', "https://www.ihappynanum.com/Nanum/nanum/user/j_spring_security_check");
                                            $("#loginForm").submit();
                                            result = 'N';
                                        }
                                    } else {
                                        alert("잘못된 접근입니다.");
                                    }

                                }
                                $("#uid").val("");
                                $("#upw").val("");
                            },
                            error: function (e) {
                            },
                            async: false
                        })
                    });
                });
            }
        },
        error: function (e) {
            return false;
        },
        async: false
    })
    if (result == 'N') {
        return false;
    }
}

/// 엔터키
/// 아이디
$(document).on('keydown', 'input[name="j_username"]', function (e) {
    if (e.keyCode == 13) {
        login();
    }
});
/// 비밀번호
$(document).on('keydown', 'input[name="j_password"]', function (e) {
    if (e.keyCode == 13) {
        login();
    }
});


function memberRegist() {
    window.open("https://www.ihappynanum.com/Nanum/nanum/common/memberRegistOrga", "온라인회원가입신청서", "width=1280,height=800,menubar=no,resizable=yes,toolbar=no,fullscreen=no,location=no,scrollbars=yes");
}

$(function () {
    var cookieValue = "";
    if (document.cookie) {
        var array = document.cookie.split((escape('week') + '='));
        if (array.length >= 2) {
            var arraySub = array[1].split(';');
            cookieValue = unescape(arraySub[0]);
        }
    }
    if (cookieValue == "true") {

        $("#detailWrap").css('display', 'none');
    } else {
        //window.open("https://www.ihappynanum.com/homepage/happy_lite_pop_page.html","해피나눔 라이트","width=500,height=701,menubar=no,resizable=no,toolbar=no,fullscreen=no,location=no,scrollbars=no");
        //window.open("https://www.ihappynanum.com/homepage/happy_arsvisitor_page.html","해피나눔 라이트","width=395,height=571,menubar=no,resizable=no,toolbar=no,fullscreen=no,location=no,scrollbars=no");
        $("#detailWrap").css('display', 'block');
    }

    $("#userRegistOrga").on("click", function () {
        /*window.open("http://localhost:8080/nanum/nanum/common/userRegistOrga","온라인 회원가입 신청서" ,"width=700, height=900, menubar=no, resizable=yes, toolbar=no, fullscreen=no, location=no, scrollbars=yes")*/
        window.open("https://www.ihappynanum.com/Nanum/nanum/common/userRegistOrga", "온라인협력사가입신청서", "width=1280,height=800,menubar=no,resizable=yes,toolbar=no,fullscreen=no,location=no,scrollbars=yes");

    })

    $("#close").click(function () {
        $("#detailWrap").css('display', 'none');
    })
    $("#week").change(function () {
        var array = document.cookie.split((escape('week') + '='));

        if ($("#week").attr('checked') == 'checked') {
            var date = new Date();
            date.setTime(date.getTime() + (7 * 24 * 60 * 60 * 1000))
            document.cookie = "week=true;expires=" + date.toGMTString();
        } else {
            document.cookie = "week=false;expires=" + (new Date(1)).toGMTString();
        }
    })


    /* ars 후원 */
    var cookieValue2 = "";
    if (document.cookie) {
        var array2 = document.cookie.split((escape('week2') + '='));
        if (array2.length >= 2) {
            var arraySub2 = array2[1].split(';');
            cookieValue2 = unescape(arraySub2[0]);
        }
    }
    if (cookieValue2 == "true") {

        $("#detailWrap2").css('display', 'none');
    } else {
        //window.open("https://www.ihappynanum.com/homepage/happy_lite_pop_page.html","해피나눔 라이트","width=500,height=701,menubar=no,resizable=no,toolbar=no,fullscreen=no,location=no,scrollbars=no");
        //window.open("https://www.ihappynanum.com/homepage/happy_arsvisitor_page.html","해피나눔 라이트","width=395,height=571,menubar=no,resizable=no,toolbar=no,fullscreen=no,location=no,scrollbars=no");
        $("#detailWrap2").css('display', 'block');
    }

    $("#close2").click(function () {
        $("#detailWrap2").css('display', 'none');
    })
    $("#week2").change(function () {
        var array2 = document.cookie.split((escape('week2') + '='));

        if ($("#week2").attr('checked') == 'checked') {
            var date2 = new Date();
            date2.setTime(date2.getTime() + (7 * 24 * 60 * 60 * 1000))
            document.cookie = "week2=true;expires=" + date2.toGMTString();
        } else {
            document.cookie = "week2=false;expires=" + (new Date(1)).toGMTString();
        }
    })

    /* ars 후원 */
    var cookieValue3 = "";
    if (document.cookie) {
        var array3 = document.cookie.split((escape('week3') + '='));
        if (array3.length >= 2) {
            var arraySub3 = array3[1].split(';');
            cookieValue3 = unescape(arraySub3[0]);
        }
    }
    if (cookieValue3 == "true") {
        $("#detailWrap3").css('display', 'none');

    } else {
        //window.open("https://www.ihappynanum.com/homepage/happy_lite_pop_page.html","해피나눔 라이트","width=500,height=701,menubar=no,resizable=no,toolbar=no,fullscreen=no,location=no,scrollbars=no");
        //window.open("https://www.ihappynanum.com/homepage/happy_arsvisitor_page.html","해피나눔 라이트","width=395,height=571,menubar=no,resizable=no,toolbar=no,fullscreen=no,location=no,scrollbars=no");
        $("#detailWrap3").css('display', 'block');
        /*var dd = new Date();
        if(dd.getFullYear()*10000+(dd.getMonth()+1)*100+dd.getDate()>20220203){
                    $("#detailWrap3").css('display', 'none');
        }else{
                    $("#detailWrap3").css('display', 'block');
        }*/

    }

    $("#close3").click(function () {
        $("#detailWrap3").css('display', 'none');
    })
    $("#week3").change(function () {
        var array3 = document.cookie.split((escape('week3') + '='));

        if ($("#week3").attr('checked') == 'checked') {
            var date3 = new Date();
            date3.setTime(date3.getTime() + (7 * 24 * 60 * 60 * 1000))
            document.cookie = "week3=true;expires=" + date3.toGMTString();
        } else {
            document.cookie = "week3=false;expires=" + (new Date(1)).toGMTString();
        }
    })
})
