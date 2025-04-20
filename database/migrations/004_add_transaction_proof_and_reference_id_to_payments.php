<?php
// Migration to add transaction_proof and reference_id columns to payments table

require_once __DIR__ . '/../init.php';

use Includes\Migration;

class Migration004 extends Migration {
    public function up() {
        $sql = "ALTER TABLE payments 
                ADD COLUMN transaction_proof VARCHAR(255) NULL AFTER status,
                ADD COLUMN reference_id VARCHAR(50) NULL AFTER transaction_proof";
        $this->execute($sql);
    }

    public function down() {
        $sql = "ALTER TABLE payments 
                DROP COLUMN transaction_proof,
                DROP COLUMN reference_id";
        $this->execute($sql);
    }
}

$migration = new Migration004();
$migration->migrate();
?>
