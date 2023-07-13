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
if (scalar @ARGV != 2 && scalar @ARGV != 3) {
	print STDOUT "Nombre de parametre incorrect !!\nUsage : perl $0 fichier_csv database_produit_name [unique id]\n";
	print STDOUT "Example !!\nUsage : perl $0 iga.csv Produit\n";
	print STDOUT "Example2 !!\nUsage : perl $0 iga.csv Produit unique_id\n";
	exit(1);
}

#======================================================
#== CONNEXION A LA BASE DE DONNEES
#======================================================
# Update by Nadia Tahiri 29 March 2018
my $database = "smartshopping";
my $hostname = "127.0.0.1";
my $login = "root";
my $mdp = '';
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
my $cpt = 0;
my $id_produit = "";
my $part_id = `date +%s%N`;
my $total = scalar @ARGV;

my $nbProdUpdated  = 0;
my $nbProdInserted = 0;

if (scalar @ARGV == 3) {
	$part_id= $ARGV[2];
}
chomp($part_id);
my $date_today = UnixDate('today', "%Y-%m-%d");
my $date_invalidate = UnixDate('next week', "%Y-%m-%d");
print STDOUT "insertProduit.pl : id_produit $part_id\n";
print STDOUT "insertProduit.pl : Opening $csv_file\n";

open IN, $csv_file || die "The file $csv_file was not found.";

