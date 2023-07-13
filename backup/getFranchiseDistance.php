<?php
//========================================================================================================================
	//== Cette fonction permet de mettre dans un fichier JSON les <$max_franchise> franchises se trouvant dans un p�rim�tre
	//== <$perimetre>. Ce json comprend en plus trois arrays: franchise, franchise_seule et group
	//== L'array [franchise] comprend la liste complete, l'array [franchise_not_in_group] celles qui ne sont pas en groupe
	//== et en [groups] celles se trouvant dans une distance <$distance_limite> l'une de l'autre
	//== auteur : Etienne Lord
	//== date   : 1er F�vrier 2013
	//== Hack Etienne Septembre 2013 -> rapidly get the list of franchises in group for the adresse
	//========================================================================================================================
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header('content-type: application/json; charset=utf-8');

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

	//--This function is used only in getNearestFranchisesJSON_limit
	function f_getDistanceGroup_NearestFranchises($group, $flat, $flong) {
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
		$dist=n_distance($flat,$flong,$grp_lat,$grp_long);
		return $dist;
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
			return $stack;
		}
		// 2. Find the one we want to display
				$i=0;
				$count=0;
				$displayed=array();
				$other_franchise=array();
				$displayed_list=array();
				$perimetre_old=$perimetre;
				$row['v']=0;
				$n_min=2; //--Minimum number of stores to find
				$max_radius=250; //--Max radius to search in km
				//--First while is to ensure that we have at least n_min store in the radius
				while (count($displayed_list)<$n_min&&$perimetre<$max_radius) {
					 while($row['v']<$perimetre&&$count<$max_franchise&&$i<count($franchise))
					{
						$row = $franchise[$i++];
						//echo $row['en'];
						if (!isset($displayed[$row['en']])) {
							if ($row['v']<$perimetre) {
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
					if ($data['v']<$perimetre) {
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

										$dist_i=f_getDistanceGroup_NearestFranchises($tmp_group_i, $data_j['la'], $data_j['lo']);
										$dist_j=f_getDistanceGroup_NearestFranchises($tmp_group_j, $data_j['la'], $data_j['lo']);
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
				echo json_encode($stack);
	}

	function file_get_contents_utf8($fn) {
     $content = file_get_contents($fn);
      return mb_convert_encoding($content, 'UTF-8',
          mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
	}

	////----Main from post

	//--Commentaire ou pas par Nadia Tahiri 15 Avril 2018
	 $adresse='H2X3Y7';
	 if (isset($_REQUEST['adr'])) {
		 $adresse=$_REQUEST['adr'];
	 }
	 #$distance=5; //5km by defaut
	  if (isset($_REQUEST['dist'])) {
		$distance=$_REQUEST['dist'];
	 }

	 //--Traitement
	 $adresse	= strtolower($adresse);
     $adresse 	= trim($adresse);
     $adresse	= str_replace(" ","",$adresse);
	//$fichier_json="/search_tmp/$adresse"."_"."$distance.json";
	$fichier_json="search_tmp/$adresse.json";
	if(file_exists($fichier_json)){

		//$data_json = file_get_contents($fichier_json);
		//echo $data_json;
		// $max_franchise=$_REQUEST['max_franchise'];
		// $distance_limite=$_REQUEST['distance_limite'];
		if ($distance>20) $distance=20;
		$data_array = json_decode(file_get_contents($fichier_json), true);
		//--Lag
		n_getNearestFranchisesJSON_limit($data_array['franchise'], $distance,50, 1.0);

	}

?>
