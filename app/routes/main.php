<?php
/**
 * Main page
 */
$app->get('/', function() use ($app) {
    require '../app/models/UserInteraction.php';

	$products = ORM::for_table('product')
                   ->where_gte('product_id', 15580)
                   ->where('stock_msg', 9)
                   ->limit(20)
                   ->find_many();

    if (!isset($_SESSION['user_session'])) {
        $_SESSION['user_session'] = session_id();
        $user_action = new UserInteraction();
        $user_action->createUserSession();
    }

    $app->render('product_list.html.twig', array(
       'products' => $products,
    ));
});

/**
 * Product page
 */
$app->get('/products/:id', function($id) use ($app) {
    require '../app/models/UserInteraction.php';
    require '../app/models/RecommendationBuilder.php';

    $product = ORM::for_table('product')
                  ->where('product_id', $id)
                  ->find_one();

    $user_action = new UserInteraction();
    $user_action->addProductViewInformation($id);

    $recommender = new RecommendationBuilder();
    $recommendations = $recommender->getRecommendation($id);

    $data = array(
        'product' => $product->as_array(),
        'recommendations' => $recommendations
    );

    $app->render('product_view.html.twig', $data);
});

/**
 * Handle product buy action.
 */
$app->get('/products/buy/:id', function($id) use ($app) {
    require '../app/models/UserInteraction.php';

    $user_action = new UserInteraction();
    $user_action->addProductBoughtInformation($id);

    $app->flash('message', 'Item is bought.');
    $app->redirect('/products/' . $id);
});