<?php
	/////////////////////////////////////////////////////////
	//
	// Presentation des code postaux, distance, etc.
    // sur le serveur distant (non le front end)
	// premi�re page: Smartshopping
	//
	// get_new_codepostal.php?adresse=J3B6N7&lg=fr
	// Author: Nadia Tahiri, Etienne Lord et Alix Boc
	// Since: 12 Septembre 2013
	@session_start();
	include_once ("bd.php");
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header('content-type: application/json; charset=utf-8');
	connection_db();

//////////////////////////////////////////////////////////////////////////////////
	/// FUNCTION ORIGINALY IN BD

	function file_get_contents_utf8($fn) {
     $content = file_get_contents($fn);
      return mb_convert_encoding($content, 'UTF-8',
          mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
	}

	function n_lectureDistanceGoogle($mode, $geo1, $geo2){
	$query 	= "SELECT $mode FROM Distance_Google WHERE geo1='$geo1' AND geo2='$geo2'";
	#$string_html .="<br>$query";
	$res 	= mysql_query($query);
	$dist 	= -1;
	if( mysql_num_rows($res) > 0){
		$dist = mysql_result($res,0, "$mode");
		if($dist == -1){
			$query = "UPDATE Distance_Google SET priorite=3 WHERE geo1=$geo1 AND geo2=$geo2";
			mysql_query($query);
		}
	}
	return $dist;
	}

	function n_lectureDistances($mode,$geo1,$geo2,$lat1, $lng1, $lat2, $lng2){

			return distance($lat1, $lng1, $lat2, $lng2);

			if($cpt_nb_distance_a_calculees > 0)
				return distanceRoutiereMoyenne($geo1,$geo2,$lat1, $lng1, $lat2, $lng2);
			if($geo1 == $geo2)
				return 0;

			$geo1  = str_replace(" ","",strtolower($geo1));
			$geo2  = str_replace(" ","",strtolower($geo2));

			$query 	= "SELECT $mode FROM Distance_Google WHERE geo1='$geo1' AND geo2='$geo2'";
		//	$string_html .="<br>$mode entre $geo1 et $geo2 : ";
			$res 	= mysql_query($query);
			$dist 	= 0;
			if( mysql_num_rows($res) > 0){
				$dist	= mysql_result($res,0, "$mode");
			}
			if($dist == -1){
				return 1000000;
				$dist = distance($lat1, $lng1, $lat2, $lng2);
			}
		//	$string_html .=$dist;
			return $dist;
		}

	function n_getLatLong($id_franchise){
		 if(is_numeric($id_franchise)){
         	$query = "SELECT latitude,longitude FROM Franchise WHERE id_franchise=$id_franchise";
            $res   = mysql_query($query);
            if(mysql_num_rows($res) > 0)
            	return array(mysql_result($res,0,'la'),mysql_result($res,0,'lo'));
			return array(100000000,100000000);
       	}
		else {
        	$query = "SELECT latitude,longitude FROM Poste_Canada WHERE codePostal='$id_franchise'";
        	$res   = mysql_query($query);
        	if(mysql_num_rows($res) > 0)
        		return array(mysql_result($res,0,'la'),mysql_result($res,0,'lo'));
        	return array(200000000,200000000);
   		}
	}


	function n_distance($lat1, $lng1, $lat2, $lng2){
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

	function n_getDistanceGroup_NearestFranchises($group, $flat, $flong) {
		$dist=0;
		$grp_lat=0;
		$grp_long=0;
		$grp_count=0;
		//--Get the center of the group
		foreach($group as $nom_enseigne=>$data) {
			$grp_lat+=$data['la'];
			$grp_long+=$data['lo'];
			$grp_count++;
		}
		if ($grp_count==0) return $dist;
		$grp_lat=$grp_lat/$grp_count;
		$grp_long=$grp_long/$grp_count;
		$dist=distance($flat,$flong,$grp_lat,$grp_long);
		return $dist;
	}

	/////////////////////////////////////////////////////////
	/// FUNCTION WITH CodePostal


	function n_getCodePostalData($codePostal) {
    	if ($codePostal == "") return array();
		//--be sure of the format
		$codePostal = strtolower($codePostal);
		$codePostal = trim($codePostal);
		$codePostal = str_replace(" ","",$codePostal);

		$longitude  = "";
		$latitude   = "";
		$id_database= "";
		$provinceCP = "";
		$villeCP    = "";
		$afficheFR  = "";
		$afficheEN  = "";
		$distanceCP = "";
		$valide     = 0;
		
/*		///////////////////////////////////////
		$myfile = fopen("tmp.trace", "w");
		fwrite($myfile, "$codePostal\n");
		///////////////////////////////////////
*/
		#= Recherche du fichier de donnees

			$query = "SELECT * FROM Code_Postal WHERE codePostal='$codePostal'";
			//echo $query;
			$res = execute_query_db($query);
			if (mysql_num_rows($res) > 0) {
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
				//file_put_contents($fichier,"$longitude<>$latitude<>$id_database<>$provinceCP<>$villeCP<>$afficheFR<>$afficheEN<>$distanceCP<>$valide");
			}
			else {
/*				#= recherche dans le fichier
				$root = substr($codePostal,0,3);
				$fichierCP = "codePostal/$root.cp";

				///////////////////////////////////////
				fwrite($myfile, "$fichierCP\n");
				///////////////////////////////////////

				if(file_exists("$fichierCP")) {

					$fp = fopen("$fichierCP","r");
					while (!feof($fp)) { //on parcourt toutes les lignes
            			$line = fgets($fp); // lecture du contenu de la ligne
            			$zip  = explode (",",$line);
            			$cp = strtolower($zip[0]);
						if ($cp == $codePostal) {
							//== est ce que l'information est dans la table Poste_Canada ?
                            $query = "SELECT * FROM Poste_Canada WHERE codePostal='$codePostal'";
                            $res   = mysql_query($query);
							if (!$res) {
								//error
							}
							else if (mysql_num_rows($res) > 0) {
                                $ville     = mysql_result($res,0,"ville");
                                $province  = mysql_result($res,0,"province");
                                $latitude  = mysql_result($res,0,'latitude');
                                $longitude = mysql_result($res,0,'longitude');
                                $valide  = 1;
                            }
                            else {
								$latitude = $zip[1];
								$longitude = $zip[2];
								$ville = $zip[3];
		                        $province = str_replace("\n", "", $zip[4]);
		                                                   
								$query = "INSERT INTO Poste_Canada VALUES('$codePostal',$latitude,$longitude,'$ville','$province',$valide,$valide,1)";
                                mysql_query($query);                  
							}
							break;
        				}
        				else {
		    				$json = file_get_contents("http://www.mapquestapi.com/geocoding/v1/address?key=sKW34DJVJ9pPauK28fJiAAoOckDc59CD&location=$codePostal");
							$output = json_decode($json);
							$latitude    = $output->results[0]->locations[0]->latLng->lat;
							$longitude    = $output->results[0]->locations[0]->latLng->lng;
							$ville = $output->results[0]->locations[0]->adminArea5;
							$province = $output->results[0]->locations[0]->adminArea3;
					
							$query = "INSERT INTO Poste_Canada VALUES('$codePostal',$latitude,$longitude,'$ville','$province',$valide,$valide,1)";
				            mysql_query($query);
        				}
					}
				} */
//				else {
					$json = file_get_contents("http://www.mapquestapi.com/geocoding/v1/address?key=sKW34DJVJ9pPauK28fJiAAoOckDc59CD&location=$codePostal");
					$output = json_decode($json);
					$latitude = $output->results[0]->locations[0]->latLng->lat;
					$longitude = $output->results[0]->locations[0]->latLng->lng;
					$ville = $output->results[0]->locations[0]->adminArea5;
					$province = $output->results[0]->locations[0]->adminArea3;
					
					$query = "SELECT * FROM Poste_Canada WHERE codePostal='$codePostal'";
                    $res = mysql_query($query);
                    if (mysql_num_rows($res) == 0) {
						$query = "INSERT INTO Poste_Canada VALUES('$codePostal',$latitude,$longitude,'$ville','$province',$valide,$valide,1)";
		                mysql_query($query);
                    }
                    else {
                        $query = "UPDATE Poste_Canada SET geocode=$valide, latitude=$latitude, longitude=$longitude, google=$valide WHERE codePostal='$codePostal'";
					   	mysql_query($query);
                    }
//				}
				
/*				///////////////////////////////////////
				fwrite($myfile, "$codePostal\n");
				fwrite($myfile, "$latitude\n");
				fwrite($myfile, "$longitude\n");
				fwrite($myfile, "$ville\n");
				fwrite($myfile, "$province\n");
				///////////////////////////////////////
*/				
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

    				$dist = n_distance($latitude,$longitude,$latitude1,$longitude1);

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

					//== ajouter par alix le 9 f�vrier 2013
					// -> Changement Etienne Query d'insertion apr�s car on veut les donn�es de province, etc...
					//== pour generer le fichier de distances en meme temps
					//n_getDistancesWithFranchises($codePostal,$id,$latitude,$longitude);
					$id_database= $id_database;
					$provinceCP = $province;
					$villeCP    = $ville;
					$distanceCP = $dist;
					//--Voir ci-haut
					$query = "INSERT INTO Code_Postal VALUES ('$codePostal',$valide,'$longitude','$latitude',$id,'$province','$ville','$afficheFR','$afficheEN',$dist)";
					//file_put_contents($fichier,"$longitude<>$latitude<>$id<>$province<>$ville<>$afficheFR<>$afficheEN<>$dist<>$valide");
					execute_query_db($query);
				}
			}
			
/*		///////////////////////////////////////	
		fclose($myfile);
		///////////////////////////////////////
*/		
		return array($longitude,$latitude,$id_database,$provinceCP,$villeCP,$afficheFR,$afficheEN,$distanceCP,$valide);
		#= Recherche de l'information dans la bd
    }

	// function n_getDistancesWithFranchises($codePostal,$id_database,$latitude,$longitude){

    	// $tableau_distances = array();
    	// $codePostal = strtolower($codePostal);
		// //$fichier_distances = "/tmp/$codePostal.distances";

	// //	echo "<br>fichier = $fichier_distances";

			// $query = "SELECT * FROM Franchise WHERE id_database=$id_database";
	// //		echo "<br>$query";
			// $res = mysql_query($query);
            // $nb = mysql_numrows($res);

            // $distance_to_enseignes = array();

            // for($i=0;$i<mysql_numrows($res);$i++){
                // $latitudeH     = mysql_result($res,$i,'la');
                // $longitudeH    = mysql_result($res,$i,'lo');
                // $id_enseigne   = mysql_result($res,$i,"id_enseigne");
                // //$nom_franchise = mysql_result($res,$i,"nom_franchise");

                // $distance_calculee = n_distance($latitudeH,$longitudeH,$latitude,$longitude);

                // if (isset($distance_to_enseignes[$id_enseigne])){
                    // if($distance_to_enseignes[$id_enseigne] > $distance_calculee){
                        // $distance_to_enseignes[$id_enseigne] = $distance_calculee;
                    // }
                // }
                // else{
                    // $distance_to_enseignes[$id_enseigne] = $distance_calculee;
                // }
            // }

            // $output_distance = "$codePostal\n";
            // foreach($distance_to_enseignes as $cle => $element){
                // $value=number_format($element,1);
                // //$output_distance .=  "$cle $value\n";
                // $sql="INSERT INTO Distance_Code_Postal(codePostal, id_enseigne, distance) VALUES ('$codePostal',$cle,$element);";
                // //echo "<br>$sql";
				// mysql_query($sql);
            // }
            // //file_put_contents($fichier_distances,$output_distance);


		// $fichier_content = file($fichier_distances);
       	// for ($i=1; $i<sizeof($fichier_content); $i++){
        	// $tmp_array = explode(" ",$fichier_content[$i]);
           	// $tableau_distances[intval($tmp_array[0])] = floatval($tmp_array[1]);
        // }
		// return $tableau_distances;
	// }

	function n_distanceGoogle($geo1,$geo2,$lat1, $lng1, $lat2, $lng2) {

		$arrayTypesDistances = array ("distance_vol_oiseau","distance","distance_temps","consommation");
		$tmp=array();
		$arrayTypesDistances = array ("distance_vol_oiseau","distance","distance_temps","consommation");
		$tmp['v']=1000; // � vol d�oiseau
		$tmp['r']=1000; //� distance routi�re (5 meilleures)
		$tmp['d']=1000; //� distance dur�e (5 meilleures)
		$tmp['c']=1000; //� distance consommation (5 meilleures)
		if($geo1 == $geo2){
			return $tmp;
		} else {
				$geo1  = str_replace(" ","",strtolower($geo1));
				$geo2  = str_replace(" ","",strtolower($geo2));
				$km1=array();
				$km2=array();
				$km1['v']    = -1;
				$km1['r']    = -1;
				$km1['d']    = -1;
				$km1['c']    = -1;
				$km2['v']    = -1;
				$km2['r']    = -1;
				$km2['d']    = -1;
				$km2['c']    = -1;

					$dist_default=n_distance($lat1, $lng1, $lat2, $lng2);
					$query1 = "SELECT * FROM Distance_Google WHERE geo1='$geo1' AND geo2='$geo2'";
					$res1   = mysql_query($query1);
					if (mysql_num_rows($res1)==1) {
						$km1['v'] = mysql_result($res1,0,"distance_vol_oiseau");
						$km1['r'] = mysql_result($res1,0,"distance")/1000.0;
						$km1['d'] = mysql_result($res1,0,"distance_temps");
						$km1['c'] = mysql_result($res1,0,"consommation");
					} else {
						$queryi = "INSERT INTO Distance_Google VALUES('$geo1','$geo2',-1,-1,$dist_default,-1,1)";
						mysql_query($queryi);
					}

					//= distance routiere dans l'autre sens
					$query2 = "SELECT * FROM Distance_Google WHERE geo1='$geo2' AND geo2='$geo1'";
					$res2   = mysql_query($query2);
					if (mysql_num_rows($res2)==1) {
						$km2['v'] = mysql_result($res2,0,"distance_vol_oiseau");
						$km2['r'] = mysql_result($res2,0,"distance")/1000.0;
						$km2['d'] = mysql_result($res2,0,"distance_temps");
						$km2['c'] = mysql_result($res2,0,"consommation");
					} else {
						$queryi = "INSERT INTO Distance_Google VALUES('$geo2','$geo1',-1,-1,$dist_default,-1,1)";
						mysql_query($queryi);
					}

					//==calcul des distance

					if ( ($km1['v'] >= 0) && ($km2['v'] >= 0) ) $tmp['v']=($km1['v']+$km2['v'])/2.0;
					if ( ($km1['r'] >= 0) && ($km2['r'] >= 0) ) $tmp['r']=($km1['r']+$km2['r'])/2.0;
					if ( ($km1['d'] >= 0) && ($km2['d'] >= 0) ) $tmp['d']=($km1['d']+$km2['d'])/2.0;
					if ( ($km1['c'] >= 0) && ($km2['c'] >= 0) ) $tmp['c']=($km1['c']+$km2['c'])/2.0;
					if ($km2['v']<0) $tmp['v'] = $km1['v'];
					if ($km1['v']<0) $tmp['v'] = $km2['v'];
					if ($km2['r']<0) $tmp['r'] = $km1['r'];
					if ($km1['r']<0) $tmp['r'] = $km2['r'];
					if ($km2['d']<0) $tmp['d'] = $km1['d'];
					if ($km1['d']<0) $tmp['d'] = $km2['d'];
					if ($km2['c']<0) $tmp['c'] = $km1['c'];
					if ($km1['c']<0) $tmp['c'] = $km2['c'];
					if (($km1['v'] < 0) && ($km2['v'] < 0) ) $tmp['v']= $dist_default;
					if (($km1['r'] < 0) && ($km2['r'] < 0) ) $tmp['r']= $dist_default;
					if (($km1['d'] < 0) && ($km2['d'] < 0) ) $tmp['d']= $dist_default;
					if (($km1['c'] < 0) && ($km2['c'] < 0) ) $tmp['c']= $dist_default;

		}
		return $tmp;
	}


	function n_distanceRoutiereMoyenne($geo1,$geo2,$lat1, $lng1, $lat2, $lng2)
	{
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
			} else {
				$dist=n_distance($lat2, $lng2, $lat1, $lng1);
				$queryi = "INSERT INTO Distance_Google VALUES('$geo1','$geo2',-1,-1,$dist,-1,1)";
				mysql_query($queryi);
			}
			//= distance routiere dans l'autre sens
			$query2 = "SELECT distance FROM Distance_Google WHERE geo1='$geo2' AND geo2='$geo1'";
		   	$res2   = mysql_query($query2);
			$km2    = -1;
			if(mysql_num_rows($res2) == 1){
				$km2 = mysql_result($res2,0,"distance");
				$km2 = $km2/1000.0;
			} else {
				$dist=n_distance($lat2, $lng2, $lat1, $lng1);
				$queryi = "INSERT INTO Distance_Google VALUES('$geo2','$geo1',-1,-1,$dist,-1,1)";
				mysql_query($queryi);
			}
			if ( ($km1 >= 0) && ($km2 >= 0) ){
				return ($km1+$km2)/2.0;
			}
			elseif( ($km1 < 0) && ($km2 < 0) ){
				return n_distance($lat1, $lng1, $lat2, $lng2);
			}
			elseif($km2 < 0){
				return $km1;
			}
			elseif($km1 < 0){
				return $km2;
			}
			else{
				return 1000;
			}
		}
	}

	//--!0 franchises les plus proches
	//--Really not efficient!
	function n_rechercheFranchisesProches($codePostal){

		// //-- Old file $fichier = "/tmp/$codePostal.json";
			// $arrayTypesDistances = array ("distance_vol_oiseau","distance","distance_temps","consommation");

			// //= selection en fonction de la distance � vol d'oiseaux
			// $query   = "SELECT id_enseigne,nom_enseigne,fichier_logo, prix_coefficient FROM Enseigne";
			// $res_ens = mysql_query($query);
			// $num_ens = mysql_num_rows($res_ens);

			// $json = array();

				// //$tableDistances[$f_idFranchise[$i]][$adr] = $distanceH;
				// //file_put_contents ($dir.'/dist'.$_SESSION['id_panier'].'.test', $distanceH." ", FILE_APPEND | LOCK_EX);
				// //for($j = 0; $j <sizeof($f_longitudeE); $j++){
				// //	$distanceH=lectureDistances($mode_distance,$f_idFranchise[$i],$f_idFranchise[$j],$f_latitudeE[$i], $f_longitudeE[$i], $f_latitudeE[$j], $f_longitudeE[$j]);
				// //}
			// for($k=0;$k<count($arrayTypesDistances);$k++){

				// $typeDistance = $arrayTypesDistances[$k];

				// for($j=0;$j<$num_ens;$j++){
            		// $id_enseigne  = mysql_result($res_ens, $j, "id_enseigne");
					// $enseigne     = mysql_result($res_ens, $j, "nom_enseigne");
            		// $img          = mysql_result($res_ens, $j, "fichier_logo");
					// $ens_pc  	  = mysql_result($res_ens, $j, "prix_coefficient");
					// $query = "SELECT * FROM Distance_Google,Franchise WHERE geo2=id_franchise AND id_enseigne=$id_enseigne AND geo1 = '$codePostal' AND distance_vol_oiseau != -1  ORDER BY ABS($typeDistance) LIMIT 10";
					// $res = mysql_query($query);
					// $num   = mysql_num_rows($res);
					// for($i=0;$i<$num;$i++){
						// $id_franchise = mysql_result($res, $i, "id_franchise");
            			// $id_enseigne  = mysql_result($res, $i, "id_enseigne");
            			// $adresse      = mysql_result($res, $i, "adresse");
            			// $longitude    = mysql_result($res, $i, 'lo');
            			// $latitude     = mysql_result($res, $i, 'la');
						// $distance	  = mysql_result($res, $i, "$typeDistance");
						// $json["$typeDistance"]["$enseigne"][$i]['id_franchise'] = $id_franchise;
						// $json["$typeDistance"]["$enseigne"][$i]['id_enseigne'] 	= $id_enseigne;
						// $json["$typeDistance"]["$enseigne"][$i]['adresse'] 	 	= $adresse;
						// $json["$typeDistance"]["$enseigne"][$i]['lo'] 	= $longitude;
						// $json["$typeDistance"]["$enseigne"][$i]['la'] 	= $latitude;
						// $json["$typeDistance"]["$enseigne"][$i]['image'] 		 	= $img;
						// $json["$typeDistance"]["$enseigne"][$i]['ep'] 		 	= $ens_pc ;
						// $json["$typeDistance"]["$enseigne"][$i]['distance'] 	= $distance;
					// }
				// }
			// }


		return $json;
		//return file_get_contents($fichier);
		//var_dump ( "\n\n" . $json);
	}

	//========================================================================================================================
	//== Cette fonction permet de mettre dans un fichier <codePostal>.franchise la liste des 5 franchises de chaque enseigne
	//== les plus proches. Il rajoute dans la base de donnees les distances entre les franchises/franchises et
	//== les franchises/codePostaux a calculer
	//== auteur : Alix Boc
	//== date   : 1er F�vrier 2013
	//========================================================================================================================
	function n_getNearestFranchises($codePostal, $longitude, $latitude, $id_database){
		//error_reporting(E_ALL);
		//$fichier = "/tmp/$codePostal.franchises";
		//$fichier_json="/tmp/$codePostal.franchises.json"
		$tab_adresse_franchise = array();

		//== Recherche de toutes les franchises
		//=========================================================
		//[matrice_distance][v] � vol d�oiseau (5 meilleures)
		//[matrice_distance][r] � distance routi�re (5 meilleures)
		//[matrice_distance][d] � distance dur�e (5 meilleures)
		//[matrice_distance][c] � distance consommation (5 meilleures)


		$tab_franchises = array();
		$json_franchises= array();
		$json_franchises_tmp= array();
		$json_franchises_distance_vol= array();  //v
		$json_franchises_distance_rout=array();  //r
		$json_franchises_distance_duree=array(); //d
		$json_franchises_distance_cons=array();  //c
		//$nLat = 1.4;
		//$nLong = 2;
		//= limitation des franchises dans la boite ...
		$query = "SELECT id_franchise,Franchise.id_enseigne, nom_enseigne,adresse,longitude,latitude,fichier_logo, prix_coefficient FROM Franchise,Enseigne WHERE Franchise.id_enseigne=Enseigne.id_enseigne AND id_database=$id_database AND latitude<($latitude+1.4) AND latitude>($latitude-1.4) AND longitude>($longitude-2) AND longitude<($longitude+2)";
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
        	$longitude2	  = mysql_result($res, $i, 'longitude');
        	$latitude2	  = mysql_result($res, $i, 'latitude');
			$ens_pc  	  = mysql_result($res, $i, "prix_coefficient");
        	$img		  = mysql_result($res, $i, "fichier_logo");
			//$dist 		  = n_distanceRoutiereMoyenne($codePostal,$id_franchise,$latitude,$longitude,$latitude2,$longitude2);
			$dist = 		n_distanceGoogle($codePostal,$id_franchise,$latitude,$longitude,$latitude2,$longitude2);
			//$dist=$test['v'];

			//$dist_vol     = n_distance($latitude,$longitude,$latitude2,$longitude2);

			//$tmp_array    = array('id_franchise'=>$id_franchise, 'id_enseigne'=>$id_enseigne, 'nom_enseigne'=>$enseigne,'adresse'=>$adresse, 'lo'=>$longitude2,'la'=>$latitude2, 'image'=>$img, 'distance_routiere'=>$dist['r'], 'distance'=>$dist['v'], 'distance_duree'=>$dist['d'], 'distance_cons'=>$dist['c']);
			$tmp_array    = array('fi'=>$id_franchise, 'ei'=>$id_enseigne, 'en'=>$enseigne,'ep'=>$ens_pc,'a'=>$adresse, 'lo'=>$longitude2,'la'=>$latitude2, 'en'=>$img, 'r'=>$dist['r'], 'v'=>$dist['v'], 'd'=>$dist['d'], 'c'=>$dist['c']);
			array_push($json_franchises, $tmp_array);
			array_push($json_franchises_tmp, $tmp_array);
			array_push($json_franchises_distance_vol, $dist['v']); //==for sorting by distance vol...
			array_push($json_franchises_distance_rout, $dist['r']); //==for sorting by distance routier...
			array_push($json_franchises_distance_cons, $dist['c']); //==for sorting by distance consommation...
			array_push($json_franchises_distance_duree, $dist['d']); //==for sorting by distance duree...
			$i++;
		}

		$array_result=array();

		array_multisort($json_franchises_distance_rout,SORT_ASC,$json_franchises);
		$array_result['enseigne_rout']=n_extractByEnseigne($json_franchises_tmp);
		$json_franchises=$json_franchises_tmp;
		array_multisort($json_franchises_distance_cons,SORT_ASC,$json_franchises);
		n_extractByEnseigne($json_franchises_tmp);
		$array_result['enseigne_cons']=n_extractByEnseigne($json_franchises_tmp);
		$json_franchises=$json_franchises_tmp;
		array_multisort($json_franchises_distance_duree,SORT_ASC,$json_franchises);
		$array_result['enseigne_duree']=n_extractByEnseigne($json_franchises_tmp);
		$json_franchises=$json_franchises_tmp;
		//== Order JSON distance lass
		array_multisort($json_franchises_distance_vol,SORT_ASC,$json_franchises);
		$array_result['enseigne_vol']=n_extractByEnseigne($json_franchises_tmp);
		$array_result['franchise']=$json_franchises;
		return $array_result;
	}

	//--Get an array of the 10 best enseigne by distance
	function n_extractByEnseigne($data) {
		$enseignes=array();
		foreach ($data as $franchise) {
			$id_enseigne=$franchise['ei'];

			if (!isset($enseignes[$id_enseigne])) {
				$enseignes[$id_enseigne]=array();
			}
			if (count($enseignes[$id_enseigne])<5) {
				array_push($enseignes[$id_enseigne], $franchise);
			}
		}
		return n_linear_array($enseignes);
	}

	//--This take the array in the form
	//
	// array[id_enseigne][data] and create...
	// array [data1][data2]...
	function n_linear_array($data) {
		$tmp=array();
		foreach ($data as $k=>$v) {
			foreach ($v as $k2 => $v2)
				array_push($tmp, $v2);
		}
		return $tmp;
	}

	function n_getNearestFranchisesJSON_limit($data, $perimetre, $max_franchise, $distance_limite) {

		// old $fichier_json="/tmp/$codePostal"."_".$perimetre.".franchises.json";
		$stack=array();
		// 1. Get the total array of franchise
		$franchise= $data;

		// 1.5 Test if we have any franchise

		if (count($franchise)==0) {
			//--Create empty array
			$stack['perimetre']=$perimetre;
			$stack['recherche_perimetre']=$perimetre;
			$stack['franchise']=array();
			$stack['other_franchise']=array();
			$stack['groups']=array();
			$stack['franchise_not_in_group']=array();
			return json_encode($stack);
		}
		// 2. Find the one we want to display
				$i=0;
				$count=0;
				$displayed=array();
				$other_franchise=array();
				$displayed_list=array();
				$perimetre_old=$perimetre;
				$row['r']=0;
				$n_min=2; //--Minimum number of stores to find
				$max_radius=250; //--Max radius to search in km
				//--First while is to ensure that we have at least n_min store in the radius
				while (count($displayed_list)<$n_min&&$perimetre<$max_radius) {
					 while($row['r']<$perimetre&&$count<$max_franchise&&$i<count($franchise))
					{
						$row = $franchise[$i++];
						//echo $row['nom_enseigne'];
						if (!isset($displayed[$row['en']])) {
							if ($row['r']<$perimetre) {
								$displayed[$row['en']]=true;
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
					if ($data['r']<$perimetre) {
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
							if (n_distance($displayed_list[$i]['la'],$displayed_list[$i]['lo'],$displayed_list[$j]['la'],$displayed_list[$j]['lo'])<$distance_limite) {
								//1. Create group if not exists
								$already_added=false;
								for ($k=0; $k<$groups_count;$k++) {
									//--Try to find in a group and add
										$dgroup=$groups[$k];
										if (isset($dgroup[$displayed_list[$i]['en']])||isset($dgroup[$displayed_list[$j]['en']])) {
											if (!$already_added) {
												$dgroup[$displayed_list[$j]['en']]=$displayed_list[$j];
												$already_added=true;
												$groups[$k]=$dgroup;
											}
										}
								}
								if (!$already_added) {
									$new_group=array();
									$new_group[$displayed_list[$i]['en']]=$displayed_list[$i];
									$new_group[$displayed_list[$j]['en']]=$displayed_list[$j];
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

										$dist_i=n_getDistanceGroup_NearestFranchises($tmp_group_i, $data_j['la'], $data_j['lo']);
										$dist_j=n_getDistanceGroup_NearestFranchises($tmp_group_j, $data_j['la'], $data_j['lo']);
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
						array_push($tmp_latitude_array, (float)$data['la']);
						array_push($tmp_longitude_array, (float)$data['lo']);
						$tmp_longitude+=$data['lo'];
						$tmp_latitude+=$data['la'];
					}
					array_multisort($tmp_latitude_array,SORT_ASC, $tmp_group);
					if (sizeof($tmp_latitude_array)>0) {
						$tmp_group['la']=$tmp_latitude/sizeof($tmp_latitude_array);
						$tmp_group['lo']=$tmp_longitude/sizeof($tmp_latitude_array);
					} else {
						$tmp_group['la']=$tmp_latitude;
						$tmp_group['lo']=$tmp_latitude;
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
						if (isset($franchise['en'])) {
							array_push($array_in_group,$franchise['en']);
						}
					}
				}

				foreach($displayed_list as $data) {
					if (!in_array( $data['en'] ,$array_in_group )) array_push($array_not_in_group,$data);
				}
				$stack['franchise_not_in_group']=$array_not_in_group;
				//5. Save the file
				return json_encode($stack);
	}


	//<!----------------------------------------------------------------------------------//
	// Calcul d'un panier si on est dans plusieurs magasins
	// description... (BASIC CASE ONLY)
	// Donnant: fmax_enseigne un nombre de franchise maximum ou n-1
	//          $data_json = json format smshopping2
	// Return: a json containing the ordered list of Panier price
	// Etienne Lord 2013 - July 2013 - conversion avec l'entre en JSON
	//----------------------------------------------------------------------------------->
	  function n_bd_Prix_des_Paniers2($fmax_enseigne,$data_json) {
		//--debug error_reporting(E_ALL);
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
		$datat=json_decode($data_json,TRUE);
		//--Array enseigne

		//--get the id_produit from the json
		echo variable_to_html($datat);


		$prix_special=0.0;
		foreach ($datat['panier'] as $produit) {
				//--FROM JSON: Contenu_Panier.id_produit,
					//echo variable_to_html($produit);
					$sql = "SELECT Enseigne.id_enseigne,
							       Enseigne.nom_enseigne,
								   Enseigne.Prix_Coefficient,
								   Produit.id_enseigne,
								   Produit.id_produit
								   FROM Produit
								   LEFT JOIN Enseigne ON Produit.id_enseigne = Enseigne.id_enseigne
								   WHERE Produit.id_produit= '".$produit['i']."';";
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
								$fid_enseigne2 = mysql_result($res, $i, "Produit.id_enseigne");
								$coefficient_estime = mysql_result($res, $i, "Enseigne.Prix_Coefficient");
								$coefficient_estime_nom = mysql_result($res, $i, "Enseigne.nom_enseigne");
								$array_produits[$fid_produit]=array('id_produit'=>$fid_produit, 'quantite'=>$fquantite,
												                   'prix_special'=>$fprix_special,'prix_regulier'=>$fprix_regulier,
																   'id_enseigne'=>$fid_enseigne2,'coefficient'=>$coefficient_estime);
								//--Array Enseigne
								$array_enseigne[$fid_enseigne2]=array('id_enseigne'=>$fid_enseigne2,'nom_enseigne'=>$coefficient_estime_nom,'coefficient'=>$coefficient_estime);
								$prix_special + ($fprix_special*$fquantite);
								$i=$nb2;
							}
		} //--End foreeach produit
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

			//$fp = fopen("$fichier_json",'w');
			//fwrite($fp,json_encode($array_prix_combination));
			//fclose($fp);
			//--error_reporting(0);
			return ($array_prix_combination);

		}

	//<!----------------------------------------------------------------------------------//
	// Calcul des matrice de distance entre les meilleures franchises
	//
	// Etienne Lord 2013 - Septembre 2013
	//----------------------------------------------------------------------------------->
	function n_matrice($data) {

		// $arrayTypesDistances = array ("distance_vol_oiseau","distance","distance_temps","consommation");
		 $array_dist=array();
		 $array_dist['v']=array();
		 $array_dist['r']=array();
		 $array_dist['d']=array();
		 $array_dist['c']=array();
		// //print_r($data);
		foreach ($data as $k1=>$v) {

			$geo1=$v['fi'];
			$array_dist['v'][$geo1]=array();
			$array_dist['r'][$geo1]=array();
			$array_dist['d'][$geo1]=array();
			$array_dist['c'][$geo1]=array();
			foreach ($data as $k2=>$w) {
				$geo2=$w['fi'];
				$array_dist['v'][$geo1][$geo2]=0.0;
				$array_dist['r'][$geo1][$geo2]=0.0;
				$array_dist['d'][$geo1][$geo2]=0.0;
				$array_dist['c'][$geo1][$geo2]=0.0;
				if ($v!=$w&&$v['ei']!=$w['ei']) {
						$geo1=$v['fi'];
						//echo "$geo1 $geo2";
						 $latitude=$v['la'];
						 $latitude2=$w['la'];
						 $longitude=$v['lo'];
						 $longitude2=$w['lo'];
						$dist=n_distanceGoogle($geo1,$geo2,$latitude,$longitude,$latitude2,$longitude2);
						$array_dist[$geo1][$geo2]['v']=$dist['v'];
						$array_dist[$geo1][$geo2]['r']=$dist['r'];
						$array_dist[$geo1][$geo2]['d']=$dist['d'];
						$array_dist[$geo1][$geo2]['c']=$dist['c'];
					}
			}
		}
		return $array_dist;

		// //print_r($data);

	}

	/////////////////////////////////////////////////////////
	/// MAIN FUNCTION

	// Comment by Nadia Tahiri
	$adresse='h2x3a1'; //--new code

	$distance=20; //--Take 20 km, will filter in the page...
	 if (isset($_REQUEST['adr'])) {
		$adresse=mysql_real_escape_string($_REQUEST['adr']);
	 }

	  if (isset($_REQUEST['dist'])) {
		//$distance=mysql_real_escape_string($_REQUEST['dist']);
	 }

	 //--Traitement
	 $adresse	= strtolower($adresse);
     $adresse 	= trim($adresse);
     // Documentation by Nadia Tahiri 10 April 2018
     // remove space on the postal code
     $adresse	= str_replace(" ","",$adresse);
	//$fichier_json="/search_tmp/$adresse"."_"."$distance.json";
	$fichier_json="search_tmp/$adresse.json";
	if(file_exists($fichier_json)){
			while (ob_get_status())
				{
					ob_end_clean();
				}
			header( "Location: $fichier_json");
		} else {

			//--VARIABLE
			$data_array=array();

			// [info] � Code postal, ville, longitude, latitude ->n_getCodePostalData($adresse);
			// [franchises] � 20km
			// [franchise_map] � franchises group�es selon la distance�
			// [franchise_distance] � 5 meilleures franchises par distance vol d�oiseau
			// [franchise_distance][v] � vol d�oiseau (5 meilleures)
			// [franchise_distance][r] � distance routi�re (5 meilleures)
			// [franchise_distance][d] � distance dur�e (5 meilleures)

			// $adresse 	= $_REQUEST['pc'];
			 $adresse	= strtolower($adresse);
			 $adresse 	= trim($adresse);
			 $adresse	= str_replace(" ","",$adresse);

			$data_array['info']=n_getCodePostalData($adresse);
			$data_array_tmp= n_getNearestFranchises($adresse, $data_array['info'][0], $data_array['info'][1], $data_array['info'][2]);
			$data_array['franchise']= $data_array_tmp['franchise'];
			$data_array['franchise_map']=n_getNearestFranchisesJSON_limit($data_array['franchise'], $distance,20, 1.0);
			$data_array['franchise_distance']=array();
			$data_array['franchise_distance']['v']=$data_array_tmp['enseigne_vol'];
			$data_array['franchise_distance']['d']=$data_array_tmp['enseigne_duree'];
			$data_array['franchise_distance']['c']=$data_array_tmp['enseigne_cons'];
			$data_array['franchise_distance']['r']=$data_array_tmp['enseigne_rout'];
			//print_r($data_array['enseigne']);
			//$data_array['franchise_matrice']=n_rechercheFranchisesProches($adresse);

			//$data_array['matrice_distance']=n_matrice($data_array['franchise_distance']['v']);
			//print_r($data_array);


			$fp = fopen("$fichier_json",'w');
			$tmp=json_encode($data_array);
			fwrite($fp,$tmp);
			fclose($fp);



		echo $tmp;
		system("php async_createMatrix.php $fichier_json >/dev/null 2>&1 &");
	}
	//--Should take about 1min...
	//--Async only... PUT IN A CALLBACK
	//--All distance matrice
	deconnection_db();

	//echo json_encode($data_array);

?>
