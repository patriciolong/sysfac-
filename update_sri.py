import os
import re
with open('app/Services/SRI/sri_common.py', 'r') as f:
    content = f.read()

key_info_code = '''    ki = etree.SubElement(signature_node, f"{{NSMAP['ds']}}}KeyInfo", Id=f"Certificate{signature_id}")
    
    public_key = certificate.public_key()
    from cryptography.hazmat.primitives.asymmetric import rsa
    if isinstance(public_key, rsa.RSAPublicKey):
        pn = public_key.public_numbers()
        kv = etree.SubElement(ki, f"{{NSMAP['ds']}}}KeyValue")
        rkv = etree.SubElement(kv, f"{{NSMAP['ds']}}}RSAKeyValue")
        mod_bytes = pn.n.to_bytes((pn.n.bit_length() + 7) // 8, byteorder='big')
        exp_bytes = pn.e.to_bytes((pn.e.bit_length() + 7) // 8, byteorder='big')
        etree.SubElement(rkv, f"{{NSMAP['ds']}}}Modulus").text = base64.b64encode(mod_bytes).decode()
        etree.SubElement(rkv, f"{{NSMAP['ds']}}}Exponent").text = base64.b64encode(exp_bytes).decode()
        
    xd = etree.SubElement(ki, f"{{NSMAP['ds']}}}X509Data")'''

content = content.replace('    ki = etree.SubElement(signature_node, f"{{NSMAP[\\'ds\\']}}}KeyInfo")\n    xd = etree.SubElement(ki, f"{{NSMAP[\\'ds\\']}}}X509Data")', key_info_code)

with open('app/Services/SRI/sri_common.py', 'w') as f:
    f.write(content)

