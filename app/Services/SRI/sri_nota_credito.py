import sys
import json
import base64
import time
import os
import re
from datetime import datetime
from lxml import etree

# Importar lógica común de firma y envío al SRI
sys.path.append(os.path.dirname(__file__))
from sri_common import generar_clave_acceso, firmar_xml_xades, enviar_recepcion, consultar_autorizacion

def construir_nota_credito(data, clave):
    nsmap = {
        "ds": "http://www.w3.org/2000/09/xmldsig#",
        "xsi": "http://www.w3.org/2001/XMLSchema-instance"
    }
    
    root = etree.Element("notaCredito", nsmap=nsmap, id="comprobante", version="1.1.0")
    
    # --- INFO TRIBUTARIA ---
    infoTrib = etree.SubElement(root, "infoTributaria")
    etree.SubElement(infoTrib, "ambiente").text = str(data['ambiente'])
    etree.SubElement(infoTrib, "tipoEmision").text = "1"
    etree.SubElement(infoTrib, "razonSocial").text = str(data['emisor']['razon_social']).replace("\n", " ").strip()
    
    nombre_comercial = data['emisor'].get('nombre_comercial') or data['emisor']['razon_social']
    etree.SubElement(infoTrib, "nombreComercial").text = str(nombre_comercial).replace("\n", " ").strip()
    
    etree.SubElement(infoTrib, "ruc").text = str(data['emisor']['ruc'])
    etree.SubElement(infoTrib, "claveAcceso").text = clave
    etree.SubElement(infoTrib, "codDoc").text = "04" # 04 = Nota de Crédito
    etree.SubElement(infoTrib, "estab").text = str(data['nota_credito']['establecimiento'])
    etree.SubElement(infoTrib, "ptoEmi").text = str(data['nota_credito']['punto_emision'])
    etree.SubElement(infoTrib, "secuencial").text = str(data['nota_credito']['secuencial'])
    etree.SubElement(infoTrib, "dirMatriz").text = str(data['emisor']['direccion_matriz']).replace("\n", " ").strip()
    
    regimen = data['emisor'].get('regimen_rimpe', '')
    if regimen and regimen.strip() not in ['NINGUNO', 'NO APLICA']:
        etree.SubElement(infoTrib, "contribuyenteRimpe").text = str(regimen).strip()

    # --- INFO NOTA CREDITO ---
    infoNC = etree.SubElement(root, "infoNotaCredito")
    etree.SubElement(infoNC, "fechaEmision").text = data['nota_credito']['fecha_emision']
    
    dir_estab = data['emisor'].get('direccion_establecimiento') or data['emisor']['direccion_matriz']
    etree.SubElement(infoNC, "dirEstablecimiento").text = str(dir_estab).replace("\n", " ").strip()
    
    etree.SubElement(infoNC, "tipoIdentificacionComprador").text = str(data['cliente']['tipo_identificacion'])
    etree.SubElement(infoNC, "razonSocialComprador").text = str(data['cliente']['razon_social']).strip()
    etree.SubElement(infoNC, "identificacionComprador").text = str(data['cliente']['identificacion']).strip()
    
    if data['emisor'].get('contribuyente_especial'):
        etree.SubElement(infoNC, "contribuyenteEspecial").text = str(data['emisor']['contribuyente_especial'])
        
    etree.SubElement(infoNC, "obligadoContabilidad").text = str(data['emisor']['obligado_contabilidad'])
    
    # Documento modificado (Factura)
    etree.SubElement(infoNC, "codDocModificado").text = "01"
    etree.SubElement(infoNC, "numDocModificado").text = str(data['nota_credito']['num_doc_modificado'])
    etree.SubElement(infoNC, "fechaEmisionDocSustento").text = str(data['nota_credito']['fecha_emision_doc_sustento'])
    
    etree.SubElement(infoNC, "totalSinImpuestos").text = "{:.2f}".format(float(data['nota_credito']['total_sin_impuestos']))
    etree.SubElement(infoNC, "valorModificacion").text = "{:.2f}".format(float(data['nota_credito']['valor_modificacion']))
    etree.SubElement(infoNC, "moneda").text = "DOLAR"

    # --- TOTALES CON IMPUESTOS ---
    totalesImp = etree.SubElement(infoNC, "totalConImpuestos")
    
    if float(data['nota_credito'].get('base_imponible_0', 0)) > 0:
        ti0 = etree.SubElement(totalesImp, "totalImpuesto")
        etree.SubElement(ti0, "codigo").text = "2" # IVA
        etree.SubElement(ti0, "codigoPorcentaje").text = "0" # 0%
        etree.SubElement(ti0, "baseImponible").text = "{:.2f}".format(float(data['nota_credito']['base_imponible_0']))
        etree.SubElement(ti0, "valor").text = "0.00"

    if float(data['nota_credito'].get('base_imponible_iva', 0)) > 0:
        ti_iva = etree.SubElement(totalesImp, "totalImpuesto")
        etree.SubElement(ti_iva, "codigo").text = "2" # IVA
        cod_porc = str(data['nota_credito'].get('codigo_porcentaje_iva', '4'))
        etree.SubElement(ti_iva, "codigoPorcentaje").text = cod_porc
        etree.SubElement(ti_iva, "baseImponible").text = "{:.2f}".format(float(data['nota_credito']['base_imponible_iva']))
        etree.SubElement(ti_iva, "valor").text = "{:.2f}".format(float(data['nota_credito']['valor_iva']))

    # Motivo de la modificación
    motivo_clean = str(data['nota_credito'].get('motivo', 'Devolución de mercadería')).strip()
    etree.SubElement(infoNC, "motivo").text = motivo_clean[:300]

    # --- DETALLES ---
    detalles_node = etree.SubElement(root, "detalles")
    for item in data['detalles']:
        det = etree.SubElement(detalles_node, "detalle")
        etree.SubElement(det, "codigoInterno").text = str(item['codigo_interno'])[:25]
        
        if item.get('codigo_adicional'):
            etree.SubElement(det, "codigoAdicional").text = str(item['codigo_adicional'])[:25]
            
        etree.SubElement(det, "descripcion").text = str(item['descripcion']).strip()
        etree.SubElement(det, "cantidad").text = "{:.6f}".format(float(item['cantidad']))
        etree.SubElement(det, "precioUnitario").text = "{:.6f}".format(float(item['precio_unitario']))
        etree.SubElement(det, "descuento").text = "{:.2f}".format(float(item.get('descuento', 0)))
        etree.SubElement(det, "precioTotalSinImpuesto").text = "{:.2f}".format(float(item['precio_total_sin_impuestos']))
        
        # Impuestos del detalle
        imps = etree.SubElement(det, "impuestos")
        imp = etree.SubElement(imps, "impuesto")
        imp_codigo = str(item.get('codigo_impuesto', '2'))
        etree.SubElement(imp, "codigo").text = imp_codigo
        imp_porc = str(item.get('codigo_porcentaje_iva', '4'))
        etree.SubElement(imp, "codigoPorcentaje").text = imp_porc
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
    resp_recepcion = ""
    resp_autorizacion = ""
    try:
        raw_input = sys.argv[1]
        data = json.loads(base64.b64decode(raw_input).decode('utf-8'))
        
        # Generar o reutilizar Clave
        clave = data.get('clave_acceso_existente')
        if not clave or len(str(clave)) != 49:
            fecha_str = data['nota_credito']['fecha_emision'].replace('/', '').replace('-', '') # ddmmyyyy
            clave = generar_clave_acceso(
                fecha_str, 
                str(data['emisor']['ruc']), 
                str(data['ambiente']), 
                str(data['nota_credito']['establecimiento']), 
                str(data['nota_credito']['punto_emision']), 
                str(data['nota_credito']['secuencial']), 
                '04' # 04 = Nota de Crédito
            )

        xml_string = construir_nota_credito(data, clave)
        
        if 'config_firma' not in data or not os.path.exists(data['config_firma']['ruta']):
            raise Exception(f"Ruta de firma electrónica no válida o archivo no existe: {data.get('config_firma', {}).get('ruta')}")

        xml_firmado = firmar_xml_xades(xml_string, data['config_firma']['ruta'], data['config_firma']['pass'])
        
        # 1. ENVIAR A RECEPCIÓN
        resp_recepcion = enviar_recepcion(xml_firmado)
        
        estado_final = "SIN_RESPUESTA"
        resp_autorizacion = ""
        
        if "RECIBIDA" in resp_recepcion or "PROCESAMIENTO" in resp_recepcion:
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
            clean_xml = re.sub(r' xmlns="[^"]+"', '', resp_autorizacion if "autorizacion" in resp_autorizacion else resp_recepcion)
            clean_xml = re.sub(r'xmlns:?[^=]*="[^"]*"', '', clean_xml)
            clean_xml = re.sub(r'</?(?!xml)[a-zA-Z0-9]+:', '<', clean_xml)
            clean_xml = re.sub(r'</([a-zA-Z0-9]+):', r'</\1:', clean_xml)
            mensajes_matches = re.findall(r'<mensaje>([^<]*)</mensaje>', clean_xml)
            info_matches = re.findall(r'<informacionAdicional>([^<]*)</informacionAdicional>', clean_xml)
            
            todos_mensajes = []
            for m in mensajes_matches:
                if m.strip(): todos_mensajes.append(m.strip())
            for i in info_matches:
                if i.strip(): todos_mensajes.append(i.strip())
            
            if todos_mensajes:
                mensaje_parseado = " | ".join(todos_mensajes)
        except Exception:
            pass
            
        print(json.dumps({
            "estado": estado_final,
            "clave_acceso": clave,
            "xml_autorizacion": resp_autorizacion,
            "mensaje": mensaje_parseado if mensaje_parseado else 'Procesado'
        }))

    except Exception as e:
        mensaje_parseado = ""
        try:
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
        except Exception:
            pass
            
        print(json.dumps({
            "estado": estado_final if 'estado_final' in locals() else 'ERROR',
            "clave_acceso": clave if 'clave' in locals() else '',
            "xml_autorizacion": resp_autorizacion if 'resp_autorizacion' in locals() else '',
            "mensaje": mensaje_parseado if mensaje_parseado else str(e)
        }))