while (my $ligne = <IN>) {
	chomp($ligne);
	my @tab = split("<>", $ligne);
	my $brand_fr = decode("utf8", $tab[BRAND_FR]);
	$brand_fr =~ s/\\'/''/g;
	my $description_fr = decode("utf8", $tab[NOM_PRODUIT_FR]);
	$description_fr =~ s/\\'/''/g;
	my @tab_description = split(" ", $tab[NOM_PRODUIT_FR]);
	my $premier_mot =  decode("utf8", $tab_description[0]);
	my $description_fr2 = decode("utf8", $tab[NOM_PRODUIT_FR2]);
	$description_fr2 =~ s/\\'/''/g;
	for (my $i = 0; $i < scalar(@tab); $i++) {
		$tab[$i] =  decode("utf8", $tab[$i]);
		$tab[$i] =~ s/[']+/'/g;
		$tab[$i] =~ s/\\//g;
	}
	my @tab_date = split(" ", $tab[DATE]);

	$cpt++;

	my $select = "SELECT * FROM $database_produit WHERE id_enseigne=? AND id_database=? AND nom_produit_fr2=? AND nom_produit_fr=? AND nom_produit_en=? AND nom_produit_en2=? AND nom_produit_es=? AND nom_produit_es2=? AND id_categorie=? AND unite_fr=? AND unite_en=? AND unite_es=? AND brand_fr=? AND brand_en=? AND brand_es=? AND quantite_fr=? AND quantite_en=? AND quantite_es=? AND ligne_complete_fr=? AND ligne_complete_en=? AND ligne_complete_es=? AND date=?";
	my $sth = $dbh->prepare($select) or PRINT STDOUT $dbh->errstr;
	$sth->execute($tab[ID_ENSEIGNE], $tab[ID_DATABASE], $tab[NOM_PRODUIT_FR2], $tab[NOM_PRODUIT_FR], $tab[NOM_PRODUIT_EN], $tab[NOM_PRODUIT_EN2], $tab[NOM_PRODUIT_ES], $tab[NOM_PRODUIT_ES2], $tab[ID_CATEGORIE], $tab[UNITE_FR], $tab[UNITE_EN], $tab[UNITE_ES], $tab[BRAND_FR], $tab[BRAND_EN], $tab[BRAND_ES], $tab[QUANTITE_FR], $tab[QUANTITE_EN], $tab[QUANTITE_ES], $tab[LIGNE_COMPLETE_FR], $tab[LIGNE_COMPLETE_EN], $tab[LIGNE_COMPLETE_ES], $tab[DATE]);
	print STDOUT "\n" . $sth->rows . "=>$select";

	if ($sth->rows > 0) {
		my @row = $sth->fetchrow_array;
		if ($row[PRIX_REGULIER] != $tab[PRIX_REGULIER] || $row[PRIX_SPECIAL] != $tab[PRIX_SPECIAL]) {
			my $update = "UPDATE $database_produit SET prix_regulier=?, prix_special=? WHERE id_enseigne=? AND id_database=? AND nom_produit_fr2=? AND nom_produit_fr=? AND nom_produit_en=? AND nom_produit_en2=? AND nom_produit_es=? AND nom_produit_es2=? AND id_categorie=? AND unite_fr=? AND unite_en=? AND unite_es=? AND brand_fr=? AND brand_en=? AND brand_es=? AND quantite_fr=? AND quantite_en=? AND quantite_es=? AND ligne_complete_fr=? AND ligne_complete_en=? AND ligne_complete_es=? AND date=?";
			$sth = $dbh->prepare($update) or PRINT STDOUT $dbh->errstr;
			$sth->execute($tab[PRIX_REGULIER], $tab[PRIX_SPECIAL], $tab[ID_ENSEIGNE], $tab[ID_DATABASE], $tab[NOM_PRODUIT_FR2], $tab[NOM_PRODUIT_FR], $tab[NOM_PRODUIT_EN], $tab[NOM_PRODUIT_EN2], $tab[NOM_PRODUIT_ES], $tab[NOM_PRODUIT_ES2], $tab[ID_CATEGORIE], $tab[UNITE_FR], $tab[UNITE_EN], $tab[UNITE_ES], $tab[BRAND_FR], $tab[BRAND_EN], $tab[BRAND_ES], $tab[QUANTITE_FR], $tab[QUANTITE_EN], $tab[QUANTITE_ES], $tab[LIGNE_COMPLETE_FR], $tab[LIGNE_COMPLETE_EN], $tab[LIGNE_COMPLETE_ES], $tab[DATE]);
			$nbProdUpdated++;
			print STDOUT "\n$update";
		}
	}
	else {
		my $insert = "INSERT INTO $database_produit (id_produit, id_database, id_enseigne, prix_regulier, prix_special, nom_produit_fr2, nom_produit_fr, nom_produit_en, nom_produit_en2, nom_produit_es, nom_produit_es2, fichier_image, id_categorie, unite_fr, unite_en, unite_es, brand_fr, brand_en, brand_es, quantite_fr, quantite_en, quantite_es, date, start_date, entered_date, status, user_id, ligne_complete_fr, ligne_complete_en, ligne_complete_es) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,Now(),?,?,?,?,?)";
		my $sth = $dbh->prepare($insert) or PRINT STDOUT $dbh->errstr;
		#===================================================
		#== Validation de l'enregistrement et execution
		#===================================================
		if ($tab[ID_ENSEIGNE] != 0) {

			$tab[ID_PRODUIT] = sprintf "%d" . "_" . "%02d" . "_" . "%d", $part_id, $tab[ID_ENSEIGNE], $cpt;

			$sth->execute($tab[ID_PRODUIT], $tab[ID_DATABASE], $tab[ID_ENSEIGNE], $tab[PRIX_REGULIER], $tab[PRIX_SPECIAL], $tab[NOM_PRODUIT_FR2], $tab[NOM_PRODUIT_FR], $tab[NOM_PRODUIT_EN], $tab[NOM_PRODUIT_EN2], $tab[NOM_PRODUIT_ES], $tab[NOM_PRODUIT_ES2], $tab[FICHIER_IMAGE], $tab[ID_CATEGORIE], $tab[UNITE_FR], $tab[UNITE_EN], $tab[UNITE_ES], $tab[BRAND_FR], $tab[BRAND_EN], $tab[BRAND_ES], $tab[QUANTITE_FR], $tab[QUANTITE_EN], $tab[QUANTITE_ES], $tab[DATE], $tab[START_DATE], $tab[STATUS], $tab[USER_ID], $tab[LIGNE_COMPLETE_FR], $tab[LIGNE_COMPLETE_EN], $tab[LIGNE_COMPLETE_ES]);
			$nbProdInserted++;
			print STDOUT "\n$insert";
		}
		else {
			print STDOUT "Produit invalide\n";
		}
	}
}

print STDOUT "\n$part_id Total products loaded: $cpt\n";
print STDOUT "Nombre de produits updatés : $nbProdUpdated\nNombre de produits inserés $nbProdInserted\n";
close IN;
