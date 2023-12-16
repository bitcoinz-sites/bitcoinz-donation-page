<?php

const BTCZ_ADDRESSES = [
    't1fHHnAXxoPWGY77sG5Zw2sFfGUTpW6BcSZ',
    't1L7TtcRPKztgScLnfUToe4sa2aFKf9rQ14',
    't3hTi3fXhcjgjRktoiucUKRtDXxV4GfEL1w',
];

const API_KEY = 'NIEKSBV3HT23UCI2ATHA5M57VVS5UWY9TF';
const CMC_API = '4fb2fb51-1b3b-4f13-9975-9593f1da4bf4';
// const ZEC_API_KEY = 'fec78e6f0e9edb79108af6c87adc5efedd4a815a';

const ETH_ADDRESS = '0x4E3154bc8691BC480D0F317E866C064cC2c9455D';
const BTC_ADDRESS = '1BzBfikDBGyWXGnPPk58nVVBppzfcGGXMx';
const ZEC_ADDRESS = 't1ef9cxzpToGJcaSMXbTGRUDyrp76GfDLJG';
const LTC_ADDRESS = 'LR8bPo7NjPNRVy6nPLVgr9zHee2C7RepKA';
const USDTE_ADDRESS = '0xD36591b20f738f6929272a4391B8C133CB2e5C96';

// const CACHE_TEMPLATE = __DIR__ . '/cache/%s.cache';

function debugLog($message) {
    error_log(print_r($message, true));
}

// function getCache($key) {
//     debugLog("getCache called for key: " . $key);
//     $cache_file = sprintf(CACHE_TEMPLATE, $key);
//     if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60))) {
//         debugLog("Cache hit for key: " . $key);
//         return file_get_contents($cache_file);
//     }
//     debugLog("Cache miss for key: " . $key);
//     return false;
// }

// function setCache($key, $value) {
//     debugLog("setCache called for key: " . $key . " with value: " . $value);
//     file_put_contents(sprintf(CACHE_TEMPLATE, $key), $value, LOCK_EX);
// }


function getCoinPrice($coin) {
    // Define standard symbols for major cryptocurrencies
    $standardSymbols = [
        'bitcoinz' => 'BTCZ', // Example, replace with actual symbol if different
        'ethereum' => 'ETH',
        'bitcoin' => 'BTC',
        'zcash'  => 'ZEC',
        'litecoin' => 'LTC',
        'tether'   => 'USDT'
        
    ];

    // Use the standard symbol if available
    $symbol = array_key_exists($coin, $standardSymbols) ? $standardSymbols[$coin] : strtoupper($coin);

    $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest?symbol=' . $symbol . '&convert=USD';

    $headers = [
        "Accepts: application/json",
        "X-CMC_PRO_API_KEY: " . CMC_API
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 10
        ]
    ]);

    try {
        $data = file_get_contents($url, false, $context);
        if ($data === false) {
            throw new Exception("Error fetching data from URL: " . $url);
        }

        $decodedData = json_decode($data, true);
        if (is_null($decodedData) || !isset($decodedData['data'][$symbol])) {
            throw new Exception("Invalid JSON structure or data for $symbol not found");
        }

        if (!isset($decodedData['data'][$symbol]['quote']['USD']['price'])) {
            throw new Exception("Price for $symbol not found in response");
        }

        return ['error' => false, 'value' => (float) $decodedData['data'][$symbol]['quote']['USD']['price']];
    } catch (Exception $e) {
        debugLog("Error in getCoinPrice for $symbol: " . $e->getMessage());
        return ['error' => true, 'value' => null];
    }
}


function getBtczBalance() {
    debugLog("getBtczBalance called");
    $total = 0;
    foreach (BTCZ_ADDRESSES as $address) {
        debugLog("Fetching balance for BTCZ address: " . $address);
        $addressTotal = file_get_contents('https://explorer.btcz.rocks/api/addr/' . $address . '/balance');

        if ($addressTotal === false) {
            debugLog("Failed to fetch balance for BTCZ address: " . $address);
            continue;
        }

        // Convert the balance to a number and log it
        $addressTotal = floatval($addressTotal);
        debugLog("Fetched balance for address $address: $addressTotal");

        $total += $addressTotal;
    }

    debugLog("Total before conversion: " . $total);

    // Convert total balance from satoshi to BTCZ using the correct conversion factor (1e8)
    $totalInBtcz = $total / 100000000;
    debugLog("Total BTCZ balance: " . $totalInBtcz);

    return $totalInBtcz;
}


