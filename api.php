<?php

const BTCZ_ADDRESSES = [
    't1fHHnAXxoPWGY77sG5Zw2sFfGUTpW6BcSZ',
    't1L7TtcRPKztgScLnfUToe4sa2aFKf9rQ14',
    't3hTi3fXhcjgjRktoiucUKRtDXxV4GfEL1w',
];
const ETH_ADDRESS = '0x4E3154bc8691BC480D0F317E866C064cC2c9455D';
const BTC_ADDRESS = '1BzBfikDBGyWXGnPPk58nVVBppzfcGGXMx';
const ZEC_ADDRESS = 't1ef9cxzpToGJcaSMXbTGRUDyrp76GfDLJG';
const LTC_ADDRESS = 'LR8bPo7NjPNRVy6nPLVgr9zHee2C7RepKA';
const USDTE_ADDRESS = '0xD36591b20f738f6929272a4391B8C133CB2e5C96';


const CACHE_TEMPLATE = __DIR__ . '/cache/%s.cache';

function getCache($key) {
    $cache_file = sprintf(CACHE_TEMPLATE, $key);
    if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60))) {
        return file_get_contents($cache_file);
    }

    return false;
}

function setCache($key, $value) {
    file_put_contents(
        sprintf(CACHE_TEMPLATE, $key),
        $value,
        LOCK_EX
    );
}


function getCoinPrice($coin) {

    #if ($cache = getCache($coin.'-price')) {
    #    return $cache;
    #}

    $data = file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids='.$coin.'&vs_currencies=usd');
    if ($data === false) {
        return null;
    }


    $data = json_decode($data);
    #setCache($coin.'-price', $data->bitcoinz->usd);

    return (float)($data->$coin->usd);
}

function getBtczBalance()
{
    if ($cache = getCache('btcz-balance')) {
        return $cache;
    }

    $total = 0;
    foreach (BTCZ_ADDRESSES as $address) {
        $addressTotal = file_get_contents('https://explorer.btcz.rocks/api/addr/' . $address . '/balance');


        $total += $addressTotal;
    }

    $total = $total / 1000000000000000000;
    setCache('btcz-balance', $total);

    return $total;
}

function getEthBalance() {
    const API_KEY = 'NIEKSBV3HT23UCI2ATHA5M57VVS5UWY9TF';

    if ($cache = getCache('eth-balance')) {
        return $cache;
    }

    $url = 'https://api.etherscan.io/api?module=account&action=balance&address='. ETH_ADDRESS .'&tag=latest&apikey=' . API_KEY;
    $data = file_get_contents($url);

    if ($data === false) {
        return null;
    }

    $data = json_decode($data, true);
    if ($data['status'] !== "1") {
        return null;
    }

    $balanceInWei = $data['result'];
    $balanceInEth = bcdiv($balanceInWei, '1000000000000000000', 18); 

    setCache('eth-balance', $balanceInEth);

    return $balanceInEth;
}


function getBtcBalance()
{
    if ($cache = getCache('btc-balance')) {
        return $cache;
    }

    $data = file_get_contents('http://blockchain.info/q/addressbalance/' . BTC_ADDRESS);
    if ($data === false) {
        return null;
    }

    $data = $data/100000000;

    setCache('btc-balance', $data);

    return $data;
}

function getZecBalance()
{
    if ($cache = getCache('zec-balance')) {
        return $cache;
    }

    $data = file_get_contents('https://api.zcha.in/v2/mainnet/accounts/' . ZEC_ADDRESS);

    if (!$data) {
        return null;
    }

    $data = json_decode($data);
    setCache('zec-balance', $data->balance);

    return $data->balance;
}

function getLtcBalance()
{
    if ($cache = getCache('ltc-balance')) {
        return $cache;
    }

    $data = file_get_contents('https://chainz.cryptoid.info/ltc/api.dws?q=getbalance&a=' . LTC_ADDRESS);
    if ($data === false) {
        return null;
    }

    setCache('ltc-balance', $data);

    return $data;
}
function getUSDTEBalance()
{
    if ($cache = getCache('USDTE-balance')) {
        return $cache;
    }
    
    $address = USDTE_ADDRESS;
    $data = json_decode(file_get_contents("https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=0xdac17f958d2ee523a2206206994597c13d831ec7&address={$address}"));

    if ((!isset($data->status) && $data->status !== 1) || $data === false) {
        return null;
    }

    setCache('USDTE-balance', $data->result * 0.000001);

    return $data->result * 0.000001;
}
$response = [
    'btczBalance' => getBtczBalance(),
    'ethBalance' => getEthBalance(),
    'btcBalance' => getBtcBalance(),
    'zecBalance' => getZecBalance(),
    'ltcBalance' => getLtcBalance(),
    'USDTEBalance' => getUSDTEBalance(),
    'btczUsd' => getCoinPrice('bitcoinz'),
    'ethUsd' => getCoinPrice('ethereum'),
    'btcUsd' => getCoinPrice('bitcoin'),
    'zecUsd' => getCoinPrice('zcash'),
    'ltcUsd' => getCoinPrice('litecoin'),
    'USDTEUsd' => getCoinPrice('tether')
];

echo json_encode($response);
