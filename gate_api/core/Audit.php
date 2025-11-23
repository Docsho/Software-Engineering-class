<?php
class Audit {
    public static function log($pdo, $user_id, $action, $target_id, $old_value, $new_value) {
        $sql = "INSERT INTO audit_logs (user_id, action, target_id, old_value, new_value)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $target_id, $old_value, $new_value]);
    }
}
?>