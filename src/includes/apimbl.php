<?php
class QRISPaymentGateway {
    private $expiredMinutes = 5;

    // Generate unique kode (untuk anti duplicate di satu waktu)
    public function generateUniqueCode($pdo) {
        $stmt = $pdo->prepare("SELECT unique_code FROM qris_payments WHERE status = 'pending' AND expired_at > NOW()");
        $stmt->execute();
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        do {
            $uniqueCode = rand(100, 999);
        } while (in_array($uniqueCode, $existing));
        return $uniqueCode;
    }

    public function generateDynamicQRIS($pdo, $orderId, $baseAmount, $description = '') {
        $uniqueCode = $this->generateUniqueCode($pdo);
        $finalAmount = $baseAmount + $uniqueCode;
        $staticQRIS = '00020101021126710019ID.CO.CIMBNIAGA.WWW...F';
        $qrisCode = substr($staticQRIS, 0, -4) . $finalAmount;
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($qrisCode);

        $createdAt = date('Y-m-d H:i:s');
        $expiredAt = date('Y-m-d H:i:s', strtotime("+{$this->expiredMinutes} minutes"));

        // Simpan ke DB pakai kolom order_id
        $stmt = $pdo->prepare("INSERT INTO qris_payments (order_id, base_amount, unique_code, final_amount, qris_code, qr_code_url, status, description, created_at, expired_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([
            $orderId, $baseAmount, $uniqueCode, $finalAmount,
            $qrisCode, $qrCodeUrl, $description, $createdAt, $expiredAt
        ]);

        return [
            'success'      => true,
            'order_id'     => $orderId,
            'base_amount'  => $baseAmount,
            'unique_code'  => $uniqueCode,
            'final_amount' => $finalAmount,
            'qris_code'    => $qrisCode,
            'qr_code_url'  => $qrCodeUrl,
            'status'       => 'pending',
            'description'  => $description,
            'created_at'   => $createdAt,
            'expired_at'   => $expiredAt,
            'paid_at'      => null
        ];
    }

    public function getTransactionByOrderId($pdo, $orderId) {
        $stmt = $pdo->prepare("SELECT * FROM qris_payments WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update otomatis expired (panggil manual setiap cek status)
    public function updateStatusExpired($pdo) {
        $stmt = $pdo->prepare("UPDATE qris_payments SET status = 'expired' WHERE status = 'pending' AND expired_at < NOW()");
        $stmt->execute();
    }
}
?>
