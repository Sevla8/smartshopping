<?php
	/*
		Déplace les produits de la table 'Produit' vers la table 'Produit_archives'.
	*/
	include_once("bd.php");
	$link = connection_db();

	const TIMESTAMP_LENGTH = 10; // if date < 2286/20/11 & date > 2001/09/09

	$today = time();	// timestamp
	$six_months_ago = $today - (6 * 31 * 24 * 60 * 60); // timestamp

	$query = "INSERT INTO Produit_archives 
			  SELECT * FROM Produit 
			  WHERE SUBSTRING(id_produit, 1, ". TIMESTAMP_LENGTH .") < $six_months_ago 
			  AND id_produit NOT IN (SELECT id_produit 
									 FROM Produit_archives)";
	execute_query_db($link, $query);

	$query = "DELETE FROM Produit
			  WHERE SUBSTRING(id_produit, 1, ". TIMESTAMP_LENGTH .") < $six_months_ago";
	execute_query_db($link, $query);

	deconnection_db($link);
?>
