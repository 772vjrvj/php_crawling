<?php
/**
 * ihappynanum 로그인 -> mem0302List 호출
 * - 전역에 8개 변수
 * - main()은 8개 파라미터 받음
 * - main()은 JSON 반환
 * - 실행부에서 echo
 */

// =========================
// 공통 CURL
// =========================
function http_request($method, $url, $headers, $body = null)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_ENCODING       => "",
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => false,

        // ⚠ SSL 검증 OFF
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $resp = curl_exec($ch);

    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException($err);
    }

    $info = curl_getinfo($ch);
    $hs   = $info['header_size'];

    $header = substr($resp, 0, $hs);
    $body   = substr($resp, $hs);

    curl_close($ch);

    return [$header, $body];
}

// =========================
// JSESSIONID 추출
// =========================
function extract_jsessionid($header)
{
    if (preg_match('/Set-Cookie:\s*JSESSIONID=([^;]+)/i', $header, $m)) {
        return trim($m[1]);
    }
    return "";
}

// =========================
// main (8개 파라미터)
// =========================
function main(
    $userId,
    $userPw,
    $startDate,
    $searchCond,
    $sTxt,
    $sTxt2,
    $numberOfRows,
    $currentPage
) {

    // 1️⃣ 로그인
    $loginUrl = "https://www.ihappynanum.com/Nanum/nanum/user/j_spring_security_check";

    $loginPayload = http_build_query([
        "j_username" => $userId,
        "j_password" => $userPw
    ]);

    list($loginHeader,) = http_request(
        "POST",
        $loginUrl,
        [
            "Content-Type: application/x-www-form-urlencoded",
            "User-Agent: Mozilla/5.0",
            "Origin: https://www.ihappynanum.com",
            "Referer: https://www.ihappynanum.com/"
        ],
        $loginPayload
    );

    $jsession = extract_jsessionid($loginHeader);

    if ($jsession === "") {
        throw new RuntimeException("로그인 실패 (JSESSIONID 없음)");
    }

    // 2️⃣ API 호출
    $apiUrl = "https://www.ihappynanum.com/Nanum/nanum/user/mem/mem0302List";

    $apiPayload = http_build_query([
        "startDate"    => $startDate,
        "searchCond"   => $searchCond,
        "sTxt"         => $sTxt,
        "sTxt2"        => $sTxt2,
        "numberOfRows" => $numberOfRows,
        "currentPage"  => $currentPage
    ]);

    list(, $apiBody) = http_request(
        "POST",
        $apiUrl,
        [
            "Content-Type: application/x-www-form-urlencoded",
            "User-Agent: Mozilla/5.0",
            "X-Requested-With: XMLHttpRequest",
            "Cookie: JSESSIONID=" . $jsession
        ],
        $apiPayload
    );

    return $apiBody; // JSON 반환
}



// =========================
// 파라미터 (8개)
// =========================
$USER_ID        = "kdjnp5660";
$USER_PW        = "rlaeownd5660!";

$START_DATE     = "2021";           // 기간
$S_TXT2         = "";               // 결제이력이있는회원만조회

$SEARCH_COND    = "all";            // 상세조회 select box 값 all : 전체, memName : 회원명
$S_TXT          = "";               // 상세조회 input 값

$NUMBER_OF_ROWS = "50";             // 페이지 당 갯수 50개씩
$CURRENT_PAGE   = "1";              // 현제 페이지 1

// =========================
// 실행부
// =========================
$json = main(
    $USER_ID,
    $USER_PW,
    $START_DATE,
    $SEARCH_COND,
    $S_TXT,
    $S_TXT2,
    $NUMBER_OF_ROWS,
    $CURRENT_PAGE
);

echo $json;

