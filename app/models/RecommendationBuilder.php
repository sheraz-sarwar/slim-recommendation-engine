<?php
/**
 * RecommendationBuilder class for building recommendation for a user when
 * they view a product.
 */
class RecommendationBuilder {
    private $connection;
    private $db_name;

    //constructor
    public function __construct() {
        $this->connection = new Mongo("mongodb://localhost:27017");

        $this->db_name = $this->connection->selectDB('rengine');
    }

    /**
     * Get recommendations based on the current product viewed by the user.
     * @param int $product_id
     * @return array | 0
     */
    public function getRecommendation($product_id) {
        $setA = $this->setOfFrequentlyBoughtProduct($product_id);
        $setB = $this->setOfFrequentlyViewedProduct($product_id);

        if($setA != 0 && $setB != 0) {
            $common_products = $this->getCommonProducts($setA, $setB);

            //loop through each common products and calculate its proximity
            //for relevance with the currently viewed product
            foreach($common_products as $product) {
                $proximity = $setA[$product]/$setB[$product];
                $recommendations[$product] = $proximity;
            }

            arsort($recommendations);

            $output = $this->flipRecommendationArrayKeyValue($recommendations);

            return $output;
        }
    }

    /**
     * Find common products in the set of frequently viewed products and frequently bought products.
     * @param array $setA
     * @param array $setB
     * @return array
     */
    private function getCommonProducts($setA, $setB) {
        $common_products = array();

        foreach($setA as $key1=>$bought_product) {
            foreach($setB as $key2=>$viewed_product) {
                if($key1 == $key2) $common_products[] = $key1;
            }
        }

        return $common_products;
    }

    /**
     * Find set of products bought by the users who viewed the current product.
     * @param int $product_id
     * @return array | 0
     */
    private function setOfFrequentlyBoughtProduct($product_id) {
        $array_to_send = array();
        $product_view = $this->db_name->product_view;
        $sessions = $product_view->distinct('session_id', array('product_id' => $product_id));

        foreach($sessions as $session) {
            $array_to_send[] = $session;
        }

        $product_bought = $this->db_name->product_bought;
        $products = $product_bought->find(
            array(
                'session_id' => array(
                    '$in' => $array_to_send
                ),
                'product_id' => array(
                    '$ne' => $product_id
                )
            ),
            array(
                'product_id' => 1
            )
        );

        return $this->sortMatchingResults($products);
    }

    /**
     * Find set of products viewed by the users who also viewed the current product.
     * @param int $product_id
     * @return array | 0
     */
    private function setOfFrequentlyViewedProduct($product_id) {
        $array_to_send = array();
        $product_view = $this->db_name->product_view;
        $sessions = $product_view->distinct('session_id', array('product_id' => $product_id));

        foreach($sessions as $session) {
            $array_to_send[] = $session;
        }

        $products = $product_view->find(
            array(
                'session_id' => array(
                    '$in' => $array_to_send
                ),
                'product_id' => array(
                    '$ne' => $product_id
                )
            ),
            array(
                'product_id' => 1
            )
        );

        return $this->sortMatchingResults($products);
    }

    /**
     * Method to flip key and value of the array.
     * @param array $array_to_flip
     * @return array
     */
    private function flipRecommendationArrayKeyValue($array_to_flip) {
        $i = 0;
        $array_to_flip = array_slice($array_to_flip, 0, 3, true);

        foreach($array_to_flip as $key=>$element) {
            $array_to_return[$i] = $key;
            $i++;
        }

        return $array_to_return;
    }

    /**
     * Sort matching results by each product's occurrences in an descending order.
     * @param array $products
     * @return array | 0
     */
    private function sortMatchingResults($products) {
        $matches = iterator_to_array($products);

        if (!empty($matches)) {
            foreach ($matches as $match) {
                $array_to_count[] = $match['product_id'];
            }

            $counted_array = array_count_values($array_to_count);
            arsort($counted_array);

            return $counted_array;
        }
        else return 0;
    }
}