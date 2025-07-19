<?php
/**
 * Response Handler
 * 
 * Provides static methods for sending standardized API responses.
 */
class Response {

    /**
     * Sends a JSON response.
     * @param mixed $data The data to be encoded as JSON.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array $headers Additional headers to send.
     */
    public static function json($data, $statusCode = 200, $headers = []) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        echo json_encode($data);
        exit();
    }

    /**
     * Sends a success JSON response.
     * @param mixed $data The data to be encoded as JSON.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array $headers Additional headers to send.
     */
    public static function success($data, $statusCode = 200, $headers = []) {
        self::json(['status' => 'success', 'data' => $data], $statusCode, $headers);
    }

    /**
     * Sends an error JSON response.
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code (default: 400).
     * @param array $errors An array of error details.
     * @param array $headers Additional headers to send.
     */
    public static function error($message, $statusCode = 400, $errors = [], $headers = []) {
        self::json(['status' => 'error', 'message' => $message, 'errors' => $errors], $statusCode, $headers);
    }
}
?>