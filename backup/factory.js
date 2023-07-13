/*
 * Copyright (c) 2013, 9279-5749 Québec inc and/or its affiliates. All rights reserved.
 * Etienne Lord, Alix Boc
 * PROPRIETARY/CONFIDENTIAL. Use is subject to license terms.
 */


		/*
		  Factory for Product //Ensure we have all the data
		*/
		myApp.factory('factoryLoadProduct',['$q','$http', '$rootScope','Produit','User', function ($q, $http, Produit) {
						var user = {};
						var def = $q.defer();
						// 1. Product
						return {
								loadProduit: function(ids_product_array) {

										return def.promise;
									},
								getProduit: function (id_produit, lg) {
												//console.log('In factory');
												////console.log(def);
												return def.resolve("getProduit_factory "+id_produit);
										}
								} //--End return
		}]);


		/*
		 Factory for products
		*/
		myApp.factory('factoryProduit',  ['$q','$http', '$rootScope','Produit','User',
				function ($q, $http,$rootScope,Produit,User) {
					var produit = {};
					var def = $q.defer();
					//--Produit database (cache for out of date product)
					// var produitdb=loadDB('Produit');
							  // if (produitdb!=false) {
								// //console.log('Loading produit');
								// Produit.produit_db=TAFFY(produit);
								// Produit.produit_db.store('Produit');
								// } else {
								// //console.log('Creating produit');
								// Produit.produit_db=TAFFY();
								// Produit.produit_db.store('Produit');
								// Produit.produit_db().remove();
							  // }
						Produit.loadProduit($http,$rootScope,User,def);

					return def.promise;
					}
		]);


		/*
		  Factory for langue, categorie and enseigne
		*/
		myApp.factory('factoryUser',  ['$q','$http','$rootScope', '$locale' ,'User','Franchise',
					function ($q, $http,$rootScope, $locale, User, Franchise) {
						var user = {};

						var def = $q.defer();
						// 1. Create the database

							 if(localStorage.getItem('USER_CONNECTED') == 'true')
							{
								console.log('CONNECTED');
								
							}
							else
							{
								console.log('NOT CONNECTED');
								
							}
							var panier=loadDB('Liste');
							console.log('Panier ' + loadDB('Liste'));

							 var data=loadDB('User');
							 var search=loadDB('Search');
							 var note=loadDB('Note');

							  if (data!=false) {
								//console.log('Loading user');
								User.info_db=TAFFY(data);
								User.info_db.store('User');
								User.NBVISITE(1); //--Increase the number of visit
								User.LAST_VISITED(getTimeStamp()); //--Set the number of visits
							  } else {
								 //ADD by REDA 14/08/2019
								  //console.log('Creating user');								
								  User.info_db=TAFFY();
								  User.info_db.store('User');
								  User.info_db().remove();	
								  User.info_db.insert({key:'LANGUE', value:$locale.id.substring(0,2)});
								  User.info_db.insert({key:'ADRESSE',value:'h2x3y7'});
								  User.info_db.insert({key:'PROVINCE',value:''});
								  User.info_db.insert({key:'VILLE',value:''});
								  User.info_db.insert({key:'AFFICHE', value:''});
								  User.info_db.insert({key:'DISTANCE',value:'5'});
								  User.info_db.insert({key:'LONGITUDE',value:''});
								  User.info_db.insert({key:'LATITUDE',value:''});
								  User.info_db.insert({key:'VALIDEGMAP',value:''});		
								  User.info_db.insert({key:'NBVISITE',value:0});
								  User.info_db.insert({key:'CURRENT_PANIER',value:getTimeStamp()});
								  User.info_db.insert({key:'LAST_VISITED',value:getTimeStamp()});								  
								  User.info_db.insert({key:'USER', value:''});
								  User.info_db.insert({key:'ZOOM', value:15});
								  User.info_db.insert({key:'Connected', value:''});
								  //User.info_db.insert({key:'USER_ENSEIGNES', value:{}});
								  //User.info_db.insert({key:'USER_CATEGORIES', value:{}});
								  //User.info_db.insert({key:'USER_TRIPAR', value:0});
								}
								
							  
							  Franchise.loadFranchise($http, $rootScope, User.ADRESSE());
							  //--Panier
							  
							  if (panier!=false) {
								//console.log('Loading panier');
								//console.log(panier);
								console.log('AAAAAAAAAAAAAAAAAAAAAAAAAA')
								console.log(localStorage.getItem("USER_CONNECTED"));
								User.panier_db=TAFFY(panier);
								User.panier_db.store('Liste');
								
							  	} 
							  	else {
								//console.log('Creating panier');
								User.panier_db=TAFFY();
								User.panier_db.store('Liste');
								User.panier_db().remove();

							  }
							
							  //--Search
							   if (search!=false) {
								 //console.log('Loading search');
								 User.search_db=TAFFY(search);
								 User.search_db.store('Search');
							   } else {
								 //console.log('Creating search');
								 User.search_db=TAFFY();
								 User.search_db.store('Search');
								 User.search_db().remove();
							   }
							    //--Note
							   if (note!=false) {
								 //console.log('Loading note');
								 User.note_db=TAFFY(note);
								 User.note_db.store('note');
							   } else {
								 //console.log('Creating note');
								 User.note_db=TAFFY();
								 User.note_db.store('note');
								 User.note_db().remove();
							   }
						 //1.1 Handle some user data before the ng-view
							   btn_minimap_adr=User.ADRESSE();
							   console.log("Starting postal code:"+btn_minimap_adr);
						// 2. load the langue and session id data
							$http.get('langue.json')
								.then(function(res){
								user.langue = res.data;

								$rootScope.$broadcast('handleUser',user);
								def.resolve();
								$http.get('getUser.php', {timeout: 1000}).then(function(res) {
										if (res.status==200) {
											User.SESSIONID(res.data.session_id);
											User.POST($http);
											} else {
												//console.log('Unable to get UserID');
												//--keep the old session_id
											}
										});
								});

						return def.promise;
					}


		]);

		//--Service for franchises
		myApp.factory('Franchise', ['User',function ($rootScope, $http) {
			 var data = {
				franchise: null,
				displayed_franchise:10,
				produit_franchise:0,
				minimum_franchise_distance:1,
				codePostal:'H2E2P3',
				pub_codePostal:'',
				distance:'',
				loaded: false,
				psq: false,
				pub:[],
				promotion: [],
				categories: [],
				promotionTop: [],
				google_loaded:false,
				promotion_loaded:false,
				http_b:null,
				minimap_codePostal:'',
				minimap_distance:'',
				minimap_franchise:[],
				minimap_franchise_group:[],
				minimap_map_bounds:null,
				minimap_myHomeLatLng:null,
				minimap_latitude:0,
				minimap_longitude:0,
				minimap_current_zoom:15,
				minimap_icon_set: false
			};

			///////////////////////////////////////////////////////////////
			//-- Main factory function
			data.loadFranchise = function ($http, $rootScope, codePostal) {
				var start= new Date();
				//--//console.log("loadFranchise ("+codePostal+")");
				if (codePostal==this.codePostal) {
					$rootScope.$broadcast('handleFranchise',null);
					return;
				}
				this.codePostal=codePostal;
				var that=this;
				data.http_d=$http; //--Same for googlemap

				//url: 'http://localhost/get_new_codepostal.php?adr='+codePostal+'&nocallback',
				$http({
							method: 'POST',
							crossDomain: true,
							data: {},
							headers: {'Content-Type': 'application/x-www-form-urlencoded'},
							url: '/get_new_codepostal.php?adr='+codePostal,
							crossDomain: true
							
							}).success(function(d) {
								 var end =  new Date();  // log end timestamp
								 var diff = end - start;
								//console.log("Loading franchise end "+diff+" ms");
								$rootScope.$broadcast('handleFranchise',d);
							});
			}

			////////////////////////////////////////////////////////////////
			//--Publicite
			data.getPub=function($http, $rootScope, codePostal, lg) {
				var start= new Date();
				if (codePostal==this.pub_codePostal) return;
				this.pub_codePostal=codePostal;
				var that=this;
				data.http_d=$http; //--Same for googlemap
				$http({
							method: 'GET',
							data: {},
							headers: {'Content-Type': 'application/x-www-form-urlencoded'},
							url: 'getPublicite.php?view_position=2&codePostal='+codePostal,		//= Position '2' correspond à COTÉ
							crossDomain: true
							}).success(function(d) {
								 var end =  new Date();  // log end timestamp
								 var diff = end - start;
								//isole.log('factory.js => Loading pub COTÉ ('+codePostal+') - '+diff+' ms');
								that.pub=d;
								////console.log("factory.js => data.getPub : ");
								////console.log(d);
								////console.log(that.pub.id_publicite);
								random = Math.random();
								//--See util.js
								if (!isDefine(that.pub)) {
									moneymaker_local="<a href='/index.html#!/advertise/en'><img src='images/advertise.png?random' style='cursor:hand;cursor:pointer;'></img></a>";
									local_moneymaker_set=true; //false;
									////console.log('f1');

									displayMoneymaker(2);
									return;
								}
								if (!isDefine(that.pub.image_fr)) {
									moneymaker_local="<a href='/index.html#!/advertise/en'><img src='images/advertise.png?random' style='cursor:hand;cursor:pointer;'></img></a>";
									local_moneymaker_set=true; //false;
									////console.log('f2');

									displayMoneymaker(2);
									return;
								}

								var img='';
								var id=""+that.pub.id_publicite; //--Note force text!
								////console.log(id);
								if (lg=='fr') {
									img=that.pub.image_fr;
								} else {
									img=that.pub.image_en;
								}

								moneymaker_local="<a ng-click='click("+id+");'><img src='pub/"+img+"?"+new Date()+"' style='cursor:hand;cursor:pointer;'></img></a>";
								local_moneymaker_set=true;
								////console.log(pub_local);
								displayMoneymaker(2);
							});
			/*	$http({
							method: 'GET',
							data: {},
							headers: {'Content-Type': 'application/x-www-form-urlencoded'},
							url: 'getPublicite.php?view_position=0&codePostal='+codePostal,		//= Position '0' correspond à HAUT
							crossDomain: true
							}).success(function(d) {
								 var end  = new Date();  // log end timestamp
								 var diff = end - start;
								 ////console.log('factory.js => Loading pub HAUT ('+codePostal+') - '+diff+' ms');
								 var cpt=0;
								 ////console.log(d);
								 pub_local_haut = "<table align=center><tr>";
								 //new Array("<a href='http://localhost/index.html#/advertise/en'><img src='images/advertise_haut.png' style='cursor:hand;cursor:pointer;'></img></a>",
								   //                         "<a href='http://localhost/index.html#/advertise/en'><img src='images/advertise_haut.png' style='cursor:hand;cursor:pointer;'></img></a>",
									//						"<a href='http://localhost/index.html#/advertise/en'><img src='images/advertise_haut.png' style='cursor:hand;cursor:pointer;'></img></a>");
								 for(i=0; i<d.length; i++){
								 	that.pub=d[i];
								 	////console.log("factory.js => data.getPub HAUT : ");
								 	////console.log(d[i]);
								 	//--See util.js
								 	if (!isDefine(that.pub)) {
										//pub_local_haut[cpt] = "<a href='http://localhost/index.html#/advertise/en'><img src='images/advertise.png' style='max-width:300px;max-height:600px;cursor:hand;cursor:pointer;'></img></a>";
										local_pub_haut_set=true;
										displayPub(0);
										////console.log('f1');
										return;
									}
								 	if (!isDefine(that.pub.image_fr)) {
										//pub_local_haut = new Array;
										local_pub_haut_set=true;
										displayPub(0);
										////console.log('f2');
										return;
									}
									var img='';
									var id=""+that.pub.id_publicite; //--Note force text!
									////console.log(id);
									if (lg=='fr') {
										img=that.pub.image_fr;
									} else {
										img=that.pub.image_en;
									}
									span= "span4";
									style = "text-align:center;";
									if(that.pub.format == "468x100"){
										span= "span8";
										cpt += 2;
									}
									else if(that.pub.format == "728x100"){
										span  = "span12";
										style = "text-align:center;";
										cpt += 3;
									}
									else{
										cpt += 1;
									}
									// border-style:solid;
									//pub_local_haut += "<div class='"+span+"' style='"+ style +" vertical-align:middle;'><a ng-click='click("+id+");'><img src='pub/"+img+"?"+new Date()+"' style='cursor:hand;cursor:pointer;'></img></a></div>";
									pub_local_haut += "<td><a ng-click='click("+id+");'><img src='pub/"+img+"?"+new Date()+"' style='cursor:hand;cursor:pointer;'></img></a></td>";
									local_pub_haut_set=true;
									//cpt++;
								}
								span= "span4";
								for(i=cpt; i<3; i++){
									//pub_local_haut += "<div class='"+span+"' style=''><a href='http://localhost/index.html#/advertise/en'><img src='images/advertise_haut.png' style='cursor:hand;cursor:pointer;'></img></a></div>";
									pub_local_haut += "<td><a href='/index.html#/advertise/en'><img src='images/advertise_haut.png' style='cursor:hand;cursor:pointer;'></img></a></td>";
								}
								pub_local_haut += "</tr></table>";
								////console.log(pub_local_haut);
								////console.log(angular.element($(this)).scope());
								$rootScope.ads_haut = pub_local_haut;
								//var e = $("#ads_haut").html(pub_local_haut);
								//$compile(e.contents())($scope);
								//displayPub(0);
							});		*/
			}

			//--Must be in a service other
			data.displayMiniMap = function(User, load) {
				//--This will calculate the franchise to add to the minimap
				if (!view_minimap) return [];
				var tmp_fran=[];
				var start= new Date();
					//--Is it the same data? -->Note: sometimes, it return null so better 2 call than none
					 if (this.minimap_distance==User.DISTANCE()&&this.minimap_codePostal==User.ADRESSE()) {
						 //--Send data to google function
						 displayMap();
						return;
					 }
					 //console.log("Minimap : ("+User.ADRESSE()+"-"+User.DISTANCE()+")");
					 data.minimap_distance=User.DISTANCE();
					 data.minimap_codePostal=User.ADRESSE();
					//url: 'http://localhost/getFranchiseDistance.php?dist='+this.distance+'&adr='+this.codePostal,
						data.http_d({
								method: 'POST',
								crossDomain: true,
								data: {},
								headers: {'Content-Type': 'application/x-www-form-urlencoded'},
								url: '/getFranchiseDistance.php?dist='+this.distance+'&adr='+this.codePostal,
								crossDomain: true
								//await quelque chose (REDA RENSEIGNE TOI VITE STP)
								}).success(function(d) {
									 var end =  new Date();  // log end timestamp
									 data.minimap_franchise=d;
									 var diff = end - start;
									 console.log('COUCOUCOUCOUCOUCOUCOU')
									
									displayMap();
									//console.log("Minimap: "+diff+" ms");
								});

			}
			function sleep(ms) {
 				return new Promise(resolve => setTimeout(resolve, ms));
				}

			data.getCategories = function (codePostal, distance, Produit, User) {

				if (!this.loaded) {
					return [];
				}
				if (!Produit.loaded) {
					return [];
				}

				data.categories=[];
				var hash_tmp=[]; //Valid Enseigne
				//--Validate available franchise
        //console.log("this.franchise=",this.franchise);
				for (var i=0; i<this.franchise['franchise_distance']['v'].length;i++) {
					var fran=this.franchise['franchise_distance']['v'][i];
					if (parseFloat(fran.v)<=distance) {
						hash_tmp[fran.ei]=false;
					}
				}
				for (var i=0; i<this.franchise['franchise_distance']['v'].length;i++) {
					var fran=this.franchise['franchise_distance']['v'][i];
					if (parseFloat(fran.v)<=distance) {
						hash_tmp[fran.ei]=true;
					}
				}
				//console.log("Generating categorie for ("+codePostal+" - "+distance+")");
				var start= new Date();
				var j=0;
							//var fran=this.franchise['franchise_distance']['v'][l];
							//p.e == fran.ei  -- parseFloat(fran.v)<distance
							for (var i=0; i<Produit.availables_cat.length; i++) {
										//--
										if (isDefine(Produit.availables_cat[i])) {
											var result = $.grep(Produit.produit, function(p){ return  p.c == i&&p.iv==true&&hash_tmp[p.e];});
											//--Filter by available franchise type...
											//var result2 = $.grep(result, function(p){ return  p.c == i&&p.iv==true;});
											var max_k=5;
											 if (result.length>=5) {
												var cat=$.grep(Produit.categorie, function(p){ return  p.ci == i});
												data.categories[j]={};
												data.categories[j].ci=""+i;
												data.categories[j].cne=cat[0].cne;
												data.categories[j].cnf=cat[0].cnf;
												var tmp=[];
												result.sort(function(a,b) { return b.b-a.b;});
												for (var k=0; k<max_k; k++) {
													tmp[k]=result[k];
												}
												data.categories[j].produit=tmp;
												j++;
											 }
										}
							}
				  var end =  new Date();  // log end timestamp
				  var diff = end - start;
				//console.log("Generating categories end "+diff+" ms");
			}

			data.getMinimumDistanceForFranchise=function(Produit) {
				var hash_tmp=[]; //Valid Enseigne
				if (!this.loaded) {
					return -1;
				}
				if (!Produit.loaded) {
					return -1;
				}
				if (!isDefine(this.franchise['franchise_distance'])) {
					return -1;
				}
				//var tmp=this.franchise['franchise_distance']['v'];
				//--Sort by distance
				//tmp.sort(function(a,b) { return a.v-b.v;});
				for (var i=0; i<this.franchise['franchise_distance']['v'].length;i++) {
						var fran=this.franchise['franchise_distance']['v'][i];
						var distance=parseFloat(fran.v);
						if (isDefine(Produit.availables[fran.ei])) {
							if (Produit.availables[fran.ei]>0) {
								data.mminimum_franchise_distance=Math.ceil(distance)+1;
								return Math.ceil(distance)+1;
							}
						}
				}
				data.mminimum_franchise_distance=0;
				return 0;
			}

			data.getPromotion = function (codePostal, distance, Produit, User) {
				// console.log("codePostal", codePostal, "distance", distance, "Produit", Produit, "User", User) //brandon
        //console.log("getPromotion...");
				//console.log(codePostal);
				if (!this.loaded) {
					//console.log('getPromotion -> Franchise not loaded');
					return [];
				}
				if (!Produit.loaded) {
					//console.log('getPromotion -> Produit not loaded');
					return [];
				}
				//if (!isDefine(Produit.produit_promotion)) {
				//	return [];
					////console.log(Produit.produit_promotion);
					////console.log("Promotion loaded: "+Object.keys(Produit.produit_promotion).length);
				//}
				//--Generate the categorie at the same time
				data.getCategories(codePostal, distance, Produit, User);
				//console.log("getCategories...");
				//--Test
				//data.getMinimumDistanceForFranchise(Produit);
				//console.log("Generating promotion for ("+codePostal+" - "+distance+")");
				var hash_tmp=[]; //Enseigne
				var start= new Date();
				data.distance=distance;
				data.displayed_franchise=10; //--Reset max franchise (index.html)
				var j=0;
				//--Iterating to get all id_enseigne in the distance vol
				var tmp_ens=[];
        // Update by Nadia Tahiri le 10 April 2018
        // Initialisation data.produit_franchise to 0
				data.produit_franchise = 0;

					//console.log("Looking for the promotion....");
					//--Verify the promotion for this user
          // Update by Nadia Tahiri 30 April 2018
					// for (var i=0; i<data.franchise['franchise_distance']['v'].length;i++) {
					for (var i=0; i<data.franchise['franchise'].length;i++) {
						// Update by Nadia Tahiri 23 April 2018
						// condition sur la distance

						// if(data.franchise['franchise_distance']['v'][i].en == "logo_uniprix.jpg"){
						// 	//console.log(data.franchise['franchise_distance']['v'][i]);
						// }

            // Update by Nadia Tahiri 30 April 2018
						// if (parseFloat(data.franchise['franchise_distance']['v'][i].v)<=distance){
						if (parseFloat(data.franchise['franchise'][i].v)<=distance){

							// Update by Nadia Tahiri 30 April 2018
							// var fran=data.franchise['franchise_distance']['v'][i];
							var fran=data.franchise['franchise'][i];
							//--parseFloat(fran.v)<distance&&
							if (!isDefine(hash_tmp[fran.ei])) {
								if (isDefine(Produit.availables[fran.ei])) {
									data.produit_franchise+=Produit.availables[fran.ei];
								}

								//--Process this franchise for the user
								//console.log("Processing "+fran.ei+" "+fran.en);
								var max_date=0;
								var min_date=99999999999999;
								var tmp={};
	              // update fran.el by fran.en by Nadia Tahiri 4 april 2018
								tmp['imageFranchise']="images/rs_"+fran.en;
								tmp['nomFranchise']=fran.en;
								tmp['adresseFranchise']=fran.a;
								tmp['ei']=fran.ei;
								tmp['distanceFranchise']=Math.round(fran.v*100)/100
								tmp['listeProduits']=[];
								hash_tmp[fran.ei]=1;

								var i=0;
								//--Now look for promotion
								if (isDefine(Produit.produit_promotion)) {
									//console.log("Predefine promotion loading...");
									if (isDefine(Produit.produit_promotion[fran.ei])&&isDefine(Produit.availables[fran.ei])) {
										for (key in Produit.produit_promotion[fran.ei]) {
											var id_produit=Produit.produit_promotion[fran.ei][key];
											var result = $.grep(Produit.produit, function(p){ return  p.i == id_produit});
											if (result.length>0&&i<5) {
												tmp['listeProduits'][i++]=result[0];
												if (result[0].d>max_date) max_date=result[0].d;
												if (result[0].d<min_date&&result[0].d!=0) min_date=result[0].d;
											}
										}

										//--Bad but fast
										if (i<5) {
											//--Complete the set with a selection
											var result = $.grep(Produit.produit, function(p){ return  p.e == fran.ei&&p.iv==true});
											if (isDefine(result)&&result.length>=20) {
												result.sort(function(a,b) { return b.b-a.b;});
												for (var o=0;o<100;o++) {
													if (i<5) {
														if ($.grep(tmp['listeProduits'], function(p){ return  p.i == result[o].i}).length<1) {
															tmp['listeProduits'][i++]=result[o];
															if (result[o].d>max_date) max_date=result[o].d;
															if (result[o].d<min_date&&result[o].d!=0) min_date=result[o].d;
														}
													}
												}
											}
										}
										if (i>0) {
											tmp['max_date']=max_date;
											tmp['min_date']=min_date;
											tmp_ens[j++]=tmp;
										}
									} //--End isDefine Produit avail
								} //--End produit_promotion
							}
						}
					}
					//console.log("getCategories...");

					//--Get the promotions by franchise - complete if we have no set special for
					//-- this franchise...

					// Update by Nadia Tahiri 30 April 2018
					// for (var i=0; i<data.franchise['franchise_distance']['v'].length;i++) {
					for (var i=0; i<data.franchise['franchise'].length;i++) {
						// Update by Nadia Tahiri 23 April 2018
						// condition sur la distance
						// Update by Nadia Tahiri 30 April 2018
						// if (parseFloat(data.franchise['franchise_distance']['v'][i].v)<=distance){
						if (parseFloat(data.franchise['franchise'][i].v)<=distance){
							// Update by Nadia Tahiri 30 April 2018
							// var fran=data.franchise['franchise_distance']['v'][i];
							var fran=data.franchise['franchise'][i];
							//--parseFloat(fran.v)<distance&&
							if (!isDefine(hash_tmp[fran.ei])) {
								var tmp={};

								if (isDefine(Produit.availables[fran.ei])) {
									data.produit_franchise+=Produit.availables[fran.ei];
								}
								// Update el by en by Nadia Tahiri 4 April 2018
								tmp['imageFranchise']="images/rs_"+fran.en;
								tmp['nomFranchise']=fran.en;
								tmp['adresseFranchise']=fran.a;
								tmp['ei']=fran.ei;
								tmp['distanceFranchise']=Math.round(fran.v*100)/100
								tmp['listeProduits']=[];
								hash_tmp[fran.ei]=1;
								//&&parseInt(p.b)>30 &&p.iv==true
								var result = $.grep(Produit.produit, function(p){ return  p.e == fran.ei});
								var max_date=0;
								var min_date=99999999999999;
								var tmp_category=[]; //--Displayed category
								if (isDefine(result)&&result.length>0) {
									result.sort(function(a,b) { return b.b-a.b;});

									//get the first 5 but only if they are in diff. category
									var max_k=result.length;
									var k=0; //--Iterateur
									var num_k=0; //number of k found (max 5)
									//-- Only get if result >5
									if (result.length>=5) {
										max_k=result.length;
										while(num_k<5&&k<max_k) {
											if (!isDefine(tmp_category[result[k].c])) {
												tmp_category[result[k].c]=1;
												tmp['listeProduits'][num_k]=result[k];
												if (result[k].d>max_date) max_date=result[k].d;
												if (result[k].d<min_date&&result[k].d!=0) min_date=result[k].d;
												num_k++;
											}
											k++;
										}

										tmp['max_date']=max_date;
										tmp['min_date']=min_date;
										if (this.psq&&fran.ei==73) {
											tmp_ens.unshift(tmp);
										} else {
											tmp_ens[j++]=tmp;
										}
									}
								}

							}
						}
					}

				//-Etienne //console.log(Franchise.franchise);
				data.promotion=tmp_ens;

				//--Post the promotion array for caching...
				//Franchise.POSTPROMOTION(tmp_ens);
				 var end =  new Date();  // log end timestamp
				 var diff = end - start;
				data.promotion_loaded=true;
				//console.log("Generating promotion end "+diff+" ms");
				console.log("Data promotions", data.promotion);
				 return data.promotion;
			}

			// data.POSTPROMOTION = function(promo) {
				// var  post=new Object();
				// post.session=User.SESSIONID();
				// post.time=getTimeStamp();
				// post.user=getObject('taffy_User');
				// post.promotion=promo;
				// post.id_database=User.USER_DATABASE();
				// data.http_d({
					// method: 'POST',
					// url: "http://localhost/promotion.php?id_database="+post.id_database+"&data",
					// data: post,
					// headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				  // }).success(function(data){
						// //console.log(data);
						// //console.log(promo);
				  // });
			// }

			return data;
		}]);

		//--Service for langue
		myApp.service('Langue', [function () {
			var data = {
				val: '', loaded: false, current: null
			};
			return {
				langue:data
			};
		}]);

		//--Service for produit
		myApp.service('Produit', ['User',function () {
			var data = {
				val: '',
				version:0,
 				enseigne:[],
				categorie:[],
				availables:[],
				availables_cat:[],
				loaded: false,
				server_retry:0,
				produit_db:null,
				produit_promotion:[],
				url:'',
				coupons:[],
				cached_get:[]
			};

			//--Si le produit n'est pas trouvé
			var dummy_produit={i:0,c:1,r:0.0,s:0.0,b:0,d:getTimeStamp(), e:0,x:'Ce produit est indisponible | This product is no more available',z:'',w:'', q:'',iv:false,eb:'', y:'', en:0, pv:false};

				//--This transform a produit return from server (e)
			//--to the current representation
			data.transformProduit = function(e) {
					var cnf="",cne="",ent="data",el="";
					//--No data
					if (e.length==0) {
						var tmp={};
						$.extend(tmp, dummy_produit);
						return tmp;
					}
					//console.log(e);
					var search_string=(removeDiacritics(e[7]+' '+e[8]+' '+e[9])+' '+e[10]).toLowerCase();
					if (parseFloat(e[2])!=parseFloat(e[3])) total_special++;
					//--Find enseigne
					var result = $.grep(this.enseigne, function(ens){ return ens.ei == e[6]; });
					if (isDefine(result)&&result.length>0) {
						ent=result[0].en;
						el=result[0].el;
					}
					var eb=parseFloat(e[4]);
					if (!isDefine(eb)) eb=0.0;
					return {i:e[0],c:e[1],r:parseFloat(e[2]),s:parseFloat(e[3]),b:eb,d:e[5], e:e[6],x:e[7],z:e[8],w:e[9], q:e[10],iv:e[11],eb:e[12],pv:e[13], y:search_string, en:ent, nf:false};
			}

			//--Add produit to the produit database for caching at userside
			//--Deprecated for now
			data.addProduct = function(produit) {
					// if (this.produit_db({i:produit.i}).get().length>0) {
						// //--Already in database of old product...
					// } else {
						// // var result = $.grep(this.produit, function(e){ return e.i == produit.i; });
						// // if (isDefine(result)&&result.length>0) {
							// // this.produit_db.insert(result[0]);
						// // }
					// }
			}

			data.transform_product=function(e, lg) {
					var search_string="";
					var ent="";
					var el="";
					if (parseFloat(e[2])!=parseFloat(e[3])) total_special++;
					var eb=parseFloat(e[4]);
					if (!isDefine(eb)) eb=0.0;
					//--Find enseigne
						var result = $.grep(this.enseigne, function(ens){ return ens.ei == e[6]; });
						if (isDefine(result)&&result.length>0) {
							ent=result[0].en;
							el=result[0].el;
						}
					if (lg=='fr') {
						search_string=(removeDiacritics(e[7]+' '+e[8]+' '+e[9])+' '+e[10]).toLowerCase();
						return {i:e[0],c:e[1],r:parseFloat(e[2]),s:parseFloat(e[3]),b:eb,d:e[5], e:e[6],x:e[7],z:e[8],w:e[9], q:e[10],iv:e[11],eb:e[12],pv:e[13], y:search_string, en:ent, nf:false};
					} else {
						search_string=(removeDiacritics(e[14]+' '+e[15]+' '+e[16])+' '+e[17]).toLowerCase();
						return {i:e[0],c:e[1],r:parseFloat(e[2]),s:parseFloat(e[3]),b:eb,d:e[5], e:e[6],x:e[14],z:e[15],w:e[16], q:e[17],iv:e[11],eb:e[12],pv:e[13], y:search_string, en:ent, nf:false};
					}
			}

			//--Get the produit from the server or local
			//--If server, set the flag nf
			data.getProduit =function(id_produit, database, lg, $http, callback) {
					var that=this;
					//--We already have an object...
					if (!isDefine(id_produit)) return {};
					if (isDefine(id_produit.i)) return id_produit; //hack
					if (!this.loaded) return {};
					if (!isDefine(User)) {
						var tmp={};
						$.extend(tmp, dummy_produit);
						tmp.i=id_produit;
						tmp.nf=true;
						//this.produit.push(tmp);
						return tmp;
					};


					if (this.server_retry>3) {
						//console.log('Server retry:'+this.server_retry);
						var tmp={};
						//--November 2013
						//$.extend(tmp, dummy_produit);
						tmp.i=id_produit;
						//this.produit.push(tmp);
						tmp.nf=true;
						return tmp;
					}
					var result = $.grep(this.produit, function(e){ return e.i == id_produit; });
					if (isDefine(result)&&result.length>0) {
						//if (result.length!=1) //console.log("*"+result.length);
						return result[0];
					} else if (isDefine(lg)) {
						//--Grep in this



						//--Look into olld product

						//--Is it in the cached product?
						//--Deprecated
						// var result=this.produit_db({i:id_produit}).get();
						// if (result.length>0) {
							// var tmp=result[0];
							// tmp.nf=true;
							// return tmp;
						// }

						//--Ajax call to server
						// var that=this;
						// if (this.cached_get.indexOf(id_produit)>-1) {
							// //--call in progress....
							// var tmp={};
							// $.extend(tmp, dummy_produit); //--7 October 2013
							// tmp.i=id_produit;
							// tmp.nf=true;
							// return tmp;
						// }
						// this.cached_get.push(id_produit);
							// Call

						 // $http.get('getProduct.php?db='+database+'&lg='+lg+'&id_produit='+id_produit)
							 // .success(function (data) {
									 // var tmp=that.transformProduit(data);
									 // tmp.i=id_produit;
									 // tmp.nf=true;
									 // that.produit.push(tmp);
									 // callback(tmp);
									 // return that.produit[that.produit.length-1];
							 // }).error(function(data,err) {
								 // //console.log(this.server_retry);
								 // this.server_retry++;
							 // });
						//result=User.getOLDPRODUCT($http, id_produit, lg);
							var this_product=[];
							var products=[];
							if (isDefine(User)) {
								products=User.OLDPRODUCT();
							}
							////console.log('Cache:'+id_produit);
							//1. Try if found in cache
							if (isDefine(products)&&products.length>0) {
								var result = $.grep(products, function(p){ return p[0] == id_produit; });
								if (isDefine(result)&&result.length>0) {
									////console.log('Found in cached:'+id_produit);
									var tmp=data.transform_product(result[0], lg);;
									tmp.nf=true;
									that.produit.push(tmp);
									callback(tmp);
									return that.produit[that.produit.length-1];
								}
							}
							if (!isDefine(User)) {

							}

							//2. Not found, try to download
							if (!User.old_product_downloaded) {
								//console.log('Downloading for:'+id_produit);
								User.old_product_downloaded=true;
								var  post=new Object();
								post.session=User.SESSIONID();
								post.time=getTimeStamp();
								post.adr=User.ADRESSE();
								post.panier=getObject('taffy_Liste');
								post.user=getObject('taffy_User');
								post.id_database=User.USER_DATABASE();
								try {
									var url="/getproduct3.php";
									$http({
										method: 'POST',
										url: url,
										data: post,
										headers: {'Content-Type': 'application/x-www-form-urlencoded'}
									}).success(function(d) {
										////console.log('Updating all panier done');
										products=d;
										//--Update this cached products...
										if (products.length>0) {
											var p=User.OLDPRODUCT();
											if (isDefine(p)&&p.length>0) {
												for (var i=0;i<products.length;i++) {
													var np=products[i];
													if ($.grep(p, function(i){ return p[0] == np[0]; }).length==0) {
														p.push(np);
													}
												}
												User.OLDPRODUCT(p);
											} else {
												User.OLDPRODUCT(products);
												//console.log('Updating cache');
											}

											var result = $.grep(products, function(p){ return p[0] == id_produit; });
											if (isDefine(result)&&result.length>0) {
												var tmp=data.transform_product(result[0], lg);;
												tmp.nf=true;
												that.produit.push(tmp);
												callback(tmp);
												return that.produit[that.produit.length-1];
											} else {
												$http.get('/getproduct3.php?&i='+id_produit)
												 .success(function (new_data) {
														if (new_data.length>0) {
															var p=User.OLDPRODUCT();
															p.push(new_data);
															User.OLDPRODUCT(p);
                              // Clean by Nadia Tahiri 10 April 2018 ;; -> ;
															 // var tmp=data.transform_product(new_data, lg);;
															 var tmp=data.transform_product(new_data, lg);
															 tmp.i=id_produit;
															 tmp.nf=true;
															 that.produit.push(tmp);
															 callback(tmp);
															 return that.produit[that.produit.length-1];
														} else {
															//--Really not found
															var tmp={};
															$.extend(tmp, dummy_produit);
															tmp.i=id_produit;
															tmp.nf=true;
															that.produit.push(tmp);
															callback(tmp);
															return that.produit[that.produit.length-1];
														}
												});
											}
										} else {
											this.server_retry++;
										}
									});
								} catch(E) {this.server_retry++;}
							} else {
								$http.get('/getproduct3.php?&i='+id_produit)
								 .success(function (new_data) {
										if (new_data.length>0) {
											var p=User.OLDPRODUCT();
											if (!isDefine(p)) {
												p=[];
											}
											p.push(new_data);
											User.OLDPRODUCT(p);
											var tmp=data.transform_product(new_data, lg);;
											 tmp.i=id_produit;
											 tmp.nf=true;
											 that.produit.push(tmp);
											 ////console.log(tmp);
											 callback(tmp);
											 return that.produit[that.produit.length-1];
										} else {
											//--Really not found
											var tmp={};
											$.extend(tmp, dummy_produit);
											tmp.i=id_produit;
											tmp.nf=true;
											that.produit.push(tmp);
											////console.log(tmp);
											callback(tmp);
											return that.produit[that.produit.length-1];
										}

								});
							}
					    // if (result.length>0) {
							// var tmp=result;
							// tmp.nf=true;
							// that.produit.push(tmp);
							// callback(tmp);
							// return that.produit[that.produit.length-1];
						// } else {
							// this.server_retry++;
						// }

					}
					//--Final return if not found
					//--> We now remove from this version of the backet...
					// //console.log('No product...'+id_produit);
					var tmp={};
					$.extend(tmp, dummy_produit);
					tmp.i=id_produit;
					tmp.nf=true;
					//this.produit.push(tmp);
					return tmp;
				}

			data.getCategorie =function(id_categorie) {
					var result = $.grep(this.categorie, function(cat){ return cat.ci == id_categorie; });
					if (isDefine(result)&&result.length>0) {
						return result[0];
					}
					return [];
				}


			data.loadProduit =function($http,$rootScope,User,def) {
						var that=this;

						that.loaded=false;
						$http.defaults.useXDomain=true;
						var database=User.USER_DATABASE();
						var lg=User.LANGUE();
						var promotion_url="promotion_"+database+".json";
						$http.get(promotion_url).then(function(res){
									this.produit_promotion = res.data;
									//console.log("Loading "+promotion_url);
						});
						$http.get('enseigne.json')
									.then(function(res){
										//produit.enseigne = res.data;
									this.enseigne= res.data;
									$http.get('categorie.json')
										.then(function(res){
											//produit.categorie = res.data;
											this.categorie=res.data;
											this.categories_ids=[];
											that.categorie=res.data;
											that.categories_ids=[];
											for (var i=0; i<this.categorie.length;i++) {
												var c=this.categorie[i];
												//this.categories_ids.push(c.ci);
												this.categories_ids[i]=c.ci;
											}
											//var id=User.PRODUCTID();
											var id="";
											var url='getProduct.php?db='+database+'&lg='+lg;
												$http.get(url)
													.then(function(res){
														var start= new Date();
																var tmp=[];
																//console.log("insert begin");
																var total_special=0;
																var min_date=999999999999999;
																var max_date=0;
																this.availables=[];
																this.availables_cat=[];
																that.availables=[];
																that.availables_cat=[];
																//console.log("Product ("+res.data.length+") "+ url);
																////console.log(res.data);
																//--Note: the first product now contain a version number and info
																//--This will be used to update the product on demand...
                                // Add condition of res.data.length>0 by Nadia Tahiri 29 March 2018, sometimes they are mistake when res.data.length==0
                                if (res.data.length>0){
	                                this.version_info=res.data[0];
																	data.version=res.data[0].version;
																	this.version=data.version;
																	var count_remove=0;
																	for (var i=1; i<res.data.length;i++) {
																			var e=res.data[i];
																			//--Find categorie
																			//--nf: not found
																			//--en:enseigne
																			//--y: search_string
																			var cnf="";
																			var cne="";
																			var ent="data";
																			var el="";
																			var search_string=(removeDiacritics(e[7]+' '+e[8]+' '+e[9])+' '+e[10]).toLowerCase();
																			if (parseFloat(e[2])!=parseFloat(e[3])) total_special++;
																			/*var result = $.grep(produit.categorie, function(cat){ return cat.ci == e[1]; });
																			if (isDefine(result)&&result.length>0) {
																				cnf=result[0].cnf;
																				cne=result[0].cne;
																			}*/

																			//--Find enseigne
																			var result = $.grep(this.enseigne, function(ens){ return ens.ei == e[6]; });
																			if (isDefine(result)&&result.length>0) {
																				ent=result[0].en;
																				el=result[0].el;
																			}
																			if (!isDefine(this.availables[e[6]])) {
																				this.availables[e[6]]=1;
																			} else {
																				this.availables[e[6]]=this.availables[e[6]]+1;
																			}

																			if (e[5]<min_date) min_date=e[5];
																			if (e[5]>max_date) max_date=e[5];

																			var eb=parseFloat(e[4]);
																			if (!isDefine(eb)) {
																				eb=0.0;
																			}

																			//tmp.push({i:e[0],c:e[1],r:parseFloat(e[2]),s:parseFloat(e[3]),b:eb,d:e[5], e:e[6],x:e[7],z:e[8],w:e[9], q:e[10],iv:e[11],eb:e[12],pv:e[13], y:search_string, en:ent, nf:false});
																			tmp[i-1]={i:e[0],c:e[1],r:parseFloat(e[2]),s:parseFloat(e[3]),b:eb,d:e[5], e:e[6],x:e[7],z:e[8],w:e[9], q:e[10],iv:e[11],eb:e[12],pv:e[13], y:search_string, en:ent, el:el, nf:false};
																			//--Remove rebate

																			 if (e[13]==false) {
																				// //console.log(e[13]);
																				// count_remove++;
																				 //--Remove false special...
																				 tmp[i-1].b=0;
																			}
																			//--Categorie
																			//--Also create the view for the categorie
																			if (!isDefine(this.availables_cat[e[1]])) {
																				this.availables_cat[e[1]]=1;
																			} else {
																				this.availables_cat[e[1]]=this.availables_cat[e[1]]+1;
																			}

																	 }
                               }
																 //console.log("remove "+count_remove);
																 //--Array for availables_cat
																// var t=[];
																// var i=0;
																// for (var key in availables_cat) {
																	// t[i++]={'id':key, 'total':parseInt(availables_cat[key])};
																// }
																 //this.availables_cat=t;
																 this.total_special=total_special;
																 this.total_regulier=tmp.length-this.total_special;
																 this.min_date=min_date;
																 this.max_date=max_date;
																 var end =  new Date();  // log end timestamp
																 var diff = end - start;
																//console.log("insert end "+diff+" ms");
																this.produit=tmp;
																var ids_produit=User.ALLPRODUCTID();
																var lg=User.LANGUE();
																//--Async load coupon
																$http.get('coupons.json')
																.then(function(res){
																	var coupon=res.data;
																	$rootScope.$broadcast('handleCoupons',coupon);
																});
																//--Continue loading product
																for (var i=0; i<ids_produit.length;i++) that.getProduit(ids_produit[i], database, lg, $http, function(){});
																//--Download promotions - Etienne 2014
																//console.log(this.availables);
																that.loaded=true;
																$rootScope.$broadcast('handleProduit',this);
																if (isDefine(def)) 	def.resolve();

													});
											});
						});
				} //--End loadProduit function

			return data;
		}]);

		//--Service for user data including the Panier
		//	that.produit_db.insert(tmp);
		myApp.service('User', ['$rootScope',function () {
				var data = {
				val: '',
				loaded: false,
				panier_db:null,
				info_db:null,
				search_db:null,
				note_db:null,
				panier:[],
				old_product_downloaded:false
			};
			//panier hash for speed--Hash
			//////////////////////////////////////////////////////
			// Panier (Liste)
			//User.addProduct(id_produit);
			data.addProduct = function (produit, quantity) {
				if (!this.loaded) return false;
				//--Look if we have the produit in the current list
					var newitem={};
					if (!isDefine(quantity)) {
						quantity=1;
					}
					newitem.quantity=quantity;  //--quantity
					newitem.i=produit.i; //--id_produit
					newitem.s=produit.s; //--prix_special
					newitem.r=produit.r; //--prix_special
					newitem.e=produit.e; //--id_enseigne
					newitem.d=getTimeStamp(); //--Time added
					newitem.c=data.CURRENT_PANIER(); //--Panier
					if (this.panier_db({i:produit.i,c:newitem.c}).get().length>0) {
						var item=this.panier_db({i:produit.i,c:newitem.c}).get()[0];
						newitem.quantity=parseInt(item.quantity)+1;
						this.panier_db({i:produit.i,c:newitem.c}).update({quantity:newitem.quantity, d:getTimeStamp()});
						if(localStorage.getItem("USER_CONNECTED") == "true"){

						taf = JSON.parse(localStorage.getItem('taffy_Liste'));
						var ind = 0; 
						for (var i in taf)
						{
							if (taf[i].i == newitem.i && taf[i].c == newitem.c)
							{
								ind = i;
							}
						}
						taf[ind].quantity = newitem.quantity;
						//console.log(ind);
						taf_usr = JSON.stringify(taf);
						//console.log(taf_usr);
						var sendData = function(){
							$.post('../../testAPI.php',{
								taffyy : taf_usr
							}, function(response){
							return response;
							});
						}
						sendData(); 
						}
					} else {
						this.panier_db.insert(newitem);
						if(localStorage.getItem("USER_CONNECTED") == "true"){
						taf = JSON.parse(localStorage.getItem('taffy_Liste'));
						//console.log(newitem);
						taf.push(newitem);
						taf_usr = JSON.stringify(taf);
						//console.log(taf_usr);
						var sendData = function(){
							$.post('../../testAPI.php',{
								taffyy : taf_usr
							}, function(response){
								return response;
							});
						}
						sendData(); 
						var addPanier = function(){
							var cur = User.CURRENT_PANIER();
							$.post('../../testAPI.php',{
								current : cur
							});
						}
						addPanier();
						}

					}
					this.getHash();//---Build cache
				return true;
			}

			// This return either
			// 1 - if the product is valid
			// 2 - if the product is old
			// 0 - if its invalid
			data.is_valid = function(id_produit) {
				var result = $.grep(produit, function(e){ return e.i == id_produit; });
				if (!isDefine(result)) return 0;
				if (result.length==0) return 0;
				if (result[0].nf) return 2;
				return 1;
			}

			/////////////////////////////////////////////////
			// Panier worker
			// This function verify if all the product in the
			// panier are too old, if so, it will create a
			// new panier.
			data.worker = function() {
				if (!this.loaded) return 0;
				var total_item=0;
				var total_valid=0;
				if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
					//console.log("worker...");
					var data_panier=this.panier_db({c:parseInt(id_panier)}).get();
					try {
						if (isDefine(data_panier)) {
							total_item=data_panier.length;
							for (var i=0; i< data_panier.length; i++) {
									var item=data_panier[i];
									if (data.is_valid(item.i)>0) {
										total_valid++;
									} else {
										//data.removeProduct(item, 999);
									}
							}
						}
					} catch(E) {}
				return total_item-total_valid;
			}

			data.removeBadPanier = function() {
				if (!this.loaded) return false;
				if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
					var data_panier=this.panier_db({c:parseInt(id_panier)}).get();
						for (var i=0; i< data_panier.length; i++) {
								var item=data_panier[i];
								if (data.is_valid(item.i)==0) {
									//data.removeProduct(item);
								}
						}
				return 0;
			}

			data.updateProduct = function (produit, quantity) {
				if (!this.loaded) return false;
				if (quantity==0) return this.removeProduct(produit);
				//--Look if we have the produit in the current list
					var newitem={};
					newitem.quantity=quantity;  //--quantity
					newitem.i=produit.i; //--id_produit
					newitem.s=produit.s; //--prix_special
					newitem.r=produit.r; //--prix_regulier
					newitem.e=produit.e; //--id_enseigne
					newitem.c=data.CURRENT_PANIER(); //--Panier
					if (this.panier_db({i:produit.i,c:newitem.c}).get().length>0) {
						this.panier_db({i:produit.i,c:newitem.c}).update({quantity:newitem.quantity, d:getTimeStamp()});
					}
					this.getHash(); //---Build cache
				return true;
			}

			data.removeProduct =function(produit, quantity_to_remove) {
				if (!this.loaded) return false;
				var id_panier=data.CURRENT_PANIER();
				//--Note: this allow a final quantity of 0
				if (isDefine(quantity_to_remove)) {
					var quantity=this.totalCurrentPanier(produit)-quantity_to_remove;
					if (quantity<=0||quantity_to_remove==999) {
						//--Change to <0 to allow a final quantity of 0
							this.panier_db({i:produit.i,c:this.CURRENT_PANIER()}).remove();
							taf = JSON.parse(localStorage.getItem('taffy_Liste'));
							var ind = 0; 
							for (var i in taf)
							{
								if (taf[i].i == produit.i && taf[i].c == this.CURRENT_PANIER())
								{
									ind = i;
								}
							}
							taf.splice(ind, 1);
							taf_usr = JSON.stringify(taf);
							//console.log(taf_usr);
							var sendData = function(){
								$.post('../../testAPI.php',{
									taffyy : taf_usr
								}, function(response){
								return response;
								});
							}
							sendData();

							this.getHash(); //---Build cache
						return true;
					} else {
						var newitem={};
						newitem.quantity=quantity;  //--quantity
						newitem.i=produit.i; //--id_produit
						newitem.s=produit.s; //--prix_special
						newitem.r=produit.r; //--prix_regulier
						newitem.e=produit.e; //--id_enseigne
						newitem.c=id_panier; //--Panier
						if (this.panier_db({i:produit.i,c:newitem.c}).get().length>0) {
							this.panier_db({i:produit.i,c:newitem.c}).update({quantity:newitem.quantity, d:getTimeStamp()});
							taf = JSON.parse(localStorage.getItem('taffy_Liste'));
							var ind = 0; 
							for (var i in taf)
							{
								if (taf[i].i == newitem.i && taf[i].c == newitem.c)
								{
									ind = i;
								}
							}
							taf[ind].quantity = newitem.quantity;
							//console.log(ind);
							taf_usr = JSON.stringify(taf);
							//console.log(taf_usr);
							var sendData = function(){
								$.post('../../testAPI.php',{
									taffyy : taf_usr
								}, function(response){
								return response;
								});
							}
							sendData();

						}
						this.getHash(); //---Build cache
					return true;
					}
				}
				if (this.panier_db({i:produit.i, c:id_panier}).get().length>0) {
					this.panier_db({i:produit.i,c:id_panier}).remove();
					taf = JSON.parse(localStorage.getItem('taffy_Liste'));
							var ind = 0; 
							for (var i in taf)
							{
								if (taf[i].i == produit.i && taf[i].c == this.CURRENT_PANIER())
								{
									ind = i;
								}
							}
							taf.splice(ind, 1);
							//console.log(taf);
							taf_usr = JSON.stringify(taf);
							//console.log(taf_usr);
							var sendData = function(){
								$.post('../../testAPI.php',{
									taffyy : taf_usr
								}, function(response){
								return response;
								});
							}
							sendData();
				}
				this.getHash(); //---Build cache
				return true;
			}


			data.total = function(id_panier) {
				if (!this.loaded) return 0;
					if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
					var ttotal=0;
					var data=this.panier_db({c:parseInt(id_panier)}).get();
						for (var i=0; i< data.length; i++) {
								var item=data[i];
								ttotal=ttotal + (parseFloat(item.s) * parseInt(item.quantity));
						}
					return ttotal;
				}

			data.totalSansSpecial = function(id_panier) {
				if (!this.loaded) return 0;
					if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
					var ttotal=0;
					var data=this.panier_db({c:parseInt(id_panier)}).get();
						for (var i=0; i< data.length; i++) {
								var item=data[i];
								ttotal=ttotal + (parseFloat(item.r) * parseInt(item.quantity));
						}
					return ttotal;
				}

			data.totalquantity = function (id_panier) {
				if (!this.loaded) return 0;
					if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
					var ttotal=0;
					var data=this.panier_db({c:parseInt(id_panier)}).get();
					for (var i=0; i<data.length;i++) {
							var item=data[i];
							ttotal=ttotal + parseInt(item.quantity);
					}
				return ttotal;
			}

			data.removePanierEnseigne=function (Produit, id_enseigne) {
				if (!this.loaded) return false;
				var id_panier=this.CURRENT_PANIER();
				var result=this.panier_db({c:parseInt(id_panier)}).get();
				if (result.length>0) {
					for (var i=0; i<result.length;i++) {
						//--get this product
						var prod = $.grep(Produit.produit, function(p){ return p.i == result[i].i; });
						if (prod[0].e==id_enseigne) {
							this.panier_db({i:prod[0].i,c:id_panier}).remove();
						}
					}
				}
				return true;
			}

			//--Return item in selected panier
			data.getItems = function (id_panier) {
				var tmp=[];
				if (!this.loaded) return tmp;
				if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
				if (id_panier=='') return tmp;
				//var data=this.panier_db({c:panier}).order("d desc").get();
				var data=this.panier_db({c:parseInt(id_panier)}).order("d desc").get();
				for (var i=0; i<data.length;i++) {
						var item=data[i];
							tmp[i]=item;
					}
				return tmp;
			}

			//--Use the hash instead
			data.inCurrentPanier  = function  (produit,id_panier) {
				if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
				var result=this.panier_db({c:parseInt(id_panier), i:produit.i}).order("d desc").get();
				if (result.length>0&&result[0].quantity>0) {
					return true;
				}
				return false;
			}


			data.totalCurrentPanier  = function  (produit,id_panier) {
				if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
				var result=this.panier_db({c:parseInt(id_panier), i:produit.i}).get();
				if (result.length>0) {
					return result[0].quantity;
				}
				return 1; //--Note: needed for placeholder
			}

			data.savingCurrentPanier  = function  (id_panier) {
					if (!this.loaded) return 0;
					if (!isDefine(id_panier)) id_panier=this.CURRENT_PANIER();
					var ttotal=0;
					var data=this.panier_db({c:parseInt(id_panier)}).get();
					for (var i=0; i<data.length;i++) {
							var item=data[i];
							ttotal=ttotal +  (parseFloat(item.r)-parseFloat(item.s)) * parseInt(item.quantity);
					}
				return ttotal;
			}

			data.listPanier = function() {
				var tmp=[];
				if (!this.loaded) return tmp;
				if (this.total(this.CURRENT_PANIER())==0) tmp[0]=this.CURRENT_PANIER();
				var data=this.panier_db().order("c desc").distinct("c");
					for (var i=0; i<data.length;i++) {
						var item=data[i];
							tmp[i]=item;
					}
				return tmp;
			}


			data.newPanier = function(id_panier) {
				if (!this.loaded) return '';
				if (isDefine(id_panier)) {
					data.CURRENT_PANIER(id_panier);
				} else {
					data.CURRENT_PANIER(getTimeStamp());
				}
			}

			data.removePanier = function(text) {
				if (!this.loaded) return '';
				this.panier_db({c:text}).remove();
			}

			//--Load a saved panier
			data.replacePanier = function(id_panier, panier, $location) {
				//console.log('Loading panier from server');
				data.CURRENT_PANIER(parseInt(id_panier));
				this.panier_db=TAFFY();
				this.panier_db.store('Liste');
				this.panier_db().remove();
				for (var i=0; i<panier.length;i++) {
					var tmp=panier[i];
					newitem={};
					newitem.quantity=tmp.quantity;  //--quantity
					newitem.i=tmp.i; //--id_produit
					newitem.s=tmp.s; //--prix_special
					newitem.r=tmp.r; //--prix_special
					newitem.e=tmp.e; //--id_enseigne
					newitem.d=getTimeStamp(); //--Time added
					newitem.c=parseInt(tmp.c); //--Panier
					this.panier_db.insert(newitem);
				}
				$location.url( "/promo");
				data.getHash(); //---Build cache
				return true;
			}

			data.CURRENT_PANIER = function (text) {
				if (!this.loaded) return '';
				if (!isDefine(text)) {
					return this.info_db({key:'CURRENT_PANIER'}).get()[0].value;
				} else {
					this.info_db({key:'CURRENT_PANIER'}).remove();
					this.info_db.insert({key:'CURRENT_PANIER', value:text});
					return text;
				}
			}

			//////////////////////////////////////////////////////
			/// User

			data.SESSIONID=function(text) {
				if (!isDefine(text)) {
					var tmp=getObject('USER_SESSIONID');
					return tmp;
				} else {
					setObject('USER_SESSIONID',text);
					return text;
				}
			}

			data.ALLPRODUCTID = function() {
				var tmp=[];
				if (!this.loaded) return tmp;
				var data=this.panier_db().distinct("i");
					for (var i=0; i<data.length;i++) {
						var item=data[i];
							tmp[i]=item;
					}
				return tmp;
			}

			//--This serialize to cookie some old products
			data.OLDPRODUCT = function (text) {
				if (!isDefine(text)) {
					var tmp=getObject('USER_OLDPRODUCT');
					if (tmp==null||!isDefine(tmp)) return [];
					return tmp;
				} else {
					setObject('USER_OLDPRODUCT',text);
					return text;
				}

			}
			//--Deprecated
			// data.getOLDPRODUCT = function ($http, id_product, lg) {

				// var this_product=[];
				// var products=data.OLDPRODUCT();
				// //console.log(products);
				// //1. Try if found in cache
				// if (isDefine(products)&&products.length>0) {
					// var result = $.grep(products, function(p){ return p[0] == id_product; });
					// if (isDefine(result)&&result.length>0) {
						// return data.transform_product(result[0], lg);
					// }
				// }
				// //2. Not found, try to download
				// if (!data.old_product_downloaded) {
					// old_product_downloaded=true;
					// var  post=new Object();
					// post.session=data.SESSIONID();
					// post.time=getTimeStamp();
					// post.adr=data.ADRESSE();
					// post.panier=getObject('taffy_Liste');
					// post.user=getObject('taffy_User');
					// post.id_database=data.USER_DATABASE();
					// try {
						// var url="http://localhost/getproduct3.php";
						// //console.log(url);
						// $http({
							// method: 'POST',
							// url: url,
							// data: post,
							// headers: {'Content-Type': 'application/x-www-form-urlencoded'}
						// }).success(function(d) {
							// //console.log('done');
							// //console.log(d);
							// data.OLDPRODUCT(d);
							// products=d;
							// if (products.length>0) {
								// data.OLDPRODUCT(products);
								// var result = $.grep(products, function(p){ return p[0] == id_product; });
								// if (isDefine(result)&&result.length>0) {
									// return data.transform_product(result[0], lg);
								// }
							// } else {
								// return this_product;
							// }
						// });
					// } catch(E) {return this_product;}
				// } else {
					// return this_product;
				// }
			// }

			//--Create an array of the enseignes
			data.ALLENSEIGNES = function(Produit, id_panier) {
				var tmp=[];
				var tmp_a=[];
				var total_items=0;
				if (!this.loaded) return tmp;
				if (!isDefine(id_panier)) id_panier=this.CURRENT_PANIER();
				var result=this.panier_db({c:parseInt(id_panier)}).get();
				if (result.length>0) {
					for (var i=0; i<result.length;i++) {
						//--get this product
						//--We now have the enseigne in the panier
						if (isDefine(tmp[result[i].e])) {
								var quantity=tmp[result[i].e].quantity;
								var total=tmp[result[i].e].total;
								tmp[result[i].e]={'quantity':quantity+parseInt(result[i].quantity), 'total':total+parseFloat(result[i].s),'ei':result[i].e};
							} else {
								tmp[result[i].e]={'quantity':parseInt(result[i].quantity), 'total':parseFloat(result[i].s),'ei':result[i].e};
							}
						 var prod = $.grep(Produit.produit, function(p){ return p.i == result[i].i; });
						 //--Set the enseigne name
						 if (isDefine(prod[0])) {
							 if (isDefine(tmp[prod[0].e])) {
								var quantity=tmp[prod[0].e].quantity;
								 var total=tmp[prod[0].e].total;
								 tmp[prod[0].e]={'quantity':quantity, 'total':total,'ei':prod[0].en};
							 }
						 }
					}
					var i=0;
					for (var key in tmp) {
						var item=tmp[key];
						var a={'enseigne':key, 'quantity':item.quantity, 'total':item.total,'ei':item.ei};
						tmp_a[i++]=a;
					}
				}
				return tmp_a;
			}

			data.NBVISITE = function (text) {
				try {
					if (!isDefine(text)) {
						//return getLocalStorage('NBVISITE',0);
						return parseInt(this.info_db({key:'NBVISITE'}).get()[0].value);
					} else {
						//var nb=parseInt(getLocalStorage('NBVISITE',0))+1;
						//setLocalStorage('NBVISITE',nb);
						var nb=parseInt(this.info_db({key:'NBVISITE'}).get()[0].value)+1;
						this.info_db({key:'NBVISITE'}).remove();
						this.info_db.insert({key:'NBVISITE', value:nb});
						return nb;
					}
				} catch(Exception) { //console.log(Exception);
					return 0;}
			}

			data.ADRESSE = function (text) {
					if (!isDefine(text)) {
						//--debug //console.log("Get "+	this.info_db({key:'ADRESSE'}).get()[0].value);
						return this.info_db({key:'ADRESSE'}).get()[0].value;
					} else {
						this.info_db({key:'ADRESSE'}).remove();
						//--debug //console.log("Put "+	text);
						this.info_db.insert({key:'ADRESSE', value:text});
						return text;
					}
					return 'H2X3Y7';
			}

			data.ZOOM = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'ZOOM'}).get()[0].value;
				} else {
					this.info_db({key:'ZOOM'}).remove();
					this.info_db.insert({key:'ZOOM', value:text});
					return text;
				}
			}

			data.LANGUE = function (text) {
				if (!isDefine(text)) {
					var tmp=getObject('USER_LANGUE');
					if (tmp==null) return 'en';
					return tmp;
					//return this.info_db({key:'LANGUE'}).get()[0].value;
				} else {
					setObject('USER_LANGUE',text);
					this.info_db({key:'LANGUE'}).remove();
					this.info_db.insert({key:'LANGUE', value:text});
					return text;
				}
			}

			//--Personalized products
			data.PRODUCTID = function (text) {
				if (!isDefine(text)) {
					var tmp=getObject('USER_PRODUCTID');
					if (tmp==null) return '';
					return tmp;
				} else {
					setObject('USER_PRODUCTID',text);
					this.info_db({key:'USER_PRODUCTID'}).remove();
					this.info_db.insert({key:'USER_PRODUCTID', value:text});
					return text;
				}
			}

			data.PROVINCE = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'PROVINCE'}).get()[0].value;
				} else {
					this.info_db({key:'PROVINCE'}).remove();
					this.info_db.insert({key:'PROVINCE', value:text});
					return text;
				}
			}

			data.VILLE = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'VILLE'}).get()[0].value;
				} else {
					this.info_db({key:'VILLE'}).remove();
					this.info_db.insert({key:'VILLE', value:text});
					return text;
				}
			}

			data.AFFICHE = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'AFFICHE'}).get()[0].value;
				} else {
					this.info_db({key:'AFFICHE'}).remove();
					this.info_db.insert({key:'AFFICHE', value:text});
					return text;
				}
			}

			data.DISTANCE = function (text) {
				if (!isDefine(text)) {
					if (this.loaded) return this.info_db({key:'DISTANCE'}).get()[0].value;
					return 5;
				} else {
					this.info_db({key:'DISTANCE'}).remove();
					this.info_db.insert({key:'DISTANCE', value:text});
					return text;
				}
			}

			data.LONGITUDE = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'LONGITUDE'}).get()[0].value;
				} else {
					this.info_db({key:'LONGITUDE'}).remove();
					this.info_db.insert({key:'LONGITUDE', value:text});
					return text;
				}
			}

			data.LATITUDE = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'LATITUDE'}).get()[0].value;
				} else {
					this.info_db({key:'LATITUDE'}).remove();
					this.info_db.insert({key:'LATITUDE', value:text});
					return text;
				}
			}

			data.VALIDEGMAP = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'VALIDEGMAP'}).get()[0].value;
				} else {
					this.info_db({key:'VALIDEGMAP'}).remove();
					this.info_db.insert({key:'VALIDEGMAP', value:text});
					return text;
				}
			}

			data.LAST_VISITED = function (text) {
				if (!isDefine(text)) {
					return this.info_db({key:'LAST_VISITED'}).get()[0].value;
				} else {
					this.info_db({key:'LAST_VISITED'}).remove();
					this.info_db.insert({key:'LAST_VISITED', value:text});
					return text;
				}
			}

			data.USER_ENSEIGNES = function (text) {
				if (!isDefine(text)) {
					var tmp=getObject('USER_ENSEIGNES');
					if (tmp==null) return [];
					return tmp;
					//return this.info_db({key:'USER_ENSEIGNES'}).get()[0].value;
				} else {
					setObject('USER_ENSEIGNES',text);
					//this.info_db({key:'USER_ENSEIGNES'}).remove();
					//this.info_db.insert({key:'USER_ENSEIGNES', value:text});
					search_changed=true;
					return text;
				}
			}

			data.USER_CATEGORIES = function (text) {
				if (!isDefine(text)) {
					var tmp=getObject('USER_CATEGORIES');
					if (tmp==null) return [];
					return tmp;
					//return this.info_db({key:'USER_CATEGORIES'}).get()[0].value;
				} else {
					setObject('USER_CATEGORIES',text);
					//this.info_db({key:'USER_CATEGORIES'}).remove();
					//this.info_db.insert({key:'USER_CATEGORIES', value:text});
					search_changed=true;
					return text;
				}
			}

			data.USER_DATABASE = function (text) {
				if (!isDefine(text)) {
					var tmp=getObject('USER_DATABASE');
					if (tmp==null) return 1;
					return tmp;
					//return this.info_db({key:'USER_ENSEIGNES'}).get()[0].value;
				} else {
					setObject('USER_DATABASE',text);
					this.info_db({key:'USER_DATABASE'}).remove();
					this.info_db.insert({key:'USER_DATABASE', value:text});
					search_changed=true;
					return text;
				}
			}


			data.USER_TRIPAR = function (text) {
				if (!this.loaded) return 0;
				if (!isDefine(text)) {
					return parseInt(getLocalStorage('USER_TRIPAR',0));
				} else {
					setLocalStorage('USER_TRIPAR',text);
					search_changed=true;
					return text;
				}
			}

			data.USER = function (text) {
				if (!this.loaded) return 0;
				if (!isDefine(text)) {
					return this.info_db({key:'USER'}).get()[0].value;
				} else {
					this.info_db({key:'USER'}).remove();
					this.info_db.insert({key:'USER', value:text});
					return text;
				}
			}

			data.USER_MONEYFILTER = function (text) {
				if (!this.loaded) return "";
				if (!isDefine(text)) {
					return getLocalStorage('USER_MONEYFILTER',"");
				} else {
					setLocalStorage('USER_MONEYFILTER',text);
					search_changed=true;
					return text;
				}
			}

			data.POST = function($http) {
				var  post=new Object();
				post.session=data.SESSIONID();
				post.time=getTimeStamp();
				post.panier=getObject('taffy_Liste');
				post.user=getObject('taffy_User');
				post.id_database=data.USER_DATABASE();
				$http({
					method: 'POST',
					url: "getUser.php?session_id="+data.SESSIONID(),
					data: post,
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
					timeout: 500, // in milliseconds
					success: function(data) {

					},
					error: function(request, status, err) {
						if(status == "timeout") {
						}
				  }});
			}

			data.WEB = function($http, url) {
				var  post=new Object();
				post.url=url;
				post.session=data.SESSIONID();
				post.time=getTimeStamp();				
				post.panier=getObject('taffy_Liste');
				post.user=getObject('taffy_User');
				post.id_database=data.USER_DATABASE();
				$http({
					method: 'POST',
					url: "getClick.php?session_id="+data.SESSIONID(),
					data: post,
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
					timeout: 500, // in milliseconds
					success: function(data) {

					},
					error: function(request, status, err) {
						if(status == "timeout") {
						}
				  }});
			}

			data.CLICK = function($http, p) {
				var  post=new Object();
				post.session=data.SESSIONID();
				post.time=getTimeStamp();
				post.panier=getObject('taffy_Liste');
				post.user=getObject('taffy_User');
				post.id_database=data.USER_DATABASE();
				//console.log("data.CLICK => " + p);
				$http({
					method: 'POST',
					url: "getClick.php?p="+p,
					data: post,
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				  }).success(function(data){
								document.location=data;
							});
			}

			data.PRINT = function($http, Produit) {
				////console.log('print');
				var  post=new Object();
				post.session=data.SESSIONID();
				var id_panier=data.CURRENT_PANIER();
				if (id_panier=="") return false;
				post.time=getTimeStamp();
				post.adr=data.ADRESSE();
				post.panier=getObject('taffy_Liste');				
				post.user=getObject('taffy_User');
				post.id_database=data.USER_DATABASE();
				//--Evaluate the panier
				post.franchise=data.ALLENSEIGNES(Produit);
				var id_panier=data.CURRENT_PANIER();
				post.id_panier=id_panier;
				//--No items?
				if (post.franchise.length==0) {
					//--No print, display message
					$location.url( '/promo' );
					return false;
				}
				var url="print.php?i="+post.session+"&p="+id_panier+"&r";
				//console.log(url);
				$http({
					method: 'POST',
					url: url,
					data: post,
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).success(function(d) {
					//console.log('done');
					////console.log(d);
					try {
						var url2='print.php'+d.adresse;
						window.open(url2, '_blank'); //--Open in a new windows..
					} catch (E) {
						try {
							//--Open in the same windows...
							window.location='print.php'+d.adresse;
						} catch(E2) {
							window.location.href='print.php'+d.adresse;
						}
					}
					//--Redirect to the print interface here
				});
			}

			// data.DOWNLOADOLDPRODUCT = function($http) {
				// var  post=new Object();
				// post.session=data.SESSIONID();
				// //var id_panier=data.CURRENT_PANIER();
				// //if (id_panier=="") return false;
				// post.time=getTimeStamp();
				// post.adr=data.ADRESSE();
				// post.panier=getObject('taffy_Liste');
				// post.user=getObject('taffy_User');
				// post.id_database=data.USER_DATABASE();
				// //--Evaluate the panier
				// //post.franchise=data.ALLENSEIGNES(Produit);
				// //var id_panier=data.CURRENT_PANIER();
				// //post.id_panier=id_panier;
				// //--No items?

				// try {
				// var url="http://localhost/getproduct3.php";
				// //console.log(url);
				// $http({
					// method: 'POST',
					// url: url,
					// data: post,
					// headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				// }).success(function(d) {
					// //console.log('done');
					// //console.log(d);
					// return d;
					// //--Redirect to the print interface here
				// });
				// } catch(E) {}
			// }

			data.MOBILE = function($http, Produit, url, email, link) {
				var  post=new Object();
				post.url=url;
				post.session=data.SESSIONID();
				post.time=getTimeStamp();
				//--CREATE THE COMPLETE PANIER
				var id_panier=data.CURRENT_PANIER();
				if (id_panier=="") return false;
				// var tmp=[];
				// //
				// var result=this.panier_db({c:parseInt(id_panier)}).get();
				// if (result.length>0) {
					// for (var i=0; i<result.length;i++) {
						// //--get this product
							// var prod = $.grep(Produit.produit, function(p){ return p.i == result[i].i; });
							// var quantity=parseInt(result[i].quantity);
							// var produit=prod[0];
							// produit.quantity=quantity;
							// tmp.push(produit);
					// }
				// } else {
					// //--Nothing in panier
					// //console.log('Nothing in panier');
					// return false;
				// }
				// post.current_panier=tmp;
				// //--USER PANIER
				post.panier=getObject('taffy_Liste');
				post.user=getObject('taffy_User');
				////console.log(email);
				if (isDefine(email)) {
					post.email=email;
				} else {
					post.email='';
				}
				post.id_database=data.USER_DATABASE();
				var url="mobile.php?i="+data.SESSIONID()+"&p="+id_panier+"&r";
				try {
				$http({
					method: 'POST',
					url: url,
					data: post,
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).success(function(d) {
					////console.log(d);
				});
				  } catch(e) {
				  //console.log(e);
				}
			}

			data.CHANGELG = function($http, urllink) {
				var  post=new Object();
				post.url=url;
				post.session=data.SESSIONID();
				post.time=getTimeStamp();
				//--CREATE THE COMPLETE PANIER
				var id_panier=data.CURRENT_PANIER();
				if (id_panier=="") {
					id_panier=getTimeStamp();
					//data.CURRENT_PANIER(id_panier);
				}
				post.panier=getObject('taffy_Liste');				
				post.user=getObject('taffy_User');
				post.id_panier=id_panier;
				post.email='changelg';
				post.id_database=data.USER_DATABASE();
				var url="mobile.php?i="+data.SESSIONID()+"&p="+id_panier+"&r";
				try {
				$http({
					method: 'POST',
					url: url,
					data: post,
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).success(function(d) {
					////console.log(d);
					////console.log(urllink);
					window.location.href=urllink;
				});
				  } catch(e) {
				  //console.log(e);
				}
			}

			//--Return a hash of items -> quantity in panier
			data.getHash = function (id_panier) {
				var tmp=[];
				tmp['total']=0;
				tmp['totalquantity']=0;
				tmp['totalSansSpecial']=0;
				try {
				if (!this.loaded) return tmp;
				if (!isDefine(id_panier)) var id_panier=this.CURRENT_PANIER();
				//var data=this.panier_db({c:panier}).order("d desc").get();
				var data=this.panier_db({c:parseInt(id_panier)}).order("d desc").get();
				var totalquantity=0;
				var total=0; //--Total avec special
				var totalSansSpecial =0; //--Total sans special
				for (var i=0; i<data.length;i++) {
						var item=data[i];
						var quantity=parseInt(item.quantity);
						var s=parseFloat(item.s);
						var r=parseFloat(item.r);
						if (s!=0) totalquantity+=quantity;
						total+=(s*quantity);
						totalSansSpecial+=(r*quantity);
						tmp[item.i]={'quantity':quantity, 's':s, 'total':(s*quantity)};
					}
					//--Hack to handle non existant product
					if (totalquantity>0&&total==0) totalquantity=0;
					tmp['total']=total;
					tmp['totalquantity']=totalquantity;
					tmp['totalSansSpecial']=totalSansSpecial;
				} catch(e) {
				//console.log(e);
			}

				this.panier=tmp;
				return tmp;

			}


			return data;

		}]);

    //-Configuration
    myApp.factory('Configs',[function(){
      var data = {
        API_URL:"http://132.208.135.226"
      };
      return data;
    }]);
