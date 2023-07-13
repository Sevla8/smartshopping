#!/usr/bin/perl

use strict;
#use warnings;
use utf8;
use DBI;
use Scalar::Util qw(looks_like_number);
use Encode;
use HTML::Entities;

use open ":encoding(utf8)";

my $root        = "/tmp/supermarches";
my $rep_scripts = "/var/www/html/admin/scripts";


if ( !-e $root){
	`mkdir $root`;
}

# Update by Nadia Tahiri 23 March 2018
# # Update by Nadia Tahiri 23 March 2018 my $database = "smartshopping";
# Update by Nadia Tahiri hostname jurassic instead of myGroceryTour

#========================================================
#= Telechargement des donnees de supermarches.ca
#========================================================
#print STDOUT `/usr/bin/perl $rep_scripts/supermarches_new.pl`;

#==========================================================
#= Telechargement des aubaines de supermarches.ca
#==========================================================
#print STDOUT `/usr/bin/perl $rep_scripts/supermarches_aubaines.pl`;

my $fichier_supermarches_new = "$root/supermarches_new.csv";
my $fichier_supermarches_aub = "$root/supermarches_aubaines.csv";
my $fichier_supermarches     = "$root/supermarches.csv";
my $id_database              = 1;

#== Les deux fichiers doivent exister, sinon il y a une erreur

if ( !-e $fichier_supermarches_new){
	print STDOUT "Le fichier $fichier_supermarches_new n'existe pas, fin du script";
	exit(1);
}
if ( !-e $fichier_supermarches_aub){
	print STDOUT "Le fichier $fichier_supermarches_aub n'existe pas, fin du script";
	exit(1);
}


open OUT   , ">$fichier_supermarches"    || die " Probleme a la creation du fichier $fichier_supermarches ($!)";
open INNEW , "$fichier_supermarches_new" || die " Probleme a l'ouverture du fichier $fichier_supermarches_new ($!)";

my $cpt        = 0;
my $id_produit = "";
my $part_id    = `date +%s%N`;
my $aubaine_flag    = "";
chomp($part_id);

while (my $ligne_new = <INNEW>){
	chomp($ligne_new);
	$aubaine_flag = "";
	my @tab_new = split("<>",$ligne_new);

	open INAUB , "$fichier_supermarches_aub" || die " Probleme a l'ouverture du fichier $fichier_supermarches_aub ($!)";
	while (my $ligne_aub = <INAUB>){
		my @tab_aub = split("<>",$ligne_aub);
		my $marque = $tab_new[0];
		# print "\n0- AUBAINE \n[0]$tab_aub[0]\n[1]$tab_aub[1]\n[2]$tab_aub[2]\n[3]$tab_aub[3]\n[4]$tab_aub[4]\n[5]$tab_aub[5]\n[6]$tab_aub[6]\n[7]$tab_aub[7]\n[8]$tab_aub[8]\n\n";
		# print "\n0- NEW \n[0]$tab_new[0]\n[1]$tab_new[1]\n[2]$tab_new[2]\n[3]$tab_new[3]\n[4]$tab_new[4]\n[5]$tab_new[5]\n[6]$tab_new[6]\n[7]$tab_new[7]\n[8]$tab_new[8]\n\n";
		if ( ($tab_aub[1] eq $tab_new[1]) &&($tab_aub[2] eq $tab_new[2]) &&($tab_aub[6] eq $tab_new[6]) &&($tab_aub[8] eq $tab_new[8])){
			# print "\n1- AUBAINE \n$tab_aub[1]\n$tab_aub[2]\n$tab_aub[6]\n\n";
			# print "\n1- NEW \n$tab_new[1]\n$tab_new[2]\n$tab_new[6]\n\n";
			# print "\n2- AUBAINE \n$tab_aub[10]\n$tab_aub[9]\n\n";
			# print "\n2- NEW \n$tab_new[10]\n$tab_new[9]\n\n";
			# my $input = <STDIN>;
			# my $prix_nadia=sprintf("%.2f",$tab_new[10]);
			# if($prix_nadia>25){
			# 	print "\n1- AUBAINE \n[1]$tab_aub[1]\n[2]$tab_aub[2]\n[6]$tab_aub[6]\n[8]$tab_aub[8]\n\n";
			# 	print "\n1- NEW \n[1]$tab_new[1]\n[2]$tab_new[2]\n[6]$tab_new[6]\n[8]$tab_new[8]\n\n";
			# 	print "\n2- AUBAINE \n[10]$tab_aub[10]\n[9]$tab_aub[9]\n\n";
			# 	print "\n2- NEW \n[10]$tab_new[10]\n[9]$tab_new[9]\n\n";
			# 	print "\n3- **** \n$id_produit\n$id_database\n\n";
      #
			# 	my $input = <STD/IN>;
			# }
			$tab_new[10] = $tab_aub[10];
			$tab_new[9]  = $tab_aub[9];
			$aubaine_flag = "*";
		}
	}
	close (INAUB);

	$id_produit = $part_id . "_" . $tab_new[8] . "_" . ($cpt++) ;

	if(	$tab_new[4] == 12){
		print OUT $id_produit . "<>" . $id_database . "<>" .$tab_new[8] . "<>" .$tab_new[9] . "<>" .$tab_new[10] . "<>" .$tab_new[6] . "<>" .$tab_new[2] . "<>" .$tab_new[2] . "<>" .$tab_new[6] . "<>spanish<>spanish2<>" .$tab_new[3] . "<>" .$tab_new[4] . "<>" .$tab_new[5] . "<>" .$tab_new[5] . "<>unite_es<><><>brand_es<>" .$tab_new[6] . "<>" .$tab_new[6] . "<>quantite_es<>" .$tab_new[11] . "<>" . $tab_new[15] . "<>1<>0<>ligne_fr<>ligne_en<>$aubaine_flag" . "supermarches.pl\n";
	}
	else{
		print OUT $id_produit . "<>" . $id_database . "<>" .$tab_new[8] . "<>" .$tab_new[9] . "<>" .$tab_new[10] . "<>" .$tab_new[6] . "<>" .$tab_new[2] . "<>" .$tab_new[2] . "<>" .$tab_new[6] . "<>spanish<>spanish2<>" .$tab_new[3] . "<>" .$tab_new[4] . "<>" .$tab_new[5] . "<>" .$tab_new[5] . "<>unite_es<>" .$tab_new[1] . "<>" .$tab_new[1] . "<>brand_es<>" .$tab_new[6] . "<>" .$tab_new[6] . "<>quantite_es<>" .$tab_new[11] . "<>" . $tab_new[15] . "<>1<>0<>ligne_fr<>ligne_en<>$aubaine_flag" . "supermarches.pl\n";
	}
}

close OUT;
close INNEW;
