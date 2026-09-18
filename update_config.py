import re

with open('app/Http/Controllers/ConfiguracionController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Modify update validation
content = content.replace(
    "'firma_clave' => 'nullable|string'",
    "'firma_clave' => 'nullable|string',\n            'establecimiento' => 'nullable|string|size:3',\n            'punto_emision' => 'nullable|string|size:3',\n            'secuencial_factura' => 'nullable|integer|min:1'"
)

# Modify update to save PuntoEmision
sync_code = r"""
        // Actualizar Punto de Emision
        $punto = \App\Models\PuntoEmision::where('estado', 'ACTIVO')->first();
        if (!$punto) {
            $punto = new \App\Models\PuntoEmision();
            $punto->estado = 'ACTIVO';
        }
        if ($request->filled('establecimiento')) $punto->establecimiento = str_pad($request->establecimiento, 3, '0', STR_PAD_LEFT);
        if ($request->filled('punto_emision')) $punto->punto_emision = str_pad($request->punto_emision, 3, '0', STR_PAD_LEFT);
        if ($request->filled('secuencial_factura')) $punto->secuencial_factura = $request->secuencial_factura;
        $punto->save();
        
        return redirect('/configuracion')->with('success', 'Configuración actualizada correctamente');
"""

content = content.replace("return redirect('/configuracion')->with('success', 'Configuración actualizada correctamente');", sync_code.strip())
content = content.replace("return redirect('/configuracion')->with('success', 'Configuracin actualizada correctamente');", sync_code.strip())

with open('app/Http/Controllers/ConfiguracionController.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("ConfiguracionController updated")
