<?php
// main.php
/**
 * 화순군 고시/공고 목록 + 상세 내용 크롤러 (단일 공개 함수: main)
 * - 목록: tables[4]의 <tr> 파싱, 헤더(번호) 스킵
 * - not_ancmt_mgt_no: onclick="searchDetail('37280')"에서 추출
 * - 상세: selectOfrNotAncmt/Regst POST 후 <td style*="word-break:break-all" colspan="4"> 텍스트를 \r\n 개행으로 수집
 * - 2페이지 이후 느려짐 대응:
 *    - cURL COOKIEJAR/COOKIEFILE로 세션 유지
 *    - initValue, countYn 은 1페이지만 Y, 이후 페이지는 N
 *    - 타임아웃 60초, 재시도+지수 백오프, 페이지/상세 사이 소폭 지연
 */

function main(int $pageIndex, int $pageSize): array {
    $URL = "https://eminwon.hwasun.go.kr/emwp/gov/mogaha/ntis/web/ofr/action/OfrAction.do";

    // ——— 세션 쿠키/커넥션 재사용을 위한 static 컨텍스트 ———
//    static $cookieFile = null;
//    if ($cookieFile === null) {
//        $cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "ntis_cookie_" . getmypid() . ".txt";
//        if (!file_exists($cookieFile)) { @touch($cookieFile); }
//    }

    // 요청 헤더 (쿠키 제외). br/zstd는 CURLOPT_ENCODING으로 자동 해제 시도.
    $headers = [
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
        "Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7",
        "Cache-Control: max-age=0",
        "Connection: keep-alive",
        "Content-Type: application/x-www-form-urlencoded",
        "Host: eminwon.hwasun.go.kr",
        "Origin: https://eminwon.hwasun.go.kr",
        "Referer: $URL",
        'Sec-CH-UA: "Google Chrome";v="141", "Not?A_Brand";v="8", "Chromium";v="141"',
        "Sec-CH-UA-Mobile: ?0",
        'Sec-CH-UA-Platform: "Windows"',
        "Sec-Fetch-Dest: document",
        "Sec-Fetch-Mode: navigate",
        "Sec-Fetch-Site: same-origin",
        "Sec-Fetch-User: ?1",
        "Upgrade-Insecure-Requests: 1",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36",
        "Expect:", // 100-continue 지연 방지
    ];

    // === 내부: POST + 재시도(지수 백오프) ===
    $post_retry = function(array $data, int $maxRetries = 3, float $backoffBase = 2.0) use ($URL, $headers, $cookieFile): string {
        $postFields = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        $lastErr = null;

        for ($i = 1; $i <= $maxRetries; $i++) {
            $ch = curl_init($URL);
            curl_setopt_array($ch, [
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $postFields,
                CURLOPT_HTTPHEADER      => $headers,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_ENCODING        => "",     // gzip/deflate 자동해제
                CURLOPT_CONNECTTIMEOUT  => 15,     // connect timeout
                CURLOPT_TIMEOUT         => 60,     // 전체/읽기 타임아웃 여유
                CURLOPT_SSL_VERIFYPEER  => false,  // (임시) 정식 배포 시 CA 적용 권장
                CURLOPT_SSL_VERIFYHOST  => false,
                // 세션 유지(쿠키)
//                CURLOPT_COOKIEJAR       => $cookieFile,
//                CURLOPT_COOKIEFILE      => $cookieFile,
                // keep-alive 힌트
                CURLOPT_FORBID_REUSE    => false,
                CURLOPT_TCP_KEEPALIVE   => 1,
                CURLOPT_TCP_KEEPIDLE    => 30,
                CURLOPT_TCP_KEEPINTVL   => 15,
            ]);
            $resp = curl_exec($ch);
            $errno = curl_errno($ch);
            $err   = curl_error($ch);
            $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno === 0 && $code >= 200 && $code < 300 && is_string($resp) && strlen($resp) > 0) {
                return $resp;
            }
            $lastErr = $err ?: "HTTP $code";
            if ($i < $maxRetries) {
                $sleep = pow($backoffBase, $i - 1); // 1,2,4...
                usleep((int)(($sleep + mt_rand(0, 500)/1000.0) * 1_000_000)); // jitter
            }
        }
        return "";
    };

    // === 1) 목록 요청 (★ 1페이지만 initValue/countYn = Y) ===
    $isFirst = ($pageIndex === 1);
    $listPayload = [
        "pageIndex"           => (string)$pageIndex,
        "jndinm"              => "OfrNotAncmtEJB",
        "context"             => "NTIS",
        "method"              => "selectListOfrNotAncmt",
        "methodnm"            => "selectListOfrNotAncmtHomepage",
        "not_ancmt_mgt_no"    => "",
        "homepage_pbs_yn"     => "Y",
        "subCheck"            => "Y",
        "ofr_pageSize"        => $pageSize,
        "not_ancmt_se_code"   => "01,02,03,04,05",
        "title"               => "고시 공고",
        "cha_dep_code_nm"     => "",
        "initValue"           => $isFirst ? "Y" : "N",
        "countYn"             => $isFirst ? "Y" : "N",
        "list_gubun"          => "A",
        "yyyy"                => "",
        "not_ancmt_sj"        => ""
    ];

    if (!$isFirst) { usleep(400_000); } // 페이지 간 매너 지연(0.4s)
    $html = $post_retry($listPayload);
    if ($html === "") return [];

    // === HTML 파싱 (목록: tables[4]) ===
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    if (!$loaded) return [];

    $xp = new DOMXPath($dom);
    $tables = $xp->query("//table");
    if (!$tables || $tables->length < 5) return [];

    $table = $tables->item(4);
    $trs = (new DOMXPath($table->ownerDocument))->query(".//tr", $table);

    $items = [];
    foreach ($trs as $tr) {
        $tds = (new DOMXPath($tr->ownerDocument))->query(".//td", $tr);
        if ($tds->length < 7) continue;

        // 헤더행(번호...) 스킵
        $firstText = trim(preg_replace('/\x{00A0}/u', ' ', $tds->item(0)->textContent));
        if ($firstText === "번호") continue;

        // onclick="searchDetail('37280')" → 37280
        $onclick = $tds->item(0)->getAttribute("onclick") ?? "";
        $notNo = "";
        if (preg_match("/searchDetail\\('([0-9]+)'\\)/", $onclick, $m)) {
            $notNo = $m[1];
        }

        $txt = function(int $i) use ($tds): string {
            $node = $tds->item($i);
            if (!$node) return "";
            $s = trim($node->textContent);
            $s = preg_replace('/\x{00A0}/u', ' ', $s); // NBSP → 공백
            return $s;
        };

        $items[] = [
            "not_ancmt_mgt_no" => $notNo,
            "번호"        => $txt(0),
            "고시공고번호" => $txt(1),
            "제목"        => $txt(2),
            "담당부서"    => $txt(3),
            "등록일"      => $txt(4),
            "게재기간"    => $txt(5),
            "조회수"      => $txt(6),
        ];
    }

    // 목록이 비면 종료 신호
    if (count($items) === 0) return [];

    // === 2) 상세 내용 요청 ===
    foreach ($items as &$it) {
        $no = $it["not_ancmt_mgt_no"] ?? "";
        if ($no === "") { $it["내용"] = ""; continue; }

        $detailPayload = [
            "pageIndex"           => "1",
            "jndinm"              => "OfrNotAncmtEJB",
            "context"             => "NTIS",
            "method"              => "selectOfrNotAncmt",
            "methodnm"            => "selectOfrNotAncmtRegst",
            "not_ancmt_mgt_no"    => $no,
            "homepage_pbs_yn"     => "Y",
            "subCheck"            => "Y",
            "ofr_pageSize"        => "10",
            "not_ancmt_se_code"   => "01,02,03,04,05",
            "title"               => "고시 공고",
            "cha_dep_code_nm"     => "",
            "initValue"           => "Y", // 상세는 그대로 Y여도 무방
            "countYn"             => "Y",
            "list_gubun"          => "A",
            "yyyy"                => "",
            "not_ancmt_sj"        => ""
        ];

        usleep(250_000); // 상세 간 매너 지연(0.25s)
        $html2 = $post_retry($detailPayload);
        if ($html2 === "") { $it["내용"] = ""; continue; }

        $dom2 = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded2 = $dom2->loadHTML(mb_convert_encoding($html2, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        if (!$loaded2) { $it["내용"] = ""; continue; }

        $xp2 = new DOMXPath($dom2);
        // style에 word-break:break-all 포함 + colspan=4
        $tdNode = null;
        $nodes = $xp2->query("//td[contains(translate(@style,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'word-break:break-all') and @colspan='4']");
        if ($nodes && $nodes->length > 0) {
            $tdNode = $nodes->item(0);
        } else {
            // 폴백: 텍스트가 가장 긴 td
            $allTds = $xp2->query("//td");
            $maxLen = -1;
            foreach ($allTds as $cand) {
                $len = mb_strlen(trim($cand->textContent));
                if ($len > $maxLen) { $maxLen = $len; $tdNode = $cand; }
            }
        }

        if ($tdNode) {
            // <br> → \r\n 치환
            foreach ($tdNode->getElementsByTagName("br") as $br) {
                $br->parentNode->replaceChild($dom2->createTextNode("\r\n"), $br);
            }
            $text = trim($tdNode->textContent);
            $text = preg_replace('/\x{00A0}/u', ' ', $text);
            $text = preg_replace("/[ \t]+\r\n/", "\r\n", $text); // 공백 정돈
            $it["내용"] = $text;
        } else {
            $it["내용"] = "";
        }
    }
    unset($it);

    // 필요 시 콘솔 검증 출력:
    // echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

    return $items;
}

// =========================
// 실행 예시 (CLI에서 실행 시)
// =========================
if (php_sapi_name() === 'cli') {
    $all = [];
    $page = 1;
    $pageSize = 10; //10, 20... 100 수정 가능

    while (true) {
        echo "\n[INFO] === {$page}페이지 수집 중 ===\n";
        $res = main($page, $pageSize);

        // 빈 페이지면 종료
        if (!$res) {
            echo "[INFO] {$page}페이지 데이터 없음 → 종료\n";
            break;
        }

        // 페이지별 즉시 로그
        echo "[INFO] {$page}페이지 수집 완료 (" . count($res) . "건)\n";
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

        $all = array_merge($all, $res);
        $page++;
        usleep(600_000); // 페이지 간 지연(0.6s)
    }

    echo "\n[INFO] 총 " . count($all) . "건 수집 완료\n";
    // 전체 결과 필요 시:
    // echo json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
}
