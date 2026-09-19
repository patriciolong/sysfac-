# python_sri/sri_common.py
import os
import base64
import requests
import random
from datetime import datetime
from lxml import etree
from cryptography.hazmat.primitives.serialization import pkcs12
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.primitives import serialization

# CONFIGURACION Y CONSTANTES
# URLs bases, se seleccionaran dinamicamente en las funciones
URL_RECEPCION_PRUEBAS = "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl"
URL_AUTORIZACION_PRUEBAS = "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl"
URL_RECEPCION_PROD = "https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl"
URL_AUTORIZACION_PROD = "https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl"
NSMAP = {"ds": "http://www.w3.org/2000/09/xmldsig#", "etsi": "http://uri.etsi.org/01903/v1.3.2#"}

def generar_clave_acceso(fecha, ruc, ambiente, estab, pto_emi, secuencial, codigo_doc):
    import random
    fecha_fmt = fecha.replace('-', '')
    codigo_numerico = str(random.randint(10000000, 99999999))
    clave = f"{fecha_fmt}{codigo_doc}{ruc}{ambiente}{estab}{pto_emi}{secuencial}{codigo_numerico}1"
    suma = 0
    factor = 2
    for digit in reversed(clave):
        suma += int(digit) * factor
        factor += 1
        if factor > 7: factor = 2
    verificador = 11 - (suma % 11)
    if verificador == 11: verificador = 0
    elif verificador == 10: verificador = 1
    return f"{clave}{verificador}"

def obtener_issuer_invertido(cert):
    try:
        parts = []
        for rdn in cert.issuer:
            for attr in rdn:
                oid_name = attr.oid._name
                if oid_name == "countryName": oid = "C"
                elif oid_name == "organizationName": oid = "O"
                elif oid_name == "organizationalUnitName": oid = "OU"
                elif oid_name == "commonName": oid = "CN"
                elif oid_name == "serialNumber": oid = "serialNumber"
                elif oid_name == "localityName": oid = "L"
                elif oid_name == "stateOrProvinceName": oid = "ST"
                else: oid = oid_name
                parts.append(f"{oid}={attr.value}")
        return ",".join(reversed(parts))
    except:
        return cert.issuer.rfc4514_string()

