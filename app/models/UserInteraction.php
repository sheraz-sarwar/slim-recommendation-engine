<?php
/**
 * UserInteraction class for recording user actions such as product view or
 * product purchase and storing this information in the database.
 */
class UserInteraction {
    private $connection;
    private $db_name;

    //constructor
    public function __construct() {
        $this->connection = new Mongo("mongodb://localhost:27017");

        $this->db_name = $this->connection->selectDB('rengine');
    }

    /**
     * Create user session record when a user first visits the site.
     */
    public function createUserSession() {
        $user_session = $this->db_name->user_session;

        $data = array(
            '_id' => $_SESSION['user_session']
        );

        $user_session->save($data);
    }

    /**
     * Add a record of a product viewed by the user.
     */
    public function addProductViewInformation($product_id) {
        $product_view = $this->db_name->product_view;

        $data = array(
            'session_id' => $_SESSION['user_session'],
            'product_id' => $product_id,
            'timestamp' => date('Y-m-d H:i:s')
        );

        $product_view->save($data);
    }

    /**
     * Add a record of product bought by the user.
     */
    public function addProductBoughtInformation($product_id) {
        $product_bought = $this->db_name->product_bought;

        $data = array(
            'session_id' => $_SESSION['user_session'],
            'product_id' => $product_id,
            'timestamp'  => date('Y-m-d H:i:s')
        );

        $product_bought->save($data);
    }
}