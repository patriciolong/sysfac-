import sys
import json
import base64
import time
import os
from datetime import datetime
from lxml import etree

# Importar lógica común de firma y envío al SRI
sys.path.append(os.path.dirname(__file__))
from sri_common import generar_clave_acceso, firmar_xml_xades, enviar_recepcion, consultar_autorizacion

def construir_factura(data, clave):
    nsmap = {
        "ds": "http://www.w3.org/2000/09/xmldsig#",
        "xsi": "http://www.w3.org/2001/XMLSchema-instance"
    }
    
    root = etree.Element("factura", nsmap=nsmap, id="comprobante", version="1.1.0")
    
    # --- INFO TRIBUTARIA ---
    infoTrib = etree.SubElement(root, "infoTributaria")
    etree.SubElement(infoTrib, "ambiente").text = str(data['ambiente'])
    etree.SubElement(infoTrib, "tipoEmision").text = "1"
    etree.SubElement(infoTrib, "razonSocial").text = str(data['emisor']['razon_social']).replace("\n", " ").strip()
    
    nombre_comercial = data['emisor'].get('nombre_comercial') or data['emisor']['razon_social']
    etree.SubElement(infoTrib, "nombreComercial").text = str(nombre_comercial).replace("\n", " ").strip()
    
    etree.SubElement(infoTrib, "ruc").text = str(data['emisor']['ruc'])
    etree.SubElement(infoTrib, "claveAcceso").text = clave
    etree.SubElement(infoTrib, "codDoc").text = "01" # 01 = Factura
    etree.SubElement(infoTrib, "estab").text = str(data['factura']['establecimiento'])
    etree.SubElement(infoTrib, "ptoEmi").text = str(data['factura']['punto_emision'])
    etree.SubElement(infoTrib, "secuencial").text = str(data['factura']['secuencial'])
    etree.SubElement(infoTrib, "dirMatriz").text = str(data['emisor']['direccion_matriz']).replace("\n", " ").strip()
    
    regimen = data['emisor'].get('regimen_rimpe', '')
    if regimen and regimen.strip() not in ['NINGUNO', 'NO APLICA']:
         etree.SubElement(infoTrib, "contribuyenteRimpe").text = str(regimen).strip()

    # --- INFO FACTURA ---
    infoFac = etree.SubElement(root, "infoFactura")
    etree.SubElement(infoFac, "fechaEmision").text = data['factura']['fecha_emision']
    dir_estab = data['emisor'].get('direccion_establecimiento') or data['emisor']['direccion_matriz']
    etree.SubElement(infoFac, "dirEstablecimiento").text = str(dir_estab).replace("\n", " ").strip()
    
    if data['emisor'].get('contribuyente_especial'):
        etree.SubElement(infoFac, "contribuyenteEspecial").text = str(data['emisor']['contribuyente_especial'])
        
    etree.SubElement(infoFac, "obligadoContabilidad").text = str(data['emisor']['obligado_contabilidad'])
    
    etree.SubElement(infoFac, "tipoIdentificacionComprador").text = str(data['cliente']['tipo_identificacion'])
    etree.SubElement(infoFac, "razonSocialComprador").text = str(data['cliente']['razon_social']).strip()
    etree.SubElement(infoFac, "identificacionComprador").text = str(data['cliente']['identificacion']).strip()
    
    if data['cliente'].get('direccion'):
        etree.SubElement(infoFac, "direccionComprador").text = str(data['cliente']['direccion']).strip()
        
    etree.SubElement(infoFac, "totalSinImpuestos").text = "{:.2f}".format(float(data['factura']['total_sin_impuestos']))
    etree.SubElement(infoFac, "totalDescuento").text = "{:.2f}".format(float(data['factura'].get('total_descuento', 0)))

    # --- TOTALES CON IMPUESTOS ---
    totalesImp = etree.SubElement(infoFac, "totalConImpuestos")
    
    if float(data['factura'].get('base_imponible_0', 0)) > 0:
        ti0 = etree.SubElement(totalesImp, "totalImpuesto")
        etree.SubElement(ti0, "codigo").text = "2" # IVA
        etree.SubElement(ti0, "codigoPorcentaje").text = "0" # 0%
        etree.SubElement(ti0, "baseImponible").text = "{:.2f}".format(float(data['factura']['base_imponible_0']))
        etree.SubElement(ti0, "valor").text = "0.00"

    if float(data['factura'].get('base_imponible_iva', 0)) > 0:
        ti_iva = etree.SubElement(totalesImp, "totalImpuesto")
        etree.SubElement(ti_iva, "codigo").text = "2" # IVA
        cod_porc = str(data['factura'].get('codigo_porcentaje_iva', '4'))
        etree.SubElement(ti_iva, "codigoPorcentaje").text = cod_porc
        etree.SubElement(ti_iva, "baseImponible").text = "{:.2f}".format(float(data['factura']['base_imponible_iva']))
        etree.SubElement(ti_iva, "valor").text = "{:.2f}".format(float(data['factura']['valor_iva']))

    if data['factura'].get('propina') and float(data['factura']['propina']) > 0:
        etree.SubElement(infoFac, "propina").text = "{:.2f}".format(float(data['factura']['propina']))
    else:
        etree.SubElement(infoFac, "propina").text = "0.00"
        
    etree.SubElement(infoFac, "importeTotal").text = "{:.2f}".format(float(data['factura']['importe_total']))
    etree.SubElement(infoFac, "moneda").text = "DOLAR"

    # --- PAGOS ---
    if 'pagos' in data and data['pagos']:
        pagos = etree.SubElement(infoFac, "pagos")
        for p in data['pagos']:
            pago = etree.SubElement(pagos, "pago")
            etree.SubElement(pago, "formaPago").text = str(p['codigo_sri']).zfill(2)
            etree.SubElement(pago, "total").text = "{:.2f}".format(float(p['total']))
            etree.SubElement(pago, "plazo").text = str(p.get('plazo', '0'))
            etree.SubElement(pago, "unidadTiempo").text = str(p.get('unidad_tiempo', 'dias')).upper()

    # --- DETALLES ---
    detalles_node = etree.SubElement(root, "detalles")
    for item in data['detalles']:
        det = etree.SubElement(detalles_node, "detalle")
        etree.SubElement(det, "codigoPrincipal").text = str(item['codigo_principal'])[:25]
        
        if item.get('codigo_auxiliar'):
            etree.SubElement(det, "codigoAuxiliar").text = str(item['codigo_auxiliar'])[:25]
            
        etree.SubElement(det, "descripcion").text = str(item['descripcion']).strip()
        etree.SubElement(det, "cantidad").text = "{:.6f}".format(float(item['cantidad']))
        etree.SubElement(det, "precioUnitario").text = "{:.6f}".format(float(item['precio_unitario']))
        etree.SubElement(det, "descuento").text = "{:.2f}".format(float(item.get('descuento', 0)))
        etree.SubElement(det, "precioTotalSinImpuesto").text = "{:.2f}".format(float(item['precio_total_sin_impuestos']))
        
        # Impuestos del detalle
        imps = etree.SubElement(det, "impuestos")
        imp = etree.SubElement(imps, "impuesto")
        etree.SubElement(imp, "codigo").text = "2" # IVA
        etree.SubElement(imp, "codigoPorcentaje").text = str(item.get('codigo_porcentaje_iva', '4'))
        etree.SubElement(imp, "tarifa").text = "{:.2f}".format(float(item.get('tarifa_iva', 15)))
        etree.SubElement(imp, "baseImponible").text = "{:.2f}".format(float(item['base_imponible']))
        etree.SubElement(imp, "valor").text = "{:.2f}".format(float(item['valor_iva']))

    # --- INFO ADICIONAL ---
    if data['cliente'].get('correo'):
        infoAdic = etree.SubElement(root, "infoAdicional")
        c1 = etree.SubElement(infoAdic, "campoAdicional", nombre="Email")
        c1.text = str(data['cliente']['correo']).strip()

    return etree.tostring(root, encoding="UTF-8", xml_declaration=True, standalone=True).decode()

