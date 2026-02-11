<?php
require_once 'function/function_bot_rayhanRP.php';
$rayhanRPContent = file_get_contents("php://input");
if ($rayhanRPContent) {
    $rayhanRPUpdate = json_decode($rayhanRPContent, true);
    $rayhanRPChat_id = $rayhanRPUpdate['message']['chat']['id'] ?? null;
    $rayhanRPText = $rayhanRPUpdate['message']['text'] ?? '';
    $rayhanRPChatName = $rayhanRPUpdate['message']['chat']['first_name'] ?? '';
    $rayhanRPPhoto = ($rayhanRPUpdate['message']['photo']) ??  null;
    $rayhanRPDocument = ($rayhanRPUpdate['message']['document']) ??  null;
    if ($rayhanRPText == "/start") {
        sendMessage($rayhanRPChat_id, "Halo $rayhanRPChatName.");
    }
}
//tes API : . $apiLink = "https://api.telegram.org/bot8219558178:AAGONLX_MZxkGWHLwygUB-CaMM-_PjYJv3k/setwebhook?url=https://herblike-unhabitably-vicente.ngrok-free.dev/bot_sirey/bot_rayhanRP.php";