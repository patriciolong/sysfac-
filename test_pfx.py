import sys
from cryptography.hazmat.primitives.serialization import pkcs12

path = "storage/app/private/certificados/1789767632_margarita.pfx"
password = b"MARgariTA8"

try:
    with open(path, "rb") as f:
        p12_data = f.read()
    
    print(f"File size: {len(p12_data)} bytes")
    
    try:
        # First try normal load
        private_key, certificate, additional_certificates = pkcs12.load_key_and_certificates(
            p12_data,
            password
        )
        print("SUCCESS! Certificate loaded normally.")
    except Exception as e:
        print(f"Error loading normally: {str(e)}")
        
        # Try with different encodings? 
        # But password is ascii, no special chars!
        # Maybe the pfx uses weak algorithms (RC2, etc.) that are disabled by default in newer OpenSSL/cryptography.
        # We can try to use backend from cryptography.hazmat.backends.openssl.backend ?

except Exception as ex:
    print(f"General error: {str(ex)}")
