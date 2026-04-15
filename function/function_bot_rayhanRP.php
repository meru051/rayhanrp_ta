<?php
require_once __DIR__ . '/common_rayhanRP.php';

$rayhanRPToken = rayhanRPGetTelegramBotToken();
$rayhanRPapiLink = $rayhanRPToken === '' ? '' : "https://api.telegram.org/bot{$rayhanRPToken}/";

function sendMessage($rayhanRPChatId, $rayhanRPMessage, $rayhanRPKeyboard = null)
{
    $rayhanRPParams = [
        'chat_id' => $rayhanRPChatId,
        'text' => $rayhanRPMessage,
    ];

    if ($rayhanRPKeyboard === null && isset($GLOBALS['rayhanRPDefaultKeyboard']) && is_array($GLOBALS['rayhanRPDefaultKeyboard'])) {
        $rayhanRPKeyboard = $GLOBALS['rayhanRPDefaultKeyboard'];
    }

    if ($rayhanRPKeyboard) {
        $rayhanRPParams['reply_markup'] = json_encode($rayhanRPKeyboard);
    }

    return rayhanRPCallTelegramApi('sendMessage', $rayhanRPParams) !== false;
}

