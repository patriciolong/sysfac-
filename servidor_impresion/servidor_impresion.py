"""
=============================================================================
SysFact+ - Servidor de Impresión Térmica Local
Servicio de impresión directa para Facturación Electrónica SRI (Ecuador),
Tickets de Venta, Notas de Crédito y Arqueos de Caja.
=============================================================================
"""

import json
import os
import sys
import threading
import tkinter as tk
from http.server import BaseHTTPRequestHandler, HTTPServer
from tkinter import messagebox, ttk
from PIL import Image, ImageDraw
import pystray
import win32con
import win32print
import win32ui

if getattr(sys, "frozen", False):
    application_path = os.path.dirname(sys.executable)
else:
    application_path = os.path.dirname(os.path.abspath(__file__))

CONFIG_FILE = os.path.join(application_path, "printer_config.json")
DEFAULT_PORT = 8080


class SysFactPrintServer:
    def __init__(self, root):
        self.root = root
        self.root.title("SysFact+ | Servidor de Impresión Térmica")
        self.root.geometry("450x380")
        self.root.resizable(False, False)
        self.root.configure(padx=20, pady=15, bg="#f8fafc")

        self.config = self.load_config()
        self.httpd = None
        self.icon = None

        self.create_widgets()

        self.root.protocol("WM_DELETE_WINDOW", self.hide_window)

        self.server_thread = threading.Thread(target=self.start_server, daemon=True)
        self.server_thread.start()

    def load_config(self):
        defaults = {
            "printer": "",
            "print_mode": "RAW/ESC-POS (Térmica Rápida)",
            "paper_width": "80mm",
            "open_drawer": False,
            "auto_cut": True,
            "port": DEFAULT_PORT,
        }
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, "r", encoding="utf-8") as f:
                    data = json.load(f)
                    defaults.update(data)
            except Exception:
                pass
        return defaults

    def save_config(self, show_msg=True):
        self.config["printer"] = self.printer_var.get()
        self.config["print_mode"] = self.print_mode_var.get()
        self.config["paper_width"] = self.paper_width_var.get()
        self.config["open_drawer"] = bool(self.drawer_var.get())
        self.config["auto_cut"] = bool(self.cut_var.get())

        try:
            with open(CONFIG_FILE, "w", encoding="utf-8") as f:
                json.dump(self.config, f, indent=4, ensure_ascii=False)
            if show_msg:
                messagebox.showinfo(
                    "Guardado",
                    "Configuración de impresora guardada con éxito.\n"
                    "El servidor continuará ejecutándose en segundo plano.",
                )
        except Exception as e:
            messagebox.showerror("Error", f"No se pudo guardar la configuración:\n{e}")

    def create_widgets(self):
        # Header Status
        header_frame = tk.Frame(self.root, bg="#f8fafc")
        header_frame.pack(fill=tk.X, pady=(0, 10))

        lbl_title = tk.Label(
            header_frame,
            text="SysFact+ Servidor de Impresión",
            font=("Segoe UI", 12, "bold"),
            fg="#0f172a",
            bg="#f8fafc",
        )
        lbl_title.pack(anchor=tk.W)

        self.lbl_status = tk.Label(
            header_frame,
            text=f"🟢 Servidor Activo en http://127.0.0.1:{self.config.get('port', 8080)}",
            fg="#16a34a",
            bg="#f8fafc",
            font=("Segoe UI", 9, "bold"),
        )
        self.lbl_status.pack(anchor=tk.W, pady=(2, 0))

        # Config Card
        card = tk.LabelFrame(
            self.root,
            text=" Configuración de Ticketera ",
            font=("Segoe UI", 9, "bold"),
            padx=12,
            pady=10,
            bg="#ffffff",
            fg="#334155",
        )
        card.pack(fill=tk.BOTH, expand=True, pady=5)

        # 1. Impresora
        tk.Label(
            card, text="Impresora Destino:", font=("Segoe UI", 9), bg="#ffffff", fg="#475569"
        ).grid(row=0, column=0, sticky=tk.W, pady=(0, 2))

        printers = self.get_installed_printers()
        self.printer_var = tk.StringVar()
        self.cb_printers = ttk.Combobox(
            card, textvariable=self.printer_var, values=printers, state="readonly", width=42
        )
        self.cb_printers.grid(row=1, column=0, columnspan=2, sticky=tk.W, pady=(0, 10))

        if self.config.get("printer") in printers:
            self.cb_printers.set(self.config.get("printer"))
        elif printers:
            self.cb_printers.current(0)

        # 2. Modo & Ancho
        tk.Label(
            card, text="Modo de Impresión:", font=("Segoe UI", 9), bg="#ffffff", fg="#475569"
        ).grid(row=2, column=0, sticky=tk.W, pady=(0, 2))

        self.print_mode_var = tk.StringVar()
        modes = [
            "RAW/ESC-POS (Térmica Rápida)",
            "Estándar Windows (GDI)",
            "OPOS Nativo",
        ]
        self.cb_mode = ttk.Combobox(
            card, textvariable=self.print_mode_var, values=modes, state="readonly", width=42
        )
        self.cb_mode.grid(row=3, column=0, columnspan=2, sticky=tk.W, pady=(0, 10))

        if self.config.get("print_mode") in modes:
            self.cb_mode.set(self.config.get("print_mode"))
        else:
            self.cb_mode.current(0)

        # 3. Ancho de Papel
        opts_frame = tk.Frame(card, bg="#ffffff")
        opts_frame.grid(row=4, column=0, columnspan=2, sticky=tk.W, pady=(0, 5))

        tk.Label(
            opts_frame, text="Ancho de Papel:", font=("Segoe UI", 9), bg="#ffffff", fg="#475569"
        ).pack(side=tk.LEFT, padx=(0, 10))

        self.paper_width_var = tk.StringVar(value=self.config.get("paper_width", "80mm"))
        rb80 = ttk.Radiobutton(
            opts_frame, text="80 mm (Estándar)", value="80mm", variable=self.paper_width_var
        )
        rb80.pack(side=tk.LEFT, padx=5)

        rb58 = ttk.Radiobutton(
            opts_frame, text="58 mm (Compacta)", value="58mm", variable=self.paper_width_var
        )
        rb58.pack(side=tk.LEFT, padx=5)

        # 4. Checkboxes: Abrir Gaveta & Corte de Papel
        chk_frame = tk.Frame(card, bg="#ffffff")
        chk_frame.grid(row=5, column=0, columnspan=2, sticky=tk.W, pady=(5, 0))

        self.drawer_var = tk.BooleanVar(value=self.config.get("open_drawer", False))
        chk_drawer = ttk.Checkbutton(
            chk_frame, text="Abrir gaveta de dinero", variable=self.drawer_var
        )
        chk_drawer.pack(side=tk.LEFT, padx=(0, 15))

        self.cut_var = tk.BooleanVar(value=self.config.get("auto_cut", True))
        chk_cut = ttk.Checkbutton(
            chk_frame, text="Corte de papel automático", variable=self.cut_var
        )
        chk_cut.pack(side=tk.LEFT)

        # Buttons Bar
        btn_frame = tk.Frame(self.root, bg="#f8fafc")
        btn_frame.pack(fill=tk.X, pady=(10, 0))

        btn_save = tk.Button(
            btn_frame,
            text="💾 Guardar Cambios",
            command=self.save_config,
            bg="#2563eb",
            fg="white",
            font=("Segoe UI", 9, "bold"),
            relief=tk.FLAT,
            padx=12,
            pady=6,
            cursor="hand2",
        )
        btn_save.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=(0, 5))

        btn_test = tk.Button(
            btn_frame,
            text="🖨️ Probar Ticket",
            command=self.print_test_receipt,
            bg="#059669",
            fg="white",
            font=("Segoe UI", 9, "bold"),
            relief=tk.FLAT,
            padx=12,
            pady=6,
            cursor="hand2",
        )
        btn_test.pack(side=tk.LEFT, padx=(0, 5))

        btn_min = tk.Button(
            btn_frame,
            text="🔽 Minimizar",
            command=self.hide_window,
            bg="#64748b",
            fg="white",
            font=("Segoe UI", 9),
            relief=tk.FLAT,
            padx=10,
            pady=6,
            cursor="hand2",
        )
        btn_min.pack(side=tk.LEFT)

    def get_installed_printers(self):
        printers = []
        try:
            flags = win32print.PRINTER_ENUM_LOCAL | win32print.PRINTER_ENUM_CONNECTIONS
            printers = [p[2] for p in win32print.EnumPrinters(flags)]
        except Exception:
            pass
        return printers

    def hide_window(self):
        self.root.withdraw()
        image = self.create_tray_icon()
        menu = pystray.Menu(
            pystray.MenuItem("Mostrar Configuración", self.show_window, default=True),
            pystray.MenuItem("Imprimir Ticket de Prueba", lambda icon, item: self.print_test_receipt()),
            pystray.MenuItem("Salir de SysFact+ Print", self.quit_window),
        )
        self.icon = pystray.Icon("SysFactPrinter", image, "SysFact+ Servidor de Impresión", menu)
        threading.Thread(target=self.icon.run, daemon=True).start()

    def show_window(self, icon=None, item=None):
        if self.icon:
            self.icon.stop()
        self.root.after(0, self.root.deiconify)

    def quit_window(self, icon=None, item=None):
        if self.icon:
            self.icon.stop()
        self.root.after(0, self.root.destroy)
        os._exit(0)

    def create_tray_icon(self):
        image = Image.new("RGB", (64, 64), color=(37, 99, 235))
        dc = ImageDraw.Draw(image)
        # Draw printer icon
        dc.rectangle((16, 24, 48, 48), fill=(255, 255, 255))
        dc.rectangle((22, 14, 42, 24), fill=(203, 213, 225))
        dc.rectangle((20, 36, 44, 54), fill=(241, 245, 249))
        dc.line((24, 42, 40, 42), fill=(37, 99, 235), width=2)
        dc.line((24, 46, 36, 46), fill=(37, 99, 235), width=2)
        return image

    def print_test_receipt(self):
        printer_name = self.printer_var.get() or self.config.get("printer")
        if not printer_name:
            messagebox.showwarning("Atención", "Por favor seleccione una impresora primero.")
            return

        test_data = {
            "type": "test",
            "title": "SYSFACT+ ECUADOR",
            "subtitle": "PRUEBA DE IMPRESION TERMICA",
            "fecha": "FECHA: 18/09/2026 19:30",
            "impresora": printer_name,
            "modo": self.print_mode_var.get(),
            "ancho": self.paper_width_var.get(),
            "mensaje": "¡Servidor de Impresión funcionando correctamente!",
        }

        try:
            self.execute_print(test_data)
            messagebox.showinfo("Éxito", f"Ticket de prueba enviado a '{printer_name}'.")
        except Exception as e:
            messagebox.showerror("Error de Impresión", f"No se pudo imprimir el ticket:\n{e}")

    def execute_print(self, data):
        printer_name = self.config.get("printer") or self.printer_var.get()
        if not printer_name:
            raise Exception("No hay impresora seleccionada en el servidor de impresión.")

        print_mode = self.config.get("print_mode", "RAW/ESC-POS (Térmica Rápida)")
        paper_width = self.config.get("paper_width", "80mm")
        width_cols = 48 if paper_width == "80mm" else 32

        # Format document based on payload type
        doc_type = data.get("type", "factura")

        if doc_type == "factura" or "comprobante" in data or "factura" in data:
            formatted_text = self.format_factura_sri(data, width=width_cols)
            title = data.get("emisor", {}).get("razon_social") or data.get("razon_social") or "FACTURA"
            subtitle = data.get("comprobante", "COMPROBANTE ELECTRONICO")
        elif doc_type == "cierre_caja" or "cierre" in data or "arqueo" in data:
            formatted_text = self.format_cierre_caja(data, width=width_cols)
            title = "CIERRE DE CAJA"
            subtitle = data.get("turno", "ARQUEO DE TURNO")
        elif doc_type == "test":
            formatted_text = self.format_test_ticket(data, width=width_cols)
            title = data.get("title", "SYSFACT+ PRUEBA")
            subtitle = data.get("subtitle", "")
        else:
            # Generic fallback
            formatted_text = self.format_generic_receipt(data, width=width_cols)
            title = data.get("title", "SYSFACT+")
            subtitle = data.get("subtitle", "")

        open_drawer = data.get("open_drawer", self.config.get("open_drawer", False))
        auto_cut = self.config.get("auto_cut", True)

        if "RAW" in print_mode or "ESC-POS" in print_mode:
            self.print_raw_thermal(printer_name, formatted_text, title=title, open_drawer=open_drawer, auto_cut=auto_cut)
        elif "OPOS" in print_mode:
            self.print_opos_native(printer_name, title, subtitle, formatted_text)
        else:
            self.print_gdi_windows(printer_name, title, subtitle, formatted_text)

    # -------------------------------------------------------------------------
    # FORMATTING METHODS (ESC-POS / TEXT)
    # -------------------------------------------------------------------------
    def format_factura_sri(self, data, width=48):
        # Allow payload nesting: data or data['factura']
        f = data.get("factura", data)
        emisor = f.get("emisor", {})
        cliente = f.get("cliente", {})
        detalles = f.get("detalles", f.get("items", []))
        totales = f.get("totales", f)

        divider = "=" * width
        sub_divider = "-" * width

        lines = []

        # Header: Emisor
        razon_social = (emisor.get("razon_social") or f.get("emisor_razon_social") or "EMPRESA S.A.").strip()
        nombre_comercial = (emisor.get("nombre_comercial") or f.get("emisor_nombre_comercial") or "").strip()
        ruc_emisor = (emisor.get("ruc") or f.get("emisor_ruc") or "1790000000001").strip()
        dir_matriz = (emisor.get("direccion_matriz") or f.get("emisor_direccion") or "").strip()
        regimen = (emisor.get("regimen_rimpe") or f.get("regimen_rimpe") or "").strip()
        obligado = (emisor.get("obligado_contabilidad") or f.get("obligado_contabilidad") or "NO").strip()

        lines.append(self.center_text(razon_social, width))
        if nombre_comercial and nombre_comercial != razon_social:
            lines.append(self.center_text(nombre_comercial, width))
        lines.append(self.center_text(f"RUC: {ruc_emisor}", width))
        if dir_matriz:
            for l in self.wrap_text(f"Dir: {dir_matriz}", width):
                lines.append(self.center_text(l, width))
        if obligado.upper() == "SI":
            lines.append(self.center_text("OBLIGADO A LLEVAR CONTABILIDAD: SI", width))
        if regimen and regimen != "NO APLICA":
            lines.append(self.center_text(regimen, width))

        lines.append(divider)

        # Factura Info
        num_factura = f.get("comprobante") or f.get("numero_factura") or "001-001-000000001"
        fecha_emision = f.get("fecha_emision") or f.get("fecha") or ""
        ambiente = "PRODUCCION" if str(f.get("ambiente", "1")) == "2" else "PRUEBAS"

        lines.append(f"FACTURA: {num_factura}")
        lines.append(f"FECHA:   {fecha_emision}")
        lines.append(f"AMBIENTE: {ambiente}  | EMISION: NORMAL")

        # Clave de acceso
        clave_acceso = (f.get("clave_acceso") or f.get("numero_autorizacion") or "").strip()
        if clave_acceso:
            lines.append("CLAVE DE ACCESO / AUTORIZACION SRI:")
            lines.append(f" {clave_acceso}")

        lines.append(sub_divider)

        # Cliente Info
        cli_nombre = cliente.get("razon_social") or cliente.get("nombre") or f.get("cliente_nombre") or "CONSUMIDOR FINAL"
        cli_ruc = cliente.get("identificacion") or cliente.get("ruc") or f.get("cliente_identificacion") or "9999999999999"
        cli_telf = cliente.get("telefono") or f.get("cliente_telefono") or ""
        cli_email = cliente.get("correo") or f.get("cliente_correo") or ""
        cli_dir = cliente.get("direccion") or f.get("cliente_direccion") or ""

        lines.append(f"CLIENTE: {cli_nombre}")
        lines.append(f"RUC/CI:  {cli_ruc}")
        if cli_telf:
            lines.append(f"TELF:    {cli_telf}")
        if cli_email:
            lines.append(f"EMAIL:   {cli_email}")
        if cli_dir:
            for l in self.wrap_text(f"DIR:     {cli_dir}", width):
                lines.append(l)

        lines.append(divider)

        # Table Header
        if width == 48:
            lines.append("CANT  DESCRIPCION                 P.UNIT   TOTAL")
            lines.append(sub_divider)
        else:
            lines.append("CANT DESCRIPCION    P.U.  TOTAL")
            lines.append(sub_divider)

        # Items
        for item in detalles:
            qty = float(item.get("cantidad", item.get("qty", 1)))
            nombre = item.get("descripcion", item.get("nombre", "PRODUCTO")).strip()
            p_unit = float(item.get("precio_unitario", item.get("precio", 0)))
            p_total = float(item.get("precio_total_sin_impuestos", item.get("total", qty * p_unit)))
            iva_rate = item.get("tarifa_iva", "")
            iva_str = f"({iva_rate}%)" if iva_rate else ""

            qty_str = f"{qty:g}".ljust(5 if width == 48 else 4)
            p_unit_str = f"${p_unit:.2f}".rjust(8 if width == 48 else 6)
            p_total_str = f"${p_total:.2f}".rjust(8 if width == 48 else 7)

            if width == 48:
                max_name_len = 25
                if len(nombre) <= max_name_len:
                    lines.append(f"{qty_str} {nombre.ljust(max_name_len)} {p_unit_str} {p_total_str}")
                else:
                    lines.append(f"{qty_str} {nombre[:max_name_len]} {p_unit_str} {p_total_str}")
                    lines.append(f"      {nombre[max_name_len:]}")
            else:
                max_name_len = 13
                if len(nombre) <= max_name_len:
                    lines.append(f"{qty_str}{nombre.ljust(max_name_len)} {p_unit_str}{p_total_str}")
                else:
                    lines.append(f"{qty_str}{nombre[:max_name_len]} {p_unit_str}{p_total_str}")
                    lines.append(f"    {nombre[max_name_len:]}")

        lines.append(sub_divider)

        # Totals Breakdown
        subtotal_sin_imp = float(totales.get("total_sin_impuestos", totales.get("subtotal_sin_impuestos", 0)))
        subtotal_0 = float(totales.get("base_imponible_0", totales.get("subtotal_0", 0)))
        subtotal_iva = float(totales.get("base_imponible_iva", totales.get("subtotal_iva", 0)))
        descuento = float(totales.get("total_descuento", totales.get("descuento", 0)))
        iva = float(totales.get("valor_iva", totales.get("iva", 0)))
        total = float(totales.get("importe_total", totales.get("total", subtotal_sin_imp + iva)))

        col_w = 12
        lbl_w = width - col_w

        if subtotal_0 > 0:
            lines.append(f"{'SUBTOTAL 0%:'.ljust(lbl_w)}{f'${subtotal_0:.2f}'.rjust(col_w)}")
        if subtotal_iva > 0:
            lines.append(f"{'SUBTOTAL 15%:'.ljust(lbl_w)}{f'${subtotal_iva:.2f}'.rjust(col_w)}")
        lines.append(f"{'SUBTOTAL SIN IMPUESTOS:'.ljust(lbl_w)}{f'${subtotal_sin_imp:.2f}'.rjust(col_w)}")
        if descuento > 0:
            lines.append(f"{'DESCUENTO:'.ljust(lbl_w)}{f'${descuento:.2f}'.rjust(col_w)}")
        lines.append(f"{'IVA (15%):'.ljust(lbl_w)}{f'${iva:.2f}'.rjust(col_w)}")
        lines.append(divider)
        lines.append(f"{'TOTAL A PAGAR:'.ljust(lbl_w)}{f'${total:.2f}'.rjust(col_w)}")
        lines.append(divider)

        # Payment Methods
        pagos = f.get("pagos", [])
        if pagos:
            lines.append("FORMA DE PAGO:")
            for p in pagos:
                metodo_nom = p.get("metodo_pago", p.get("nombre", "EFECTIVO"))
                p_total = float(p.get("total", total))
                lines.append(f" - {metodo_nom}: ${p_total:.2f}")

        if f.get("monto_recibido"):
            recibido = float(f.get("monto_recibido", 0))
            vuelto = float(f.get("vuelto", max(0, recibido - total)))
            lines.append(f"EFECTIVO RECIBIDO: ${recibido:.2f}")
            lines.append(f"CAMBIO / VUELTO:   ${vuelto:.2f}")

        # Footer Notes
        lines.append(sub_divider)
        cajero = f.get("usuario", f.get("cajero", "ADMIN"))
        lines.append(f"CAJERO: {cajero}")
        lines.append("")
        lines.append(self.center_text("DOCUMENTO EMITIDO ELECTRONICAMENTE", width))
        lines.append(self.center_text("Consulte su factura en www.sri.gob.ec", width))
        lines.append(self.center_text("¡GRACIAS POR SU COMPRA!", width))
        lines.append("")

        return "\n".join(lines)

    def format_cierre_caja(self, data, width=48):
        c = data.get("cierre", data)
        divider = "=" * width
        sub_divider = "-" * width
        lines = []

        lines.append(self.center_text("SYSFACT+ CIERRE DE CAJA", width))
        lines.append(self.center_text("ARQUEO DE TURNO / POS", width))
        lines.append(divider)

        lines.append(f"TURNO ID:    #{c.get('turno_id', c.get('id', 'N/A'))}")
        lines.append(f"CAJA:        {c.get('caja', 'Caja Principal')}")
        lines.append(f"CAJERO:      {c.get('usuario', c.get('cajero', 'ADMIN'))}")
        lines.append(f"APERTURA:    {c.get('fecha_apertura', 'N/A')}")
        lines.append(f"CIERRE:      {c.get('fecha_cierre', 'N/A')}")
        lines.append(sub_divider)

        m_apertura = float(c.get("monto_apertura", 0))
        v_efectivo = float(c.get("ventas_efectivo", 0))
        v_tarjetas = float(c.get("ventas_tarjetas", 0))
        v_transf = float(c.get("ventas_transferencia", 0))
        v_total = float(c.get("total_ventas", v_efectivo + v_tarjetas + v_transf))

        ingresos = float(c.get("total_ingresos", 0))
        egresos = float(c.get("total_egresos", 0))

        ef_esperado = float(c.get("efectivo_esperado", m_apertura + v_efectivo + ingresos - egresos))
        ef_real = float(c.get("efectivo_real", ef_esperado))
        diferencia = float(c.get("diferencia", ef_real - ef_esperado))

        col_w = 12
        lbl_w = width - col_w

        lines.append(f"{'MONTO APERTURA:'.ljust(lbl_w)}{f'${m_apertura:.2f}'.rjust(col_w)}")
        lines.append(f"{'VENTAS EFECTIVO:'.ljust(lbl_w)}{f'${v_efectivo:.2f}'.rjust(col_w)}")
        lines.append(f"{'VENTAS TARJETAS:'.ljust(lbl_w)}{f'${v_tarjetas:.2f}'.rjust(col_w)}")
        lines.append(f"{'VENTAS TRANSFERENCIA:'.ljust(lbl_w)}{f'${v_transf:.2f}'.rjust(col_w)}")
        lines.append(f"{'TOTAL VENTAS:'.ljust(lbl_w)}{f'${v_total:.2f}'.rjust(col_w)}")
        lines.append(sub_divider)

        if ingresos > 0:
            lines.append(f"{'OTROS INGRESOS:'.ljust(lbl_w)}{f'${ingresos:.2f}'.rjust(col_w)}")
        if egresos > 0:
            lines.append(f"{'RETIROS/GASTOS:'.ljust(lbl_w)}{f'-${egresos:.2f}'.rjust(col_w)}")

        lines.append(divider)
        lines.append(f"{'EFECTIVO ESPERADO:'.ljust(lbl_w)}{f'${ef_esperado:.2f}'.rjust(col_w)}")
        lines.append(f"{'EFECTIVO CONTADO:'.ljust(lbl_w)}{f'${ef_real:.2f}'.rjust(col_w)}")

        dif_str = f"+${diferencia:.2f}" if diferencia >= 0 else f"-${abs(diferencia):.2f}"
        lines.append(f"{'DIFERENCIA:'.ljust(lbl_w)}{dif_str.rjust(col_w)}")
        lines.append(divider)

        lines.append("")
        lines.append(self.center_text("FIRMA RESPONSABLE DE CAJA", width))
        lines.append("")
        lines.append("")
        lines.append(self.center_text("_____________________________", width))
        lines.append("")

        return "\n".join(lines)

    def format_test_ticket(self, data, width=48):
        divider = "=" * width
        lines = [
            self.center_text("SYSFACT+ PRUEBA DE IMPRESION", width),
            divider,
            f"FECHA / HORA: {data.get('fecha', '')}",
            f"IMPRESORA:    {data.get('impresora', '')}",
            f"MODO:         {data.get('modo', '')}",
            f"ANCHO PAPEL:  {data.get('ancho', '80mm')}",
            divider,
            self.center_text("CALIBRACION DE CARACTERES", width),
            "1234567890" * (width // 10),
            "-" * width,
            "TEST LINEA 1: Caracteres especiales: áéíóú Ññ $ %",
            "TEST LINEA 2: Corte de papel y gaveta OK",
            divider,
            self.center_text("¡IMPRESORA CONFIGURADA CORRECTAMENTE!", width),
            "",
        ]
        return "\n".join(lines)

    def format_generic_receipt(self, data, width=48):
        divider = "=" * width
        lines = []
        if "title" in data:
            lines.append(self.center_text(data["title"], width))
        if "subtitle" in data:
            lines.append(self.center_text(data["subtitle"], width))
        lines.append(divider)

        if "content" in data:
            lines.append(data["content"])
        else:
            for k, v in data.items():
                if k not in ["type", "title", "subtitle"]:
                    lines.append(f"{k}: {v}")

        lines.append(divider)
        return "\n".join(lines)

    # -------------------------------------------------------------------------
    # PRINT EXECUTION ENGINES
    # -------------------------------------------------------------------------
    def print_raw_thermal(self, printer_name, text_content, title="Ticket", open_drawer=False, auto_cut=True):
        ESC = b"\x1b"
        GS = b"\x1d"

        raw_data = b""

        # Initialize printer
        raw_data += ESC + b"@"

        # Cash Drawer Pulse: ESC p 0 25 250
        if open_drawer:
            raw_data += ESC + b"p\x00\x19\xfa"

        # Content in CP850 / Latin-1 for Spanish accents
        encoded_text = text_content.encode("cp850", "replace")
        raw_data += encoded_text

        # Feed lines
        raw_data += b"\n\n\n\n"

        # Auto-cut paper: GS V 65 0 (Full/Partial Cut)
        if auto_cut:
            raw_data += GS + b"V\x41\x00"

        hPrinter = win32print.OpenPrinter(printer_name)
        try:
            win32print.StartDocPrinter(hPrinter, 1, (title, None, "RAW"))
            win32print.StartPagePrinter(hPrinter)
            win32print.WritePrinter(hPrinter, raw_data)
            win32print.EndPagePrinter(hPrinter)
            win32print.EndDocPrinter(hPrinter)
        finally:
            win32print.ClosePrinter(hPrinter)

    def print_gdi_windows(self, printer_name, title, subtitle, text_content):
        hDC = win32ui.CreateDC()
        hDC.CreatePrinterDC(printer_name)
        dpi_y = hDC.GetDeviceCaps(win32con.LOGPIXELSY)
        hDC.StartDoc(title)
        hDC.StartPage()

        font_title = win32ui.CreateFont({"name": "Arial", "height": int(dpi_y * 0.18), "weight": 800})
        font_body = win32ui.CreateFont({"name": "Courier New", "height": int(dpi_y * 0.11)})

        margin_x = int(dpi_y * 0.08)
        y_pos = int(dpi_y * 0.08)

        if title:
            hDC.SelectObject(font_title)
            hDC.TextOut(margin_x, y_pos, title)
            y_pos += int(dpi_y * 0.22)

        hDC.SelectObject(font_body)
        for line in text_content.split("\n"):
            hDC.TextOut(margin_x, y_pos, line)
            y_pos += int(dpi_y * 0.14)

        hDC.EndPage()
        hDC.EndDoc()
        hDC.DeleteDC()

    def print_opos_native(self, printer_name, title, subtitle, text_content):
        import subprocess
        import tempfile

        vbs_code = f"""On Error Resume Next
Set opos = CreateObject("OPOS.POSPrinter")
opos.Open "{printer_name}"
opos.ClaimDevice 1000
opos.DeviceEnabled = True
Dim ESC: ESC = Chr(27)
Dim txt
txt = ESC & "|cA" & ESC & "|bC" & "{title}" & vbCrLf & vbCrLf
txt = txt & ESC & "|N" & "{text_content.replace(chr(10), '" & vbCrLf & "')}" & vbCrLf & vbCrLf
txt = txt & ESC & "|100fP"
opos.PrintNormal 2, txt
opos.DeviceEnabled = False
opos.ReleaseDevice
opos.Close
WScript.Quit 0
"""
        vbs_file = os.path.join(tempfile.gettempdir(), "sysfact_opos.vbs")
        with open(vbs_file, "w", encoding="ansi", errors="replace") as f:
            f.write(vbs_code)
        cscript = r"C:\Windows\SysWOW64\cscript.exe" if os.path.exists(r"C:\Windows\SysWOW64\cscript.exe") else "cscript.exe"
        subprocess.run([cscript, "//nologo", "//B", vbs_file])

    # -------------------------------------------------------------------------
    # HELPER STRING UTILITIES
    # -------------------------------------------------------------------------
    def center_text(self, text, width):
        text = text.strip()
        if len(text) >= width:
            return text[:width]
        pad = (width - len(text)) // 2
        return " " * pad + text

    def wrap_text(self, text, width):
        words = text.split()
        lines = []
        curr = ""
        for w in words:
            if len(curr) + len(w) + 1 <= width:
                curr = f"{curr} {w}" if curr else w
            else:
                lines.append(curr)
                curr = w
        if curr:
            lines.append(curr)
        return lines

    # -------------------------------------------------------------------------
    # HTTP SERVER & API ENDPOINTS
    # -------------------------------------------------------------------------
    def start_server(self):
        port = int(self.config.get("port", DEFAULT_PORT))
        server_address = ("127.0.0.1", port)
        outer_self = self

        class SysFactHttpHandler(BaseHTTPRequestHandler):
            def log_message(self, format, *args):
                pass  # Silent console logs

            def do_OPTIONS(self):
                self.send_response(200, "ok")
                self.send_header("Access-Control-Allow-Origin", "*")
                self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
                self.send_header("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, Accept")
                self.end_headers()

            def do_GET(self):
                if self.path == "/status" or self.path == "/":
                    self.send_json(
                        200,
                        {
                            "status": "online",
                            "system": "SysFact+ Print Server",
                            "version": "2.0",
                            "printer": outer_self.config.get("printer"),
                            "print_mode": outer_self.config.get("print_mode"),
                            "paper_width": outer_self.config.get("paper_width"),
                        },
                    )
                elif self.path == "/printers":
                    printers = outer_self.get_installed_printers()
                    self.send_json(200, {"success": True, "printers": printers})
                else:
                    self.send_json(404, {"success": False, "message": "Endpoint no encontrado."})

            def do_POST(self):
                content_length = int(self.headers.get("Content-Length", 0))
                post_data = self.rfile.read(content_length) if content_length > 0 else b"{}"

                try:
                    payload = json.loads(post_data.decode("utf-8"))
                except Exception:
                    payload = {}

                if self.path in ["/print_receipt", "/print_factura", "/print_cierre_caja", "/test_print"]:
                    try:
                        outer_self.execute_print(payload)
                        self.send_json(
                            200,
                            {
                                "status": "ok",
                                "success": True,
                                "message": "Comprobante impreso exitosamente en la ticketera local.",
                                "printer": outer_self.config.get("printer"),
                            },
                        )
                    except Exception as e:
                        self.send_json(500, {"status": "error", "success": False, "message": f"Error al imprimir: {str(e)}"})
                elif self.path == "/open_drawer":
                    try:
                        printer_name = outer_self.config.get("printer")
                        if not printer_name:
                            raise Exception("No hay impresora seleccionada.")
                        outer_self.print_raw_thermal(printer_name, "", open_drawer=True, auto_cut=False)
                        self.send_json(200, {"status": "ok", "success": True, "message": "Pulso de apertura de gaveta enviado."})
                    except Exception as e:
                        self.send_json(500, {"status": "error", "success": False, "message": f"Error al abrir gaveta: {str(e)}"})
                else:
                    self.send_json(404, {"success": False, "message": "Ruta no encontrada."})

            def send_json(self, code, data):
                self.send_response(code)
                self.send_header("Access-Control-Allow-Origin", "*")
                self.send_header("Content-Type", "application/json; charset=utf-8")
                self.end_headers()
                self.wfile.write(json.dumps(data, ensure_ascii=False).encode("utf-8"))

        try:
            self.httpd = HTTPServer(server_address, SysFactHttpHandler)
            self.httpd.serve_forever()
        except Exception as e:
            self.root.after(
                0,
                lambda: messagebox.showerror(
                    "Error de Puerto",
                    f"No se pudo iniciar el servidor en el puerto {port}.\n"
                    f"Verifique si otra instancia de SysFact+ o el Módulo Billetera está abierta.\n\nDetalles: {e}",
                ),
            )


def main():
    root = tk.Tk()
    app = SysFactPrintServer(root)
    root.mainloop()


if __name__ == "__main__":
    main()
