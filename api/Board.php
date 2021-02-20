<?php

use function PHPUnit\Framework\throwException;
use Exception;

include_once('Config.php');
include_once('ConnectionDB.php');
include_once("Helper.php");

/**
 * Client class verify what method was sent and execute the respective method.
 */
class Board
{

    // Constants
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';

    const BOARD_TABLE = 'boards';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    // Attributes
    private $id;
    private $name;
    private $content;
    private $created;
    private $views;
    private $expiration;
    private $db;

    function __construct($name = '', $content = '', $created = '')
    {
        try {
            # Construct the class and set the values in the attributes.
            $config = new Config;
            $this->db = ConnectionDB::getInstance($config);
            $this->name = $name;
            $this->content = $content;
            $this->created = $created;
        } catch (Exception $e) {
            Helper::saveInLog("ERROR CREANDO BOARD", $e->getMessage());
        }
    }


    function verifyMethod($method, $route)
    {
        //Verifies what is the http method sent.
        try {
            switch ($method) {
                case self::HTTP_GET:
                    $name = isset($route[1]) ? (string) $route[1] : null;
                    $boards = $this->getBoards($name);
                    return [
                        'status' => 200,
                        'method' => self::HTTP_GET,
                        'boards' => $boards,
                    ];
                    break;
                case self::HTTP_POST:
                    $post = json_decode(file_get_contents('php://input'), true);
                    $this->name = (string) $post['name'];
                    $this->content = (string) $post['content'];
                    return $this->addBoard($this->name, $this->content);
                    break;
                default:
                    # When the method is different of the previous methods, return an error message.
                    return array('status' => 405);
                    break;
            }
        } catch (Exception $e) {
            Helper::saveInLog("ERROR CON LA PETICION", $e->getMessage());
            return [
                'status' => 500,
                'method' => self::HTTP_GET,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function addBoard(string $name, string $content): array
    {

        $response = [];

        if (empty($name)) {
            throw new Exception("INVALID NAME");
        }

        if (empty($content)) {
            throw new Exception("NO CONTENT");
        }

        $exist = [] != $this->getBoards($name);
        if ($exist) {

            Helper::saveInLog("Board already exist", $name);
            $response['status'] = 202;
            $response['error'] = 'ALREADY_EXIST';
            return $response;
        }

        // TODO => JRGM => delete_at = null -- Handle in front and back - 1hour - 1day - 1 week - 1 month
        $delete_at = null;
        // TODO => JRGM => delete_at_view = 0 -- Handle in front and back
        $delete_at_view = 0;

        $sql = "INSERT INTO " . self::BOARD_TABLE . " (name, content, created_at, delete_at, delete_at_view) VALUES (:name, :content, :created_at, :delete_at, :delete_at_view)";
        $created_at = date(self::MYSQL_DATETIME_FORMAT, time());
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':delete_at', $delete_at);
        $stmt->bindParam(':delete_at_view', $delete_at_view);
        $result = $stmt->execute();
        $response['status'] = $result ? 200 : 500;
        return $response;
    }

    protected function getBoards(string $name = null): array
    {
        $result = [];
        if (empty($name)) {

            $sql = "SELECT * FROM " . self::BOARD_TABLE;
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {

            $sql = "SELECT * FROM " . self::BOARD_TABLE . " WHERE name LIKE ?";
            $stmt = $this->db->prepare($sql);
            // $parametros = ["%$name%"];
            $parametros = ["$name"];
            $stmt->execute($parametros);
        }

        if ($stmt->rowCount() > 0) {

            $var = $stmt->fetchAll();
            foreach ($var as $row) {

                $result[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'content' => $row['content'],
                    'created' => $row['created_at'],
                ];
            }
        }

        return $result;
    }
}
