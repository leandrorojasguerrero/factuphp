<?php 
require_once("cantidad_en_letras.php");

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


$cliente = array(
			'tipodoc'		=> '6',//6->ruc, 1-> dni 
			'ruc'			=> '20480631286', 
			'razon_social'  => 'ASOCIACION CENTRO DE ENTRENAMIENTO EN TECNOLOGIAS DE INFORMACION - CETI', 
			'direccion'		=> 'Cal. Francisco Cuneo-Pataz Nro. 270(Frente al Circulo Departamental de Emple)',
			'pais'			=> 'PE'
			);	

$comprobante =	array(
			'tipodoc'		=> '08', //01->FACTURA, 03->BOLETA, 07->NOTA CREDITO, 08->NOTA DEBITO Catálogo No. 01: Código de Tipo de documento
			'serie'			=> 'FD01',//F: proviene de una factura, B: de una boleta
			'correlativo'	=> '46',
			'fecha_emision' => '2021-02-16',
			'moneda'		=> 'PEN', //PEN->SOLES; USD->DOLARES
			'total_opgravadas'=> 0, //OP. GRAVADAS
			'total_opexoneradas'=>0,
			'total_opinafectas'=>0,
			'igv'			=> 0,
			'total'			=> 0,
			'total_texto'	=> '',
            'tipodoc_ref'   => '01', //FACTURA
            'serie_ref'     => 'F002',
            'correlativo_ref'   => '789',
            'codmotivo'     => '02', // Catálogo No. 10: Códigos de Tipo de nota de débito electrónica
            'descripcion'   =>  'AUMENTO EN EL VALOR'
		);

        $detalle = 
        array(
            array(
                'item' 				=> 1,
                'codigo'			=> '11',
                'descripcion'		=> 'ACEITE CAPRI',
                'cantidad'			=> 1,
                'valor_unitario'	=> 50,
                'precio_unitario'	=> 59,
                'tipo_precio'		=> "01", //ya incluye igv
                'igv'				=> 9,
                'porcentaje_igv'	=> 18, //de 0 a 100
                'valor_total'		=> 50, 
                'importe_total'		=> 59,
                'unidad'			=> 'NIU',//unidad,
                'letra_afectacion'	=> 'S',
                'codigo_afectacion_alt'	=> '10',
                'codigo_afectacion'	=> 1000,
                'nombre_afectacion'	=>'IGV',
                'tipo_afectacion'	=> 'VAT' //GRAVADAS				 
            ),
            array(
                'item' 				=> 2,
                'codigo'			=> '22',
                'descripcion'		=> 'AYUDIN',
                'cantidad'			=> 1,
                'valor_unitario'	=> 50,
                'precio_unitario'	=> 59,
                'tipo_precio'		=> "01", //ya incluye igv
                'igv'				=> 9,
                'porcentaje_igv'	=> 18,
                'valor_total'		=> 50,
                'importe_total'		=> 59,
                'unidad'			=> 'NIU',//unidad,
                'codigo_afectacion_alt'	=> '10',
                'codigo_afectacion'	=> 1000,
                'nombre_afectacion'	=>	'IGV',
                'tipo_afectacion'	=> 'VAT' //GRAVADAS			 
            )								
        );
$op_gravadas = 0;
$op_inafectas = 0;
$op_exoneradas = 0;
$igv = 0;
$total = 0; 

foreach ($detalle as $k => $v) {
	if($v['codigo_afectacion_alt']=='10'){
		$op_gravadas = $op_gravadas + $v['valor_total'];
	}

	if($v['codigo_afectacion_alt']=='20'){
		$op_exoneradas = $op_exoneradas + $v['valor_total'];
	}

	if($v['codigo_afectacion_alt']=='30'){
		$op_inafectas = $op_inafectas + $v['valor_total'];
	}

	$igv = $igv + $v['igv'];
	$total = $total + $v['importe_total'];
}

$comprobante['total_opgravadas'] = $op_gravadas;
$comprobante['total_opexoneradas'] = $op_exoneradas;
$comprobante['total_opinafectas'] = $op_inafectas;
$comprobante['igv'] = $igv;
$comprobante['total'] = $total;
$comprobante['total_texto'] = CantidadEnLetra($total);

//CREAR XML INICIO
require_once("xml.php");

$xml = new GeneradorXML();

//RUC DEL EMISOR - TIPO DE COMPROBANTE - SERIE DEL DOCUMENTO - CORRELATIVO
//01-> FACTURA, 03-> BOLETA, 07-> NOTA DE CREDITO, 08-> NOTA DE DEBITO, 09->GUIA DE REMISION
$nombrexml = $emisor['ruc'].'-'.$comprobante['tipodoc'].'-'.$comprobante['serie'].'-'.$comprobante['correlativo'];

$ruta = "xml/".$nombrexml;
$xml->CrearXMLNotaDebito($ruta, $emisor, $cliente, $comprobante, $detalle);

echo 'XML CREADO';
//CREAR XML FIN

//ENVIO DE COMPROBANTE A SUNAT - INICIO
require_once('ApiFacturacion.php');

$objApi = new ApiFacturacion();
$objApi->EnviarComprobanteElectronico($emisor, $nombrexml);

//ENVIO DE COMPROBANTE A SUNAT - FIN

?>