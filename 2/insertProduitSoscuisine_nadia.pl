#!/usr/bin/perl

#==============================================================================
#== Ce script permet de charger les donnees dans la table produit. Il prend
#== en entrée un fichier csv contenant l'ensemble des champs de la table.
#== Les champs sont validés et chaque enregistrement est inseré dans la table.
#==
#== auteur : Alix Boc
#== date de creation : 6 juillet 2012
#== revision         : 23 March 2018 - Nadia Tahiri
#=============================================================================

use DBI;
use strict;
use warnings;
use Date::Manip;
use Encode qw(decode encode);

binmode(STDOUT, ":utf8");

#require "convert.pl";

#=============================================================================
#== CHAMPS DE LA TABLE PRODUIT
#=============================================================================
use constant ID_PRODUIT        => 0;
use constant ID_DATABASE       => 1;
use constant ID_ENSEIGNE       => 2;
use constant PRIX_REGULIER     => 3;
use constant PRIX_SPECIAL      => 4;
use constant NOM_PRODUIT_FR2   => 5;
use constant NOM_PRODUIT_FR    => 6;
use constant NOM_PRODUIT_EN    => 7;
use constant NOM_PRODUIT_EN2   => 8;
use constant NOM_PRODUIT_ES    => 9;
use constant NOM_PRODUIT_ES2   => 10;
use constant FICHIER_IMAGE     => 11;
use constant ID_CATEGORIE      => 12;
use constant UNITE_FR          => 13;
use constant UNITE_EN          => 14;
use constant UNITE_ES          => 15;
use constant BRAND_FR          => 16;
use constant BRAND_EN          => 17;
use constant BRAND_ES          => 18;
use constant QUANTITE_FR       => 19;
use constant QUANTITE_EN       => 20;
use constant QUANTITE_ES       => 21;
use constant DATE              => 22;
use constant START_DATE        => 23;
use constant STATUS            => 24;
use constant USER_ID           => 25;
use constant LIGNE_COMPLETE_FR => 26;
use constant LIGNE_COMPLETE_EN => 27;
use constant LIGNE_COMPLETE_ES => 28;

#======================================================
#== Lecture des parametres de la ligne de commande
#======================================================
if (scalar @ARGV != 2 && scalar @ARGV != 3){
	print STDOUT "Nombre de parametre incorrect !!\nUsage : perl $0 fichier_csv database_produit_name [unique id]\n";
	print STDOUT "Example !!\nUsage : perl $0 iga.csv Produit\n";
	print STDOUT "Example2 !!\nUsage : perl $0 iga.csv Produit unique_id\n";
	exit(1);
}

#======================================================
#== CONNEXION A LA BASE DE DONNEES
#======================================================
# Update by Nadia Tahiri 29 March 2018
my $database 		 = "smartshopping";
my $hostname		 = "localhost";
my $login  		   = "smartshopping";
my $mdp     		 = 'Escher1ch1a';
my $database_produit = $ARGV[1];
chomp($database_produit);

my $dsn = "DBI:mysql:database=$database;host=$hostname";
my $dbh = DBI->connect($dsn, $login, $mdp) or die "Echec connexion";
$dbh->do('SET NAMES utf8');



#======================================================
#== Ouverture du fichier csv
#======================================================

my %month = (	"Jan" => "January",
		"Féb" => "February",
		"Mar" => "March",
		"Avr" => "April",
		"Mai" => "May",
		"Jun" => "June",
		"Jul" => "July",
		"Aoû" => "August",
		"Sep" => "September",
		"Oct" => "October",
		"Nov" => "November",
		"Déc" => "December"
);

my $csv_file = $ARGV[0];
my $requete = "";
my $cpt        = 0;
my $id_produit = "";
my $part_id    = `date +%s%N`;
my $total=scalar @ARGV;

my $nbProdUpdated  = 0;
my $nbProdInserted = 0;

if (scalar @ARGV==3) {
	$part_id= $ARGV[2];
}
chomp($part_id);
my $date_today = UnixDate('today', "%Y-%m-%d");
my $date_invalidate = UnixDate('next week', "%Y-%m-%d");
print STDOUT "insertProduit.pl : id_produit $part_id\n";
print STDOUT "insertProduit.pl : Opening $csv_file\n";

open IN, $csv_file || die "The file $csv_file was not found.";

