<?php

use function PHPUnit\Framework\throwException;
use Exception;
use PDOException;
use Throwable;

include_once('Config.php');
include_once('ConnectionDB.php');
include_once("Helper.php");

/**
 * Client class verify what method was sent and execute the respective method.
 */
class Board
{
    // Errors
    const ERROR_CREATING_BOARD = "ERROR CREANDO BOARD";
    const ERROR_CRITICAL = 'CRITICAL_ERROR: ';
    const ERROR_HTTP_METHOD_NOT_ALLOWED = 'HTTP_METHOD_NOT_ALLOWED';
    const ERROR_INVALID_CONTENT = 'INVALID CONTENT';
    const ERROR_INVALID_NAME = 'INVALID NAME';

    // HTTP constants
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';

    const HTTP_SUCCESSFUL_CODE = 200;
    const HTTP_ALREADY_EXIST_CODE = 202;
    const HTTP_NON_ALLOWED_METHOD = 405;
    const HTTP_SERVER_ERROR_CODE = 500;

    // Messages
    const BOARD_ALREADY_EXIST = 'ALREADY_EXIST';

    // Other consts
    const BOARD_TABLE = 'boards';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    // Attributes
    private $id;
    private $name;
    private $content;
    private $created;
    private $views;
    private $expiration;
    private $destroy;
    private $db;

    function __construct($name = '', $content = '', $created = '', $destroy = 0)
    {
        try {
            # Construct the class and set the values in the attributes.
            $config = new Config;
            $this->db = ConnectionDB::getInstance($config);
            $this->name = $name;
            $this->content = $content;
            $this->created = $created;
            $this->destroy = $destroy;
        } catch (Throwable | Exception $e) {
            Helper::saveInLog(self::ERROR_CREATING_BOARD, $e->getMessage());
        }
    }

    function verifyMethod($method, $route)
    {
        try {
            switch ($method) {

                case self::HTTP_GET:
                    $name = isset($route[1]) ? (string) $route[1] : null;
                    $boards = $this->getBoards($name);
                    return [
                        'status' => self::HTTP_SUCCESSFUL_CODE,
                        'method' => self::HTTP_GET,
                        'boards' => $boards,
                    ];
                    break;

                case self::HTTP_POST:
                    $post = json_decode(file_get_contents('php://input'), true);
                    $this->name = (string) $post['name'];
                    $this->content = (string) $post['content'];
                    $this->destroy = (int) $post['destroy'];
                    $result = $this->addBoard($this->name, $this->content, $this->destroy);
                    return $result;
                    break;

                default:
                    # When the method is different of the previous methods, return an error message.
                    return [
                        'status' => self::HTTP_NON_ALLOWED_METHOD,
                        'error' => self::ERROR_HTTP_METHOD_NOT_ALLOWED
                    ];
                    break;

            }
        } catch (Throwable | Exception $e) {
            Helper::saveInLog(self::ERROR_CRITICAL, $e->getMessage());
            return [
                'status' => self::HTTP_SERVER_ERROR_CODE,
                'method' => $method,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function addBoard(string $name, string $content, int $destroy): array
    {
        $response = [];
        try {

            if (empty($name)) {
                throw new Exception(self::ERROR_INVALID_NAME);
            }

            if (empty($content)) {
                throw new Exception(self::ERROR_INVALID_CONTENT);
            }

            $exist = [] != $this->getBoards($name);
            if ($exist) {
                Helper::saveInLog(self::BOARD_ALREADY_EXIST, $name);
                $response['status'] = self::HTTP_ALREADY_EXIST_CODE;
                $response['error'] = self::BOARD_ALREADY_EXIST;
                return $response;
            }

            // TODO => JRGM => delete_at = null -- Handle in front and back - 1hour - 1day - 1 week - 1 month
            $delete_at = null;

            $created_at = date(self::MYSQL_DATETIME_FORMAT, time());

            $board = [];
            $board['name'] = $name;
            $board['content'] = $content;
            $board['destroy'] = $destroy;
            $board['created_at'] = $created_at;
            $board['delete_at'] = $delete_at;

            $sql = "INSERT INTO " . self::BOARD_TABLE . " (name, content, destroy, created_at, delete_at) VALUES (:name, :content, :destroy, :created_at, :delete_at)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($board);

            if ($result) {
                $response['status'] = self::HTTP_SUCCESSFUL_CODE;
                $board['id'] = $this->db->lastInsertId();
                $response['board'] = $board;
            } else {
                $response['status'] = self::HTTP_SERVER_ERROR_CODE;
            }

        } catch (Throwable | Exception $e) {
            Helper::saveInLog("ERROR ADDING BOARD", $e->getMessage());
            $response['status'] = self::HTTP_SERVER_ERROR_CODE;
        }

        return $response;
    }

    protected function getBoards(string $name = null): array
    {
        $result = [];
        try {

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
                        'destroy' => $row['destroy'],
                        'created' => $row['created_at'],
                    ];
                }

                // Code to destroy an self destroyed board. Only if it was looked.
                if ($stmt->rowCount() == 1 && !empty($name) && $result[0]['destroy'] == 1) {
                    if ($this->deleteBoardByName($name)) {
                        $result[0]['destroyed'] = true;
                    }
                }

            }
        } catch (Throwable | Exception $e) {
            Helper::saveInLog("ERROR GETTING BOARDS: ", $e->getMessage());
            $result = [];
        }

        return $result;
    }

    protected function deleteBoardByName(string $name) : bool
    {
        try {

            if (empty($name)) {
                throw new Exception(self::ERROR_INVALID_NAME);
            }

            $sql = "DELETE FROM " . self::BOARD_TABLE . " WHERE name LIKE ?";
            $stmt = $this->db->prepare($sql);
            $parametros = ["$name"];
            $result = $stmt->execute($parametros);

        } catch (Throwable | Exception | PDOException $e) {
            Helper::saveInLog("ERROR DELETING BOARD: ", $e->getMessage());
            $result = false;
        }

        return $result;
    }


    /**
     * Función para debug las PDO queries y devolverlo en un string.
     * @param  [type] $stmt [description]
     * @return [type]       [description]
     */
    private function pdo_debugStrParams($stmt) {
        ob_start();
        $stmt->debugDumpParams();
        $r = ob_get_contents();
        ob_end_clean();
        return $r;
    }

}
