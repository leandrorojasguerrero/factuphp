<?php
require_once('signature.php'); // permite firmar xml

class ApiFacturacion
{
    public function EnviarComprobanteElectronico($emisor, $nombre, $rutacertificado="", $ruta_archivo_xml = "xml/", $ruta_archivo_cdr = "cdr/")
    {
        $objfirma = new Signature();
        $flg_firma = 0; //Posicion del XML: 0 para firma
        // $ruta_xml_firmar = $ruta . '.XML'; //es el archivo XML que se va a firmar
        $ruta = $ruta_archivo_xml . $nombre . '.XML';

        $ruta_firma = $rutacertificado. 'certificado_prueba.pfx'; //ruta del archivo del certicado para firmar
        $pass_firma = 'ceti';
        
        $resp = $objfirma->signature_xml($flg_firma, $ruta, $ruta_firma, $pass_firma);
        print_r($resp);
        echo '</br> XML FIRMADO';
        
        //FIRMAR XML - FIN
        
        //CONVERTIR A ZIP - INICIO
        $zip = new ZipArchive();

        $nombrezip = $nombre.".ZIP";
        $rutazip = $ruta_archivo_xml . $nombre.".ZIP";
        
        if($zip->open($rutazip, ZipArchive::CREATE) === TRUE)
        {
            $zip->addFile($ruta, $nombre . '.XML');
            $zip->close();
        }
        
        echo '</br>XML ZIPEADO';
        
        //CONVERTIR A ZIP - FIN
        
        
        //ENVIAR EL ZIP A LOS WS DE SUNAT - INICIO
        $ws = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService'; //ruta del servicio web de pruebad e SUNAT para enviar documentos
        
        $ruta_archivo = $rutazip;
		$nombre_archivo = $nombrezip;

        $contenido_del_zip = base64_encode(file_get_contents($ruta_archivo)); //codificar y convertir en texto el .zip
        
        //echo '</br> '. $contenido_del_zip;
        $xml_envio ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasisopen.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                        <soapenv:Header>
                        <wsse:Security>
                            <wsse:UsernameToken>
                                <wsse:Username>'.$emisor['ruc'].$emisor['usuario_sol'].'</wsse:Username>
                                <wsse:Password>'.$emisor['clave_sol'].'</wsse:Password>
                            </wsse:UsernameToken>
                        </wsse:Security>
                        </soapenv:Header>
                        <soapenv:Body>
                        <ser:sendBill>
                            <fileName>'.$nombre_archivo.'</fileName>
                            <contentFile>'.$contenido_del_zip.'</contentFile>
                        </ser:sendBill>
                        </soapenv:Body>
                    </soapenv:Envelope>';
        
            $header = array(
                "Content-type: text/xml; charset=\"utf-8\"",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "SOAPAction: ",
                "Content-lenght: ".strlen($xml_envio)
                );
        
        $ch = curl_init(); //iniciar la llamada
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 1); //
        curl_setopt($ch,CURLOPT_URL, $ws);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $xml_envio);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        
        //para ejecutar los procesos de forma local en windows
        //enlace de descarga del cacert.pem https://curl.haxx.se/docs/caextract.html
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem"); //solo en local, si estas en el servidor web con ssl comentar esta línea
        
        $response = curl_exec($ch); // ejecucion del llamado y respuesta del WS SUNAT.
        
        $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE); // objten el codigo de respuesta de la peticion al WS SUNAT
        $estadofe = "0"; //inicializo estado de operación interno
        
        if($httpcode == 200)//200: La comunicacion fue satisfactoria
        {
            $doc = new DOMDocument();//clase que nos permite crear documentos XML
            $doc->loadXML($response); //cargar y crear el XML por medio de text-xml response
        
            if( isset( $doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue ) ) // si en la etique de rpta hay valor entra
            {
                $cdr = $doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue; //guadarmos la respuesta(text-xml) en la variable 
                $cdr = base64_decode($cdr); //decodificando el xml
                file_put_contents($ruta_archivo_cdr . 'R-' . $nombrezip, $cdr ); //guardo el CDR zip en la carpeta cdr
                $zip = new ZipArchive();
                if($zip->open($ruta_archivo_cdr. 'R-' . $nombrezip ) === true ) //rpta es identica existe el archivo
                {
                    $zip->extractTo($ruta_archivo_cdr, 'R-' . $nombre . '.XML');
                    $zip->close();
                }
                $estadofe = '1';
                echo 'Procesado correctamente, OK';
            }
            else {
                $estadofe = '2';
                $codigo = $doc->getElementsByTagName('faultcode')->item(0)->nodeValue;
                $mensaje = $doc->getElementsByTagName('faultstring')->item(0)->nodeValue;
                //LOG DE TRAX ERRORES DB
                echo 'Ocurrio un error con código: ' . $codigo . ' Msje:' . $mensaje;
            }
        }
        else { //Problemas de comunicacion
            $estadofe = "3";
            //LOG DE TRAX ERRORES DB
            echo curl_error($ch);
            echo 'Hubo existe un problema de conexión';
        }
        
        curl_close($ch);
        
        //ENVIAR EL ZIP A LOS WS DE SUNAT - FIN
        
    }

	public function EnviarResumenComprobantes($emisor,$nombre, $rutacertificado="", $ruta_archivo_xml = "xml/")
	{
		//firma del documento
		$objSignature = new Signature();

		$flg_firma = "0";
		//$ruta_archivo_xml = "xml/";
		$ruta = $ruta_archivo_xml.$nombre.'.XML';

		$ruta_firma = $rutacertificado."certificado_prueba.pfx";
		$pass_firma = "ceti";

		$resp = $objSignature->signature_xml($flg_firma, $ruta, $ruta_firma, $pass_firma);

		print_r($resp); //hash


		//Generar el .zip

		$zip = new ZipArchive();

		$nombrezip = $nombre.".ZIP";
		$rutazip = $ruta_archivo_xml.$nombre.".ZIP";

		if($zip->open($rutazip,ZIPARCHIVE::CREATE)===true){
			$zip->addFile($ruta, $nombre.'.XML');
			$zip->close();
		}


		//Enviamos el archivo a sunat

		$ws = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService";

		$ruta_archivo = $ruta_archivo_xml.$nombrezip;
		$nombre_archivo = $nombrezip;
		$ruta_archivo_cdr = "cdr/";

		$contenido_del_zip = base64_encode(file_get_contents($ruta_archivo));


		$xml_envio ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
				 <soapenv:Header>
				 	<wsse:Security>
				 		<wsse:UsernameToken>
				 			<wsse:Username>'.$emisor['ruc'].$emisor['usuario_sol'].'</wsse:Username>
				 			<wsse:Password>'.$emisor['clave_sol'].'</wsse:Password>
				 		</wsse:UsernameToken>
				 	</wsse:Security>
				 </soapenv:Header>
				 <soapenv:Body>
				 	<ser:sendSummary>
				 		<fileName>'.$nombre_archivo.'</fileName>
				 		<contentFile>'.$contenido_del_zip.'</contentFile>
				 	</ser:sendSummary>
				 </soapenv:Body>
				</soapenv:Envelope>';


			$header = array(
						"Content-type: text/xml; charset=\"utf-8\"",
						"Accept: text/xml",
						"Cache-Control: no-cache",
						"Pragma: no-cache",
						"SOAPAction: ",
						"Content-lenght: ".strlen($xml_envio)
					);


			$ch = curl_init();
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);
			curl_setopt($ch,CURLOPT_URL,$ws);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_ANY);
			curl_setopt($ch,CURLOPT_TIMEOUT,30);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$xml_envio);
			curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
			//para ejecutar los procesos de forma local en windows
			//enlace de descarga del cacert.pem https://curl.haxx.se/docs/caextract.html
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");


			$response = curl_exec($ch);

			$httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			$estadofe = "0";

			$ticket = "0";
			if($httpcode == 200){
				$doc = new DOMDocument();
				$doc->loadXML($response);

				if (isset($doc->getElementsByTagName('ticket')->item(0)->nodeValue)) {
	                $ticket = $doc->getElementsByTagName('ticket')->item(0)->nodeValue;
					echo "TODO OK NRO TK: ".$ticket;
				}else{		

					$codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
					$mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;
					echo "error ".$codigo.": ".$mensaje; 
				}

			}else{
				echo curl_error($ch);
				echo "Problema de conexión";
			}

			curl_close($ch);
			return $ticket;

	}


    function ConsultarTicket($emisor, $cabecera, $ticket, $ruta_archivo_cdr = "cdr/")
    {
		$ws = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService";
		$nombre	= $emisor["ruc"]."-".$cabecera["tipodoc"]."-".$cabecera["serie"]."-".$cabecera["correlativo"];
		$nombre_xml	= $nombre.".XML";

		//===============================================================//
		//FIRMADO DEL cpe CON CERTIFICADO DIGITAL
		$objSignature = new Signature();
		$flg_firma = "0";
		$ruta = $nombre_xml;

		$ruta_firma = "certificado_prueba.pfx";
		$pass_firma = "ceti";

		//===============================================================//

		//ALMACENAR EL ARCHIVO EN UN ZIP
		$zip = new ZipArchive();

		$nombrezip = $nombre.".ZIP";

		if($zip->open($nombrezip,ZIPARCHIVE::CREATE)===true){
			$zip->addFile($ruta, $nombre_xml);
			$zip->close();
		}

		//===============================================================//

		//ENVIAR ZIP A SUNAT
		$ruta_archivo = $nombre;
		$nombre_archivo = $nombre;
		//$ruta_archivo_cdr = "cdr/";

		//$contenido_del_zip = base64_encode(file_get_contents($ruta_archivo.'.ZIP'));
		//FIN ZIP

		$xml_envio = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                    <wsse:Username>'.$emisor['ruc'].$emisor['usuario_sol'].'</wsse:Username>
                    <wsse:Password>'.$emisor['clave_sol'].'</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:getStatus>
                    <ticket>' . $ticket . '</ticket>
                </ser:getStatus>
            </soapenv:Body>
        </soapenv:Envelope>';


		$header = array(
					"Content-type: text/xml; charset=\"utf-8\"",
					"Accept: text/xml",
					"Cache-Control: no-cache",
					"Pragma: no-cache",
					"SOAPAction: ",
					"Content-lenght: ".strlen($xml_envio)
				);


		$ch = curl_init();
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);
		curl_setopt($ch,CURLOPT_URL,$ws);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_ANY);
		curl_setopt($ch,CURLOPT_TIMEOUT,120);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml_envio);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		//para ejecutar los procesos de forma local en windows
		//enlace de descarga del cacert.pem https://curl.haxx.se/docs/caextract.html
		curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");

		$response = curl_exec($ch);
		$httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);

		echo "codigo:".$httpcode;

		if($httpcode == 200){
			$doc = new DOMDocument();
			$doc->loadXML($response);

			if(isset($doc->getElementsByTagName('content')->item(0)->nodeValue)){
				$cdr = $doc->getElementsByTagName('content')->item(0)->nodeValue;
				$cdr = base64_decode($cdr);
				

				file_put_contents($ruta_archivo_cdr."R-".$nombre_archivo.".ZIP", $cdr);

				$zip = new ZipArchive;
				if($zip->open($ruta_archivo_cdr."R-".$nombre_archivo.".ZIP")===true){
					$zip->extractTo($ruta_archivo_cdr,'R-'.$nombre_archivo.'.XML');
					$zip->close();
				}
				echo "TODO OK";
			}else{		
				$codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
				$mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;
				echo "error ".$codigo.": ".$mensaje; 
			}

		}else{
			echo curl_error($ch);
			echo "Problema de conexión";
		}

		curl_close($ch);
	}

	
    function consultarComprobante($emisor, $comprobante)
    {
		try{
				$ws = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService";
				$soapUser = "";  
				$soapPassword = "";

				$xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
				xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" 
				xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
					<soapenv:Header>
						<wsse:Security>
							<wsse:UsernameToken>
								<wsse:Username>'.$emisor['ruc'].$emisor['usuariosol'].'</wsse:Username>
								<wsse:Password>'.$emisor['clavesol'].'</wsse:Password>
							</wsse:UsernameToken>
						</wsse:Security>
					</soapenv:Header>
					<soapenv:Body>
						<ser:getStatus>
							<rucComprobante>'.$emisor['ruc'].'</rucComprobante>
							<tipoComprobante>'.$comprobante['tipodoc'].'</tipoComprobante>
							<serieComprobante>'.$comprobante['serie'].'</serieComprobante>
							<numeroComprobante>'.$comprobante['correlativo'].'</numeroComprobante>
						</ser:getStatus>
					</soapenv:Body>
				</soapenv:Envelope>';
			
				$headers = array(
					"Content-type: text/xml;charset=\"utf-8\"",
					"Accept: text/xml",
					"Cache-Control: no-cache",
					"Pragma: no-cache",
					"SOAPAction: ",
					"Content-length: " . strlen($xml_post_string),
				); 			
			
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($ch, CURLOPT_URL, $ws);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
				//para ejecutar los procesos de forma local en windows
				//enlace de descarga del cacert.pem https://curl.haxx.se/docs/caextract.html
				curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");

				$response = curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				echo var_dump($response);
				
			} catch (Exception $e) {
				echo "SUNAT ESTA FUERA SERVICIO: ".$e->getMessage();
			}
    }


}

?>