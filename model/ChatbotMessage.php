<?php
// model/ChatbotMessage.php
class ChatbotMessage {
    public $id, $session_id, $user_id, $message, $reponse, $created_at;

    public function __construct($message, $session_id = null, $user_id = null) {
        $this->message    = $message;
        $this->session_id = $session_id;
        $this->user_id    = $user_id;
    }
}