while (my $ligne = <IN>){
	chomp($ligne);
	my @tab = split("<>",$ligne);

	my $brand_fr = decode("utf8", $tab[BRAND_FR]);
	$brand_fr =~ s/\\'/''/g;
	my $description_fr = decode("utf8",$tab[NOM_PRODUIT_FR]);
	$description_fr =~ s/\\'/''/g;
	my @tab_description = split(" ",$tab[NOM_PRODUIT_FR]);
	my $premier_mot =  decode("utf8", $tab_description[0]);
	my $description_fr2 = decode("utf8",$tab[NOM_PRODUIT_FR2]);
	$description_fr2 =~ s/\\'/''/g;

    for(my $i=0;$i< scalar(@tab);$i++){
					$tab[$i] =  decode("utf8",$tab[$i]);
				#	$tab[$i] =~ s/[']+/''/g;
				#	$tab[$i] =~ s/\\//g;
				}

	my @tab_date = split(" ",$tab[DATE]);
	#my $date	 = $month{$tab_date[0]} . " " . $tab_date[1]; # brand_fr LIKE '$brand_fr' AND

  # Update by Nadia Tahiri 29 March 2018 car la fonction estUneSousChaine n'exite pas
  # my $select = "SELECT * FROM $database_produit WHERE $tab[ID_DATABASE]=id_database AND date='$tab[DATE]' AND estUneSousChaine('$tab[NOM_PRODUIT_FR]',nom_produit_fr,'/') > 0  AND id_enseigne=$tab[ID_ENSEIGNE] AND prix_special=$tab[PRIX_SPECIAL] AND ligne_complete_es LIKE '\%soscuisine\%' AND id_categorie=$tab[ID_CATEGORIE]";
  my $select = "SELECT * FROM $database_produit WHERE id_database = $tab[ID_DATABASE] AND date='$tab[DATE]' AND id_enseigne=$tab[ID_ENSEIGNE] AND prix_special=$tab[PRIX_SPECIAL] AND ligne_complete_es LIKE '\%soscuisine\%' AND id_categorie=$tab[ID_CATEGORIE]";

	my $sth = $dbh->prepare($select);
	$sth->execute();
	print STDOUT "\n" . $sth->rows . "=>$select";
	if ($sth->rows > 0){
	#	if( $tab[LIGNE_COMPLETE_ES] =~ /^\*/){
			#print STDOUT "\nUPDATE : $tab[LIGNE_COMPLETE_ES] : " . $sth->rows . " => $select";
			my $promo = "soscuisine";
			$promo = "*soscuisine" if($tab[LIGNE_COMPLETE_ES] =~ /^\*/);
      # Update by Nadia Tahiri 29 March 2018 car la fonction estUneSousChaine n'exite pas
			# my $update = "UPDATE $database_produit SET ligne_complete_es='$promo',prix_regulier=$tab[PRIX_REGULIER] WHERE $tab[ID_DATABASE]=id_database AND date='$tab[DATE]' AND estUneSousChaine('$tab[NOM_PRODUIT_FR]',nom_produit_fr,'/') > 0  AND id_enseigne=$tab[ID_ENSEIGNE] AND prix_special=$tab[PRIX_SPECIAL] AND ligne_complete_es NOT LIKE '*\%' AND id_categorie=$tab[ID_CATEGORIE]";
      my $update = "UPDATE $database_produit SET ligne_complete_es='$promo',prix_regulier=$tab[PRIX_REGULIER] WHERE $tab[ID_DATABASE]=id_database AND date='$tab[DATE]' AND id_enseigne=$tab[ID_ENSEIGNE] AND prix_special=$tab[PRIX_SPECIAL] AND ligne_complete_es NOT LIKE '*\%' AND id_categorie=$tab[ID_CATEGORIE]";

      print STDOUT "\nUPDATE : $update";
            $sth = $dbh->prepare($update);
			$sth->execute();
      # Update by Nadia Tahiri 29 March 2018 car la fonction estUneSousChaine n'exite pas et AND manquant
			# $update = "UPDATE Produit SET ligne_complete_es='$promo',prix_regulier=$tab[PRIX_REGULIER] WHERE $tab[ID_DATABASE]=id_database AND date='$tab[DATE]' AND estUneSousChaine('$tab[NOM_PRODUIT_FR]',nom_produit_fr,'/') > 0 id_enseigne=$tab[ID_ENSEIGNE] AND prix_special=$tab[PRIX_SPECIAL] AND ligne_complete_es NOT LIKE '*\%' AND id_categorie=$tab[ID_CATEGORIE]";
      $update = "UPDATE Produit SET ligne_complete_es='$promo',prix_regulier=$tab[PRIX_REGULIER] WHERE $tab[ID_DATABASE]=id_database AND date='$tab[DATE]' AND id_enseigne=$tab[ID_ENSEIGNE] AND prix_special=$tab[PRIX_SPECIAL] AND ligne_complete_es NOT LIKE '*\%' AND id_categorie=$tab[ID_CATEGORIE]";
      $sth = $dbh->prepare($update);
			$sth->execute();
			$nbProdUpdated++;
	#	}
	}
	else{
		 $select = "SELECT * FROM $database_produit WHERE $tab[ID_DATABASE]=id_database AND nom_produit_fr2 LIKE '$description_fr2' AND date='$tab[DATE]' AND nom_produit_fr LIKE '$description_fr' AND id_enseigne=$tab[ID_ENSEIGNE] AND prix_special=$tab[PRIX_SPECIAL] AND prix_regulier=$tab[PRIX_REGULIER] AND id_categorie=$tab[ID_CATEGORIE] AND brand_fr LIKE '$brand_fr' AND ligne_complete_es LIKE '\%supermarches\%'";
		 $sth = $dbh->prepare($select);
		 $sth->execute();
		#
		 print STDOUT "\n" . $sth->rows . ":$select";
		#
		 if ($sth->rows == 0){
			#print STDOUT "\n" . $sth->rows . " => $select";
			my $sth = $dbh->prepare("INSERT INTO $database_produit (id_produit, id_database,id_enseigne,prix_regulier, prix_special, nom_produit_fr2, nom_produit_fr,nom_produit_en, nom_produit_en2, nom_produit_es, nom_produit_es2, fichier_image, id_categorie, unite_fr, unite_en, unite_es, brand_fr, brand_en, brand_es, quantite_fr, quantite_en, quantite_es, date, start_date, entered_date, status, user_id, ligne_complete_fr, ligne_complete_en, ligne_complete_es) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,Now(),?,?,?,?,?);") or PRINT STDOUT $dbh->errstr;
			#===================================================
			#== Validation de l'enregistrement et execution
			#===================================================
			if ( (&isValide(@tab) == 1) && ($tab[ID_ENSEIGNE] != 0) ){
				$tab[ID_PRODUIT]= sprintf "%d" . "_" . "%02d" . "_" . "%d", $part_id,$tab[ID_ENSEIGNE],($cpt++) ;

				for(my $i=0;$i< scalar(@tab);$i++){
					#$tab[$i] =  decode("utf8",$tab[$i]);
					$tab[$i] =~ s/[']+/'/g;
					$tab[$i] =~ s/\\//g;
				}

					if(!$sth->execute($tab[ID_PRODUIT], $tab[ID_DATABASE],$tab[ID_ENSEIGNE],$tab[PRIX_REGULIER],$tab[PRIX_SPECIAL],$tab[NOM_PRODUIT_FR2],$tab[NOM_PRODUIT_FR],$tab[NOM_PRODUIT_EN],$tab[NOM_PRODUIT_EN2],$tab[NOM_PRODUIT_ES],$tab[NOM_PRODUIT_ES2],$tab[FICHIER_IMAGE],$tab[ID_CATEGORIE],$tab[UNITE_FR],$tab[UNITE_EN],$tab[UNITE_ES],$tab[BRAND_FR],$tab[BRAND_EN],$tab[BRAND_ES],$tab[QUANTITE_FR],$tab[QUANTITE_EN],$tab[QUANTITE_ES],$tab[DATE],$tab[START_DATE],$tab[STATUS],$tab[USER_ID],$tab[LIGNE_COMPLETE_FR],$tab[LIGNE_COMPLETE_EN],$tab[LIGNE_COMPLETE_ES] )) {
				   		print STDOUT $DBI::errstr."\n";
					}
					else{
						$nbProdInserted++;
					}
				} else {
					print STDOUT "Produit invalide\n";
				}
		 }
	}
}
print STDOUT "$part_id Total products loaded: $cpt\n";
print STDOUT "Nombre de produits updatés : $nbProdUpdated\nNombre de produits inserés $nbProdInserted\n";
close IN;

#==========================================
#== ROUTINE
#==========================================
sub isValide{
	return 1;
}
