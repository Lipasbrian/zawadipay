<?php
class Database {
    private $host = 'localhost';
    private $port = '5432';
    private $db_name = 'zawadipay';
    private $username = 'postgres';
    private $password = '*O926202o';
    public $conn;

    public function connect() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
        }
        return $this->conn;
    }
}
?>