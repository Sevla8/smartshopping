<?php
@session_start();
include_once('bd.php');
date_default_timezone_set('America/Montreal');
@connection_db();	
			//====================================================================================
			//= ADMIN TEST
			//====================================================================================
			//--Privilege des utilisateurs (see bd.php)
			// -1: Unregistered
			//  0: Registered
			// 99: Sysop
			$user_privilege=99; 		
			if (isset($_SESSION['id_client'])&&isset($_SESSION['nom'])) {
				$user_privilege=bd_getUserPrivilege(mysql_real_escape_string($_SESSION['id_client']));		
			}	
	
	//====================================================================================
	//= UPDATE
	//====================================================================================
	 if(isset($_REQUEST['action'])){
		//--Look for privilege here
		if ($user_privilege<=1) exit(); //--Verify admin
		$operation = $_REQUEST['action'];	
		
		
		if ($operation=="upload") {
			//--Create the publicite here and add flag if created
			//echo variable_to_html($_REQUEST);
			//echo variable_to_html($_FILES);
			//--Create the publicite here 
			if (isset($_POST['id_publicite'])) { $id_publicite=addslashes($_POST['id_publicite']);}
			if (isset($_POST['start_date'])) { $start_date=addslashes($_POST['start_date']);}
			if (isset($_POST['end_date'])) { $end_date=addslashes($_POST['end_date']);}
			if (isset($_POST['url'])) { $url=addslashes($_POST['url']);}
			if (isset($_POST['codePostal'])) { $codePostal=addslashes($_POST['codePostal']);}
			if (isset($_POST['distance'])) { $distance=addslashes($_POST['distance']);}
			$codePostal = strtolower($codePostal);
			$codePostal = trim($codePostal);
			$codePostal=str_replace(" ","",$codePostal);
			$id_client=$_SESSION['id_client'];
			if ($_FILES['file_fr']['error']!=4) {
				//--TO DO SET FLAG HERE
			
					$image_fr=$id_publicite."_fr.gif";			
					 move_uploaded_file($_FILES["file_fr"]["tmp_name"],"/var/www/html/pub/".$image_fr);
					
					if ($_FILES['file_en']['error']==4) {
						$image_en=$id_publicite."_fr.gif";
					} else {
						$image_en=$id_publicite."_en.gif";
						move_uploaded_file($_FILES["file_en"]["tmp_name"],"/var/www/html/pub/".$image_en);
					}					
					
					//--CREATE OR UPDATE
					$sqlq="DELETE FROM Publicite WHERE id_publicite='$id_publicite';";
					$res=mysql_query($sqlq);
					$sqlq="INSERT INTO Publicite (id_publicite, start_date, end_date, click_count_fr, click_count_en, view_position, url, image_fr, image_en, id_client, status, codePostal, distance) VALUES 
							('$id_publicite', '$start_date', '$end_date', 0, 0, 0, '$url', '$image_fr', '$image_en', '$id_client', 1, '$codePostal', '$distance');";
					
					$res=mysql_query($sqlq);									
						if (!$res) {				
							//--TO DO SET FLAG HERE
							echo("Error ".mysql_error());
						}				
			}
			
		}
			
			if ($operation=="update") {
			echo "Updating produit...";
			echo variable_to_html($_POST);
			if (isset($_POST['i'])) { $id_produit=$_POST['i'];}
			if (isset($_POST['w'])) { $brand_fr=addslashes($_POST['w']);}
			if (isset($_POST['we'])) { $brand_en=addslashes($_POST['we']);}
			if(isset($_POST['x'])){$nom_produit_fr= addslashes($_POST['x']);};				
			if(isset($_POST['z'])){$nom_produit_fr2= addslashes($_POST['z']);};	
			if(isset($_POST['xe'])){$nom_produit_en= addslashes($_POST['xe']);};				
			if(isset($_POST['ze'])){$nom_produit_en2= addslashes($_POST['ze']);};				
			if(isset($_POST['c'])){$id_categorie= $_POST['c'];};
			if(isset($_POST['q'])){$quantite_fr = addslashes($_POST['q']);};
			if(isset($_POST['qe'])){$quantite_en = addslashes($_POST['qe']);};
			if(isset($_POST['unite'])){$unite_fr= addslashes($_POST['unite']);};
			if(isset($_POST['unite_en'])){$unite_en= addslashes($_POST['unite_en']);};
			if(isset($_POST['r'])){$prix_regulier= $_POST['r'];};
			if(isset($_POST['s'])){$prix_special= $_POST['s'];};			
			if(isset($_POST['e'])){$id_enseigne = $_POST['e'];};
			if(isset($_POST['pv'])){$ligne_complete_es = $_POST['pv'];};
			if(isset($_POST['d'])){$end_date = $_POST['d'];};
			if(isset($_POST['sd'])){$start_date = $_POST['sd'];};
			if(isset($_POST['nd'])){$normal_end_date = $_POST['nd'];};
			//--Get this product status...
			
			$sql_produit="SELECT status, date FROM Produit_scripts WHERE id_produit='$id_produit';";							
			$res=mysql_query($sql_produit);
			$status=2; //--Upcoming
			$end_date=intval($end_date)/1000;	
			//--We don't change the date
			$new_product=true;
			if ($res) {						
						while($r = mysql_fetch_assoc($res)){																				
							$status=$r['status'];	
							$end_date=$r['date'];	
							$new_product=false;							
						}						
					}	
			
			if (filter_var($ligne_complete_es, FILTER_VALIDATE_BOOLEAN)) $ligne_complete_es='*';			
			if ($brand_en=="") $brand_en=$brand;
			if ($quantite_en=="") $quantite_en=$quantite;
			
			// if (isset($_POST['status'])) {
				// $status=$_POST['status'];
				// $sql_produit="UPDATE Produit_scripts SET status=$status WHERE Produit_scripts.id_produit='$id_produit';";
				// $res=mysql_query($sql_produit);
				// //echo "update status: $res <br>";
			// }
			//--Remove old product than insert new
			$sql_produit="DELETE FROM Produit_scripts WHERE id_produit='$id_produit';";							
			$res=mysql_query($sql_produit);
			//--We need to update both table
			if ($status==1) {
				$sql_produit="DELETE FROM Produit WHERE id_produit='$id_produit';";							
				$res=mysql_query($sql_produit);
			}
			//
			$sql_produit="INSERT INTO Produit_scripts (id_produit, id_database,id_enseigne,prix_regulier, prix_special, nom_produit_fr2, nom_produit_fr,nom_produit_en, nom_produit_en2,nom_produit_es, nom_produit_es2, fichier_image, id_categorie, unite_fr, unite_en, unite_es, brand_fr, brand_en, brand_es, quantite_fr, quantite_en, quantite_es, date, start_date, entered_date, status, user_id, ligne_complete_fr, ligne_complete_en, ligne_complete_es) VALUES ('$id_produit',1,'$id_enseigne','$prix_regulier','$prix_special','$nom_produit_fr2','$nom_produit_fr','$nom_produit_en','$nom_produit_en2','','','','$id_categorie','$unite_fr','$unite_en','','$brand_fr','$brand_en','','$quantite_fr','$quantite_en','','$normal_end_date','$start_date',Now(),$status,'".$_SESSION['id_client']."','','','$ligne_complete_es');";							
			//$sql_produit="UPDATE Produit_scripts SET id_categorie='$id_categorie', nom_produit_fr='$nom_produit_fr',nom_produit_fr2='$nom_produit_fr2', nom_produit_en='$nom_produit_en',nom_produit_en2='$nom_produit_en2', unite_fr='$unite',unite_en='$unite_en', brand_fr='$brand',brand_en='$brand_en',brand_es='$brand', quantite_fr='$quantite',quantite_en='$quantite_en', prix_regulier='$prix_regulier', prix_special='$prix_special', id_enseigne='$id_enseigne', FD_ID='$FD_ID' WHERE Produit_scripts.id_produit='$id_produit';";							
			echo $sql_produit;			
			$res=mysql_query($sql_produit);
			
			if ($status==1) {
				$sql_produit="INSERT INTO Produit (id_produit, id_database,id_enseigne,prix_regulier, prix_special, nom_produit_fr2, nom_produit_fr,nom_produit_en, nom_produit_en2,nom_produit_es, nom_produit_es2, fichier_image, id_categorie, unite_fr, unite_en, unite_es, brand_fr, brand_en, brand_es, quantite_fr, quantite_en, quantite_es, date, start_date, entered_date, status, user_id, ligne_complete_fr, ligne_complete_en, ligne_complete_es) VALUES ('$id_produit',1,'$id_enseigne','$prix_regulier','$prix_special','$nom_produit_fr2','$nom_produit_fr','$nom_produit_en','$nom_produit_en2','','','','$id_categorie','$unite_fr','$unite_en','','$brand_fr','$brand_en','','$quantite_fr','$quantite_en','','$normal_end_date','$start_date',Now(),$status,'".$_SESSION['id_client']."','','','$ligne_complete_es');";							
				//$sql_produit="UPDATE Produit_scripts SET id_categorie='$id_categorie', nom_produit_fr='$nom_produit_fr',nom_produit_fr2='$nom_produit_fr2', nom_produit_en='$nom_produit_en',nom_produit_en2='$nom_produit_en2', unite_fr='$unite',unite_en='$unite_en', brand_fr='$brand',brand_en='$brand_en',brand_es='$brand', quantite_fr='$quantite',quantite_en='$quantite_en', prix_regulier='$prix_regulier', prix_special='$prix_special', id_enseigne='$id_enseigne', FD_ID='$FD_ID' WHERE Produit_scripts.id_produit='$id_produit';";							
				echo $sql_produit;			
				$res=mysql_query($sql_produit);
			}
			
			//echo "update produit: $res <br>";
			if (!$res) echo("Error ".mysql_error());
			exit();
		}
			
	}		

	
	
