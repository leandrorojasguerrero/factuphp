<?php 
try{

	$manejador = "mysql";
	$servidor = "localhost";
	$usuario = "root"; // usuario con acceso a la base de datos, generalmente root
	$pass = "";// aquí coloca la clave de la base de datos del servidor o hosting
	$base = "facturacion13"; //nombre de la base de datos
	$cadena = "$manejador:host=$servidor;dbname=$base";
	$cnx = new PDO($cadena, $usuario, $pass, array(PDO::ATTR_PERSISTENT => "true", PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));

	
	//insertar
	// $sql = "INSERT INTO tipo_documento VALUES(:codigo,:nombre)";
	// $parametros = array(':codigo'=>"I", ':nombre'=>"PERMISO TEMPORAL");
	// $pre = $cnx->prepare($sql);
	// $pre->execute($parametros);
	
	
	//actualizar
	// $sql = "UPDATE tipo_documento SET descripcion = :nombre WHERE codigo=:codigo";
	// $parametros = array(':codigo'=>"B", ':nombre'=>"Documento identidad país residencia-no.d");
	// $pre = $cnx->prepare($sql);
	// $pre->execute($parametros);	

	//DELETE
	// $sql = "DELETE from tipo_documento WHERE codigo=:codigo";
	// $parametros = array(':codigo'=>"B");
	// $pre = $cnx->prepare($sql);
	// $pre->execute($parametros);	

	
	// $res = $cnx->query("SELECT * FROM tipo_documento");
	// while($fila = $res->fetch(PDO::FETCH_NAMED)){
	// 	echo $fila['codigo']."-".$fila['descripcion'].'<br/>';
	// }

	
	// $res = $cnx->query("SELECT * FROM tipo_documento");
	// $res = $res->fetchAll(PDO::FETCH_NAMED);
	// foreach ($res as $k => $v) {
	// 	echo $v['codigo']."-".$v['descripcion'].'<br/>';	
	// }
	

}catch(Exception $ex){
	echo "Error de acceso, informelo a la brevedad.";
	exit;
}
?>