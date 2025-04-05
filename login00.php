

<?php
	// Connect to database
	include("db.php");
	$request_method = $_SERVER["REQUEST_METHOD"];
	
	// Autoriser les requêtes depuis n'importe quel domaine
header("Access-Control-Allow-Origin: *");

// Vérifier la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // La requête est une pré-vérification CORS, donc retourner les en-têtes appropriés sans exécuter le reste du script
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit;
}

	function getProducts()
	{
		global $conn;
		$query = "SELECT * FROM produit";
		$response = array();
		$result = mysqli_query($conn, $query);
		while($row = mysqli_fetch_array($result))
		{
			$response[] = $row;
		}
		$response[] =["fazert"];
		header('Content-Type: application/json');
		echo json_encode($response, JSON_PRETTY_PRINT);
	}
	
	function getProduct($id=0)
	{
		global $conn;
		$query = "SELECT * FROM produit";
		if($id != 0)
		{
			$query .= " WHERE id=".$id." LIMIT 1";
		}
		$response = array();
		$result = mysqli_query($conn, $query);
		while($row = mysqli_fetch_array($result))
		{
			$response[] = $row;
		}
		header('Content-Type: application/json');
		echo json_encode($response, JSON_PRETTY_PRINT);
	}
	

		
	function AddProduct() {
    global $conn;
    $email = $_POST["userEmail"];
    $password = $_POST["userPassword"];
    // $role = $_POST["userType"];
    
    // Vérifier si l'email existe déjà dans la base de données
    // $check_query = "SELECT * FROM users WHERE Email = '".$email."'";
    // $result = mysqli_query($conn, $check_query);

    // if(mysqli_num_rows($result) > 0) {
    //     // L'email existe déjà, vérifier si les identifiants sont corrects
    //     $user = mysqli_fetch_assoc($result);
    //     if(password_verify($password, $user['Password'])) {
    //         // Les identifiants sont corrects, renvoyer une réponse avec le statut de connexion
    //         $response = array(
    //             'status' => 1,
    //             'status_message' => 'Connexion.'
    //         );
    //     } else {
    //         // Les identifiants sont incorrects
    //         $response = array(
    //             'status' => 2,
    //             'status_message' => 'Identifiants incorrects.'
    //         );
    //     }
    // } else {
    //     // L'email n'existe pas dans la base de données
    //     $response = array(
    //         'status' => 2,
    //         'status_message' => 'Aucun compte lié à cette adresse mail.'
    //     );
    // }
    
    // header('Content-Type: application/json');
    // echo json_encode($response);


	
// Requête SQL pour vérifier l'utilisateur
$sql = "SELECT * FROM users WHERE Email = '".$email."'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // L'utilisateur existe dans la base de données
    $row = $result->fetch_assoc();
	$hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // if ($row['Password'] == $hashed_password) {
		if (password_verify($password, $row['Password'])) {
        // Le mot de passe est correct
        $response = array("status" => "success", "message" => "Connexion réussie", "role" => $row['Role'], "idWp" => $row['Id_Wp']);
    } else {
        // Le mot de passe est incorrect
        $response = array("status" => "error", "message" => "Mot de passe incorrect");
    }
} else {
    // L'utilisateur n'existe pas dans la base de données
    $response = array("status" => "error", "message" => "Utilisateur non trouvé");
}

// Fermeture de la connexion à la base de données
$conn->close();

// Retourner la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
}


	function updateProduct($id)
	{
		global $conn;
		$_PUT = array();
		parse_str(file_get_contents('php://input'), $_PUT);
		$name = $_PUT["name"];
		$description = $_PUT["description"];
		$price = $_PUT["price"];
		$category = $_PUT["category"];
		$created = 'NULL';
		$modified = date('Y-m-d H:i:s');
		$query="UPDATE produit SET name='".$name."', description='".$description."', price='".$price."', category_id='".$category."', modified='".$modified."' WHERE id=".$id;
		
		if(mysqli_query($conn, $query))
		{
			$response=array(
				'status' => 1,
				'status_message' =>'Produit mis a jour avec succes.'
			);
		}
		else
		{
			$response=array(
				'status' => 0,
				'status_message' =>'Echec de la mise a jour de produit. '. mysqli_error($conn)
			);
			
		}
		
		header('Content-Type: application/json');
		echo json_encode($response);
	}
	
	function deleteProduct($id)
	{
		global $conn;
		$query = "DELETE FROM produit WHERE id=".$id;
		if(mysqli_query($conn, $query))
		{
			$response=array(
				'status' => 1,
				'status_message' =>'Produit supprime avec succes.'
			);
		}
		else
		{
			$response=array(
				'status' => 0,
				'status_message' =>'La suppression du produit a echoue. '. mysqli_error($conn)
			);
		}
		header('Content-Type: application/json');
		echo json_encode($response);
	}
	
	switch($request_method)
	{
		
		case 'GET':
			// Retrive Products
			if(!empty($_GET["id"]))
			{
				$id=intval($_GET["id"]);
				getProduct($id);
			}
			else
			{
				getProducts();
			}
			break;
		default:
			// Invalid Request Method
			header("HTTP/1.0 405 Method Not Allowed");
			break;
			
		case 'POST':
			// Ajouter un produit
			AddProduct();
			break;
			
		case 'PUT':
			// Modifier un produit
			$id = intval($_GET["id"]);
			updateProduct($id);
			break;
			
		case 'DELETE':
			// Supprimer un produit
			$id = intval($_GET["id"]);
			deleteProduct($id);
			break;

	}
?>