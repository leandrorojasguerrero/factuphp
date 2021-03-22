<?php
require_once("xml.php");

$emisor = 	array(
			'tipodoc'		=> '6',
			'ruc' 			=> '20602814425', 
			'razon_social'	=> 'TAQINI TECHNOLOGY SAC', 
			'nombre_comercial'	=> 'TAQINI TECHNOLOGY SAC', 
			'direccion'		=> '8 DE OCTUBRE N 123 - CHICLAYO - CHICLAYO - LAMBAYEQUE', 
			'pais'			=> 'PE', 
			'departamento'  => 'LAMBAYEQUE',//LAMBAYEQUE 
			'provincia'		=> 'CHICLAYO',//CHICLAYO 
			'distrito'		=> 'CHICLAYO', //CHICLAYO
			'ubigeo'		=> '140101', //CHICLAYO
			'usuario_sol'	=> 'MODDATOS', //USUARIO SECUNDARIO EMISOR ELECTRONICO
			'clave_sol'		=> 'MODDATOS' //CLAVE DE USUARIO SECUNDARIO EMISOR ELECTRONICO
			);


$cabecera = array(
			"tipodoc"		=>"RA",
            "serie"			=> '20210223', //date('Ymd'),
			"correlativo"	=> "1",
			"fecha_emision" => '2021-02-23', //date('Y-m-d'),			
			"fecha_envio"	=> '2021-02-23' //date('Y-m-d')	
	);


$items = array();

$cant=100;

for($i=1;$i<=$cant;$i++){
	$items[] = array(
			"item"				=> $i,
			"tipodoc"			=> "01",
			"serie"				=> "F00".rand(1,9),
			"correlativo"		=> rand(1,50000),
			"motivo"			=> "ERROR EN DOCUMENTO" //no existe catalogo de codigos, es a desicicion del negocio
		);
}

$objXml = new GeneradorXML();
$nombrexml = $emisor['ruc'].'-'.$cabecera['tipodoc'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'];

$rutaxml = "xml/";

$objXml->CrearXmlBajaDocumentos($emisor, $cabecera, $items, $rutaxml.$nombrexml);


require_once("ApiFacturacion.php");
$api = new ApiFacturacion();

$ticket = $api->EnviarResumenComprobantes($emisor,$nombrexml);

$api->ConsultarTicket($emisor, $cabecera, $ticket);

?>