if __name__ == "__main__":
    try:
        raw_input = sys.argv[1]
        data = json.loads(base64.b64decode(raw_input).decode('utf-8'))
        
        # Generar o reutilizar Clave
        clave = data.get('clave_acceso_existente')
        if not clave or len(str(clave)) != 49:
            fecha_str = data['factura']['fecha_emision'].replace('/', '').replace('-', '') # ddmmyyyy
            clave = generar_clave_acceso(
                fecha_str, 
                str(data['emisor']['ruc']), 
                str(data['ambiente']), 
                str(data['factura']['establecimiento']), 
                str(data['factura']['punto_emision']), 
                str(data['factura']['secuencial']), 
                '01'
            )

        xml_string = construir_factura(data, clave)
        
        if 'config_firma' not in data or not os.path.exists(data['config_firma']['ruta']):
            raise Exception(f"Ruta de firma electrónica no válida o archivo no existe: {data.get('config_firma', {}).get('ruta')}")

        xml_firmado = firmar_xml_xades(xml_string, data['config_firma']['ruta'], data['config_firma']['pass'])
        
        # 1. ENVIAR A RECEPCIÓN
        resp_recepcion = enviar_recepcion(xml_firmado)
        
        estado_final = "SIN_RESPUESTA"
        resp_autorizacion = ""
        
        # Aceptamos RECIBIDA o PROCESAMIENTO como señal de éxito inicial
        if "RECIBIDA" in resp_recepcion or "PROCESAMIENTO" in resp_recepcion:
            
            # --- INICIO DEL BUCLE DE ESPERA ---
            intentos_max = 6
            espera = 3 # Segundos
            
            for i in range(intentos_max):
                time.sleep(espera)
                resp_autorizacion = consultar_autorizacion(clave)
                
                
                if "AUTORIZADO" in resp_autorizacion and "<estado>AUTORIZADO</estado>" in resp_autorizacion:
                    estado_final = "AUTORIZADO"
                    break
                
                if "<estado>NO AUTORIZADO</estado>" in resp_autorizacion or "RECHAZADA" in resp_autorizacion:
                    estado_final = "NO AUTORIZADO"
                    break
            if estado_final == "SIN_RESPUESTA":
                estado_final = "EN_PROCESO"
                resp_autorizacion = resp_recepcion

        else:
            estado_final = "DEVUELTA"
            resp_autorizacion = resp_recepcion

        mensaje_parseado = ""
        try:
            import xml.etree.ElementTree as ET
            import re
            
            # Clean up namespaces for easier parsing
            clean_xml = re.sub(r' xmlns="[^"]+"', '', resp_autorizacion if "autorizacion" in resp_autorizacion else resp_recepcion)
            clean_xml = re.sub(r'xmlns:?[^=]*="[^"]*"', '', clean_xml)
            # Remove namespace prefixes like soap: or ns2:
            clean_xml = re.sub(r'</?(?!xml)[a-zA-Z0-9]+:', '<', clean_xml)
            clean_xml = re.sub(r'</([a-zA-Z0-9]+):', r'</\1:', clean_xml)
            # Actually easier to just find tags via regex if XML is tricky
            mensajes_matches = re.findall(r'<mensaje>([^<]*)</mensaje>', clean_xml)
            info_matches = re.findall(r'<informacionAdicional>([^<]*)</informacionAdicional>', clean_xml)
            
            todos_mensajes = []
            for m in mensajes_matches:
                if m.strip(): todos_mensajes.append(m.strip())
            for i in info_matches:
                if i.strip(): todos_mensajes.append(i.strip())
            
            if todos_mensajes:
                mensaje_parseado = " | ".join(todos_mensajes)
        except:
            pass
            
        print(json.dumps({
            "estado": estado_final,
            "clave_acceso": clave,
            "xml_autorizacion": resp_autorizacion,
            "mensaje": mensaje_parseado if mensaje_parseado else 'Procesado'
        }))

    except Exception as e:
        import traceback
        mensaje_parseado = ""
        try:
            import xml.etree.ElementTree as ET
            import re
            clean_xml = re.sub(r' xmlns="[^"]+"', '', resp_autorizacion if "autorizacion" in resp_autorizacion else resp_recepcion)
            clean_xml = re.sub(r'xmlns:?[^=]*="[^"]*"', '', clean_xml)
            mensajes_matches = re.findall(r'<mensaje>([^<]*)</mensaje>', clean_xml)
            info_matches = re.findall(r'<informacionAdicional>([^<]*)</informacionAdicional>', clean_xml)
            
            todos_mensajes = []
            for m in mensajes_matches:
                if m.strip(): todos_mensajes.append(m.strip())
            for i in info_matches:
                if i.strip(): todos_mensajes.append(i.strip())
            
            if todos_mensajes:
                mensaje_parseado = " | ".join(todos_mensajes)
        except:
            pass
            
        print(json.dumps({
            "estado": estado_final if 'estado_final' in locals() else 'ERROR',
            "clave_acceso": clave if 'clave' in locals() else '',
            "xml_autorizacion": resp_autorizacion if 'resp_autorizacion' in locals() else '',
            "mensaje": mensaje_parseado if mensaje_parseado else str(e)
        }))
