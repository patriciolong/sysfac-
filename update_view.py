import re

with open('resources/views/configuracion/index.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

punto_emision_html = """
    </div>

    <!-- CARD 3: PUNTO DE EMISION -->
    <div class="data-card" style="margin-top: 1.15rem;">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-file-invoice text-primary"></i> Configuración de Secuenciales (Facturación)</h2>
        </div>
        <div style="padding: 1rem;">
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">
                Configure el establecimiento y punto de emisión. El sistema usará el secuencial configurado aquí para la <strong>primera factura</strong>. 
                A partir de la segunda, continuará automáticamente basándose en la última factura guardada en la base de datos.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Establecimiento (Ej: 001)</label>
                    <input type="text" name="establecimiento" class="form-control" value="{{ $puntos_emision->first() ? $puntos_emision->first()['establecimiento'] : '001' }}" maxlength="3" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Punto de Emisión (Ej: 001)</label>
                    <input type="text" name="punto_emision" class="form-control" value="{{ $puntos_emision->first() ? $puntos_emision->first()['punto_emision'] : '001' }}" maxlength="3" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Secuencial Inicial Factura</label>
                    <input type="number" name="secuencial_factura" class="form-control" value="{{ $puntos_emision->first() ? $puntos_emision->first()['secuencial_factura'] : '1' }}" min="1" required>
                </div>
            </div>
        </div>
    </div>
"""

content = content.replace("    </div>\n</form>", punto_emision_html + "\n</form>")

with open('resources/views/configuracion/index.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("index.blade.php updated")
