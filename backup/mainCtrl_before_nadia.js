/*
 * Copyright (c) 2013, 9279-5749 Québec inc and/or its affiliates. All rights reserved.
 * Etienne Lord, Alix Boc
 * PROPRIETARY/CONFIDENTIAL. Use is subject to license terms.
 */
				
			var MainCtrl=function ($route,$routeParams,$rootScope,$scope,$timeout, $http, $locale, $location, $window,$filter,Langue,Produit,User,Franchise) {  						
						$scope.phantomJSready=false;
						$scope.moneymaker_haut = "";
						$scope.navbar="template/produit_navbar.html";					
						$scope.current_panier=User.CURRENT_PANIER();
						$scope.panier=[]; //--All this user paniers
						$scope.link=""; //--MOBILE LINK
						$scope.moneymaker2="";
						$scope.moneymaker3="";
						$scope.flags=[]; //--flags for different reasons
						$scope.valid_code_and_distance=true; //--codePostal and distance valid
					   	$scope.obj = { '5': '5 km', '10': '10 km' };
					   	$scope.rayons = [
							{label:'5 km', value:5},
							{label:'10 km', value:10},
							{label:'15 km', value:15},
							{label:'20 km', value:20}
						  ];
						 
						$scope.center={
									latitude: 0, // initial map center latitude
									longitude: 0, // initial map center longitude
						}
						$scope.markers= []; // an array of markers,
						$scope.zoom= 8; // the zoom level
						 $scope.pageSizeValue = [25, 50, 100, 200];
						 $scope.selected_enseignes=[];
						 $scope.selected_categories=[];		
						 $scope.availables=[]; //--hashMap id_enseigne->total_products	
						 $scope.availables_cat=[]; //--hashMap id_cat->total_products	
						 $scope.search=""; //--Actual search
						 $scope.langage="en"; //--For googlemap
						 $scope.loading_map=false;
						 $scope.loading_langue=false;
						 $scope.promotion_loading=false;
						 $scope.map_small=true; //--to increse or decrease the size of map.
						 $scope.desktop_width=$(window).width();
						 $scope.cached_produit=[]; //--for faster promotioms
						 $scope.search_input=""; //--Input of search
						 $scope.print=false;							 
						 $scope.produit_franchise=Franchise.produit_franchise;
						 $scope.ask_distance = User.DISTANCE(); 
						 $scope.ask_postalCode="H2X3Y7";
						 $scope.old_postalCode=""; 
						 $scope.couponscat=[]; //Coupons by cat	
						 $scope.previousTriParam="";	
						 $scope.previousTriSearch="";
						 $scope.previousTriResult="1.0-b";
						 $scope.displaymoreFranchise=false;
						
					   	//$scope.special_date=getDayOfSpecial();
						
						
						$scope.openModal = function(id_modal){
						
							$('#' + id_modal).modal('show');
						}
						
						//--this show the map in medium form		
						$scope.showSmallMap = function() {						
								view_minimap=true;
								 //Franchise.displayMiniMap(User);
								$('#moneymaker_top').hide();
								$('#link_message_info').hide();
								$('#link_codepostal').hide();
								$('#resize_btn').hide();
								$('#link_magasin_info').show();	
								$('#wrapper').show();
								//$('#over_map').css("height","300px",function(){});
								//$('#over_map').css("width","500px",function(){});
								
								$('#map_canvas').animate({height:'300px',width:'600px'}, 500, function() {
									if (isDefine(minimap)&&isDefine(google)) {
										google.maps.event.trigger(minimap, "resize");
										centerMap();
									}
								});															
								$('#table_info').show();					
							}
						
						//--Print
						$scope.print_panier = function() {
							User.PRINT($http, Produit);
						}
						
						//--Click
						$scope.click=function(p) {
							User.CLICK($http,p);
						}					
						
						//--This show the initial map 
						//--when this is the first time 							
						$scope.showInitialMap = function() {
								//--Call the map function
								 view_minimap=true;
								 Franchise.displayMiniMap(User);								
								$('#table_info').show();
								$('#moneymaker_top').hide();
								$('#link_message_info').show();
								$('#link_codepostal').show();
								$('#resize_btn').show();
								$('#link_magasin_info').hide();								
								//$('#over_map').css("height","300px",function(){});
								//$('#over_map').css("width","500px",function(){});
								$('#wrapper').show();
								$('#map_canvas').animate({height:'300px',width:'600px'}, 500, function() {
									if (isDefine(minimap)&&isDefine(google)) {
										google.maps.event.trigger(minimap, "resize");
										centerMap();
									}
								});															
								$('#table_info').show();					
							}
						
						//--This display the fullsize map with information
						$scope.showNearbyStores = function() {
								//--Call the map function
								view_minimap=true;
								 Franchise.displayMiniMap(User);
								
								$('#link_message_info').hide();	
								$('#link_codepostal').show();
								$('#moneymaker_top').hide();
								$('#resize_btn').hide();
								$('#link_magasin_info').show();		
								//$('#over_map').css("height","0px",function(){});
								//$('#over_map').css("width","0px",function(){});
								$('#wrapper').show();
								$('#map_canvas').animate({height:'600px',width:'600px'}, 500, function() {
									if (isDefine(minimap)&&isDefine(google)) {
										google.maps.event.trigger(minimap, "resize");
										centerMap();
									}
									
								});															
								$('#table_info').show();					
							}
					
						//--This display no map at all but ads
						$scope.hideFastNearbyStores = function() {						
						     	view_minimap=false;
								$('#moneymaker_top').show();
								$('#link_message_info').hide();
								$('#link_codepostal').hide();
								$('#resize_btn').show();
								$('#link_magasin_info').hide();
								//$('#over_map').css("height","40px",function(){});
								//$('#over_map').css("width","500px",function(){});								
								$('#map_canvas').css("width","600px", function() {});																
								$('#wrapper').hide();
								$('#table_info').show();	
						}
						
						$scope.hideNearbyStores = function() {
							view_minimap=false;
							$('#table_info').hide();
							$scope.hideFastNearbyStores();
							//$scope.showSmallMap();
						}
						
						$scope.showMap = function() {
							//console.log('showMap ' + $scope.map_small);
							if (!$scope.map_small) {
								$scope.map_small=true;
								$scope.hideNearbyStores();
							} else {
								$scope.map_small=false;
								$scope.showNearbyStores();
							}
						}
						
						$scope.getUser=function() {
							//--Help fonction for the initial map
							return User;
						}
						
						$scope.getFranchise=function() {
							//--Help fonction for the initial map
							return Franchise;
						}

						$scope.getHttp=function() {
							//--Help fonction for the initial map
							return $http;
						}	
						
						$scope.gocr = function ( hash ) {							  
							User.WEB($http, hash);
							$location.url( hash+"/"+$scope.langage);							  
							 //--path, hash instead?
						};
						
						$scope.go = function ( hash ) {							  
							  if (hash=='/map'&&$scope.totalquantity()==0) {
								//--Popup add things to list - TO DO
								return true;
							  }
							  //--Save any ads...		
							  if ($scope.moneymaker2==""||!isDefine($scope.moneymaker2)) {
								 $scope.moneymaker2=$('#moneymaker2').html();														 							
							  }							  
							  if ($scope.moneymaker3==""||!isDefine($scope.moneymaker3)) {
								 $scope.moneymaker3=$('#moneymaker3').html();														 							
							  }								  							  
							  if (hash=='/categories'||hash=='/promo') {
									$scope.search="";
									$scope.search_input="";
							  }
							  $location.url( hash );							  
							  //--
							  
							  
							  //User.WEB($http, hash);
							  //--path, hash instead?
							};
						
						$scope.gomap=function() {
							if ($scope.totalquantity()==0) {
								$scope.showNearbyStores();
							} else {
								$scope.go('/map');
							}
						}
						
							$scope.goback = function() {
								 $window.history.back();
							}
						
						$scope.desktopwidth=function() {	
							//console.log("width:"+$scope.desktop_width);
						
							return (width>980);
						}
						
						$scope.setFilter=function(promo) {
							$scope.hideNearbyStores();						
							$scope.selected_enseignes=[];
							$scope.selected_enseignes.push(promo.nomFranchise);
							//--Clear categories
							$scope.selected_categories=[];	
							User.USER_CATEGORIES($scope.selected_categories);		
							
							User.USER_ENSEIGNES($scope.selected_enseignes);		
							$scope.go ('/produit');
						}
						
						$scope.setFilterCategorie=function(cat) {
							$scope.hideNearbyStores();						
							$scope.selected_categories=[];							
							$scope.selected_categories.push(cat.ci);
							//--Clear enseignes
							$scope.selected_enseignes=[];
							User.USER_ENSEIGNES($scope.selected_enseignes);		
							
							User.USER_CATEGORIES($scope.selected_categories);		
							$scope.go ('/produit');
						}
						
						$scope.filterFound=function() {							
							if (!isDefine($scope.enseignes)) return false;
							if (!isDefine($scope.categories)) return false;							
							if ($scope.selected_enseignes.length>0||
								$scope.selected_categories.length>0||
								$scope.moneyFilter()!=""||
								$scope.search.length>0
								) {								
									return true;
								}	
							return false;
						}
						
						$scope.moneyFilter=function(data) {																					
							var selected_money=User.USER_MONEYFILTER();
							if (isDefine(data)) return selected_money; //--For the search filter
							var limitPriceUp=999999.99;
							var limitPriceDown=0.0;								    			
							if (isDefine(selected_money)&&selected_money!="") {
								//--Read the price min max
								var cas1 = /([0-9][0-9]*([.,][0-9][0-9]*)?)/;
								var cas2 = /([0-9][0-9]*([.,][0-9][0-9]*)?)[-]([0-9][0-9]*([.,][0-9][0-9]*)?)/;
								var match2 = cas2.exec(selected_money);			
								if (match2!=null) {
									limitPriceDown=parseFloat(match2[1]);
									limitPriceUp=parseFloat(match2[3]);	
									$scope.priceLow=limitPriceDown;
									$scope.priceHigh=limitPriceUp;
									return $scope.langue.l_between+" "+$filter('currency')(limitPriceDown)+" "+$scope.langue.l_and+" "+$filter('currency')(limitPriceUp);
								} else {
									var match1 = cas1.exec(selected_money);
									if (match1!=null) {
										limitPriceUp=parseFloat(match1[1]);						
										$scope.priceHigh=limitPriceUp;
										return $scope.langue.l_lessThan+" "+ $filter('currency')(limitPriceUp);
									}
								}
							}
							return selected_money;
						}
											
						$scope.applyMoneyFilter=function(low, high) {								
							if (parseFloat(high)>0) {
								if (parseFloat(low)>0) {
									User.USER_MONEYFILTER(low+"-"+high);
									$scope.priceLow=low;
									$scope.priceHigh=high;
								} else {
									User.USER_MONEYFILTER(""+high);
									$scope.priceHigh=high;
								}
							}							
							search_changed=true;
							$scope.go('/produit');
							//$scope.goback();
						}
						
						$scope.removeMoneyFilter=function(){
							User.USER_MONEYFILTER("");
							search_changed=true;							
						}
						
						
						
						$scope.removeSearch=function() {
							$scope.search="";
							$scope.search_input="";
							search_changed=true;
							
						}
						
						$scope.getSelectedTooltip=function() {
							var tmp="";
							
							for (var i=0; i<$scope.selected_enseignes.length;i++) tmp+=$scope.selected_enseignes[i]+" ";		
							return tmp;
						}
						
						$scope.isCheckedEnseigne=function(item) {						
							if (!isDefine($scope.selected_enseignes)||!$.isArray($scope.selected_enseignes)) {
									$scope.selected_enseignes= [];	
									return false;						  
							   }							  
							  if($scope.selected_enseignes.indexOf(item.en) !== -1){
								return true;
							  }
							  return false;
						}
						
						$scope.isCheckedCategorie=function(item) {						
							
							if (!isDefine($scope.selected_categories)||!$.isArray($scope.selected_categories)) {
									$scope.selected_categories= [];										
									return false;						  
							   }							 					
							  if($scope.selected_categories.indexOf(item.ci) !== -1){
								return true;
							  }
							  return false;
						}
						
						$scope.getOrderCategorie = function() {
							if ($scope.langage=='fr') {
								return 'cnf';
							} else {
								return 'cne';
							}							
						}
						
						$scope.findLocation=function() {
							//== Determiner la position de l'utilisateur
							if(navigator.geolocation) {
								// API 
								navigator.geolocation.getCurrentPosition(function(position) {
									var infopos = "Position :\n";
									infopos += "Latitude : "+position.coords.latitude +"\n";
									infopos += "Longitude: "+position.coords.longitude+"\n";
									infopos += "Altitude : "+position.coords.altitude +"\n";
									console.log(infopos);
									setLocalStorage("LONGITUDE",position.coords.longitude);
									setLocalStorage("LATITUDE",position.coords.latitude);
									setLocalStorage("ZOOM",15);
								},null,{timeout:10000});
							} else {
								//--Not found
							}
						}
						
						$scope.isProduit=function() {							
							return ($route.current.loadedTemplateUrl=="template/produit.html"||$route.current.loadedTemplateUrl=="template/filtre.html"||$route.current.loadedTemplateUrl=="template/filtreIE8.html");							
						}
						
						$scope.getNameCategorie = function(item){
							if ($scope.langage=='fr') {
								return item.cnf;
							} else {
								return item.cne;
							}
						}
						
						$scope.toggleCheckedEnseigne = function(item){												  
						  if(!$scope.isCheckedEnseigne(item)){
							$scope.selected_enseignes.push(item.en);
						  }else{
							$scope.selected_enseignes.splice($scope.selected_enseignes.indexOf(item.en), 1);
						  }
						   User.USER_ENSEIGNES($scope.selected_enseignes);							   						  
						};
						
						$scope.remoreAllEnseignes = function() {
							$scope.selected_enseignes= [];						
							User.USER_ENSEIGNES($scope.selected_enseignes);														
						}
						
						$scope.removeAllFilter = function() {						
							$scope.selected_enseignes= [];						
							User.USER_ENSEIGNES($scope.selected_enseignes);				
							$scope.selected_categories=[];						
							User.USER_CATEGORIES($scope.selected_categories);	
							User.USER_MONEYFILTER("");
							$scope.search="";
							$scope.search_input="";
							search_changed=true;			
						}
						
						$scope.showAll = function() {
							$scope.removeAllFilter();
							$scope.go('/produit');
						}
						
						$scope.toggleCheckedCategorie = function(item){												  
						  if(!$scope.isCheckedCategorie(item)){
							$scope.selected_categories.push(item.ci);
						  }else{
							$scope.selected_categories.splice($scope.selected_categories.indexOf(item.ci), 1);
						  }
						   User.USER_CATEGORIES($scope.selected_categories);							   						  
						};
						
						$scope.newPanier = function()  {
							User.newPanier();
							User.getHash();
							$route.reload();
						}
						
						$scope.newPanierModal = function()  {
							User.newPanier();
							User.getHash();
							$("#popup_invalid_product").modal('hide');
							$route.reload();							
						}
						
						$scope.removeBadProductModal = function()  {
							User.removeBadPanier();
							User.getHash();
							$("#popup_invalid_product").modal('hide');
							$route.reload();							
						}
						
						$scope.gobackProduct = function(code_postal)  {																						
								$scope.ask_postalCode=code_postal;
								$("#popup_invalid_product").modal('hide');
								$scope.changePostalCode();
						}
						
						
						$scope.totalquantity = function(panier) {				 
							return User.totalquantity(panier);
						}
						
						$scope.total = function(panier) {				 
								return User.total(panier);
						}
						
						
						$scope.savingCurrentPanier = function(panier) {
							return User.savingCurrentPanier(panier);
						}
						
						$scope.getProduitFromPanier=function(data) {
							if (!isDefine(Produit.produit)) return {};			
							var result = $.grep(Produit.produit, function(e){ return e.p == data.p; });				
							if (!isDefine(result)) return "";
							return result[0];							
						}
						
						$scope.loadPanier = function(id_session, id_panier) {
							var  post=new Object();							
							console.log('Loading panier');
							//--This session 
							post.mobile=true;
							post.session=User.SESSIONID();
							post.time=getTimeStamp();
							//post.panier=getObject('taffy_Liste');								
							//post.user=getObject('taffy_User');
							post.id_database=User.USER_DATABASE();
							var url="mobile.php?i="+id_session+"&p="+id_panier;
							try {
								$http({
									method: 'POST',
									url: url,
									data: post,
									headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								}).success(function(d) {														
									User.replacePanier(id_panier,d, $location);															
								}).error(function(d) {
									//console.log('error');
								});
							  } catch(e) {console.log(e);}
						}
						
						$scope.proceed_to_other_database=function() {
							//--This will load the product for a new database...
							
						
						}
						
						$scope.roolback_to_database=function() {
							//--This will return to the old code postal...
							
						
						}
						
						
						//--Handle User					 
						 $scope.$on('handleFranchise', function(events,data) {								
							$scope.loading_map=false;
							$("#loading_info").hide();	
							if (data!=null) {								
								Franchise.franchise=data;
								Franchise.psq=data['promoPsq'];
								Franchise.loaded=true;	
								setLocalStorage("LONGITUDE",data.info[0]);
								setLocalStorage("LATITUDE",data.info[1]);							
								setLocalStorage("ZOOM",15);
								User.LONGITUDE(data.info[0]);
								User.LATITUDE(data.info[1]);
								User.ZOOM(15);
								console.log("Franchise code postal:"+Franchise.codePostal);
								$scope.postalCode=Franchise.codePostal;
								//--Update button gmap and map
								$scope.url_google_static2="<img src='http://maps.googleapis.com/maps/api/staticmap?center="+Franchise.codePostal+"&size=200x120&sensor=false&zoom=10'></img>"; 	
								$('.btn_minimap_adr').html( $scope.url_google_static2);	
								//console.log("handleFranchise:"+$scope.url_google_static2);
								if (isDefine(minimap)&&isDefine(google)) {
										google.maps.event.trigger(minimap, "resize");
										centerMap();
									}	
								if (User.USER_DATABASE()!=data.info[2]) {									
									User.USER_DATABASE(data.info[2]);
									Produit.loadProduit($http,$rootScope,User);										
								} else {
									
									promotion_codePostal=$scope.postalCode;	
									promotion_distance=User.DISTANCE();	
									User.ADRESSE($scope.postalCode);																	
									$scope.promos=Franchise.getPromotion($scope.postalCode, $scope.distance, Produit, User);									
									$scope.promos_categories=Franchise.categories;							
									$scope.produit_franchise=Franchise.produit_franchise;
									$scope.promotion_loading=false;
									$scope.cached_produit=[];																									
								}								 
							} else {											
								promotion_codePostal=$scope.postalCode;	
								promotion_distance=User.DISTANCE();	
								User.ADRESSE($scope.postalCode);																	
								$scope.promos=Franchise.getPromotion($scope.postalCode, $scope.distance, Produit, User);								
								$scope.promos_categories=Franchise.categories;							
								$scope.produit_franchise=Franchise.produit_franchise;
								$scope.promotion_loading=false;
								$scope.cached_produit=[];															
								//--Note: this is a request 									
								 //Franchise.displayMiniMap(User);								 
								 }
							 Franchise.displayMiniMap(User);	
							 
						 })
						
						//--Handle User					 
						 $scope.$on('handleUser', function(events,user) {						  							  
							  Langue=user.langue;
							  $scope.langage=User.LANGUE();
							  
							  //--Load map data							  
							if (!google_map_loaded) {
								console.log("Loading gmap api");
								try {							
									var script = document.createElement("script");
									script.type = "text/javascript";
									script.src = "http://maps.googleapis.com/maps/api/js?libraries=geometry&sensor=false&language="+$scope.langage+"&callback=loadMap";								
									document.body.appendChild(script);	
									try {									
									var nht=new google.maps.LatLng(0,0);
									} catch(Exception2) {}
									google_map_loaded=true;
									//--Put in user callback loadMap();																	
								} catch(Exception) {
									console.log(Exception);
								}
								
							}
							
							  $scope.postalCode	= User.ADRESSE();	
							  btn_minimap_adr=$scope.postalCode;								
							  $scope.distance 	= parseInt(User.DISTANCE());
							  $scope.priceLow=0.0;
							  $scope.priceHigh=0.0;							 
							//--Hard code the langue							 
							 var path=$(location).attr('href');
									if (path.indexOf('circuitpromo')!=-1) {
										User.LANGUE('fr');										
									} else {
										User.LANGUE('en');
									}
									
							  if(User.NBVISITE() == 0&&!isDefine($route.current.params.changelg)) {
									overlayBody();
									//console.log("apres : overlayBody");
									//$("body").append('<div style="position: fixed;width: 100%;height: 100%;top: 0px;left: 0px;background-color: black;">');
									$( "#table_info" ).show();								
									$( "#form_postalCode" ).show();								
									$scope.showInitialMap();
							   } else {
									//--No map at all
									$scope.hideFastNearbyStores();
								}							  
							    User.NBVISITE(1); //--Increase by one							  
								
								if (User.LANGUE()=='fr') {								
									$scope.lg=false;
									$scope.langue = Langue[0]; 	
									$scope.langue_otherChoice = "English";									
								} else {									
									$scope.langue = Langue[1]; 	
									$scope.lg=true;
									$scope.langue_otherChoice = "Français";																	
								}		
								//--Set title
								document.title=$scope.langue.titreSite+" - "+$scope.langue.l_slogan;
								//--Selected enseignes and categories
								$scope.selected_enseignes=User.USER_ENSEIGNES();								
								$scope.selected_categories=User.USER_CATEGORIES();	
								//--TriPar
								$scope.tripar = [$scope.langue.l_rabais,
												 $scope.langue.l_dateFin,
												 $scope.langue.l_magasin,												 
												 $scope.langue.l_prix,												 
												 'Description'
												];
								$scope.getTripar = function () {
									return $scope.tripar[User.USER_TRIPAR()];									
								}
								$scope.setTripar = function (tripar) {
									User.USER_TRIPAR(tripar);																	
								}
								//--Return the param to search							
								$scope.getTriParam = function (search) {									
									
									//--Which user option
									var chosenParam = User.USER_TRIPAR();									
									//--return function Adjusts the sorting process by pushing up entries whose description contains the search query string as a separate word or phrase 
									//--Note: here produit is the push version in the result pipe
									function sortAdjust(sortParam) {									 
										if(typeof search !== "undefined" && search !== "") {													
											var getter = function(produit) {
												var pattern = new RegExp("\\b" + search + "\\b", "i");
												return !pattern.test(produit.y); 
												};															
											return [getter,sortParam];
											}
										else { 
											return sortParam;
										}
									};
																		
									if(chosenParam === $scope.previousTriParam && search === $scope.previousTriSearch) return $scope.previousTriResult;									
									$scope.previousTriParam=chosenParam;	
									$scope.previousTriSearch=search;									
									switch (chosenParam) {
										case 0: $scope.previousTriResult = sortAdjust('1.0-b'); break;
										case 1: $scope.previousTriResult = 'd'; break;
										case 2: $scope.previousTriResult = ['en','1.0-b']; break;
										case 3:  $scope.previousTriResult = 's'; break;
										case 4: $scope.previousTriResult = sortAdjust('x'); break;
										default: $scope.previousTriResult = '1.0-b';
									}
									return $scope.previousTriResult;									
								}
								
								User.loaded=true;
								//--Handle a change of site -> need to load the user data
								if (isDefine($route.current.params.changelg)) {																	
									var tmp=$route.current.params.changelg.split("-");
									var load_product_new_code=false;
									var load_product_old_codepostal=User.ADRESSE();									
									$scope.id_session=tmp[0];
									$scope.id_panier=tmp[1];	
									User.LANGUE(tmp[2]);									
									//--If the user Adresse is not the same here ->
									//--Reload using a flag...
									if (tmp[3]!=User.ADRESSE()) {
										load_product_new_code=true;
									}	
									User.ADRESSE(tmp[3]);
									User.DISTANCE(tmp[4]);									
									User.USER_DATABASE(tmp[5]);	
									$scope.postalCode	= User.ADRESSE();
									$scope.distance 	= parseInt(User.DISTANCE());
									console.log('Changing langage to '+tmp[2]+' from '+tmp[3]+' ('+load_product_old_codepostal+')');									
									if (User.LANGUE()=='fr') {								
										$scope.lg=false;
										$scope.langue = Langue[0]; 	
										$scope.langue_otherChoice = "English";										
									} else {									
										$scope.langue = Langue[1]; 	
										$scope.lg=true;
										$scope.langue_otherChoice = "Français";																	
									}									
									if (load_product_new_code) {
										$scope.hideFastNearbyStores();
										btn_minimap_adr=$scope.postalCode;	
										$scope.loadPanier($scope.id_session, $scope.id_panier);
										//--Update button gmap
										$scope.url_google_static3="<img src='http://maps.googleapis.com/maps/api/staticmap?center="+User.ADRESSE()+"&size=200x120&sensor=false&zoom=10'></img>"; 	
										console.log($scope.url_google_static3);
										//--Change the title bar
										$('.btn_minimap_adr').html( $scope.url_google_static3);										
										console.log("Update product to "+User.ADRESSE());
										$scope.changePromotion();
									} else {
										$scope.hideFastNearbyStores();
										$scope.loadPanier($scope.id_session, $scope.id_panier);
									}
								}
								
								$scope.postalCode	= User.ADRESSE();
								$scope.distance 	= parseInt(User.DISTANCE());
								//--Get the pub							
								Franchise.getPub($http, $scope, $scope.postalCode);								
								//--Build a cache of the panier	
								User.getHash();
								$scope.panier=User.listPanier();								
							}); //--Fin handleUser
							
						
						  //--Coupons		
						 $scope.$on('handleCoupons', function(events,coupons) {		
							//--Transform into array
							var fr=(User.LANGUE()=='fr');
							var coupons = $.map(coupons, function(value, index) {
								 return [value];
							});	
							var tmp_ens={};							
							for (var i=0; i<coupons.length;i++) {
								coupons[i].y=removeDiacritics(coupons[i].title_fr.toLowerCase()+" "+coupons[i].title_en.toLowerCase());		
								//--Uniform link
								  //coupons[i].noCategorie=parseInt(coupons[i].noCategorie);
									coupons[i].index=i;
									if (coupons[i].link.indexOf("http")!=0) {
										coupons[i].link="http://"+coupons[i].link;
									}
								var result = $.grep(Produit.categorie, function(cat){ return cat.ci == coupons[i].noCategorie; });	
								if (isDefine(result)&&result.length>0) {																										
										if (fr) {
											coupons[i].cat=result[0].cnf;									
										} else {
											coupons[i].cat=result[0].cne;									
										}
								} else {
									if (fr) {
										coupons[i].cat="Divers";
									} else {
										coupons[i].cat="Others";
									}
								}		
								if (!isDefine(tmp_ens[coupons[i].cat])) {
									tmp_ens[coupons[i].cat]=[];																		
								}									
								tmp_ens[coupons[i].cat].push(coupons[i]);								
							}
							//--Remix array
							var to=$scope.getPromotionNumber();
							for (var key in tmp_ens) {
								var tmp=tmp_ens[key];
								var tmpp=[];
								tmpp['cat']=tmp[0].noCategorie;
								tmpp['show']=1;
								tmpp['total']=tmp.length;
								for (var i=0; i<tmp.length;i++) {
									if (!isDefine(tmpp[Math.floor(i/to)])) {
										tmpp[Math.floor(i/to)]=[];
									}
									tmpp[Math.floor(i/to)].push(tmp[i]);
								}
								tmp_ens[key]=tmpp;
							}
							$scope.couponscat=tmp_ens;
							//console.log(tmp_ens);							
							Produit.coupons=coupons;  
							$scope.coupons=Produit.coupons;
						
							console.log('Coupons loaded...');			
						 });
							
							
						  //--Produits	
						  $scope.$on('handleProduit', function(events,produit) {								
							 search_changed=true;
							 promotion_changed=true;
							 Produit.produit=produit.produit;								 
							 Produit.enseigne=produit.enseigne;
							 Produit.version=produit.version;
							 Produit.total_special=produit.total_special;
							 Produit.categorie=produit.categorie;
							 Produit.total_regulier=produit.total_regulier;
							 Produit.availables=produit.availables;
							 Produit.availables_cat=produit.availables_cat;
							 $scope.availables=produit.availables;							
							 $scope.availables_cat=produit.availables_cat;		
							 if (isDefine(Produit.produit_promotion)) Produit.produit_promotion=produit.produit_promotion;
							 $scope.version=Produit.version;
							 $scope.produits=produit.produit;							
							 $scope.enseignes=produit.enseigne;	
							 $scope.categories=produit.categorie;							
							 $scope.total_special=produit.total_special;
							 $scope.total_regulier=produit.total_regulier;
							 $scope.special_date=[];
							 $scope.special_date[0]=produit.min_date;
							 $scope.special_date[1]=produit.max_date;							
							 if (User.loaded&&User.USER_CATEGORIES().length==0) User.USER_CATEGORIES(Produit.categories_ids);														
							 $scope.promos=Franchise.getPromotion($scope.postalCode, $scope.distance, Produit, User);									 
							 $scope.promos_categories=Franchise.categories;							
							 $scope.produit_franchise=Franchise.produit_franchise;							 
							 $scope.cached_produit=[];		
							 $('#loading_page').fadeOut("slow");
							 $scope.total_invalid=User.worker();
							 if ($scope.total_invalid>0&&$scope.old_postalCode.length>0) {
								 $("#popup_invalid_product").modal({backdrop: 'static',  keyboard: false},'show');
							 } else {
								User.removeBadPanier();
							 }
							});
 
				$scope.clearLocalStorage = function() {
					//--Clear local storate -> Need to move to util.js? - Etienne
					localStorage.clear();
				}
						
				$scope.update = function() {
                    //alert("host : " + window.location.host);
					
                    $("#loading_info").show();
					 if ($scope.lg) {						
                         //--Change site
                         var host = window.location.host.replace("mygrocerytour","circuitpromo");
                         
                         var url='http://' + host + '/index.html#!/change/'+User.SESSIONID()+"-"+User.CURRENT_PANIER()+"-fr-"+User.ADRESSE()+"-"+User.DISTANCE()+"-"+User.USER_DATABASE();
                         User.CHANGELG($http,url);
						
					 } else {						
						//--Change site
                        var host = window.location.host.replace("circuitpromo","mygrocerytour");
						var url='http://' + host + '/index.html#!/change/'+User.SESSIONID()+"-"+User.CURRENT_PANIER()+"-en-"+User.ADRESSE()+"-"+User.DISTANCE()+"-"+User.USER_DATABASE();								
						User.CHANGELG($http,url);	
					 }																					
				};
				
				
				$scope.test=function(item) {
					console.log(item);
				}
				
				$scope.viewFormPostalCode = function(){
					$scope.ask_postalCode 	= User.ADRESSE();
					$scope.ask_distance 	= parseInt(User.DISTANCE());
					$( "#form_postalCode" ).show();
				}
				
				$scope.goToSearch = function() {
						if (!isFixed) {
							$scope.go('/produit');							
						}
				}
				
				$scope.changePromotion=function() {
							//console.log("->changePromotion");
							$scope.promotion_loading=true;
							if (!isDefine($scope.postalCode)) return;
							
							if ($scope.postalCode.replace(' ','').length==6&&(promotion_codePostal!=$scope.postalCode||promotion_distance!=$scope.ask_distance)) {						
								console.log("Changing code postal from "+promotion_codePostal+"("+promotion_distance+"km) to "+$scope.postalCode+"("+$scope.ask_distance+"km)");										
										promotion_codePostal=$scope.postalCode;
										promotion_distance=$scope.ask_distance;
										Franchise.loadFranchise($http,$rootScope, $scope.postalCode);
								// if (promotion_codePostal!=$scope.postalCode) {
										// console.log("Changing code postal from "+promotion_codePostal+" to "+$scope.postalCode);
										// Franchise.loadFranchise($http,$rootScope, $scope.postalCode);
									// } else {
										// //$scope.promos=Franchise.getPromotion($http, $scope.postalCode,User.DISTANCE(), Produit, User);	
										// $scope.cached_produit=[];																			
									// }
								
							} 
						
						}
				
				 $scope.$watch('Franchise.promotion', function() {					 
					 if (!Franchise.promotion_loaded) {								
						$scope.promos=Franchise.getPromotion($scope.postalCode,User.DISTANCE(), Produit, User);							
						 $scope.promos_categories=Franchise.categories;
						$scope.produit_franchise=Franchise.produit_franchise;
					 }					 
				 }, true);
				
				//--Print view
				$scope.$watch('panier_items', function() {				
					if ($scope.print&&$scope.panier_items.length>0) {					 
						window.print();
					 }					 
				 }, true);
				
				$scope.add2Newsletter = function(){
					
					var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    				
    				if(!re.test($scope.newsletter_email)){
    					$('#newsletter_button').hide();
						$("#invalide_email_newsletter").show();
						return;
    				}
  					var postalCode = $scope.postalCode.replace(' ','');

					if( (postalCode != "")){																		
						//== validation du code postal
						$http.get('http://50.63.186.48/postalCodeExists.php?pc=' + postalCode).success(function(data){ 
							//console.log(data);
							if(data == 0){
								console.log('http://50.63.186.48/newsletter.php?action=add&lg=' + User.LANGUE() + '&email='+$scope.newsletter_email+'&pc='+postalCode);
								$http.get('http://50.63.186.48/newsletter.php?action=add&lg=' + User.LANGUE() + '&email='+$scope.newsletter_email+'&pc='+postalCode).success(function(data){
									
									console.log(data);
									if(data == 0){
										//$( "#dropdown_newsletter" ).dropdown('toggle');
										$('#newsletter_button').hide();
										$("#success_newsletter").show();
									}
									else if(data == 1){
										$('#newsletter_button').hide();
										$("#already_exist_newsletter").show();
									}
									else{
										$('#newsletter_button').hide();
										$("#internal_error_newsletter").show();
									}
								});
							}
							else{						
								$('#newsletter_button').hide();
								$("#invalide_cp_newsletter").show();
							}
						})
						.error(function(errorData, errorStatus){
							//console.log(errorData+"<>"+errorStatus);
							$("#invalide_cp_newsletter").show();
						});	
					}
				}
				
				$scope.changePostalCode = function(){										
							$scope.loading_map=true;	
							//##validation distance
							
							$scope.valid_code_and_distance=true;
							if (!isDefine($scope.ask_distance)) {
								$("#invalide_distance").show();
								$scope.loading_map=false;
								$scope.valid_code_and_distance=false;
								return;
							}
							
							
							$("#invalide_codePostal").hide();
							if( ($scope.ask_postalCode != User.ADRESSE())){																		
								//== validation du code postal
								$http.get('http://50.63.186.48/postalCodeExists.php?pc=' + $scope.ask_postalCode).success(function(data){ 
									//console.log(data);
									if(data == 0){
										$scope.old_postalCode=User.ADRESSE().toUpperCase();
										var data = new Array();
										//--Call to google BLOCKING NOT GOOD! Etienne
										//-- DO NOT USE!, Update data in the callback of handleFranchise
										// data = getLatLong($scope.ask_postalCode);
																
										User.ADRESSE($scope.ask_postalCode);
										$scope.postalCode = $scope.ask_postalCode;
										
										$( "#form_postalCode" ).hide('toggle');
										$( "#villes" ).modal('hide');
										$(".modalOverlay").remove();
										view_minimap=true;
										$scope.changePromotion();										
										$scope.showSmallMap(); //--Removed test
										$scope.valid_code_and_distance=true;
									}
									else{										
										$("#input_postalCode").css("margin-bottom: 34px");
										$("#invalide_codePostal").show();
										$scope.valid_code_and_distance=false;
										$scope.loading_map=false;
									}
								})
								.error(function(errorData, errorStatus){
									//console.log(errorData+"<>"+errorStatus);
										$("invalide_codePostal").show();
									$scope.valid_code_and_distance=false;
									$scope.loading_map=false;
								});					
							} else if ($scope.ask_distance != User.DISTANCE()) {
								User.DISTANCE($scope.ask_distance);
								$scope.distance   = $scope.ask_distance;
								view_minimap=true;
								$( "#form_postalCode" ).hide('toggle');
								$( "#villes" ).modal('hide');
								$scope.valid_code_and_distance=true;
								$(".modalOverlay").remove();									
								$scope.changePromotion();
								$scope.showSmallMap(); //--Removed test
							} else{
								$( "#form_postalCode" ).hide('toggle');
								$( "#villes" ).modal('hide');
								$scope.valid_code_and_distance=true;
								$(".modalOverlay").remove();
								$scope.loading_map=false;
							}										
				}
				
				//--Test Promotion
						$scope.cached_promo=[];
						 $scope.last_codePostal='';
						 $scope.loading=false;
						
						$scope.getEnseigne = function (id_enseigne) {
							var result = $.grep(Produit.enseigne, function(ens){ return ens.ei == id_enseigne; });							
							if (result.length>0) {
								console.log(result);
								return result[0];
							}
							return [];							
						}
						
						
						$scope.getProduit = function(produit) {								
							if (isDefine($scope.cached_produit[produit.i])) {
								return $scope.cached_produit[produit.i];
							} else {							
								$scope.cached_produit[produit.i]=Produit.getProduit(produit.i,User.LANGUE(), $http, function(data) {						
								});
								return $scope.cached_produit[produit.i];
							}
						}
						
						//--Add a product to the liste
						$scope.addProduct =function(produit) {														
							if( User.addProduct(produit)) {
								Produit.addProduct(produit);
							}
						}
						
						$scope.removeProduct =function(data, quantity_to_remove) {						
							$scope.update_panier=false;
							if (User.removeProduct(data, quantity_to_remove)) {
								
							}
						}
						
						$scope.inCurrentPanier=function(produit) {							
							if (!User.loaded) return false;
							return isDefine(User.panier[produit.i]);							
							//return User.inCurrentPanier(produit);
						}
						
						$scope.totalCurrentPanier=function(produit) {
							if ($scope.inCurrentPanier(produit)) {
								return User.panier[produit.i].quantity;
							} else {
								return 0;
							}							
							//return User.totalCurrentPanier(produit);
						}
						
						$scope.updateProduct = function (produit, quantity) {
							if (User.updateProduct(produit, quantity)) {								
							
							}
						}					
						
						//--Return the number of promotion by store to show
						$scope.getPromotionNumber = function() {							
							try {
								var w_1 = window,d_1 = document,e_1 = d_1.documentElement,g_1 = d_1.getElementsByTagName('body')[0],width = w_1.innerWidth || e_1.clientWidth || g_1.clientWidth,	height = w_1.innerHeight|| e_1.clientHeight|| g_1.clientHeight;
							} catch(e) {console.log(e);}						
							//--Note: global variable width in util.js
							if (width>1200) return 5;
							if (width>1000) return 4;
							return 3;
						}
						
						// SEARCH SEARCH SEARCH
						//--Search delai 200ms		
						//$scope.search = $scope.search_input;
						var filterTextTimeout;
						$scope.$watch('search_input', function (val) {
							if (filterTextTimeout) $timeout.cancel(filterTextTimeout);							
							filterTextTimeout = $timeout(function() {
								$scope.search = $scope.search_input;
							}, 300)
						})
						
						// MOBILE
						$scope.tm=function() {
							//$scope.link="";							
							User.MOBILE($http, Produit,'', 'email');
							$scope.link='mobile.html#!/mobile/'+User.SESSIONID()+"-"+User.CURRENT_PANIER();							
						}
						
						 $scope.$on('$viewContentLoaded', function() {							
							//--PhantomJS
							$scope.phantomJSready=true;
							
							
							
							$("#loading_info").hide();	
							$scope.url_google_static="<img src='http://maps.googleapis.com/maps/api/staticmap?center="+btn_minimap_adr+"&size=200x120&sensor=false&zoom=10'></img>"; 	
							//console.log("viewContentLoaded:"+$scope.url_google_static);
							$('.btn_minimap_adr').html( $scope.url_google_static);
							
							//if (pub_google!="")  $('#ads2').html(pub_google);
							//if (pub_google!="")  $('#ads3').html(pub_google);
							
							//console.log($route);
							if ($route.current.loadedTemplateUrl=="template/print.html") {
												//--set the flag to print
												//--the actual printing should be in a watch
												$scope.print=true;
							}
							
							if ($scope.moneymaker2!="") $('#moneymaker2').html($scope.moneymaker2);				
							//if ($scope.ads3!="") $('#ads3').html($scope.ads3);	
						});
			
		
				////////////////////////////////////////////////
				//--Show more franchise (index.html)				
				
				$scope.moreFranchiseDistance=function() {					
					var more=0;
					for (var i=0; i<$scope.promos.length; i++) {
						var promo=$scope.promos[i];
						if (promo.distanceFranchise>$scope.ask_distance) more++;
					}			
					return more;
				}
				
				
				$scope.FranchiseLimit = function() {
					//console.log($scope.promos);
					//if (!isDefine($scope.promos.displayed_franchise)) return 10;
					return Franchise.displayed_franchise;
				};
				
				$scope.hasMoreFranchiseToShow = function() {					
					return Franchise.displayed_franchise < ($scope.promos.length);
				};
				
				$scope.showMoreFranchise = function() {
					//if (!isDefine($scope.promos.displayed_franchise)) return;					
					Franchise.displayed_franchise+=10;
				};
					
			}
