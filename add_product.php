<?php
session_start();
//gets inputs from add product form
$uploadsDir = "uploads/";
$allowedFileType = array('jpg', 'png', 'jpeg');

$name = filter_input(INPUT_POST, 'name');
$desc = filter_input(INPUT_POST, 'description');
$category = filter_input(INPUT_POST, 'cat');
$price = filter_input(INPUT_POST, 'price');

//validate inputs
if ($name == null || $price == null) {
    $error_message = "Invalid product data. Check all fields and try again.";
    include('add_product_error.php');
}
else {
    
    require_once('database.php');
    $query = 'INSERT INTO product (userId, name, description, categoryId, price, createDate, sold, customerId)
                VALUES (:userId, :name, :description, :categoryId, :price, CURRENT_TIMESTAMP, 0, 0)';
    $statement = $db->prepare($query);
    $statement->bindValue(':userId', $_SESSION['userID']);
    $statement->bindValue(':name', $name);
    $statement->bindValue(':description', $desc);
    $statement->bindValue(':categoryId', $category);
    $statement->bindValue(':price', $price);
    $statement->execute();
    $statement->closeCursor();

    $productId = $db->lastInsertId();
    //https://www.positronx.io/php-multiple-files-images-upload-in-mysql-database/
    //if images exist
   //if (!empty(array_filter($_FILES['imageFile']['name']))) {
    //if ($_FILES['imageFile']['size'] != 0 && $_FILES['imageFile']['error'] == 0) {
    if ($_FILES['imageFile']['size'] > 0) {
        foreach ($_FILES['imageFile']['name'] as $id=>$val) {
            $fileName = $_FILES['imageFile']['name'][$id];
            $tempLocation = $_FILES['imageFile']['tmp_name'][$id];
            $uploadDate = date('Y-m-d H:i:s');
            $targetFilePath = $uploadsDir.$fileName.'.'.$uploadDate;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $uploadOK = 1;
            if(in_array($fileType, $allowedFileType)) {
                if(move_uploaded_file($tempLocation, $targetFilePath)) {
                    $image = $uploadsDir.$fileName.'.'.$uploadDate;
                }
                else {
                    $error_message = "Image could not be uploaded.";
                    include('add_product_error.php');
                }
            }
            else {
                $error_message="Only .jpg, .jpeg, and .png file formats allowed.";
            }
            if(!empty($image)) {
                $query = 'INSERT INTO image (type, typeId, image) VALUES (0, :typeId, :image)';
                $statement = $db->prepare($query);
                $statement->bindValue(':typeId', $productId);
                $statement->bindValue(':image', $image);
                $statement->execute();
                $statement->closeCursor();
            }
        }
    }
    else { //no images added
        $query = 'INSERT INTO image (type, typeId, image) VALUES (0, :typeId, "images/camera-icon.png")';
        $statement = $db->prepare($query);
        $statement->bindValue(':typeId', $productId);
        $statement->execute();
        $statement->closeCursor();   
    }

    //if product input valid, takes user back to their account page
    $_SESSION["productId"] = $productId;
    include('product.php');
}

?>