?>

<?php 
	//--Load include
	
?>	
<!doctype html>
<html lang="en" xmlns:ng="http://angularjs.org" id="ng:app" ng-app="myApp">
	<HEAD>
		<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
		<META HTTP-EQUIV="Expires" CONTENT="-1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<meta name="viewport" content="height=device-height, initial-scale=1.0"/>
		
    	<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />

			<link rel="shortcut icon" href="data/favicon.ico" />  
		<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.2/css/font-awesome.css" rel="stylesheet">
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.css" />
		<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
				
		 <link href="css/font-awesome/css/font-awesome.min.css" rel="stylesheet">
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.0.1-p7/css/bootstrap.min.css">
		<!-- Optional theme -->
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.0.1-p7/css/bootstrap-theme.min.css">
		<!-- Latest compiled and minified JavaScript -->
		<script src="//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.0.1-p7/js/bootstrap.min.js"></script>		
	
		<script src="js/jqm/jquery.mobile-1.3.1.min.js"></script>				
		<script type="text/javascript" src="js/angular.min.js"></script>
		<script type="text/javascript" src="js/localStorageModule.js"></script>    
			
		<link rel="stylesheet" href="js/jqm/jqm-datebox.min.css" />
		<script type="text/javascript"  src="js/jqm/jqm-datebox.core.min.js"></script>
		<script type="text/javascript"  src="js/jqm/jqm-datebox.mode.calbox.min.js"></script>
		
	<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>		
		<!-- load angular -->
		<script type="text/javascript">
						
					//////////////////////////////////////////////////////////
					/// MAIN APPLICATION
					
					var parsedUrl = $.mobile.path.parseUrl( location.href );
					
					if (parsedUrl.hrefNoHash!=parsedUrl.href) {
						var newurl=parsedUrl.href.substring(parsedUrl.hrefNoHash.length);
						location.href=parsedUrl.hrefNoHash;
						//$.mobile.changePage( newurl, { changeHash: false });
					}
					
					var myApp = angular.module('myApp',['LocalStorageModule']).config(function($locationProvider) {					
							$locationProvider.html5Mode(true);						
					});			
										
					/////////////////////////////////////////////////////////////
					/// MAP
					
					function initialize(latlng, dist) {
					  // Create the map.
					  
					  
					  var mapOptions = {
						zoom: 12,						
						center: latlng,
						//mapTypeId: google.maps.MapTypeId.TERRAIN
					  };

					  var map = new google.maps.Map(document.getElementById('map-canvas'),
						  mapOptions);
						  
					if (isDefine(dist)) {
						dist=dist*1000; //--km
						var ra = {
						  strokeColor: '#FF0000',
						  strokeOpacity: 0.8,
						  strokeWeight: 2,
						  fillColor: '#FF0000',
						  fillOpacity: 0.35,
						  map: map,
						  center: latlng,
						  radius: dist
						};
						new google.maps.Circle(ra);
					}					
					 
					}
					
					/*
				   Be sure we have some data before showing some page
				*/
										
		</script>
     <!--------------------------------------------------------------------------------
		-------------------------  LOAD SOME ANGULAR --------------------------------------
		---------------------------------------------------------------------------------->
		<script type="text/javascript" src="js/factory/factoryAdmin.js"></script>	
		<script type="text/javascript" src="js/controler/mainCtrlAdmin.js"></script>
		<script type="text/javascript" src="js/taffy-min.js"></script>	
		<script type="text/javascript" src="js/filter/currency.js"></script>
		<script type="text/javascript" src="js/filter/date.js"></script>
		<script type="text/javascript" src="js/filter/searchAdmin.js"></script>
		<script type="text/javascript" src="js/util.js"></script>
		<script type="text/javascript" >
		 $(document).delegate("#new_pub", "pagebeforeshow",
					function (event) {
							 var $scope=  angular.element($(this)).scope();
							// console.log($scope);
							 $scope.load_map();
							 $scope.$apply();							
				 });
		</script>
		<!-- MAP -->
		
		<style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px
      }
    </style>
   <?php 			
			//--Insert in the scope the user privilege
			//--This make all bug ...
			echo "<script>var log_admin = $user_privilege;</script>";
			//--If not logged -> display the login dialog...
			//-- AND DON'T LOAD ANYTHING ELSE!
			//--Echo the associated javascript
			
			if ($user_privilege==-1) {
						echo '
						<script type="text/javascript">
						var myApp = angular.module("myApp",["LocalStorageModule"]).config(function($locationProvider) {					
								$locationProvider.html5Mode(true);
						});		
						$(document).ready(function() { 
							$("#login-submit").click(function(event){
										var email = document.getElementById("email").value;
										var pass  = document.getElementById("pass").value;
										var retour = func_login(email,pass);
								});  
						});
						
						function func_login(email,pass){
							$.ajax({
								type: "POST",
								url:  "login.php",
								data: ({emailConnexion: email , passwordConnexion: pass}),
								cache: false,
								async: true,
								dataType: "text",
								success: onSuccess,
								error: function (data) {console.log("error");console.log(data);}
							});				
							function onSuccess(data){ 
								console.log("Connexion : " + data);
								if(data == "success_login"){
									location.reload(true);
								}
								else if(data == "email_inexistant"){
									$("#messageLogin").html("<font color=red>Email inexistant</font>");
								}
								else if(data == "failed_login"){
									$("#messageLogin").html("<font color=red>Mot de passe incorrect</font>");
								}
							}
							}
							
							</script>
							
							<body>
								<div data-role="dialog" id="popupMenu" data-theme="b" data-transition="pop" data-position-to="window">
									  <div data-role="content">
										<div style="padding:10px 20px;">
											<h3>
											Please log to localhost
											</h3>											
											<div id="messageLogin"></div>
											<form>
											<label for="email" class="ui-hidden-accessible">Email:</label>
											<input type="text" name="email" id="email"  ng-model="email" placeholder="email" data-theme="c" />
											<label for="pass" class="ui-hidden-accessible">Password:</label>
											<input type="password" name="pass" id="pass" ng-model="password" placeholder="password" data-theme="c" />
											</form>	
											<button id="login-submit" data-theme="b" data-icon="check">Log in</button>
										</div>
									</div>
								</div>
						</body></html>';
				exit();
				}
			?>