def firmar_xml_xades(xml_str, ruta_p12, password):
    if not os.path.exists(ruta_p12): raise Exception(f"No existe firma: {ruta_p12}")
    with open(ruta_p12, "rb") as f: p12_data = f.read()
    private_key, certificate, additional_certs = pkcs12.load_key_and_certificates(p12_data, password.encode())
    
    parser = etree.XMLParser(remove_blank_text=True)
    root = etree.fromstring(xml_str.encode(), parser)
    if 'id' not in root.attrib: root.set('id', 'comprobante')

    signature_id = f"Signature-{random.randint(10000,99999)}"
    signed_props_id = f"SignedProperties-{random.randint(10000,99999)}"
    reference_id = f"Reference-ID-{random.randint(10000,99999)}"
    
    cert_der = certificate.public_bytes(serialization.Encoding.DER)
    h_cert = hashes.Hash(hashes.SHA1()); h_cert.update(cert_der)
    cert_digest = base64.b64encode(h_cert.finalize()).decode()
    cert_serial = str(certificate.serial_number)
    cert_issuer = obtener_issuer_invertido(certificate)

    xml_c14n = etree.tostring(root, method="c14n", exclusive=False, with_comments=False)
    h_comp = hashes.Hash(hashes.SHA1()); h_comp.update(xml_c14n)
    comprobante_digest = base64.b64encode(h_comp.finalize()).decode()

    signature_node = etree.Element(f"{{{NSMAP['ds']}}}Signature", Id=signature_id, nsmap=NSMAP)
    root.append(signature_node)

    signed_info = etree.SubElement(signature_node, f"{{{NSMAP['ds']}}}SignedInfo")
    etree.SubElement(signed_info, f"{{{NSMAP['ds']}}}CanonicalizationMethod", Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315")
    etree.SubElement(signed_info, f"{{{NSMAP['ds']}}}SignatureMethod", Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1")
    ref_comp = etree.SubElement(signed_info, f"{{{NSMAP['ds']}}}Reference", Id=reference_id, URI="#comprobante")
    etree.SubElement(etree.SubElement(ref_comp, f"{{{NSMAP['ds']}}}Transforms"), f"{{{NSMAP['ds']}}}Transform", Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature")
    etree.SubElement(ref_comp, f"{{{NSMAP['ds']}}}DigestMethod", Algorithm="http://www.w3.org/2000/09/xmldsig#sha1")
    etree.SubElement(ref_comp, f"{{{NSMAP['ds']}}}DigestValue").text = comprobante_digest

    obj = etree.SubElement(signature_node, f"{{{NSMAP['ds']}}}Object")
    qp = etree.SubElement(obj, f"{{{NSMAP['etsi']}}}QualifyingProperties", Target=f"#{signature_id}")
    sp = etree.SubElement(qp, f"{{{NSMAP['etsi']}}}SignedProperties", Id=signed_props_id)
    ssp = etree.SubElement(sp, f"{{{NSMAP['etsi']}}}SignedSignatureProperties")
    
    # SRI requires full timezone ISO format
    etree.SubElement(ssp, f"{{{NSMAP['etsi']}}}SigningTime").text = datetime.now().astimezone().replace(microsecond=0).isoformat()
    
    signing_cert = etree.SubElement(ssp, f"{{{NSMAP['etsi']}}}SigningCertificate")
    cert_node = etree.SubElement(signing_cert, f"{{{NSMAP['etsi']}}}Cert")
    cdn = etree.SubElement(cert_node, f"{{{NSMAP['etsi']}}}CertDigest")
    etree.SubElement(cdn, f"{{{NSMAP['ds']}}}DigestMethod", Algorithm="http://www.w3.org/2000/09/xmldsig#sha1")
    etree.SubElement(cdn, f"{{{NSMAP['ds']}}}DigestValue").text = cert_digest
    isn = etree.SubElement(cert_node, f"{{{NSMAP['etsi']}}}IssuerSerial")
    etree.SubElement(isn, f"{{{NSMAP['ds']}}}X509IssuerName").text = cert_issuer
    etree.SubElement(isn, f"{{{NSMAP['ds']}}}X509SerialNumber").text = cert_serial
    
    sdop = etree.SubElement(sp, f"{{{NSMAP['etsi']}}}SignedDataObjectProperties")
    dof = etree.SubElement(sdop, f"{{{NSMAP['etsi']}}}DataObjectFormat", ObjectReference=f"#{reference_id}")
    etree.SubElement(dof, f"{{{NSMAP['etsi']}}}Description").text = "contenido comprobante"
    etree.SubElement(dof, f"{{{NSMAP['etsi']}}}MimeType").text = "text/xml"

    # Calculate SP c14n AFTER appending to document tree
    sp_c14n = etree.tostring(sp, method="c14n", exclusive=False, with_comments=False)
    h_sp = hashes.Hash(hashes.SHA1()); h_sp.update(sp_c14n)
    sp_digest = base64.b64encode(h_sp.finalize()).decode()

    ref_props = etree.SubElement(signed_info, f"{{{NSMAP['ds']}}}Reference", URI=f"#{signed_props_id}", Type="http://uri.etsi.org/01903#SignedProperties")
    etree.SubElement(ref_props, f"{{{NSMAP['ds']}}}DigestMethod", Algorithm="http://www.w3.org/2000/09/xmldsig#sha1")
    etree.SubElement(ref_props, f"{{{NSMAP['ds']}}}DigestValue").text = sp_digest

    # Calculate SI c14n AFTER appending to document tree
    si_c14n = etree.tostring(signed_info, method="c14n", exclusive=False, with_comments=False)
    signature_val = base64.b64encode(private_key.sign(si_c14n, padding.PKCS1v15(), hashes.SHA1())).decode()

    sig_val_node = etree.Element(f"{{{NSMAP['ds']}}}SignatureValue")
    sig_val_node.text = signature_val
    signature_node.insert(1, sig_val_node)

    ki = etree.Element(f"{{{NSMAP['ds']}}}KeyInfo", Id=f"Certificate{signature_id}")
    signature_node.insert(2, ki)
    
    public_key = certificate.public_key()
    from cryptography.hazmat.primitives.asymmetric import rsa
    if isinstance(public_key, rsa.RSAPublicKey):
        pn = public_key.public_numbers()
        kv = etree.SubElement(ki, f"{{{NSMAP['ds']}}}KeyValue")
        rkv = etree.SubElement(kv, f"{{{NSMAP['ds']}}}RSAKeyValue")
        mod_bytes = pn.n.to_bytes((pn.n.bit_length() + 7) // 8, byteorder='big')
        exp_bytes = pn.e.to_bytes((pn.e.bit_length() + 7) // 8, byteorder='big')
        etree.SubElement(rkv, f"{{{NSMAP['ds']}}}Modulus").text = base64.b64encode(mod_bytes).decode()
        etree.SubElement(rkv, f"{{{NSMAP['ds']}}}Exponent").text = base64.b64encode(exp_bytes).decode()
        
    xd = etree.SubElement(ki, f"{{{NSMAP['ds']}}}X509Data")
    cert_clean = "".join(certificate.public_bytes(serialization.Encoding.PEM).decode().splitlines()[1:-1])
    etree.SubElement(xd, f"{{{NSMAP['ds']}}}X509Certificate").text = cert_clean
    if additional_certs:
        for ac in additional_certs:
            ac_clean = "".join(ac.public_bytes(serialization.Encoding.PEM).decode().splitlines()[1:-1])
            etree.SubElement(xd, f"{{{NSMAP['ds']}}}X509Certificate").text = ac_clean

    return etree.tostring(root, xml_declaration=True, encoding="UTF-8", standalone=True).decode()

def enviar_recepcion(xml_firmado):
    import re
    m = re.search(r'<claveAcceso>(\d{49})</claveAcceso>', xml_firmado)
    ambiente = m.group(1)[23] if m else '1'
    url = URL_RECEPCION_PROD if ambiente == '2' else URL_RECEPCION_PRUEBAS
    xml_b64 = base64.b64encode(xml_firmado.encode()).decode()
    envelope = f"""<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.recepcion"><soapenv:Header/><soapenv:Body><ec:validarComprobante><xml>{xml_b64}</xml></ec:validarComprobante></soapenv:Body></soapenv:Envelope>"""
    try: return requests.post(url, data=envelope, headers={'Content-Type': 'text/xml;charset=UTF-8'}).text
    except Exception as e: return str(e)

def consultar_autorizacion(clave_acceso):
    ambiente = clave_acceso[23] if len(clave_acceso) == 49 else '1'
    url = URL_AUTORIZACION_PROD if ambiente == '2' else URL_AUTORIZACION_PRUEBAS
    envelope = f"""<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.autorizacion"><soapenv:Header/><soapenv:Body><ec:autorizacionComprobante><claveAccesoComprobante>{clave_acceso}</claveAccesoComprobante></ec:autorizacionComprobante></soapenv:Body></soapenv:Envelope>"""
    try: return requests.post(url, data=envelope, headers={'Content-Type': 'text/xml;charset=UTF-8'}).text
    except Exception as e: return str(e)