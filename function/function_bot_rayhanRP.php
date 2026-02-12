<?php
$rayhanRPToken = "8219558178:AAGONLX_MZxkGWHLwygUB-CaMM-_PjYJv3k";
$rayhanRPapiLink = "https://api.telegram.org/bot$rayhanRPToken/";

function sendMessage($rayhanRPChat_id, $rayhanRPMessage, $rayhanRPKeyboard = null)
{
    global $rayhanRPapiLink;
    $rayhanRPparams = [
        'chat_id' => $rayhanRPChat_id,
        'text' => $rayhanRPMessage
    ];

    if ($rayhanRPKeyboard) {
        $rayhanRPparams['reply_markup'] = json_encode($rayhanRPKeyboard);
    }

    $rayhanRPContext = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);

    @file_get_contents($rayhanRPapiLink . "sendMessage?" . http_build_query($rayhanRPparams), false, $rayhanRPContext);
}
?>
