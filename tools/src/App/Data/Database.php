<?php

namespace App\Data;

class Database
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var \PDO
     */
    private $conn;

    /**
     * Database constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function reinitialize()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        $conn = $this->getConnection();
        $conn->exec('CREATE TABLE chardata (codepoint INT PRIMARY KEY, big5 VARCHAR(8), hkscs VARCHAR(8), hk_common INT, iicore_hk VARCHAR(10), iicore_tw VARCHAR(5), iicore_jp VARCHAR(5), iicore_mo VARCHAR(5))');
        $conn->exec('CREATE TABLE cmap (codepoint INT PRIMARY KEY, hex_cp VARCHAR(10), cid_cn INT, cid_jp INT, cid_kr INT, cid_tw INT, cid_hk INT)');
        $conn->exec('CREATE TABLE process (codepoint INT, workset INT, tag VARCHAR(10), category VARCHAR(10), action VARCHAR(10), new_cid INT, export INT)');
    }

    public function deleteAll($tableName, $condition)
    {
        $sql = 'DELETE FROM ' . $tableName . ($condition ? ' WHERE ' . $condition : '');

        return $this->getConnection()->exec($sql);
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        if (!$this->conn) {
            $this->conn = new \PDO('sqlite:' . $this->path);
        }

        return $this->conn;
    }
}
