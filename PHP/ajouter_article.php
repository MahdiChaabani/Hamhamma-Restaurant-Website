<?php
require_once 'db.php';

$nom = $_POST['nom'];
$desc = $_POST['description'];
$prix = $_POST['prix'];
$image = $_POST['url_image'];
$dispo = $_POST['disponible'];

$sql = "INSERT INTO MENUITEM (id, nom, description, prix, url_image, disponible)
        VALUES (seq_menu.NEXTVAL, :nom, :desc, :prix, :image, :dispo)
        RETURNING id INTO :id_returned";

$stid = oci_parse($connect, $sql);
oci_bind_by_name($stid, ":nom", $nom);
oci_bind_by_name($stid, ":desc", $desc);
oci_bind_by_name($stid, ":prix", $prix);
oci_bind_by_name($stid, ":image", $image);
oci_bind_by_name($stid, ":dispo", $dispo);
oci_bind_by_name($stid, ":id_returned", $id, 32);

if (oci_execute($stid)) {
  echo json_encode(["success" => true, "id" => $id]);
} else {
  $e = oci_error($stid);
  echo json_encode(["success" => false, "message" => $e['message']]);
}
?>
