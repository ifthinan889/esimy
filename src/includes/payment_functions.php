<?php
if (!defined('ALLOWED_ACCESS')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

// Dummy payment function for now
function createPaymentForMultipleOrders($orderTokens, $packageCode, $baseAmountUsd, $totalAmountIdr, $count) {
    return [
        'success' => true,
        'payment_id' => 'DUMMY_' . time(),
        'message' => 'Payment created (dummy)'
    ];
}
?>