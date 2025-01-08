<?php

/**
 * Perform login using the provided username and password.
 *
 * @param string $username
 * @param string $password
 * @return bool
 */
function setLogin($username, $password) {
    $loginUrl = "https://tyc.best/include/login_chk.asp";

    $postData = http_build_query([
        "MEMB_ID" => $username,
        "PASS2" => $password,
    ]);

    $headers = [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // SSL 검증 비활성화
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . PHP_EOL;
        curl_close($ch);
        return false;
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode == 200) {
        echo "로그인 성공!" . PHP_EOL;
        return true;
    } else {
        echo "로그인 실패! HTTP 상태 코드: $statusCode" . PHP_EOL;
        return false;
    }
}

/**
 * Send a GET request and return the HTML content.
 *
 * @param string $url
 * @return string|null
 */
function getRequest($url) {
    $headers = [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL 인증서 검증 비활성화
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 호스트 이름 검증 비활성화

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . PHP_EOL;
        curl_close($ch);
        return null;
    }

    curl_close($ch);
    return $response;
}

/**
 * Extract rewards from the main dashboard page.
 *
 * @return array
 */
function mainReward() {
    $url = "https://tyc.best/dashboard/index.asp";
    $html = getRequest($url);

    if (!$html) {
        echo "HTML 가져오기 실패" . PHP_EOL;
        return [];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // 최종 데이터 저장할 배열
    $dataList = [];

    // 첫 번째 div 탐색
    $mainDivs = $xpath->query("//div[contains(@class, 'col-xxl-auto col-xl-3 col-sm-6 box-col-6')]");
    foreach ($mainDivs as $mainDiv) {
        try {
            // 두 번째 div 탐색
            $widgetContents = $xpath->query(".//div[contains(@class, 'widget-content')]", $mainDiv);
            foreach ($widgetContents as $widgetContent) {
                // 세 번째 div 탐색
                $innerDivs = $xpath->query(".//div", $widgetContent);
                if ($innerDivs->length >= 3) {
                    $h4Tag = $xpath->query(".//h4", $innerDivs->item(2))->item(0);
                    $spanTag = $xpath->query(".//span[contains(@class, 'f-light')]", $innerDivs->item(2))->item(0);

                    if ($h4Tag && $spanTag) {
                        // 데이터 저장
                        $dataList[] = (object) [
                            "값" => trim($h4Tag->textContent),
                            "이름" => trim($spanTag->textContent),
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error processing a widget content: " . $e->getMessage() . PHP_EOL;
        }
    }

    return $dataList;
}

/**
 * Extract mining rewards from the bonus day list page.
 *
 * @return array
 */
function miningReward() {
    $url = "https://tyc.best/dashboard/depth/bonus/bonus_daylist.asp";
    $html = getRequest($url);

    if (!$html) {
        echo "HTML 가져오기 실패" . PHP_EOL;
        return [];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $dataList = [];

    // 테이블 찾기
    $table = $xpath->query("//table[contains(@class, 'basic_table')]")->item(0);

    if ($table) {
        // 테이블 헤더 가져오기
        $headers = [];
        $headerElements = $xpath->query(".//thead/tr/th", $table);

        foreach ($headerElements as $header) {
            $headers[] = trim($header->textContent);
        }

        // 테이블 바디 가져오기
        $tbody = $xpath->query(".//tbody", $table)->item(0);
        if ($tbody && !empty($headers)) {
            $rows = $xpath->query(".//tr", $tbody);

            foreach ($rows as $row) {
                // 해당 row에서 th나 td를 가져옴
                $cells = $xpath->query(".//th | .//td", $row);
                $rowData = [];

                // 데이터 매핑
                foreach ($cells as $index => $cell) {
                    $headerKey = $headers[$index] ?? "Column_$index";
                    $rowData[$headerKey] = trim($cell->textContent);
                }

                // 데이터를 배열에 추가
                $dataList[] = (object) $rowData; // 객체로 추가 (필요에 따라 배열 형태로도 가능)
            }
        }
    }

    return $dataList;
}


/**
 * Main function to execute the login and reward extraction.
 *
 * @param string $username
 * @param string $password
 * @return void
 */
function main($username, $password) {
    if (setLogin($username, $password)) {
        $mainRewardList = mainReward();
        print_r($mainRewardList);

        $miningRewardList = miningReward();
        print_r($miningRewardList);
    } else {
        echo "로그인에 실패했습니다." . PHP_EOL;
    }
}




// 실행
$username = "kkckkc";
$password = "k@4358220";

main($username, $password);


// 필요 composer 목록
// composer require guzzlehttp/guzzle symfony/dom-crawler symfony/css-selector monolog/monolog
