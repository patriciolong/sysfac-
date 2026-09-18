import re

with open('app/Http/Controllers/FacturacionController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace sequence calculation
old_seq_code = """
            $puntoEmision = PuntoEmision::where('estado', 'ACTIVO')->lockForUpdate()->first();
            $secuencialNum = $puntoEmision ? $puntoEmision->secuencial_factura : 1;
            $secuencialPadded = str_pad($secuencialNum, 9, '0', STR_PAD_LEFT);
            $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
            $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';
"""

new_seq_code = """
            $puntoEmision = PuntoEmision::where('estado', 'ACTIVO')->lockForUpdate()->first();
            $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
            $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';
            
            // Lógica para secuencial: usar la última factura si existe, sino el inicial de config
            $ultimaFactura = Factura::where('establecimiento', $estab)
                                    ->where('punto_emision', $pto)
                                    ->orderBy('secuencial', 'desc')
                                    ->first();
                                    
            if ($ultimaFactura) {
                $secuencialNum = ((int) $ultimaFactura->secuencial) + 1;
            } else {
                $secuencialNum = $puntoEmision ? $puntoEmision->secuencial_factura : 1;
            }
            
            $secuencialPadded = str_pad($secuencialNum, 9, '0', STR_PAD_LEFT);
"""

content = content.replace(old_seq_code.strip(), new_seq_code.strip())

# Remove $puntoEmision->increment('secuencial_factura');
old_inc = """
            if ($puntoEmision) {
                $puntoEmision->increment('secuencial_factura');
            }
"""
content = content.replace(old_inc.strip(), "")

# Let's also fix how `siguiente_secuencial_factura_formatted` behaves in `index()` since it needs the same logic
old_index_seq = "$secuencial_siguiente = $puntoEmision ? $puntoEmision->siguiente_secuencial_factura_formatted : '001-001-000000001';"

new_index_seq = """
        $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
        $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';
        $ultimaFactura = Factura::where('establecimiento', $estab)
                                ->where('punto_emision', $pto)
                                ->orderBy('secuencial', 'desc')
                                ->first();
        if ($ultimaFactura) {
            $nextSec = ((int) $ultimaFactura->secuencial) + 1;
        } else {
            $nextSec = $puntoEmision ? $puntoEmision->secuencial_factura : 1;
        }
        $secuencial_siguiente = $estab . '-' . $pto . '-' . str_pad($nextSec, 9, '0', STR_PAD_LEFT);
"""
content = content.replace(old_index_seq, new_index_seq.strip())

# And also for the return response after store
old_resp_seq = "'siguiente_secuencial' => $puntoEmision ? $puntoEmision->fresh()->siguiente_secuencial_factura_formatted : ''"
new_resp_seq = "'siguiente_secuencial' => $estab . '-' . $pto . '-' . str_pad($secuencialNum + 1, 9, '0', STR_PAD_LEFT)"
content = content.replace(old_resp_seq, new_resp_seq)


with open('app/Http/Controllers/FacturacionController.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("FacturacionController sequences updated")
