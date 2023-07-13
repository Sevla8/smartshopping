<?php
	/////////////////////////////////////////////////////////
	//
	// BD pour SmartShopping 2 - trex2
	//
	//----------------------------------------------------//
	//Connexion a la base de données
	//----------------------------------------------------//
    date_default_timezone_set('America/Montreal');

    function connection_db(){
      // Update by Nadia Tahiri 23 March 2018 to connect BD jurassic
      // $host = "smshopping.db.11730095.hostedresource.com"; //trex.labunix.uqam.ca
      // $user = "smshopping";
      // $pass = "Escher1ch1@";
      // $bdd = "smshopping";

      $host = "localhost"; //jurassic.uqam.ca
      $user = "root";
      $pass = "";
      $bdd = "smartshopping";

     $link = mysqli_connect($host, $user, $pass) or saveLog("Impossible de se connecter à la base de données 3 : " . mysql_error());
      mysqli_select_db($link, $bdd) or saveLog("Impossible de se connecter à la base de données 4");
	  mysqli_query($link, "SET NAMES 'utf8'");  //--Test
	  //--Create a tmp directory
	  //if (!is_dir('smartshopping')) {system("mkdir /tmp/smartshopping");}
	  return $link;
    }

	//--Deprecated
	function connection_from_trex2_db(){
    // Update by Nadia Tahiri 23 March 2018 to connect BD jurassic
    // $host = "smshopping.db.11730095.hostedresource.com"; //trex.labunix.uqam.ca
    // $user = "smshopping";
    // $pass = "Escher1ch1@";
    // $bdd = "smshopping";

    $host = "localhost"; //jurassic.uqam.ca
    $user = "smartshopping";
    $pass = "Escher1ch1a";
    $bdd = "smartshopping";
      @mysql_connect($host, $user, $pass) or die("Impossible de se connecter à la base de données 3 : " . mysql_error());
      @mysql_select_db($bdd) or die("Impossible de se connecter à la base de données 4");
	  @mysql_query("SET NAMES 'utf8'");  //--Test
    }

	function deconnection_db($link) {
 		mysqli_close($link);
	}

	 function execute_query_db($link, $query){
      return mysqli_query($link, $query);
    }

   function AlreadyExists($email)
   {
     connection_db();
  	 $query = "SELECT * FROM Users WHERE email= '" . $email . "'";
  	 return (mysql_num_rows(execute_query_db($query)) > 0);
   }

		  function startsWith($haystack, $needle)
		{
			return !strncmp($haystack, $needle, strlen($needle));
		}

		function endsWith($haystack, $needle)
		{
			$length = strlen($needle);
			if ($length == 0) {
				return true;
			}

			return (substr($haystack, -$length) === $needle);
		}

   //==============================================================
	//== Recherche du prix de l'essence en fonction de la database
	//==============================================================
    function getFuelPrice($id_database){
		connection_db();
  	 	$query = "SELECT prix_essence FROM Ville WHERE id_database= '" . $id_database . "'";
		$res = execute_query_db($query);
		$price = 0;
		while($row = mysql_fetch_assoc($res)) {
        	$price = $row['prix_essence'];
      	}
		return $price;
    }

	function distance($lat1, $lng1, $lat2, $lng2){
      	$pi80 = M_PI / 180;
      	$lat1 *= $pi80;
      	$lng1 *= $pi80;
      	$lat2 *= $pi80;
      	$lng2 *= $pi80;
      	$r = 6372.797; // mean radius of Earth in km
      	$dlat = $lat2 - $lat1;
      	$dlng = $lng2 - $lng1;
      	$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
      	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
      	$km = $r * $c;
      	return $km;
	}

	function getIdDatabaseFromIP($ip){

		connection_db();
		$query = "SELECT id_database,codePostal FROM AdresseIP WHERE ip = '$ip'";
		$res = execute_query_db($query);

		if(mysql_num_rows($res) > 0){
			$id_database  = mysql_result($res,0,"id_database");
			$code_postal = mysql_result($res,0,"codePostal");
			return array ($id_database,$code_postal);
		}
		else{
			//echo "\nhttp://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=$ip";
			$tags = get_meta_tags("http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=$ip");
			$longitude = $tags['longitude'];
			$latitude  = $tags['latitude'];

			if (!is_numeric($longitude)){
				return array(0,0);
			}
			//echo "\nlongitude=$longitude , latitude=$latitude";

			$query = "SELECT longitudeVille,latitudeVille,id_database,codePostal FROM Ville";
            $res2 = execute_query_db($query);

            $min = 100000;
			$cp_final          = "";
			$id_database_final = "";

            while($row2 = mysql_fetch_assoc($res2)) {
                $longitude1  = $row2['longitude'];
                $latitude1   = $row2['latitude'];
                $id_database = $row2['id_database'];
				$cp          = $row2['codePostal'];

                $dist = distance($latitude,$longitude,$latitude1,$longitude1);
                //echo "<br><font color=red>" . $row['nomVille'] . " : $dist : $id_db</font>";
                //echo "\n => distance=$dist";
                if ($dist < $min){
                    $min = $dist;
					$cp_final = $cp;
					$id_database_final = $id_database;
                    //echo " , id_database=$id_database,code postal=$cp";
                }
            }
			if($min < 5000){
				$query = "INSERT INTO AdresseIP VALUES ($id_database_final,'$cp_final','$ip')";
                //echo "\n$query";
				execute_query_db($query);
				return array($id_database_final,$cp_final);
			}
		}

		return array(0,0);

	}

	//===========================================================
	//== Recherche de l'id_database en fonction
	//========================================db_handle===================
   	function getIdDataBaseFromPostalCode($codePostal){

		connection_db();
        $longitude = 0;
        $latitude  = 0;
		$id_database = 0;

  	 	$query = "SELECT longitude,latitude,id_database FROM Code_Postal WHERE codePostal= '" . $codePostal . "' LIMIT 1;";
		$res = execute_query_db($query);

		while($row = mysql_fetch_assoc($res)) {
        	$longitude = $row['longitude'];
        	$latitude  = $row['latitude'];
			$id_database = $row['id_database'];
      	}

		if($id_database == 0){
			$dist = $min = 10000;
			$query = "SELECT longitude,latitude,nomVille,Ville.id_database AS id_db FROM Ville,Code_Postal WHERE Ville.codePostal=Code_Postal.codePostal;";
			//echo "<br><font color=red>$query</font>";
			$res = execute_query_db($query);
			while($row = mysql_fetch_assoc($res)) {
            	$longitude1 = $row['longitude'];
            	$latitude1  = $row['latitude'];
            	$id_db      = $row['id_db'];
        		$dist = distance($latitude,$longitude,$latitude1,$longitude1);
				//echo "<br><font color=red>" . $row['nomVille'] . " : $dist : $id_db</font>";
				if ($dist < $min){
					$min = $dist;
					$id_database = $id_db;
				}
			}
			$query = "UPDATE Code_Postal SET id_database=$id_database WHERE codePostal='$codePostal'";
			execute_query_db($query);
		}

		return $id_database;
	}

	function getIdDataBaseFromIdProduit($fid_produit){
		$query = "SELECT id_database FROM Produit WHERE id_produit LIKE '$fid_produit%';";
			$res = execute_query_db($query);
			while($row = mysql_fetch_assoc($res)) {
            	return $row['id_database'];
			}
			return 0;
	}

	function getInfoFromIdProduit($fid_produit){
			$query = "SELECT date, entered_date,id_database,status FROM Produit WHERE id_produit LIKE '$fid_produit%' LIMIT 1;";
			$stack_panier=array();
			$res = execute_query_db($query);
			while($row = mysql_fetch_assoc($res)) {
            	$stack_panier['id_produit']=$fid_produit;
				$stack_panier['date']=$row['date'];
				$stack_panier['entered_date']=$row['entered_date'];
				$stack_panier['status']=$row['status'];
				$stack_panier['id_database']=$row['id_database'];
			}
			return $stack_panier;
	}

	//===========================================================
    //== Recherche de l'id_database en fonction
    //===========================================================
    function updateIdDataBaseInFranchise(){

        connection_db();
        $longitude = 0;
        $latitude  = 0;
        $id_database = 0;

		$query = "SELECT longitude,latitude,id_franchise,nom_franchise FROM Franchise";
        $res = execute_query_db($query);


        while($row = mysql_fetch_assoc($res)) {
            $longitude     = $row['longitude'];
            $latitude      = $row['latitude'];
            $id_franchise  = $row['id_franchise'];
            $nom_franchise = $row['nom_franchise'];

			echo "\nTraitement de la franchise $id_franchise : $nom_franchise";

			$query = "SELECT longitude,latitude,Ville.id_database AS id_db FROM Ville,Code_Postal WHERE Ville.codePostal=Code_Postal.codePostal";
        	$res2 = execute_query_db($query);

            $min = 100000;
            while($row2 = mysql_fetch_assoc($res2)) {
                $longitude1  = $row2['longitude'];
                $latitude1   = $row2['latitude'];
            	$id_database = $row2['id_db'];
				$dist = distance($latitude,$longitude,$latitude1,$longitude1);
                //echo "<br><font color=red>" . $row['nomVille'] . " : $dist : $id_db</font>";
				echo "\n => distance=$dist";
                if ($dist < $min){
					$min = $dist;
					echo " , id_database=$id_database";
            		$query = "UPDATE Franchise SET id_database=$id_database WHERE id_franchise=$id_franchise";
            		execute_query_db($query);
                }
            }
        }
    }

	//===========================================================
    //== Recherche de l'id_database en fonction
    //===========================================================
    function updateCode_Postal_Table($fichier){

		echo "\nOuverture du fichier $fichier";
		$fp = fopen("$fichier","r"); //lecture du fichier

		while (!feof($fp)) { //on parcourt toutes les lignes
        	connection_db();
			$page = fgets($fp, 4096); // lecture du contenu de la ligne

			$zip  = explode (",",$page);
			//list($codePostal,$latitude,$longitude,$ville,$province) = explode (",",$page);
			$codePostal = strtolower($zip[0]);
			$longitude  = strtolower($zip[1]);
			$latitude   = strtolower($zip[2]);
			$ville      = "";
			for($i=3;$i<count($zip)-1;$i++)
				$ville .= $zip[$i];
			$ville      = str_replace("'","\'",$ville);
			$province   = str_replace("\n", "", $zip[count($zip)-1]);

			echo "\n$codePostal<>$latitude<>$longitude<>$province<>$ville<>";

			$query = "SELECT nomVille,id_database,longitudeVille,latitudeVille FROM Ville";
			$res   = execute_query_db($query);
        	$min   = 100000;
			$id    = 0;
			$dist  = 0;
        	while($row = mysql_fetch_assoc($res)) {
        		$longitude1  = $row['longitudeVille'];
        		$latitude1   = $row['latitudeVille'];
       			$id_database = $row['id_database'];
 				$nomVille    = $row['nomVille'];

				$dist = distance($latitude,$longitude,$latitude1,$longitude1);
            	//echo "<br><font color=red>" . $row['nomVille'] . " : $dist : $id_db</font>";
				echo "\n$nomVille,$id_database -> $dist";
            	if ($dist < $min){
					$min = $dist;
					$id = $id_database;
            	}
        	}
			if($min < 100000){
				$affiche = $ville;
				if($min > 50) {
					$affiche = $province;
				}
            	$query = "UPDATE Code_Postal SET id_database=$id,provinceCP='$province',villeCP='$ville',afficheCP='$affiche',distanceCP=$min WHERE codePostal='$codePostal'";
            	echo "\n$min : $query";
				execute_query_db($query);
			}
			//deconnection_db();;
		}
		fclose($fp);
    }

	//=================================================================================
	//= This return an array of informations from the table code_postal
	//=================================================================================
	function getCodePostalData($codePostal){
      $ins =0;
    	if ($codePostal=="") return array();
		//--be sure of the format
		$codePostal = strtolower($codePostal);
		$codePostal = trim($codePostal);
		$codePostal=str_replace(" ","",$codePostal);
		//echo "<br>code postal = $codePostal";
		$longitude  = "";
		$latitude   = "";
		$id_database= "";
		$provinceCP = "";
		$villeCP    = "";
		$afficheFR  = "";
		$afficheEN  = "";
		$distanceCP = "";
		$valide     = 0;

		#= Recherche du fichier de donnees
		$fichier = "/tmp/$codePostal.info";
		if(file_exists("$fichier")){
			#echo "<br>Le fichier $fichier existe";
			$data = file_get_contents($fichier);
			list($longitude,$latitude,$id_database,$provinceCP,$villeCP,$afficheFR,$afficheEN,$distanceCP,$valide) = explode("<>",$data);
		}
		else{
      // Commenter par Nadia Tahiri 10 May 2018
		// echo "<br>Le fichier $fichier n'existe pas";
            //-- On est dans bd ... connection_db();
			$query = "SELECT * FROM Code_Postal WHERE codePostal='$codePostal'";
			//echo $query;
			$res = execute_query_db($query);
			if (mysql_num_rows($res) > 0){
				$row = mysql_fetch_assoc($res);
				//echo "<br>$codePostal existe dans la bd";
				$valide     = $row['valideGMap'];
				$longitude  = $row['longitude'];
				$latitude   = $row['latitude'];
				$id_database= $row['id_database'];
				$provinceCP = $row['ProvinceCP'];
				$villeCP    = $row['VilleCP'];
				$afficheFR  = $row['AfficheFR'];
				$afficheEN  = $row['AfficheEN'];
				$distanceCP = $row['distanceCP'];
				#echo "<br>$longitude<>$latitude<>$id_database<>$provinceCP<>$villeCP<>$affichageCP<>$distanceCP";
				file_put_contents($fichier,"$longitude<>$latitude<>$id_database<>$provinceCP<>$villeCP<>$afficheFR<>$afficheEN<>$distanceCP<>$valide");
			}
			else{
				//echo "<br>$codePostal n'existe pas dans la bd";
				#= recherche dans le fichier
				$root = substr($codePostal,0,3);
				$fichierCP = "codePostal/$root.cp";
				//echo "<br>Lecture de $codePostal dans le fichier $fichierCP";

				if(file_exists("$fichierCP")){
					$fp = fopen("$fichierCP","r");
					while (!feof($fp)) { //on parcourt toutes les lignes
            			$page = fgets($fp, 4096); // lecture du contenu de la ligne
            			$zip  = explode (",",$page);
            			$cp = strtolower($zip[0]);
						if ($cp == $codePostal){

							$ville    = "";
              $province = "";
              //== est ce que l'information est dans la table Poste_Canada ?
              $query = "SELECT * FROM Poste_Canada WHERE codePostal='$codePostal' AND geocode=1 ";
              $res   = mysql_query($query);
							if (!$res) {
								savelog("error bd 124: $query");
							}
                            if(mysql_num_rows($res) == 0){

								$json   = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($codePostal).'&sensor=false&region=ca');
								$output = json_decode($json);
								$lat    = $output->results[0]->geometry->location->lat;
								$lng    = $output->results[0]->geometry->location->lng;
            					#echo "lat=$lat,lng=$lng";
								if($lat == ""){
									$valide = 0;
								}
								else{
									$valide = 1;
								}
								// -- On est dans bd...connection_db();
								//$longitude  = strtolower($zip[2]); //strtolower($zip[2]); $lng;
            					//$latitude   = strtolower($zip[1]); //strtolower($zip[1]); $lat;
								$longitude  = $lng;
            					$latitude   = $lat;

								$ville      = "";
            					for($i=3;$i<count($zip)-1;$i++)
                					$ville .= $zip[$i];
            					$ville      = str_replace("'","\'",$ville);
								$ville		= str_replace("É","é",$ville);
								$ville 		= utf8_encode($ville);
								//--Non car cas spéciaux
								//$ville		= ucfirst(strtolower($ville));
								$ville		= ucfirst($ville);
            		$province   = str_replace("\n", "", $zip[count($zip)-1]);

            					#echo "<br>On a trouve ca : $codePostal<>$latitude<>$longitude<>$province<>$ville<>";

								$query = "SELECT * FROM Poste_Canada WHERE codePostal='$codePostal'";
                                $res   = mysql_query($query);
                                if(mysql_num_rows($res) == 0){
                                	$query = "INSERT INTO Poste_Canada VALUES('$codePostal',$latitude,$longitude,'$ville','$province',$valide,$valide)";
                                    mysql_query($query);
                                    $ins = 1;
                                }
                                else{
                                    $query = "UPDATE Poste_Canada SET geocode=$valide,latitude=$latitude,longitude=$longitude,google=$valide WHERE codePostal='$codePostal'";
                                 	//echo "$query";
								   	mysql_query($query);
                                }
							}
							else{
                      $ville     = mysql_result($res,0,"ville");
                      $province  = mysql_result($res,0,"province");
                      $latitude  = mysql_result($res,0,"latitude");
                      $longitude = mysql_result($res,0,"longitude");
                      $valide  = 1;
              }

            				$query = "SELECT nomVille,id_database,longitudeVille,latitudeVille FROM Ville WHERE ProvinceCP='$province'";
            				$res   = execute_query_db($query);
           			 		$min   = 100000;
            				$id    = 0;
            				$dist  = 0;
            				while($row = mysql_fetch_assoc($res)) {
                				$longitude1  = $row['longitudeVille'];
                				$latitude1   = $row['latitudeVille'];
                				$id_database = $row['id_database'];
                				$nomVille    = $row['nomVille'];

                				$dist = distance($latitude,$longitude,$latitude1,$longitude1);
                			//echo "<br><font color=red>" . $row['nomVille'] . " : $dist : $id_db</font>";
                				#echo "<br>$nomVille,$id_database -> $dist";
                				if ($dist < $min){
                    				$min = $dist;
                    				$id = $id_database;
                				}
            				}
            				if($min < 100000){
                				$afficheFR = $afficheEN = $ville;
                				if($min > 50) {
                   	 				$afficheFR = $afficheEN = $province;
                				}

								//== ajouter par alix le 9 février 2013
								// -> Changement Etienne Query d'insertion après car on veut les données de province, etc...
								//== pour generer le fichier de distances en meme temps
								getDistancesWithFranchises($codePostal,$id,$latitude,$longitude);
        						$id_database= $id_database;
        						$provinceCP = $province;
        						$villeCP    = $ville;
        						$distanceCP = $dist;
								//--Voir ci-haut
								$query = "INSERT INTO Code_Postal VALUES ('$codePostal',$valide,'$longitude','$latitude',$id,'$province','$ville','$afficheFR','$afficheEN',$dist)";
								file_put_contents($fichier,"$longitude<>$latitude<>$id<>$province<>$ville<>$afficheFR<>$afficheEN<>$dist<>$valide");
								execute_query_db($query);
            				}
        				}
					}
					fclose($fp);
				} else {
					//echo "pas de fichier $fichierCP";
				}
			}
		}

		return array($longitude,$latitude,$id_database,$provinceCP,$villeCP,$afficheFR,$afficheEN,$distanceCP,$valide,$ins);
		#= Recherche de l'information dans la bd
    }

	//--This function is used only in getNearestFranchisesJSON_limit
	function getDistanceGroup_NearestFranchises($group, $flat, $flong) {
		$dist=0;
		$grp_lat=0;
		$grp_long=0;
		$grp_count=0;
		//--Get the center of the group
		foreach($group as $nom_enseigne=>$data) {
			$grp_lat+=$data['latitude'];
			$grp_long+=$data['longitude'];
			$grp_count++;
		}
		if ($grp_count==0) return $dist;
		$grp_lat=$grp_lat/$grp_count;
		$grp_long=$grp_long/$grp_count;
		$dist=distance($flat,$flong,$grp_lat,$grp_long);
		return $dist;
	}

	//========================================================================================================================
	//== Cette fonction permet de mettre dans un fichier JSON les <$max_franchise> franchises se trouvant dans un périmètre
	//== <$perimetre> d'un <$codePostal>. Ce json comprend en plus trois arrays: franchise, franchise_seule et group
	//== L'array [franchise] comprend la liste complete, l'array [franchise_not_in_group] celles qui ne sont pas en groupe
	//== et en [groups] celles se trouvant dans une distance <$distance_limite> l'une de l'autre
	//== auteur : Etienne Lord
	//== date   : 1er Février 2013
	//========================================================================================================================
	function getNearestFranchisesJSON_limit($codePostal, $perimetre, $max_franchise, $distance_limite) {
		//error_reporting(E_ALL);
		$codePostal = strtolower($codePostal);
		$fichier_json="/tmp/$codePostal"."_".$perimetre.".franchises.json";
		$stack=array();
		if(file_exists($fichier_json)){
			return file_get_contents($fichier_json);
		} else {
		// 1. Get the total array of franchise
		$franchise= json_decode(getNearestFranchisesJSON($codePostal),true);
		 //error_reporting(E_ALL);

		// 1.5 Test if we have any franchise

		if (count($franchise)==0) {
			//--Create empty array
			$stack['perimetre']=$perimetre;
			$stack['recherche_perimetre']=$perimetre;
			$stack['franchise']=array();
			$stack['other_franchise']=array();
			$stack['groups']=array();
			$stack['franchise_not_in_group']=array();
			$fp = fopen("$fichier_json",'w');
			fwrite($fp,json_encode($stack));
			fclose($fp);
			return json_encode($stack);
		}
		// 2. Find the one we want to display
				$i=0;
				$count=0;
				$displayed=array();
				$other_franchise=array();
				$displayed_list=array();
				$perimetre_old=$perimetre;
				$row['distance']=0;
				$n_min=2; //--Minimum number of stores to find
				$max_radius=250; //--Max radius to search in km
				//--First while is to ensure that we have at least n_min store in the radius
				while (count($displayed_list)<$n_min&&$perimetre<$max_radius) {
					 while($row['distance']<$perimetre&&$count<$max_franchise&&$i<count($franchise))
					{
						$row = $franchise[$i++];
						//echo $row['nom_enseigne'];
						if (!isset($displayed[$row['nom_enseigne']])) {
							if ($row['distance']<$perimetre) {
								$displayed[$row['nom_enseigne']]=true;
								array_push($displayed_list,$row);
								unset($franchise[$i-1]);
								$count++;
							}
						}
					}
					if (count($displayed_list)<$n_min) {
						$perimetre+=5;
						$i=0;
						$franchise=array_values($franchise);
					}
				}
				$stack['perimetre']=$perimetre;
				$stack['recherche_perimetre']=$perimetre_old;
				$stack['franchise']=$displayed_list;
				$stack['other_franchise']=array();
				// Other franchise is the perimetre

				foreach ($franchise as $data) {
					if ($data['distance']<$perimetre) {
						array_push($stack['other_franchise'], $data);
					}
				}
		//3. Compute the group using the distance_limit

				$count=0;
				$displayed=array();
				$groups=array();
				$groups_count=0;

				//--Detect overlap of marker N^2
				for ($i=0; $i<sizeof($displayed_list)-1; $i++) {
					for ($j=$i+1; $j<sizeof($displayed_list);$j++) {
							if (distance($displayed_list[$i]['latitude'],$displayed_list[$i]['longitude'],$displayed_list[$j]['latitude'],$displayed_list[$j]['longitude'])<$distance_limite) {
								//1. Create group if not exists
								$already_added=false;
								for ($k=0; $k<$groups_count;$k++) {
									//--Try to find in a group and add
										$dgroup=$groups[$k];
										if (isset($dgroup[$displayed_list[$i]['nom_enseigne']])||isset($dgroup[$displayed_list[$j]['nom_enseigne']])) {
											if (!$already_added) {
												$dgroup[$displayed_list[$j]['nom_enseigne']]=$displayed_list[$j];
												$already_added=true;
												$groups[$k]=$dgroup;
											}
										}
								}
								if (!$already_added) {
									$new_group=array();
									$new_group[$displayed_list[$i]['nom_enseigne']]=$displayed_list[$i];
									$new_group[$displayed_list[$j]['nom_enseigne']]=$displayed_list[$j];
									$groups[$groups_count]=$new_group;
									$groups_count++;
								}
							}
					} //--End for j
				} //--End for i

				// 3.45 Be sure the first element of each group don't overlaps
				//-- 1. iterate over each group
				//-- 2. if element intersect, calculate smallest distance (not done)
				if ($groups_count>1) {
					for ($i=0; $i<$groups_count;$i+=2) {
						for ($j=$i+1; $j<$groups_count;$j++) {
							$tmp_group_i=$groups[$i];
							$tmp_group_j=$groups[$j];
							foreach($tmp_group_i as $nom_enseigne=>$data_i) {
								foreach($tmp_group_j as $nom_enseigne_j=>$data_j) {
									if ($nom_enseigne==$nom_enseigne_j) {
										//--Where to kept i or j
										//--get distance from each groups

										$dist_i=getDistanceGroup_NearestFranchises($tmp_group_i, $data_j['latitude'], $data_j['longitude']);
										$dist_j=getDistanceGroup_NearestFranchises($tmp_group_j, $data_j['latitude'], $data_j['longitude']);
										if ($dist_i<$dist_j) {
											unset($groups[$i][$nom_enseigne]);
										} else {
											unset($groups[$j][$nom_enseigne]);
										}
									}
								}
							}
							$groups=array_values($groups);
						} //--End for j
					} //--End for i
				} //--if group_count >1

				// 3.5 Sort the element in the groups in order of latitude
				// and add the mean latitude and longitude
				$array_group_longitude=array();
				for ($i=0; $i<$groups_count;$i++) {
					$tmp_group=$groups[$i];
					$tmp_latitude_array=array();
					$tmp_longitude_array=array();
					$tmp_latitude=0;
					$tmp_longitude=0;
					foreach ($tmp_group as $data) {
						array_push($tmp_latitude_array, (float)$data['latitude']);
						array_push($tmp_longitude_array, (float)$data['longitude']);
						$tmp_longitude+=$data['longitude'];
						$tmp_latitude+=$data['latitude'];
					}
					array_multisort($tmp_latitude_array,SORT_ASC, $tmp_group);
					if (sizeof($tmp_latitude_array)>0) {
						$tmp_group['latitude']=$tmp_latitude/sizeof($tmp_latitude_array);
						$tmp_group['longitude']=$tmp_longitude/sizeof($tmp_latitude_array);
					} else {
						$tmp_group['latitude']=$tmp_latitude;
						$tmp_group['longitude']=$tmp_latitude;
					}
					$groups[$i]=$tmp_group;
					array_push($tmp_longitude_array,99999);
					array_push($tmp_longitude_array,99999);
					array_multisort($tmp_longitude_array,SORT_ASC, $tmp_group);
					$array_group_longitude[$i]=$tmp_group;
				}
				$stack['groups']=$groups;
				$stack['groups_longitude']=$array_group_longitude;
				// 3.6 Sort the element in the groups in order of longitude

				//4. Remove the franchise in the group from the display_list
				$array_not_in_group=array();
				$array_in_group=array();
				foreach ($groups as $group) {
					foreach ($group as $franchise) {
						if (isset($franchise['nom_enseigne'])) {
							array_push($array_in_group,$franchise['nom_enseigne']);
						}
					}
				}

				foreach($displayed_list as $data) {
					if (!in_array( $data['nom_enseigne'] ,$array_in_group )) array_push($array_not_in_group,$data);
				}
				$stack['franchise_not_in_group']=$array_not_in_group;
				//5. Save the file
				$fp = fopen("$fichier_json",'w');
				fwrite($fp,json_encode($stack));
				fclose($fp);
				return json_encode($stack);
			} //--End else
	}

	function getNearestFranchisesJSON($codePostal) {
		$codePostal = strtolower($codePostal);
		$fichier_json="/tmp/$codePostal.franchises.json";
		if(file_exists($fichier_json)){
			return file_get_contents($fichier_json);
		} else {
			getNearestFranchises($codePostal);
			return file_get_contents($fichier_json);
		}
	}

	//========================================================================================================================
	//==
	//== auteur : Alix Boc
	//== date   : 7 Fvrier 2013
	//========================================================================================================================
	function distanceRoutiereMoyenne($geo1,$geo2,$lat1, $lng1, $lat2, $lng2)
	{
		//return distance($lat1, $lng1, $lat2, $lng2);
		//== DEBUT : code temporaire pour mettre a jour la distance a vol d'oiseau dans la table Distance_Google, a supprimer le temps venu
		/*$query      = "SELECT * FROM Distance_Google WHERE distance_vol_oiseau = -1 AND ( (geo1='$geo1' AND geo2='$geo2') OR (geo1='$geo2' AND geo2='$geo1') )";
		$res 		= mysql_query($query);
		if(mysql_num_rows($res) > 0){
			$dist  = distance($lat1, $lng1, $lat2, $lng2);
			$query = "UPDATE Distance_Google SET distance_vol_oiseau=$dist WHERE geo1='$geo1' AND geo2='$geo2'";
			mysql_query($query);
			$query = "UPDATE Distance_Google SET distance_vol_oiseau=$dist WHERE geo1='$geo2' AND geo2='$geo1'";
			mysql_query($query);
		}*/
		//== FIN
		$user_privilege=-1;

		if($geo1 == $geo2){
			return 0;
		}
		else{
			$geo1  = str_replace(" ","",strtolower($geo1));
			$geo2  = str_replace(" ","",strtolower($geo2));
			//= distance routiere dans un sens
			$query1 = "SELECT distance FROM Distance_Google WHERE geo1='$geo1' AND geo2='$geo2'";
		   	$res1   = mysql_query($query1);
			$km1    = -1;
			if(mysql_num_rows($res1) == 1){
				$km1 = mysql_result($res1,0,"distance");
				$km1 = $km1/1000.0;
			}
			//= distance routiere dans l'autre sens
			$query2 = "SELECT distance FROM Distance_Google WHERE geo1='$geo2' AND geo2='$geo1'";
		   	$res2   = mysql_query($query2);
			$km2    = -1;
			if(mysql_num_rows($res2) == 1){
				$km2 = mysql_result($res2,0,"distance");
				$km2 = $km2/1000.0;
			}

			if ( ($km1 >= 0) && ($km2 >= 0) ){
				if (isset($user_privilege)&&$user_privilege==99) {
					echo "<br>Route aller/retour => geos : $geo1-->$geo2 : " . ($km1+$km2)/2.0 . " km";
				}
				return ($km1+$km2)/2.0;
			}
			elseif( ($km1 < 0) && ($km2 < 0) ){
				if (isset($user_privilege)&&$user_privilege==99) {
					echo "<br>Oiseau => geos : $geo1-->$geo2 : " . distance($lat1, $lng1, $lat2, $lng2) . " km";
				}
				return distance($lat1, $lng1, $lat2, $lng2);
			}
			elseif($km2 < 0){
				if (isset($user_privilege)&&$user_privilege==99) {
					echo "<br>Route aller => geos : $geo1-->$geo2 : " . $km1 . " km";
				}
				return $km1;
			}
			elseif($km1 < 0){
				if (isset($user_privilege)&&$user_privilege==99) {
					echo "<br>Route retour => geos : $geo1-->$geo2 : " . $km2 . " km";
				}
				return $km2;
			}
			else{
				if (isset($user_privilege)&&$user_privilege==99) {
					echo "<br>$geo1-$geo2 : $km1,$km2 PROBLEME DE DISTANCES";
				}
				return 1000;
			}
		}
	}

	function distanceRoutiere($geo1,$geo2,$lat1, $lng1, $lat2, $lng2)
	{
		//return distance($lat1, $lng1, $lat2, $lng2);


		$user_privilege=bd_getUserPrivilege(mysql_real_escape_string($_SESSION['id_client']));
		if($geo1 == $geo2){
			return 0;
		}
		else{
			$geo1  = str_replace(" ","",strtolower($geo1));
			$geo2  = str_replace(" ","",strtolower($geo2));
			$query = "SELECT distance FROM Distance_Google WHERE geo1='$geo1' AND geo2='$geo2'";
		   // echo "<br>$query";
			$res   = mysql_query($query);
			$km    = -1;
			if(mysql_num_rows($res) == 1){
				$km = mysql_result($res,0,"distance");
			}
			if ($km > -1){
				if (isset($user_privilege)&&$user_privilege==99) {
					echo "<br>Route => geos : $geo1-->$geo2 : " . $km/1000 . " km";
				}
				return $km/1000;
			}
		}
		$km = distance($lat1, $lng1, $lat2, $lng2);
	   // echo "<br>Oiseau => geos : $geo1-->$geo2 : " . $km . " km";
				if (isset($user_privilege)&&$user_privilege==99) {
					//echo "<br>Oiseau => geos : $geo1-->$geo2 : " . $km . " km";
				}
		return $km;
	}


	function rechercheFranchisesProches($codePostal){

		$codePostal = strtolower($codePostal);

		$fichier = "/tmp/$codePostal.json";

		if(!file_exists($fichier)){
			$arrayTypesDistances = array ("distance_vol_oiseau","distance","distance_temps","consommation");

			//= selection en fonction de la distance à vol d'oiseaux
			$query   = "SELECT * FROM Enseigne";
			$res_ens = mysql_query($query);
			$num_ens = mysql_num_rows($res_ens);

			$json = array();

			for($k=0;$k<count($arrayTypesDistances);$k++){

				$typeDistance = $arrayTypesDistances[$k];

				for($j=0;$j<$num_ens;$j++){
            		$id_enseigne  = mysql_result($res_ens, $j, "id_enseigne");
					$enseigne     = mysql_result($res_ens, $j, "nom_enseigne");
            		$img          = mysql_result($res_ens, $j, "fichier_logo");
				//echo "\n";
					$query = "SELECT * FROM Distance_Google,Franchise WHERE geo2=id_franchise AND id_enseigne=$id_enseigne AND geo1 = '$codePostal' AND distance_vol_oiseau != -1  ORDER BY ABS($typeDistance) LIMIT 5";
					$res = mysql_query($query);
					$num   = mysql_num_rows($res);

					//echo "$num : $query";
					for($i=0;$i<$num;$i++){
						$id_franchise = mysql_result($res, $i, "id_franchise");
            			$id_enseigne  = mysql_result($res, $i, "id_enseigne");
            			$adresse      = mysql_result($res, $i, "adresse");
            			$longitude    = mysql_result($res, $i, "longitude");
            			$latitude     = mysql_result($res, $i, "latitude");
						$distance	  = mysql_result($res, $i, "$typeDistance");

						$json["$typeDistance"]["$enseigne"][$i]['id_franchise'] = $id_franchise;
						$json["$typeDistance"]["$enseigne"][$i]['id_enseigne'] 	= $id_enseigne;
						$json["$typeDistance"]["$enseigne"][$i]['adresse'] 	 	= $adresse;
						$json["$typeDistance"]["$enseigne"][$i]['longitude'] 	= $longitude;
						$json["$typeDistance"]["$enseigne"][$i]['latitude'] 	= $latitude;
						$json["$typeDistance"]["$enseigne"][$i]['image'] 		 	= $img;
						$json["$typeDistance"]["$enseigne"][$i]['distance'] 	= $distance;
						//echo "\n$id_franchise<>$id_enseigne<>$adresse<>$enseigne<>$longitude<>$latitude<>$img<>$distance";
					}
				}
			}
			$json = json_encode($json);
			file_put_contents($fichier,$json);
		}
		return file_get_contents($fichier);
		//var_dump ( "\n\n" . $json);
	}

	//========================================================================================================================
	//== Cette fonction permet de mettre dans un fichier <codePostal>.franchise la liste des 5 franchises de chaque enseigne
	//== les plus proches. Il rajoute dans la base de donnees les distances entre les franchises/franchises et
	//== les franchises/codePostaux a calculer
	//== auteur : Alix Boc
	//== date   : 1er Février 2013
	//========================================================================================================================
	function getNearestFranchises($codePostal){
		//error_reporting(E_ALL);
		$codePostal = strtolower($codePostal);
		$fichier = "/tmp/$codePostal.franchises";
		$fichier_json="/tmp/$codePostal.franchises.json";
		if( file_exists($fichier) && file_exists($fichier_json) ){
		//if( file_exists($fichier_json)){
			return file_get_contents($fichier);
		}
		$tab_adresse_franchise = array();

		//== Lecture des longitude et latitude correpondants
		//=========================================================

		//$content = file_get_contents("/home/smartshopping/public_html//tmp/$codePostal.info");
		//$tab_content = explode("<>",$content);
		$tab_content=getCodePostalData($codePostal);
		$longitude  = $tab_content[0];
		$latitude   = $tab_content[1];
		$database	= $tab_content[2];
		//echo "<br>coordonnees : $longitude , $latitude, $database";

		//== Recherche de toutes les franchises
		//=========================================================
		$tab_franchises = array();
		$json_franchises= array();
		$json_franchises_distance= array();
		//$nLat = 1.4;
		//$nLong = 2;
		//= limitation des franchises dans la boite ...
		$query = "SELECT id_franchise,Franchise.id_enseigne, nom_enseigne,adresse,longitude,latitude,fichier_logo FROM Franchise,Enseigne WHERE Franchise.id_enseigne=Enseigne.id_enseigne AND id_database=$database AND latitude<($latitude+1.4) AND latitude>($latitude-1.4) AND longitude>($longitude-2) AND longitude<($longitude+2)";
		$res   = mysql_query($query);
		$num   = mysql_num_rows($res);
		$i=0;
		$liste_ctrl_franchise = array();
		//saveLog("Number of franchise: $num");
		while($i < $num){
			$id_franchise = mysql_result($res, $i, "id_franchise");
			$id_enseigne = mysql_result($res, $i, "id_enseigne");
			$adresse	  = mysql_result($res, $i, "adresse");
        	$enseigne	  = mysql_result($res, $i, "nom_enseigne");
        	$longitude2	  = mysql_result($res, $i, "longitude");
        	$latitude2	  = mysql_result($res, $i, "latitude");
        	$img		  = mysql_result($res, $i, "fichier_logo");
			$dist 		  = distanceRoutiereMoyenne($codePostal,$id_franchise,$latitude,$longitude,$latitude2,$longitude2);
			$dist_vol     = distance($latitude,$longitude,$latitude2,$longitude2);
			$tmp_array    = array('id_franchise'=>$id_franchise, 'id_enseigne'=>$id_enseigne, 'nom_enseigne'=>$enseigne,'adresse'=>$adresse, 'longitude'=>$longitude2,'latitude'=>$latitude2, 'image'=>$img, 'distance_routiere'=>$dist, 'distance'=>$dist_vol);
			array_push($json_franchises, $tmp_array);
			array_push($json_franchises_distance, $dist_vol); //==for sorting by distance vol...
			$liste_ctrl_franchise["$id_franchise"] = $id_enseigne;
			$j=0;
			if( isset($tab_franchises["$enseigne"]) ){
				$j = count ($tab_franchises["$enseigne"]);
			}
			//== on trie au fur et a mesure
			for($k=0;$k<$j;$k++){
				$tab = explode("<>",$tab_franchises["$enseigne"][$k]);
				if($dist < $tab[5]){
					for($m=$j-1;$m>=$k;$m--){
						$tab_franchises["$enseigne"][$m+1] = $tab_franchises["$enseigne"][$m];
					}
					$j = $k;
					$k = 10000;
				}
			}
			$tab_franchises["$enseigne"][$j] = "$id_franchise<>$adresse<>$longitude2<>$latitude2<>$img<>$dist";
			$i++;
		}

		//== Order JSON distance
		array_multisort($json_franchises_distance,SORT_ASC,$json_franchises);

		$fp = fopen("$fichier_json",'w');
		fwrite($fp,json_encode($json_franchises));
		fclose($fp);
		//--Etienne
		//== Ecriture des resultats dans le fichier resultat
		$fp = fopen("$fichier",'w');

		foreach($tab_franchises as $key => $value){
			$chaine=$key;
			$j=1;
			foreach ($value as $element){
				if($j<=5){
					$tab_element = explode("<>",$element);
					array_push($tab_adresse_franchise,$tab_element[0]);
					//echo "<>$element";
					$chaine .= "<>$element";
				}
				$j++;
			}
			fwrite($fp,"$chaine\n");
		}

		fclose($fp);


		//== Chargement dans la base de donnees des distances a calculer
		foreach($tab_adresse_franchise as $value1){
			$query = "INSERT INTO Distance_Google VALUES('$value1','$codePostal',-1,-1,-1,-1,1)";
			//echo "$query\n";
        	$res   = mysql_query($query);
			$query = "INSERT INTO Distance_Google VALUES('$codePostal','$value1',-1,-1,-1,-1,1)";
        	$res   = mysql_query($query);
			foreach($tab_adresse_franchise as $value2){
				if(($value1 != $value2) && ($liste_ctrl_franchise["$value1"] != $liste_ctrl_franchise["$value2"])){
					$query = "INSERT INTO Distance_Google VALUES('$value1','$value2',-1,-1,-1,-1,0)";
        			$res   = mysql_query($query);
				}
			}
		}
		//calculDeToutesLesDistancesVO();
		//--Etienne
		return file_get_contents($fichier);
	}

	function getLatLong($id_franchise){
		 if(is_numeric($id_franchise)){
         	$query = "SELECT latitude,longitude FROM Franchise WHERE id_franchise=$id_franchise";
            $res   = mysql_query($query);
            if(mysql_num_rows($res) > 0)
            	return array(mysql_result($res,0,"latitude"),mysql_result($res,0,"longitude"));
			return array(100000000,100000000);
       	}
		else{
        	$query = "SELECT latitude,longitude FROM Poste_Canada WHERE codePostal='$id_franchise'";
        	$res   = mysql_query($query);
        	if(mysql_num_rows($res) > 0)
        		return array(mysql_result($res,0,"latitude"),mysql_result($res,0,"longitude"));
        	return array(200000000,200000000);
   		}
	}


	function calculDeToutesLesDistancesVO(){
		//== Calcul de toutes les diatnces a vol d'oiseaux
		$query = "SELECT * FROM Distance_Google WHERE distance_vol_oiseau=-1";
		$res   = mysql_query($query);

		if($res){
			$num_rows 	= mysql_num_rows($res);
			for($i=0;$i<$num_rows;$i++){
				$geo1	= mysql_result($res,$i,"geo1");
				$geo2 	= mysql_result($res,$i,"geo2");
				$latLong1 = getLatLong($geo1);
				$latLong2 = getLatLong($geo2);

				$dist = distance($latLong1[0],$latLong1[1],$latLong2[0],$latLong2[1]);

				$query = "UPDATE Distance_Google SET distance_vol_oiseau=$dist WHERE geo1='$geo1' AND geo2='$geo2'";
				//echo "$query";
				mysql_query($query);

			}
		}
	}

	function getDistancesWithFranchises($codePostal,$idDatabase,$latitude,$longitude){

    	$tableau_distances = array();
    	$codePostal = strtolower($codePostal);
		$fichier_distances = "/tmp/$codePostal.distances";

	//	echo "<br>fichier = $fichier_distances";

		if(!file_exists($fichier_distances)){
			connection_db();
			$query = "SELECT * FROM Franchise WHERE id_database=$idDatabase ORDER BY id_enseigne";
	//		echo "<br>$query";
			$res = mysql_query($query);
            $nb = mysql_numrows($res);

            $distance_to_enseignes = array();

            for($i=0;$i<mysql_numrows($res);$i++){
                $latitudeH     = mysql_result($res,$i,"latitude");
                $longitudeH    = mysql_result($res,$i,"longitude");
                $id_enseigne   = mysql_result($res,$i,"id_enseigne");
                $nom_franchise = mysql_result($res,$i,"nom_franchise");

                $distance_calculee = distance($latitudeH,$longitudeH,$latitude,$longitude);

                if (isset($distance_to_enseignes[$id_enseigne])){
                    if($distance_to_enseignes[$id_enseigne] > $distance_calculee){
                        $distance_to_enseignes[$id_enseigne] = $distance_calculee;
                    }
                }
                else{
                    $distance_to_enseignes[$id_enseigne] = $distance_calculee;
                }
            }

            $output_distance = "$codePostal\n";
            foreach($distance_to_enseignes as $cle => $element){
                $value=number_format($element,1);
                $output_distance .=  "$cle $value\n";
                $sql="INSERT INTO Distance_Code_Postal(codePostal, id_enseigne, distance) VALUES ('$codePostal',$cle,$element);";
                //echo "<br>$sql";
				mysql_query($sql);
            }
            file_put_contents($fichier_distances,$output_distance);
            //deconnection_db();
        }

		$fichier_content = file($fichier_distances);
       	for ($i=1; $i<sizeof($fichier_content); $i++){
        	$tmp_array = explode(" ",$fichier_content[$i]);
           	$tableau_distances[intval($tmp_array[0])] = floatval($tmp_array[1]);
        }
		return $tableau_distances;
	}

	//===========================================================
	//== Recherche de la ville en fonction du codePostal
	//===========================================================
   	function getVilleFromPostalCode($codePostal){

		$id_database = 0;
	 	$query = "SELECT id_database FROM Code_Postal WHERE codePostal= '" . $codePostal . "';";
		$res = execute_query_db($query);

		while($row = mysql_fetch_assoc($res)) {
			$id_database = $row['id_database'];
      	}

		if($id_database != 0){
			$query = "SELECT nomVille FROM Ville WHERE id_database='$id_database';";
			//echo "<br><font color=red>$query</font>";
			$res = execute_query_db($query);
			while($row = mysql_fetch_assoc($res)) {
            	return $row['nomVille'];
			}
		} else {
			return "Montreal";
		}
	}

	//===========================================================
	//== Recherche de la ville en fonction du id_database
	//===========================================================
   	function getVilleFromDatabase($id_database){
		if($id_database != 0){
				$query = "SELECT nomVille FROM Ville WHERE id_database='$id_database';";
				//echo "<br><font color=red>$query</font>";
				$res = execute_query_db($query);
				while($row = mysql_fetch_assoc($res)) {
					return $row['nomVille'];
				}
			} else {
				return "Montreal";
			}
	}

   	//===========================================================================
	//== QUELQUES FONCTIONS GLOBALES
	//===========================================================================

	//<!----------------------------------------------------//
	//Fonction de transformation accents HTML
	//----------------------------------------------------->
	Function accents_entities($string){
		 // On commence par transformer les caractères utf8 en iso « ordinaire »
		 // On définit la liste des caractères à remplacer :
		 $caracteres=array('â','é','è','à','ë','ê','û','ü','ù','î','ï', 'ô', 'ö');
		 // On définit les entités qui les remplaceront :
		 $entities=array('&acirc;','&eacute;', '&egrave;', '&agrave;', '&euml;', '&ecirc;', '&ucirc;', '&uuml;', '&ugrave;', '&icirc;', '&iuml;', '&ocirc;', '&ouml;');
		 // On applique le remplacement :
		 $string = str_replace($caracteres,$entities,$string);
		 // On retourne la nouvelle chaine :
		 Return $string ;
	}
	//<!----------------------------------------------------//
	//Fonction de suppression des accents
	//----------------------------------------------------->
	Function sans_accents($string){
		 // On commence par transformer les caractères utf8 en iso « ordinaire »
		 // On définit la liste des caractères à remplacer :
		 $caracteres=array('&acirc;','&eacute;', '&egrave;', '&agrave;', '&euml;', '&ecirc;', '&ucirc;', '&uuml;', '&ugrave;', '&icirc;', '&iuml;', '&ocirc;', '&ouml;');
		 // On définit les entités qui les remplaceront :
		 $entities=array('a','e','e','a','e','e','u','u','u','i','i', 'o', 'o');
		 // On applique le remplacement :
		 $string = str_replace($caracteres,$entities,$string);
		 // On retourne la nouvelle chaine :
		 Return $string ;
	}

	//<!----------------------------------------------------------------------------------//
	// Fonction remplacent les caractères français -> anglais (neutre sans accents)
	//----------------------------------------------------------------------------------->
    function replaceFrench($s) {
        $cfrench=array('à','À','â','Â','ä','Ä','á','Á','é','É','è','È','ê','Ê','ë','Ë','ì','Ì','î','Î','ï','Ï','ò','Ò','ô','Ô','ö','Ö','ù','Ù','û','Û','ü','Ü','ç','Ç','','ñ');
        $cequivalent= array('a','A','a','A','a','A','a','A','e','E','e','E','e','E','e','E','i','I','i','I','i','I','o','O','o','O','o','O','u','U','u','U','u','U','c','C','_','n');
        $new_s=str_replace($cfrench, $cequivalent, $s);
        return $new_s;
    }

	//--FROM PHP site
	function ucfirstUTF8($stri) {
             //$cfrench=array('à','â','ä','á','é','è','ê','ë','ì','Ì','î','Î','ï','Ï','ò','Ò','ô','Ô','ö','Ö','ù','Ù','û','Û','ü','Ü','ç','Ç','','ñ');
        //$cequivalent= array('À','Â','Ä','Á','É','È','Ê','Ë','e','E','e','E','e','E','i','I','i','I','i','I','o','O','o','O','o','O','u','U','u','U','u','U','c','C','_','n');
		//if($stri{0}>="\xc3")
		//	return (($stri{1}>="\xa0")?
		//	($stri{0}.chr(ord($stri{1})-32)):
		//	($stri{0}.$stri{1})).substr($stri,2);
		//else return ucfirst($stri);
		$stri[0] = mb_strtoupper($stri[0]);
		return $stri;
    }


	//<!----------------------------------------------------------------------------------//
	// Fonction permettant de remplacer les caractères pour l'affichage
	//----------------------------------------------------------------------------------->
	Function bd_UTF8_decode($s) {
		$tmp_s = stripslashes(htmlentities($s,ENT_QUOTES,'UTF-8',true));
		if(!empty($tmp_s))
		{
			$s = $tmp_s;
		}
		else
		{
			$s =utf8_encode(html_entity_decode($s));
		}
		return $s;
	}

	Function bd_UTF8_encode($s) {
		$tmp_s = addslashes(htmlentities($s,ENT_QUOTES,'UTF-8',true));
		if(!empty($tmp_s))
		{
			$s = $tmp_s;
		}
		else
		{
			$s=utf8_decode(utf8_encode(html_entity_decode($s)));
		}
		return $s;
	}

	//<!----------------------------------------------------------------------------------//
	// Fonction permettant d'afficher la bonne date en fonction de la langue
	//----------------------------------------------------------------------------------->
	Function bd_Date($s) {
		if ($_SESSION['langue'] == "en") {
			$s= ucfirst(strftime("%A, %b. %e ", strtotime($s)));
		} else {
			$s= ucfirst(strftime("%A, %e %b. ", strtotime($s)));
		}
		return $s;
	}

	Function bd_DateEn($s) {
		setlocale(LC_TIME, 'en_CA.utf8');
		return ucfirst(strftime("%A, %b. %e ", strtotime($s)));
	}

	Function bd_DateUnix($s) {
		//setlocale(LC_TIME, 'en_CA.utf8');
		//date_default_timezone_set('UTC');
		//return gmdate('r',strtotime($s));
		return strtotime($s)*1000; //--for js
	}

	//<!----------------------------------------------------//
	//Fonction de correspondence des images dans la bd
	//----------------------------------------------------->
	Function getCorrespondence($id_produit) {
		$sql = "SELECT id_image,filename FROM Correspondence WHERE id_produit = '".$id_produit."';";
		$res = mysql_query($sql);
		$row = mysql_fetch_assoc($res);
		 //echo variable_to_html($row);
		if (isset($row['id_image'])) {
			if ($row['id_image']==0)  return 'images/NotAvailable.gif';
			if ($row['filename']=="")  return 'images/NotAvailable.gif';
			//saveLog("$img");
		   return "images_db/".$row['filename'];
		} else {
			//if ($_SESSION['langue']=="en") {
			 return 'images/NotAvailable.gif';
			// } else {
			//  return 'images/NotAvailable.gif';
			//}
		}
     }

	 	//<!----------------------------------------------------//
	//Fonction de correspondence des images dans la bd
	//----------------------------------------------------->
	Function getCorrespondenceBig($id_produit) {
		$sql = "SELECT id_image,filename_big FROM Correspondence WHERE id_produit = '".$id_produit."';";
		//saveLog($sql);
		$res = mysql_query($sql);
		$row = mysql_fetch_assoc($res);
		 //echo variable_to_html($row);
		if (isset($row['id_image'])) {
			if ($row['id_image']==0)  return 'images/NotAvailable.gif';
			if ($row['filename_big']=="")  return 'images/NotAvailable.gif';
			//saveLog("$img");
		   return "images_db/".$row['filename_big'];
		} else {
		//	if ($_SESSION['langue']=="en") {
			 return 'images/NotAvailable.gif';
		//	 } else {
		//	  return 'images/NotAvailable.gif';
		//	}
		}
     }

	 function _convert($content) {
    if(!mb_check_encoding($content, 'UTF-8')
        OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {

        $content = mb_convert_encoding($content, 'UTF-8');

        if (mb_check_encoding($content, 'UTF-8')) {
            // log('Converted to UTF-8');
        } else {
            // log('Could not converted to UTF-8');
        }
    }
    return $content;
	}

	//<!----------------------------------------------------//
	// Transforme un Array en une Table HTML
	//----------------------------------------------------->
	Function variable_to_html($variable) {
		if ($variable === true) {
			return 'true';
		} else if ($variable === false) {
			return 'false';
		} else if ($variable === null) {
			return 'null';
		} else if (is_array($variable)) {
			$html = "<table border=\"1\">\n";
			$html .= "<thead><tr><td><b>KEY</b></td><td><b>VALUE</b></td></tr></thead>\n";
			$html .= "<tbody>\n";
			foreach ($variable as $key => $value) {
				$value = variable_to_html($value);
				$html .= "<tr><td>$key</td><td>$value</td></tr>\n";
			}
			$html .= "</tbody>\n";
			$html .= "</table>";
			return $html;
		} else {
			return strval($variable);
		}
	}
	//<!----------------------------------------------------//
	// Debug saving...
	//----------------------------------------------------->
	Function saveLog($variable) {
	 $myFile = "log.txt";
	 $fh = fopen($myFile, 'a') or die("can't open file ".$myFile);
	 fwrite ($fh, date('l jS \of F Y h:i:s A'));
	 fwrite($fh, " $variable\n");
	 fclose($fh);
	}

	//<!----------------------------------------------------//
	// Format string quantity 1.00 ~> 1 1.5 ~> 1.50
	//----------------------------------------------------->
	Function returnQuantity($variable) {
		list($number, $decimal) = sscanf($variable, "%d.%d");
		if ($decimal=="00") {
			return number_format($variable);
		} else {
			return $variable;
		}
	}

	//<!----------------------------------------------------//
	// Format string quantity 1.00 ~> 1 1.5 ~> 1.50
	//----------------------------------------------------->
	Function returnRabais($prix_regulier, $prix_special) {
		if ($prix_regulier==0) return "";
		$rdisc=(1-($prix_special/$prix_regulier))*100;
		list($number, $decimal) = sscanf($rdisc, "%d.%1d");
		if ($decimal>0.5) $number+=1;
		return $number;
	}


   /////////////////////////////////////////////////////////////////
   /// USERS
   /////////////////////////////////////////////////////////////////

     //<!--------------------------------------------------------//
	// Fonction permettant de retourner le privilege d'un utilisateur
	// -1: Unregistered
	//   0: Registered
	//  99: SYSOP
	//--------------------------------------------------------->
    function bd_getUserPrivilege($fid_client) {
	  $sqlq="SELECT privilege FROM User WHERE id_client='$fid_client';";
	  $resq=mysql_query($sqlq);
	  $user_privilege=-1;
	  while($row2 = mysql_fetch_assoc($resq)) {
		$user_privilege = $row2['privilege'];
	  }
	  return $user_privilege;
   }


   function bd_getUserEmailTags($fid_client) {
	  $sqlq="SELECT tags_email FROM User WHERE id_client='$fid_client';";
	  $resq=mysql_query($sqlq);
	  while($row2 = mysql_fetch_assoc($resq)) {
		$data= $row2['tags_email'];
		$stack_panier=json_decode($data, true);
	  }
	  if ($stack_panier==null) $stack_panier=array();
	  return $stack_panier;
   }

   function bd_setUserEmailTags($fid_client,$ftag) {
	  $ftag=mysql_real_escape_string(json_encode($ftag));
	  $sqlq="UPDATE User SET tags_email='$ftag' WHERE id_client='$fid_client';";
	  $resq=mysql_query($sqlq);
   }

   	//<!--------------------------------------------------------//
	//                       PANIER
	//--------------------------------------------------------->

   //<!--------------------------------------------------------//
	// Fonction permettant tester si le contenu du panier
	// fid_panier=1 exists dans un panier sauvegarder actif
	// pour le client fid_client
	// Return le id_panier semblable ou 0
	//
	//--------------------------------------------------------->
   Function bd_PanierCourantExists($fid_client, $fid_database) {
		//--SELECTIONNE LE NOMBRE D'ITEMS DU PANIER actif

		$sqlq = "SELECT COUNT(*) AS nombre_items FROM Contenu_Panier WHERE id_client ='$fid_client' AND id_database = '$fid_database' AND id_panier = '1' AND actif = 1;";
		$res = mysql_query($sqlq);
		$nb_items = mysql_result($res, 0, "nombre_items");

		//--NOTE: Le panier courant est le numero 1, On selectionne les paniers d'aujourd'hui seulement avec le même nombre d'items.
		//$sqlq = "SELECT id_panier FROM Contenu_Panier WHERE id_client ='$fid_client' AND actif=1 AND id_panier != '1'  GROUP BY id_panier HAVING COUNT(id_produit)= $nb_items;";
           $sqlq = "SELECT DISTINCT(id_panier), COUNT(id_produit) FROM Contenu_Panier WHERE id_client ='$fid_client' AND id_panier != '1' AND id_database = '$fid_database' AND actif=1 GROUP BY id_panier HAVING COUNT(id_produit)=$nb_items;";
		   $res = mysql_query($sqlq);
			$nb = mysql_numrows($res);
			$memePanier=false;
			$stack_panier=array();
				for($i=0;$i<$nb;$i++){
							//--PANIER Requete
							$id_panier = mysql_result($res, $i, "id_panier");
							$sql2 = "SELECT id_produit,quantite_produit FROM Contenu_Panier WHERE id_client ='$fid_client' AND id_panier = '".$id_panier."' AND id_database = '$fid_database' AND actif = 1 ORDER BY id_produit ASC";
							$res2 = mysql_query($sql2);
							$nb2 = mysql_numrows($res2);

							//--PANIER courant
							$sql3 = "SELECT id_produit,quantite_produit FROM Contenu_Panier WHERE id_client ='$fid_client' AND id_panier='1' AND id_database = '$fid_database' AND actif=1 ORDER BY id_produit ASC";
							$res3 = mysql_query($sql3);
							$nb3 = mysql_numrows($res3);
							$i2 = 0;
							$memePanier = true;
							if($nb2 != $nb3){
								$memePanier = false;
							}
							else{
								while ($i2 < $nb3){
									$produit1 = mysql_result($res2, $i2, "id_produit");
									$produit2 = mysql_result($res3, $i2, "id_produit");
									$produit1_qte = mysql_result($res2, $i2, "quantite_produit");
									$produit2_qte = mysql_result($res3, $i2, "quantite_produit");
									if($produit1 != $produit2){
										$memePanier = false;
									}
									if ($produit1 == $produit2 && floatval($produit1_qte) != floatval($produit2_qte)) {
										$memePanier = false;
									}
									$i2++;
								}
							}
						$stack_panier[$id_panier]=$memePanier;
				} //--End for
				//saveLog(variable_to_html($stack_panier));

		return $stack_panier;
   }

   //<!--------------------------------------------------------//
	// Fonction permettant de créer le coupon dans le
	//  imprimerCoupon.php
	//  Note: le fid_panier devrait etre le panier courrant (=1)
	//	return the new qrcode
	//--------------------------------------------------------->
    Function bd_generateCoupon($fid_client, $fid_panier, $fid_database) {
		$qrcode="qrcode_".session_id();

		//--SAVE A NEW Panier
		//--ONLY ONE COUPON PER SESSION_ID
		//--DELETE PANIER AND CONTENU PANIER FOR THS SESSION_ID

		$sqlq = "DELETE FROM Contenu_Panier WHERE id_panier  ='".$qrcode."';";
		mysql_query($sqlq);

		//--1. Calcul des variables
		$remoteAddr="";
		if (isset($_SERVER['REMOTE_ADDR'])) $remoteAddr=$_SERVER['REMOTE_ADDR'];

		//--2.0 Selection et Insertion dans contenu panier
		$sql = "SELECT id_produit, quantite_produit, id_enseigne, id_categorie, prix_regulier, prix_special, id_image FROM Contenu_Panier WHERE id_client ='$fid_client' AND id_panier='$fid_panier' AND actif =1 AND id_database='$fid_database';";
		$res = mysql_query($sql);
		$nb = mysql_numrows($res);
		$i = 0;
		while ($i < $nb){ // parcours des resultats de la requete
			$id_produit = mysql_result($res, $i, "id_produit");
			$quantite_produit = mysql_result($res, $i, "quantite_produit");
			$id_enseigne = mysql_result($res, $i, "id_enseigne");
			$id_categorie = mysql_result($res, $i, "id_categorie");
			$prix_special = mysql_result($res, $i, "prix_special");
			$prix_regulier = mysql_result($res, $i, "prix_regulier");
			$id_image = mysql_result($res, $i, "id_image");
			$i++;
			$sql = "INSERT INTO Contenu_Panier(id_panier,id_client,id_produit,quantite_produit,id_enseigne,id_categorie,prix_regulier, prix_special, id_image, date_sauvegarde, date_changed, actif, remote_addr) VALUES ('$qrcode','0','$id_produit',$quantite_produit,$id_enseigne,$id_categorie,$prix_regulier,$prix_special,'$id_image',Now(),Now(),1,'$remoteAddr');";
			mysql_query($sql);
		}
		return $qrcode;
   }

   //<!--------------------------------------------------------//
	// Fonction permettant de faire une fusion du panier courant
	// et du panier utilisateur...
	//--------------------------------------------------------->
    Function bd_Fusion($fid_client, $fid_database) {
		//--PANIER NON IDENTIFIE
		// id_panier = session_id()
		// id_client = 1
		//--PANIER UTILISATEUR IDENTIFIE
		// id_panier =1
		// id_client = id_client bd
		$id_panier_non_identifie=session_id();
		$id_client_identifie=$fid_client;

		//--1. Calcul des variables
		$remoteAddr="";
		if (isset($_SERVER['REMOTE_ADDR'])) $remoteAddr=$_SERVER['REMOTE_ADDR'];

		//--2.0 Selection et Insertion dans contenu panier
		$sqlq = "SELECT id_produit, quantite_produit FROM Contenu_Panier WHERE id_client ='1' AND id_panier='$id_panier_non_identifie' AND id_database = '$fid_database' AND actif =1;";
		$res = mysql_query($sqlq);
		$nb = mysql_numrows($res);

		while ($row = mysql_fetch_assoc($res)){
			$id_produit = $row ["id_produit"];
			$quantite_produit = $row["quantite_produit"];

			//--2.1 SELECTION DE LA QUANTITE DEJA PRESENTE DANS LE PANIER SAUVEGARDE
			$sql1 = "SELECT quantite_produit FROM Contenu_Panier WHERE id_panier = '1' AND id_client = '$id_client_identifie' AND id_produit = '$id_produit' AND id_database = '$fid_database' AND actif = 1;";
	    	saveLog($sql1);
			$res1 = mysql_query($sql1);
   		    while ($row2 = mysql_fetch_assoc($res1)) {
				$quantite_panier_courant = $row2["quantite_produit"];
				if ($quantite_panier_courant > 0){
					 $quantite_produit = $quantite_produit+$quantite_panier_courant;
				}
			}
			//--2.2 INSERTION DU PRODUIT
			bd_InsertProduct($quantite_produit, $id_produit,1, $id_client_identifie);
		}
   }

    //<!--------------------------------------------------------//
	// Fonction permettant tester si le panier courant
	// pour le $fid_client Exists et est pareil..
	//
	//--------------------------------------------------------->
   Function bd_PanierCourantExistsBoolean($fid_client, $fid_database) {
		$stack_id_panier_semblable=bd_PanierCourantExists($fid_client, $fid_database);
		$id_panier_semblable=0;
		foreach ($stack_id_panier_semblable as $id_panier => $memePanier) {
			if ($memePanier==true) $id_panier_semblable=$id_panier;
		}
		if ($id_panier_semblable==0) {
			return false;
		} else {
			return true;
		}
   }
    //<!--------------------------------------------------------//
	// Fonction permettant de connaitre le nombre d'items dans
	// un panier.
	//
	// Note: si le client est identifié, le id_panier=1 et le id_client=# du client
	//       sinon, le id_panier=session_id et le id_client=1;
	//--------------------------------------------------------->

   Function bd_getNbItemPanier($fid_client, $fid_panier) {
				$fsql = "SELECT COUNT(*) AS nombre_items
						 FROM Contenu_Panier
						 WHERE id_client='$fid_client' AND id_panier='$fid_panier' AND actif=1;";
				$fres = mysql_query($fsql);
				$fnb_items = mysql_result($fres, 0, "nombre_items");
				return $fnb_items;
   }

   //<!--------------------------------------------------------//
	// Fonction permettant de retourner la date de sauvegarde du panier
	//--------------------------------------------------------->

   function bd_getDatePanier($fid_client, $fid_panier) {
	  $sqlq="SELECT date_sauvegarde FROM Contenu_Panier WHERE id_client='$fid_client' AND id_panier='$fid_panier' LIMIT 1;";
	  $resq=mysql_query($sqlq);
	  $date_sauvegarde="";
	  while($row2 = mysql_fetch_assoc($resq)) {
		$date_sauvegarde = $row2['date_sauvegarde'];
	  }
	  return $date_sauvegarde;
   }

   	//<!--------------------------------------------------------//
	// Fonction permettant de changer la quantite dans un panier
	//--------------------------------------------------------->
   function bd_InsertProduct($fquantite_produit, $fid_produit,$fid_panier, $fid_client) {
       // Si on veut changer la quantite d'un produit ou l'insérer dans la bd
		$sqlq = "SELECT Produit.prix_regulier,Produit.prix_special,Produit.id_categorie, Produit.id_enseigne, Produit.id_database
				FROM Produit
				WHERE Produit.id_produit='$fid_produit'
				LIMIT 0, 1 ";
		//saveLog($fquantite_produit.", ".$fid_produit.", ".$fid_panier.", ". $fid_client);
		//saveLog($sqlq);
		$res = mysql_query($sqlq);
		while($row = mysql_fetch_assoc($res)){
			$prix_regulier = $row['prix_regulier'];
			$prix_special  = $row['prix_special'];
			$id_categorie  = $row["id_categorie"];
			$fid_enseigne  = $row["id_enseigne"];
			$fid_database  = $row["id_database"];
		}
		$id_image = 0;
		//--THIS MIGHT FAILED
		$sqlq = "DELETE FROM Contenu_Panier WHERE id_panier = '$fid_panier' AND id_client = '$fid_client' AND id_produit ='$fid_produit' AND id_enseigne = '$fid_enseigne';";
		//saveLog("1.".$sqlq);
		mysql_query($sqlq);
		//--THIS WILL SUCCEED AFTER
		$sqlq = "INSERT INTO Contenu_Panier(id_panier,id_client,id_database,id_produit,quantite_produit,id_enseigne,id_categorie,prix_regulier, prix_special, id_image, date_sauvegarde, date_changed, actif, remote_addr) VALUES ('$fid_panier','$fid_client','$fid_database','$fid_produit',$fquantite_produit,$fid_enseigne,$id_categorie,$prix_regulier,$prix_special,'$id_image',Now(),Now(),1,'$remoteAddr');";
		//saveLog("2.".$sqlq);
		return mysql_query($sqlq);
	}

	//<!--------------------------------------------------------//
	// Fonction permettant d'enlever un item du panier
	//--------------------------------------------------------->
	function bd_DeleteProduct($fid_produit,$fid_panier, $fid_client) {
		//$sqlq="DELETE FROM Contenu_Panier WHERE id_produit='$fid_produit' AND id_panier='$fid_panier' AND id_client = '$fid_client';";
		$sqlq="UPDATE Contenu_Panier SET actif = 0 WHERE id_produit='$fid_produit' AND id_panier='$fid_panier' AND id_client = '$fid_client';";
		return  mysql_query($sqlq);
	}

	//<!--------------------------------------------------------//
	// Fonction permettant d'enlever TOUS les item du panier
	//--------------------------------------------------------->

	function bd_DeleteAllProducts($fid_panier, $fid_client) {
		//$sqlq="DELETE FROM Contenu_Panier WHERE id_panier='$fid_panier' AND id_client = '$fid_client';";
		$sqlq="UPDATE Contenu_Panier SET actif = 0 WHERE id_panier='$fid_panier' AND id_client = '$fid_client';";
		return  mysql_query($sqlq);
	}

	//<!--------------------------------------------------------//
	// Fonction permettant de sauvegarder un panier (fid_panier)
	// vers un nouveau panier (new_fid_panier)
	// -> Une copie parfaite
	//--------------------------------------------------------->
	function bd_SavePanier($fid_panier, $fid_client, $new_fid_panier) {
		$remoteAddr="";
		if (isset($_SERVER['REMOTE_ADDR'])) $remoteAddr=$_SERVER['REMOTE_ADDR'];
		$sql = "SELECT id_produit, id_database, quantite_produit, id_enseigne, id_categorie, prix_regulier, prix_special, id_image FROM Contenu_Panier WHERE id_client ='$fid_client' AND id_panier='$fid_panier' AND actif = 1;";
		$res = mysql_query($sql);
		$nb = mysql_numrows($res);
		$i = 0;
		while ($i < $nb){ // parcours des resultats de la requete
			$id_produit = mysql_result($res, $i, "id_produit");
			$id_database = mysql_result($res, $i, "id_database");
			$quantite_produit = mysql_result($res, $i, "quantite_produit");
			$id_enseigne = mysql_result($res, $i, "id_enseigne");
			$id_categorie = mysql_result($res, $i, "id_categorie");
			$prix_special = mysql_result($res, $i, "prix_special");
			$prix_regulier = mysql_result($res, $i, "prix_regulier");
			$id_image = mysql_result($res, $i, "id_image");
			$i++;
			$sql = "INSERT INTO Contenu_Panier(id_panier,id_database,id_client,id_produit,quantite_produit,id_enseigne,id_categorie,prix_regulier, prix_special, id_image, date_sauvegarde, date_changed, actif, remote_addr) VALUES ('$new_fid_panier','$id_database','$fid_client','$id_produit',$quantite_produit,$id_enseigne,$id_categorie,$prix_regulier,$prix_special,'$id_image',Now(),Now(),1,'$remoteAddr');";
			//saveLog($sql);
			mysql_query($sql);
			}
	}

	//<!------------------------------------------------------------//
	// Fonction permettant de connaitre le nombre de produits
	// dans une categories (si fid_categorie==0: total des produits)
	//------------------------------------------------------------->
	 function bd_getNbProduit($fid_categorie,$fid_database) {
		  if ($fid_categorie==0||!isset($fid_categorie)|$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE Produit.status=1 AND Produit.id_database='$fid_database';";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND Produit.id_database='$fid_database' AND Produit.status=1;";
		  }
		  //--Use the json
		  $fichier_json="index_special.json";
		  if(file_exists($fichier_json)){
				$array=json_decode(file_get_contents($fichier_json),true);
				foreach ($array as $data) {
					if ($data['id_database']==$fid_database&&$data['id_categorie']==$fid_categorie&&$data['id_enseigne']==0) {
						return $data['total_regulier'];
					}
				}
		   }


		  $resq=mysql_query($sql_total_produit);
		  if (!$resq) saveLog($sql_total_produit."\n".mysql_error());
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }

   	//<!------------------------------------------------------------//
	// Fonction permettant de connaitre le nombre de produits
	// dans une categories (si fid_categorie==0: total des produits)
	//------------------------------------------------------------->
	 function bd_getNbProduitWithStatus($fid_categorie,$fid_database,$view_status) {
		  if ($fid_categorie==0||!isset($fid_categorie)|$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE Produit.status=$view_status AND Produit.id_database='$fid_database';";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND Produit.id_database='$fid_database' AND Produit.status=$view_status;";
		  }
		  $resq=mysql_query($sql_total_produit);
		  if (!$resq) saveLog($sql_total_produit."\n".mysql_error());
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }

   	//<!------------------------------------------------------------//
	// Fonction permettant de connaitre le nombre de produits
	// dans une categories avec une enseigne particulière
	//------------------------------------------------------------->

   	 function bd_getNbProduitWithEnseigne($fid_categorie, $fid_enseigne,$fid_database) {
		  if ($fid_categorie==0||!isset($fid_categorie)||$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_enseigne=$fid_enseigne AND Produit.id_database='$fid_database' AND Produit.status=1;";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND id_enseigne=$fid_enseigne AND Produit.id_database='$fid_database' AND Produit.status=1;";
		  }
		  //--Use the json
		  $fichier_json="index_special.json";
		  if(file_exists($fichier_json)){
				$array=json_decode(file_get_contents($fichier_json),true);
				foreach ($array as $data) {
					if ($data['id_database']==$fid_database&&$data['id_categorie']==$fid_categorie&&$data['id_enseigne']==$fid_enseigne) {
						return $data['total_special'];
					}
				}
		   }
		  //--Use query otherwise
		  $resq=mysql_query($sql_total_produit);
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }

   	//<!------------------------------------------------------------//
	// Fonction permettant de connaitre le nombre de produits
	// dans une categories (si fid_categorie==0: total des produits)
	//------------------------------------------------------------->
	 function bd_getNbProduit_withIDProduit($fid_categorie, $fid_produit,$fid_database) {
		  if ($fid_categorie==0||!isset($fid_categorie)|$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE Produit.id_produit LIKE '$fid_produit%' AND Produit.id_database='$fid_database';";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND Produit.id_produit LIKE '$fid_produit%' AND Produit.id_database='$fid_database' ;";
		  }
		  $resq=mysql_query($sql_total_produit);
		  if (!$resq) saveLog($sql_total_produit."\n".mysql_error());
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }

    function bd_getNbProduitSpecial_withIDProduit($fid_categorie, $fid_produit,$fid_database) {
		  if ($fid_categorie==0||!isset($fid_categorie)||$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE prix_regulier!=prix_special AND Produit.id_produit LIKE '$fid_produit%' AND Produit.id_database='$fid_database' ;";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND Produit.prix_regulier!=Produit.prix_special AND Produit.id_produit LIKE '$fid_produit%' AND Produit.id_database='$fid_database' ;";
		  }
		  $resq=mysql_query($sql_total_produit);
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }


   //<!----------------------------------------------------------------------------------//
	// Fonction permettant de connaitre le nombre de produits
	// en special dans une categories (si fid_categorie=0: total des produit en solde)
	//----------------------------------------------------------------------------------->
	 function bd_getNbProduitSpecial($fid_categorie,$fid_database) {
		  if ($fid_categorie==0||!isset($fid_categorie)||$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE prix_regulier!=prix_special AND Produit.status=1 AND Produit.id_database='$fid_database';";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND Produit.prix_regulier!=Produit.prix_special AND Produit.status=1 AND Produit.id_database='$fid_database' ;";
		  }
		  //--Use the json
		  $fichier_json="index_special.json";
		  if(file_exists($fichier_json)){
				$array=json_decode(file_get_contents($fichier_json),true);
				foreach ($array as $data) {
					if ($data['id_database']==$fid_database&&$data['id_categorie']==$fid_categorie&&$data['id_enseigne']==0) {
						return $data['total_special'];
					}
				}
		   }
		  //--Use query otherwise
		  $resq=mysql_query($sql_total_produit);
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }

    function bd_getNbProduitSpecialWithStatus($fid_categorie,$fid_database,$view_status) {
		if ($fid_categorie==0||!isset($fid_categorie)||$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE prix_regulier!=prix_special AND Produit.status=$view_status AND Produit.id_database='$fid_database';";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND Produit.prix_regulier!=Produit.prix_special AND Produit.status=$view_status AND Produit.id_database='$fid_database' ;";
		  }
		  $resq=mysql_query($sql_total_produit);
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }

   	 function bd_getNbProduitSpecialWithEnseigne($fid_categorie, $fid_enseigne,$fid_database) {
		  if ($fid_categorie==0||!isset($fid_categorie)||$fid_categorie==99) {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE prix_regulier!=prix_special AND id_enseigne=$fid_enseigne AND Produit.id_database='$fid_database' AND Produit.status=1;";
		  } else {
				$sql_total_produit = "SELECT COUNT(*) AS count_total FROM Produit WHERE id_categorie = $fid_categorie AND Produit.prix_regulier!=Produit.prix_special AND id_enseigne=$fid_enseigne AND Produit.id_database='$fid_database' AND Produit.status=1;";
		  }
		  //--Use the json
		  $fichier_json="index_special.json";
		  if(file_exists($fichier_json)){
				$array=json_decode(file_get_contents($fichier_json),true);
				foreach ($array as $data) {
					if ($data['id_database']==$fid_database&&$data['id_categorie']==$fid_categorie&&$data['id_enseigne']==$fid_enseigne) {
						return $data['total_special'];
					}
				}
		   }
		  //--Use query otherwise


		  $resq=mysql_query($sql_total_produit);
		  $total=0;
		  while($row2 = mysql_fetch_assoc($resq)) {
			$total = $row2['count_total'];
		  }
	  return $total;
   }

   //<!----------------------------------------------------------------------------------//
	// Fonction permettant de connaitre les enseignes associées à une recherche
	// de produit
	// params: $fid_categories
	// params: $fsearch_string (created with bd_createSearchString)
	//----------------------------------------------------------------------------------->
   function bd_getEnseigneQuery($fid_categorie,$fid_database) {
                 $stack=array();
				 if ($fid_categorie==0||$fid_categorie==99) {
					//--Pas de categories, on simplifie le query
					//--Nouveau May 2012 (Etienne Lord)
					//$sqlq = "SELECT Enseigne.id_enseigne, Enseigne.nom_enseigne FROM Enseigne GROUP BY Enseigne.nom_enseigne ORDER BY Enseigne.nom_enseigne ASC";
					$sqlq = "SELECT Enseigne.id_enseigne, Enseigne.nom_enseigne FROM Enseigne WHERE id_enseigne IN (SELECT DISTINCT (id_enseigne) FROM Produit WHERE Produit.id_database='$fid_database' AND  Produit.status=1 );";
					//--Look for file instead...
						$fichier_json="id_enseigne_$fid_database.json";
						 if(file_exists($fichier_json)){
							 return json_decode(file_get_contents($fichier_json),true);
						 }
						//Normal query otherwise...
				} else {
					$sqlq ="SELECT Enseigne.id_enseigne, Enseigne.nom_enseigne, Enseigne.fichier_logo,Enseigne.prix_coefficient  FROM Enseigne
							LEFT JOIN Produit
							ON Produit.id_enseigne=Enseigne.id_enseigne
							WHERE Produit.id_categorie = '".$fid_categorie."'
							AND Produit.status=1 AND Produit.id_database='$fid_database'
							GROUP BY Enseigne.nom_enseigne
							ORDER BY Enseigne.nom_enseigne ASC";
						//--Look for file instead...
						$fichier_json="id_enseigne_$fid_database"."_".$fid_categorie.".json";
						 if(file_exists($fichier_json)){
							  return json_decode(file_get_contents($fichier_json),true);
						 }
				 }
					$res2 = mysql_query($sqlq);
					while ($row = mysql_fetch_array($res2))
					{
						$a=array('id_enseigne'=>$row['id_enseigne'],'nom_enseigne'=>$row['nom_enseigne'], 'fichier_logo'=>$row['fichier_logo'], 'prix_coefficient'=>$row['prix_coefficient']);
						array_push($stack, $a);
					}
	return $stack;
   }

   //<!----------------------------------------------------------------------------------//
	// Fonction permettant de connaitre les enseignes associées à une recherche
	// de produit
	// params: $fid_categories
	// params: $fsearch_string (created with bd_createSearchString)
	//----------------------------------------------------------------------------------->
   function bd_getEnseigneQueryWithSearch($fid_categorie,$fsearch_string) {
                 $stack=array();
				 //$fsearch=bd_createSearchString($fsearch_string);
				 //========================
				 // MAI 2012 - Etienne Lord
				 //========================

					$fsearch="";
					$product_id_search="";
					$sid_file = "/tmp/".session_id();
					$sid_file_search = "/tmp/".session_id().".search";
					//--SEARCH AND SEND OUTPUT TO $sid file
					//system("java -jar /home/smartshopping/downloadImages2/downloadImages2.jar SEARCH \"$fsearch_string\" >> $sid_file",$retour);
          // Update by Nadia Tahiri 15 April 2018
          // system("java -jar /home/smart_dev/downloadImages2/downloadImages2.jar SEARCH_OLD $sid_file_search >> $sid_file",$retour);
          system("java -jar /var/www/html/admin/downloadImages2/downloadImages2/downloadImages2.jar SEARCH_OLD $sid_file_search >> $sid_file",$retour);

					//--Read the file
					 $fh = fopen($sid_file, 'r');
					 if ($fh) {
						 while (($buffer = fgets($fh, 4096)) !== false) {
							$product_id_search.=$buffer;
						}
						if ($product_id_search=="") $product_id_search="0";
						$fsearch="Produit.id_produit IN ($product_id_search) ";
						saveLog($product_search_id);
						fclose($fh);
						//--Clean up
						system("rm -rf $sid_file");
						//system("rm -rf $sid_file_search");
					 } else {
					   saveLog("Unable to open ".$sid_file);
					 }

				 //--saveLog($fsearch_string."\n".$fsearch."\n");

				 if ($fid_categorie==0||$fid_categorie==99) {
					$sqlq = "SELECT Enseigne.id_enseigne, Enseigne.nom_enseigne, Enseigne.fichier_logo,Enseigne.prix_coefficient FROM Enseigne WHERE Enseigne.id_enseigne IN
					(SELECT DISTINCT (Enseigne.id_enseigne) FROM Produit
					JOIN Enseigne ON Enseigne.id_enseigne=Produit.id_enseigne
					WHERE $fsearch)" ;
				} else {
					$sqlq = "SELECT Enseigne.id_enseigne, Enseigne.nom_enseigne,Enseigne.fichier_logo,Enseigne.prix_coefficient FROM Enseigne WHERE Enseigne.id_enseigne IN
							(SELECT DISTINCT (Enseigne.id_enseigne) FROM Produit
							JOIN Enseigne ON Enseigne.id_enseigne=Produit.id_enseigne
							WHERE Produit.id_categorie = '".$fid_categorie."' AND
							Produit.status=1 AND
							$fsearch)" ;
				 }
					//saveLog($sqlq);
					$res2 = mysql_query($sqlq);
					if ($res2==null) saveLog("\n".mysql_error()."\n".$sqlq);
					while ($row = mysql_fetch_array($res2))
					{
						$array=array('id_enseigne'=>$row['id_enseigne'],'nom_enseigne'=>$row['nom_enseigne'], 'fichier_logo'=>$row['fichier_logo'], 'prix_coefficient'=>$row['prix_coefficient']);
						array_push($stack, $array);
						//$v=$magasins['id_enseigne'];
						//$stack[$v]=$magasins['nom_enseigne'];
					}
	return $stack;
   }
    //<!----------------------------------------------------------------------------------//
	// Fonction permettant de connaitre les enseignes associées à une recherche
	// de produit
	// params: $fid_panier,fid_client
	// NOTE: La recherche original doit avoir été faite avant et inserer dans la table
	//       Tmp_Search
	//----------------------------------------------------------------------------------->
    function bd_getEnseigneQueryWithSearch_2($fid_panier,$fid_client, $fid_database) {
                 $stack=array();
				 $sqlq="SELECT DISTINCT(Enseigne.id_enseigne), Enseigne.nom_enseigne,Enseigne.fichier_logo, Enseigne.prix_coefficient FROM Enseigne, Produit, Tmp_Search WHERE id_panier = '$fid_panier' AND id_client = '$fid_client' AND Produit.id_produit=Tmp_Search.id_produit AND Produit.id_database = '$fid_database' AND Produit.id_enseigne = Enseigne.id_enseigne;";
				 $res2 = mysql_query($sqlq);
					if ($res2==null) saveLog("\n".mysql_error()."\n".$sqlq);
					while ($row = mysql_fetch_array($res2))
					{
						$array=array('id_enseigne'=>$row['id_enseigne'],'nom_enseigne'=>$row['nom_enseigne'], 'fichier_logo'=>$row['fichier_logo'], 'prix_coefficient'=>$row['prix_coefficient']);
						array_push($stack, $array);
						// $v=$magasins['id_enseigne'];
						// $stack[$v]=$magasins['nom_enseigne'];
					}

	 return $stack;
   }
  //<!----------------------------------------------------------------------------------//
	// Fonction permettant de chercher des images similaires
	// de produit
	// params: $f_search (search string)
	// params: $fsearch_string (une clause Where de correspondence)
	//----------------------------------------------------------------------------------->
   function bd_searchImage($f_search) {
		$fsearch="";
		$image_id_search="";
    // Update by Nadia Tahiri 17 April 2018
		// $sid_file = "/home/smart_dev/public_html//tmp/".session_id();
		// $sid_file_search = "/home/smart_dev/public_html//tmp/".session_id().".search";
    $sid_file = "/home/smart_dev/public_html//tmp/".session_id();
    $sid_file_search = "/home/smart_dev/public_html//tmp/".session_id().".search";

		//--SEARCH AND SEND OUTPUT TO $sid file
			$fh = fopen($sid_file_search, 'w') or die("can't open file ".$myFile);
			fwrite($fh, _convert($f_search));
			fclose($fh);
		//system("java -jar /home/smartshopping/downloadImages2/downloadImages2.jar SEARCH \"$fsearch_string\" >> $sid_file",$retour);
    // Update by Nadia Tahiri 17 April 2018
		// system("java -jar /home/smart_dev/downloadImages2/downloadImages2.jar SEARCH_IMAGE $sid_file_search >> $sid_file",$retour);
    system("java -jar /var/www/html/admin/downloadImages2/downloadImages2.jar SEARCH_IMAGE $sid_file_search >> $sid_file",$retour);
		//--Read the file
		 $fh = fopen($sid_file, 'r');
		 if ($fh) {
			 while (($buffer = fgets($fh, 4096)) !== false) {
				$image_id_search.=$buffer;
			}
			if ($image_id_search=="") $image_id_search="0";
			$fsearch="Correspondence.ImageID IN ($image_id_search) ";
			system("rm -rf $sid_file");
			system("rm -rf $sid_file_search");
		}
	return $fsearch;
   }

   //<!----------------------------------------------------------------------------------//
	// Fonction permettant construire la chaines de recherche de produit
	// NOTE: Cette fonction est la fonction principale de la recherche
	//----------------------------------------------------------------------------------->
	// NOTE: Deprecated! - Now use a Java program
   function bd_createSearchString($f_search) {
	  // Si on a un produit recherché
	  $b_pattern="/([0-9][0-9]*)/"; //--Extract some number from quantity

	  if($f_search!=""){

				$f_search=mysql_real_escape_string($f_search); //--Securite
				$f_search=trim(preg_replace('/\s*\([^)]*\)/', '', $f_search)); //--Enleve le ( )
				$nom_pr_recherche = explode(" ", $f_search);
				$recherche = "";

				//--CAS 1. (Note: on prend le AND car plus restrictif -- Non, plus avec la quantite, Etienne Lord Mai 2012
				$recherche=$recherche." (Produit.nom_produit_fr LIKE _utf8'$f_search%' OR Produit_en.nom_produit_en LIKE _utf8'$f_search%') OR ";

				//--CAS 2. (On recherche les nom individuellement...
				for($i=0;$i<count($nom_pr_recherche);$i++){
					if($nom_pr_recherche[$i] !=""&&!preg_match($b_pattern, $nom_pr_recherche[$i], $matches)){
						if($i == 0){
						$recherche=$recherche." ((Produit.nom_produit_fr LIKE _utf8'%$nom_pr_recherche[$i]%' OR Produit_en.nom_produit_en LIKE _utf8'%$nom_pr_recherche[$i]%')";
						}
						else{
						$recherche=$recherche." AND (Produit.nom_produit_fr LIKE _utf8'%$nom_pr_recherche[$i]%' OR Produit_en.nom_produit_en LIKE _utf8'%$nom_pr_recherche[$i]%')";
						}
					}
				}
				$recherche=$recherche." ) ";
				for($i=0;$i<count($nom_pr_recherche);$i++){
					if($nom_pr_recherche[$i] !="" && strlen($nom_pr_recherche[$i]) > 1){
						if($i == 0){
							$recherche=$recherche." OR ( Produit.brand_fr LIKE _utf8'%$nom_pr_recherche[$i]%'";
						}
						else{
							$recherche=$recherche." OR Produit.brand_fr LIKE _utf8'%$nom_pr_recherche[$i]%'";
						}
					}
				}
				$recherche=$recherche." ) ";

				//--TEST Ajout pour la quantité (on regarde les chiffre)

				//--On a un chiffre dans la recherche?
				$flag_added=false;
				$icount=0;
				for($i=0;$i<count($nom_pr_recherche);$i++){
					if($nom_pr_recherche[$i] !=""){
						if (preg_match($b_pattern, $nom_pr_recherche[$i], $matches)) {
							$flag_added=true;
							if($icount == 0){
								$recherche=$recherche." AND ( Produit.quantite_fr LIKE _utf8'%$nom_pr_recherche[$i]%'";
							}
							else{
								$recherche=$recherche." OR Produit.quantite_fr LIKE _utf8'%$nom_pr_recherche[$i]%'";
							}
							$icount++;
						}
					}
				}
				if ($flag_added==true) $recherche=$recherche." ) ";

				//saveLog($recherche);
				return "($recherche)";

	  }
	  return ""; //--Error or no search string...
   }

     //<!----------------------------------------------------------------------------------//
	// Fonction permettant de connaitres le nombre de résultats par categories
	// param:  $f_search (voir fonction ci-haut)
	//         $fid_categorie (a valid categorie)
	//----------------------------------------------------------------------------------->
   function bd_ResultatParCategorie($f_search, $fid_categorie) {

		$sqlq = "SELECT COUNT(*) AS TOTAL FROM Produit
				JOIN Enseigne ON Enseigne.id_enseigne=Produit.id_enseigne
				WHERE $f_search AND Produit.id_categorie=$fid_categorie AND Produit.status =1 GROUP BY Produit.id_categorie;" ;
		$res2 = mysql_query($sqlq);
		if (!res2) {
			return 0;
		} else {
		  $results = mysql_fetch_array($res2);
		  if (!$results) return 0;
		  return $results['TOTAL'] ;
		}
   }

   function bd_NbResultatsParCategorie($f_search) {
		$stack=array();
		$sqlq="SELECT id_categorie FROM Categorie";
		$res2 = mysql_query($sqlq);
		if (!res2) {
			return 0;
		} else {
		  while($results = mysql_fetch_array($res2)) {
			$fid_categorie=$results['id_categorie'];
			$stack["$fid_categorie"]=bd_ResultatParCategorie($f_search,$fid_categorie);
		  }
		  return $stack;
		}
   }


   //<!----------------------------------------------------------------------------------//
	// Fonction permettant de detruire les anciens Paniers (>7 jours)
	//----------------------------------------------------------------------------------->
   function bd_delete7DaysOldPanier() {
    //--TO DO - put in a distinct PHP file to launch (protected)
	//--NOTE: Fait maintenant partie du script de chargement...
	//-->
	//$sql= "DELETE FROM Contenu_Panier WHERE id_panier IN (SELECT id_panier FROM Panier WHERE id_client=1 AND DATEDIFF(Now(), date_sauvegarde) >7);";
    //$sql= "DELETE FROM Panier WHERE id_client=1 AND DATEDIFF(Now(), date_sauvegarde)>7;";
    //$sql= "DELETE FROM Contenu_Panier WHERE id_panier IN (SELECT id_panier FROM Panier WHERE id_client=1 AND nombre_items=0);";
    //$sql= "DELETE FROM Panier WHERE id_client=1 AND nombre_items=0;";

	//--SELECT LES id_panier ou la date >7 jours et le id_panier > 10 (donc temporaire)
	  //$sqlq="DELETE FROM Contenu_Panier WHERE id_panier IN (SELECT id_panier FROM Panier WHERE id_client=1 AND DATEDIFF(Now(), date_sauvegarde) >7);";
	  //mysql_query($sqlq);
	  //saveLog(mysql_error()."\n".$sqlq);
	  //$sqlq="DELETE FROM Panier WHERE id_client=1 AND DATEDIFF(Now(), date_sauvegarde)>7";
	  //saveLog(mysql_error()."\n".$sqlq);
	}

	//<!----------------------------------------------------------------------------------//
	// Fonction permettant de savoir si une quantité (nombre) se retrouve dans la
	// description... (BASIC CASE ONLY)
	//----------------------------------------------------------------------------------->
	function bd_QuantityInDescription($b_desc, $b_quantity) {
		$b_pattern="/([0-9][0-9]*)/"; //--Extract some number from quantity
		if (preg_match($b_pattern, $b_quantity, $matches)) {
			$matches="/".$matches[0]."/";
			//-- debug saveLog( $b_quantity.'\n'.$matches);
			if (preg_match($matches,$b_desc)) {
				return true;
			} else {
				return false;
			}
		} else {
		 return false;
		 }
	}

	//<!----------------------------------------------------------------------------------//
	// Calcul d'un panier si on est seulement dans 1 magasin
	// description... (BASIC CASE ONLY)
	//----------------------------------------------------------------------------------->
	  function bd_Prix_Un_Panier($fid_enseigne,$fid_client, $fid_panier,$fid_database) {
        $fprix_total=0;
		$enseigne_default="";
		$sql3="SELECT Prix_Coefficient, nom_enseigne FROM Enseigne WHERE Enseigne.id_enseigne=$fid_enseigne;";
		$res3 = mysql_query($sql3);
		while ($row3 = mysql_fetch_assoc($res3)) {
			$coefficient=$row3['Prix_Coefficient'];
			$enseigne_default=$row3['nom_enseigne'];
		}

	   // Si on veut changer la quantite d'un produit
		$sql0 = "SELECT DISTINCT(id_produit) FROM Contenu_Panier WHERE Contenu_Panier.id_client='".$fid_client ."' AND Contenu_Panier.id_panier='". $fid_panier ."' AND Contenu_Panier.id_database='". $fid_database ."' AND actif=1;";
		saveLog($sql0);
		$res0 = mysql_query($sql0);
		$nb = mysql_numrows($res0);
			//-- Debug echo variable_to_html($_SESSION); echo variable_to_html($_GET);
		if ($nb != 0)
			{
				while($row = mysql_fetch_assoc($res0))
				{
					$sql = "SELECT Contenu_Panier.quantite_produit,
									Contenu_Panier.id_produit,
									Enseigne.id_enseigne,
									Enseigne.nom_enseigne,
									Enseigne.Prix_Coefficient,
									Produit.id_enseigne,
									Produit.prix_special,
									Produit.prix_regulier,
									Produit.id_produit,
									Produit.unite_fr
									FROM Contenu_Panier
									LEFT JOIN Produit ON Contenu_Panier.id_produit = Produit.id_produit
									LEFT JOIN Enseigne ON Contenu_Panier.id_enseigne = Enseigne.id_enseigne
									WHERE Contenu_Panier.id_client='". $fid_client ."'
									AND Contenu_Panier.id_panier='". $fid_panier."'
									AND Produit.id_produit= '".$row["id_produit"]."';";

					$res = mysql_query($sql);
					saveLog(mysql_error()."\n".$sql);
					$nb2 = mysql_numrows($res);  // on recupere le nombre d'enregistrements
					$i = 0;
						 while ($i < $nb2){ // parcours des resultats de la requete

								$fquantite= mysql_result($res, $i, "Contenu_Panier.quantite_produit");
								$fprix_special = mysql_result($res, $i, "Produit.prix_special");
								$fprix_regulier = mysql_result($res, $i, "Produit.prix_regulier");
								$fid_enseigne2 = mysql_result($res, $i, "Produit.id_enseigne");
								$coefficient_estime = mysql_result($res, $i, "Enseigne.Prix_Coefficient");
								$coefficient_estime_nom = mysql_result($res, $i, "Enseigne.nom_enseigne");
								$i=$nb2;
								if ($fid_enseigne2!=$fid_enseigne) {
									//saveLog($fid_enseigne." ".$enseigne_default." ".$coefficient." Coefficient_en_cours:".$coefficient." ".$coefficient_estime_nom." Coefficient_estimation:".$coefficient_estime);
									//saveLog("Prix_regulier du produit: ".$fprix_regulier." * ".$coefficient_estime." = ".((($fprix_regulier*$coefficient)/$coefficient_estime)*$fquantite));
									$fprix_total=$fprix_total + ((($fprix_regulier*$coefficient)/$coefficient_estime)*$fquantite);
								} else {
									//saveLog($enseigne_default." ".($fprix_special*$fquantite));
									$fprix_total=$fprix_total + ($fprix_special*$fquantite);
								}
					}
		}
		//saveLog("Total pour ".$enseigne_default.": ".$fprix_total);
		}
		return $fprix_total;
	}
	/////////////////////////////////////////////////////////////////////////
	/// PRIX PANIER
	/// Etienne Lord - 2013
	//$a=array(1,24,25,6,7,8,10,12);
	//$a=array(1,2,3);
	//echo variable_to_html(getCombinations($a,2));
	//--FROM http://stackoverflow.com/questions/4279722/php-recursion-to-get-all-possibilities-of-strings/8880362#8880362
	function getCombinations($base,$n){

				$baselen = count($base);
				if($baselen == 0){
					return;
				}
					if($n == 1){
						$return = array();
						foreach($base as $b){
							$return[] = array($b);
						}
						return $return;
					}else{
						//get one level lower combinations
						$oneLevelLower = getCombinations($base,$n-1);
						//for every one level lower combinations add one element to them that the last element of a combination is preceeded by the element which follows it in base array if there is none, does not add
						$newCombs = array();

						foreach($oneLevelLower as $oll){
							$lastEl = $oll[$n-2];
							$found = false;
							foreach($base as  $key => $b){
								if($b == $lastEl){
									$found = true;
									continue;
									//last element found
								}
								if($found == true){
										//add to combinations with last element
										if($key < $baselen){
											$tmp = $oll;
											$newCombination = array_slice($tmp,0);
											$newCombination[]=$b;
											$newCombs[] = array_slice($newCombination,0);
										}
								}
							}
						}
					}
					return $newCombs;
				}

	//<!----------------------------------------------------------------------------------//
	// Calcul d'un panier si on est dans plusieurs magasins
	// description... (BASIC CASE ONLY)
	// Donnant: fmax_enseigne un nombre de franchise maximum ou n-1
	//          $data_json = json format smshopping2
	// Return: a json containing the ordered list of Panier price
	// Etienne Lord 2013 - July 2013 - conversion avec l'entre en JSON
	//----------------------------------------------------------------------------------->
	  function bd_Prix_des_Paniers2($fmax_enseigne,$data_json, $id_current_panier) {
		//--debug error_reporting(E_ALL);
        //////////////////////////////////////////////////////////
		/// Variables
		//$fichier_json = "/tmp/".session_id().".panier.json";

		//////////////////////////////////////////////////////////
		//--Array
		//$fid_enseigne,$fid_client, $fid_panier,$fid_database
		$array_produits=array();
		$array_enseigne=array();     //--Avec info et coefficient
		$array_enseigne_coefficient=array(); //--To sort
		$array_id_enseigne=array(); //--Seulement id_enseigne
		$array_prix_combination=array();
		$array_panier_courant=array();
		$datat=json_decode($data_json,TRUE); //--Note: this part is still encoded

		//--Array enseigne
		//print_r($data_json);
		//--get the id_produit from the json
		//echo variable_to_html($datat);

		$prix_special=0.0;
		//--Take only the current panier
		foreach ($datat['panier'] as $produit) {
				//--FROM JSON: Contenu_Panier.id_produit,
					//echo variable_to_html($produit);
					//--CUrrent panier
					//--Note: we only take the produit if the enseigne is valid
					//        and it is the current user panier...
					if (strcmp($produit['c'],$id_current_panier)==0&&$produit['e']!=0) {
								$sql = "SELECT Enseigne.id_enseigne,
											   Enseigne.nom_enseigne,
											   Enseigne.Prix_Coefficient
											   FROM Enseigne
											   WHERE Enseigne.id_enseigne = '".$produit['e']."';";
										//--FROM JSON: Contenu_Panier.quantite_produit,
										//--FROM JSON: Contenu_Panier.prix_special,
										//--FROM JSON: Contenu_Panier.prix_regulier,

								$res = mysql_query($sql);
								$nb2 = mysql_numrows($res);  // on recupere le nombre d'enregistrements
									$i = 0;
									 while ($i < $nb2){ // parcours des resultats de la requete
											//--Array produits
											$fid_produit= $produit['i'];
											$fquantite= intval($produit['quantity']);
											$fprix_special =  floatval($produit['s']);
											$fprix_regulier =  floatval($produit['r']);
											$fid_enseigne2 = $produit['e'];
											$coefficient_estime = floatval(mysql_result($res, $i, "Enseigne.Prix_Coefficient"));
											$coefficient_estime_nom = mysql_result($res, $i, "Enseigne.nom_enseigne");
											$array_produits[$fid_produit]=array('id_produit'=>$fid_produit, 'quantite'=>$fquantite,
																			   'prix_special'=>$fprix_special,'prix_regulier'=>$fprix_regulier,
																			   'id_enseigne'=>$fid_enseigne2,'coefficient'=>$coefficient_estime);
											//--Array Enseigne
											$array_enseigne[$fid_enseigne2]=array('id_enseigne'=>$fid_enseigne2,'nom_enseigne'=>$coefficient_estime_nom,'coefficient'=>$coefficient_estime);
											$prix_special + ($fprix_special*$fquantite);
											$i=$nb2;
										}
			} //--End current panier
		} //--End foreach produit


		//echo variable_to_html($array_produits);
		//echo variable_to_html($array_enseigne);
		//--Array  id_enseigne for combination
		foreach ($array_enseigne as $id_enseigne=>$data) {
			array_push($array_id_enseigne, $id_enseigne);
			array_push($array_enseigne_coefficient, $data['coefficient']);
			//echo "$id_enseigne".$data['nom_enseigne']."<br>";
			}
		//--Sort by coefficient du plus grand au plus petit
		array_multisort($array_enseigne_coefficient, SORT_ASC, $array_enseigne);

		//--1. Get a list of combinations
		for ($i=1;$i<=count($array_id_enseigne);$i++) {
			if ($i<=$fmax_enseigne) {
			//getCombinations($array_id_enseigne,$fmax_enseigne)
			//$array_prix_combination=
				foreach (getCombinations($array_id_enseigne,$i) as $key=>$data)  array_push($array_prix_combination,$data);
			}
		}
		//echo variable_to_html($array_prix_combination);
		//--2. For each combination, calculate a price
		$count=count($array_prix_combination);
		$array_prix_panier=array(); //--To sort
		//echo variable_to_html($array_enseigne);
		for ($i=0; $i<$count;$i++) {
			$ids_enseigne_calcul=$array_prix_combination[$i];
			//--Array of this enseignes
			$tmp_enseigne=array();
			foreach ($array_enseigne as $key=>$data) {
				foreach ($ids_enseigne_calcul as $id) {
					if ($id==$data['id_enseigne']) array_push($tmp_enseigne, $data);
				}
			}
			$fprix_total=0;
			foreach ($array_produits as $id_produit=>$data) {
					//--CAS 1. id_enseigne in this combination => On garde le prix
				//echo "$id_produit =>";
				if (in_array($data['id_enseigne'], $ids_enseigne_calcul)) {
					$fprix_total=$fprix_total + ($data['prix_special']*$data['quantite']);
					//echo ($data['prix_special']*$data['quantite'])."<br>";
				} else {
					//--CAS 2. id_enseigne in this combination, on prend le prix au coefficient le plus bas
					if ($data['id_enseigne']!=$array_enseigne[0]['id_enseigne']) {
						$coefficient=$array_enseigne[0]['coefficient'];
					} else {
						$coefficient=$array_enseigne[1]['coefficient'];
					}
					//echo  "$coefficient =>".((($data['prix_regulier']*$coefficient)/$data['coefficient'])*$data['quantite'])."<br>";
					$fprix_total=$fprix_total + ((($data['prix_regulier']*$coefficient)/$data['coefficient'])*$data['quantite']);

				}
			}
			array_push($array_prix_panier, $fprix_total);
			unset($array_prix_combination[$i]);
			$array_prix_combination[$i]['prix']=$fprix_total;
			//$array_prix_combination[$i]['id_enseigne']=$ids_enseigne_calcul;
			$array_prix_combination[$i]['ids_enseigne']=$tmp_enseigne;
			//echo variable_to_html($data);
		} //--End combination

		//--Sort by smallest price
		array_multisort($array_prix_panier, SORT_ASC, $array_prix_combination);
		//--Set the last element to the default price.
		$array_panier_courant['prix']=$prix_special;
		$array_panier_courant['ids_enseigne']=$array_enseigne;
		array_push($array_prix_combination,$array_panier_courant);
		//array_push($array_prix_combination,$sql0);
		//--Create the n-1 combination array
		// $array_results=array();
		// for ($i=1;$i<count($array_id_enseigne);$i++) {
		// $tmp=array();
		// foreach ($array_prix_combination as $key=>$data) {
			// if (count($data['ids_enseigne'])==$i) {
				// array_push($tmp, $data);
			// }
		// }
		// $array_results[$i]=$tmp;
		// }

		//--Array for n produit
		//$array_results[count($array_id_enseigne)]=$array_panier_courant;

		//echo variable_to_html($array_prix_combination);

			// $fp = fopen("$fichier_json",'w');
			// fwrite($fp,json_encode($array_prix_combination));
			// fclose($fp);
			//--error_reporting(0);
			return json_encode($array_prix_combination);

		}


	//<!----------------------------------------------------------------------------------//
	// Calcul d'un panier si on est dans plusieurs magasins
	// description... (BASIC CASE ONLY)
	// Donnant: fmax_enseigne un nombre de franchise maximum ou n-1
	// $fid_client, $fid_panier,$fid_database
	// Return: a json containing the ordered list of Panier price
	// Etienne Lord 2013
	//----------------------------------------------------------------------------------->
	  function bd_Prix_des_Paniers($fmax_enseigne,$fid_client, $fid_panier,$fid_database) {
        //////////////////////////////////////////////////////////
		/// Variables
		$fichier_json = "/tmp/".session_id().".panier.json";

		//////////////////////////////////////////////////////////
		//--Array
		//$fid_enseigne,$fid_client, $fid_panier,$fid_database
		$array_produits=array();
		$array_enseigne=array();     //--Avec info et coefficient
		$array_enseigne_coefficient=array(); //--To sort
		$array_id_enseigne=array(); //--Seulement id_enseigne
		$array_prix_combination=array();
		$array_panier_courant=array();
		//--Array enseigne

	   // Si on veut changer la quantite d'un produit
		$sql0 = "SELECT DISTINCT(id_produit) FROM Contenu_Panier WHERE Contenu_Panier.id_client='".$fid_client ."' AND Contenu_Panier.id_panier='". $fid_panier ."' AND Contenu_Panier.id_database='". $fid_database ."' AND actif=1;";
		$res0 = mysql_query($sql0);
		$nb = mysql_numrows($res0);

		$prix_special=0;
		if ($nb != 0)
			{
				while($row = mysql_fetch_assoc($res0))
				{
					$sql = "SELECT Contenu_Panier.quantite_produit,
									Contenu_Panier.id_produit,
									Enseigne.id_enseigne,
									Enseigne.nom_enseigne,
									Enseigne.Prix_Coefficient,
									Produit.id_enseigne,
									Produit.prix_special,
									Produit.prix_regulier,
									Produit.id_produit,
									Produit.unite_fr
									FROM Contenu_Panier
									LEFT JOIN Produit ON Contenu_Panier.id_produit = Produit.id_produit
									LEFT JOIN Enseigne ON Contenu_Panier.id_enseigne = Enseigne.id_enseigne
									WHERE Contenu_Panier.id_client='". $fid_client ."'
									AND actif = 1 AND Contenu_Panier.id_panier='". $fid_panier."'
									AND Produit.id_produit= '".$row["id_produit"]."';";

					$res = mysql_query($sql);
					$nb2 = mysql_numrows($res);  // on recupere le nombre d'enregistrements
					$i = 0;
						 while ($i < $nb2){ // parcours des resultats de la requete
								//--Array produits
								$fid_produit= mysql_result($res, $i, "Contenu_Panier.id_produit");
								$fquantite= mysql_result($res, $i, "Contenu_Panier.quantite_produit");
								$fprix_special = mysql_result($res, $i, "Produit.prix_special");
								$fprix_regulier = mysql_result($res, $i, "Produit.prix_regulier");
								$fid_enseigne2 = mysql_result($res, $i, "Produit.id_enseigne");
								$coefficient_estime = mysql_result($res, $i, "Enseigne.Prix_Coefficient");
								$coefficient_estime_nom = mysql_result($res, $i, "Enseigne.nom_enseigne");
								$array_produits[$fid_produit]=array('id_produit'=>$fid_produit, 'quantite'=>$fquantite,
												                   'prix_special'=>$fprix_special,'prix_regulier'=>$fprix_regulier,
																   'id_enseigne'=>$fid_enseigne2,'coefficient'=>$coefficient_estime);
								//--Array Enseigne
								$array_enseigne[$fid_enseigne2]=array('id_enseigne'=>$fid_enseigne2,'nom_enseigne'=>$coefficient_estime_nom,'coefficient'=>$coefficient_estime);
								$prix_special=$prix_special + ($fprix_special*$fquantite);
								$i=$nb2;
							}
						}
			} //--End while produit
		//echo variable_to_html($array_produits);
		//--Array  id_enseigne for combination
		foreach ($array_enseigne as $id_enseigne=>$data) {
			array_push($array_id_enseigne, $id_enseigne);
			array_push($array_enseigne_coefficient, $data['coefficient']);
			//echo "$id_enseigne".$data['nom_enseigne']."<br>";
			}
		//--Sort by coefficient du plus grand au plus petit
		array_multisort($array_enseigne_coefficient, SORT_ASC, $array_enseigne);

		//--1. Get a list of combinations
		for ($i=1;$i<=count($array_id_enseigne);$i++) {
			if ($i<=$fmax_enseigne) {
			//getCombinations($array_id_enseigne,$fmax_enseigne)
			//$array_prix_combination=
				foreach (getCombinations($array_id_enseigne,$i) as $key=>$data)  array_push($array_prix_combination,$data);
			}
		}
		//echo variable_to_html($array_prix_combination);
		//--2. For each combination, calculate a price
		$count=count($array_prix_combination);
		$array_prix_panier=array(); //--To sort
		//echo variable_to_html($array_enseigne);
		for ($i=0; $i<$count;$i++) {
			$ids_enseigne_calcul=$array_prix_combination[$i];
			//--Array of this enseignes
			$tmp_enseigne=array();
			foreach ($array_enseigne as $key=>$data) {
				foreach ($ids_enseigne_calcul as $id) {
					if ($id==$data['id_enseigne']) array_push($tmp_enseigne, $data);
				}
			}
			$fprix_total=0;
			foreach ($array_produits as $id_produit=>$data) {
					//--CAS 1. id_enseigne in this combination => On garde le prix
				//echo "$id_produit =>";
				if (in_array($data['id_enseigne'], $ids_enseigne_calcul)) {
					$fprix_total=$fprix_total + ($data['prix_special']*$data['quantite']);
					//echo ($data['prix_special']*$data['quantite'])."<br>";
				} else {
					//--CAS 2. id_enseigne in this combination, on prend le prix au coefficient le plus bas
					if ($data['id_enseigne']!=$array_enseigne[0]['id_enseigne']) {
						$coefficient=$array_enseigne[0]['coefficient'];
					} else {
						$coefficient=$array_enseigne[1]['coefficient'];
					}
					//echo  "$coefficient =>".((($data['prix_regulier']*$coefficient)/$data['coefficient'])*$data['quantite'])."<br>";
					$fprix_total=$fprix_total + ((($data['prix_regulier']*$coefficient)/$data['coefficient'])*$data['quantite']);

				}
			}
			array_push($array_prix_panier, $fprix_total);
			unset($array_prix_combination[$i]);
			$array_prix_combination[$i]['prix']=$fprix_total;
			//$array_prix_combination[$i]['id_enseigne']=$ids_enseigne_calcul;
			$array_prix_combination[$i]['ids_enseigne']=$tmp_enseigne;
			//echo variable_to_html($data);
		} //--End combination

		//--Sort by smallest price
		array_multisort($array_prix_panier, SORT_ASC, $array_prix_combination);
		//--Set the last element to the default price.
		$array_panier_courant['prix']=$prix_special;
		$array_panier_courant['ids_enseigne']=$array_enseigne;
		array_push($array_prix_combination,$array_panier_courant);
		//array_push($array_prix_combination,$sql0);
		//--Create the n-1 combination array
		// $array_results=array();
		// for ($i=1;$i<count($array_id_enseigne);$i++) {
		// $tmp=array();
		// foreach ($array_prix_combination as $key=>$data) {
			// if (count($data['ids_enseigne'])==$i) {
				// array_push($tmp, $data);
			// }
		// }
		// $array_results[$i]=$tmp;
		// }

		//--Array for n produit
		//$array_results[count($array_id_enseigne)]=$array_panier_courant;

		//echo variable_to_html($array_prix_combination);

			$fp = fopen("$fichier_json",'w');
			fwrite($fp,json_encode($array_prix_combination));
			fclose($fp);
			return json_encode($array_prix_combination);
		}

	/////////////////////////////////////////////////////////////////////////
	/// PROMOTIONS ET SCRIPTS
	/// Etienne Lord - 2012

	//<!--------------------------------------------------------//
	// insert a Promotion into the database
	// fpromo: Promotion Type: promo, duo, trio
	// fid_product: id_produit
	// actif
	//--------------------------------------------------------->
    Function bd_insertPromotion($fpromo, $fid_product,$fid_database,$fid_categorie, $caption_fr, $caption_en,$caption_es, $description_fr, $description_en,$description_es,$f_actif,$f_position) {
		//$fpromo="promo";
		//$fpromo="combo_"+return_unique_id();

		//--1.0 Calcul des variables
		$remoteAddr="";

		//--2.0 On enleve si déja présent

		$sql="DELETE FROM Promotions WHERE id_categorie ='$fid_categorie' AND id_database = '$fid_database' AND id_promotion='$fpromo' AND id_produit='$fid_produit';";
		$res = mysql_query($sql);
		if (!$res) {
			echo ($sql." ".mysql_error());
			}
		//--3.0 Insertion dans contenu panier
		$sql="INSERT INTO Promotions (id_promotion, id_database, id_categorie, id_produit, caption_fr, caption_en, caption_es, description_fr, description_en, description_es, actif, position_categorie) VALUES ('$fpromo','$fid_database','$fid_categorie','$fid_product','$caption_fr','$caption_en','$caption_es','$description_fr','$description_en','$description_es','$f_actif','$f_position');";
		$res = mysql_query($sql);
		if (!$res) {
			echo ($sql." ".mysql_error());
			return false;
		}
		echo "done";
		return true;
   }


   Function bd_modifyPromotion($id_promotion, $caption_fr,$caption_en,$caption_es,$description_fr,$description_en,$description_es) {
		$sql="UPDATE Promotions SET caption_fr='$caption_fr',
			 caption_en='$caption_en',
			 caption_es='$caption_es',
			 description_fr='$description_fr',
			 description_en='$description_en',
			 description_es='$description_es'
			 WHERE id_promotion='$id_promotion';";
		saveLog($sql);
		$res = mysql_query($sql);

   }

   Function bd_setPromotionPosition($fid_product,$fid_promotion,$fid_position_categorie) {
		$sql="UPDATE Promotions SET position_categorie=$fid_position_categorie WHERE id_promotion='$fid_promotion' AND id_produit='$fid_product';";
		saveLog($sql);
		$res = mysql_query($sql);
   }


   Function bd_setPromotionOrder($fid_product,$fid_promotion,$fid_order) {
		$sql="UPDATE Promotions SET actif=$fid_order WHERE id_promotion='$fid_promotion' AND id_produit='$fid_product';";
		saveLog($sql);
		$res = mysql_query($sql);
   }

	//--SET A PRODUCT TO THE HOMEPAGE
	Function bd_insertPromotionSimple($fid_product,$fid_database,$fid_categorie) {
		$promotion_id="promo";
		//--Find the position in categorie
		$position=getProductPositionInCategorie($fid_product, $fid_categorie);
		bd_insertPromotion($promotion_id, $fid_product,$fid_database,$fid_categorie, "", "","",1, $position);
	}

	Function bd_removePromotionSimple($fid_product,$fid_database,$fid_categorie) {
		$promotion_id="promo";
		$sql="DELETE FROM Promotions WHERE id_categorie ='$fid_categorie' AND id_database = '$fid_database' AND id_promotion='$promotion_id' AND id_produit='$fid_product';";
		$res = mysql_query($sql);
		if (!$res) {
			echo ($sql." ".mysql_error());
			return false;
		}
		return true;
	}

	Function bd_removePromotion($fpromo, $fid_produit,$fid_database,$fid_categorie) {
		$sql="DELETE FROM Promotions WHERE id_categorie ='$fid_categorie' AND id_database = '$fid_database' AND id_promotion='$fpromo' AND id_produit='$fid_produit';";
		$res = mysql_query($sql);
		if (!$res) {
			echo ($sql." ".mysql_error());
			return false;
		}
		return true;
	}

	Function bd_getPromotionWithIdProduit($fid_product) {
		 $stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT id_promotion,actif FROM Promotions WHERE Promotions.id_produit='$fid_product';";
					$res2 = mysql_query($sqlq);
					while ($results = mysql_fetch_array($res2))
					{
						$id_promotion=$results['id_promotion'];
						$stack[$id_promotion]=$results['actif'];
					}
			return $stack;
	}

	Function bd_getPromotionWithDatabase($fid_database,$view_status) {
		$stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT id_produit, position_categorie FROM Promotions JOIN Produit USING (id_produit) WHERE Promotions.id_promotion='promo' AND Promotions.id_database='$fid_database' AND status=$view_status ORDER BY Promotions.actif;";
					$res2 = mysql_query($sqlq);

					while ($results = mysql_fetch_array($res2))
					{
						$position_categorie=$results['position_categorie'];
						$id_produit=$results['id_produit'];
						$stack[$id_produit]=$position_categorie;
					}
			return $stack;
	}


	Function bd_getPromotionWithEnseigne($fid_database,$fid_enseigne) {
		$stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT id_produit, position_categorie,id_promotion FROM Promotions JOIN Produit USING (id_produit) WHERE Promotions.id_database='$fid_database' AND id_enseigne=$fid_enseigne ORDER BY Promotions.actif;";
					$res2 = mysql_query($sqlq);

					while ($results = mysql_fetch_array($res2))
					{
						$array=array('id_produit'=>$results['id_produit'], 'id_promotion'=>$results['id_promotion'],'position_categorie'=>$results['position_categorie']);
						array_push($stack, $array);
					}
			return $stack;
	}

	Function bd_getValidPromotionWithEnseigne($fid_database,$fid_enseigne) {
		$id_promotion='promo_'.$fid_enseigne;
		$stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT id_produit, position_categorie,id_promotion FROM Promotions JOIN Produit USING (id_produit) WHERE Promotions.id_database='$fid_database' AND id_enseigne=$fid_enseigne AND Produit.status=1 AND id_promotion='$id_promotion' ORDER BY Promotions.actif;";
					$res2 = mysql_query($sqlq);
					if ($res2) {
						while ($results = mysql_fetch_array($res2))
						{
							$array=array('id_produit'=>$results['id_produit'], 'id_promotion'=>$results['id_promotion'],'position_categorie'=>$results['position_categorie']);
							array_push($stack, $array);
						}
					}
			return $stack;
	}

	Function bd_getComboIDWithDatabase($fid_database,$view_status) {
		$stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT id_promotion,caption_fr,caption_en, caption_es,description_fr,description_en, description_es, actif FROM Promotions JOIN Produit USING (id_produit) WHERE Promotions.id_promotion LIKE 'combo_%' AND Promotions.id_database='$fid_database' AND status=$view_status GROUP BY id_promotion;";
					$res2 = mysql_query($sqlq);
					while ($results = mysql_fetch_array($res2))
					{
						$id_promotion=$results['id_promotion'];
						$caption=array('caption_fr'=>$results['caption_fr'],'caption_en'=>$results['caption_en'],'caption_es'=>$results['caption_es'],'description_fr'=>$results['description_fr'],'description_en'=>$results['description_en'],'description_es'=>$results['description_es'],'actif'=>$results['actif']);
						$stack[$id_promotion]=$caption;
					}
			return $stack;
	}

	Function bd_getProductIDWithCombo($fid_combo) {
		$stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT Promotions.*,prix_special,prix_regulier, Enseigne.* FROM Promotions JOIN Produit ON Promotions.id_produit=Produit.id_produit JOIN Enseigne ON Produit.id_enseigne=Enseigne.id_enseigne WHERE Promotions.id_promotion ='$fid_combo';";
					$res2 = mysql_query($sqlq);
					$prix_total_special=0;
					$prix_total_regulier=0;
					$fichier_logo=array();
					$nom_enseigne=array();
					$total_items=0;
					while ($results = mysql_fetch_array($res2))
					{
						$prix_total_special+=$results['prix_special'];
						$prix_total_regulier+=$results['prix_regulier'];
						$nom_enseigne[$results['nom_enseigne']]=1;
						$fichier_logo[$results['fichier_logo']]=1;
						$id_produit=$results['id_produit'];
						$total_items++;
						$caption=array('caption_fr'=>$results['caption_fr'],'caption_en'=>$results['caption_en'],'caption_es'=>$results['caption_es'],'description_fr'=>$results['description_fr'],'description_en'=>$results['description_en'],'description_es'=>$results['description_es'],'actif'=>$results['actif']);
						array_push($stack,$id_produit);
					}

					$stack['caption']=$caption;
					$stack['nom_enseigne']=$nom_enseigne;
					$stack['total_items']=$total_items;
					$stack['prix_total_special']=$prix_total_special;
					$stack['prix_total_regulier']=$prix_total_regulier;
					$stack['pourcentage_de_rabais']=returnRabais($stack['prix_total_regulier'], $stack['prix_total_special']);
					$stack['fichier_logo']=$fichier_logo;

			return $stack;
	}

	Function bd_removeCombo($fid_combo) {
		$promotion_id="promo";
		$sql="DELETE FROM Promotions WHERE id_promotion='$fid_combo';";
		$res = mysql_query($sql);
	}

	Function bd_InsertPromotionInPanier($fid_promo,$fid_panier, $fid_client) {
					//--1. Sélection des produits en promotions
					$sqlq = "SELECT id_produit FROM Promotions WHERE Promotions.id_promotion ='$fid_promo';";
				    $res1 = mysql_query($sqlq);
					while ($results = mysql_fetch_array($res1)) {
						$id_produit=$results['id_produit'];
						//--2. Sélection du panier en cours pour vérifier si on a déjà le produit dans le panier
						$sqlq2 = "SELECT quantite_produit FROM Contenu_Panier WHERE id_panier = '$fid_panier' AND id_client = '$fid_client' AND id_produit = '$id_produit' AND actif = 1;";
						 echo "$sqlq2<br>";
						 $quantite_produit=1;
						 $res = mysql_query($sqlq2);
						 if ($res) {
							$results_quantite = mysql_fetch_array($res);
							$quantite_panier_courant = $results_quantite["quantite_produit"];
						 }
						 if ($quantite_panier_courant > 0){
							 $quantite_produit = $quantite_produit+$quantite_panier_courant;
						 }
						bd_InsertProduct($quantite_produit, $id_produit,$fid_panier, $fid_client);
				}
	}

	//<!--------------------------------------------------------//
	// This function return the product information for
	// promotions
	//--------------------------------------------------------->
    function bd_getProduct($fid_produit) {
			$stack=array();
			 $sql = "SELECT  Produit.*, Categorie.*, Enseigne.nom_enseigne, Enseigne.fichier_logo FROM Produit
					JOIN Enseigne ON Produit.id_enseigne = Enseigne.id_enseigne
					JOIN Categorie ON Categorie.id_categorie=Produit.id_categorie
					WHERE Produit.id_produit = '$fid_produit'";

			$res = mysql_query($sql);
			while(($row = mysql_fetch_assoc($res))){
					if ($_SESSION["langue"]=="en") {
							$nom_produit = $row["nom_produit_en"];
							$nom_produit2 = $row["nom_produit_en2"];
							$quantite = $row["quantite_en"];
							$brand = $row["brand_en"];
							$categorie=$row["nom_categorie_en"];
						} else if ($_SESSION["langue"]=='es') {
							$nom_produit = $row["nom_produit_es"];
							$nom_produit2 = $row["nom_produit_es2"];
							$quantite = $row["quantite_es"];
							$brand = $row["brand_es"];
							$categorie=$row["nom_categorie_en"];
						} else {
							$nom_produit = $row["nom_produit_fr"];
							$nom_produit2 = $row["nom_produit_fr2"];
							$quantite = $row["quantite_fr"];
							$brand = $row["brand_fr"];
							$categorie=$row["nom_categorie_fr"];
						}
						$stack['id_database'] = $row["id_database"];
						$stack['prix_regulier'] = $row["prix_regulier"];
						$stack['prix_special'] = $row["prix_special"];
						$stack['id_categorie'] = $row["id_categorie"];
						$stack['id_produit'] = $row["id_produit"];
						$stack['categorie'] = $categorie;
						$stack['date']=bd_DateEn(bd_UTF8_decode($row['date']));
						$stack['nom_enseigne'] = $row["nom_enseigne"];
						$stack['id_enseigne'] = $row["id_enseigne"];
						$stack['fichier_logo'] = $row["fichier_logo"];
						$stack['FD_ID']=$row["FD_ID"];
						$stack['pourcentage_de_rabais'] = returnRabais($stack['prix_regulier'], $stack['prix_special']);
						//--Decodage de l'information pour affichage
						$stack['nom_produit']=ucfirst(bd_UTF8_decode($nom_produit));
						$stack['nom_produit2']=ucfirst(bd_UTF8_decode($nom_produit2));
						if ($stack['nom_produit2']=="---"||$stack['nom_produit2']=="N/A") $stack['nom_produit2']="";
						$brand=ucfirst(bd_UTF8_decode($brand));
						if ($brand=="N/A"||$brand=="---"||$brand=="") $brand="";
						$stack['brand']=$brand;
						$quantite=bd_UTF8_decode($quantite);
						if ($quantite=="N/A"||$quantite=="---"||$quantite==""||$quantite=="Each"||$quantite=="Chacun") $quantite="";
						$stack['quantite']=$quantite;
						//--Image
						$image="images/getPicture.php?id_produit=$fid_produit";
						$image_big="images/getPictureBig.php?id_produit=$fid_produit";
						$stack['image']=$image;
						$stack['image_valid']=(getCorrespondence($fid_produit)!='images/NotAvailable.gif');
						$stack['image_big']=$image_big;
						$stack['image_big_valid']=(getCorrespondenceBig($fid_produit)!='images/NotAvailable.gif');
		}
		return $stack;
   }

  //<!--------------------------------------------------------//
	// This function return the product position in a
	// given categorie
	// Usage: use for the promotion page
	// Return: 0 if not found, the posititon otherwise
	// Note: the order and categorie is fixed
	// Note: Go with the query index.php?list_categories=XXX&triPar=0&magasin=0
	//--------------------------------------------------------->
   function getProductPositionInCategorie($fid_produit, $fid_categorie,$fid_database) {
	// 1. Run a default query
	$sql = "SELECT Produit.id_produit FROM Produit WHERE Produit.id_categorie=$fid_categorie AND Produit.status = 1 AND Produit.id_database = '$fid_database'
			ORDER BY (1-(Produit.prix_special/Produit.prix_regulier)) DESC ,Produit.id_produit ASC;";
    $res = mysql_query($sql);
	$count=0;
	while(($row = mysql_fetch_assoc($res))){
		if ($row['id_produit']==$fid_produit) return $count;
		$count++;
    }
    return 0;
   }


    function return_unique_id() {
		return exec("date +%s%N");
   }



	//<!--------------------------------------------------------//
	// This function return the number of flyers in database
	// fid_database = 0 ALL database
	// fid_database > 0 this database
	// fid_status   = 0 Inactive flyers only
	// fid_status   = 1 Active flyers only
	// fid_status   = 2 InProcess flyers only
	// fid_status   = 3 Any
	//--Mpte
	//--------------------------------------------------------->
	function bd_getNbFlyers($fid_database,$fid_status) {
		if ($fid_database==0&&$fid_status==3) {
			$sqlq='SELECT COUNT(*) FROM Produit GROUP BY substring(id_produit,1,19);';
		}
		else if ($fid_database==0&&$fid_status<3) {
			$sqlq="SELECT COUNT(*) FROM Produit WHERE status=$fid_status GROUP BY substring(id_produit,1,19);";
		}
		else if ($fid_database!=0&&$fid_status<3) {
			$sqlq="SELECT COUNT(*) FROM Produit WHERE status=$fid_status WHERE id_database=$fid_database GROUP BY substring(id_produit,1,19);";
		}
		else {
				$sqlq="SELECT COUNT(*) FROM Produit WHERE id_database=$fid_database GROUP BY substring(id_produit,1,19);";
			//$sqlq="SELECT COUNT(*) AS total FROM (SELECT COUNT(*) FROM Produit WHERE id_database=$fid_database GROUP BY substring(id_produit,1,19) ) AS Sq;";
		}
		$res = mysql_query($sqlq);
		if ($res) return mysql_num_rows($res);
		return 0;
	}

	//////////////////////////////////////////////////////////////////////
	/// Enseigne

	//<!----------------------------------------------------------------------------------//
	//   Get the id_enseigne found in a panier
	//  W A R N I N G : Forced to id_enseigne < 15
	//----------------------------------------------------------------------------------->
		 function bd_getEnseigneWithPanier($fid_panier, $fid_client,$fid_database) {
					 $stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT DISTINCT(id_enseigne) FROM Contenu_Panier WHERE Contenu_Panier.id_client='".$fid_client ."' AND Contenu_Panier.id_database='".$fid_database."' AND Contenu_Panier.id_panier='". $fid_panier ."' AND actif=1;";
					$res2 = mysql_query($sqlq);
					while ($enseigne = mysql_fetch_array($res2))
					{
						$id_e=$enseigne['id_enseigne'];
						//if ($id_e<=15) {
							array_push($stack, $id_e);
							//}
					}
			return $stack;
	   }

	    function bd_getCirculaire($fid_enseigne,$flangue) {
					 $stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT circulaire_fr, circulaire_en FROM Enseigne WHERE id_enseigne='".$fid_enseigne ."';";
					$res2 = mysql_query($sqlq);
					while ($enseigne = mysql_fetch_array($res2))
					{
						if ($flangue=='fr') return $enseigne['circulaire_fr'];
						if ($flangue=='en') return $enseigne['circulaire_en'];
					}
			return "";
	   }

	    function bd_getIdProduitWithPanier($fid_panier, $fid_client,$fid_database) {
					 $stack=array();
					//--Nouveau May 2012 (Etienne Lord)
					$sqlq = "SELECT DISTINCT(id_produit) FROM Contenu_Panier WHERE Contenu_Panier.id_client='".$fid_client ."' AND Contenu_Panier.id_database='".$fid_database."' AND Contenu_Panier.id_panier='". $fid_panier ."' AND actif=1;";
					$res2 = mysql_query($sqlq);
					while ($produit = mysql_fetch_array($res2))
					{
						$id_e=$produit['id_produit'];
						array_push($stack, $id_e);
					}
			return $stack;
	   }

	   	//<!----------------------------------------------------------------------------------//
		//    Get the nom_enseigne of en enseigne associated with an id
		//----------------------------------------------------------------------------------->
	    function bd_getEnseigne($fid_enseigne) {
			$sql2="SELECT nom_enseigne FROM Enseigne WHERE Enseigne.id_enseigne=$fid_enseigne;";
			$res2 = mysql_query($sql2);
			while ($row2 = mysql_fetch_assoc($res2)) {
				return $row2['nom_enseigne'];
			}
			return "";
		}

		//<!----------------------------------------------------------------------------------//
		//    Get the image of en enseigne associated with an id
		//----------------------------------------------------------------------------------->
	    function bd_getEnseigneLogo($fid_enseigne) {
			$sql2="SELECT fichier_logo FROM Enseigne WHERE Enseigne.id_enseigne=$fid_enseigne LIMIT 1;";
			$res2 = mysql_query($sql2);
			while ($row2 = mysql_fetch_assoc($res2)) {
				return $row2['fichier_logo'];
			}
			return "";
		}

	//<!----------------------------------------------------------------------------------//
	//    Get all the enseigne and id_enseigne in a database (id_database)
	//----------------------------------------------------------------------------------->
		function bd_getEnseigneWithDatabase($fid_database) {
			 $stack=array();
			$sql2="SELECT id_enseigne,nom_enseigne,fichier_logo,prix_coefficient FROM Enseigne WHERE Enseigne.id_enseigne IN (SELECT DISTINCT (id_enseigne) FROM Produit WHERE id_database='$fid_database' AND status=1) ORDER BY nom_enseigne;";
			$res2 = mysql_query($sql2);
			while ($row2 = mysql_fetch_assoc($res2)) {
				$v=$row2['id_enseigne'];
				//$w=$row2['fichier_logo'];
				$array=array('id_enseigne'=>$id_enseigne, 'nom_enseigne'=>$row2['nom_enseigne'], 'fichier_logo'=>$row2['fichier_logo'],'prix_coefficient'=>$row2['prix_coefficient']);
				array_push($stack, $array);
				//$stack[$v]=$row2['nom_enseigne'];
			}
			return $stack;
		}

		function bd_getEnseigneWithDatabase2($fid_database) {
			$sql1="SELECT id_enseigneVille FROM Ville WHERE id_database=$fid_database;";
			$res1 = mysql_query($sql1);
			while ($row2 = mysql_fetch_assoc($res1)) {
				$n_adr=$row2['id_enseigneVille'];
				$n_adr=str_replace("-",",",$n_adr);
				$n_adr=substr($n_adr, 0, -1);
			}
			$stack=array();
			$sql2="SELECT id_enseigne,nom_enseigne,fichier_logo FROM Enseigne WHERE Enseigne.id_enseigne IN ($n_adr);";
			$res2 = mysql_query($sql2);
      echo 'console.log("'.$res2.'");'.NL;
			while ($row2 = mysql_fetch_assoc($res2)) {
				$v=$row2['id_enseigne'];
				//$w=$row2['fichier_logo'];
				$stack[$v]=array($row2['nom_enseigne'],$row2['fichier_logo']);
				//$stack[$v]=$row2['nom_enseigne'];
			}
			return $stack;
		}

	//<!----------------------------------------------------------------------------------//
	//    Add statistic by date for a click
	//----------------------------------------------------------------------------------->
		Function click($page) {
			$dateDownloaded= date( 'Y-m-d');  // Current date
			$version=""; 				  // File version (ex. add information for the product_id, etc.)
			$remoteAddr="";
			if (isset($_SERVER['REMOTE_ADDR'])) $remoteAddr=$_SERVER['REMOTE_ADDR'];
			//--Add to the Database
			$sth = "INSERT INTO Counter(Page, DateDownloaded, version,remoteAddr) VALUES ('$page','$dateDownloaded','$version','$remoteAddr');";
			$res2 = mysql_query($sth);
			if (!$res2) {
				//saveLog(mysql_error());
			}
		}

	//<!----------------------------------------------------------------------------------//
	//    Is the postal code valid?
	//----------------------------------------------------------------------------------->

		Function isValidCodePostal($codePostal) {

			$data = getCodePostalData($codePostal);
			if($data[0] == ""){
				return false;
			}
			return true;

			$codePostal=strtolower ($codePostal);
			$codePostal=str_replace(" ","",$codePostal);
			$sth = "SELECT COUNT(*) AS Total FROM Code_Postal WHERE codePostal = '$codePostal' LIMIT 1;";
			$res2 = mysql_query($sth);
			while ($row2 = mysql_fetch_assoc($res2)) {
					return ($row2['Total']>0);
			}
			return false;
		}

	//<!----------------------------------------------------------------------------------//
	//    Get the count for the GoogleMap page for today
	//----------------------------------------------------------------------------------->
		Function getGoogleMapCount() {
				$dateToday= date( 'Y-m-d');
				$sth = "SELECT COUNT(*) AS Total FROM Counter WHERE Page = 'GoogleMap.php' AND DateDownloaded = '$dateToday';";
				$res2 = mysql_query($sth);
				if ($res2)
					while ($row2 = mysql_fetch_assoc($res2)) {
						return $row2['Total'];
					}
			return 0;
		}

		function bd_generateMenuDroite() {
			$sql1="SELECT DISTINCT(id_database) FROM Ville;";
			$res1 = mysql_query($sql1);
			while ($row1 = mysql_fetch_assoc($res1)) {
				$strId="";
				$fid_database=$row1['id_database'];
				$sql2="SELECT id_enseigne,nom_enseigne,fichier_logo FROM Enseigne WHERE Enseigne.id_enseigne IN (SELECT DISTINCT (id_enseigne) FROM Produit WHERE id_database='$fid_database');";
				$res2 = mysql_query($sql2);
				while ($row2 = mysql_fetch_assoc($res2)) {
					$v=$row2['id_enseigne'];
					$strId=$strId . strval($v) . "-";
				}
				$sql2="UPDATE Ville SET id_enseigneVille = '$strId' WHERE id_database='$fid_database'";
				$res2 = mysql_query($sql2);
			}
		}


	//<!----------------------------------------------------------------------------------//
	//    VARGET/POST/REQUEST
	//----------------------------------------------------------------------------------->

			function varPOST($variable,$defaut){
				if(isset($_POST[$variable])){
					return $_POST[$variable];
				}
				else{
					return $defaut;
				}
			}

			function varGET($variable,$defaut){
				if(isset($_GET[$variable])){
					return $_GET[$variable];
				}
				else{
					return $defaut;
				}
			}

			function varREQUEST($variable,$defaut){
				if(isset($_REQUEST[$variable])){
					return $_REQUEST[$variable];
				}
				else{
					return $defaut;
				}
			}

			function read_SESSION($nom_variable,$default){
				$variable = $default;
				if(isset($_SESSION[$nom_variable])){
					$variable = $_SESSION[$nom_variable];
				}
				return $variable;
			}

	////////////////////////////////////////////////////////////////////////////////////////////////////////
	/// INFORMATIONS NUTRITIVES

	Function bd_getValeurNutritiveCFD_100g($FD_ID,$NT_ID) {
		$sqlq="SELECT NT_SYM, UNIT, NT_NME, NT_NMF, NT_VALUE,DAILY_VAL FROM CFD_NT_NM JOIN CFD_NT_AMT USING(NT_ID) WHERE CFD_NT_AMT.FD_ID =$FD_ID AND CFD_NT_NM.NT_ID=$NT_ID" ;
		$res   = mysql_query($sqlq);
		if (!$res) return;
		while($row = mysql_fetch_assoc($res)) {
			$NT_NME=$row['NT_NME'];
			$NT_NMF=$row['NT_NMF'];
			$NT_VALUE=$row['NT_VALUE'];
			$DAILY_VAL=$row['DAILY_VAL'];
			$UNIT=$row['UNIT'];
			if ($UNIT=='kCal') $UNIT="";
			$PERCENT_DAILY_VAL=0;
			if ($DAILY_VAL!=0) $PERCENT_DAILY_VAL=round(($NT_VALUE/$DAILY_VAL)*100,1);
			$a=array('NT_NME'=>$NT_NME, 'NT_NMF'=>$NT_NMF, 'NT_VALUE'=>$NT_VALUE,'DAILY_VAL'=>$DAILY_VAL,'PERCENT_DAILY_VAL'=>$PERCENT_DAILY_VAL, 'UNIT'=>$UNIT);
			return $a;
		}
	}


	Function bd_getValeurNutritiveCFD_Fat_100g($FD_ID) {
		$saturated=bd_getValeurNutritiveCFD_100g($FD_ID,605);
		$trans=bd_getValeurNutritiveCFD_100g($FD_ID,606);
		$total=round((($saturated['NT_VALUE']+$trans['NT_VALUE'])/20)*100);
		return "<tr>
							<td colspan='2'>Saturated ".round($saturated['NT_VALUE'])." g<br />+ Trans ".round($trans['NT_VALUE'])." g
							</td>
							<td align=center colspan='2'><strong>".$total."</strong> %</td>
						</tr>";
		}

	function getInfoNutritive($FD_ID) {
			echo "<table style='border: 1px solid black; background-color:#79D62D;'><tr><td>";
			echo "<div class='borderSolid' style='width:300px; margin-bottom:10px; ' >
					<div class='fontSize140 strong'><b>Nutrition Facts</b></div>
					<div style='width:300px; border-bottom:solid 4px #000000;'>Per 100 g</div>
                    <table id='dv_over' style='margin:0 auto; width:275px; line-height:1.4em;' cellspacing='0' cellpadding='0' class='fontSize90'>";
			echo "	<tr>
							<th scope='col' align=left width55' colspan='2'>Amount</th>
							<th scope='col' align=center colspan='2'>% Daily Value</th>
						</tr>";
			$data=bd_getValeurNutritiveCFD_100g($FD_ID,208); //Calories
						echo "<tr><td colspan='4' ><strong>Calories ".$data['NT_VALUE']."</strong></td></tr>";
			$data=bd_getValeurNutritiveCFD_100g($FD_ID,204);
						echo "<tr><td  colspan='2'><strong>Fat</strong>&nbsp;".$data['NT_VALUE']." ".$data['UNIT']."</td>";
						echo "<td align=center colspan='2'><strong>".$data['PERCENT_DAILY_VAL']."</strong> %</td></tr>";


						echo bd_getValeurNutritiveCFD_Fat_100g($FD_ID);

					$data=bd_getValeurNutritiveCFD_100g($FD_ID,601);
						echo "<tr><td  colspan='4'><strong>Cholesterol</strong>&nbsp;".$data['NT_VALUE']." ".$data['UNIT']."</td></tr>";
					$data=bd_getValeurNutritiveCFD_100g($FD_ID,307);
						echo "<tr><td  colspan='2'><strong>Sodium</strong>&nbsp;".$data['NT_VALUE']." ".$data['UNIT']."</td>";
						echo "<td align=center colspan='2'><strong>".$data['PERCENT_DAILY_VAL']."</strong> %</td></tr>";
					$data=bd_getValeurNutritiveCFD_100g($FD_ID,205);
						echo "<tr><td  colspan='2'><strong>Carbohydrate</strong>&nbsp;".round($data['NT_VALUE'])." ".$data['UNIT']."</td>";
						echo "<td align=center colspan='2'><strong>".$data['PERCENT_DAILY_VAL']."</strong> %</td></tr>";
					$data=bd_getValeurNutritiveCFD_100g($FD_ID,291); //Fibre
						echo "<tr><td  colspan='2'><strong>Fibre</strong>&nbsp;".$data['NT_VALUE']." ".$data['UNIT']."</td>";
						echo "<td align=center colspan='2'><strong>".$data['PERCENT_DAILY_VAL']."</strong> %</td></tr>";
					$data=bd_getValeurNutritiveCFD_100g($FD_ID,269); //Sugars
						echo "<tr><td class='bottomSolid indent10' colspan='4'>Sugars&nbsp;".$data['NT_VALUE']." ".$data['UNIT']."</td></tr>";
					$data=bd_getValeurNutritiveCFD_100g($FD_ID,203); //Protein
						echo "<tr><td colspan='4' style='border-bottom:solid 4px #000000;'><strong>Protein&nbsp;</strong>".$data['NT_VALUE']." ".$data['UNIT']."</td></tr>";
					$data=bd_getValeurNutritiveCFD_100g($FD_ID,814); //Vitamin A
						echo "<tr>
							<td >Vitamin A</td>
							<td align=center>".$data['PERCENT_DAILY_VAL']."&nbsp;%&nbsp;</td>";
						$data=bd_getValeurNutritiveCFD_100g($FD_ID,401); //Vitamin C
						echo "<td >Vitamin&nbsp;C&nbsp;</td>
							<td align=center>".$data['PERCENT_DAILY_VAL']."&nbsp;%&nbsp;</td></tr>";

						$data=bd_getValeurNutritiveCFD_100g($FD_ID,301);
						echo "<tr>
							<td>Calcium</td>
							<td align=center>".$data['PERCENT_DAILY_VAL']."&nbsp;%&nbsp;</td>";
						$data=bd_getValeurNutritiveCFD_100g($FD_ID,303);
						echo "<td>&nbsp;Iron&nbsp;</td>
							<td align=center>".$data['PERCENT_DAILY_VAL']." %</td></tr>";
						$data=bd_getValeurNutritiveCFD_100g($FD_ID,306);
						echo "<tr><td>&nbsp;Potassium&nbsp;</td>
							<td align=center>".$data['PERCENT_DAILY_VAL']." %</td><td></td>
							</tr>";
					echo "</table></div>";
				echo "</td></tr></table>";
			}

	///////////////////////////////////////////////////////////////////
	/// CREATE A JSON OF THE PRODUCT

	//<!--------------------------------------------------------//
	// This function return the product information for
	// promotions
	//--------------------------------------------------------->
    function bd_getProductStripped($fid_produit,$langue) {
			$stack=array();
			 $sql = "SELECT  Produit.*, Categorie.*, Enseigne.nom_enseigne, Enseigne.fichier_logo FROM Produit
					JOIN Enseigne ON Produit.id_enseigne = Enseigne.id_enseigne
					JOIN Categorie ON Categorie.id_categorie=Produit.id_categorie
					WHERE Produit.id_produit = '$fid_produit'";

			$res = mysql_query($sql);
			while(($row = mysql_fetch_assoc($res))){
					if ($langue=="en") {
							$nom_produit = $row["nom_produit_en"];
							$nom_produit2 = $row["nom_produit_en2"];
							$quantite = $row["quantite_en"];
							$brand = $row["brand_en"];
							$categorie=$row["nom_categorie_en"];
						} else if ($langue=='es') {
							$nom_produit = $row["nom_produit_es"];
							$nom_produit2 = $row["nom_produit_es2"];
							$quantite = $row["quantite_es"];
							$brand = $row["brand_es"];
							$categorie=$row["nom_categorie_en"];
						} else {
							$nom_produit = $row["nom_produit_fr"];
							$nom_produit2 = $row["nom_produit_fr2"];
							$quantite = $row["quantite_fr"];
							$brand = $row["brand_fr"];
							$categorie=$row["nom_categorie_fr"];
						}
						$stack['i'] = $row["id_database"];
						$stack['r'] = $row["prix_regulier"];
						$stack['s'] = $row["prix_special"];
						$stack['c'] = $row["id_categorie"];
						$stack['p'] = $row["id_produit"];
						//$stack['a'] = html_entity_decode($categorie, ENT_COMPAT, 'UTF-8');
						$stack['d']=bd_DateEn(bd_UTF8_decode($row['date']));
						$stack['n'] = $row["nom_enseigne"];
						$stack['e'] = $row["id_enseigne"];
						//$stack['o'] = $row["fichier_logo"];
						//$stack['FD_ID']=$row["FD_ID"];
						$stack['b'] = returnRabais($stack['prix_regulier'], $stack['prix_special']);
						//--Decodage de l'information pour affichage
						$stack['x']=ucfirst(str_replace("\'","'",html_entity_decode($nom_produit, ENT_COMPAT, 'UTF-8')));
						//$stack['x']=ucfirst(bd_UTF8_decode($nom_produit));
						$stack['z']=ucfirst(str_replace("\'","'",html_entity_decode($nom_produit2, ENT_COMPAT, 'UTF-8')));
						if ($stack['z']=="---"||$stack['z']=="N/A") $stack['z']="";
						$brand=ucfirst($brand);
						if ($brand=="N/A"||$brand=="---"||$brand=="") $brand="";
						$stack['w']=$brand;
						$quantite=bd_UTF8_decode($quantite);
						if ($quantite=="N/A"||$quantite=="---"||$quantite==""||$quantite=="Each"||$quantite=="Chacun") $quantite="";
						$stack['q']=$quantite;
						//--Image
						$image="images/getPicture.php?id_produit=$fid_produit";
						$image_big="images/getPictureBig.php?id_produit=$fid_produit";
						//$stack['i']=$image;
						$stack['iv']=(getCorrespondence($fid_produit)!='images/NotAvailable.gif');
						//$stack['ib']=$image_big;
						$stack['ibv']=(getCorrespondenceBig($fid_produit)!='images/NotAvailable.gif');
		}
		return $stack;
   }

	function bd_getProductStripped2($fid_produit,$langue) {
			$stack=array();
			 $sql = "SELECT  Produit.*, Categorie.*, Enseigne.nom_enseigne, Enseigne.fichier_logo FROM Produit
					JOIN Enseigne ON Produit.id_enseigne = Enseigne.id_enseigne
					JOIN Categorie ON Categorie.id_categorie=Produit.id_categorie
					WHERE Produit.id_produit = '$fid_produit'";
			$res = mysql_query($sql);
			$row = mysql_fetch_assoc($res);
					if ($langue=="en") {
							$nom_produit = $row["nom_produit_en"];
							$nom_produit2 = $row["nom_produit_en2"];
							$quantite = $row["quantite_en"];
							$brand = $row["brand_en"];
							$categorie=$row["nom_categorie_en"];
						} else if ($langue=='es') {
							$nom_produit = $row["nom_produit_es"];
							$nom_produit2 = $row["nom_produit_es2"];
							$quantite = $row["quantite_es"];
							$brand = $row["brand_es"];
							$categorie=$row["nom_categorie_en"];
						} else {
							$nom_produit = $row["nom_produit_fr"];
							$nom_produit2 = $row["nom_produit_fr2"];
							$quantite = $row["quantite_fr"];
							$brand = $row["brand_fr"];
							$categorie=$row["nom_categorie_fr"];
						}
						//$stack['i'] = $row["id_produit"];
						array_push($stack, $row["id_produit"]);
						//$stack['c'] = $row["id_categorie"];
						array_push($stack, $row["id_categorie"]);
						//$stack['p'] = $row["id_produit"];
						//$stack['r'] = $row["prix_regulier"];
						$prix_regulier=$row["prix_regulier"];
						array_push($stack, $prix_regulier);
						//$stack['s'] = $row["prix_special"];
						$prix_special=$row["prix_special"];
						array_push($stack, $prix_special);
						//$stack['b'] = returnRabais($stack['prix_regulier'], $stack['prix_special']);
						array_push($stack, returnRabais($prix_regulier, $prix_special));

						//$stack['c'] = $row["id_categorie"];
						//$stack['a'] = html_entity_decode($categorie, ENT_COMPAT, 'UTF-8');
						//$stack['d']=bd_DateEn(bd_UTF8_decode($row['date']));
						array_push($stack, bd_DateUnix(bd_UTF8_decode($row['date'])));
						//$stack['n'] = $row["nom_enseigne"];

						//$stack['e'] = $row["id_enseigne"];
						array_push($stack, $row["id_enseigne"]);
						//$stack['o'] = $row["fichier_logo"];
						//$stack['FD_ID']=$row["FD_ID"];
						//$stack['b'] = returnRabais($stack['prix_regulier'], $stack['prix_special']);
						//--Decodage de l'information pour affichage
						//$stack['x']=ucfirst(str_replace("\'","'",html_entity_decode($nom_produit, ENT_COMPAT, 'UTF-8')));
						array_push($stack,ucfirst(str_replace("\'","'",html_entity_decode($nom_produit, ENT_COMPAT, 'UTF-8'))));
						//$stack['x']=ucfirst(bd_UTF8_decode($nom_produit));
						$desc2=ucfirst(str_replace("\'","'",html_entity_decode($nom_produit2, ENT_COMPAT, 'UTF-8')));
						if ($desc2=="---"||$desc2=="N/A") $desc2="";
						array_push($stack,$desc2);
						$brand=ucfirst($brand);
						if ($brand=="N/A"||$brand=="---"||$brand=="") $brand="";
						array_push($stack,str_replace("\'","'",html_entity_decode($brand, ENT_COMPAT, 'UTF-8')));
						//$stack['w']=$brand;
						$quantite=bd_UTF8_decode($quantite);
						if ($quantite=="N/A"||$quantite=="---"||$quantite==""||$quantite=="Each"||$quantite=="Chacun") $quantite="";
						//$stack['q']=$quantite;
						array_push($stack,str_replace("\'","'",html_entity_decode($quantite, ENT_COMPAT, 'UTF-8')));
						//--Image
						$image="images/getPicture.php?id_produit=$fid_produit";
						$image_big="images/getPictureBig.php?id_produit=$fid_produit";
						//$stack['i']=$image;
						//$stack['iv']=(getCorrespondence($fid_produit)!='images/NotAvailable.gif');
						array_push($stack,(getCorrespondence($fid_produit)!='images/NotAvailable.gif'));
						//$stack['ib']=$image_big;
						//$stack['ibv']=(getCorrespondenceBig($fid_produit)!='images/NotAvailable.gif');
						array_push($stack,(getCorrespondenceBig($fid_produit)!='images/NotAvailable.gif'));
						//--Valid promotion
						array_push($stack,startsWith($row['ligne_complete_es'], '*'));
		return $stack;
   }

	function bd_createProductJSON($fid_database,$flangue) {

		$fichier="product".$fid_database."_".$flangue.".json";

		// if(file_exists("$fichier")){
			// return (file_get_contents($fichier));
		// }

		//connection_db();
			//-- All product
			// $sqlq="SELECT id_produit FROM Produit WHERE status=1 AND id_database=$fid_database" ;
			//--special
      // $sqlq="SELECT id_produit FROM Produit WHERE status=1 AND prix_regulier!=prix_special AND prix_special>0.01 AND id_database=$fid_database" ;

			$sqlq="SELECT id_produit FROM Produit WHERE status=1 AND prix_special>0.01 AND id_database=$fid_database" ;
      $res   = mysql_query($sqlq);
			if (!$res) return;
			$tmp=array();
			array_push($tmp, array('version'=>date('U')));
			while($row = mysql_fetch_assoc($res)) {
				//--Array pos	id in javascript	information
				// 0	i	id_produit
				// 1    c 	id_categorie -> lien vers ci (javascript) {cnf ou cne: nom categorie}
				// 2	r	prix_regulier
				// 3	s   prix_special
				// 4 	b 	% rabais
				// 5	d	date fin
				// 6	e	id_enseigne -> lien vers ei (javascript) { ei : id_enseigne, en : nom_enseigne, el : logo_enseigne, ep : prix_coefficient_enseigne}
				// 7    x	description 1
				// 8 	z   description 2
				// 9	w	brand
				// 10	q	quantite
				// 11	iv  valid image (true: false)
				// 12	ib  valid big image (true: false)
				// 13   pv  valid promotion
				array_push($tmp,bd_getProductStripped2($row['id_produit'],$flangue));
			}
		//deconnection_db();
		//--This will bug if not root
		// $fp = fopen($fichier,'w');
		// fwrite($fp,json_encode($tmp));
		// fclose($fp);
		//--Create the categorie.json
		// connection_db();
			// $sqlq="SELECT id_categorie, nom_categorie_fr, nom_categorie_en FROM Categorie;" ;
			// $res   = mysql_query($sqlq);
			// if (!$res) return;
			// $tmp=array();
			// while($row = mysql_fetch_assoc($res)) {
				// $cat_id=$row["id_categorie"];
				// $cat_fr=html_entity_decode($row["nom_categorie_fr"], ENT_COMPAT, 'UTF-8');
				// $cat_en=html_entity_decode($row["nom_categorie_en"], ENT_COMPAT, 'UTF-8');
				// $cat_cnt="";
				// // ci: id_categorie
				// // cnf: nom francais
				// // cne: nom anglais
				// // cnt: nom selon langue
				// if ($flangue=='fr') {
					// $cat_cnt=$cat_fr;
				// } else {
					// $cat_cnt=$cat_en;
				// }
				// array_push($tmp,array('ci'=>$cat_id,'cnf'=>$cat_fr,'cne'=>$cat_en));
			// }
		// deconnection_db();
		// $fp = fopen("/tmp/categorie.json",'w');
		// fwrite($fp,json_encode($tmp));
		// fclose($fp);
			// //--Create the enseigne.json
		// connection_db();
			// $sqlq="SELECT id_enseigne,nom_enseigne,fichier_logo,prix_coefficient FROM Enseigne;" ;
			// $res   = mysql_query($sqlq);
			// if (!$res) return;
			// $tmp=array();
			// while($row = mysql_fetch_assoc($res)) {
				// $ens_id=$row["id_enseigne"];
				// $ens_nom=html_entity_decode($row["nom_enseigne"], ENT_COMPAT, 'UTF-8');
				// $ens_logo=$row["fichier_logo"];
				// $ens_pc=$row["prix_coefficient"];
				// // ei : id_enseigne
				// // en : nom_enseigne
				// // el : logo_enseigne
				// // ep : prix_coefficient_enseigne
				// array_push($tmp,array('ei'=>$ens_id,'en'=>$ens_nom,'el'=>$ens_logo, 'ep'=>$ens_pc));
			// }
		// deconnection_db();
		// $fp = fopen("/tmp/enseigne.json",'w');
		// fwrite($fp,json_encode($tmp));
		// fclose($fp);

		//return file_get_contents($fichier); //--bug?
		return json_encode($tmp);
	}

   //--New october 2013 - get all the product not active in produits...
	function bd_createProductJSON_old($fid_database,$flangue) {

		$fichier="product_old".$fid_database."_".$flangue.".json";

			$sqlq="SELECT id_produit FROM Produit WHERE status!=1 AND prix_regulier!=prix_special AND prix_special>0.01 AND id_database=$fid_database" ;
			$res   = mysql_query($sqlq);
			if (!$res) return;
			$tmp=array();
			//--Add the version
			array_push($tmp, array('version'=>date('U')));
			while($row = mysql_fetch_assoc($res)) {
				array_push($tmp,bd_getProductStripped2($row['id_produit'],$flangue));
			}

		return json_encode($tmp);
	}

?>
