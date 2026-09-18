import re
import subprocess

with open('resources/views/facturacion/_creacion.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Extract script blocks
scripts = re.findall(r'<script>(.*?)</script>', content, re.DOTALL)

for i, script in enumerate(scripts):
    with open(f'scratch/script_{i}.js', 'w', encoding='utf-8') as sf:
        sf.write(script)
    
    print(f"Checking script_{i}.js...")
    result = subprocess.run(['node', '-c', f'scratch/script_{i}.js'], capture_output=True, text=True)
    if result.returncode != 0:
        print(f"Error in script_{i}.js:\n{result.stderr}")
    else:
        print(f"script_{i}.js is OK.")
