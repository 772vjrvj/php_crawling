<?php
/**
 * ihappynanum - login -> JSESSIONID -> mem0302List API call
 * - 쿠키 파일 저장 없음 (메모리로만 Cookie 헤더 사용)
 * - 응답 JSON 그대로 출력
 */

// === 신규 === 전역 계정 (테스트용)
const USER_ID = "kdjnp5660";
const USER_PW = "rlaeownd5660!";

// === 신규 === 공통 UA
const UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36";

function curl_request(string $method, string $url, array $headers, ?string $body = null): array
{
    $ch = curl_init($url);

    $opts = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => array_values(array_filter($headers, fn($h) => is_string($h) && trim($h) !== "")),
        CURLOPT_ENCODING => "",

        // POSTFIELDS는 GET에도 넣을 필요 없음
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => false,

        // === 테스트용(원복 권장): SSL CA 문제 회피 ===
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ];

    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = $body;
    }

    curl_setopt_array($ch, $opts);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ["ok" => false, "error" => $err];
    }

    $info = curl_getinfo($ch);
    $hs = $info["header_size"] ?? 0;

    $respHeader = substr($resp, 0, $hs);
    $respBody = substr($resp, $hs);

    curl_close($ch);

    return [
        "ok" => true,
        "status" => (int)($info["http_code"] ?? 0),
        "resp_header" => $respHeader,
        "body" => $respBody,
        "body_len" => strlen($respBody),
    ];
}

function extract_cookie_pairs(string $respHeader): array
{
    // Set-Cookie: KEY=VALUE; ... => ["KEY" => "VALUE"]
    $cookies = [];
    if (preg_match_all('/^Set-Cookie:\s*([^;=\s]+)=([^;]*)/mi', $respHeader, $m)) {
        for ($i = 0; $i < count($m[1]); $i++) {
            $k = trim($m[1][$i]);
            $v = trim($m[2][$i]);
            if ($k !== "") $cookies[$k] = $v;
        }
    }
    return $cookies;
}

function cookie_header_from_pairs(array $pairs): string
{
    $items = [];
    foreach ($pairs as $k => $v) {
        $items[] = $k . "=" . $v;
    }
    return implode("; ", $items);
}

function find_location(string $respHeader): string
{
    if (preg_match('/^Location:\s*(.+)\s*$/mi', $respHeader, $m)) {
        return trim($m[1]);
    }
    return "";
}

function login_get_jsessionid(): string
{
    $loginUrl = "https://www.ihappynanum.com/Nanum/nanum/user/j_spring_security_check";

    $postBody = http_build_query([
        "j_username" => USER_ID,
        "j_password" => USER_PW,
    ], "", "&", PHP_QUERY_RFC3986);

    $headers = [
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-Type: application/x-www-form-urlencoded",
        "Origin: https://www.ihappynanum.com",
        "Referer: https://www.ihappynanum.com/",
        "User-Agent: " . UA,
    ];

    $r = curl_request("POST", $loginUrl, $headers, $postBody);
    if (!$r["ok"]) {
        throw new RuntimeException("login curl error: " . $r["error"]);
    }

    // 보통 302 + Set-Cookie(JSESSIONID)
    $cookies = extract_cookie_pairs($r["resp_header"]);
    $jsid = $cookies["JSESSIONID"] ?? "";

    // 디버그 출력
    echo "LOGIN status=" . $r["status"] . PHP_EOL;
    echo "LOGIN location=" . (find_location($r["resp_header"]) ?: "(none)") . PHP_EOL;
    echo "LOGIN JSESSIONID=" . ($jsid !== "" ? $jsid : "(none)") . PHP_EOL;
    echo PHP_EOL;

    return $jsid;
}

function call_mem0302List(string $jsessionid): string
{
    $url = "https://www.ihappynanum.com/Nanum/nanum/user/mem/mem0302List";

    $payload = http_build_query([
        "startDate" => "2021",
        "searchCond" => "all",
        "sTxt" => "",
        "sTxt2" => "",
        "numberOfRows" => "50",
        "currentPage" => "1",
    ], "", "&", PHP_QUERY_RFC3986);

    // 너가 준 헤더 기반 (AJAX)
    $headers = [
        "Accept: application/json, text/javascript, */*; q=0.01",
        "Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-Type: application/x-www-form-urlencoded",
        "Origin: https://www.ihappynanum.com",
        "Referer: https://www.ihappynanum.com/Nanum/nanum/user/mem/Mem0302.nanum",
        "User-Agent: " . UA,
        "X-Requested-With: XMLHttpRequest",
        // sec-ch-ua 류는 필수는 아니지만 원하면 추가 가능
        "Cookie: JSESSIONID=" . $jsessionid,
    ];

    $r = curl_request("POST", $url, $headers, $payload);
    if (!$r["ok"]) {
        throw new RuntimeException("mem0302List curl error: " . $r["error"]);
    }

    echo "API status=" . $r["status"] . PHP_EOL;
    echo "API body_len=" . $r["body_len"] . PHP_EOL;

    // 응답 원문(JSON 문자열) 그대로 반환
    return $r["body"];
}

function main(): void
{
    try {
        $jsid = login_get_jsessionid();
        if ($jsid === "") {
            echo "❌ JSESSIONID를 못 받았습니다. (로그인 실패 또는 정책 차단 가능)" . PHP_EOL;
            return;
        }

        $json = call_mem0302List($jsid);

        echo PHP_EOL . "===== RESPONSE JSON =====" . PHP_EOL;
        echo $json . PHP_EOL;

        // JSON 파싱해서 transactionStatus만 찍고 싶으면:
        $obj = json_decode($json, true);
        if (is_array($obj)) {
            echo PHP_EOL . "transactionStatus=" . (isset($obj["transactionStatus"]) ? var_export($obj["transactionStatus"], true) : "null") . PHP_EOL;
            echo "errorMsg=" . (isset($obj["errorMsg"]) ? var_export($obj["errorMsg"], true) : "null") . PHP_EOL;
        } else {
            echo PHP_EOL . "⚠ json_decode 실패" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . PHP_EOL;
    }
}

main();
