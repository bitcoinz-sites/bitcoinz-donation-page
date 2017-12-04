<?php

const BTCZ_ADDRESS = "t1fHHnAXxoPWGY77sG5Zw2sFfGUTpW6BcSZ";
const ETH_ADDRESS = "0x4E3154bc8691BC480D0F317E866C064cC2c9455D";
const BTC_ADDRESS = "1BzBfikDBGyWXGnPPk58nVVBppzfcGGXMx";
const ZEC_ADDRESS = "t1ef9cxzpToGJcaSMXbTGRUDyrp76GfDLJG";

function getCache($key)
{
    $cache_file = $key . '.cache';
    if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60))) {
        return file_get_contents($cache_file);
    }
    return false;
}

function setCache($key, $value)
{
    $cache_file = $key . '.cache';
    file_put_contents($cache_file, $value, LOCK_EX);
}


function getCoinPrice($coin)
{
    if ($cache = getCache($coin."-price")) {
        return $cache;
    }
    $data = file_get_contents("https://api.coinmarketcap.com/v1/ticker/" . $coin);
    if ($data === false) {
        return null;
    }
    $data = json_decode($data);
    setCache($coin."-price", $data[0]->price_usd);
    return $data[0]->price_usd;
}

function getBtczBalance()
{
    if ($cache = getCache("btcz-balance")) {
        return $cache;
    }
    $data = file_get_contents("http://btczexplorer.blockhub.info/ext/getbalance/" . BTCZ_ADDRESS);
    if ($data === false) {
        return null;
    }
    setCache("btcz-balance", $data);
    return $data;
}

function getEthBalance()
{
    if ($cache = getCache("eth-balance")) {
        return $cache;
    }
    $data = file_get_contents("https://etherchain.org/api/account/" . ETH_ADDRESS);
    if ($data === false) {
        return null;
    }
    $data = json_decode($data);
    if ($data->status !== 1) {
        return null;
    }
    setCache("eth-balance", $data->data[0]->balance / 1000000000000000000);
    return $data->data[0]->balance / 1000000000000000000;
}

function getBtcBalance()
{
    if ($cache = getCache("btc-balance")) {
        return $cache;
    }
    $data = file_get_contents("http://blockchain.info/q/addressbalance/" . BTC_ADDRESS);
    if ($data === false) {
        return null;
    }
    setCache("btc-balance", $data);
    return $data;
}

function getZecBalance()
{
    if ($cache = getCache("zec-balance")) {
        return $cache;
    }
    $data = file_get_contents("https://api.zcha.in/v2/mainnet/accounts/" . ZEC_ADDRESS);
    if (!$data) {
        return null;
    }
    $data = json_decode($data);
    setCache("zec-balance", $data->balance);
    return $data->balance;
}

$response = [
    'btczBalance' => getBtczBalance(),
    'ethBalance' => getEthBalance(),
    'btcBalance' => getBtcBalance(),
    'zecBalance' => getZecBalance(),
    'btczUsd' => getCoinPrice('bitcoinz'),
    'ethUsd' => getCoinPrice('ethereum'),
    'btcUsd' => getCoinPrice('bitcoin'),
    'zecUsd' => getCoinPrice('zcash')
];

echo json_encode($response);