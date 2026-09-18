import re

with open('app/Services/SRI/sri_factura.py', 'r', encoding='utf-8') as f:
    content = f.read()

replacement = """
                if "AUTORIZADO" in resp_autorizacion and "<estado>AUTORIZADO</estado>" in resp_autorizacion:
                    estado_final = "AUTORIZADO"
                    break
                
                if "<estado>NO AUTORIZADO</estado>" in resp_autorizacion or "RECHAZADA" in resp_autorizacion:
                    estado_final = "NO AUTORIZADO"
                    break
"""

content = re.sub(r'if "AUTORIZADO" in resp_autorizacion(.*?)(?=if estado_final == "SIN_RESPUESTA":)', replacement, content, flags=re.DOTALL)

# Add XML parser for messages
parser_addition = """
        mensaje_parseado = ""
        try:
            from xml.dom import minidom
            doc = minidom.parseString(resp_autorizacion if "autorizacion" in resp_autorizacion else resp_recepcion)
            mensajes = doc.getElementsByTagName('mensaje')
            for m in mensajes:
                if m.firstChild:
                    mensaje_parseado += m.firstChild.nodeValue + " | "
            infoAdicional = doc.getElementsByTagName('informacionAdicional')
            for i in infoAdicional:
                 if i.firstChild:
                      mensaje_parseado += i.firstChild.nodeValue + " | "
        except:
            pass
            
        print(json.dumps({
            "estado": estado_final,
            "clave_acceso": clave,
            "xml_autorizacion": resp_autorizacion,
            "mensaje": mensaje_parseado.strip(' | ') if mensaje_parseado else 'Procesado'
        }))
"""

content = re.sub(r'print\(json\.dumps\(\{(.*?)\}\)\)', parser_addition.strip(), content, flags=re.DOTALL)


with open('app/Services/SRI/sri_factura.py', 'w', encoding='utf-8') as f:
    f.write(content)

print("sri_factura.py updated")
