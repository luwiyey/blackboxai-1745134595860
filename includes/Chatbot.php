<?php
class Chatbot {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get chatbot response for a user query
     * For now, a simple keyword-based response. Can be extended to AI integration.
     */
    public function getResponse($query) {
        $query = strtolower(trim($query));
        if (strpos($query, 'hours') !== false) {
            return "Our library hours are Monday to Friday, 8 AM to 6 PM.";
        } elseif (strpos($query, 'location') !== false) {
            return "The library is located at the main campus building, 2nd floor.";
        } elseif (strpos($query, 'contact') !== false) {
            return "You can contact us at library@ppu.edu.ph or call (123) 456-7890.";
        } else {
            return "Sorry, I am still learning. Please contact the librarian for assistance.";
        }
    }
}
?>