function getEthBalance() {
    debugLog("getEthBalance called");
    $url = 'https://api.etherscan.io/api?module=account&action=balance&address='. ETH_ADDRESS .'&tag=latest&apikey=' . API_KEY;
    $data = file_get_contents($url);
    if ($data === false) {
        debugLog("Failed to fetch ETH balance");
        return null;
    }
    $data = json_decode($data, true);
    if ($data['status'] !== "1") {
        debugLog("Error in ETH balance response");
        return null;
    }
    $balanceInWei = $data['result'];
    $balanceInEth = bcdiv($balanceInWei, '1000000000000000000', 18);
    debugLog("ETH balance: " . $balanceInEth);
    // setCache('eth-balance', $balanceInEth);
    return $balanceInEth;
}

function getBtcBalance() {
    debugLog("getBtcBalance called");

    $data = file_get_contents('https://chainz.cryptoid.info/btc/api.dws?q=getbalance&a=' . BTC_ADDRESS);
    if ($data === false) {
        debugLog("Failed to fetch BTC balance");
        return null;
    }

    // Assuming the API returns the balance in Bitcoin, not in satoshis
    $balanceInBtc = floatval($data);
    debugLog("BTC balance: " . $balanceInBtc);

    return $balanceInBtc;
}

// function getZcashBalance() {
//     debugLog("getZcashBalance called");

//     $url = 'https://rest.cryptoapis.io/blockchain-data/zcash/mainnet/addresses/' . ZEC_ADDRESS . '/balance';

//     $request = new HttpRequest();
//     $request->setUrl($url);
//     $request->setMethod(HTTP_METH_GET);

//     $request->setHeaders(array(
//         'Content-Type' => 'application/json',
//         'X-API-Key' => ZEC_API_KEY
//     ));

//     try {
//         $response = $request->send();
//         $body = $response->getBody();
//         $decodedData = json_decode($body, true);

//         if (json_last_error() !== JSON_ERROR_NONE) {
//             debugLog("JSON decoding error: " . json_last_error_msg());
//             return null;
//         }

//         if (!isset($decodedData['data']['item']['confirmedBalance']['amount'])) {
//             debugLog("ZEC balance data not found in the API response");
//             return null;
//         }

//         $balanceInZec = $decodedData['data']['item']['confirmedBalance']['amount'];
//         debugLog("Fetched ZEC balance in ZEC: " . $balanceInZec);

//         return $balanceInZec;
//     } catch (HttpException $ex) {
//         debugLog("HTTP exception occurred: " . $ex);
//         return null;
//     }
// }



function getLtcBalance() {
    debugLog("getLtcBalance called");
    $data = file_get_contents('https://chainz.cryptoid.info/ltc/api.dws?q=getbalance&a=' . LTC_ADDRESS);
    if ($data === false) {
        debugLog("Failed to fetch LTC balance");
        return null;
    }
    debugLog("LTC balance: " . $data);
    // setCache('ltc-balance', $data);
    return $data;
}

function getUSDTEBalance() {
    debugLog("getUSDTEBalance called");
    $url = 'https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=0xdac17f958d2ee523a2206206994597c13d831ec7&address=' . USDTE_ADDRESS . '&tag=latest&apikey=' . API_KEY;
    $data = file_get_contents($url);
    if ($data === false) {
        debugLog("Failed to fetch USDTE balance");
        return null;
    }
    $data = json_decode($data, true);
    if (!isset($data['status']) || $data['status'] !== "1") {
        debugLog("Error in USDTE balance response");
        return null;
    }
    $balanceInUSDTE = bcdiv($data['result'], '1000000', 6); // Tether has 6 decimal places
    debugLog("USDTE balance: " . $balanceInUSDTE);
    // setCache('USDTE-balance', $balanceInUSDTE);
    return $balanceInUSDTE;
}

$response = [
    'btczBalance' => getBtczBalance(),
    'ethBalance' => getEthBalance(),
    'btcBalance' => getBtcBalance(),
    //'zecBalance' => getZecBalance(),
    'ltcBalance' => getLtcBalance(),
    'USDTEBalance' => getUSDTEBalance(),
    'btczUsd' => getCoinPrice('bitcoinz'),
    'ethUsd' => getCoinPrice('ethereum'),
    'btcUsd' => getCoinPrice('bitcoin'),
    //'zecUsd' => getCoinPrice('zcash'),
    'ltcUsd' => getCoinPrice('litecoin'),
    'USDTEUsd' => getCoinPrice('tether')
];

debugLog("Response: " . json_encode($response));
echo json_encode($response);
