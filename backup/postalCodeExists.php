<?php
	include_once ("bd.php");
	header('Access-Control-Allow-Origin: *');

	connection_db();
	$adresse 	= $_REQUEST['pc'];
	$adresse	= strtolower($adresse);
	$adresse 	= trim($adresse);
	$adresse	= str_replace(" ","",$adresse);

    //isValidCodePostal() in bd?
	$query		= "SELECT codePostal FROM Poste_Canada WHERE codePostal='$adresse' LIMIT 1;";
	$res		= mysql_query($query);
	$retour		= -1;

	if($res){
		$num_rows	= mysql_num_rows($res);
		// echo "<br>num_rows 1 fois : $num_rows";
		if($num_rows > 0){
			$retour=0;
		}

	/*	// Nadia Tahiri 10 May 2018 add else
		else{
			$tab_content=getCodePostalData($adresse);
			$longitude  = $tab_content[0];
			$latitude   = $tab_content[1];
			$database	= $tab_content[2];


			$provinceCP  = $tab_content[3];
			$villeCP  = $tab_content[4];
			$afficheFR  = $tab_content[5];
			$afficheEN  = $tab_content[6];
			$distanceCP  = $tab_content[7];
			$valide  = $tab_content[8];
			$ins  = $tab_content[9];

			// echo "<br>coordonnees : $adresse, $longitude , $latitude, $database, $provinceCP, $villeCP, $afficheFR, $afficheEN, $distanceCP, $valide, $ins";
			if($tab_content[0] == "" || $tab_content[1] == "" || $tab_content[3] == "" || $tab_content[4] == ""){
				$retour = -1;
			}
			$retour = 0;
		}*/
	}

	deconnection_db();

	//== build json and matrix files on VPS
	//--NON -> Etienne: ceci est fait a partir du code de fastory.js -> Franchise.loadFranchise($http,$rootScope, $scope.postalCode); finalement
	// Si on change de code postal, on appel cette fonction dans le mainCtrl -> changePromotion (si le code postal est valide)
	// Update by Nadia Tahiri 26 April 2018
	// $tmp = file("http://localhost/get_new_codepostal.php?adresse=$adresse"); old version
	// $tmp = file("http://localhost/get_new_codepostal.php?adr=$adresse");

	//echo json_encode($retour);
	echo json_encode(0);
	
?>