</HEAD>
	
	<BODY ng-controller='MainCtrl'>
	
	<!--------------------------------------------------------------------------------
				---------------------  PUBLICITE LIST  -----------------------------
		---------------------------------------------------------------------------------->
		<div data-role="page" id="pagePrincipale">
            <div data-role="header" data-theme="b">
				<a target="_self" href="index.php" data-icon="arrow-l">Back</a>
				<a target="_self" ng-click='regenerate_front_pub()' data-icon="refresh">Regenerate Front-End <span id='refresh_front_end_pub'></span> </a>
                <H1>Publicite</H1>
            </div>
            <div data-role="content">
                <div class="content-primary">
					{{new_product.ei}} <span class="badge">total_this</span> <span class="label label-success">date-start</span> - <span class="label label-success">date-end</span>		
				
					latest
					<ul>
					
					
					</ul>
					<div class="form-inline">
						<input type="search" name="" id="search" value="" placeholder="New item to add">					
							<input type="number" data-role="none" class="input-small" style='width:100px' name="" id="search" value="" placeholder="Prix">
							<input type="number" data-role="none"  class="input-small" style='width:100px'  name="" id="search" value="" placeholder="Prix regulier">						
							<input type="number" data-role="none"  class="input-small" style='width:100px'  name="" id="search" value="" placeholder="% rabais">						
					</div>
					{{totalItems}}
					<div class="row no-space" style='margin:0px'>
								<div class="span12" >							
									<ul class="thumbnails" style="margin:0 0 0 0;min-height:147px;">
										<!-- style="height:200px;width:200px;150px;border:1px" -->
										<li class="span2" style="width:135px;margin-bottom:0px;" ng-repeat="produit in filteredItems = ( totalItems = definition  | limitTo:10 )">		 																	
											<div id="all_desc-{{produit.i}}"  href="#" class="thumbnail all_desc" style="background:white;" data-overlayid="springinfo">
												<div id="thumbnail_desc-{{produit.i}}" class="thumbnail_desc show" >													 
													<center>
														<!-- <div style='width:80px;height:90px'><img name=image id="imagethumb-{{produit.i}}" ng-click='select_image(produit);' src="http://gmap.localhost/images/getPicture.php?id_produit={{produit.i}}&{{getTime()}}" style="max-width: 80px !important;max-height:80px !important; width: initial;"> -->
														
														</div>
													</center>																	
												</div>
												<div id="complete_desc-{{produit.i}}" class='complete_desc' style="height:44px;overflow:hidden">
													<p>{{produit.x}} - {{produit.z}}<br>
													<span ng-show="produit.w!=''"><b>{{produit.w|uppercase}}</b></span><br>
													<div class=floatBottom" ng-class="{produit_dispo:produit.nf}" style="color:black;">{{langue.l_until}}: <b>{{produit.d | datelg:'dd MMM yyyy'}}</b></div>
												</div>
												<b>{{produit.s | currencylg}}</b> <b>-{{(1-(produit.s/produit.r))*100  | number:1 }}%</b>																
												<button data-role="none" type="button" data="{{produit.i}}" class="btn btn-primary btn-mini"  ng-click='editproduct(produit)' class="btn btn-info">Edit</button>
											</div>											
										</li>
								</ul>															
							</div>										
				</div>
			</div>	
			<div data-role="footer" class="ui-bar" data-theme="b">
				
			</div>
        </div>
			<!--------------------------------------------------------------------------------
		---------------------  EDIT PRODUIT  -----------------------------
		---------------------------------------------------------------------------------->
		<div data-role="page" id="edit_produit">
            <div data-role="header" data-theme="b">
				<a target="_self" id="edit_produit_back" href="#produit" data-icon="arrow-l">Back</a>
				<a data-role="button" data-icon="delete" id='remove-pr' >Remove product</a>
                <H1>Edit </H1>
            </div>
            <div data-role="content">
                <div class="content-primary">
					
					<form>
					
					
						<!-- Display a view of the product here -->
					<div data-role="navbar">						
						<table  class="table  table-condensed">  	  	  					
							<tbody>
								<tr style='background-color:white;'>
									<td>
									
									<img src="http://gmap.localhost/images/getPicture.php?id_produit={{current_product.i}}&{{getTime()}}" class="img-polaroid floatLeft">					
										<p class='span4'><b>{{current_product.w|uppercase}}<br ng-show="current_product.w!=''">{{current_product.x}} {{current_product.z}} {{current_product.q}}	</b>
											<br><span style='color:grey;'>{{current_product.xe}} {{current_product.ze}} {{current_product.qe}}</span>		
											<br><span ng-class="{produit_dispo:current_product.nf}" style="color:black;">Until: <b>{{current_product.d | datelg:'dd MMM yyyy'}}</b></span>							
										</p>					
										<p class='span10'>						
											<div style='float:bottom;display:inline' >							
												<span style='font-size:150%; font-weight:bold; color:red;'>{{current_product.s | currencylg}}</span>		
												<br>{{current_product.r | currencylg}}
												<br><b>Rabais {{(1-(current_product.s/current_product.r))*100  | number:1 }}%</b>
											</div>				
										</p>
										<br><span style="color:grey;font-size:90%;">{{current_product.i}}</span>									
									</td>
								</tr>
							</tbody>
						</table>
						</div>
						<!-- Edit the product-->
						<ul data-role="listview">
							<li data-role="fieldcontain">
							<label for="brand_fr">Brand</label>
								<input type="text" name="brand_fr" id="brand_fr" ng-model="current_product.w" >																	
							</li>
							<li data-role="fieldcontain">
							<label for="brand_en">Brand (English if different)</label>												
								<input type="text" name="brand_en" id="brand_en" ng-model="current_product.we" >						
							</li>
							<li data-role="fieldcontain">
							<label for="nom_produit_fr">Description</label>
								<input type="text" name="nom_produit_fr" id="nom_produit_fr" ng-model="current_product.x" >						
							</li>
							<li data-role="fieldcontain">
							<label for="nom_produit_fr2"></label>
								<input type="text" name="nom_produit_fr2" id="nom_produit_fr2" ng-model="current_product.z" >						
							</li>
							<li data-role="fieldcontain">
							<label for="nom_produit_fr">Description (English)</label>
								<input type="text" name="nom_produit_fr" id="nom_produit_en" ng-model="current_product.xe" >						
							</li>
							<li data-role="fieldcontain">
							<label for="nom_produit_fr2"></label>
								<input type="text" name="nom_produit_fr2" id="nom_produit_en2" ng-model="current_product.ze" >						
							</li>
							
							<li data-role="fieldcontain">
							<label for="quantite">Quantity</label>
								<input type="text" name="quantite" id="quantite" ng-model="current_product.q" >						
							</li>						
							<li data-role="fieldcontain">
							<label for="quantite_en"></label>
								<input type="text" name="quantite_en" id="quantite_en" ng-model="current_product.qe" >						
							</li>	
							
							<li data-role="fieldcontain">
						
							<label for="id_categorie">Category</label>
							<select data-role="none" name="id_categorie" id="id_categorie" ng-model="current_product.c" 
									ng-options="cat.ci as cat.cnf for cat in categories" >								
							</select>
							
							</li>
							
							<li data-role="fieldcontain">
							<label for="prix_special">Prix special</label>
								<input type="text" name="prix_special" id="prix_special" ng-model="current_product.s" >						
							</li>
							<li data-role="fieldcontain">
							<label for="prix_regulier">Prix regulier</label>
								<input type="text" name="prix_regulier" id="prix_regulier" ng-model="current_product.r" >						
							</li>							
						</ul>
					</form>
                </div>
            </div>
			<div data-role="footer" class="ui-bar" data-theme="b">
				<a target="_self" href="#produit" data-icon="arrow-l">Cancel</a>
				<a data-role="button" id='update_product'>Update <span id='update_product_loading'></span></a>
			
			</div>
        </div>
		
		<!--------------------------------------------------------------------------------
		---------------------  PRODUIT   --------------------------------------
		---------------------------------------------------------------------------------->
		<div data-role="page" id="produit">
            <div data-role="header" data-theme="b">
				<a target="_self" id='produit_back' href="#current_page" data-icon="arrow-l">Back</a>
                <H1>Produit</H1>														  
            </div>
            <div data-role="content">
                <div class="content-primary">
						<div class="panel-heading ui-bar-c" style="margin: -15px -15px 5px !important;background-color:white;">
							 <h3 class="panel-title" style="line-height: 20px;"> 								 
								 <!-- tri par -->	
								<fieldset data-role="controlgroup" data-type="horizontal"  data-mini="true">					
									<input type="radio" name="radio-choice-produit-up" id="radio-choice-produit-up-1" value="0" checked="checked" />
									<label for="radio-choice-produit-up-1">%</label>
									<input type="radio" name="radio-choice-produit-up" id="radio-choice-produit-up-2" value="1" />
									<label for="radio-choice-produit-up-2">Exp.</label>																	
									<input type="radio" name="radio-choice-produit-up" id="radio-choice-produit-up-4" value="3" />
									<label for="radio-choice-produit-up-4">$</label>
									<input type="radio" name="radio-choice-produit-up" id="radio-choice-produit-up-3" value="5" />
									<label for="radio-choice-produit-up-3">w/o image</label>	
								</fieldset>	
								
								 <!-- search -->
								 <input placeholder="Filter items..." data-type="search"  ng-model="search" class="ui-input-text ui-body-c" ng-click='search=""'>
								 <!-- mode -->	
								<fieldset data-role="controlgroup" data-type="horizontal"  data-mini="true">	
									<input type="radio" name="radio-choice-list-type" id="radio-choice-produit-ll-1" value="0" ng-init="listmode=true" ng-click="listmode=true" checked="checked" />
									<label for="radio-choice-produit-ll-1"><i class="icon-list"></i></label>
									<input type="radio" name="radio-choice-list-type" id="radio-choice-produit-ll-2" ng-click="listmode=false" value="1" />
									<label for="radio-choice-produit-ll-2"><i class="icon-th"></i></label>										
								</fieldset>	
								
								
								<span ng-show="produits[0].b==-1"><b >{{totalItems.length-1}}</b> products </span>
								<span ng-show="produits[0].b!=-1"><b >{{totalItems.length}}</b> products </span>
								<span  ng-show='Produit.min_date==Produit.max_date' style="text-align:center;">(Until {{Produit.max_date | date:'dd MMM yyyy'}})</span>
								<span  ng-show='Produit.min_date!=Produit.max_date' style="text-align:center;">(Between {{Produit.min_date | date:'dd MMM yyyy'}} and {{Produit.max_date | date:'dd MMM yyyy'}})</span>							
								<button ng-show='produits[0].b==-1' data-role="none" type="button" data="{{produit.i}}" class="btn btn-primary"  ng-click='newproduct()' class="btn btn-info">New Product</button>
								
							</h3> 
							
						</div> 		

							<!-- Display as list -->	
							 <table ng-show='listmode' class="table  table-condensed">  	  	  
							  <tbody>
							  <!-- | filter:search  table-bordered table-striped table-hover-->
							  <tr ng-repeat="produit in filteredItems = ( totalItems = (produits | filter:search | orderBy:getTriParam() ) | limitTo:itemsLimit() )">		
								<td ng-show='produit.b!=-1'><img src="http://gmap.localhost/images/getPicture.php?id_produit={{produit.i}}&lg={{langage}}&{{getTime()}}" ng-click='select_image(produit);' class="img-polaroid floatLeft">					
									<!-- <deals deals="produit.b" regulier="produit.r" langue="langue"></deals>	-->				
									<p class='span4'><b>{{produit.w|uppercase}}<br ng-show="produit.w!=''">{{produit.x}} {{produit.z}} {{produit.q}}</b>
										<br><span style='color:grey;'>{{produit.xe}} {{produit.ze}} {{produit.qe}}</span>		
										<br><span ng-class="{produit_dispo:produit.nf}" style="color:black;">Until: <b>{{produit.d | datelg:'dd MMM yyyy'}}</b></span>							
									</p>					
									<p class='span10'>						
										<div style='float:bottom;display:inline' >							
											<span style='font-size:150%; font-weight:bold; color:red;'>{{produit.s | currencylg}}</span>		
											<br>{{produit.r | currencylg}}

										<button data-role="none" type="button" data="{{produit.i}}" class="btn btn-primary"  ng-click='editproduct(produit)' class="btn btn-info">Edit</button>
										</div>				
									</p>				
								<!-- <td>{{produit.d | datelg:'dd MMM yyyy'}}</td> -->
								<!-- <a class="btn btn-small btn-info" href="#/produit/{{produit.i}}"> <i class="icon-info-sign"></i> {{langue.l_details}}</a>	-->				
								</td>
							  </tr>
							  </tbody>
							</table> 
							
							<!-- display as thumb -->
							 <div ng-show='!listmode' class="row no-space" style='margin:0px'>
								<div class="span12" >							
									<ul class="thumbnails" style="margin:0 0 0 0;min-height:147px;">
										<!-- style="height:200px;width:200px;150px;border:1px" -->
										<li class="span2" style="width:135px;margin-bottom:0px;" ng-repeat="produit in filteredItems = ( totalItems = (produits | filter:search | orderBy:getTriParam() ) | limitTo:(itemsLimit()-1) )">		 																	
											<div id="all_desc-{{produit.i}}"  href="#" class="thumbnail all_desc" style="background:white;" data-overlayid="springinfo">
												<div id="thumbnail_desc-{{produit.i}}" class="thumbnail_desc show" >													 
													<center>
														<div style='width:80px;height:90px'><img name=image id="imagethumb-{{produit.i}}" ng-click='select_image(produit);' src="http://gmap.localhost/images/getPicture.php?id_produit={{produit.i}}&{{getTime()}}" style="max-width: 80px !important;max-height:80px !important; width: initial;">
														</div>
													</center>																	
												</div>
												<div id="complete_desc-{{produit.i}}" class='complete_desc' style="height:44px;overflow:hidden">
													<p>{{produit.x}} - {{produit.z}}<br>
													<span ng-show="produit.w!=''"><b>{{produit.w|uppercase}}</b></span><br>
													<div class=floatBottom" ng-class="{produit_dispo:produit.nf}" style="color:black;">{{langue.l_until}}: <b>{{produit.d | datelg:'dd MMM yyyy'}}</b></div>
												</div>
												<b>{{produit.s | currencylg}}</b> <b>-{{(1-(produit.s/produit.r))*100  | number:1 }}%</b>																
												<button data-role="none" type="button" data="{{produit.i}}" class="btn btn-primary btn-mini"  ng-click='editproduct(produit)' class="btn btn-info">Edit</button>
											</div>											
										</li>
								</ul>															
							</div>						
						</div>
					<button type="button" id="show_more_btn" class="btn btn-inverse" ng-show="hasMoreItemsToShow()" ng-click="showMoreItems()">Show More</button> 								 	
					<br>
			    </div>
            </div>
        </div>
	
	</BODY>
</HTML>	
<?php 
@deconnection_db();	
?>	